// Stock Movement Composable - State Management for Stock Operations
import type { Item } from '@/composables/useItems'
import { InventoryApi } from '@/utils/api/InventoryApi'
import { ItemsApi } from '@/utils/api/ItemsApi'
import { computed, reactive, ref } from 'vue'

// Types for Stock Movement Management
export interface StockMovement {
  id?: number
  item_id: number
  type: 'in' | 'out' | 'adjustment' | 'production' | 'waste'
  quantity: number
  unit: string
  reference_id?: number
  reference_type?: 'purchase' | 'sale' | 'production' | 'adjustment' | 'waste'
  notes?: string
  cost_per_unit?: number
  total_cost?: number
  created_by?: number
  created_at?: string
  item?: Item
  creator?: any
}

export interface StockStats {
  total_items: number
  total_value: number
  low_stock_items: number
  out_of_stock_items: number
  movements_today: number
  movements_this_month: number
  top_consumed_items: Item[]
  recent_movements: StockMovement[]
}

export interface StockMovementFormData {
  item_id: number
  type: 'in' | 'out' | 'adjustment'
  quantity: number
  notes?: string
  cost_per_unit?: number
  reference_type?: string
  reference_id?: number
}

export interface StockFilters {
  search?: string
  stock_status?: 'all' | 'in_stock' | 'low_stock' | 'out_of_stock'
  movement_type?: string
  date_from?: string
  date_to?: string
  item_id?: number
}

// Composable
export function useStockMovements() {
  // State
  const itemsList = ref<Item[]>([])
  const stockMovements = ref<StockMovement[]>([])
  const loading = ref(false)
  const saveLoading = ref(false)
  const stats = ref<StockStats>({
    total_items: 0,
    total_value: 0,
    low_stock_items: 0,
    out_of_stock_items: 0,
    movements_today: 0,
    movements_this_month: 0,
    top_consumed_items: [],
    recent_movements: []
  })

  // Pagination
  const currentPage = ref(1)
  const totalItems = ref(0)
  const itemsPerPage = ref(10)

  // Filters
  const filters = reactive<StockFilters>({
    search: '',
    stock_status: 'all',
    movement_type: '',
    date_from: '',
    date_to: '',
    item_id: undefined
  })

  // Dialog states
  const movementDialog = ref(false)
  const adjustmentDialog = ref(false)
  const selectedItem = ref<Item | null>(null)

  // Form data
  const movementFormData = reactive<StockMovementFormData>({
    item_id: 0,
    type: 'in',
    quantity: 0,
    notes: '',
    cost_per_unit: 0
  })

  // Error handling
  const errorMessage = ref('')
  const successMessage = ref('')
  const modalErrorMessage = ref('')

  // Computed
  const canManageStock = computed(() => true)

  const stockStatusOptions = [
    { title: 'Semua Status', value: 'all' },
    { title: 'Tersedia', value: 'in_stock' },
    { title: 'Stok Rendah', value: 'low_stock' },
    { title: 'Habis', value: 'out_of_stock' }
  ]

  const movementTypeOptions = [
    { title: 'Masuk', value: 'in', color: 'success', icon: 'tabler-plus' },
    { title: 'Keluar', value: 'out', color: 'error', icon: 'tabler-minus' },
    { title: 'Penyesuaian', value: 'adjustment', color: 'warning', icon: 'tabler-adjustments' }
  ]

  const referenceTypeOptions = [
    { title: 'Pembelian', value: 'purchase' },
    { title: 'Penjualan', value: 'sale' },
    { title: 'Produksi', value: 'production' },
    { title: 'Penyesuaian', value: 'adjustment' },
    { title: 'Kerusakan', value: 'waste' }
  ]

  // Get stock status info
  const getStockStatus = (item: Item) => {
    if (item.current_stock <= 0) {
      return { status: 'out_of_stock', color: 'error', text: 'Habis', icon: 'tabler-x' }
    } else if (item.current_stock <= item.minimum_stock) {
      return { status: 'low_stock', color: 'warning', text: 'Rendah', icon: 'tabler-alert-triangle' }
    } else {
      return { status: 'in_stock', color: 'success', text: 'Tersedia', icon: 'tabler-check' }
    }
  }

  // Calculate stock percentage
  const getStockPercentage = (item: Item): number => {
    if (!item.maximum_stock || item.maximum_stock <= 0) return 100
    return Math.round((item.current_stock / item.maximum_stock) * 100)
  }

  // API Functions (Mock for now)
  const fetchItems = async () => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 1000))
      itemsList.value = await ItemsApi.getAll()
      totalItems.value = itemsList.value.length
      successMessage.value = ''
    } catch (error) {
      errorMessage.value = 'Gagal memuat data items'
    } finally {
      loading.value = false
    }
  }

  const fetchStockMovements = async (itemId?: number) => {
    loading.value = true
    try {
      const response = await InventoryApi.getMovements({
        id_inventory: itemId,
        page: currentPage.value,
        per_page: itemsPerPage.value
      })
      
      // Map InventoryMovement to StockMovement format
      stockMovements.value = response.data.map(movement => ({
        id: movement.id_movement,
        item_id: movement.id_inventory, // This should map to the inventory/item
        type: movement.movement_type as 'in' | 'out' | 'adjustment' | 'production' | 'waste',
        quantity: movement.quantity,
        unit: 'pcs', // Default unit, should be from item data
        reference_id: movement.reference_number ? parseInt(movement.reference_number) : undefined,
        reference_type: movement.reason as any,
        notes: movement.notes,
        cost_per_unit: movement.cost_per_unit,
        total_cost: movement.total_cost,
        created_by: movement.created_by,
        created_at: movement.created_at,
        creator: movement.user
      }))
      
      totalItems.value = response.total
    } catch (error) {
      console.error('Failed to fetch stock movements:', error)
      stockMovements.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchStats = async () => {
    try {
      await fetchItems()
      const items = itemsList.value
      
      stats.value = {
        total_items: items.length,
        total_value: items.reduce((sum, item) => sum + (item.current_stock * item.cost_per_unit), 0),
        low_stock_items: items.filter(item => item.current_stock <= item.minimum_stock && item.current_stock > 0).length,
        out_of_stock_items: items.filter(item => item.current_stock <= 0).length,
        movements_today: 0,
        movements_this_month: 0,
        top_consumed_items: [],
        recent_movements: []
      }
    } catch (error) {
      console.error('Failed to fetch stats:', error)
    }
  }

  const recordStockMovement = async () => {
    saveLoading.value = true
    modalErrorMessage.value = ''
    
    try {
      // Call the actual API for stock movement recording
      const response = await fetch('/api/inventory/movement', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          item_id: movementFormData.item_id,
          movement_type: movementFormData.type,
          quantity: movementFormData.quantity,
          notes: movementFormData.notes,
          cost_per_unit: movementFormData.cost_per_unit
        })
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Gagal mencatat pergerakan stok')
      }

      successMessage.value = `Stock ${movementFormData.type === 'in' ? 'masuk' : movementFormData.type === 'out' ? 'keluar' : 'disesuaikan'} berhasil dicatat`
      closeMovementDialog()
      
      // Refresh data
      await fetchItems()
      await fetchStats()
    } catch (error: any) {
      modalErrorMessage.value = error.message || 'Gagal mencatat pergerakan stok'
    } finally {
      saveLoading.value = false
    }
  }

  // Dialog controls
  const openStockInDialog = (item: Item) => {
    selectedItem.value = item
    Object.assign(movementFormData, {
      item_id: item.id_item,
      type: 'in' as const,
      quantity: 0,
      notes: '',
      cost_per_unit: item.cost_per_unit
    })
    movementDialog.value = true
  }

  const openStockOutDialog = (item: Item) => {
    selectedItem.value = item
    Object.assign(movementFormData, {
      item_id: item.id_item,
      type: 'out' as const,
      quantity: 0,
      notes: '',
      cost_per_unit: item.cost_per_unit
    })
    movementDialog.value = true
  }

  const openAdjustmentDialog = (item: Item) => {
    selectedItem.value = item
    Object.assign(movementFormData, {
      item_id: item.id_item,
      type: 'adjustment' as const,
      quantity: item.current_stock, // Set to current stock for adjustment
      notes: '',
      cost_per_unit: item.cost_per_unit
    })
    adjustmentDialog.value = true
  }

  const closeMovementDialog = () => {
    movementDialog.value = false
    adjustmentDialog.value = false
    modalErrorMessage.value = ''
    selectedItem.value = null
  }

  const clearModalError = () => {
    modalErrorMessage.value = ''
  }

  // Pagination
  const onPageChange = (page: number) => {
    currentPage.value = page
    fetchItems()
  }

  const handleFiltersUpdate = (newFilters: StockFilters) => {
    Object.assign(filters, newFilters)
    currentPage.value = 1
    fetchItems()
  }

  return {
    // State
    itemsList,
    stockMovements,
    loading,
    saveLoading,
    stats,
    currentPage,
    totalItems,
    itemsPerPage,
    filters,
    movementDialog,
    adjustmentDialog,
    selectedItem,
    movementFormData,
    errorMessage,
    successMessage,
    modalErrorMessage,
    
    // Computed
    canManageStock,
    stockStatusOptions,
    movementTypeOptions,
    referenceTypeOptions,
    
    // Methods
    getStockStatus,
    getStockPercentage,
    fetchItems,
    fetchStockMovements,
    fetchStats,
    recordStockMovement,
    openStockInDialog,
    openStockOutDialog,
    openAdjustmentDialog,
    closeMovementDialog,
    clearModalError,
    onPageChange,
    handleFiltersUpdate
  }
}
