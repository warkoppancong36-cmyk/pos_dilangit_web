// Items Composable - State Management
import { ItemsApi } from '@/utils/api/ItemsApi'
import { computed, reactive, ref } from 'vue'

// Types
export interface Item {
  id_item: number
  item_code: string
  name: string
  description?: string
  unit: string
  cost_per_unit: number
  current_stock: number
  minimum_stock: number
  maximum_stock?: number
  supplier_id?: number
  storage_location?: string
  expiry_date?: string
  active: boolean
  is_delivery?: boolean
  is_takeaway?: boolean
  properties?: Record<string, any>
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
  deleted_at?: string
  formatted_cost_per_unit?: string
  stock_status?: 'in_stock' | 'low_stock' | 'out_of_stock'
  is_low_stock?: boolean
  stock_percentage?: number
  supplier?: any
  creator?: any
  updater?: any
  inventory?: {
    id_inventory: number
    current_stock: number
    reorder_level: number
    max_stock_level?: number
    available_stock: number
    reserved_stock: number
  }
}

export interface ItemFormData {
  id_item?: number
  name: string
  description?: string
  unit: string
  cost_per_unit?: number  // Add back for backend compatibility
  current_stock?: number  // Add back for backend compatibility  
  minimum_stock?: number  // Add back for backend compatibility
  storage_location?: string
  expiry_date?: string
  active?: boolean
  is_delivery?: boolean
  is_takeaway?: boolean
  properties?: Record<string, any>
}

export interface ItemFilters {
  search?: string
  active?: string | boolean
  unit?: string
  stock_status?: string
  expiring_days?: number
  show_expired?: boolean
}

export interface ItemStats {
  total_items: number
  active_items: number
  inactive_items: number
  low_stock_items: number
  out_of_stock_items: number
  total_value: number
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

// State
const itemsList = ref<Item[]>([])
const loading = ref(false)
const saveLoading = ref(false)
const deleteLoading = ref(false)
const stats = ref<ItemStats>({
  total_items: 0,
  active_items: 0,
  inactive_items: 0,
  low_stock_items: 0,
  out_of_stock_items: 0,
  total_value: 0
})

// Dialog states
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedItem = ref<Item | null>(null)
const selectedItems = ref<number[]>([])

// Pagination
const currentPage = ref(1)
const totalItems = ref(0)
const itemsPerPage = ref(15)

// Filters
const filters = reactive<ItemFilters>({
  search: '',
  active: 'all',
  unit: '',
  stock_status: 'all',
  expiring_days: undefined,
  show_expired: false
})

// Messages
const errorMessage = ref('')
const successMessage = ref('')
const modalErrorMessage = ref('')

// Form
const formData = reactive<ItemFormData>({
  name: '',
  description: '',
  unit: '',
  cost_per_unit: 0,        // Default value for backend
  current_stock: 0,        // Default value for backend
  minimum_stock: 0,        // Default value for backend
  storage_location: '',
  expiry_date: undefined,
  active: true,
  is_delivery: false,
  is_takeaway: false,
  properties: {}
})

// Computed
const canCreateEdit = computed(() => {
  // Add role-based permissions here
  return true
})

const hasSelectedItems = computed(() => {
  return selectedItems.value.length > 0
})

const stockStatusOptions = computed(() => [
  { title: 'Semua Status', value: 'all' },
  { title: 'Stok Tersedia', value: 'in_stock' },
  { title: 'Stok Rendah', value: 'low_stock' },
  { title: 'Stok Habis', value: 'out_of_stock' }
])

const unitOptions = computed(() => {
  const units = new Set(itemsList.value.map(item => item.unit))
  return Array.from(units).map(unit => ({ title: unit, value: unit }))
})

// Methods
const fetchItemsList = async () => {
  loading.value = true
  errorMessage.value = ''
  
  try {
    const params: any = {
      page: currentPage.value,
      per_page: itemsPerPage.value,
      ...filters
    }

    // Clean up undefined values properly
    Object.keys(params).forEach(key => {
      if (params[key] === undefined || params[key] === '' || params[key] === 'all') {
        delete params[key]
      }
    })

    console.log('Fetching items with params:', params) // Debug log

    const response = await ItemsApi.getAll(params)
    
    if (response.success && response.data) {
      const paginatedData = response.data as PaginatedResponse<Item>
      itemsList.value = paginatedData.data || []
      totalItems.value = paginatedData.total || 0
      currentPage.value = paginatedData.current_page || 1
      
      console.log('Items fetched:', {
        data: paginatedData.data?.length,
        total: paginatedData.total,
        current_page: paginatedData.current_page
      }) // Debug log
    }
  } catch (error: any) {
    console.error('Error fetching items:', error)
    errorMessage.value = error.message || 'Failed to fetch items'
    itemsList.value = []
    totalItems.value = 0
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await ItemsApi.getStats()
    if (response.success && response.data) {
      stats.value = response.data
    }
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

const saveItem = async () => {
  if (!validateForm()) {
    return
  }

  saveLoading.value = true
  modalErrorMessage.value = ''

  try {
    let response
    
    if (editMode.value && selectedItem.value) {
      response = await ItemsApi.update(selectedItem.value.id_item, formData)
    } else {
      response = await ItemsApi.create(formData)
    }

    if (response.success) {
      successMessage.value = editMode.value ? 'Item berhasil diperbarui!' : 'Item berhasil ditambahkan!'
      closeDialog()
      await fetchItemsList()
      await fetchStats()
    } else {
      modalErrorMessage.value = response.message || 'Terjadi kesalahan saat menyimpan item'
    }
  } catch (error: any) {
    console.error('Error saving item:', error)
    modalErrorMessage.value = error.message || 'Terjadi kesalahan saat menyimpan item'
  } finally {
    saveLoading.value = false
  }
}

const deleteItem = async () => {
  if (!selectedItem.value) return

  deleteLoading.value = true

  try {
    const response = await ItemsApi.delete(selectedItem.value.id_item)
    
    if (response.success) {
      successMessage.value = 'Item berhasil dihapus!'
      closeDeleteDialog()
      await fetchItemsList()
      await fetchStats()
    } else {
      errorMessage.value = response.message || 'Gagal menghapus item'
    }
  } catch (error: any) {
    console.error('Error deleting item:', error)
    errorMessage.value = error.message || 'Terjadi kesalahan saat menghapus item'
  } finally {
    deleteLoading.value = false
  }
}

const validateForm = (): boolean => {
  if (!formData.name.trim()) {
    modalErrorMessage.value = 'Nama item harus diisi'
    return false
  }
  
  if (!formData.unit.trim()) {
    modalErrorMessage.value = 'Satuan harus diisi'
    return false
  }

  return true
}

// Dialog methods
const openCreateDialog = () => {
  editMode.value = false
  selectedItem.value = null
  resetForm()
  dialog.value = true
}

const openEditDialog = (item: Item) => {
  editMode.value = true
  selectedItem.value = item
  fillFormData(item)
  dialog.value = true
}

const openDeleteDialog = (item: Item) => {
  selectedItem.value = item
  deleteDialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  clearModalError()
  resetForm()
}

const closeDeleteDialog = () => {
  deleteDialog.value = false
  selectedItem.value = null
}

const resetForm = () => {
  Object.assign(formData, {
    name: '',
    description: '',
    unit: '',
    cost_per_unit: 0,        // Default value for backend
    current_stock: 0,        // Default value for backend
    minimum_stock: 0,        // Default value for backend
    storage_location: '',
    expiry_date: undefined,
    active: true,
    is_delivery: false,
    is_takeaway: false,
    properties: {}
  })
}

const fillFormData = (item: Item) => {
  Object.assign(formData, {
    name: item.name,
    description: item.description || '',
    unit: item.unit,
    cost_per_unit: item.cost_per_unit || 0,
    current_stock: item.current_stock || 0,
    minimum_stock: item.minimum_stock || 0,
    storage_location: item.storage_location || '',
    expiry_date: item.expiry_date,
    active: item.active,
    is_delivery: item.is_delivery || false,
    is_takeaway: item.is_takeaway || false,
    properties: item.properties || {}
  })
}

const clearModalError = () => {
  modalErrorMessage.value = ''
}

// Pagination
const onPageChange = (page: number) => {
  currentPage.value = page
  fetchItemsList()
}

const onItemsPerPageChange = (newItemsPerPage: number) => {
  itemsPerPage.value = newItemsPerPage
  currentPage.value = 1  // Reset to first page when changing items per page
  fetchItemsList()
}

// Filters
const handleFiltersUpdate = () => {
  currentPage.value = 1
  fetchItemsList()
}

const clearFilters = () => {
  Object.assign(filters, {
    search: '',
    active: 'all',
    supplier_id: undefined,
    unit: '',
    stock_status: 'all',
    expiring_days: undefined,
    show_expired: false
  })
  handleFiltersUpdate()
}

export const useItems = () => {
  return {
    // State
    itemsList,
    loading,
    saveLoading,
    deleteLoading,
    stats,
    dialog,
    deleteDialog,
    editMode,
    selectedItem,
    selectedItems,
    currentPage,
    totalItems,
    itemsPerPage,
    filters,
    errorMessage,
    successMessage,
    modalErrorMessage,
    formData,
    
    // Computed
    canCreateEdit,
    hasSelectedItems,
    stockStatusOptions,
    unitOptions,
    
    // Methods
    fetchItemsList,
    fetchStats,
    saveItem,
    deleteItem,
    openCreateDialog,
    openEditDialog,
    openDeleteDialog,
    closeDialog,
    closeDeleteDialog,
    clearModalError,
    onPageChange,
    onItemsPerPageChange,
    handleFiltersUpdate,
    clearFilters
  }
}
