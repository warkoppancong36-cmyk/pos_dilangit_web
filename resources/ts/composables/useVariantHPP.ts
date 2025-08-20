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
  variant_id: number
  variant_name: string
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
  variant_id: number
  variant_name: string
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

export interface HPPUpdateResult {
  variant_id: number
  variant_name: string
  old_cost: number
  new_cost: number
  difference: number
  method: string
  updated_at: string
}

export const useVariantHPP = () => {
  // State
  const loading = ref(false)
  const currentHPPBreakdown = ref<HPPBreakdown | null>(null)
  const currentHPPComparison = ref<HPPComparison | null>(null)
  const currentHPPSuggestion = ref<HPPSuggestion | null>(null)
  const bulkUpdateResults = ref<HPPUpdateResult[]>([])

  // Computed
  const hasHPPData = computed(() => {
    return currentHPPBreakdown.value && 
           currentHPPBreakdown.value.items && 
           currentHPPBreakdown.value.items.length > 0
  })

  const hasSuggestionData = computed(() => {
    return currentHPPSuggestion.value && 
           currentHPPSuggestion.value.suggestions
  })

  const formatCurrency = (amount: number | string): string => {
    const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(numAmount || 0)
  }

  // Helper function to get auth headers
  const getAuthHeaders = () => {
    const token = localStorage.getItem('token')
    return {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
    }
  }

  // Get variant HPP breakdown
  const getVariantHPPBreakdown = async (variantId: number, method: 'current' | 'latest' | 'average' = 'latest') => {
    loading.value = true
    try {
      const response = await axios.get(`/api/variants/${variantId}/hpp-breakdown`, {
        params: { method },
        headers: getAuthHeaders(),
      })

      if (response.data.success) {
        currentHPPBreakdown.value = response.data.data
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Failed to get HPP breakdown')
      }
    } catch (error: any) {
      console.error('Error getting variant HPP breakdown:', error)
      currentHPPBreakdown.value = null
      throw error
    } finally {
      loading.value = false
    }
  }

  // Update variant HPP
  const updateVariantHPP = async (variantId: number, method: 'current' | 'latest' | 'average' = 'latest') => {
    loading.value = true
    try {
      const response = await axios.post(`/api/variants/${variantId}/hpp-update`, {
        method,
      }, {
        headers: getAuthHeaders(),
      })

      if (response.data.success) {
        // Refresh the breakdown after update
        await getVariantHPPBreakdown(variantId, method)
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Failed to update HPP')
      }
    } catch (error: any) {
      console.error('Error updating variant HPP:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  // Calculate suggested price
  const calculateSuggestedPrice = async (variantId: number, markupPercentage: number) => {
    loading.value = true
    try {
      const response = await axios.post(`/api/variants/${variantId}/hpp-suggestion`, {
        markup_percentage: markupPercentage,
      }, {
        headers: getAuthHeaders(),
      })

      if (response.data.success) {
        currentHPPSuggestion.value = response.data.data
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Failed to calculate price suggestion')
      }
    } catch (error: any) {
      console.error('Error calculating suggested price:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  // Update price from HPP
  const updatePriceFromHPP = async (
    variantId: number,
    method: 'current' | 'latest' | 'average',
    targetPrice: number,
    updateStock: boolean = false,
    useTargetPrice: boolean = true
  ) => {
    loading.value = true
    try {
      const response = await axios.post(`/api/variants/${variantId}/hpp-price-update`, {
        method,
        target_price: targetPrice,
        update_stock: updateStock,
        use_target_price: useTargetPrice,
      }, {
        headers: getAuthHeaders(),
      })

      if (response.data.success) {
        return response.data
      } else {
        throw new Error(response.data.message || 'Failed to update price from HPP')
      }
    } catch (error: any) {
      console.error('Error updating price from HPP:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  // Get HPP comparison
  const getVariantHPPComparison = async (variantId: number) => {
    loading.value = true
    try {
      const response = await axios.get(`/api/variants/${variantId}/hpp-comparison`, {
        headers: getAuthHeaders(),
      })

      if (response.data.success) {
        currentHPPComparison.value = response.data.data
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Failed to get HPP comparison')
      }
    } catch (error: any) {
      console.error('Error getting variant HPP comparison:', error)
      currentHPPComparison.value = null
      throw error
    } finally {
      loading.value = false
    }
  }

  // Bulk update variant HPP
  const bulkUpdateVariantHPP = async (variantIds: number[], method: 'current' | 'latest' | 'average') => {
    loading.value = true
    try {
      const response = await axios.post('/api/variants/bulk-hpp-update', {
        variant_ids: variantIds,
        method,
      }, {
        headers: getAuthHeaders(),
      })

      if (response.data.success) {
        bulkUpdateResults.value = response.data.data.results || []
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Failed to bulk update HPP')
      }
    } catch (error: any) {
      console.error('Error bulk updating variant HPP:', error)
      bulkUpdateResults.value = []
      throw error
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    loading,
    currentHPPBreakdown,
    currentHPPComparison,
    currentHPPSuggestion,
    bulkUpdateResults,

    // Computed
    hasHPPData,
    hasSuggestionData,

    // Methods
    getVariantHPPBreakdown,
    updateVariantHPP,
    calculateSuggestedPrice,
    updatePriceFromHPP,
    getVariantHPPComparison,
    bulkUpdateVariantHPP,
    formatCurrency,
  }
}
