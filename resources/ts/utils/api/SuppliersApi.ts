// Update the import path below if your axios instance is located elsewhere
import type { AxiosResponse } from 'axios'
import axios from 'axios'

// API Base URL
const API_BASE_URL = '/api/suppliers'

// Types
export interface Supplier {
  id_supplier: number
  id: number // Keep this for backward compatibility
  code: string
  name: string
  email?: string
  phone?: string
  address?: string
  city?: string
  province?: string
  postal_code?: string
  contact_person?: string
  tax_number?: string
  bank_name?: string
  bank_account?: string
  bank_account_name?: string
  notes?: string
  status: 'active' | 'inactive'
  created_by?: number
  updated_by?: number
  deleted_by?: number
  created_at: string
  updated_at: string
  deleted_at?: string

  // Computed fields
  total_purchases?: number
  total_purchase_amount?: number
  last_purchase_date?: string
  full_address?: string

  // Relations
  creator?: { id: number; name: string; email: string }
  updater?: { id: number; name: string; email: string }
  deleter?: { id: number; name: string; email: string }
  purchases?: any[]
}

export interface SupplierCreateData {
  code: string
  name: string
  email?: string
  phone?: string
  address?: string
  city?: string
  province?: string
  postal_code?: string
  contact_person?: string
  tax_number?: string
  bank_name?: string
  bank_account?: string
  bank_account_name?: string
  notes?: string
  status: 'active' | 'inactive'
}

export interface SupplierUpdateData {
  name: string
  email?: string
  phone?: string
  address?: string
  city?: string
  province?: string
  postal_code?: string
  contact_person?: string
  tax_number?: string
  bank_name?: string
  bank_account?: string
  bank_account_name?: string
  notes?: string
  status: 'active' | 'inactive'
}

export interface SupplierFormData {
  name: string
  code?: string
  contact_person?: string
  phone?: string
  email?: string
  address?: string
  city?: string
  province?: string
  postal_code?: string
  tax_number?: string
  bank_name?: string
  bank_account?: string
  bank_account_name?: string
  notes?: string
  status: 'active' | 'inactive'
}

export interface SupplierFilters {
  search?: string
  status?: 'active' | 'inactive' | undefined
  city?: string
  province?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface SupplierStats {
  total_suppliers: number
  active_suppliers: number
  inactive_suppliers: number
  suppliers_with_purchases: number
  total_cities: number
  total_provinces: number
}

interface ApiResponse<T> {
  success: boolean
  message: string
  data: T
  pagination?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

interface SupplierResponse extends ApiResponse<Supplier> { }
interface SuppliersResponse extends ApiResponse<Supplier[]> { }
interface SupplierStatsResponse extends ApiResponse<SupplierStats> { }

export class SuppliersApi {
  // Get all suppliers with filters
  static async getAll(filters: SupplierFilters = {}): Promise<SuppliersResponse> {
    try {
      const params = new URLSearchParams()


      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, String(value))
        }
      })

      const url = `${API_BASE_URL}?${params.toString()}`

      const response: AxiosResponse<SuppliersResponse> = await axios.get(url)

      return response.data
    } catch (error: any) {
      console.error('SuppliersApi.getAll error:', error)
      throw error.response?.data || { success: false, message: 'Failed to fetch suppliers' }
    }
  }

  // Get single supplier
  static async getById(id: number): Promise<SupplierResponse> {
    try {
      const response: AxiosResponse<SupplierResponse> = await axios.get(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || { success: false, message: 'Failed to fetch supplier' }
    }
  }

  // Create new supplier
  static async create(data: SupplierCreateData): Promise<SupplierResponse> {
    try {
      const response: AxiosResponse<SupplierResponse> = await axios.post(API_BASE_URL, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || { success: false, message: 'Failed to create supplier' }
    }
  }

  // Update supplier
  static async update(id: number, data: SupplierUpdateData): Promise<SupplierResponse> {
    try {
      const response: AxiosResponse<SupplierResponse> = await axios.put(`${API_BASE_URL}/${id}`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || { success: false, message: 'Failed to update supplier' }
    }
  }

  // Delete supplier
  static async delete(id: number): Promise<ApiResponse<null>> {
    try {
      const response: AxiosResponse<ApiResponse<null>> = await axios.delete(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || { success: false, message: 'Failed to delete supplier' }
    }
  }

  // Toggle supplier active status
  static async toggleActive(id: number): Promise<SupplierResponse> {
    try {
      const response: AxiosResponse<SupplierResponse> = await axios.put(`${API_BASE_URL}/${id}/toggle-active`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || { success: false, message: 'Failed to toggle supplier status' }
    }
  }

  // Get supplier statistics
  static async getStats(): Promise<SupplierStatsResponse> {
    try {
      const response: AxiosResponse<SupplierStatsResponse> = await axios.get(`${API_BASE_URL}/stats`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || { success: false, message: 'Failed to fetch supplier statistics' }
    }
  }

  // Get cities list
  static async getCities(): Promise<ApiResponse<string[]>> {
    try {
      const response: AxiosResponse<ApiResponse<string[]>> = await axios.get(`${API_BASE_URL}/cities`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || { success: false, message: 'Failed to fetch cities' }
    }
  }

  // Get provinces list
  static async getProvinces(): Promise<ApiResponse<string[]>> {
    try {
      const response: AxiosResponse<ApiResponse<string[]>> = await axios.get(`${API_BASE_URL}/provinces`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || { success: false, message: 'Failed to fetch provinces' }
    }
  }

  // Validation helpers
  static validateEmail(email: string): { valid: boolean; error?: string } {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(email)) {
      return { valid: false, error: 'Please enter a valid email address' }
    }
    return { valid: true }
  }

  static validatePhone(phone: string): { valid: boolean; error?: string } {
    const phoneRegex = /^[\+]?[\d\s\-\(\)]{7,20}$/
    if (!phoneRegex.test(phone)) {
      return { valid: false, error: 'Please enter a valid phone number' }
    }
    return { valid: true }
  }

  static validateCode(code: string): { valid: boolean; error?: string } {
    if (code.length < 3) {
      return { valid: false, error: 'Supplier code must be at least 3 characters' }
    }
    if (code.length > 50) {
      return { valid: false, error: 'Supplier code must not exceed 50 characters' }
    }
    return { valid: true }
  }
}
