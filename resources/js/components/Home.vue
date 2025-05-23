<template>
  <div class="home-wrapper">
    <div class="home-container">
      <!-- User Header -->
      <div class="user-header">
        <div v-if="user" class="user-info">
          <h1>{{ getGreeting() }}</h1>
          <p class="user-subtitle">{{ user.name }}</p>
        </div>
        <div v-else class="user-info">
          <p>Loading user information...</p>
        </div>
        
        <div class="header-actions">
          <button @click="refreshData" :disabled="loading" class="refresh-btn">
            {{ loading ? '⟳' : '↻' }}
          </button>
          <button @click="handleLogout" :disabled="loggingOut" class="logout-btn">
            {{ loggingOut ? '...' : '⏻' }}
          </button>
        </div>
      </div>

      <!-- Quick Stats -->
      <div v-if="lists.length > 0" class="quick-stats">
        <div class="stat-item">
          <span class="stat-number">{{ totalLists }}</span>
          <span class="stat-label">Lists</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ totalPendingItems }}</span>
          <span class="stat-label">Pending</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ totalCompletedToday }}</span>
          <span class="stat-label">Completed Today</span>
        </div>
      </div>

      <!-- Pending Share Invitations -->
      <div v-if="pendingShares.length > 0" class="pending-shares-section">
        <div class="section-header">
          <h2>📨 Pending Invitations</h2>
        </div>
        
        <div class="pending-shares-list">
          <div 
            v-for="share in pendingShares" 
            :key="share.id" 
            class="pending-share-item"
          >
            <div class="share-info">
              <h4>{{ share.reusable_list.name }}</h4>
              <p>Shared by {{ share.shared_by?.name || 'Unknown' }}</p>
              <span class="permission-badge">{{ share.permission_level }}</span>
            </div>
            <div class="share-actions">
              <button 
                @click="acceptShare(share)" 
                :disabled="processingShare === share.id"
                class="accept-btn"
              >
                ✓ Accept
              </button>
              <button 
                @click="declineShare(share)" 
                :disabled="processingShare === share.id"
                class="decline-btn"
              >
                ✗ Decline
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Lists Section -->
      <div class="lists-section">
        <div class="section-header">
          <h2>My Lists</h2>
          <button @click="showCreateList = true" class="add-list-btn">
            + New List
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading && lists.length === 0" class="loading-state">
          <p>Loading your lists...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading && lists.length === 0" class="empty-state">
          <div class="empty-icon">📝</div>
          <h3>No Lists Yet</h3>
          <p>Create your first list to get started organizing your reminders</p>
          <button @click="showCreateList = true" class="create-first-list-btn">
            Create Your First List
          </button>
        </div>

        <!-- Lists Grid -->
        <div v-else class="lists-grid">
          <div 
            v-for="list in sortedLists" 
            :key="list.id" 
            class="list-card"
            @click="openList(list)"
            :style="{ borderLeftColor: list.color }"
          >
            <div class="list-header">
              <div class="list-icon">{{ getListIcon(list.icon) }}</div>
              <div class="list-info">
                <h3 class="list-name">{{ list.name }}</h3>
              </div>
              <div class="list-actions">
                <button 
                  @click.stop="shareList(list)" 
                  class="share-btn"
                  :title="'Share list'"
                >
                  👥
                </button>
                <button 
                  @click.stop="togglePin(list)" 
                  :disabled="pinningList === list.id"
                  class="pin-btn"
                  :class="{ 'pinned': list.is_pinned }"
                  :title="list.is_pinned ? 'Unpin list' : 'Pin list'"
                >
                  {{ list.is_pinned ? '📌' : '📍' }}
                </button>
                <div v-if="list.is_shared" class="shared-indicator">👥</div>
              </div>
            </div>
            
            <div class="list-stats">
              <div class="pending-count">
                {{ list.item_counts.pending }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Shared Lists Section -->
      <div v-if="sharedLists.length > 0" class="shared-lists-section">
        <div class="section-header">
          <h2>Shared With Me</h2>
        </div>
        
        <div class="lists-grid">
          <div 
            v-for="list in sharedLists" 
            :key="'shared-' + list.id" 
            class="list-card shared-list"
            @click="openList(list)"
            :style="{ borderLeftColor: list.color }"
          >
            <div class="list-header">
              <div class="list-icon">{{ getListIcon(list.icon) }}</div>
              <div class="list-info">
                <h3 class="list-name">{{ list.name }}</h3>
                <p class="shared-by">Shared by {{ list.user?.name || 'Unknown' }}</p>
              </div>
              <div class="list-actions">
                <button 
                  @click.stop="togglePin(list)" 
                  :disabled="pinningList === list.id"
                  class="pin-btn"
                  :class="{ 'pinned': list.is_pinned }"
                  :title="list.is_pinned ? 'Unpin list' : 'Pin list'"
                >
                  {{ list.is_pinned ? '📌' : '📍' }}
                </button>
                <div class="shared-indicator">👥</div>
              </div>
            </div>
            
            <div class="list-stats">
              <div v-if="list.item_counts.pending > 0" class="pending-count">
                {{ list.item_counts.pending }} pending
              </div>
              <div v-if="list.item_counts.total > 0" class="total-count">
                {{ list.item_counts.total }} total
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div v-if="recentActivity.length > 0" class="recent-activity-section">
        <div class="section-header">
          <h2>Recent Activity</h2>
        </div>
        
        <div class="activity-list">
          <div 
            v-for="activity in recentActivity" 
            :key="activity.id" 
            class="activity-item"
          >
            <div class="activity-icon">{{ activity.type === 'completed' ? '✓' : '+' }}</div>
            <div class="activity-content">
              <p class="activity-text">{{ activity.text }}</p>
              <span class="activity-time">{{ formatTimeAgo(activity.timestamp) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <button @click="showCreateList = true" class="quick-action-btn">
          <span class="action-icon">📝</span>
          <span>New List</span>
        </button>
        <button @click="refreshData" class="quick-action-btn" :disabled="loading">
          <span class="action-icon">↻</span>
          <span>Refresh</span>
        </button>
        <button @click="testWebSocket" class="quick-action-btn">
          <span class="action-icon">🔌</span>
          <span>Test WS</span>
        </button>
      </div>
    </div>

    <!-- Create List Modal -->
    <div v-if="showCreateList" class="modal-overlay" @click="closeCreateList">
      <div class="modal-content" @click.stop>
        <h3>Create New List</h3>
        <form @submit.prevent="createList">
          <div class="form-group">
            <label for="listName">List Name</label>
            <input 
              id="listName"
              v-model="newList.name" 
              type="text" 
              placeholder="Enter list name"
              required
            >
          </div>
          <div class="form-group">
            <label for="listDescription">Description (optional)</label>
            <textarea 
              id="listDescription"
              v-model="newList.description" 
              placeholder="Enter description"
              rows="3"
            ></textarea>
          </div>
          <div class="form-group">
            <label for="listColor">Color</label>
            <select id="listColor" v-model="newList.color">
              <option value="#007AFF">Blue (Default)</option>
              <option value="#FF3B30">Red</option>
              <option value="#34C759">Green</option>
              <option value="#FF9500">Orange</option>
              <option value="#AF52DE">Purple</option>
              <option value="#FF2D92">Pink</option>
              <option value="#5AC8FA">Light Blue</option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeCreateList" class="cancel-btn">Cancel</button>
            <button type="submit" :disabled="creatingList" class="create-btn">
              {{ creatingList ? 'Creating...' : 'Create List' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Share List Modal -->
    <div v-if="showShareModal" class="modal-overlay" @click="closeShareModal">
      <div class="modal-content" @click.stop>
        <h3>Share "{{ selectedList?.name }}"</h3>
        <form @submit.prevent="submitShare">
          <div class="form-group">
            <label for="shareEmail">Email Address</label>
            <input 
              id="shareEmail"
              v-model="shareForm.email" 
              type="email" 
              placeholder="Enter email address"
              required
            >
          </div>
          <div class="form-group">
            <label for="permissionLevel">Permission Level</label>
            <select id="permissionLevel" v-model="shareForm.permission_level" required>
              <option value="view">View Only</option>
              <option value="edit">Can Edit</option>
              <option value="admin">Admin (can share)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input 
                type="checkbox" 
                v-model="shareForm.can_share"
              >
              Allow this user to share with others
            </label>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeShareModal" class="cancel-btn">Cancel</button>
            <button type="submit" :disabled="sharingList" class="share-btn">
              {{ sharingList ? 'Sharing...' : 'Share List' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import authService from '../services/auth'
import listService from '../services/list'

export default {
  name: 'Home',
  data() {
    return {
      user: null,
      lists: [],
      loading: false,
      loggingOut: false,
      showCreateList: false,
      creatingList: false,
      newList: {
        name: '',
        description: '',
        color: '#007AFF',
        icon: 'list.bullet',
        is_public: false,
        sort_order: 0
      },
      recentActivity: [], // Will be populated with recent completions/additions
      error: null,
      pinningList: null,
      showShareModal: false,
      selectedList: null,
      shareForm: {
        email: '',
        permission_level: 'view',
        can_share: false
      },
      processingShare: null,
      sharingList: false,
      pendingShares: []
    }
  },
  
  computed: {
    sortedLists() {
      return this.lists
        .filter(list => list.user_id === this.user?.id)
        .sort((a, b) => {
          // First sort by pinned status (pinned first)
          if (a.is_pinned !== b.is_pinned) {
            return b.is_pinned - a.is_pinned
          }
          // Then sort by sort_order
          return a.sort_order - b.sort_order
        })
    },
    
    sharedLists() {
      return this.lists
        .filter(list => list.user_id !== this.user?.id)
        .sort((a, b) => {
          // First sort by pinned status (pinned first)
          if (a.is_pinned !== b.is_pinned) {
            return b.is_pinned - a.is_pinned
          }
          // Then sort by name
          return a.name.localeCompare(b.name)
        })
    },
    
    totalLists() {
      return this.sortedLists.length
    },
    
    totalPendingItems() {
      return this.lists.reduce((total, list) => {
        return total + (list.item_counts?.pending || 0)
      }, 0)
    },
    
    totalCompletedToday() {
      // This would need actual completion tracking by date
      // For now, showing completed items from all lists
      return this.lists.reduce((total, list) => {
        return total + (list.item_counts?.completed || 0)
      }, 0)
    }
  },
  
  mounted() {
    this.initializeData()
    this.setupRealTimeUpdates()
  },
  
  beforeUnmount() {
    this.cleanupRealTimeUpdates()
  },
  
  methods: {
    async initializeData() {
      // Get user from auth service
      this.user = authService.getUser()
      
      // If not available, try to fetch it
      if (!this.user) {
        await this.refreshUser()
      }
      
      // Load lists and pending shares
      await Promise.all([
        this.loadLists(),
        this.loadPendingShares()
      ])
    },
    
    async refreshUser() {
      try {
        const currentUser = await authService.getCurrentUser()
        this.user = currentUser
      } catch (error) {
        console.error('Failed to refresh user:', error)
        this.error = 'Failed to load user information'
      }
    },
    
    async loadLists() {
      if (this.loading) return
      
      this.loading = true
      this.error = null
      
      try {
        const listsWithCounts = await listService.getListsWithCounts()
        this.lists = listsWithCounts
        
        // Generate some mock recent activity for now
        this.generateRecentActivity()
        
      } catch (error) {
        console.error('Failed to load lists:', error)
        this.error = error.message
      } finally {
        this.loading = false
      }
    },
    
    async loadPendingShares() {
      try {
        const shares = await listService.getMyShares()
        console.log('Pending shares debug info:', shares.debug)
        console.log('Pending shares data:', shares)
        this.pendingShares = shares.pending || []
      } catch (error) {
        console.error('Failed to load pending shares:', error)
      }
    },
    
    async refreshData() {
      await Promise.all([
        this.refreshUser(),
        this.loadLists(),
        this.loadPendingShares()
      ])
    },
    
    setupRealTimeUpdates() {
      if (!window.Echo || !this.user) return
      
      console.log('🔌 Setting up real-time updates for user:', this.user.id)
      
      // Listen for list updates on the user's private channel
      const channel = window.Echo.private(`user.${this.user.id}`)
      
      console.log('📡 Subscribing to channel:', `user.${this.user.id}`)
      
      channel
        .listen('list.updated', (e) => {
          console.log('📝 List updated event received:', e)
          this.handleListUpdate(e.list)
        })
        .listen('list.shared', (e) => {
          console.log('👥 List shared event received:', e)
          this.handleListShared(e.share)
        })
        .listen('list.item.updated', (e) => {
          console.log('✏️ List item updated event received:', e)
          this.handleListItemUpdate(e)
        })
        
      // Debug channel subscription
      channel.subscribed(() => {
        console.log('✅ Successfully subscribed to user channel')
      })
      
      channel.error((error) => {
        console.log('❌ Channel subscription error:', error)
      })
    },
    
    cleanupRealTimeUpdates() {
      if (!window.Echo || !this.user) return
      
      window.Echo.leave(`user.${this.user.id}`)
    },
    
    handleListUpdate(updatedList) {
      const listIndex = this.lists.findIndex(l => l.id === updatedList.id)
      if (listIndex !== -1) {
        // Update existing list
        this.lists[listIndex] = { ...this.lists[listIndex], ...updatedList }
      } else {
        // Add new list if it doesn't exist
        this.lists.push(updatedList)
      }
      
      // Refresh item counts for the updated list
      this.refreshListCounts(updatedList.id)
    },
    
    handleListShared(share) {
      // Add to pending shares
      this.pendingShares.push(share)
    },
    
    handleListItemUpdate(data) {
      // Refresh item counts for the affected list
      this.refreshListCounts(data.list_id)
    },
    
    async refreshListCounts(listId) {
      try {
        const items = await listService.getListItems(listId)
        const totalItems = items.length
        const completedItems = items.filter(item => item.is_completed).length
        const pendingItems = totalItems - completedItems
        
        const listIndex = this.lists.findIndex(l => l.id === listId)
        if (listIndex !== -1) {
          this.lists[listIndex].item_counts = {
            total: totalItems,
            completed: completedItems,
            pending: pendingItems
          }
        }
      } catch (error) {
        console.error('Failed to refresh list counts:', error)
      }
    },
    
    async createList() {
      if (!this.newList.name.trim()) return
      
      this.creatingList = true
      
      try {
        const createdList = await listService.createList({
          ...this.newList,
          name: this.newList.name.trim()
        })
        
        // Add to local lists with empty counts
        this.lists.push({
          ...createdList,
          item_counts: {
            total: 0,
            completed: 0,
            pending: 0
          }
        })
        
        this.closeCreateList()
        
      } catch (error) {
        console.error('Failed to create list:', error)
        this.error = error.message
      } finally {
        this.creatingList = false
      }
    },
    
    openList(list) {
      this.$router.push(`/lists/${list.id}`)
    },
    
    closeCreateList() {
      this.showCreateList = false
      this.newList = {
        name: '',
        description: '',
        color: '#007AFF',
        icon: 'list.bullet',
        is_public: false,
        sort_order: 0
      }
    },
    
    async handleLogout() {
      this.loggingOut = true
      
      try {
        await authService.logout()
        this.$router.push('/login')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.loggingOut = false
      }
    },
    
    getGreeting() {
      const hour = new Date().getHours()
      if (hour < 12) return 'Good Morning'
      if (hour < 17) return 'Good Afternoon'
      return 'Good Evening'
    },
    
    getListIcon(iconName) {
      // Map iOS SF Symbols to emoji for now
      const iconMap = {
        'list.bullet': '📋',
        'cart': '🛒',
        'house': '🏠',
        'briefcase': '💼',
        'heart': '❤️',
        'star': '⭐',
        'flag': '🏴',
        'bookmark': '📖',
        'person': '👤',
        'gear': '⚙️'
      }
      return iconMap[iconName] || '📋'
    },
    
    formatTimeAgo(timestamp) {
      const now = new Date()
      const time = new Date(timestamp)
      const diffInMinutes = Math.floor((now - time) / (1000 * 60))
      
      if (diffInMinutes < 1) return 'Just now'
      if (diffInMinutes < 60) return `${diffInMinutes}m ago`
      
      const diffInHours = Math.floor(diffInMinutes / 60)
      if (diffInHours < 24) return `${diffInHours}h ago`
      
      const diffInDays = Math.floor(diffInHours / 24)
      return `${diffInDays}d ago`
    },
    
    generateRecentActivity() {
      // Mock recent activity - in real app this would come from API
      this.recentActivity = [
        {
          id: 1,
          type: 'completed',
          text: 'Completed "Buy groceries" in Grocery List',
          timestamp: new Date(Date.now() - 30 * 60 * 1000) // 30 min ago
        },
        {
          id: 2,
          type: 'added',
          text: 'Added "Call dentist" to Personal Tasks',
          timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000) // 2 hours ago
        }
      ].slice(0, 5) // Show max 5 recent activities
    },
    
    async togglePin(list) {
      if (this.pinningList === list.id) return
      
      this.pinningList = list.id
      const newPinnedStatus = !list.is_pinned
      
      try {
        await listService.pinList(list.id, newPinnedStatus)
        
        // Update the local list data
        const listIndex = this.lists.findIndex(l => l.id === list.id)
        if (listIndex !== -1) {
          this.lists[listIndex].is_pinned = newPinnedStatus
        }
        
      } catch (error) {
        console.error('Failed to toggle pin status:', error)
        this.error = error.message
      } finally {
        this.pinningList = null
      }
    },
    
    async shareList(list) {
      this.selectedList = list
      this.showShareModal = true
    },
    
    async submitShare() {
      if (!this.shareForm.email.trim()) return
      
      this.sharingList = true
      
      try {
        await listService.shareList(this.selectedList.id, this.shareForm)
        
        // Update the local list data
        const listIndex = this.lists.findIndex(l => l.id === this.selectedList.id)
        if (listIndex !== -1) {
          this.lists[listIndex].is_shared = true
        }
        
        this.closeShareModal()
        
      } catch (error) {
        console.error('Failed to share list:', error)
        this.error = error.message
      } finally {
        this.sharingList = false
      }
    },
    
    closeShareModal() {
      this.showShareModal = false
      this.selectedList = null
      this.shareForm = {
        email: '',
        permission_level: 'view',
        can_share: false
      }
    },
    
    async acceptShare(share) {
      this.processingShare = share.id
      
      try {
        await listService.acceptShare(share.id)
        
        // Refresh lists to include the newly accepted shared list
        await this.loadLists()
        
        // Remove from pending shares
        this.pendingShares = this.pendingShares.filter(s => s.id !== share.id)
        
      } catch (error) {
        console.error('Failed to accept share:', error)
        this.error = error.message
      } finally {
        this.processingShare = null
      }
    },
    
    async declineShare(share) {
      this.processingShare = share.id
      
      try {
        await listService.declineShare(share.id)
        
        // Remove from pending shares
        this.pendingShares = this.pendingShares.filter(s => s.id !== share.id)
        
      } catch (error) {
        console.error('Failed to decline share:', error)
        this.error = error.message
      } finally {
        this.processingShare = null
      }
    },
    
    testWebSocket() {
      console.log('🧪 Testing WebSocket connection...')
      console.log('Echo instance:', window.Echo)
      console.log('Connection state:', window.Echo?.connector?.pusher?.connection?.state)
      
      if (window.Echo && this.user) {
        // Test by trying to subscribe to a test channel
        const testChannel = window.Echo.private(`user.${this.user.id}`)
        console.log('Test channel created:', testChannel)
      }
    }
  }
}
</script>

<style scoped>
.list-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pin-btn, .share-btn {
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s ease;
  opacity: 0.6;
}

.pin-btn:hover, .share-btn:hover {
  opacity: 1;
  background-color: rgba(0, 0, 0, 0.1);
}

.pin-btn:disabled, .share-btn:disabled {
  cursor: not-allowed;
  opacity: 0.3;
}

.pin-btn.pinned {
  opacity: 1;
  background-color: rgba(255, 215, 0, 0.2);
}

.share-btn {
  color: #007AFF;
}

.share-btn:hover {
  background-color: rgba(0, 122, 255, 0.1);
}

.shared-indicator {
  font-size: 14px;
  opacity: 0.7;
}

.list-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
}

.list-info {
  flex: 1;
}

.list-name {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
}

.list-description {
  margin: 0;
  font-size: 14px;
  color: #666;
  opacity: 0.8;
}

/* Pending Shares Section */
.pending-shares-section {
  margin-bottom: 32px;
}

.pending-shares-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pending-share-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background: #fff;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.share-info h4 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
}

.share-info p {
  margin: 0 0 8px 0;
  color: #666;
  font-size: 14px;
}

.permission-badge {
  display: inline-block;
  padding: 2px 8px;
  background: #e3f2fd;
  color: #1976d2;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: capitalize;
}

.share-actions {
  display: flex;
  gap: 8px;
}

.accept-btn, .decline-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.accept-btn {
  background: #4caf50;
  color: white;
}

.accept-btn:hover {
  background: #45a049;
}

.decline-btn {
  background: #f44336;
  color: white;
}

.decline-btn:hover {
  background: #da190b;
}

.accept-btn:disabled, .decline-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 24px;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content h3 {
  margin: 0 0 20px 0;
  font-size: 20px;
  font-weight: 600;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #007AFF;
  box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.checkbox-label {
  display: flex !important;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: auto !important;
  margin: 0;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

.cancel-btn, .create-btn, .share-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cancel-btn {
  background: #f5f5f5;
  color: #666;
}

.cancel-btn:hover {
  background: #e0e0e0;
}

.create-btn, .share-btn {
  background: #007AFF;
  color: white;
}

.create-btn:hover, .share-btn:hover {
  background: #0056b3;
}

.create-btn:disabled, .share-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
  .pending-share-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .share-actions {
    width: 100%;
    justify-content: flex-end;
  }
  
  .modal-content {
    margin: 20px;
    width: calc(100% - 40px);
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .cancel-btn, .create-btn, .share-btn {
    width: 100%;
  }
}
</style> 