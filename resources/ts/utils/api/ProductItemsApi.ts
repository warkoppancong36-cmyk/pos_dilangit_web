import type { ProductItemFormData } from '@/composables/useProductItems'
import axios from 'axios'

export const ProductItemsApi = {
  // Get all product items with filters and pagination
  async getAll(params: any = {}) {
    try {
      const response = await axios.get('/api/product-items', { params })
      return response.data
    } catch (error: any) {
      console.error('ProductItemsApi.getAll error:', error)
      throw error.response?.data || error
    }
  },

  // Get specific product item by ID
  async getById(id: number) {
    try {
      const response = await axios.get(`/api/product-items/${id}`)
      return response.data
    } catch (error: any) {
      console.error('ProductItemsApi.getById error:', error)
      throw error.response?.data || error
    }
  },

  // Create new product item
  async create(data: ProductItemFormData) {
    try {
      const response = await axios.post('/api/product-items', data)
      return response.data
    } catch (error: any) {
      console.error('ProductItemsApi.create error:', error)
      throw error.response?.data || error
    }
  },

  // Update existing product item
  async update(id: number, data: ProductItemFormData) {
    try {
      const response = await axios.put(`/api/product-items/${id}`, data)
      return response.data
    } catch (error: any) {
      console.error('ProductItemsApi.update error:', error)
      throw error.response?.data || error
    }
  },

  // Delete product item
  async delete(id: number) {
    try {
      const response = await axios.delete(`/api/product-items/${id}`)
      return response.data
    } catch (error: any) {
      console.error('ProductItemsApi.delete error:', error)
      throw error.response?.data || error
    }
  },

  // Get production capacity for a product
  async getProductionCapacity(productId: number) {
    try {
      const response = await axios.get(`/api/product-items/production-capacity/${productId}`)
      return response.data
    } catch (error: any) {
      console.error('ProductItemsApi.getProductionCapacity error:', error)
      throw error.response?.data || error
    }
  }
}
