import axios from 'axios'

class ListService {
  constructor() {
    this.baseURL = '/api'
  }

  async getLists(includeShared = true) {
    try {
      const response = await axios.get(`${this.baseURL}/lists`, {
        params: { include_shared: includeShared }
      })
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to fetch lists')
    }
  }

  async getList(listId) {
    try {
      const response = await axios.get(`${this.baseURL}/lists/${listId}`)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to fetch list')
    }
  }

  async createList(listData) {
    try {
      const response = await axios.post(`${this.baseURL}/lists`, listData)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to create list')
    }
  }

  async updateList(listId, listData) {
    try {
      const response = await axios.put(`${this.baseURL}/lists/${listId}`, listData)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to update list')
    }
  }

  async deleteList(listId) {
    try {
      const response = await axios.delete(`${this.baseURL}/lists/${listId}`)
      return response.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to delete list')
    }
  }

  async reorderLists(listIds) {
    try {
      const response = await axios.post(`${this.baseURL}/lists/reorder`, {
        list_ids: listIds
      })
      return response.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to reorder lists')
    }
  }

  async pinList(listId, isPinned) {
    try {
      const response = await axios.patch(`${this.baseURL}/lists/${listId}/pin`, {
        is_pinned: isPinned
      })
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to pin/unpin list')
    }
  }

  async getListItems(listId, completed = null) {
    try {
      const params = {}
      if (completed !== null) {
        params.completed = completed
      }
      
      const response = await axios.get(`${this.baseURL}/lists/${listId}/items`, { params })
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to fetch list items')
    }
  }

  // Item management methods
  async createItem(listId, itemData) {
    try {
      const response = await axios.post(`${this.baseURL}/lists/${listId}/items`, itemData)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to create item')
    }
  }

  async updateItem(listId, itemId, itemData) {
    try {
      const response = await axios.put(`${this.baseURL}/lists/${listId}/items/${itemId}`, itemData)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to update item')
    }
  }

  async deleteItem(listId, itemId) {
    try {
      const response = await axios.delete(`${this.baseURL}/lists/${listId}/items/${itemId}`)
      return response.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to delete item')
    }
  }

  async toggleItemCompletion(listId, itemId) {
    try {
      const response = await axios.patch(`${this.baseURL}/lists/${listId}/items/${itemId}/toggle-complete`)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to toggle item completion')
    }
  }

  async reorderItems(listId, itemIds) {
    try {
      const response = await axios.post(`${this.baseURL}/lists/${listId}/items/reorder`, {
        item_ids: itemIds
      })
      return response.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to reorder items')
    }
  }

  async autocompleteItems(query, limit = 20) {
    try {
      const response = await axios.get(`${this.baseURL}/items/autocomplete`, {
        params: { q: query, limit: limit }
      })
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to get autocomplete suggestions')
    }
  }

  async deleteUsageStat(itemTitle) {
    try {
      const response = await axios.delete(`${this.baseURL}/items/usage-stats`, {
        data: { item_title: itemTitle }
      })
      return response.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to delete usage stat')
    }
  }

  // Share-related methods
  async getListShares(listId) {
    try {
      const response = await axios.get(`${this.baseURL}/lists/${listId}/shares`)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to fetch list shares')
    }
  }

  async shareList(listId, shareData) {
    try {
      const response = await axios.post(`${this.baseURL}/lists/${listId}/shares`, shareData)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to share list')
    }
  }

  async updateShare(listId, shareId, shareData) {
    try {
      const response = await axios.patch(`${this.baseURL}/lists/${listId}/shares/${shareId}`, shareData)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to update share')
    }
  }

  async removeShare(listId, shareId) {
    try {
      const response = await axios.delete(`${this.baseURL}/lists/${listId}/shares/${shareId}`)
      return response.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to remove share')
    }
  }

  async getMyShares() {
    try {
      const response = await axios.get(`${this.baseURL}/shares/my-shares`)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to fetch my shares')
    }
  }

  async acceptShare(shareId) {
    try {
      const response = await axios.post(`${this.baseURL}/shares/${shareId}/accept`)
      return response.data.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to accept share')
    }
  }

  async declineShare(shareId) {
    try {
      const response = await axios.post(`${this.baseURL}/shares/${shareId}/decline`)
      return response.data
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to decline share')
    }
  }

  // Helper method to get item counts for lists
  async getListsWithCounts() {
    try {
      const lists = await this.getLists()
      
      // For each list, get the item counts
      const listsWithCounts = await Promise.all(
        lists.map(async (list) => {
          try {
            const items = await this.getListItems(list.id)
            const totalItems = items.length
            const completedItems = items.filter(item => item.is_completed).length
            const pendingItems = totalItems - completedItems
            
            return {
              ...list,
              item_counts: {
                total: totalItems,
                completed: completedItems,
                pending: pendingItems
              }
            }
          } catch (error) {
            // If we can't get items for this list, just return the list without counts
            return {
              ...list,
              item_counts: {
                total: 0,
                completed: 0,
                pending: 0
              }
            }
          }
        })
      )
      
      return listsWithCounts
    } catch (error) {
      throw new Error('Failed to fetch lists with counts')
    }
  }
}

export default new ListService() 