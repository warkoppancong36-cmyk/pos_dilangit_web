import axios from 'axios'

const API_BASE_URL = '/api/customers'

export interface Customer {
  id_customer: number
  name: string
  email?: string
  phone?: string
  address?: string
  created_at: string
  updated_at: string
}

export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data: T
}

export interface ApiError {
  success: false
  message: string
  errors?: Record<string, string[]>
}

export class CustomerApi {
  // Get all customers
  static async getCustomers(params?: {
    search?: string
    per_page?: number
    page?: number
  }): Promise<ApiResponse<Customer[]>> {
    try {
      const response = await axios.get(`${API_BASE_URL}`, { params })
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get customer by ID
  static async getCustomer(id: number): Promise<ApiResponse<Customer>> {
    try {
      const response = await axios.get(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Handle API errors
  private static handleError(error: any): ApiError {
    if (error.response) {
      return {
        success: false,
        message: error.response.data?.message || 'Server error occurred',
        errors: error.response.data?.errors
      }
    } else if (error.request) {
      return {
        success: false,
        message: 'Network error occurred'
      }
    } else {
      return {
        success: false,
        message: error.message || 'An unexpected error occurred'
      }
    }
  }
}
