import { ref, computed } from 'vue'
import { $api } from '@/utils/api'

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

export interface CreateAssetData {
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
  image?: File
}

export interface UpdateAssetData {
  name?: string
  category?: string
  brand?: string
  model?: string
  serial_number?: string
  purchase_date?: string
  purchase_price?: number
  location?: string
  condition?: 'excellent' | 'good' | 'fair' | 'poor' | 'damaged'
  status?: 'active' | 'inactive' | 'maintenance' | 'disposed'
  description?: string
  supplier?: string
  warranty_until?: string
  assigned_to?: string
  department?: string
  image?: File
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

export const useAssets = () => {
  // Getters
  const assets = computed(() => state.value.assets)
  const asset = computed(() => state.value.asset)
  const loading = computed(() => state.value.loading)
  const error = computed(() => state.value.error)
  const pagination = computed(() => state.value.pagination)
  const filters = computed(() => state.value.filters)

  const hasAssets = computed(() => assets.value.length > 0)
  const isEmpty = computed(() => !loading.value && !hasAssets.value)
  const activeAssets = computed(() => assets.value.filter(asset => asset.status === 'active'))
  const maintenanceAssets = computed(() => assets.value.filter(asset => asset.status === 'maintenance'))

  // Actions
  const fetchAssets = async (page = 1) => {
    state.value.loading = true
    state.value.error = null

    try {
      const params = new URLSearchParams({
        page: page.toString(),
        per_page: state.value.pagination.per_page.toString(),
        ...Object.fromEntries(
          Object.entries(state.value.filters).filter(([_, value]) => 
            value !== undefined && value !== '' && value !== null
          )
        )
      })

      const response = await $api(`/api/test-assets?${params}`)
      
      state.value.assets = response.data || response
      
      // Update pagination if provided
      if (response.meta) {
        Object.assign(state.value.pagination, response.meta)
      }
    } catch (err) {
      state.value.error = err instanceof Error ? err.message : 'An error occurred'
      console.error('Error fetching assets:', err)
    } finally {
      state.value.loading = false
    }
  }

  const fetchAsset = async (id: number) => {
    state.value.loading = true
    state.value.error = null

    try {
      const response = await $api(`/api/assets/${id}`)
      state.value.asset = response.data || response
      
      return state.value.asset
    } catch (err) {
      state.value.error = err instanceof Error ? err.message : 'An error occurred'
      console.error('Error fetching asset:', err)
      throw err
    } finally {
      state.value.loading = false
    }
  }

  const createAsset = async (assetData: CreateAssetData) => {
    state.value.loading = true
    state.value.error = null

    try {
      const formData = new FormData()
      
      // Add all asset data to FormData
      Object.entries(assetData).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          if (key === 'image' && value instanceof File) {
            formData.append('image', value)
          } else {
            formData.append(key, value.toString())
          }
        }
      })

      const response = await $api('/api/assets', {
        method: 'POST',
        body: formData
      })

      const newAsset = response.data || response

      // Add to local state
      state.value.assets.unshift(newAsset)

      return newAsset
    } catch (err) {
      state.value.error = err instanceof Error ? err.message : 'Failed to create asset'
      console.error('Error creating asset:', err)
      throw err
    } finally {
      state.value.loading = false
    }
  }

  const updateAsset = async (id: number, assetData: UpdateAssetData) => {
    state.value.loading = true
    state.value.error = null

    try {
      const formData = new FormData()
      formData.append('_method', 'PUT')
      
      // Add all asset data to FormData
      Object.entries(assetData).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          if (key === 'image' && value instanceof File) {
            formData.append('image', value)
          } else {
            formData.append(key, value.toString())
          }
        }
      })

      const response = await $api(`/api/assets/${id}`, {
        method: 'POST', // Using POST with _method=PUT for FormData
        body: formData
      })

      const updatedAsset = response.data || response

      // Update local state
      const index = state.value.assets.findIndex(a => a.id === id)
      if (index !== -1) {
        state.value.assets[index] = updatedAsset
      }

      return updatedAsset
    } catch (err) {
      state.value.error = err instanceof Error ? err.message : 'Failed to update asset'
      console.error('Error updating asset:', err)
      throw err
    } finally {
      state.value.loading = false
    }
  }

  const deleteAsset = async (id: number) => {
    state.value.loading = true
    state.value.error = null

    try {
      await $api(`/api/assets/${id}`, {
        method: 'DELETE'
      })

      // Remove from local state
      state.value.assets = state.value.assets.filter(a => a.id !== id)

      return true
    } catch (err) {
      state.value.error = err instanceof Error ? err.message : 'Failed to delete asset'
      console.error('Error deleting asset:', err)
      throw err
    } finally {
      state.value.loading = false
    }
  }

  const bulkDeleteAssets = async (assetIds: number[]) => {
    state.value.loading = true
    state.value.error = null

    try {
      await $api('/api/assets/bulk-delete', {
        method: 'POST',
        body: { asset_ids: assetIds }
      })

      // Remove from local state
      state.value.assets = state.value.assets.filter(a => !assetIds.includes(a.id))

      return true
    } catch (err) {
      state.value.error = err instanceof Error ? err.message : 'Failed to delete assets'
      console.error('Error deleting assets:', err)
      throw err
    } finally {
      state.value.loading = false
    }
  }

  const updateAssetStatus = async (id: number, status: Asset['status']) => {
    return await updateAsset(id, { status })
  }

  const assignAsset = async (id: number, assigned_to: string) => {
    return await updateAsset(id, { assigned_to })
  }

  const searchAssets = async (query: string) => {
    state.value.filters.search = query
    await fetchAssets(1)
  }

  const filterAssets = async (newFilters: Partial<AssetFilters>) => {
    Object.assign(state.value.filters, newFilters)
    await fetchAssets(1)
  }

  const resetFilters = async () => {
    Object.assign(state.value.filters, {
      search: '',
      category: '',
      location: '',
      condition: '',
      status: '',
      department: '',
      assigned_to: '',
      sort_by: 'asset_code',
      sort_order: 'asc'
    })
    await fetchAssets(1)
  }

  const getAssetsByCategory = (category: string) => {
    return assets.value.filter(asset => asset.category === category)
  }

  const getAssetsByLocation = (location: string) => {
    return assets.value.filter(asset => asset.location === location)
  }

  const getAssetsByDepartment = (department: string) => {
    return assets.value.filter(asset => asset.department === department)
  }

  const getAssetStats = async () => {
    try {
      const response = await $api('/api/assets-stats')
      return response.data || response
    } catch (err) {
      console.error('Error fetching asset stats:', err)
      return null
    }
  }

  const getCategories = async () => {
    try {
      const response = await $api('/api/assets-categories')
      return response.data || response
    } catch (err) {
      console.error('Error fetching categories:', err)
      return []
    }
  }

  const getLocations = async () => {
    try {
      const response = await $api('/api/assets-locations')
      return response.data || response
    } catch (err) {
      console.error('Error fetching locations:', err)
      return []
    }
  }

  // Utility functions
  const clearError = () => {
    state.value.error = null
  }

  const reset = () => {
    state.value = {
      assets: [],
      asset: null,
      loading: false,
      error: null,
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
      }
    }
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
    fetchAsset,
    createAsset,
    updateAsset,
    deleteAsset,
    bulkDeleteAssets,
    updateAssetStatus,
    assignAsset,
    searchAssets,
    filterAssets,
    resetFilters,
    getAssetsByCategory,
    getAssetsByLocation,
    getAssetsByDepartment,
    getAssetStats,
    getCategories,
    getLocations,

    // Utilities
    clearError,
    reset
  }
}
