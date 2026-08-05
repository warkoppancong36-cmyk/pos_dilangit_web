// Items Composable - State Management
import { computed, reactive, ref } from 'vue'
import { ItemsApi } from '@/utils/api/ItemsApi'

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
  available_in_kitchen?: boolean
  available_in_bar?: boolean
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
  cost_per_unit?: number // Add back for backend compatibility
  current_stock?: number // Add back for backend compatibility
  minimum_stock?: number // Add back for backend compatibility
  storage_location?: string
  expiry_date?: string
  active?: boolean
  available_in_kitchen?: boolean
  available_in_bar?: boolean
  properties?: Record<string, any>
}

export interface ItemFilters {
  search?: string
  active?: string | boolean
  unit?: string
  stock_status?: string
  station?: string
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
  total_value: 0,
})

// Dialog states
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedItem = ref<Item | null>(null)
const selectedItems = ref<number[]>([])

// Export states
const exportLoading = ref(false)
const exportDialog = ref(false)

// Pagination
const currentPage = ref(1)
const totalItems = ref(0)
const itemsPerPage = ref(15)

// Filters
const initialFilters: ItemFilters = {
  search: '',
  active: true,
  unit: '',
  stock_status: '',
  station: 'all',
}

const filters = reactive<ItemFilters>({ ...initialFilters })

// Messages
const errorMessage = ref('')
const successMessage = ref('')
const modalErrorMessage = ref('')

// Form
const formData = reactive<ItemFormData>({
  name: '',
  description: '',
  unit: '',
  cost_per_unit: 0, // Default value for backend
  current_stock: 0, // Default value for backend
  minimum_stock: 0, // Default value for backend
  storage_location: '',
  expiry_date: undefined,
  active: true,
  available_in_kitchen: true,
  available_in_bar: true,
  properties: {},
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
  { title: 'Stok Habis', value: 'out_of_stock' },
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
      ...filters,
    }

    // Clean up undefined values and 'all' values
    Object.keys(params).forEach(key => {
      if (params[key] === undefined || params[key] === 'all')
        delete params[key]

      // Don't send empty strings for most filters, except search which can be empty
      if (params[key] === '' && key !== 'search')
        delete params[key]
    })

    const response = await ItemsApi.getAll(params)

    if (response.success && response.data) {
      const paginatedData = response.data as PaginatedResponse<Item>

      itemsList.value = paginatedData.data || []
      totalItems.value = paginatedData.total || 0
      currentPage.value = paginatedData.current_page || 1
    }
  }
  catch (error: any) {
    console.error('Error fetching items:', error)
    errorMessage.value = error.message || 'Failed to fetch items'
    itemsList.value = []
    totalItems.value = 0
  }
  finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await ItemsApi.getStats()
    if (response.success && response.data)
      stats.value = response.data
  }
  catch (error) {
    console.error('Error fetching stats:', error)
  }
}

// Fetch ALL items data for export (bypass pagination)
const fetchAllItemsForExport = async (): Promise<Item[]> => {
  const params: any = {
    page: 1,
    per_page: 999999,
    ...filters,
  }

  // Clean up undefined values and 'all' values (same rules as fetchItemsList)
  Object.keys(params).forEach(key => {
    if (params[key] === undefined || params[key] === 'all')
      delete params[key]

    if (params[key] === '' && key !== 'search')
      delete params[key]
  })

  const response = await ItemsApi.getAll(params)

  if (!response.success || !response.data)
    throw new Error(response.message || 'Gagal mengambil data item lengkap')

  const paginatedData = response.data as PaginatedResponse<Item>
  const items = paginatedData.data || []

  // Remove duplicates based on id_item
  return items.filter((item, index, self) =>
    index === self.findIndex(t => t.id_item === item.id_item),
  )
}

const stockStatusLabels: Record<string, string> = {
  out_of_stock: 'Stok Habis',
  low_stock: 'Stok Rendah',
  in_stock: 'Tersedia',
}

const getStockStatusLabel = (item: Item): string => {
  if (item.stock_status && stockStatusLabels[item.stock_status])
    return stockStatusLabels[item.stock_status]

  const stock = item.inventory?.current_stock ?? 0
  if (stock <= 0)
    return 'Stok Habis'
  if (stock <= (item.inventory?.reorder_level ?? 0))
    return 'Stok Rendah'

  return 'Tersedia'
}

const exportToExcel = async () => {
  try {
    exportLoading.value = true

    // Fetch ALL items for export (not limited to current page)
    const allItems = await fetchAllItemsForExport()

    // Dynamic import for better performance
    const XLSX = await import('xlsx')

    // Summary statistics
    const totalAllItems = allItems.length
    const lowStockCount = allItems.filter(item => getStockStatusLabel(item) === 'Stok Rendah').length
    const outOfStockCount = allItems.filter(item => getStockStatusLabel(item) === 'Stok Habis').length

    const totalStockValue = allItems.reduce((sum, item) =>
      sum + ((item.inventory?.current_stock ?? 0) * (item.cost_per_unit || 0)), 0,
    )

    const exportData = allItems.map((item, index) => ({
      'No': index + 1,
      'Nama Item': item.name,
      'Kode Item': item.item_code || '-',
      'Deskripsi': item.description || '-',
      'Satuan': item.unit,
      'Harga/Unit': item.cost_per_unit || 0,
      'Stok Saat Ini': item.inventory?.current_stock ?? 0,
      'Stok Minimum': item.inventory?.reorder_level ?? 0,
      'Status Stok': getStockStatusLabel(item),
      'Kitchen': item.available_in_kitchen ? 'Ya' : 'Tidak',
      'Bar': item.available_in_bar ? 'Ya' : 'Tidak',
      'Lokasi': item.storage_location || '-',
      'Status': item.active ? 'Aktif' : 'Nonaktif',
      'Nilai Stok': (item.inventory?.current_stock ?? 0) * (item.cost_per_unit || 0),
      'Dibuat': item.created_at ? new Date(item.created_at).toLocaleDateString('id-ID') : '-',
      'Diperbarui': item.updated_at ? new Date(item.updated_at).toLocaleDateString('id-ID') : '-',
    }))

    const workbook = XLSX.utils.book_new()

    // Filter info for report header
    const filterInfo: string[] = []
    if (filters.search)
      filterInfo.push(`Pencarian: "${filters.search}"`)

    if (filters.active !== undefined && filters.active !== 'all' && filters.active !== '') {
      const isActive = filters.active === true || filters.active === 'true'

      filterInfo.push(`Status: ${isActive ? 'Aktif' : 'Nonaktif'}`)
    }
    if (filters.stock_status && filters.stock_status !== 'all') {
      const stockLabels: Record<string, string> = {
        in_stock: 'Stok Tersedia',
        low_stock: 'Stok Rendah',
        out_of_stock: 'Stok Habis',
      }

      filterInfo.push(`Stok: ${stockLabels[filters.stock_status] || filters.stock_status}`)
    }
    if (filters.station && filters.station !== 'all') {
      const stationLabels: Record<string, string> = {
        kitchen: 'Kitchen Only',
        bar: 'Bar Only',
        both: 'Kitchen & Bar',
      }

      filterInfo.push(`Station: ${stationLabels[filters.station] || filters.station}`)
    }
    if (filters.unit)
      filterInfo.push(`Satuan: ${filters.unit}`)

    const titleData = [
      ['LAPORAN DATA ITEM'],
      ['Tanggal Export:', new Date().toLocaleDateString('id-ID', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
      })],
      ['Total Item:', totalAllItems],
      ['Total Nilai Stok:', `Rp ${totalStockValue.toLocaleString('id-ID')}`],
      ['Item Stok Rendah:', lowStockCount],
      ['Item Stok Habis:', outOfStockCount],
      ['Filter Diterapkan:', filterInfo.length > 0 ? filterInfo.join(', ') : 'Semua Data'],
      [], // Empty row
    ]

    const worksheet = XLSX.utils.aoa_to_sheet(titleData)

    // Add main data after title and summary rows
    XLSX.utils.sheet_add_json(worksheet, exportData, { origin: 'A9' })

    worksheet['!cols'] = [
      { wch: 5 }, // No
      { wch: 30 }, // Nama Item
      { wch: 18 }, // Kode Item
      { wch: 30 }, // Deskripsi
      { wch: 8 }, // Satuan
      { wch: 15 }, // Harga/Unit
      { wch: 12 }, // Stok Saat Ini
      { wch: 12 }, // Stok Minimum
      { wch: 12 }, // Status Stok
      { wch: 8 }, // Kitchen
      { wch: 8 }, // Bar
      { wch: 18 }, // Lokasi
      { wch: 10 }, // Status
      { wch: 15 }, // Nilai Stok
      { wch: 12 }, // Dibuat
      { wch: 12 }, // Diperbarui
    ]

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Data Item')

    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')

    XLSX.writeFile(workbook, `item-report-${timestamp}.xlsx`)

    successMessage.value = `Laporan berhasil di-export ke Excel dengan ${totalAllItems} item lengkap!`
  }
  catch (error: any) {
    console.error('Export error:', error)
    errorMessage.value = `Gagal mengexport laporan: ${error?.message || 'Unknown error'}`
  }
  finally {
    exportLoading.value = false
  }
}

const openExportDialog = () => {
  exportDialog.value = true
}

const closeExportDialog = () => {
  exportDialog.value = false
}

const saveItem = async () => {
  if (!validateForm())
    return

  saveLoading.value = true
  modalErrorMessage.value = ''

  try {
    let response

    if (editMode.value && selectedItem.value)
      response = await ItemsApi.update(selectedItem.value.id_item, formData)
    else
      response = await ItemsApi.create(formData)

    if (response.success) {
      successMessage.value = editMode.value ? 'Item berhasil diperbarui!' : 'Item berhasil ditambahkan!'
      closeDialog()
      await fetchItemsList()
      await fetchStats()
    }
    else {
      modalErrorMessage.value = response.message || 'Terjadi kesalahan saat menyimpan item'
    }
  }
  catch (error: any) {
    console.error('Error saving item:', error)
    modalErrorMessage.value = error.message || 'Terjadi kesalahan saat menyimpan item'
  }
  finally {
    saveLoading.value = false
  }
}

const deleteItem = async () => {
  if (!selectedItem.value)
    return

  deleteLoading.value = true

  try {
    const response = await ItemsApi.delete(selectedItem.value.id_item)

    if (response.success) {
      successMessage.value = 'Item berhasil dihapus!'
      closeDeleteDialog()
      await fetchItemsList()
      await fetchStats()
    }
    else {
      errorMessage.value = response.message || 'Gagal menghapus item'
    }
  }
  catch (error: any) {
    console.error('Error deleting item:', error)
    errorMessage.value = error.message || 'Terjadi kesalahan saat menghapus item'
  }
  finally {
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
    cost_per_unit: 0, // Default value for backend
    current_stock: 0, // Default value for backend
    minimum_stock: 0, // Default value for backend
    storage_location: '',
    expiry_date: undefined,
    active: true,
    available_in_kitchen: true,
    available_in_bar: true,
    properties: {},
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
    available_in_kitchen: item.available_in_kitchen ?? true,
    available_in_bar: item.available_in_bar ?? true,
    properties: item.properties || {},
  })
}

const clearModalError = () => {
  modalErrorMessage.value = ''
}

// Pagination
const onPageChange = (page: number) => {
  if (page !== currentPage.value) {
    currentPage.value = page
    fetchItemsList()
  }
}

const onItemsPerPageChange = (newItemsPerPage: number) => {
  if (newItemsPerPage !== itemsPerPage.value) {
    itemsPerPage.value = newItemsPerPage
    currentPage.value = 1 // Reset to first page when changing items per page
    fetchItemsList()
  }
}

// Filters
const handleFiltersUpdate = (newFilters?: ItemFilters) => {
  if (newFilters)
    Object.assign(filters, newFilters)

  currentPage.value = 1
  fetchItemsList()
}

const clearFilters = () => {
  Object.assign(filters, {
    search: '',
    active: 'all',
    unit: '',
    stock_status: 'all',
    station: 'all',
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
    exportLoading,
    stats,
    dialog,
    deleteDialog,
    exportDialog,
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
    exportToExcel,
    openExportDialog,
    closeExportDialog,
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
    clearFilters,
  }
}
