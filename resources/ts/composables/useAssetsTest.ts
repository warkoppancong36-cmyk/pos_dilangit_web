import { ref, computed } from 'vue'
import { getAuthToken } from '@/utils/auth'

export interface Asset {
  id: number
  asset_code: string
  name: string
  category: string
  brand?: string
  model?: string
  serial_number?: string
  purchase_date?: string
  purchase_price?: number
  location: string
  condition: 'excellent' | 'good' | 'fair' | 'poor' | 'damaged'
  status: 'active' | 'inactive' | 'maintenance' | 'disposed'
  description?: string
  supplier?: string
  warranty_until?: string
  assigned_to?: string
  department?: string
  image_url?: string
  created_at: string
  updated_at: string
}

export interface AssetFilters {
  search?: string
  category?: string
  location?: string
  condition?: string
  status?: string
  department?: string
  assigned_to?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

const state = ref({
  assets: [] as Asset[],
  asset: null as Asset | null,
  loading: false,
  error: null as string | null,
  pagination: {
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0
  },
  filters: {
    search: '',
    category: '',
    location: '',
    condition: '',
    status: '',
    department: '',
    assigned_to: '',
    sort_by: 'asset_code',
    sort_order: 'asc'
  } as AssetFilters
})

export const useAssetsTest = () => {
  // Getters
  const assets = computed(() => state.value.assets)
  const asset = computed(() => state.value.asset)
  const loading = computed(() => state.value.loading)
  const error = computed(() => state.value.error)
  const pagination = computed(() => state.value.pagination)
  const filters = computed(() => state.value.filters)

  const hasAssets = computed(() => {
    return Array.isArray(assets.value) && assets.value.length > 0
  })
  const isEmpty = computed(() => {
    return !loading.value && !hasAssets.value
  })
  const activeAssets = computed(() => {
    if (!Array.isArray(assets.value)) return []
    return assets.value.filter(asset => asset.status === 'active')
  })
  const maintenanceAssets = computed(() => {
    if (!Array.isArray(assets.value)) return []
    return assets.value.filter(asset => asset.status === 'maintenance')
  })

  // Actions
  const fetchAssets = async (page = 1) => {
    state.value.loading = true
    state.value.error = null

    try {
      // Get authentication token
      const token = getAuthToken()
      const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
      
      // Add authorization header if token exists
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }
      
      const response = await fetch('http://localhost:8000/api/test-assets', {
        method: 'GET',
        headers
      })

      if (!response.ok) {
        const errorText = await response.text()
        throw new Error(`HTTP error! status: ${response.status} - ${errorText}`)
      }

      const data = await response.json()
      
      // Handle Laravel response format with pagination
      if (data.success && data.data) {
        // Check if data.data has pagination structure (has 'data' property)
        if (data.data.data && Array.isArray(data.data.data)) {
          state.value.assets = data.data.data
          
          // Update pagination info
          state.value.pagination = {
            current_page: data.data.current_page || 1,
            last_page: data.data.last_page || 1,
            per_page: data.data.per_page || 20,
            total: data.data.total || 0,
            from: data.data.from || 0,
            to: data.data.to || 0
          }
        } else if (Array.isArray(data.data)) {
          // Direct array in data.data
          state.value.assets = data.data
        } else {
          state.value.assets = []
        }
      } else if (Array.isArray(data)) {
        state.value.assets = data
      } else {
        state.value.assets = []
      }
      
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch assets'
      state.value.error = errorMessage
      
      // Fallback: Load sample data if API fails
      const sampleData = getSampleAssets()
      state.value.assets = sampleData
      state.value.error = `API Error: ${errorMessage}. Showing sample data instead.`
      
    } finally {
      state.value.loading = false
    }
  }

  // Sample data fallback
  const getSampleAssets = (): Asset[] => {
    return [
      {
        id: 1,
        asset_code: 'KIT-001',
        name: 'Commercial Oven',
        category: 'Kitchen Equipment',
        brand: 'Rational',
        model: 'SelfCookingCenter',
        serial_number: 'RAT123456',
        purchase_date: '2024-01-15',
        purchase_price: 15999.99,
        location: 'Main Kitchen',
        condition: 'excellent',
        status: 'active',
        description: 'Commercial combi oven for restaurant kitchen',
        supplier: 'Restaurant Supply Co',
        warranty_until: '2027-01-15',
        assigned_to: 'Head Chef',
        department: 'Kitchen',
        image_url: undefined,
        created_at: '2024-01-15T10:00:00Z',
        updated_at: '2024-01-15T10:00:00Z'
      },
      {
        id: 2,
        asset_code: 'KIT-002',
        name: 'Deep Fryer',
        category: 'Kitchen Equipment',
        brand: 'Henny Penny',
        model: 'PFE-500',
        serial_number: 'HP789012',
        purchase_date: '2024-02-01',
        purchase_price: 8500.00,
        location: 'Frying Station',
        condition: 'good',
        status: 'active',
        description: 'Professional deep fryer for restaurant use',
        supplier: 'Kitchen Pro Indonesia',
        warranty_until: '2026-02-01',
        assigned_to: 'Fry Cook',
        department: 'Kitchen',
        image_url: undefined,
        created_at: '2024-02-01T09:30:00Z',
        updated_at: '2024-02-01T09:30:00Z'
      },
      {
        id: 3,
        asset_code: 'POS-001',
        name: 'POS Terminal',
        category: 'Technology',
        brand: 'Square',
        model: 'Terminal',
        serial_number: 'SQ345678',
        purchase_date: '2024-01-20',
        purchase_price: 1200.00,
        location: 'Front Counter',
        condition: 'excellent',
        status: 'active',
        description: 'Point of sale terminal for order processing',
        supplier: 'Tech Solutions',
        warranty_until: '2026-01-20',
        assigned_to: 'Cashier',
        department: 'Front of House',
        image_url: undefined,
        created_at: '2024-01-20T11:00:00Z',
        updated_at: '2024-01-20T11:00:00Z'
      },
      {
        id: 4,
        asset_code: 'FUR-001',
        name: 'Dining Table Set',
        category: 'Furniture',
        brand: 'Restaurant Furniture Co',
        model: 'Classic 4-Top',
        serial_number: 'RFC901234',
        purchase_date: '2023-12-15',
        purchase_price: 450.00,
        location: 'Dining Area',
        condition: 'good',
        status: 'active',
        description: 'Wooden dining table with 4 chairs',
        supplier: 'Furniture Direct',
        warranty_until: '2025-12-15',
        assigned_to: 'Floor Manager',
        department: 'Front of House',
        image_url: undefined,
        created_at: '2023-12-15T14:00:00Z',
        updated_at: '2023-12-15T14:00:00Z'
      },
      {
        id: 5,
        asset_code: 'KIT-003',
        name: 'Refrigerator',
        category: 'Kitchen Equipment',
        brand: 'True',
        model: 'T-49-GC',
        serial_number: 'TRUE567890',
        purchase_date: '2024-01-10',
        purchase_price: 3200.00,
        location: 'Cold Storage',
        condition: 'excellent',
        status: 'active',
        description: 'Commercial refrigerator for food storage',
        supplier: 'Cold Chain Solutions',
        warranty_until: '2027-01-10',
        assigned_to: 'Kitchen Manager',
        department: 'Kitchen',
        image_url: undefined,
        created_at: '2024-01-10T08:00:00Z',
        updated_at: '2024-01-10T08:00:00Z'
      }
    ]
  }

  // Simplified methods for testing
  const searchAssets = async (query: string) => {
    state.value.filters.search = query
    await fetchAssets()
  }

  const filterAssets = async (newFilters: AssetFilters) => {
    Object.assign(state.value.filters, newFilters)
    await fetchAssets()
  }

  const resetFilters = () => {
    state.value.filters = {
      search: '',
      category: '',
      location: '',
      condition: '',
      status: '',
      department: '',
      assigned_to: '',
      sort_by: 'asset_code',
      sort_order: 'asc'
    }
  }

  const deleteAsset = async (id: number) => {
    console.log('Delete asset:', id)
    // Mock delete for now
    state.value.assets = state.value.assets.filter(asset => asset.id !== id)
  }

  const bulkDeleteAssets = async (ids: number[]) => {
    console.log('Bulk delete assets:', ids)
    // Mock bulk delete for now
    state.value.assets = state.value.assets.filter(asset => !ids.includes(asset.id))
  }

  const getCategories = async () => {
    const categories = [...new Set(state.value.assets.map(asset => asset.category))]
    return categories
  }

  const getLocations = async () => {
    const locations = [...new Set(state.value.assets.map(asset => asset.location))]
    return locations
  }

  return {
    // State
    assets,
    asset,
    loading,
    error,
    pagination,
    filters,
    
    // Computed
    hasAssets,
    isEmpty,
    activeAssets,
    maintenanceAssets,
    
    // Actions
    fetchAssets,
    searchAssets,
    filterAssets,
    resetFilters,
    deleteAsset,
    bulkDeleteAssets,
    getCategories,
    getLocations
  }
}
