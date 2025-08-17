// Inventory API Utility
import axios from 'axios'

// Define types locally to avoid circular imports
interface Inventory {
  id_inventory: number
  id_product?: number | null
  id_item?: number | null
  current_stock: number
  reserved_stock: number
  available_stock: number
  reorder_level: number
  max_stock_level?: number
  average_cost: number
  stock_status: string
  is_low_stock: boolean
  last_stock_update?: string
  created_at: string
  updated_at: string
  product?: {
    id_product: number
    name: string
    sku: string
    category?: {
      name: string
    }
  }
  item?: {
    id_item: number
    name: string
    item_code: string
    description?: string
    unit: string
    cost_per_unit: string
  }
}

interface InventoryStats {
  total_items: number
  low_stock_items: number
  out_of_stock_items: number
  overstock_items: number
  total_stock_value: number
  total_stock_quantity: number
  total_reserved_stock: number
  items_need_restock: number
}

interface InventoryFilters {
  search?: string
  stock_status?: string
  sort_by?: string
  sort_order?: string
  page?: number
  per_page?: number
}

interface InventoryMovement {
  id_movement: number
  id_inventory: number
  movement_type: string
  quantity: number
  stock_before: number
  stock_after: number
  reason: string
  reference_number?: string
  cost_per_unit?: number
  total_cost?: number
  notes?: string
  created_by: number
  movement_date: string
  created_at: string
  updated_at: string
  user?: {
    id: number
    name: string
  }
}

interface StockUpdateData {
  movement_type: string
  quantity: number
  reason: string
  reference_number?: string
  cost_per_unit?: number
  notes?: string
}

interface BulkStockUpdateData {
  items: Array<{
    id_inventory: number
    movement_type: string
    quantity: number
    reason: string
    reference_number?: string
    cost_per_unit?: number
    notes?: string
  }>
}

// Define API response types
interface ApiError {
  message: string
  success?: boolean
  errors?: Record<string, string[]>
}

interface InventoryResponse {
  success: boolean
  message?: string
  data: {
    data: Inventory[]
    total: number
    per_page: number
    current_page: number
    last_page: number
    first_page_url: string
    last_page_url: string
    next_page_url?: string
    prev_page_url?: string
    from: number
    to: number
    links: Array<{
      url?: string
      label: string
      active: boolean
    }>
  }
}

interface InventorySingleResponse {
  success: boolean
  message?: string
  data: Inventory
}

interface InventoryStatsResponse {
  success: boolean
  message?: string
  data: InventoryStats
}

interface InventoryMovementResponse {
  success: boolean
  message?: string
  data: InventoryMovement[]
  total: number
  per_page: number
  current_page: number
  last_page: number
}

interface StockUpdateResponse {
  success: boolean
  message?: string
  data: Inventory
}

interface BulkStockUpdateResponse {
  success: boolean
  message?: string
  data: {
    updated_items: number
    item_ids: number[]
  }
}

const API_BASE_URL = '/api/inventory'

export class InventoryApi {
  // Get all inventory items with filters and pagination
  static async getAll(params: Partial<InventoryFilters> & { page?: number; per_page?: number }): Promise<InventoryResponse> {
    try {
      const response = await axios.get(API_BASE_URL, { params })
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get single inventory item by ID
  static async getById(id: number): Promise<InventorySingleResponse> {
    try {
      const response = await axios.get(`${API_BASE_URL}/${id}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get inventory statistics
  static async getStats(): Promise<InventoryStatsResponse> {
    try {
      const response = await axios.get(`${API_BASE_URL}/stats`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get low stock alerts
  static async getLowStockAlerts(): Promise<InventoryResponse> {
    try {
      const response = await axios.get(`${API_BASE_URL}/low-stock-alerts`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Update stock for single item
  static async updateStock(id: number, data: StockUpdateData): Promise<StockUpdateResponse> {
    try {
      const response = await axios.post(`${API_BASE_URL}/${id}/update-stock`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Bulk update stock for multiple items
  static async bulkUpdateStock(data: BulkStockUpdateData): Promise<BulkStockUpdateResponse> {
    try {
      const response = await axios.post(`${API_BASE_URL}/bulk-update-stock`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Set reorder level for inventory item
  static async setReorderLevel(id: number, data: { reorder_level: number; max_stock_level?: number }): Promise<InventorySingleResponse> {
    try {
      const response = await axios.post(`${API_BASE_URL}/${id}/reorder-level`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get inventory movements
  static async getMovements(params?: { 
    id_inventory?: number; 
    start_date?: string; 
    end_date?: string; 
    movement_type?: string;
    page?: number;
    per_page?: number;
  }): Promise<InventoryMovementResponse> {
    try {
      const url = params?.id_inventory 
        ? `${API_BASE_URL}/${params.id_inventory}/movements`
        : `${API_BASE_URL}/movements`
      
      const response = await axios.get(url, { params })
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

  // Stock movement type options
  static getMovementTypes() {
    return [
      { value: 'stock_in', label: 'Stock Masuk', color: 'success' },
      { value: 'stock_out', label: 'Stock Keluar', color: 'warning' },
      { value: 'adjustment', label: 'Penyesuaian', color: 'info' },
      { value: 'transfer', label: 'Transfer', color: 'primary' },
      { value: 'return', label: 'Retur', color: 'success' },
      { value: 'damaged', label: 'Rusak', color: 'error' },
      { value: 'expired', label: 'Kadaluarsa', color: 'error' }
    ]
  }

  // Stock status options
  static getStockStatusOptions() {
    return [
      { value: 'all', label: 'Semua Status' },
      { value: 'in_stock', label: 'Tersedia' },
      { value: 'low_stock', label: 'Stok Menipis' },
      { value: 'out_of_stock', label: 'Habis' },
      { value: 'overstock', label: 'Stok Berlebih' }
    ]
  }
}
