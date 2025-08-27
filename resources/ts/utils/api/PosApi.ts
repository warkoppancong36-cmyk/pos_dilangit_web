import axios from 'axios'

const API_BASE_URL = '/api/pos'

export interface OrderItem {
  id_order_item: number
  id_order: number
  id_product: number
  id_variant?: number
  item_name: string
  item_sku: string
  quantity: number
  unit_price: number
  total_price: number
  notes?: string
  created_at: string
  updated_at: string
  // Relationships
  product?: any
  variant?: any
}

export interface Order {
  id_order: number
  order_number: string
  id_customer?: number
  id_user: number
  id_shift?: number
  order_type: 'dine_in' | 'takeaway' | 'delivery'
  status: 'draft' | 'pending' | 'preparing' | 'ready' | 'completed' | 'cancelled'
  table_number?: string
  guest_count: number
  subtotal: number
  discount_amount: number
  discount_type?: string
  tax_amount: number
  service_charge: number
  total_amount: number
  notes?: string
  customer_info?: any
  order_date: string
  prepared_at?: string
  completed_at?: string
  created_at: string
  updated_at: string
  // Relationships
  customer?: any
  user?: any
  orderItems?: OrderItem[]
  payments?: Payment[]
  // Computed
  formatted_total_amount?: string
  formatted_subtotal?: string
  order_status_text?: string
  order_type_text?: string
  is_paid?: boolean
  total_paid?: number
  remaining_amount?: number
}

export interface TransactionItem {
  id_product: number
  product_name: string
  sku: string
  quantity: number
  unit_price: number
  subtotal: number
}

export interface Payment {
  id_payment: number
  id_order: number
  payment_method: 'cash' | 'card' | 'digital_wallet' | 'bank_transfer' | 'qris' | 'other'
  amount: number
  reference_number?: string
  status: 'pending' | 'completed' | 'failed' | 'cancelled'
  notes?: string
  payment_date: string
  processed_by: number
  created_at: string
  updated_at: string
  // Computed
  formatted_amount?: string
  payment_method_text?: string
  status_text?: string
}

export interface ProductForPos {
  id_product: number
  name: string
  slug: string
  description?: string
  sku: string
  barcode?: string
  price: string
  cost: string
  stock: number
  min_stock: number
  unit: string
  weight?: string
  dimensions?: string
  image?: string
  category_id: number
  brand?: string
  tags?: string
  meta_title?: string
  meta_description?: string
  status: string
  active: boolean
  featured: boolean
  available_in_kitchen: boolean
  available_in_bar: boolean
  created_by?: number
  updated_by?: number
  deleted_by?: number
  created_at: string
  updated_at: string
  deleted_at?: string
  image_url?: string
  stock_status: string
  formatted_price: string
  formatted_cost: string
  profit_margin: number
  stock_value: number
  category?: {
    id_category: number
    name: string
    description?: string
    image?: string
    active: boolean
    sort_order: number
    created_by?: number
    updated_by?: number
    deleted_by?: number
    created_at: string
    updated_at: string
    deleted_at?: string
  }
  variants?: any[]
  inventory?: any[]
  stock_info?: {
    current_stock: number
    available_stock: number
    is_available: boolean
  }
}

export interface PosStats {
  today_orders: number
  today_sales: number
  active_orders: number
  pending_orders: number
  completed_orders_today: number
  payment_methods_today: Record<string, any>
  hourly_sales_today: any[]
}

export interface CreateOrderData {
  order_type: 'dine_in' | 'takeaway' | 'delivery'
  table_number?: string
  guest_count?: number
  id_customer?: number
  customer_info?: {
    name: string
    phone?: string
  }
  notes?: string
}

export interface AddItemData {
  id_product: number
  id_variant?: number
  quantity: number
  price?: number
  notes?: string
}

export interface UpdateItemData {
  quantity: number
  notes?: string
}

export interface ApplyDiscountData {
  discount_type: 'percentage' | 'fixed'
  discount_value: number
}

export interface ApplyTaxData {
  tax_type: 'percentage' | 'fixed'
  tax_value: number
}

export interface ProcessPaymentData {
  payment_method: 'cash' | 'card' | 'digital_wallet' | 'bank_transfer' | 'qris' | 'other'
  amount: number
  reference_number?: string
  notes?: string
}

export interface UpdateOrderStatusData {
  status: 'pending' | 'preparing' | 'ready' | 'completed' | 'cancelled'
}

export interface EditOrderData {
  order_type?: 'dine_in' | 'takeaway' | 'delivery'
  table_number?: string
  guest_count?: number
  id_customer?: number
  customer_info?: {
    name: string
    phone?: string
  }
  notes?: string
  discount_type?: 'percentage' | 'fixed'
  discount_value?: number
  items?: Array<{
    id_product: number
    quantity: number
    unit_price: number
    notes?: string
  }>
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

export class PosApi {
  // Get POS statistics
  static async getStats(): Promise<ApiResponse<PosStats>> {
    try {
      const response = await axios.get(`${API_BASE_URL}/stats`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get products for POS
  static async getProducts(params?: {
    search?: string
    category_id?: number
  }): Promise<ApiResponse<ProductForPos[]>> {
    try {
      const response = await axios.get(`${API_BASE_URL}/products`, { params })
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get active orders
  static async getActiveOrders(params?: {
    order_type?: string
    table_number?: string
    per_page?: number
    page?: number
  }): Promise<ApiResponse<any>> {
    try {
      const response = await axios.get(`${API_BASE_URL}/orders`, { params })
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Create new order
  static async createOrder(data: CreateOrderData): Promise<ApiResponse<Order>> {
    try {
      const response = await axios.post(`${API_BASE_URL}/orders`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Get order details
  static async getOrder(orderId: number): Promise<ApiResponse<Order>> {
    try {
      const response = await axios.get(`${API_BASE_URL}/orders/${orderId}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Update order status
  static async updateOrderStatus(orderId: number, data: UpdateOrderStatusData): Promise<ApiResponse<Order>> {
    try {
      const response = await axios.put(`${API_BASE_URL}/orders/${orderId}/status`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Edit/Update order
  static async editOrder(orderId: number, data: EditOrderData): Promise<ApiResponse<Order>> {
    try {
      const response = await axios.put(`${API_BASE_URL}/orders/${orderId}`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Cancel order
  static async cancelOrder(orderId: number): Promise<ApiResponse<Order>> {
    try {
      const response = await axios.post(`${API_BASE_URL}/orders/${orderId}/cancel`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Add item to order
  static async addItem(orderId: number, data: AddItemData): Promise<ApiResponse<OrderItem>> {
    try {
      const response = await axios.post(`${API_BASE_URL}/orders/${orderId}/items`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Update order item
  static async updateItem(orderId: number, itemId: number, data: UpdateItemData): Promise<ApiResponse<OrderItem>> {
    try {
      const response = await axios.put(`${API_BASE_URL}/orders/${orderId}/items/${itemId}`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Remove item from order
  static async removeItem(orderId: number, itemId: number): Promise<ApiResponse<void>> {
    try {
      const response = await axios.delete(`${API_BASE_URL}/orders/${orderId}/items/${itemId}`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Apply discount to order
  static async applyDiscount(orderId: number, data: ApplyDiscountData): Promise<ApiResponse<Order>> {
    try {
      const response = await axios.post(`${API_BASE_URL}/orders/${orderId}/discount`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Apply tax to order
  static async applyTax(orderId: number, data: ApplyTaxData): Promise<ApiResponse<Order>> {
    try {
      const response = await axios.post(`${API_BASE_URL}/orders/${orderId}/tax`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Process payment
  static async processPayment(orderId: number, data: ProcessPaymentData): Promise<ApiResponse<{
    payment: Payment
    order: Order
    change_amount: number
  }>> {
    try {
      const response = await axios.post(`${API_BASE_URL}/orders/${orderId}/payment`, data)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Format error response
  static formatError(error: any): string {
    if (error.errors) {
      return Object.values(error.errors).flat().join(', ')
    }
    return error.message || 'An unexpected error occurred'
  }

  // Get orders/transaction history
  static async getOrders(params?: {
    search?: string
    status?: string
    from_date?: string
    to_date?: string
    per_page?: number
    page?: number
  }): Promise<ApiResponse<Order[]>> {
    try {
      const response = await axios.get(`${API_BASE_URL}/orders/history`, { params })
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
