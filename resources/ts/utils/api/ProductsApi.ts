// Products API Utility
import type {
    Product,
    ProductFilters,
    ProductFormData,
    ProductStats
} from '@/composables/useProducts';
import axios from 'axios';

// Define API response types
interface ApiError {
  message: string
  success?: boolean
  errors?: Record<string, string[]>
}

interface ProductResponse {
  success: boolean
  message?: string
  data: Product[]
  total: number
  per_page: number
  current_page: number
  last_page: number
  pagination?: {
    total: number
    current_page: number
    per_page: number
    last_page: number
  }
}

interface ProductSingleResponse {
  success: boolean
  message?: string
  data: Product
}

interface ProductStatsResponse {
  success: boolean
  message?: string
  data: ProductStats
}

const API_BASE_URL = '/api/products'

export class ProductsApi {
  // Get all products with filters and pagination
  static async getAll(params: Partial<ProductFilters> & { page?: number; per_page?: number }): Promise<ProductResponse> {
    try {
      console.log('[ProductsApi.getAll] Making API call with params:', params);
      const response = await axios.get(API_BASE_URL, { params })
      console.log('[ProductsApi.getAll] API response received:', response.data);
      return response.data
    } catch (error: any) {
      console.error('[ProductsApi.getAll] API error:', error);
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get single product by ID
  static async getById(id: number): Promise<ProductSingleResponse> {
    try {
      const response = await axios.get(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Create new product
  static async create(data: ProductFormData, image?: File): Promise<ProductSingleResponse> {
    try {
      const formData = new FormData()
      
      // Append all form fields
      Object.entries(data).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          if (key === 'dimensions' || key === 'tags') {
            formData.append(key, JSON.stringify(value))
          } else {
            formData.append(key, String(value))
          }
        }
      })

      // Append image if provided
      if (image) {
        formData.append('image', image)
      }

      const response = await axios.post(API_BASE_URL, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Update product
  static async update(id: number, data: ProductFormData, image?: File): Promise<ProductSingleResponse> {
    try {
      const formData = new FormData()
      formData.append('_method', 'PUT')
      
      // Append all form fields
      Object.entries(data).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          if (key === 'dimensions' || key === 'tags') {
            formData.append(key, JSON.stringify(value))
          } else {
            formData.append(key, String(value))
          }
        }
      })

      // Append image if provided
      if (image) {
        formData.append('image', image)
      }

      const response = await axios.post(`${API_BASE_URL}/${id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Delete product
  static async delete(id: number): Promise<{ success: boolean; message: string }> {
    try {
      const response = await axios.delete(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Toggle active status
  static async toggleActive(id: number): Promise<ProductSingleResponse> {
    try {
      const response = await axios.post(`${API_BASE_URL}/${id}/toggle-active`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Toggle featured status
  static async toggleFeatured(id: number): Promise<ProductSingleResponse> {
    try {
      const response = await axios.post(`${API_BASE_URL}/${id}/toggle-featured`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Bulk delete products
  static async bulkDelete(ids: number[]): Promise<{ success: boolean; message: string }> {
    try {
      const response = await axios.post(`${API_BASE_URL}/bulk-delete`, { ids })
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get product statistics
  static async getStats(): Promise<ProductStatsResponse> {
    try {
      const response = await axios.get(`${API_BASE_URL}/stats`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Validate image file
  static validateImage(file: File): { valid: boolean; error?: string } {
    const maxSize = 2 * 1024 * 1024 // 2MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']

    if (!allowedTypes.includes(file.type)) {
      return {
        valid: false,
        error: 'Format file tidak didukung. Gunakan JPEG, PNG, JPG, atau GIF.'
      }
    }

    if (file.size > maxSize) {
      return {
        valid: false,
        error: 'Ukuran file terlalu besar. Maksimal 2MB.'
      }
    }

    return { valid: true }
  }

  // Format price for display
  static formatPrice(price: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(price)
  }

  // Calculate profit margin
  static calculateProfitMargin(price: number, cost: number): number {
    if (cost <= 0) return 0
    const margin = ((price - cost) / cost) * 100
    return Math.ceil(margin)
  }

  // Generate SKU suggestion
  static generateSKU(productName: string): string {
    const prefix = productName
      .replace(/[^A-Za-z]/g, '')
      .substring(0, 3)
      .toUpperCase()
    
    const timestamp = new Date().toISOString().slice(2, 10).replace(/-/g, '')
    const random = Math.random().toString(36).substring(2, 5).toUpperCase()
    
    return `${prefix}${timestamp}${random}`
  }

  // Validate SKU format
  static validateSKU(sku: string): { valid: boolean; error?: string } {
    if (sku.length < 3) {
      return {
        valid: false,
        error: 'SKU minimal 3 karakter'
      }
    }

    if (sku.length > 20) {
      return {
        valid: false,
        error: 'SKU maksimal 20 karakter'
      }
    }

    if (!/^[A-Za-z0-9-_]+$/.test(sku)) {
      return {
        valid: false,
        error: 'SKU hanya boleh mengandung huruf, angka, dash (-), dan underscore (_)'
      }
    }

    return { valid: true }
  }

  // Handle API errors
  private static handleError(error: any): ApiError {
    if (error.response) {
      // Server responded with error status
      const { status, data } = error.response
      
      if (status === 422) {
        // Validation errors
        return {
          success: false,
          message: data.message || 'Validation failed',
          errors: data.errors || {}
        }
      }
      
      if (status === 404) {
        return {
          success: false,
          message: 'Produk tidak ditemukan'
        }
      }
      
      if (status === 500) {
        return {
          success: false,
          message: 'Terjadi kesalahan server'
        }
      }
      
      return {
        success: false,
        message: data.message || 'Terjadi kesalahan'
      }
    } else if (error.request) {
      // Network error
      return {
        success: false,
        message: 'Tidak dapat terhubung ke server'
      }
    } else {
      // Other error
      return {
        success: false,
        message: error.message || 'Terjadi kesalahan tidak dikenal'
      }
    }
  }
}
