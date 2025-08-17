// Update the import path below if your axios instance is located elsewhere
import type { AxiosResponse } from 'axios'
import axios from 'axios'

// API Base URL
const API_BASE_URL = '/api/variants'

// Types
export interface Variant {
  id_variant: number
  id: number // Keep this for backward compatibility
  id_product: number
  sku: string
  name: string
  variant_values: Record<string, string> // {"size": "L", "temperature": "Hot"}
  price: number
  cost_price?: number
  barcode?: string
  image?: string
  active: boolean
  created_by?: number
  updated_by?: number
  deleted_by?: number
  created_at: string
  updated_at: string
  deleted_at?: string
  
  // Computed fields from Model
  formatted_price: string
  profit_margin?: number
  variant_display_name: string
  stock_info: {
    current_stock: number
    reserved_stock: number
    available_stock: number
    reorder_level: number
  }
  
  // Relations
  product?: {
    id_product: number
    name: string
    category?: any
  }
  creator?: { id: number; name: string; email: string }
  updater?: { id: number; name: string; email: string }
  deleter?: { id: number; name: string; email: string }
  inventory?: any
}

export interface VariantCreateData {
  id_product: number
  name: string
  variant_values: Record<string, string>
  price: number
  cost_price?: number
  sku?: string
  barcode?: string
  image?: string
  active?: boolean
  reorder_level?: number
  max_stock_level?: number
}

export interface VariantUpdateData {
  name: string
  variant_values: Record<string, string>
  price: number
  cost_price?: number
  sku?: string
  barcode?: string
  image?: string
  active?: boolean
}

export interface VariantBulkCreateData {
  id_product: number
  base_price: number
  base_cost_price?: number
  attributes: Array<{
    name: string
    values: string[]
  }>
  price_adjustments: Record<string, number>
}

export interface VariantFilters {
  search?: string
  product_id?: number
  status?: 'active' | 'inactive'
  variant_filters?: Record<string, string>
  stock_status?: 'low_stock' | 'in_stock'
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export interface VariantFormData {
  id_product: number
  name: string
  variant_values: Record<string, string>
  price: number
  cost_price: number
  sku: string
  barcode: string
  image: string
  active: boolean
  reorder_level: number
  max_stock_level: number
}

export interface VariantStats {
  total_variants: number
  active_variants: number
  inactive_variants: number
  low_stock_variants: number
  total_products_with_variants: number
  average_price: number
  highest_price: number
  lowest_price: number
}

export interface VariantAttributes {
  [key: string]: string[]
}

// API Response Types
export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data?: T
  pagination?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  error?: string
  errors?: Record<string, string[]>
}

// Validation Types
export interface VariantValidation {
  valid: boolean
  error?: string
}

export class VariantsApi {
  // Get all variants with filters and pagination
  static async getAll(filters: VariantFilters = {}): Promise<ApiResponse<Variant[]>> {
    try {
      const params = new URLSearchParams()
      
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          if (key === 'variant_filters' && typeof value === 'object') {
            params.append(key, JSON.stringify(value))
          } else {
            params.append(key, String(value))
          }
        }
      })

      const response: AxiosResponse<ApiResponse<Variant[]>> = await axios.get(
        `${API_BASE_URL}?${params.toString()}`
      )
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  // Get variant by ID
  static async getById(id: number): Promise<ApiResponse<Variant>> {
    try {
      const response: AxiosResponse<ApiResponse<Variant>> = await axios.get(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  // Create new variant
  static async create(data: VariantCreateData): Promise<ApiResponse<Variant>> {
    try {
      const response: AxiosResponse<ApiResponse<Variant>> = await axios.post(API_BASE_URL, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  // Update variant
  static async update(id: number, data: VariantUpdateData): Promise<ApiResponse<Variant>> {
    try {
      const response: AxiosResponse<ApiResponse<Variant>> = await axios.put(`${API_BASE_URL}/${id}`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  // Delete variant
  static async delete(id: number): Promise<ApiResponse> {
    try {
      const response: AxiosResponse<ApiResponse> = await axios.delete(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  // Bulk create variants
  static async bulkCreate(data: VariantBulkCreateData): Promise<ApiResponse<Variant[]>> {
    try {
      const response: AxiosResponse<ApiResponse<Variant[]>> = await axios.post(`${API_BASE_URL}/bulk-create`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  // Get variant statistics
  static async getStats(): Promise<ApiResponse<VariantStats>> {
    try {
      const response: AxiosResponse<ApiResponse<VariantStats>> = await axios.get(`${API_BASE_URL}/stats`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  // Get variant attributes (for filters)
  static async getAttributes(): Promise<ApiResponse<VariantAttributes>> {
    try {
      const response: AxiosResponse<ApiResponse<VariantAttributes>> = await axios.get(`${API_BASE_URL}/attributes`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  // Get variants by product
  static async getByProduct(productId: number): Promise<ApiResponse<Variant[]>> {
    try {
      const response: AxiosResponse<ApiResponse<Variant[]>> = await axios.get(
        `${API_BASE_URL}?product_id=${productId}`
      )
      return response.data
    } catch (error: any) {
      throw error.response?.data || error
    }
  }

  // Validation methods
  static validateSku(sku: string): VariantValidation {
    if (!sku) {
      return { valid: true } // SKU is optional, will be auto-generated
    }
    
    if (sku.length < 3) {
      return { valid: false, error: 'SKU harus minimal 3 karakter' }
    }
    
    if (sku.length > 100) {
      return { valid: false, error: 'SKU maksimal 100 karakter' }
    }
    
    if (!/^[A-Z0-9\-_]+$/i.test(sku)) {
      return { valid: false, error: 'SKU hanya boleh mengandung huruf, angka, dash, dan underscore' }
    }
    
    return { valid: true }
  }

  static validatePrice(price: number): VariantValidation {
    if (!price || price <= 0) {
      return { valid: false, error: 'Harga harus lebih dari 0' }
    }
    
    if (price > 99999999.99) {
      return { valid: false, error: 'Harga terlalu besar' }
    }
    
    return { valid: true }
  }

  static validateVariantValues(values: Record<string, string>): VariantValidation {
    if (!values || Object.keys(values).length === 0) {
      return { valid: false, error: 'Variant values tidak boleh kosong' }
    }
    
    for (const [key, value] of Object.entries(values)) {
      if (!key.trim() || !value.trim()) {
        return { valid: false, error: 'Semua variant values harus diisi' }
      }
    }
    
    return { valid: true }
  }

  static validateBarcode(barcode: string): VariantValidation {
    if (!barcode) {
      return { valid: true } // Barcode is optional
    }
    
    if (barcode.length < 8) {
      return { valid: false, error: 'Barcode minimal 8 karakter' }
    }
    
    if (barcode.length > 100) {
      return { valid: false, error: 'Barcode maksimal 100 karakter' }
    }
    
    return { valid: true }
  }

  // Utility methods
  static formatPrice(price: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(price)
  }

  static generateVariantName(productName: string, variantValues: Record<string, string>): string {
    const values = Object.values(variantValues).join(' ')
    return `${productName} - ${values}`
  }

  static calculateProfitMargin(price: number, costPrice: number): number | null {
    if (!costPrice || costPrice === 0) return null
    const margin = ((price - costPrice) / costPrice) * 100
    return Math.ceil(margin)
  }

  static isLowStock(currentStock: number, reorderLevel: number): boolean {
    return currentStock <= reorderLevel
  }
}
