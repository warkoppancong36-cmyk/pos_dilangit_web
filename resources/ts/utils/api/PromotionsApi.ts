import type { AxiosResponse } from 'axios'
import axios from 'axios'

export interface Promotion {
  id_promotion: number
  name: string
  description?: string
  type: 'happy_hour' | 'buy_one_get_one' | 'combo_deal' | 'category_discount' | 'quantity_discount'
  promotion_rules?: any[]
  discount_value: number
  discount_type: 'percentage' | 'fixed_amount'
  max_discount_amount?: number
  minimum_amount?: number
  priority: number
  valid_from?: string
  valid_until?: string
  valid_days?: string[]
  
  // Type-specific fields
  start_time?: string
  end_time?: string
  buy_quantity?: number
  get_quantity?: number
  combo_quantity?: number
  combo_price?: number
  min_quantity?: number
  
  active: boolean
  applicable_products?: number[]
  applicable_categories?: number[]
  conditions?: any
  banner_image?: string
  created_by?: number
  updated_by?: number
  created_at: string
  updated_at: string
  
  // Computed attributes
  status?: string
  formatted_discount?: string
  valid_days_text?: string
  valid_time_text?: string
  
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

export interface PromotionFilters {
  search?: string
  type?: string
  status?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export interface PromotionCalculation {
  promotion: Promotion
  discount_amount: number
  description: string
  applied_items: number[]
  formatted_discount: string
}

export interface PromotionStats {
  total_promotions: number
  active_promotions: number
  expired_promotions: number
  scheduled_promotions: number
  promotion_types: Array<{
    type: string
    count: number
  }>
  highest_priority: number
  current_active: number
}

export interface CreatePromotionRequest {
  name: string
  description?: string
  type: 'happy_hour' | 'buy_one_get_one' | 'combo_deal' | 'category_discount' | 'quantity_discount'
  promotion_rules: any[]
  discount_value: number
  discount_type: 'percentage' | 'fixed_amount'
  max_discount_amount?: number | null
  minimum_amount?: number | null
  priority: number
  valid_from?: string | null
  valid_until?: string | null
  valid_days?: string[]
  
  // Type-specific fields
  start_time?: string | null
  end_time?: string | null
  buy_quantity?: number | null
  get_quantity?: number | null
  combo_quantity?: number | null
  combo_price?: number | null
  min_quantity?: number | null
  
  active?: boolean
  applicable_products?: number[]
  applicable_categories?: number[]
  conditions?: any
  banner_image?: string
}

export interface UpdatePromotionRequest extends CreatePromotionRequest {
  // Same structure as CreatePromotionRequest
}

export interface PaginatedPromotions {
  data: Promotion[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

class PromotionsApi {
  private static readonly BASE_URL = '/api/promotions'

  static async getPromotions(filters: PromotionFilters = {}): Promise<PaginatedPromotions> {
    const response: AxiosResponse<{
      success: boolean
      data: PaginatedPromotions
      message: string
    }> = await axios.get(this.BASE_URL, { params: filters })
    
    return response.data.data
  }

  static async getPromotion(id: number): Promise<Promotion> {
    const response: AxiosResponse<{
      success: boolean
      data: Promotion
      message: string
    }> = await axios.get(`${this.BASE_URL}/${id}`)
    
    return response.data.data
  }

  static async createPromotion(data: CreatePromotionRequest): Promise<Promotion> {
    const response: AxiosResponse<{
      success: boolean
      data: Promotion
      message: string
    }> = await axios.post(this.BASE_URL, data)
    
    return response.data.data
  }

  static async updatePromotion(id: number, data: CreatePromotionRequest): Promise<Promotion> {
    const response: AxiosResponse<{
      success: boolean
      data: Promotion
      message: string
    }> = await axios.put(`${this.BASE_URL}/${id}`, data)
    
    return response.data.data
  }

  static async deletePromotion(id: number): Promise<void> {
    await axios.delete(`${this.BASE_URL}/${id}`)
  }

  static async getActivePromotions(): Promise<Promotion[]> {
    const response: AxiosResponse<{
      success: boolean
      data: Promotion[]
      message: string
    }> = await axios.get(`${this.BASE_URL}/active`)
    
    return response.data.data
  }

  static async calculatePromotions(cartItems: any[]): Promise<PromotionCalculation[]> {
    const response: AxiosResponse<{
      success: boolean
      data: PromotionCalculation[]
      message: string
    }> = await axios.post(`${this.BASE_URL}/calculate`, {
      cart_items: cartItems
    })
    
    return response.data.data
  }

  static async getStats(): Promise<PromotionStats> {
    const response: AxiosResponse<{
      success: boolean
      data: PromotionStats
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

  static async duplicate(id: number): Promise<Promotion> {
    const response: AxiosResponse<{
      success: boolean
      data: Promotion
      message: string
    }> = await axios.post(`${this.BASE_URL}/${id}/duplicate`)
    
    return response.data.data
  }

  // Utility functions
  static getPromotionTypeLabel(type: string): string {
    const types = {
      happy_hour: 'Happy Hour',
      buy_one_get_one: 'Buy 1 Get 1',
      combo_deal: 'Combo Deal',
      member_discount: 'Member Discount',
      seasonal: 'Seasonal'
    }
    return types[type as keyof typeof types] || type
  }

  static getPromotionTypeIcon(type: string): string {
    const icons = {
      happy_hour: 'mdi-clock-outline',
      buy_one_get_one: 'mdi-numeric-1-box-multiple',
      combo_deal: 'mdi-food-variant',
      member_discount: 'mdi-account-star',
      seasonal: 'mdi-weather-sunny'
    }
    return icons[type as keyof typeof icons] || 'mdi-tag'
  }

  static getPromotionTypeColor(type: string): string {
    const colors = {
      happy_hour: 'warning',
      buy_one_get_one: 'success',
      combo_deal: 'info',
      member_discount: 'secondary',
      seasonal: 'primary'
    }
    return colors[type as keyof typeof colors] || 'primary'
  }

  static getStatusColor(status: string): string {
    const colors = {
      active: 'success',
      inactive: 'secondary',
      expired: 'error',
      scheduled: 'warning',
      not_applicable: 'info'
    }
    return colors[status as keyof typeof colors] || 'primary'
  }

  static getStatusLabel(status: string): string {
    const labels = {
      active: 'Aktif',
      inactive: 'Tidak Aktif',
      expired: 'Kadaluarsa',
      scheduled: 'Terjadwal',
      not_applicable: 'Tidak Berlaku'
    }
    return labels[status as keyof typeof labels] || status
  }

  static formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount)
  }

  static formatDiscountValue(promotion: Promotion): string {
    if (!promotion.discount_value) return 'Special Offer'
    
    switch (promotion.discount_type) {
      case 'percentage':
        return `${promotion.discount_value}% OFF`
      case 'fixed_amount':
        return `Rp ${this.formatCurrency(promotion.discount_value)} OFF`
      default:
        return 'Special Offer'
    }
  }

  static getDayLabel(day: string): string {
    const days = {
      monday: 'Senin',
      tuesday: 'Selasa',
      wednesday: 'Rabu',
      thursday: 'Kamis',
      friday: 'Jumat',
      saturday: 'Sabtu',
      sunday: 'Minggu'
    }
    return days[day as keyof typeof days] || day
  }

  static formatValidDays(days: string[]): string {
    if (!days || days.length === 0) return 'Setiap hari'
    
    const dayLabels = days.map(day => this.getDayLabel(day))
    
    if (dayLabels.length === 7) return 'Setiap hari'
    if (dayLabels.length === 1) return dayLabels[0]
    if (dayLabels.length === 2) return dayLabels.join(' & ')
    
    return dayLabels.slice(0, -1).join(', ') + ' & ' + dayLabels.slice(-1)[0]
  }

  static formatTimeRange(timeFrom?: string, timeUntil?: string): string {
    if (!timeFrom || !timeUntil) return 'Sepanjang hari'
    
    return `${timeFrom} - ${timeUntil}`
  }

  static formatPromotionDiscount(promotion: Promotion): string {
    if (promotion.discount_type === 'percentage') {
      return `${promotion.discount_value}%`
    } else {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(promotion.discount_value)
    }
  }

  static isPromotionValid(promotion: Promotion): boolean {
    if (!promotion.active) return false
    
    const now = new Date()
    
    // Check date validity if dates are provided
    if (promotion.valid_from && promotion.valid_until) {
      const validFrom = new Date(promotion.valid_from)
      const validUntil = new Date(promotion.valid_until)
      
      if (now < validFrom || now > validUntil) return false
    }
    
    // Check day validity
    if (promotion.valid_days && promotion.valid_days.length > 0) {
      const currentDay = now.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase()
      if (!promotion.valid_days.includes(currentDay)) return false
    }
    
    // Check time validity for happy hour
    if (promotion.type === 'happy_hour' && promotion.start_time && promotion.end_time) {
      const currentTime = now.toTimeString().slice(0, 5) // HH:MM format
      if (currentTime < promotion.start_time || currentTime > promotion.end_time) {
        return false
      }
    }
    
    return true
  }

  static createPromotionRules(type: string, data: any): any {
    switch (type) {
      case 'happy_hour':
        return {
          discount_percentage: data.discount_percentage || 0,
          applicable_hours: data.applicable_hours || []
        }
      
      case 'buy_one_get_one':
        return {
          buy_quantity: data.buy_quantity || 1,
          get_quantity: data.get_quantity || 1,
          max_free_items: data.max_free_items
        }
      
      case 'combo_deal':
        return {
          required_items: data.required_items || [],
          combo_price: data.combo_price || 0
        }
      
      case 'member_discount':
        return {
          member_tiers: data.member_tiers || [],
          discount_percentage: data.discount_percentage || 0
        }
      
      case 'seasonal':
        return {
          season: data.season || '',
          discount_percentage: data.discount_percentage || 0
        }
      
      default:
        return data
    }
  }

  static getPriorityLabel(priority: number): string {
    if (priority >= 90) return 'Sangat Tinggi'
    if (priority >= 70) return 'Tinggi'
    if (priority >= 50) return 'Sedang'
    if (priority >= 30) return 'Rendah'
    return 'Sangat Rendah'
  }

  static getPriorityColor(priority: number): string {
    if (priority >= 90) return 'error'
    if (priority >= 70) return 'warning'
    if (priority >= 50) return 'info'
    if (priority >= 30) return 'success'
    return 'secondary'
  }
}

export default PromotionsApi
