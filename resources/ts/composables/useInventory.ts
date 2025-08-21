import { InventoryApi } from '@/utils/api/InventoryApi'
import { computed, ref } from 'vue'

// Types
export interface InventoryItem {
  id_inventory: number
  id_product?: number | null
  id_item?: number | null
  current_stock: number
  reorder_level: number
  max_stock_level?: number
  average_cost: number
  reserved_stock: number
  available_stock: number
  stock_status: string
  is_low_stock: boolean
  created_at?: string
  updated_at?: string
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

export interface InventoryMovement {
  id_movement: number
  id_inventory: number
  movement_type: string
  quantity: number
  stock_before: number
  stock_after: number
  unit_cost?: number
  total_cost?: number
  reference_type?: string
  reference_id?: number
  batch_number?: string
  expiry_date?: string
  notes?: string
  created_by: number
  movement_date: string
  created_at: string
  updated_at: string
  // Relationships
  inventory?: InventoryItem
  user?: { id: number; name: string; email?: string }
  // Computed attributes
  formatted_quantity?: string
  formatted_cost?: string
  movement_type_name?: string
}

export interface InventoryFilters {
  search?: string
  stock_status?: 'all' | 'in_stock' | 'low_stock' | 'out_of_stock' | 'overstock'
  category_id?: number
  supplier_id?: number
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface InventoryStats {
  total_items: number
  low_stock_items: number
  out_of_stock_items: number
  overstock_items: number
  total_stock_value: number
  total_stock_quantity: number
  total_reserved_stock: number
  items_need_restock: number
}

export interface StockUpdateData {
  movement_type: 'stock_in' | 'stock_out' | 'adjustment' | 'transfer' | 'return'
  quantity: number
  reason: string
  reference_number?: string
  cost_per_unit?: number
  notes?: string
}

export interface BulkStockUpdateData {
  items: Array<{
    id_inventory: number
    movement_type: 'stock_in' | 'stock_out' | 'adjustment'
    quantity: number
    reason: string
    cost_per_unit?: number
  }>
}

export const useInventory = () => {
  // State
  const inventoryList = ref<InventoryItem[]>([])
  const loading = ref(false)
  const saveLoading = ref(false)
  const stats = ref<InventoryStats>({
    total_items: 0,
    low_stock_items: 0,
    out_of_stock_items: 0,
    overstock_items: 0,
    total_stock_value: 0,
    total_stock_quantity: 0,
    total_reserved_stock: 0,
    items_need_restock: 0
  })

  // Movements
  const movementsList = ref<InventoryMovement[]>([])
  const movementsLoading = ref(false)

  // Low stock alerts
  const lowStockItems = ref<InventoryItem[]>([])
  const alertsLoading = ref(false)

  // Dialog states
  const stockUpdateDialog = ref(false)
  const bulkUpdateDialog = ref(false)
  const movementsDialog = ref(false)
  const reorderDialog = ref(false)
  const selectedInventory = ref<InventoryItem | null>(null)
  const selectedItems = ref<number[]>([])

  // Pagination
  const currentPage = ref(1)
  const totalItems = ref(0)
  const itemsPerPage = ref(15)

  // Filters
  const filters = ref<InventoryFilters>({
    search: '',
    stock_status: 'all',
    category_id: undefined,
    supplier_id: undefined,
    sort_by: 'current_stock',
    sort_order: 'asc'
  })

  // Messages
  const errorMessage = ref('')
  const successMessage = ref('')
  const modalErrorMessage = ref('')

  // Stock update form
  const stockUpdateForm = ref<StockUpdateData>({
    movement_type: 'stock_in',
    quantity: 0,
    reason: '',
    reference_number: '',
    cost_per_unit: undefined,
    notes: ''
  })

  // Reorder level form
  const reorderForm = ref({
    reorder_level: 0,
    max_stock_level: 0
  })

  // Computed
  const hasSelectedItems = computed(() => selectedItems.value.length > 0)
  const totalStockValue = computed(() => stats.value.total_stock_value)
  const lowStockCount = computed(() => stats.value.low_stock_items)
  const outOfStockCount = computed(() => stats.value.out_of_stock_items)

  // Validation rules
  const quantityRules = [
    (v: number) => v > 0 || 'Kuantitas harus lebih dari 0',
    (v: number) => Number.isInteger(v) || 'Kuantitas harus berupa bilangan bulat'
  ]

  const reasonRules = [
    (v: string) => !!v || 'Alasan wajib diisi',
    (v: string) => (v && v.length >= 3) || 'Alasan minimal 3 karakter'
  ]

  const costRules = [
    (v?: number) => !v || v >= 0 || 'Harga harus lebih dari atau sama dengan 0'
  ]

  // Movement type options
  const movementTypeOptions = [
    { title: 'Stock Masuk', value: 'stock_in', color: 'success' },
    { title: 'Stock Keluar', value: 'stock_out', color: 'warning' },
    { title: 'Penyesuaian', value: 'adjustment', color: 'info' },
    { title: 'Transfer', value: 'transfer', color: 'primary' },
    { title: 'Retur', value: 'return', color: 'success' }
  ]

  // Stock status options
  const stockStatusOptions = [
    { title: 'Semua Status', value: 'all' },
    { title: 'Tersedia', value: 'in_stock' },
    { title: 'Stok Menipis', value: 'low_stock' },
    { title: 'Habis', value: 'out_of_stock' },
    { title: 'Stok Berlebih', value: 'overstock' }
  ]

  // Methods
  const fetchInventoryList = async () => {
    loading.value = true
    errorMessage.value = ''
    
    try {
      const params = {
        ...filters.value,
        page: currentPage.value,
        per_page: itemsPerPage.value
      }

      // Remove empty filters
      Object.keys(params).forEach(key => {
        const value = params[key as keyof typeof params]
        if (value === '' || value === undefined || value === null || value === 'all') {
          delete params[key as keyof typeof params]
        }
      })

      console.log('Fetching inventory with params:', params)
      const response = await InventoryApi.getAll(params)
      
      if (response.success) {
        // API mengembalikan data dalam structure: response.data.data (Laravel pagination)
        inventoryList.value = response.data.data
        totalItems.value = response.data.total
        currentPage.value = response.data.current_page
        
        // Log untuk debugging
        console.log('Inventory data loaded:', {
          total: response.data.total,
          itemsLength: response.data.data.length,
          currentPage: response.data.current_page,
          perPage: response.data.per_page,
          lastPage: response.data.last_page,
          from: response.data.from,
          to: response.data.to,
          requestedPerPage: itemsPerPage.value
        })
      } else {
        throw new Error(response.message || 'Gagal mengambil data inventory')
      }
    } catch (error: any) {
      console.error('Error fetching inventory:', error)
      errorMessage.value = InventoryApi.formatError(error) || 'Gagal mengambil data inventory'
      inventoryList.value = []
      totalItems.value = 0
    } finally {
      // Pastikan loading selalu diset ke false
      loading.value = false
    }
  }

  const fetchStats = async () => {
    try {
      const response = await InventoryApi.getStats()
      if (response.success) {
        stats.value = response.data
      }
    } catch (error: any) {
      console.error('Error fetching inventory stats:', error)
    }
  }

  const fetchLowStockAlerts = async () => {
    alertsLoading.value = true
    try {
      const response = await InventoryApi.getLowStockAlerts()
      if (response.success) {
        // Low stock alerts juga menggunakan struktur Laravel pagination
        lowStockItems.value = response.data.data || []
      }
    } catch (error: any) {
      console.error('Error fetching low stock alerts:', error)
      lowStockItems.value = []
    } finally {
      alertsLoading.value = false
    }
  }

  const fetchMovements = async (inventoryId?: number) => {
    movementsLoading.value = true
    try {
      const response = await InventoryApi.getMovements({
        id_inventory: inventoryId,
        page: 1,
        per_page: 20
      })
      if (response.success) {
        movementsList.value = response.data
      }
    } catch (error: any) {
      console.error('Error fetching movements:', error)
    } finally {
      movementsLoading.value = false
    }
  }

  const updateStock = async () => {
    if (!selectedInventory.value) return
    
    saveLoading.value = true
    modalErrorMessage.value = ''
    
    try {
      const response = await InventoryApi.updateStock(
        selectedInventory.value.id_inventory,
        stockUpdateForm.value
      )
      
      if (response.success) {
        successMessage.value = 'Stock berhasil diperbarui'
        closeStockUpdateDialog()
        await fetchInventoryList()
        await fetchStats()
        await fetchLowStockAlerts()
      } else {
        throw new Error(response.message || 'Gagal memperbarui stock')
      }
    } catch (error: any) {
      console.error('Error updating stock:', error)
      modalErrorMessage.value = InventoryApi.formatError(error) || 'Gagal memperbarui stock'
    } finally {
      saveLoading.value = false
    }
  }

  const setReorderLevel = async () => {
    if (!selectedInventory.value) return
    
    saveLoading.value = true
    modalErrorMessage.value = ''
    
    try {
      const response = await InventoryApi.setReorderLevel(
        selectedInventory.value.id_inventory,
        reorderForm.value
      )
      
      if (response.success) {
        successMessage.value = 'Reorder level berhasil diperbarui'
        closeReorderDialog()
        await fetchInventoryList()
      } else {
        throw new Error(response.message || 'Gagal memperbarui reorder level')
      }
    } catch (error: any) {
      console.error('Error setting reorder level:', error)
      modalErrorMessage.value = InventoryApi.formatError(error) || 'Gagal memperbarui reorder level'
    } finally {
      saveLoading.value = false
    }
  }

  // Dialog methods
  const openStockUpdateDialog = (inventory: InventoryItem) => {
    selectedInventory.value = inventory
    stockUpdateForm.value = {
      movement_type: 'stock_in',
      quantity: 0,
      reason: '',
      reference_number: '',
      cost_per_unit: inventory.average_cost || undefined,
      notes: ''
    }
    modalErrorMessage.value = ''
    stockUpdateDialog.value = true
  }

  const openReorderDialog = (inventory: InventoryItem) => {
    selectedInventory.value = inventory
    reorderForm.value = {
      reorder_level: inventory.reorder_level || 0,
      max_stock_level: inventory.max_stock_level || 0
    }
    modalErrorMessage.value = ''
    reorderDialog.value = true
  }

  const openMovementsDialog = (inventory: InventoryItem) => {
    selectedInventory.value = inventory
    fetchMovements(inventory.id_inventory)
    movementsDialog.value = true
  }

  const closeStockUpdateDialog = () => {
    stockUpdateDialog.value = false
    setTimeout(() => {
      selectedInventory.value = null
      modalErrorMessage.value = ''
    }, 300)
  }

  const closeReorderDialog = () => {
    reorderDialog.value = false
    setTimeout(() => {
      selectedInventory.value = null
      modalErrorMessage.value = ''
    }, 300)
  }

  const closeMovementsDialog = () => {
    movementsDialog.value = false
    setTimeout(() => {
      selectedInventory.value = null
      movementsList.value = []
    }, 300)
  }

  const clearModalError = () => {
    modalErrorMessage.value = ''
  }

  // Pagination and filters
  const onPageChange = (page: number) => {
    console.log('Page changed to:', page)
    currentPage.value = page
    fetchInventoryList()
  }

  const onItemsPerPageChange = (itemsPerPageValue: number) => {
    console.log('Items per page changed to:', itemsPerPageValue)
    itemsPerPage.value = itemsPerPageValue
    currentPage.value = 1 // Reset to first page when changing items per page
    fetchInventoryList()
  }

  const handleFiltersUpdate = (newFilters: Partial<InventoryFilters>) => {
    filters.value = { ...filters.value, ...newFilters }
    currentPage.value = 1
    fetchInventoryList()
  }

  // Utility functions
  const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value)
  }

  const getStockStatusColor = (inventory: InventoryItem): string => {
    if (inventory.current_stock === 0) return 'error'
    if (inventory.is_low_stock) return 'warning'
    return 'success'
  }

  const getStockStatusText = (inventory: InventoryItem): string => {
    if (inventory.current_stock === 0) return 'Habis'
    if (inventory.reorder_level > 0 && inventory.current_stock <= inventory.reorder_level) return 'Menipis'
    if (inventory.max_stock_level && inventory.current_stock > inventory.max_stock_level) return 'Berlebih'
    return 'Tersedia'
  }

  const getMovementTypeColor = (type: string): string => {
    const colors: Record<string, string> = {
      'stock_in': 'success',
      'stock_out': 'warning',
      'adjustment': 'info',
      'transfer': 'primary',
      'return': 'success',
      'damaged': 'error',
      'expired': 'error'
    }
    return colors[type] || 'grey'
  }

  return {
    // State
    inventoryList,
    loading,
    saveLoading,
    stats,
    movementsList,
    movementsLoading,
    lowStockItems,
    alertsLoading,
    stockUpdateDialog,
    bulkUpdateDialog,
    movementsDialog,
    reorderDialog,
    selectedInventory,
    selectedItems,
    currentPage,
    totalItems,
    itemsPerPage,
    filters,
    errorMessage,
    successMessage,
    modalErrorMessage,
    stockUpdateForm,
    reorderForm,

    // Computed
    hasSelectedItems,
    totalStockValue,
    lowStockCount,
    outOfStockCount,

    // Validation
    quantityRules,
    reasonRules,
    costRules,

    // Options
    movementTypeOptions,
    stockStatusOptions,

    // Methods
    fetchInventoryList,
    fetchStats,
    fetchLowStockAlerts,
    fetchMovements,
    updateStock,
    setReorderLevel,
    openStockUpdateDialog,
    openReorderDialog,
    openMovementsDialog,
    closeStockUpdateDialog,
    closeReorderDialog,
    closeMovementsDialog,
    clearModalError,
    onPageChange,
    onItemsPerPageChange,
    handleFiltersUpdate,
    formatCurrency,
    getStockStatusColor,
    getStockStatusText,
    getMovementTypeColor
  }
}
