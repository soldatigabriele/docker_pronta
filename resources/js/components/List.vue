<template>
  <div class="list-wrapper">
    <div class="list-container">
      <!-- Header -->
      <div class="list-header">
        <div class="header-top">
          <button @click="goBack" class="back-btn">← Back</button>
          <div class="header-actions">
            <button @click="showShareModal = true" v-if="canShare" class="share-btn">📤</button>
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

      <!-- Quick Stats -->
      <div v-if="list && items.length > 0" class="quick-stats">
        <div class="stat-item">
          <span class="stat-number">{{ pendingItems.length }}</span>
          <span class="stat-label">Pending</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ completedItems.length }}</span>
          <span class="stat-label">Completed</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ Math.round(completionRate) }}%</span>
          <span class="stat-label">Done</span>
        </div>
      </div>

      <!-- Add Item Form -->
      <div v-if="canEdit" class="add-item-section">
        <form @submit.prevent="addItem" class="add-item-form">
          <div class="input-group">
            <input v-model="newItem.title" ref="itemInput" type="text" placeholder="Add new item..." class="item-input" @input="handleAutocomplete" @focus="showAutocomplete = true" @blur="hideAutocomplete" />
            <button type="submit" :disabled="!newItem.title.trim() || addingItem" class="add-btn">{{ addingItem ? '⟳' : '+' }}</button>
          </div>

          <!-- Autocomplete dropdown -->
          <div v-if="showAutocomplete && (autocompleteResults.length > 0 || autocompleteLoading)" class="autocomplete-dropdown">
            <div v-if="autocompleteLoading" class="autocomplete-loading">Searching...</div>
            <div v-for="suggestion in autocompleteResults" :key="suggestion.item_title" @mousedown.prevent="selectSuggestion(suggestion)" class="autocomplete-item">
              <div class="suggestion-title" v-html="highlightMatch(suggestion.item_title, newItem.title)"></div>
              <div class="suggestion-meta">Used {{ suggestion.usage_count }} times • {{ Math.round(suggestion.completion_rate) }}% completion rate</div>
            </div>
          </div>
        </form>
      </div>

      <!-- Loading State -->
      <div v-if="loading && items.length === 0" class="loading-state">
        <p>Loading items...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading && items.length === 0" class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No Items Yet</h3>
        <p>Add your first item to get started</p>
      </div>

      <!-- Items List -->
      <div v-else class="items-section">
        <!-- Pending Items -->
        <div v-if="pendingItems.length > 0" class="items-group">
          <h3 class="group-title">To Do</h3>
          <div class="items-list">
            <div v-for="item in sortedPendingItems" :key="item.id" class="item-card" :class="{ 'editing': editingItem === item.id }">
              <div v-if="editingItem !== item.id" class="item-content">
                <button @click="toggleComplete(item)" class="complete-btn" :disabled="item.updating">
                  <div class="checkbox">{{ item.updating ? '⟳' : (item.is_completed ? '✓' : '') }}</div>
                </button>

                <div class="item-info" @click="startEdit(item)">
                  <h4 class="item-title">{{ item.title }}</h4>
                  <div v-if="item.usage_count > 1" class="item-meta">Used {{ item.usage_count }} times</div>
                </div>

                <div class="item-actions">
                  <button v-if="canEdit" @click="startEdit(item)" class="edit-item-btn">✏️</button>
                  <button v-if="canEdit" @click="deleteItem(item)" class="delete-item-btn">🗑️</button>
                </div>
              </div>

              <!-- Edit Form -->
              <div v-else class="edit-form">
                <input v-model="editingItemData.title" type="text" class="edit-title-input" @keyup.enter="saveEdit" @keyup.escape="cancelEdit" ref="editInput" />
                <div class="edit-actions">
                  <button @click="saveEdit" class="save-btn">Save</button>
                  <button @click="cancelEdit" class="cancel-btn">Cancel</button>
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
            <div v-for="item in sortedCompletedItems" :key="item.id" class="item-card completed">
              <div class="item-content">
                <button @click="toggleComplete(item)" class="complete-btn" :disabled="item.updating">
                  <div class="checkbox completed">{{ item.updating ? '⟳' : '✓' }}</div>
                </button>

                <div class="item-info">
                  <h4 class="item-title">{{ item.title }}</h4>
                  <div class="completion-info">
                    Completed {{ formatTimeAgo(item.completed_at) }}
                    <span v-if="item.completed_by?.name">by {{ item.completed_by.name }}</span>
                    <span v-if="item.usage_count > 1">• Used {{ item.usage_count }} times</span>
                  </div>
                </div>

                <div class="item-actions">
                  <button v-if="canEdit" @click="deleteItem(item)" class="delete-item-btn">🗑️</button>
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
      editingItemData: {},
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
      userChannel: null
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
      
      // Listen for list updates
      this.userChannel.bind('list.updated', (data) => {
        console.log('📝 List updated event received:', data)
        if (data.list.id === parseInt(this.listId)) {
          this.handleListUpdate(data.list)
        }
      })
      
      // Listen for list item updates
      this.userChannel.bind('list.item.updated', (data) => {
        console.log('✏️ List item updated event received:', data)
        if (data.list_id === parseInt(this.listId)) {
          this.handleItemUpdate(data.item)
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
    
    handleItemUpdate(updatedItem) {
      const itemIndex = this.items.findIndex(item => item.id === updatedItem.id);
      
      if (itemIndex !== -1) {
        // Update existing item
        this.items[itemIndex] = updatedItem;
      } else {
        // Add new item if it doesn't exist
        this.items.unshift(updatedItem);
      }
    },

    async refreshList() {
      await Promise.all([this.loadList(), this.loadItems()]);
    },

    async addItem() {
      if (!this.newItem.title.trim()) return;

      this.addingItem = true;
      this.error = null;

      try {
        const trimmedTitle = this.newItem.title.trim();
        
        // Check if an item with this title already exists (case-insensitive)
        const existingItem = this.items.find(item => 
          item.title.toLowerCase() === trimmedTitle.toLowerCase()
        );

        if (existingItem) {
          if (existingItem.is_completed) {
            // If the item exists and is completed, bring it back to "to do"
            await this.toggleComplete(existingItem);
          }
          // If the item exists and is not completed, just reset the form
          // (no need to create a duplicate)
          
          // Reset form
          this.newItem = {
            title: "",
            sort_order: 0,
          };
          this.hideAutocomplete();
          return;
        }

        // If no existing item found, create new one
        const itemData = {
          title: trimmedTitle,
          sort_order: this.newItem.sort_order,
        };

        const createdItem = await listService.createItem(this.listId, itemData);
        
        // Don't add to local items here - let the real-time update handle it
        // This prevents duplicate items when the broadcast event comes back
        
        // Reset form
        this.newItem = {
          title: "",
          sort_order: 0,
        };
        this.hideAutocomplete();
      } catch (error) {
        console.error("Failed to add item:", error);
        this.error = error.message;
      } finally {
        this.addingItem = false;
      }
    },

    async toggleComplete(item) {
      console.log("toggleComplete called for item:", item.id, item.title);
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
      this.editingItem = item.id;
      this.editingItemData = {
        title: item.title,
      };

      this.$nextTick(() => {
        if (this.$refs.editInput) {
          this.$refs.editInput.focus();
        }
      });
    },

    async saveEdit() {
      if (!this.editingItemData.title.trim()) return;

      try {
        const itemData = {
          title: this.editingItemData.title.trim(),
        };

        const updatedItem = await listService.updateItem(
          this.listId,
          this.editingItem,
          itemData
        );

        // Update the item in the list immediately for better UX
        const index = this.items.findIndex((i) => i.id === this.editingItem);
        if (index !== -1) {
          this.items[index] = updatedItem;
        }

        this.cancelEdit();
      } catch (error) {
        console.error("Failed to update item:", error);
        this.error = error.message;
      }
    },

    cancelEdit() {
      this.editingItem = null;
      this.editingItemData = {};
    },

    async deleteItem(item) {
      if (!confirm(`Delete "${item.title}"?`)) return;

      try {
        await listService.deleteItem(this.listId, item.id);
        
        // Remove from local items immediately for better UX
        this.items = this.items.filter((i) => i.id !== item.id);
      } catch (error) {
        console.error("Failed to delete item:", error);
        this.error = error.message;
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
      const query = this.newItem.title.trim().toLowerCase();

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
        this.autocompleteResults = this.autocompleteCache.get(cacheKey);
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
              this.autocompleteResults = filteredResults;
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
          // Use a more responsive timeout for better UX
          const results = await listService.autocompleteItems(query);
          
          // Cache the results
          this.autocompleteCache.set(cacheKey, results);
          
          // Only update if this is still the current query
          if (this.newItem.title.trim().toLowerCase() === query) {
            this.autocompleteResults = results;
            this.autocompleteLoading = false;
            
            // Keep autocomplete visible if we have results
            if (results.length > 0) {
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
      this.hideAutocomplete();
      this.$refs.itemInput.focus();
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
  },
};
</script> 