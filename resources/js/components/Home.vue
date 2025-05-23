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
                <p v-if="list.description" class="list-description">{{ list.description }}</p>
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
                <div v-if="list.is_shared" class="shared-indicator">👥</div>
              </div>
            </div>
            
            <div class="list-stats">
              <div v-if="list.item_counts.pending > 0" class="pending-count">
                {{ list.item_counts.pending }} pending
              </div>
              <div v-if="list.item_counts.total > 0" class="total-count">
                {{ list.item_counts.total }} total
              </div>
              <div v-if="list.item_counts.total === 0" class="empty-list">
                Empty list
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
      </div>
    </div>

    <!-- Create List Modal (Simple for now) -->
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
      pinningList: null
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
  },
  
  methods: {
    async initializeData() {
      // Get user from auth service
      this.user = authService.getUser()
      
      // If not available, try to fetch it
      if (!this.user) {
        await this.refreshUser()
      }
      
      // Load lists
      await this.loadLists()
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
    
    async refreshData() {
      await Promise.all([
        this.refreshUser(),
        this.loadLists()
      ])
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

.pin-btn {
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s ease;
  opacity: 0.6;
}

.pin-btn:hover {
  opacity: 1;
  background-color: rgba(0, 0, 0, 0.1);
}

.pin-btn:disabled {
  cursor: not-allowed;
  opacity: 0.3;
}

.pin-btn.pinned {
  opacity: 1;
  background-color: rgba(255, 215, 0, 0.2);
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
</style> 