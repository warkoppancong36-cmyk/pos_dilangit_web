import axios from 'axios'
import { computed, ref } from 'vue'

// Types
export interface HPPBreakdownItem {
  item_name: string
  item_code: string
  quantity_needed: number
  unit: string
  cost_per_unit: number
  total_cost: number
  is_critical: boolean
  notes?: string
}

export interface HPPBreakdown {
  items: HPPBreakdownItem[]
  total_hpp: number
  method: 'current' | 'latest' | 'average'
  calculated_at: string
}

export interface HPPComparison {
  product_id: number
  product_name: string
  current_cost: number
  current_price: number
  hpp_methods: {
    current: number
    latest_purchase: number
    average_purchase: number
  }
  breakdown: {
    current: HPPBreakdown
    latest_purchase: HPPBreakdown
    average_purchase: HPPBreakdown
  }
  calculated_at: string
}

export interface HPPSuggestion {
  product_id: number
  product_name: string
  current_price: number
  markup_percentage: number
  suggestions: {
    current: {
      hpp: number
      markup_percentage: number
      suggested_price: number
      profit_margin: number
    }
    latest: {
      hpp: number
      markup_percentage: number
      suggested_price: number
      profit_margin: number
    }
    average: {
      hpp: number
      markup_percentage: number
      suggested_price: number
      profit_margin: number
    }
  }
  calculated_at: string
}

export interface HPPDashboard {
  total_products: number
  products_with_items: number
  products_without_items: number
  hpp_statistics: {
    average_hpp: number
    min_hpp: number
    max_hpp: number
    average_margin: number
  }
}

export interface HPPUpdateResult {
  product_id: number
  product_name: string
  old_cost: number
  new_cost: number
  difference: number
  method: string
  updated_at: string
}

export const useHPP = () => {
  // State
  const loading = ref(false)
  const currentHPPBreakdown = ref<HPPBreakdown | null>(null)
  const currentHPPComparison = ref<HPPComparison | null>(null)
  const currentHPPSuggestion = ref<HPPSuggestion | null>(null)
  const hppDashboard = ref<HPPDashboard | null>(null)
  const bulkUpdateResults = ref<HPPUpdateResult[]>([])

  const getHPPDashboard = async () => {
    loading.value = true
    try {
      const response = await axios.get('/api/hpp/dashboard')
      const data = response.data

      if (data.success) {
        hppDashboard.value = data.data

        return data.data
      }
      else {
        throw new Error(data.message || 'Failed to get HPP dashboard')
      }
    }
    catch (error: any) {
      console.error('Error getting HPP dashboard:', error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const getProductHPPBreakdown = async (productId: number, method: 'current' | 'latest' | 'average' = 'current') => {
    loading.value = true
    try {
      console.log('🔍 HPP Breakdown - Product ID:', productId, 'Method:', method)
      console.log('🔍 Access Token:', useCookie('accessToken').value ? 'EXISTS' : 'NOT FOUND')

      const response = await axios.get(`/api/hpp/products/${productId}/breakdown?method=${method}`)
      const data = response.data

      if (data.success) {
        currentHPPBreakdown.value = data.data.hpp_breakdown

        return data.data
      }
      else {
        throw new Error(data.message || 'Failed to get HPP breakdown')
      }
    }
    catch (error: any) {
      console.error('❌ Error getting HPP breakdown:', error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const updateProductHPP = async (productId: number, method: 'current' | 'latest' | 'average') => {
    loading.value = true
    try {
      console.log('🔄 Updating HPP for product:', productId, 'with method:', method)

      // Get token from localStorage like other APIs
      const token = localStorage.getItem('token')

      const response = await axios.post(`/api/hpp/products/${productId}/update`, {
        method,
      }, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      })

      const data = response.data

      if (data.success) {
        console.log(`✅ HPP updated successfully for ${data.data.product_name}`)

        return data.data
      }
      else {
        throw new Error(data.message || 'Failed to update HPP')
      }
    }
    catch (error: any) {
      console.error('❌ Error updating HPP:', error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const compareHPPMethods = async (productId: number) => {
    loading.value = true
    try {
      const response = await axios.get(`/api/hpp/products/${productId}/compare-methods`)
      const data = response.data

      if (data.success) {
        currentHPPComparison.value = data.data

        return data.data
      }
      else {
        throw new Error(data.message || 'Failed to compare HPP methods')
      }
    }
    catch (error: any) {
      console.error('Error comparing HPP methods:', error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const calculateSuggestedPrice = async (productId: number, markupPercentage: number) => {
    loading.value = true
    try {
      console.log('🔄 Calculating suggested price for product:', productId, 'with markup:', markupPercentage)

      // Get token from localStorage like other APIs
      const token = localStorage.getItem('token')

      const response = await axios.post(`/api/hpp/products/${productId}/suggested-price`, {
        markup_percentage: markupPercentage,
      }, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      })

      const data = response.data

      if (data.success) {
        currentHPPSuggestion.value = data.data
        console.log('✅ Suggested price calculated:', data.data)

        return data.data
      }
      else {
        throw new Error(data.message || 'Failed to calculate suggested price')
      }
    }
    catch (error: any) {
      console.error('❌ Error calculating suggested price:', error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const updatePriceFromHPP = async (
    productId: number,
    method: 'current' | 'latest' | 'average',
    markupPercentageOrTargetPrice: number,
    updateCost: boolean = true,
    useTargetPrice: boolean = false,
  ) => {
    loading.value = true
    try {
      console.log('🔄 Updating price from HPP for product:', productId)

      // Get token from localStorage like other APIs
      const token = localStorage.getItem('token')

      const payload: any = {
        method,
        update_cost: updateCost,
      }

      // Add either markup_percentage or target_price based on mode
      if (useTargetPrice) {
        payload.target_price = markupPercentageOrTargetPrice
        console.log('📊 Using target price mode:', markupPercentageOrTargetPrice)
      } else {
        payload.markup_percentage = markupPercentageOrTargetPrice
        console.log('📊 Using markup percentage mode:', markupPercentageOrTargetPrice)
      }

      const response = await axios.post(`/api/hpp/products/${productId}/update-price`, payload, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      })

      const data = response.data

      if (data.success) {
        console.log(`✅ Price updated successfully for ${data.data.product_name}`)

        return data // Return full response object instead of just data.data
      }
      else {
        throw new Error(data.message || 'Failed to update price from HPP')
      }
    }
    catch (error: any) {
      console.error('❌ Error updating price from HPP:', error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const bulkUpdateHPP = async (method: 'current' | 'latest' | 'average') => {
    loading.value = true
    try {
      const data = await $api('/hpp/bulk-update', {
        method: 'POST',
        body: { method },
      })

      if (data.success) {
        bulkUpdateResults.value = data.data.details
        console.log(`Bulk HPP update completed for ${data.data.updated_products} products`)

        return data.data
      }
      else {
        throw new Error(data.message || 'Failed to bulk update HPP')
      }
    }
    catch (error: any) {
      console.error('Error bulk updating HPP:', error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  // Computed properties
  const hasHPPData = computed(() => currentHPPBreakdown.value !== null)
  const hasComparisonData = computed(() => currentHPPComparison.value !== null)
  const hasSuggestionData = computed(() => currentHPPSuggestion.value !== null)
  const hasDashboardData = computed(() => hppDashboard.value !== null)

  // Format currency
  const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount)
  }

  // Calculate profit margin percentage
  const calculateProfitPercentage = (price: number, cost: number): number => {
    if (cost === 0)
      return 0

    return ((price - cost) / cost) * 100
  }

  // Reset states
  const resetStates = () => {
    currentHPPBreakdown.value = null
    currentHPPComparison.value = null
    currentHPPSuggestion.value = null
    bulkUpdateResults.value = []
  }

  return {
    // State
    loading,
    currentHPPBreakdown,
    currentHPPComparison,
    currentHPPSuggestion,
    hppDashboard,
    bulkUpdateResults,

    // API methods
    getHPPDashboard,
    getProductHPPBreakdown,
    updateProductHPP,
    compareHPPMethods,
    calculateSuggestedPrice,
    updatePriceFromHPP,
    bulkUpdateHPP,

    // Computed
    hasHPPData,
    hasComparisonData,
    hasSuggestionData,
    hasDashboardData,

    // Utility methods
    formatCurrency,
    calculateProfitPercentage,
    resetStates,
  }
}
