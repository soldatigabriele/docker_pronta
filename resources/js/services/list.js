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