import type { AxiosResponse } from 'axios'
import axios from 'axios'

export interface Discount {
  id_discount: number
  code: string
  name: string
  description?: string
  type: 'percentage' | 'fixed_amount' | 'buy_x_get_y'
  value: number
  minimum_amount?: number
  maximum_discount?: number
  usage_limit?: number
  usage_limit_per_customer?: number
  used_count: number
  valid_from: string
  valid_until: string
  active: boolean
  applicable_products?: number[]
  applicable_categories?: number[]
  customer_groups?: string[]
  conditions?: any
  created_by?: number
  updated_by?: number
  created_at: string
  updated_at: string
  
  // Computed attributes
  status?: string
  formatted_value?: string
  remaining_usage?: number | null
  
  // Relations
  createdBy?: {
    id: number
    name: string
  }
  updatedBy?: {
    id: number
    name: string
  }
}

export interface DiscountFilters {
  search?: string
  type?: string
  status?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export interface DiscountValidation {
  discount: Discount
  discount_amount: number
  formatted_discount: string
}

export interface DiscountStats {
  total_discounts: number
  active_discounts: number
  expired_discounts: number
  scheduled_discounts: number
  total_usage: number
  most_used_discount?: Discount
  discount_types: Array<{
    type: string
    count: number
  }>
}

export interface CreateDiscountRequest {
  code: string
  name: string
  description?: string
  type: 'percentage' | 'fixed_amount' | 'buy_x_get_y'
  value: number
  minimum_amount?: number
  maximum_discount?: number
  usage_limit?: number
  usage_limit_per_customer?: number
  valid_from: string
  valid_until: string
  active?: boolean
  applicable_products?: number[]
  applicable_categories?: number[]
  customer_groups?: string[]
  conditions?: any
}

export interface PaginatedDiscounts {
  data: Discount[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

class DiscountsApi {
  private static readonly BASE_URL = '/api/discounts'

  static async getDiscounts(filters: DiscountFilters = {}): Promise<PaginatedDiscounts> {
    const response: AxiosResponse<{
      success: boolean
      data: PaginatedDiscounts
      message: string
    }> = await axios.get(this.BASE_URL, { params: filters })
    
    return response.data.data
  }

  static async getDiscount(id: number): Promise<Discount> {
    const response: AxiosResponse<{
      success: boolean
      data: Discount
      message: string
    }> = await axios.get(`${this.BASE_URL}/${id}`)
    
    return response.data.data
  }

  static async createDiscount(data: CreateDiscountRequest): Promise<Discount> {
    const response: AxiosResponse<{
      success: boolean
      data: Discount
      message: string
    }> = await axios.post(this.BASE_URL, data)
    
    return response.data.data
  }

  static async updateDiscount(id: number, data: CreateDiscountRequest): Promise<Discount> {
    const response: AxiosResponse<{
      success: boolean
      data: Discount
      message: string
    }> = await axios.put(`${this.BASE_URL}/${id}`, data)
    
    return response.data.data
  }

  static async deleteDiscount(id: number): Promise<void> {
    await axios.delete(`${this.BASE_URL}/${id}`)
  }

  static async validateDiscountCode(code: string, orderTotal: number, customerId?: number): Promise<DiscountValidation> {
    const response: AxiosResponse<{
      success: boolean
      data: DiscountValidation
      message: string
    }> = await axios.post(`${this.BASE_URL}/validate-code`, {
      code,
      order_total: orderTotal,
      customer_id: customerId
    })
    
    return response.data.data
  }

  static async getStats(): Promise<DiscountStats> {
    const response: AxiosResponse<{
      success: boolean
      data: DiscountStats
      message: string
    }> = await axios.get(`${this.BASE_URL}/stats`)
    
    return response.data.data
  }

  static async toggleStatus(id: number): Promise<{ active: boolean }> {
    const response: AxiosResponse<{
      success: boolean
      data: { active: boolean }
      message: string
    }> = await axios.post(`${this.BASE_URL}/${id}/toggle-status`)
    
    return response.data.data
  }

  static async duplicate(id: number): Promise<Discount> {
    const response: AxiosResponse<{
      success: boolean
      data: Discount
      message: string
    }> = await axios.post(`${this.BASE_URL}/${id}/duplicate`)
    
    return response.data.data
  }

  // Utility functions
  static formatDiscountValue(discount: Discount): string {
    switch (discount.type) {
      case 'percentage':
        return `${discount.value}%`
      case 'fixed_amount':
        return `Rp ${this.formatCurrency(discount.value)}`
      default:
        return discount.value.toString()
    }
  }

  static formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount)
  }

  static getDiscountTypeLabel(type: string): string {
    const types = {
      percentage: 'Persentase',
      fixed_amount: 'Jumlah Tetap',
      buy_x_get_y: 'Beli X Dapat Y'
    }
    return types[type as keyof typeof types] || type
  }

  static getStatusColor(status: string): string {
    const colors = {
      active: 'success',
      inactive: 'secondary',
      expired: 'error',
      scheduled: 'warning',
      exhausted: 'info'
    }
    return colors[status as keyof typeof colors] || 'primary'
  }

  static getStatusLabel(status: string): string {
    const labels = {
      active: 'Aktif',
      inactive: 'Tidak Aktif',
      expired: 'Kadaluarsa',
      scheduled: 'Terjadwal',
      exhausted: 'Habis'
    }
    return labels[status as keyof typeof labels] || status
  }

  static isDiscountValid(discount: Discount): boolean {
    if (!discount.active) return false
    
    const now = new Date()
    const validFrom = new Date(discount.valid_from)
    const validUntil = new Date(discount.valid_until)
    
    return now >= validFrom && now <= validUntil
  }

  static canDiscountBeUsed(discount: Discount, orderTotal: number): boolean {
    if (!this.isDiscountValid(discount)) return false
    
    if (discount.minimum_amount && orderTotal < discount.minimum_amount) return false
    
    if (discount.usage_limit && discount.used_count >= discount.usage_limit) return false
    
    return true
  }

  static calculateDiscountAmount(discount: Discount, orderTotal: number): number {
    if (!this.canDiscountBeUsed(discount, orderTotal)) return 0
    
    let discountAmount = 0
    
    switch (discount.type) {
      case 'percentage':
        discountAmount = (orderTotal * discount.value) / 100
        break
      case 'fixed_amount':
        discountAmount = discount.value
        break
      case 'buy_x_get_y':
        // Complex logic would be implemented here
        discountAmount = 0
        break
    }
    
    // Apply maximum discount limit
    if (discount.maximum_discount && discountAmount > discount.maximum_discount) {
      discountAmount = discount.maximum_discount
    }
    
    return Math.round(discountAmount * 100) / 100
  }
}

export default DiscountsApi
