import type { ItemFormData } from '@/composables/useItems'
import axios from 'axios'

export const ItemsApi = {
  // Get all items with filters and pagination
  async getAll(params: any = {}) {
    try {

      const response = await axios.get('/api/items', { params })
      return response.data
    } catch (error: any) {

      throw error.response?.data || error
    }
  },

  // Get specific item by ID
  async getById(id: number) {
    try {
      const response = await axios.get(`/api/items/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  },

  // Create new item
  async create(data: ItemFormData) {
    try {
      const response = await axios.post('/api/items', data)
      return response.data
    } catch (error: any) {
      console.error('ItemsApi.create error:', error)
      throw error.response?.data || error
    }
  },

  // Update existing item
  async update(id: number, data: ItemFormData) {
    try {
      const response = await axios.put(`/api/items/${id}`, data)
      return response.data
    } catch (error: any) {
      console.error('ItemsApi.update error:', error)
      throw error.response?.data || error
    }
  },

  // Delete item
  async delete(id: number) {
    try {
      const response = await axios.delete(`/api/items/${id}`)
      return response.data
    } catch (error: any) {
      console.error('ItemsApi.delete error:', error)
      throw error.response?.data || error
    }
  },

  // Get item statistics
  async getStats() {
    try {
      const response = await axios.get('/api/items/stats')
      return response.data
    } catch (error: any) {
      console.error('ItemsApi.getStats error:', error)
      throw error.response?.data || error
    }
  }
}
