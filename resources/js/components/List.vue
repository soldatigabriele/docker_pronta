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
          <div v-if="showAutocomplete && autocompleteResults.length > 0" class="autocomplete-dropdown">
            <div v-for="suggestion in autocompleteResults" :key="suggestion.item_title" @mousedown.prevent="selectSuggestion(suggestion)" class="autocomplete-item">
              <div class="suggestion-title">{{ suggestion.item_title }}</div>
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
      autocompleteTimeout: null,
      newItem: {
        title: "",
        sort_order: 0,
      },
      editListData: {},
      error: null,
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
          this.list.permission_level === "admin")
      );
    },
  },

  async mounted() {
    await this.loadList();
    await this.loadItems();
  },

  methods: {
    async loadList() {
      this.loading = true;
      this.error = null;

      try {
        this.list = await listService.getList(this.listId);
        this.editListData = { ...this.list };
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

    async refreshList() {
      await Promise.all([this.loadList(), this.loadItems()]);
    },

    async addItem() {
      if (!this.newItem.title.trim()) return;

      this.addingItem = true;
      this.error = null;

      try {
        const itemData = {
          title: this.newItem.title.trim(),
          sort_order: this.newItem.sort_order,
        };

        const createdItem = await listService.createItem(this.listId, itemData);
        this.items.unshift(createdItem);

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

        // Update the item in the list
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

        // Update the item in the list
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
      const query = this.newItem.title.trim();

      if (query.length < 2) {
        this.autocompleteResults = [];
        return;
      }

      // Debounce the autocomplete request
      if (this.autocompleteTimeout) {
        clearTimeout(this.autocompleteTimeout);
      }

      this.autocompleteTimeout = setTimeout(async () => {
        try {
          this.autocompleteResults = await listService.autocompleteItems(query);
        } catch (error) {
          console.error("Autocomplete failed:", error);
          this.autocompleteResults = [];
        }
      }, 300);
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
  },
};
</script> 