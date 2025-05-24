<template>
  <div class="list-wrapper">
    <div class="list-container">
      <!-- Header -->
      <div class="list-header">
        <div class="header-top">
          <button @click="goBack" class="back-btn">← Back</button>
          <div class="header-actions">
            <button @click="openShareModal" v-if="canShare" class="share-btn">📤</button>
            <button @click="showEditModal = true" v-if="canEdit" class="edit-btn">✏️</button>
            <button @click="refreshList" :disabled="loading" class="refresh-btn">{{ loading ? '⟳' : '↻' }}</button>
          </div>
        </div>

        <div v-if="list" class="list-info">
          <div class="list-icon" :style="{ color: list.color }">{{ getListIcon(list.icon) }}</div>
          <div>
            <h1 class="list-name">{{ list.name }}</h1>
            <p v-if="list.description" class="list-description">{{ list.description }}</p>
            <div class="list-meta">
              <span v-if="list.is_shared" class="shared-badge">👥 Shared</span>
              <span class="items-count">{{ totalItems }} {{ totalItems === 1 ? 'item' : 'items' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading && items.length === 0" class="loading-state">
        <p>Loading items...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading && items.length === 0 && !canEdit" class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No Items Yet</h3>
        <p>Add your first item to get started</p>
      </div>

      <!-- Items List -->
      <div v-else class="items-section">
        <!-- Pending Items -->
        <div class="items-group">
          <h3 class="group-title">To Do</h3>
          <div class="items-list">
            <!-- Fake empty item for adding new items -->
            <div v-if="canEdit" class="item-card add-new-item" :class="{ 'editing': editingItem === 'new' }">
              <div v-if="editingItem !== 'new'" class="item-content" @click="startAddNew">
                <div class="add-new-checkbox">
                  <div class="checkbox empty"></div>
                </div>
                <div class="item-info add-new-info">
                  <h4 class="item-title add-new-title">Add new item...</h4>
                </div>
              </div>
              
              <!-- Add new item form -->
              <div v-else class="item-content">
                <div class="add-new-checkbox">
                  <div class="checkbox empty"></div>
                </div>
                <div class="item-info">
                  <input 
                    v-model="newItem.title" 
                    ref="addInput" 
                    type="text" 
                    placeholder="Add new item..." 
                    class="inline-edit-input" 
                    @blur="handleAddBlur"
                    @keyup.enter="addItem" 
                    @keyup.escape="cancelAdd"
                    @input="handleAutocomplete"
                  />
                  
                  <!-- Autocomplete dropdown -->
                  <div v-if="showAutocomplete && (autocompleteResults.length > 0 || autocompleteLoading)" class="autocomplete-dropdown">
                    <div v-if="autocompleteLoading" class="autocomplete-loading">Searching...</div>
                    <div v-for="suggestion in autocompleteResults" :key="suggestion.item_title" class="autocomplete-item">
                      <div class="suggestion-content" @mousedown.prevent="selectSuggestion(suggestion)">
                        <div class="suggestion-title" v-html="suggestion.item_title"></div>
                        <div class="suggestion-meta">Used {{ suggestion.usage_count }} times • {{ Math.round(suggestion.completion_rate) }}% completion rate</div>
                      </div>
                      <button @mousedown.prevent="deleteSuggestion(suggestion)" class="delete-suggestion-btn" title="Delete this suggestion">🗑️</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Regular pending items -->
            <div v-for="item in sortedPendingItems" :key="item.id" class="item-card" :class="{ 'editing': editingItem === item.id, 'deleting': item.deleting, 'optimistic': item.isOptimistic }"
                 @touchstart="handleTouchStart($event, item)"
                 @touchmove="handleTouchMove($event, item)"
                 @touchend="handleTouchEnd($event, item)">
              <div v-if="editingItem !== item.id" class="item-content">
                <button @click="toggleComplete(item)" class="complete-btn" :disabled="item.updating || item.deleting || item.isOptimistic">
                  <div class="checkbox">{{ item.updating ? '⟳' : (item.is_completed ? '✓' : '') }}</div>
                </button>

                <div class="item-info" @click="!item.deleting && !item.isOptimistic ? startEdit(item) : null">
                  <h4 class="item-title">{{ item.title }}</h4>
                  <div v-if="item.usage_count > 1" class="item-meta">Used {{ item.usage_count }} times</div>
                  <!-- <div v-if="item.isOptimistic" class="item-meta optimistic-indicator">Processing...</div> -->
                </div>

                <div class="item-actions">
                  <button v-if="canEdit" @click="deleteItem(item)" :disabled="item.deleting || item.isOptimistic" class="delete-item-btn">🗑️</button>
                </div>
              </div>

              <!-- Inline Edit -->
              <div v-else class="item-content">
                <button @click="toggleComplete(item)" class="complete-btn" :disabled="item.updating || item.deleting || item.isOptimistic">
                  <div class="checkbox">{{ item.updating ? '⟳' : (item.is_completed ? '✓' : '') }}</div>
                </button>

                <div class="item-info">
                  <input 
                    v-model="editingItemData.title" 
                    type="text" 
                    class="inline-edit-input" 
                    @blur="saveEdit" 
                    @keyup.enter="saveEdit" 
                    @keyup.escape="cancelEdit" 
                    ref="editInput" 
                  />
                  <div v-if="item.usage_count > 1" class="item-meta">Used {{ item.usage_count }} times</div>
                </div>

                <div class="item-actions">
                  <button v-if="canEdit" @click="deleteItem(item)" :disabled="item.deleting || item.isOptimistic" class="delete-item-btn">🗑️</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Completed Items -->
        <div v-if="completedItems.length > 0" class="items-group completed-group">
          <button @click="showCompleted = !showCompleted" class="group-toggle">
            <h3 class="group-title">
              Completed ({{ completedItems.length }})
              <span class="toggle-icon">{{ showCompleted ? '⌄' : '⌃' }}</span>
            </h3>
          </button>

          <div v-show="showCompleted" class="items-list">
            <div v-for="item in sortedCompletedItems" :key="item.id" class="item-card completed" :class="{ 'deleting': item.deleting, 'optimistic': item.isOptimistic }"
                 @touchstart="handleTouchStart($event, item)"
                 @touchmove="handleTouchMove($event, item)"
                 @touchend="handleTouchEnd($event, item)">
              <div class="item-content">
                <button @click="toggleComplete(item)" class="complete-btn" :disabled="item.updating || item.deleting || item.isOptimistic">
                  <div class="checkbox completed">{{ item.updating ? '⟳' : '✓' }}</div>
                </button>

                <div class="item-info">
                  <h4 class="item-title">{{ item.title }}</h4>
                  <div class="completion-info">
                    <span v-if="!item.isOptimistic">
                      Completed {{ formatTimeAgo(item.completed_at) }}
                      <span v-if="item.completed_by?.name">by {{ item.completed_by.name }}</span>
                      <span v-if="item.usage_count > 1">• Used {{ item.usage_count }} times</span>
                    </span>
                    <span v-else class="optimistic-indicator">Processing...</span>
                  </div>
                </div>

                <div class="item-actions">
                  <button v-if="canEdit" @click="deleteItem(item)" :disabled="item.deleting || item.isOptimistic" class="delete-item-btn">🗑️</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit List Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
      <div class="modal-content" @click.stop>
        <h3>Edit List</h3>
        <form @submit.prevent="updateList">
          <div class="form-group">
            <label for="editListName">List Name</label>
            <input id="editListName" v-model="editListData.name" type="text" required />
          </div>
          <div class="form-group">
            <label for="editListDescription">Description</label>
            <textarea id="editListDescription" v-model="editListData.description" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label for="editListColor">Color</label>
            <select id="editListColor" v-model="editListData.color">
              <option value="#007AFF">Blue</option>
              <option value="#FF3B30">Red</option>
              <option value="#34C759">Green</option>
              <option value="#FF9500">Orange</option>
              <option value="#AF52DE">Purple</option>
              <option value="#FF2D92">Pink</option>
              <option value="#5AC8FA">Light Blue</option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeEditModal" class="cancel-btn">Cancel</button>
            <button type="submit" :disabled="updatingList" class="save-btn">{{ updatingList ? 'Saving...' : 'Save Changes' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Share List Modal -->
    <div v-if="showShareModal" class="modal-overlay" @click="closeShareModal">
      <div class="modal-content" @click.stop>
        <h3>Share List</h3>
        <form @submit.prevent="shareList">
          <div class="form-group">
            <label for="shareUser">Select User</label>
            <select id="shareUser" v-model="shareData.email" required :disabled="loadingUsers">
              <option value="">{{ loadingUsers ? 'Loading users...' : 'Select a user' }}</option>
              <option v-for="user in users" :key="user.id" :value="user.email">
                {{ user.name }} ({{ user.email }})
              </option>
            </select>
          </div>
          <div class="form-group">
            <label for="permissionLevel">Permission Level</label>
            <select id="permissionLevel" v-model="shareData.permission_level">
              <option value="view">View Only - Can view list and items</option>
              <option value="edit">Edit - Can view, add, edit, and complete items</option>
              <option value="admin">Admin - Can do everything including sharing</option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeShareModal" class="cancel-btn">Cancel</button>
            <button type="submit" :disabled="shareData.loading || !shareData.email" class="save-btn">
              {{ shareData.loading ? 'Sharing...' : 'Share List' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="error-banner">
      {{ error }}
      <button @click="error = null" class="close-error">×</button>
    </div>
  </div>
</template>

<script>
import Pusher from 'pusher-js'
import listService from "../services/list";
import authService from "../services/auth";

export default {
  name: "List",
  data() {
    return {
      list: null,
      items: [],
      loading: false,
      addingItem: false,
      updatingList: false,
      editingItem: null,
      editingItemData: {
        title: ""
      },
      showCompleted: false,
      showEditModal: false,
      showShareModal: false,
      showAutocomplete: false,
      autocompleteResults: [],
      autocompleteLoading: false,
      autocompleteCache: new Map(), // Cache for autocomplete results
      autocompleteTimeout: null,
      newItem: {
        title: "",
        sort_order: 0,
      },
      editListData: {},
      error: null,
      pusher: null,
      userChannel: null,
      // Swipe-to-delete state
      swipeState: {
        itemId: null,
        startX: 0,
        currentX: 0,
        isDragging: false,
        threshold: 80, // Minimum swipe distance to trigger delete
        maxDistance: 120 // Maximum swipe distance
      },
      shareData: {
        email: '',
        permission_level: 'view',
        loading: false
      },
      users: [],
      loadingUsers: false
    };
  },

  computed: {
    listId() {
      return this.$route.params.id;
    },

    pendingItems() {
      return this.items.filter((item) => !item.is_completed);
    },

    completedItems() {
      return this.items.filter((item) => item.is_completed);
    },

    sortedPendingItems() {
      return [...this.pendingItems].sort((a, b) => a.sort_order - b.sort_order);
    },

    sortedCompletedItems() {
      // Sort completed items by usage count (most popular first), then by completion time
      return [...this.completedItems].sort((a, b) => {
        if (b.usage_count !== a.usage_count) {
          return b.usage_count - a.usage_count;
        }
        return new Date(b.completed_at) - new Date(a.completed_at);
      });
    },

    totalItems() {
      return this.items.length;
    },

    completionRate() {
      if (this.totalItems === 0) return 0;
      return (this.completedItems.length / this.totalItems) * 100;
    },

    canEdit() {
      // User can edit if they own the list or have edit/admin permissions
      const user = authService.getUser();
      return (
        this.list &&
        (this.list.user_id === user?.id ||
          this.list.permission_level === "owner" ||
          this.list.permission_level === "edit" ||
          this.list.permission_level === "admin")
      );
    },

    canShare() {
      // User can share if they own the list or have admin permissions
      const user = authService.getUser();
      return (
        this.list &&
        (this.list.user_id === user?.id ||
          this.list.permission_level === "owner" ||
          this.list.permission_level === "admin")
      );
    },
  },

  async mounted() {
    await this.loadList();
    await this.loadItems();
    this.setupRealTimeUpdates();
  },

  beforeUnmount() {
    this.cleanupRealTimeUpdates();
  },

  methods: {
    async loadList() {
      this.loading = true;
      this.error = null;

      try {
        this.list = await listService.getList(this.listId);
        this.editListData = { ...this.list };
        
        // Debug: Log the list data and permission level
        console.log('Loaded list:', this.list);
        console.log('Permission level:', this.list.permission_level);
        console.log('User ID:', authService.getUser()?.id);
        console.log('List owner ID:', this.list.user_id);
        console.log('Can edit:', this.canEdit);
      } catch (error) {
        console.error("Failed to load list:", error);
        this.error = error.message;
        // If list not found, go back to home
        if (error.message.includes("not found")) {
          this.$router.push("/home");
        }
      } finally {
        this.loading = false;
      }
    },

    async loadItems() {
      this.loading = true;
      this.error = null;

      try {
        this.items = await listService.getListItems(this.listId);
      } catch (error) {
        console.error("Failed to load items:", error);
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },

    setupRealTimeUpdates() {
      const user = authService.getUser();
      if (!user) {
        console.log('⚠️ No user available for real-time updates')
        return;
      }
      
      console.log('🔌 Setting up Pusher real-time updates for user:', user.id)
      
      // Initialize Pusher
      Pusher.logToConsole = true
      
      this.pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        authEndpoint: '/api/broadcasting/auth',
        auth: {
          headers: {
            'Authorization': `Bearer ${authService.getToken()}`,
            'Accept': 'application/json',
          }
        }
      })
      
      console.log('📡 Subscribing to private channel:', `private-user.${user.id}`)
      
      // Subscribe to the user's private channel
      this.userChannel = this.pusher.subscribe(`private-user.${user.id}`)
      
      // Handle subscription success
      this.userChannel.bind('pusher:subscription_succeeded', () => {
        console.log('✅ Successfully subscribed to user channel')
      })
      
      // Handle subscription error
      this.userChannel.bind('pusher:subscription_error', (error) => {
        console.log('❌ Channel subscription error:', error)
      })
      
      // Add a catch-all event listener for debugging
      this.userChannel.bind_global((eventName, data) => {
        if (eventName.startsWith('list.')) {
          console.log(`🔄 Received event: ${eventName}`, data)
        }
      })
      
      // Listen for list updates
      this.userChannel.bind('list.updated', (data) => {
        console.log('📝 List updated event received:', data)
        if (data.list.id === parseInt(this.listId)) {
          this.handleListUpdate(data.list)
        }
      })
      
      // Listen for list item creation
      this.userChannel.bind('list.item.created', (data) => {
        console.log('➕ List item created event received:', data)
        if (data.list_id === parseInt(this.listId)) {
          this.handleItemCreated(data.item)
        }
      })
      
      // Listen for list item updates (edits, completions)
      this.userChannel.bind('list.item.updated', (data) => {
        console.log('✏️ List item updated event received:', data)
        if (data.list_id === parseInt(this.listId)) {
          this.handleItemUpdate(data.item)
        }
      })
      
      // Listen for list item deletion
      this.userChannel.bind('list.item.deleted', (data) => {
        console.log('🗑️ List item deleted event received:', data)
        console.log('🔍 Current listId:', parseInt(this.listId), 'Event listId:', data.list_id)
        if (data.list_id === parseInt(this.listId)) {
          console.log('✅ List ID matches, calling handleItemDeleted')
          this.handleItemDeleted(data.item)
        } else {
          console.log('❌ List ID does not match, ignoring event')
        }
      })
      
      // Handle connection state changes
      this.pusher.connection.bind('state_change', (states) => {
        console.log('🔄 Pusher connection state changed:', states.previous, '->', states.current)
      })
      
      this.pusher.connection.bind('connected', () => {
        console.log('🟢 Pusher connected')
      })
      
      this.pusher.connection.bind('disconnected', () => {
        console.log('🔴 Pusher disconnected')
      })
    },
    
    cleanupRealTimeUpdates() {
      if (this.userChannel) {
        console.log('🧹 Unsubscribing from user channel')
        this.pusher.unsubscribe(`private-user.${authService.getUser()?.id}`)
        this.userChannel = null
      }
      
      if (this.pusher) {
        console.log('🧹 Disconnecting Pusher')
        this.pusher.disconnect()
        this.pusher = null
      }
    },
    
    handleListUpdate(updatedList) {
      // Update list information
      this.list = { ...this.list, ...updatedList };
      this.editListData = { ...this.list };
    },
    
    handleItemCreated(createdItem) {
      console.log('🔍 Handling item created:', createdItem)
      
      // Check if this is a duplicate of an optimistic item we already added
      const optimisticItemIndex = this.items.findIndex(item => 
        item.isOptimistic && 
        item.title === createdItem.title &&
        item.is_completed === createdItem.is_completed
      );
      
      if (optimisticItemIndex !== -1) {
        console.log('🔄 Replacing optimistic item with real item')
        // Replace optimistic item with real item from server
        this.items[optimisticItemIndex] = createdItem;
      } else {
        // Check if we already have this item by ID (to prevent duplicates)
        const existingItemIndex = this.items.findIndex(item => item.id === createdItem.id);
        if (existingItemIndex === -1) {
          console.log('➕ Adding new item from real-time event')
          // Only add if it's not already in the list
          this.items.unshift(createdItem);
        } else {
          console.log('⚠️ Item already exists, skipping addition')
        }
      }
    },
    
    handleItemUpdate(updatedItem) {
      console.log('🔍 Handling item update:', updatedItem)
      
      const itemIndex = this.items.findIndex(item => item.id === updatedItem.id);
      
      if (itemIndex !== -1) {
        // Only update if it's not an optimistic update that we haven't replaced yet
        if (!this.items[itemIndex].isOptimistic) {
          console.log('📝 Updating existing item')
          this.items[itemIndex] = updatedItem;
        } else {
          console.log('⏳ Skipping update for optimistic item')
        }
      } else {
        console.log('⚠️ Received update for unknown item:', updatedItem.id)
      }
    },
    
    handleItemDeleted(deletedItem) {
      console.log('🔍 Handling item deletion:', deletedItem)
      
      // Remove the item from the local list
      const itemIndex = this.items.findIndex(item => item.id === deletedItem.id);
      if (itemIndex !== -1) {
        console.log('🗑️ Removing deleted item from list')
        
        // Clear any fallback timeout since the real-time event arrived
        if (this.items[itemIndex].fallbackTimeout) {
          clearTimeout(this.items[itemIndex].fallbackTimeout);
          console.log('⏰ Cleared fallback timeout for real-time deletion');
        }
        
        this.items.splice(itemIndex, 1);
        
        // Cancel edit if we're editing this item
        if (this.editingItem === deletedItem.id) {
          console.log('❌ Canceling edit for deleted item')
          this.cancelEdit();
        }
      } else {
        console.log('⚠️ Tried to delete item that was not found in local list')
      }
    },

    async refreshList() {
      await Promise.all([this.loadList(), this.loadItems()]);
    },

    async addItem() {
      if (!this.newItem.title?.trim()) return;

      this.addingItem = true;
      this.error = null;

      const trimmedTitle = this.newItem.title.trim();
      
      // Check if an item with this title already exists (case-insensitive)
      // Exclude items that are being deleted or are optimistic items
      const existingItem = this.items.find(item => 
        item.title?.toLowerCase() === trimmedTitle.toLowerCase() &&
        !item.deleting &&
        !item.isOptimistic &&
        !String(item.id).startsWith('temp-')
      );

      if (existingItem) {
        if (existingItem.is_completed) {
          // If the item exists and is completed, bring it back to "to do"
          try {
            await this.toggleComplete(existingItem);
            // Reset editing state after successful toggle
            this.editingItem = null;
            this.newItem = {
              title: "",
              sort_order: 0,
            };
            this.hideAutocomplete();
          } catch (error) {
            console.error("Failed to toggle existing item:", error);
            this.error = error.message;
          }
        } else {
          // Item already exists and is not completed, just reset the form
          this.editingItem = null;
          this.newItem = {
            title: "",
            sort_order: 0,
          };
          this.hideAutocomplete();
        }
        this.addingItem = false;
        return;
      }

      // Create optimistic item for immediate UI update
      const optimisticItem = {
        id: `temp-${Date.now()}`, // Temporary ID
        title: trimmedTitle,
        is_completed: false,
        sort_order: this.newItem.sort_order,
        usage_count: 1,
        updating: false,
        created_at: new Date().toISOString(),
        isOptimistic: true // Flag to identify optimistic updates
      };

      try {
        // Add optimistically to UI immediately
        this.items.unshift(optimisticItem);
        console.log('➕ Added optimistic item:', optimisticItem);

        // Reset form and editing state immediately for better UX
        this.editingItem = null;
        this.newItem = {
          title: "",
          sort_order: 0,
        };
        this.hideAutocomplete();

        const itemData = {
          title: trimmedTitle,
          sort_order: optimisticItem.sort_order,
        };

        console.log('📡 Creating item via API...');
        const createdItem = await listService.createItem(this.listId, itemData);
        console.log('✅ Item created via API, waiting for real-time event...');
        
        // Don't manually replace the optimistic item here
        // Let the real-time event (handleItemCreated) handle the replacement
        
      } catch (error) {
        console.error("❌ Failed to add item:", error);
        this.error = error.message;
        
        // Remove optimistic item on error
        this.items = this.items.filter(item => item.id !== optimisticItem.id);
        console.log('🗑️ Removed failed optimistic item');
        
        // Restore form data on error and re-enter editing mode
        this.editingItem = 'new';
        this.newItem.title = trimmedTitle;
      } finally {
        this.addingItem = false;
      }
    },

    async toggleComplete(item) {
      console.log("toggleComplete called for item:", item.id, item.title);
      
      // Don't allow toggling for optimistic items or items with temporary IDs
      if (item.isOptimistic || String(item.id).startsWith('temp-')) {
        console.warn("Cannot toggle completion for optimistic item:", item);
        this.error = "Cannot complete item while it's being processed. Please wait a moment.";
        return;
      }
      
      // Don't allow toggling for items being deleted
      if (item.deleting) {
        console.warn("Cannot toggle completion for item being deleted:", item);
        this.error = "Cannot complete item while it's being deleted.";
        return;
      }
      
      // Optimistic update
      item.updating = true;

      try {
        console.log(
          "Calling API with listId:",
          this.listId,
          "itemId:",
          item.id
        );
        const updatedItem = await listService.toggleItemCompletion(
          this.listId,
          item.id
        );
        console.log("API response:", updatedItem);

        // Update the item in the list immediately for better UX
        const index = this.items.findIndex((i) => i.id === item.id);
        if (index !== -1) {
          this.items[index] = updatedItem;
        }
        console.log("Item updated in list at index:", index);
      } catch (error) {
        console.error("Failed to toggle completion:", error);
        this.error = error.message;
      } finally {
        item.updating = false;
      }
    },

    startEdit(item) {
      // Don't allow editing optimistic items, items being deleted, or items with temporary IDs
      if (item.isOptimistic || item.deleting || String(item.id).startsWith('temp-')) {
        console.warn("Cannot edit item in current state:", item);
        return;
      }

      this.editingItem = item.id;
      this.editingItemData = {
        title: item.title,
      };

      this.$nextTick(() => {
        // Fix focus error by checking if ref exists and has focus method
        const editInput = this.$refs.editInput;
        if (editInput && typeof editInput.focus === 'function') {
          editInput.focus();
          if (typeof editInput.select === 'function') {
            editInput.select();
          }
        } else if (editInput && editInput.length > 0 && typeof editInput[0].focus === 'function') {
          // Handle case where ref returns an array
          editInput[0].focus();
          if (typeof editInput[0].select === 'function') {
            editInput[0].select();
          }
        }
      });
    },

    startAddNew() {
      this.editingItem = 'new';
      this.newItem = {
        title: "",
        sort_order: 0,
      };

      this.$nextTick(() => {
        // Fix focus error by checking if ref exists and has focus method
        const addInput = this.$refs.addInput;
        if (addInput && typeof addInput.focus === 'function') {
          addInput.focus();
        } else if (addInput && addInput.length > 0 && typeof addInput[0].focus === 'function') {
          // Handle case where ref returns an array
          addInput[0].focus();
        }
      });
    },

    cancelAdd() {
      this.editingItem = null;
      this.newItem = {
        title: "",
        sort_order: 0,
      };
      this.hideAutocomplete();
    },

    handleAddBlur() {
      // Small delay to allow autocomplete selection
      setTimeout(() => {
        if (this.newItem.title?.trim()) {
          this.addItem();
        } else {
          this.cancelAdd();
        }
      }, 150);
    },

    async saveEdit() {
      if (!this.editingItemData.title?.trim()) {
        this.cancelEdit();
        return;
      }

      const trimmedTitle = this.editingItemData.title.trim();
      const itemIndex = this.items.findIndex((i) => i.id === this.editingItem);
      let originalTitle = '';
      
      // Store original title for rollback if needed
      if (itemIndex !== -1) {
        originalTitle = this.items[itemIndex].title;
        // Optimistically update the title immediately
        this.items[itemIndex].title = trimmedTitle;
      }

      try {
        const itemData = {
          title: trimmedTitle,
        };

        const updatedItem = await listService.updateItem(
          this.listId,
          this.editingItem,
          itemData
        );

        // Update with server response
        if (itemIndex !== -1) {
          this.items[itemIndex] = updatedItem;
        }

        this.cancelEdit();
      } catch (error) {
        console.error("Failed to update item:", error);
        this.error = error.message;
        
        // Rollback the optimistic update on error
        if (itemIndex !== -1) {
          this.items[itemIndex].title = originalTitle;
        }
      }
    },

    cancelEdit() {
      this.editingItem = null;
      this.editingItemData = {
        title: ""
      };
    },

    async deleteItem(item) {
      // Don't allow deleting optimistic items or items with temporary IDs
      if (item.isOptimistic || String(item.id).startsWith('temp-')) {
        console.warn("Cannot delete optimistic item:", item);
        this.error = "Cannot delete item while it's being processed. Please wait a moment.";
        return;
      }

      // Don't allow deleting items that are already being deleted
      if (item.deleting) {
        console.warn("Item is already being deleted:", item);
        return;
      }

      if (!confirm(`Delete "${item.title}"?`)) return;

      console.log('🗑️ Starting deletion for item:', item.id, item.title);

      // Set a flag to indicate deletion is in progress
      const itemIndex = this.items.findIndex((i) => i.id === item.id);
      if (itemIndex !== -1) {
        this.items[itemIndex] = { ...this.items[itemIndex], deleting: true };
      }

      try {
        console.log('📡 Calling deleteItem API...');
        await listService.deleteItem(this.listId, item.id);
        console.log('✅ API call successful, waiting for real-time update...');
        
        // Set up a fallback timeout in case real-time event doesn't arrive
        const fallbackTimeout = setTimeout(() => {
          console.log('⏰ Real-time event timeout, removing item manually');
          const currentIndex = this.items.findIndex((i) => i.id === item.id);
          if (currentIndex !== -1) {
            this.items.splice(currentIndex, 1);
            
            // Cancel edit if we're editing this item
            if (this.editingItem === item.id) {
              this.cancelEdit();
            }
          }
        }, 100); // 3 second fallback
        
        // Store the timeout ID on the item so we can clear it if the real-time event arrives
        if (itemIndex !== -1) {
          this.items[itemIndex] = { ...this.items[itemIndex], fallbackTimeout };
        }
        
        // Don't remove from local items here - let the real-time event handle it
        // The handleItemDeleted method will remove it when the broadcast event arrives
        
      } catch (error) {
        console.error("❌ Failed to delete item:", error);
        this.error = error.message;
        
        // Remove the deleting flag on error
        if (itemIndex !== -1) {
          this.items[itemIndex] = { ...this.items[itemIndex], deleting: false };
          
          // Clear any fallback timeout
          if (this.items[itemIndex].fallbackTimeout) {
            clearTimeout(this.items[itemIndex].fallbackTimeout);
          }
        }
        
        // Reload items on error to ensure consistency
        console.log('🔄 Reloading items due to error...');
        this.loadItems();
      }
    },

    async updateList() {
      this.updatingList = true;

      try {
        const updatedList = await listService.updateList(
          this.listId,
          this.editListData
        );
        this.list = updatedList;
        this.closeEditModal();
      } catch (error) {
        console.error("Failed to update list:", error);
        this.error = error.message;
      } finally {
        this.updatingList = false;
      }
    },

    async handleAutocomplete() {
      const query = this.newItem.title?.trim()?.toLowerCase() || '';

      // Show results for single character searches
      if (query.length < 1) {
        this.autocompleteResults = [];
        this.autocompleteLoading = false;
        this.showAutocomplete = false;
        return;
      }

      // Always show autocomplete when typing
      this.showAutocomplete = true;

      // Check cache first for immediate results
      const cacheKey = query;
      if (this.autocompleteCache.has(cacheKey)) {
        const cachedResults = this.autocompleteCache.get(cacheKey);
        // Sort cached results by usage count (most popular first)
        this.autocompleteResults = [...cachedResults].sort((a, b) => b.usage_count - a.usage_count);
        this.autocompleteLoading = false;
        this.showAutocomplete = true;
        return;
      }

      // For very short queries, also check if we have cached results for longer queries that start with this
      if (query.length <= 2) {
        for (const [cachedQuery, results] of this.autocompleteCache.entries()) {
          if (cachedQuery.startsWith(query) && results.length > 0) {
            // Filter the cached results to match current query
            const filteredResults = results.filter(item => 
              item.item_title.toLowerCase().includes(query)
            );
            if (filteredResults.length > 0) {
              // Sort filtered results by usage count (most popular first)
              this.autocompleteResults = filteredResults.sort((a, b) => b.usage_count - a.usage_count);
              this.autocompleteLoading = false;
              this.showAutocomplete = true;
              break;
            }
          }
        }
      }

      // Show loading state
      this.autocompleteLoading = true;

      // Debounce the autocomplete request with shorter timeout for better reactivity
      if (this.autocompleteTimeout) {
        clearTimeout(this.autocompleteTimeout);
      }

      this.autocompleteTimeout = setTimeout(async () => {
        try {
          // Use a more responsive timeout for better UX and request more results
          const results = await listService.autocompleteItems(query, 30);
          
          // Sort results by usage count (most popular first)
          const sortedResults = [...results].sort((a, b) => b.usage_count - a.usage_count);
          
          // Cache the sorted results
          this.autocompleteCache.set(cacheKey, sortedResults);
          
          // Only update if this is still the current query
          if (this.newItem.title?.trim()?.toLowerCase() === query) {
            this.autocompleteResults = sortedResults;
            this.autocompleteLoading = false;
            
            // Keep autocomplete visible if we have results
            if (sortedResults.length > 0) {
              this.showAutocomplete = true;
            }
          }
        } catch (error) {
          console.error("Autocomplete failed:", error);
          this.autocompleteResults = [];
          this.autocompleteLoading = false;
        }
      }, 100); // Reduced from 300ms to 100ms for much faster response
    },

    selectSuggestion(suggestion) {
      this.newItem.title = suggestion.item_title;
      this.addItem();
    },

    hideAutocomplete() {
      setTimeout(() => {
        this.showAutocomplete = false;
        this.autocompleteResults = [];
        this.autocompleteLoading = false;
      }, 150); // Small delay to allow click events on suggestions
    },

    closeEditModal() {
      this.showEditModal = false;
      this.editListData = { ...this.list };
    },

    goBack() {
      this.$router.push("/home");
    },

    getListIcon(iconName) {
      const iconMap = {
        "list.bullet": "📋",
        cart: "🛒",
        house: "🏠",
        briefcase: "💼",
        heart: "❤️",
        star: "⭐",
        flag: "🏴",
        bookmark: "📖",
        person: "👤",
        gear: "⚙️",
      };
      return iconMap[iconName] || "📋";
    },

    formatTimeAgo(timestamp) {
      const now = new Date();
      const time = new Date(timestamp);
      const diffInMinutes = Math.floor((now - time) / (1000 * 60));

      if (diffInMinutes < 1) return "just now";
      if (diffInMinutes < 60) return `${diffInMinutes}m ago`;

      const diffInHours = Math.floor(diffInMinutes / 60);
      if (diffInHours < 24) return `${diffInHours}h ago`;

      const diffInDays = Math.floor(diffInHours / 24);
      return `${diffInDays}d ago`;
    },

    highlightMatch(text, query) {
      if (!query) return text;
      const regex = new RegExp(`(${query})`, 'gi');
      return text.replace(regex, '<span class="highlight">$1</span>');
    },

    deleteSuggestion(suggestion) {
      if (!confirm(`Delete "${suggestion.item_title}" from suggestions?`)) {
        return;
      }

      // Remove from current results immediately for better UX
      this.autocompleteResults = this.autocompleteResults.filter(
        item => item.item_title !== suggestion.item_title
      );

      // Clear from cache
      this.autocompleteCache.forEach((cachedResults, key) => {
        const filteredResults = cachedResults.filter(
          item => item.item_title !== suggestion.item_title
        );
        this.autocompleteCache.set(key, filteredResults);
      });

      // Call backend to delete the usage stat
      this.deleteUsageStat(suggestion.item_title).catch(error => {
        console.error("Failed to delete suggestion:", error);
        this.error = "Failed to delete suggestion. Please refresh and try again.";
        
        // Reload autocomplete to restore consistency
        if (this.newItem.title.trim()) {
          this.handleAutocomplete();
        }
      });
    },

    async deleteUsageStat(itemTitle) {
      try {
        await listService.deleteUsageStat(itemTitle);
      } catch (error) {
        throw new Error(error.message || 'Failed to delete usage stat');
      }
    },

    // Swipe-to-delete methods
    handleTouchStart(event, item) {
      // Don't allow swiping on optimistic items, editing items, or items being deleted
      if (item.isOptimistic || item.deleting || this.editingItem === item.id || String(item.id).startsWith('temp-')) {
        return;
      }

      this.swipeState.startX = event.touches[0].clientX;
      this.swipeState.currentX = this.swipeState.startX;
      this.swipeState.itemId = item.id;
      this.swipeState.isDragging = false;
    },

    handleTouchMove(event, item) {
      if (this.swipeState.itemId !== item.id) return;

      event.preventDefault(); // Prevent scrolling
      this.swipeState.currentX = event.touches[0].clientX;
      const deltaX = this.swipeState.startX - this.swipeState.currentX;

      // Only handle right-to-left swipe (positive deltaX)
      if (deltaX > 10) {
        this.swipeState.isDragging = true;
        
        // Apply transform to show swipe progress
        const distance = Math.min(deltaX, this.swipeState.maxDistance);
        const itemElement = event.currentTarget;
        itemElement.style.transform = `translateX(-${distance}px)`;
        itemElement.style.transition = 'none';
        
        // Update opacity based on swipe distance
        const opacity = Math.max(0.3, 1 - (distance / this.swipeState.maxDistance));
        itemElement.style.opacity = opacity;
      }
    },

    handleTouchEnd(event, item) {
      if (this.swipeState.itemId !== item.id) return;

      const deltaX = this.swipeState.startX - this.swipeState.currentX;
      const itemElement = event.currentTarget;

      // Reset transform and transition
      itemElement.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
      
      if (deltaX >= this.swipeState.threshold && this.swipeState.isDragging) {
        // Trigger delete with animation
        itemElement.style.transform = `translateX(-100%)`;
        itemElement.style.opacity = '0';
        
        setTimeout(() => {
          this.deleteItem(item);
        }, 300);
      } else {
        // Reset position
        itemElement.style.transform = 'translateX(0)';
        itemElement.style.opacity = '1';
      }

      // Reset swipe state
      this.swipeState.itemId = null;
      this.swipeState.startX = 0;
      this.swipeState.currentX = 0;
      this.swipeState.isDragging = false;

      // Clear styles after animation
      setTimeout(() => {
        itemElement.style.transition = '';
        if (!item.deleting) {
          itemElement.style.transform = '';
          itemElement.style.opacity = '';
        }
      }, 300);
    },

    // Share modal methods
    async openShareModal() {
      this.showShareModal = true;
      await this.loadUsers();
    },

    async loadUsers() {
      this.loadingUsers = true;
      try {
        // We'll need to add this API endpoint
        this.users = await listService.getUsers();
      } catch (error) {
        console.error('Failed to load users:', error);
        this.error = 'Failed to load users for sharing';
        this.users = [];
      } finally {
        this.loadingUsers = false;
      }
    },

    async shareList() {
      if (!this.shareData.email) {
        this.error = 'Please select a user to share with';
        return;
      }

      this.shareData.loading = true;
      try {
        await listService.shareList(this.listId, {
          email: this.shareData.email,
          permission_level: this.shareData.permission_level
        });
        
        this.closeShareModal();
        // Refresh list to show updated share status
        await this.loadList();
      } catch (error) {
        console.error('Failed to share list:', error);
        this.error = error.message;
      } finally {
        this.shareData.loading = false;
      }
    },

    closeShareModal() {
      this.showShareModal = false;
      this.shareData.email = '';
      this.shareData.permission_level = 'view';
      this.shareData.loading = false;
    },
  },
};
</script>

<style scoped>
.item-card.deleting {
  opacity: 0.5;
  pointer-events: none;
  position: relative;
}

.item-card.deleting::after {
  content: '🗑️';
  position: absolute;
  top: 50%;
  right: 10px;
  transform: translateY(-50%);
  font-size: 14px;
  opacity: 0.7;
}

.item-card.deleting .item-actions {
  opacity: 0.3;
}

.item-card.optimistic {
  opacity: 0.7;
  position: relative;
}

.item-card.optimistic .item-title {
  font-style: italic;
}

.optimistic-indicator {
  color: #666;
  font-size: 0.9em;
  font-style: italic;
}

.item-card.optimistic .complete-btn,
.item-card.optimistic .delete-item-btn {
  opacity: 0.5;
  cursor: not-allowed;
}

.item-card.optimistic .item-info {
  cursor: default;
}

/* Add new item styles */
.add-new-item {
  opacity: 0.6;
  border: 2px dashed #ddd !important;
  background: #fafafa;
  transition: all 0.2s ease;
}

.add-new-item:hover {
  opacity: 0.8;
  border-color: #007AFF !important;
  background: #f5f9ff;
}

.add-new-item.editing {
  opacity: 1;
  border: 2px solid #007AFF !important;
  background: white;
}

.add-new-checkbox .checkbox.empty {
  border: 2px dashed #ccc;
  background: transparent;
  color: transparent;
}

.add-new-item:hover .add-new-checkbox .checkbox.empty {
  border-color: #007AFF;
}

.add-new-item.editing .add-new-checkbox .checkbox.empty {
  border: 2px solid #007AFF;
}

.add-new-title {
  color: #999;
  font-style: italic;
  font-weight: normal;
}

.add-new-info {
  cursor: pointer;
}

/* Inline edit input */
.inline-edit-input {
  border: none;
  outline: none;
  background: transparent;
  font-size: 1rem;
  font-weight: 500;
  color: #333;
  width: 100%;
  padding: 0;
  margin: 0;
  font-family: inherit;
}

.inline-edit-input::placeholder {
  color: #999;
  font-style: italic;
  font-weight: normal;
}

.inline-edit-input:focus {
  background: rgba(0, 122, 255, 0.05);
  border-radius: 4px;
  padding: 2px 4px;
  margin: -2px -4px;
}

/* Autocomplete positioning for inline input */
.item-info {
  position: relative;
}

.autocomplete-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1000;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  max-height: 300px;
  overflow-y: auto;
  margin-top: 4px;
}

.autocomplete-item {
  display: flex;
  align-items: center;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s ease;
}

.autocomplete-item:last-child {
  border-bottom: none;
}

.autocomplete-item:hover {
  background-color: #f8f9fa;
}

.suggestion-content {
  flex: 1;
  padding: 12px;
  cursor: pointer;
}

.suggestion-title {
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
}

.suggestion-meta {
  font-size: 12px;
  color: #666;
}

.delete-suggestion-btn {
  background: none;
  border: none;
  padding: 8px 12px;
  cursor: pointer;
  color: #999;
  font-size: 14px;
  transition: all 0.2s ease;
  border-radius: 4px;
  margin-right: 8px;
}

.delete-suggestion-btn:hover {
  background-color: #fee;
  color: #e74c3c;
}

.autocomplete-loading {
  padding: 12px;
  text-align: center;
  color: #666;
  font-size: 14px;
}

/* Swipe-to-delete styles */
.item-card {
  position: relative;
  touch-action: pan-y; /* Allow vertical scrolling but handle horizontal swipes */
  user-select: none;
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.item-card.swiping {
  transition: none;
}

/* Improved swipe visual feedback */
.item-card::after {
  content: '🗑️ Swipe to delete';
  position: absolute;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  background: #ff3b30;
  color: white;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
  z-index: -1;
}

.item-card.deleting::after {
  opacity: 1;
  z-index: 1;
}

/* Enhanced modal styling */
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
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 16px;
  padding: 24px;
  width: 90%;
  max-width: 400px;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-content h3 {
  margin: 0 0 20px 0;
  color: #333;
  font-size: 1.2rem;
  font-weight: 600;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  color: #555;
  font-weight: 500;
  font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px;
  border: 2px solid #e1e5e9;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  background: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #007AFF;
  box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.form-group select {
  cursor: pointer;
}

.form-group select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modal-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
  justify-content: flex-end;
}

.cancel-btn,
.save-btn {
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid transparent;
}

.cancel-btn {
  background: #f8f9fa;
  color: #6c757d;
  border-color: #e1e5e9;
}

.cancel-btn:hover {
  background: #e9ecef;
  border-color: #d1d5db;
}

.save-btn {
  background: #007AFF;
  color: white;
  border-color: #007AFF;
}

.save-btn:hover:not(:disabled) {
  background: #0056b3;
  border-color: #0056b3;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 122, 255, 0.2);
}

.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.add-new-item:hover {
  opacity: 0.8;
  border-color: #007AFF !important;
  background: #f5f9ff;
}
</style> 