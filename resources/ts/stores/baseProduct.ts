import { defineStore } from 'pinia'
import { $api } from '@/utils/api'
import axios from 'axios'
import { CategoriesApi } from '@/utils/api/CategoriesApi'

export interface BaseProduct {
  id_base_product: number
  name: string
  sku: string | null
  description: string | null
  category_id: number
  unit: string
  cost_per_unit: number
  current_stock: number
  min_stock: number
  image_url: string | null
  is_active: boolean
  stock_status: 'in_stock' | 'low_stock' | 'out_of_stock'
  formatted_cost: string
  formatted_cost_per_unit: string
  category?: {
    id_category: number
    name: string
  }
  created_at: string
  updated_at: string
}

export interface BaseProductMovement {
  id_movement: number
  base_product_id: number
  movement_type: 'stock_in' | 'stock_out' | 'adjustment'
  quantity: number
  previous_stock: number
  new_stock: number
  reason: string
  notes: string | null
  reference_number: string | null
  user_id: number
  created_at: string
  base_product?: BaseProduct
  user?: {
    id: number
    name: string
  }
}

export interface ProductComposition {
  id_composition: number
  product_id: number
  base_product_id: number
  quantity: number
  cost_per_unit: number
  notes: string | null
  is_active: boolean
  max_producible_quantity: number
  formatted_cost_per_unit: string
  product?: {
    id_product: number
    name: string
  }
  base_product?: BaseProduct
  created_at: string
  updated_at: string
}

export interface ApiResponse<T> {
  success: boolean
  message: string
  data: T
  pagination?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
  }
}

export const useBaseProductStore = defineStore('baseProduct', {
  state: () => ({
    baseProducts: [] as BaseProduct[],
    movements: [] as BaseProductMovement[],
    compositions: [] as ProductComposition[],
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    }
  }),

  actions: {
    // Base Products Actions
    async fetchBaseProducts(params?: any): Promise<ApiResponse<BaseProduct[]>> {
      this.loading = true
      try {
        const response = await axios.get('/api/base-products', {
          params
        })
        console.log('fetchBaseProducts response:', response.data)

        // Handle different response structures
        if (response.data.data) {
          this.baseProducts = Array.isArray(response.data.data) ? response.data.data : []
        } else {
          this.baseProducts = []
        }

        if (response.data.pagination) {
          this.pagination = response.data.pagination
        } else if (response.data.meta) {
          this.pagination = response.data.meta
        }

        console.log('Base products loaded:', this.baseProducts.length)
        return response.data
      } catch (error) {
        console.error('Error fetching base products:', error)
        this.baseProducts = []
        throw error
      } finally {
        this.loading = false
      }
    },

    async createBaseProduct(data: FormData): Promise<ApiResponse<BaseProduct>> {
      try {
        const response = await axios.post('/api/base-products', data, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        return response.data
      } catch (error) {
        console.error('Error creating base product:', error)
        throw error
      }
    },

    async updateBaseProduct(id: number, data: FormData): Promise<ApiResponse<BaseProduct>> {
      try {
        // Laravel needs _method=PUT for FormData with file uploads
        data.append('_method', 'PUT')
        const response = await axios.post(`/api/base-products/${id}`, data, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        return response.data
      } catch (error) {
        console.error('Error updating base product:', error)
        throw error
      }
    },

    async deleteBaseProduct(id: number): Promise<ApiResponse<any>> {
      try {
        const response = await axios.delete(`/api/base-products/${id}`)
        return response.data
      } catch (error) {
        console.error('Error deleting base product:', error)
        throw error
      }
    },

    async updateStock(id: number, data: {
      movement_type: string
      quantity: number
      reason: string
      notes?: string
      reference_number?: string
    }): Promise<ApiResponse<BaseProductMovement>> {
      try {
        const response = await axios.post(`/api/base-products/${id}/stock`, data)
        return response.data
      } catch (error) {
        console.error('Error updating stock:', error)
        throw error
      }
    },

    async fetchCategories(): Promise<ApiResponse<any[]>> {
      try {
        const response = await CategoriesApi.getCategories({
          page: 1,
          per_page: 100,
          status: 'active'
        })
        return {
          success: response.success,
          data: response.data,
          message: response.message || 'Categories retrieved successfully'
        }
      } catch (error) {
        console.error('Error fetching categories:', error)
        throw error
      }
    },

    // Product Compositions Actions
    async fetchCompositions(params?: any): Promise<ApiResponse<ProductComposition[]>> {
      this.loading = true
      try {
        const response = await $api('/product-compositions', {
          method: 'GET',
          query: params
        })
        this.compositions = response.data
        if (response.pagination) {
          this.pagination = response.pagination
        }
        return response
      } catch (error) {
        console.error('Error fetching compositions:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async createComposition(data: {
      product_id: number
      base_product_id: number
      quantity: number
      notes?: string
      is_active: boolean
    }): Promise<ApiResponse<ProductComposition>> {
      try {
        const response = await $api('/product-compositions', {
          method: 'POST',
          body: data
        })
        return response
      } catch (error) {
        console.error('Error creating composition:', error)
        throw error
      }
    },

    async updateComposition(id: number, data: {
      product_id: number
      base_product_id: number
      quantity: number
      notes?: string
      is_active: boolean
    }): Promise<ApiResponse<ProductComposition>> {
      try {
        const response = await $api(`/product-compositions/${id}`, {
          method: 'PUT',
          body: data
        })
        return response
      } catch (error) {
        console.error('Error updating composition:', error)
        throw error
      }
    },

    async deleteComposition(id: number): Promise<ApiResponse<any>> {
      try {
        const response = await $api(`/product-compositions/${id}`, {
          method: 'DELETE'
        })
        return response
      } catch (error) {
        console.error('Error deleting composition:', error)
        throw error
      }
    },

    async fetchProducts(): Promise<ApiResponse<any[]>> {
      try {
        const response = await $api('/product-compositions/products', {
          method: 'GET'
        })
        return response
      } catch (error) {
        console.error('Error fetching products:', error)
        throw error
      }
    },

    async fetchAvailableBaseProducts(): Promise<ApiResponse<BaseProduct[]>> {
      try {
        const response = await axios.get('/api/product-compositions/base-products')
        return response.data
      } catch (error) {
        console.error('Error fetching available base products:', error)
        throw error
      }
    },

    async checkProductionAvailability(productId: number, quantity: number): Promise<ApiResponse<any>> {
      try {
        const response = await $api(`/product-compositions/production-check/${productId}`, {
          method: 'GET',
          query: { quantity }
        })
        return response
      } catch (error) {
        console.error('Error checking production availability:', error)
        throw error
      }
    },

    // Stock Movements Actions
    async fetchMovements(baseProductId: number, params?: any): Promise<ApiResponse<BaseProductMovement[]>> {
      try {
        const response = await axios.get(`/api/base-products/${baseProductId}/movements`, {
          params
        })
        this.movements = response.data.data
        return response.data
      } catch (error) {
        console.error('Error fetching movements:', error)
        throw error
      }
    },

    // Bulk Actions
    async bulkUpdateBaseProducts(ids: number[], data: any): Promise<ApiResponse<any>> {
      try {
        const response = await axios.post('/api/base-products/bulk-update', {
          ids,
          ...data
        })
        return response.data
      } catch (error) {
        console.error('Error bulk updating base products:', error)
        throw error
      }
    },

    async bulkDeleteBaseProducts(ids: number[]): Promise<ApiResponse<any>> {
      try {
        const response = await axios.delete('/api/base-products/bulk-delete', {
          data: { ids }
        })
        return response.data
      } catch (error) {
        console.error('Error bulk deleting base products:', error)
        throw error
      }
    },

    async bulkUpdateCompositions(ids: number[], data: any): Promise<ApiResponse<any>> {
      try {
        const response = await $api('/product-compositions/bulk-update', {
          method: 'POST',
          body: {
            ids,
            ...data
          }
        })
        return response
      } catch (error) {
        console.error('Error bulk updating compositions:', error)
        throw error
      }
    },

    async bulkDeleteCompositions(ids: number[]): Promise<ApiResponse<any>> {
      try {
        const response = await $api('/product-compositions/bulk-delete', {
          method: 'DELETE',
          body: { ids }
        })
        return response
      } catch (error) {
        console.error('Error bulk deleting compositions:', error)
        throw error
      }
    },

    async getBaseProductsForSelection(): Promise<ApiResponse<BaseProduct[]>> {
      try {
        const response = await axios.get('/api/base-products/for-selection')
        return response.data
      } catch (error) {
        console.error('Error fetching base products for selection:', error)
        throw error
      }
    }
  },

  getters: {
    // Base Products Getters
    getBaseProductById: (state) => (id: number) => {
      return state.baseProducts.find(bp => bp.id_base_product === id)
    },

    getActiveBaseProducts: (state) => {
      return state.baseProducts.filter(bp => bp.is_active)
    },

    getInStockBaseProducts: (state) => {
      return state.baseProducts.filter(bp => bp.stock_status === 'in_stock')
    },

    getLowStockBaseProducts: (state) => {
      return state.baseProducts.filter(bp => bp.stock_status === 'low_stock')
    },

    getOutOfStockBaseProducts: (state) => {
      return state.baseProducts.filter(bp => bp.stock_status === 'out_of_stock')
    },

    // Compositions Getters
    getCompositionById: (state) => (id: number) => {
      return state.compositions.find(c => c.id_composition === id)
    },

    getCompositionsByProduct: (state) => (productId: number) => {
      return state.compositions.filter(c => c.product_id === productId)
    },

    getCompositionsByBaseProduct: (state) => (baseProductId: number) => {
      return state.compositions.filter(c => c.base_product_id === baseProductId)
    },

    getActiveCompositions: (state) => {
      return state.compositions.filter(c => c.is_active)
    }
  }
})
