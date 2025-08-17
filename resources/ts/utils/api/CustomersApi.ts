// Customers API Utility
import type {
  Customer,
  CustomerFilters,
  CustomerFormData,
  CustomerStats
} from '@/composables/useCustomers'
import axios from 'axios'

// Define API response types
interface ApiError {
  message: string
  success?: boolean
  errors?: Record<string, string[]>
}

interface CustomerResponse {
  success: boolean
  message?: string
  data: Customer[]
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

interface CustomerSingleResponse {
  success: boolean
  message?: string
  data: Customer
}

interface CustomerStatsResponse {
  success: boolean
  message?: string
  data: CustomerStats
}

interface CustomerBulkResponse {
  success: boolean
  message?: string
  data?: any
}

interface CustomerToggleResponse {
  success: boolean
  message?: string
  data: { active: boolean }
}

interface CustomerSuggestionsResponse {
  success: boolean
  message?: string
  data: string[]
}

const API_BASE_URL = '/api/customers'

export class CustomersApi {
  // Get all customers with filters and pagination
  static async getAll(params: Partial<CustomerFilters> & { page?: number; per_page?: number }): Promise<CustomerResponse> {
    try {
      const response = await axios.get(API_BASE_URL, { params })
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get single customer by ID
  static async getById(id: number): Promise<CustomerSingleResponse> {
    try {
      const response = await axios.get(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Create new customer
  static async create(data: CustomerFormData): Promise<CustomerSingleResponse> {
    try {
      const response = await axios.post(API_BASE_URL, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Update existing customer
  static async update(id: number, data: CustomerFormData): Promise<CustomerSingleResponse> {
    try {
      const response = await axios.put(`${API_BASE_URL}/${id}`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Delete customer
  static async delete(id: number): Promise<CustomerSingleResponse> {
    try {
      const response = await axios.delete(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Bulk delete customers
  static async bulkDelete(ids: number[]): Promise<CustomerBulkResponse> {
    try {
      const response = await axios.post(`${API_BASE_URL}/bulk-delete`, { ids })
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Toggle customer active status
  static async toggleActive(id: number): Promise<CustomerToggleResponse> {
    try {
      const response = await axios.post(`${API_BASE_URL}/${id}/toggle-active`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get customer statistics
  static async getStats(): Promise<CustomerStatsResponse> {
    try {
      const response = await axios.get(`${API_BASE_URL}/stats`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get search suggestions
  static async getSearchSuggestions(query: string): Promise<CustomerSuggestionsResponse> {
    try {
      const response = await axios.get(`${API_BASE_URL}/search-suggestions`, {
        params: { q: query }
      })
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Handle API errors
  private static handleError(error: any): ApiError {
    if (error.response) {
      // Server responded with error status
      return {
        message: error.response.data?.message || 'Terjadi kesalahan pada server',
        success: false,
        errors: error.response.data?.errors
      }
    } else if (error.request) {
      // Request was made but no response received
      return {
        message: 'Tidak dapat terhubung ke server',
        success: false
      }
    } else {
      // Something else happened
      return {
        message: error.message || 'Terjadi kesalahan yang tidak diketahui',
        success: false
      }
    }
  }

  // Utility method to format error for display
  static formatError(error: any): string {
    if (error.errors) {
      // Laravel validation errors
      const errorMessages = Object.values(error.errors).flat()
      return errorMessages.join(', ')
    }
    return error.message || 'Terjadi kesalahan yang tidak diketahui'
  }

  // Utility method to check if error is validation error
  static isValidationError(error: any): boolean {
    return error.errors && typeof error.errors === 'object'
  }

  // Utility method to get specific field error
  static getFieldError(error: any, field: string): string | null {
    if (error.errors && error.errors[field]) {
      return Array.isArray(error.errors[field]) 
        ? error.errors[field][0] 
        : error.errors[field]
    }
    return null
  }
}
