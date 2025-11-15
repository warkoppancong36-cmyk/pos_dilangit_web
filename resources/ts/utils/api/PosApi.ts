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
  payment_method: 'cash' | 'card' | 'digital_wallet' | 'bank_transfer' | 'qris' | 'gopay' | 'gojek' | 'grab' | 'shopee' | 'other'
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
  payment_bank?: string
  bank?: any
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
  payment_method: 'cash' | 'card' | 'digital_wallet' | 'bank_transfer' | 'qris' | 'gopay' | 'grabpay' | 'shopeepay' | 'other'
  amount: number
  reference_number?: string
  notes?: string
  bank_id?: number
  bank?: string
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
    category_id?: string
    payment_method?: string
    date_from?: string
    date_to?: string
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

  // Get categories for filter dropdown
  static async getCategories(): Promise<ApiResponse<any[]>> {
    try {
      const response = await axios.get(`${API_BASE_URL}/orders/categories`)
      return response.data
    } catch (error: any) {
      throw error.response?.data || this.handleError(error)
    }
  }

  // Export orders to Excel
  // Get all orders for export (not paginated)
  static async getAllOrdersForExport(params?: {
    search?: string
    status?: string
    category_id?: string
    payment_status?: string
    start_date?: string
    end_date?: string
  }) {
    try {
      const exportParams: any = {
        per_page: 'all', // Get all data - use string to match backend logic
        page: 1
      }

      // Map parameters to match backend API expectations
      if (params?.search) exportParams.search = params.search
      if (params?.status) exportParams.status = params.status
      if (params?.category_id) exportParams.category_id = params.category_id
      if (params?.payment_status) exportParams.payment_status = params.payment_status

      // Always send date parameters to prevent backend default filter
      // Map date parameters: start_date/end_date -> date_from/date_to
      exportParams.date_from = params?.start_date || ''
      exportParams.date_to = params?.end_date || ''

      console.log('🔍 Export API parameters sent to backend:', exportParams)
      console.log('🔍 Original params received:', params)

      const response = await axios.get(`${API_BASE_URL}/orders/history`, {
        params: exportParams
      });

      return response.data.data || []
    } catch (error: any) {
      console.error('Get all orders error:', error)
      throw error.response?.data || this.handleError(error)
    }
  }

  // Export orders to Excel using xlsx library (like inventory-management)
  static async exportOrdersToExcel(params?: {
    search?: string
    status?: string
    category_id?: string
    payment_status?: string
    start_date?: string
    end_date?: string
  }) {
    try {
      console.log('🚀 Starting POS export to Excel...')

      // Get all orders data
      const ordersData = await this.getAllOrdersForExport(params)
      console.log('📊 Exporting', ordersData.length, 'orders to Excel')

      // Dynamic import for better performance
      const XLSX = await import('xlsx')

      // Calculate summary statistics
      const totalOrders = ordersData.length
      const totalAmount = ordersData.reduce((sum: number, order: any) =>
        sum + (parseFloat(order.total_amount) || 0), 0
      )
      const paidOrders = ordersData.filter((order: any) =>
        order.payment_status === 'paid'
      ).length
      const unpaidOrders = ordersData.filter((order: any) =>
        order.payment_status === 'unpaid' || order.payment_status === 'pending'
      ).length

      // Prepare clean data for export
      const exportData: any[] = [];
      for (let index = 0; index < ordersData.length; index++) {
        const order: any = ordersData[index];
        const itemArray = (order.order_items || order.orderItems || []);
        const itemsList = (itemArray)
          .map((it: any) => `${it.quantity}x ${it.product?.name || it.product_name || ''}`)
          .join(', ');

        exportData.push({
          'No': index + 1,
          'ID Order': order.id,
          'Tanggal': order.created_at ? new Date(order.created_at).toLocaleDateString('id-ID') : '-',
          'Waktu': order.created_at ? new Date(order.created_at).toLocaleTimeString('id-ID') : '-',
          'Customer': order.customer?.name || 'Walk-in Customer',
          'Kategori': (itemArray?.[0]?.product?.category?.name) || '-',
          'List Barang': itemsList,
          'Total Amount': parseFloat(order.total_amount) || 0,
          'Status Pembayaran': order.payment_status === 'paid' ? 'Lunas' :
            order.payment_status === 'pending' ? 'Pending' : 'Belum Lunas',
          'Metode Pembayaran': order.payments?.[0]?.payment_method || '-',
          'Jumlah Item': (itemArray?.length) || 0,
          'Catatan': order.notes || '-'
        });
      }

      // Create workbook and worksheet
      const workbook = XLSX.utils.book_new()

      // Create title and summary data
      const filterInfo = []
      if (params?.search) {
        filterInfo.push(`Pencarian: "${params.search}"`)
      }
      if (params?.category_id) {
        filterInfo.push(`Kategori: ID ${params.category_id}`)
      }
      if (params?.payment_status && params.payment_status !== 'all') {
        const statusLabels = {
          paid: 'Lunas',
          unpaid: 'Belum Lunas',
          pending: 'Pending'
        }
        filterInfo.push(`Status Pembayaran: ${statusLabels[params.payment_status as keyof typeof statusLabels] || params.payment_status}`)
      }
      if (params?.start_date || params?.end_date) {
        const dateRange = []
        if (params.start_date) dateRange.push(`Dari: ${params.start_date}`)
        if (params.end_date) dateRange.push(`Sampai: ${params.end_date}`)
        filterInfo.push(`Periode: ${dateRange.join(' ')}`)
      }

      const titleData = [
        ['LAPORAN RIWAYAT TRANSAKSI POS'],
        ['Tanggal Export:', new Date().toLocaleDateString('id-ID', {
          weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        })],
        ['Total Transaksi:', totalOrders],
        ['Total Amount:', `Rp ${totalAmount.toLocaleString('id-ID')}`],
        ['Transaksi Lunas:', paidOrders],
        ['Transaksi Belum Lunas:', unpaidOrders],
        ['Filter Diterapkan:', filterInfo.length > 0 ? filterInfo.join(', ') : 'Semua Data'],
        [], // Empty row
      ]

      // Create worksheet starting with title
      const worksheet = XLSX.utils.aoa_to_sheet(titleData)

      // Add main data starting from row 9 (after title and summary)
      XLSX.utils.sheet_add_json(worksheet, exportData, { origin: 'A9' })

      // Set column widths
      const columnWidths = [
        { wch: 5 },   // No
        { wch: 10 },  // ID Order
        { wch: 12 },  // Tanggal
        { wch: 10 },  // Waktu
        { wch: 20 },  // Customer
        { wch: 15 },  // Kategori
        { wch: 30 },  // List Barang
        { wch: 15 },  // Total Amount
        { wch: 15 },  // Status Pembayaran
        { wch: 15 },  // Metode Pembayaran
        { wch: 10 },  // Jumlah Item
        { wch: 20 },  // Catatan
      ]
      worksheet['!cols'] = columnWidths

      // Add worksheet to workbook
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Transaction History')

      // Generate filename with timestamp
      const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')
      const filename = `pos-riwayat-transaksi-${timestamp}.xlsx`

      // Download file
      XLSX.writeFile(workbook, filename)

      console.log('✅ Excel file generated successfully:', filename)

      return {
        success: true,
        filename,
        totalRecords: totalOrders,
        totalAmount
      }

    } catch (error: any) {
      console.error('Export to Excel error:', error)
      throw error.response?.data || this.handleError(error)
    }
  }

  static async exportOrders(params?: {
    search?: string
    status?: string
    category_id?: string
    payment_method?: string
    date_from?: string
    date_to?: string
  }): Promise<void> {
    try {
      const response = await axios.get(`${API_BASE_URL}/orders/export`, {
        params,
        responseType: 'blob' // Important for file download
      })

      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url

      // Get filename from response headers or use default
      const contentDisposition = response.headers['content-disposition']
      let filename = 'riwayat-transaksi.xlsx'

      if (contentDisposition) {
        const fileNameMatch = contentDisposition.match(/filename="?(.+)"?/)
        if (fileNameMatch) {
          filename = fileNameMatch[1]
        }
      }

      link.setAttribute('download', filename)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

    } catch (error: any) {
      console.error('Export error:', error)
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
