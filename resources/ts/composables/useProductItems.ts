// Product Items Composable - State Management
import { ItemsApi } from '@/utils/api/ItemsApi'
import { ProductItemsApi } from '@/utils/api/ProductItemsApi'
import { ProductsApi } from '@/utils/api/ProductsApi'
import { computed, reactive, ref } from 'vue'

export interface ProductItem {
  id_product_item: number
  product_id: number
  item_id: number
  quantity_needed: number
  unit: string
  cost_per_unit?: number
  is_critical: boolean
  notes?: string
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
  total_cost_per_product?: number
  formatted_cost_per_unit?: string
  formatted_total_cost?: string
  product?: {
    id_product: number
    name: string
    slug: string
    description?: string
    sku: string
    price: string
    cost: string
    image?: string
    image_url?: string
    formatted_price?: string
    formatted_cost?: string
    active: boolean
    featured: boolean
  }
  item?: {
    id_item: number
    item_code: string
    name: string
    description?: string
    unit: string
    cost_per_unit: string
    formatted_cost_per_unit?: string
    stock_status: string
    is_low_stock: boolean
    stock_percentage: number
    active: boolean
    inventory?: {
      id_inventory: number
      current_stock: number
      available_stock: number
      reorder_level: number
      max_stock_level: number
      is_low_stock: boolean
      stock_status: string
    }
  }
}

export interface ProductItemFormData {
  id_product_item?: number
  product_id: number
  item_id: number
  quantity_needed: number
  unit: string
  is_critical?: boolean
  notes?: string
}

export interface ProductItemFilters {
  search?: string
  product_id?: number
  item_id?: number
  critical_only?: boolean
  stock_status?: string
  sort_by?: string
}

export interface ProductionCapacity {
  can_produce: number
  limiting_items: string[]
  details: Array<{
    item_id: number
    item_name: string
    available_stock: number
    needed_per_product: number
    can_produce: number
    is_limiting: boolean
  }>
}

const productItemsList = ref<ProductItem[]>([])
const products = ref<any[]>([])
const items = ref<any[]>([])
const loading = ref(false)
const saveLoading = ref(false)
const deleteLoading = ref(false)

const dialog = ref(false)
const deleteDialog = ref(false)
const capacityDialog = ref(false)
const editMode = ref(false)
const selectedProductItem = ref<ProductItem | null>(null)
const selectedProduct = ref<any | null>(null)
const productionCapacity = ref<ProductionCapacity | null>(null)

const currentPage = ref(1)
const totalItems = ref(0)
const itemsPerPage = ref(15)

const filters = reactive<ProductItemFilters>({
  search: '',
  product_id: undefined,
  item_id: undefined,
  critical_only: false,
  stock_status: null,
  sort_by: null,
})

const errorMessage = ref('')
const successMessage = ref('')
const modalErrorMessage = ref('')

const formData = reactive<ProductItemFormData>({
  product_id: 0,
  item_id: 0,
  quantity_needed: 0,
  unit: '',
  is_critical: false,
  notes: '',
})

const canCreateEdit = computed(() => {
  return true
})

const productOptions = computed(() => [
  { title: 'Pilih Produk', value: 0 },
  ...products.value.map(product => ({
    title: product.name,
    value: product.id_product || product.id,
  })),
])

const itemOptions = computed(() => [
  { title: 'Pilih Item', value: 0 },
  ...items.value.map(item => ({
    title: `${item.name} (${item.unit})`,
    value: item.id_item || item.id,
  })),
])

const fetchProductItemsList = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const params = {
      page: currentPage.value,
      per_page: itemsPerPage.value,
      ...filters,
    }

    Object.keys(params).forEach(key => {
      const paramKey = key as keyof typeof params
      const value = params[paramKey]
      if (value === undefined || value === 0 || (typeof value === 'string' && value === ''))
        delete (params as any)[key]
    })

    const response = await ProductItemsApi.getAll(params)

    if (response.success && response.data) {
      if (Array.isArray(response.data)) {
        productItemsList.value = response.data
        totalItems.value = response.data.length
      }
      else {
        productItemsList.value = response.data.data || []
        totalItems.value = response.data.total || 0
        currentPage.value = response.data.current_page || 1
      }
    }
  }
  catch (error: any) {
    errorMessage.value = error.message || 'Failed to fetch product items'
  }
  finally {
    loading.value = false
  }
}

const fetchProducts = async () => {
  try {
    const response = await ProductsApi.getAll({})
    if (response.success && response.data) {
      products.value = Array.isArray(response.data) ? response.data : []
    }
    else {
      products.value = []
    }
  }
  catch (error) {
    products.value = []
  }
}

const fetchItems = async () => {
  try {
    const response = await ItemsApi.getAll({ per_page: 'all' })
    if (response.success && response.data && response.data.data) {
      items.value = Array.isArray(response.data.data) ? response.data.data : []
    }
    else {
      items.value = []
    }
  }
  catch (error) {
    items.value = []
  }
}

const saveProductItem = async () => {
  if (!validateForm())
    return

  saveLoading.value = true
  modalErrorMessage.value = ''

  try {
    let response

    if (editMode.value && selectedProductItem.value) {
      // For existing items, use regular update
      response = await ProductItemsApi.update(selectedProductItem.value.id_product_item, formData)
    } else {
      // For new items, use regular create
      response = await ProductItemsApi.create(formData)
    }

    if (response.success) {
      successMessage.value = editMode.value
        ? 'Hubungan produk-item berhasil diperbarui!'
        : 'Hubungan produk-item berhasil ditambahkan!'
      closeDialog()
      await fetchProductItemsList()
    }
    else {
      modalErrorMessage.value = response.message || 'Terjadi kesalahan saat menyimpan'
    }
  }
  catch (error: any) {
    modalErrorMessage.value = error.message || 'Terjadi kesalahan saat menyimpan'
  }
  finally {
    saveLoading.value = false
  }
}

const deleteProductItem = async () => {
  if (!selectedProductItem.value)
    return

  deleteLoading.value = true

  try {
    const response = await ProductItemsApi.delete(selectedProductItem.value.id_product_item)

    if (response.success) {
      successMessage.value = 'Hubungan produk-item berhasil dihapus!'
      closeDeleteDialog()
      await fetchProductItemsList()
    }
    else {
      errorMessage.value = response.message || 'Gagal menghapus hubungan produk-item'
    }
  }
  catch (error: any) {
    errorMessage.value = error.message || 'Terjadi kesalahan saat menghapus'
  }
  finally {
    deleteLoading.value = false
  }
}

const getProductionCapacity = async (productId: number) => {
  loading.value = true

  try {
    const response = await ProductItemsApi.getProductionCapacity(productId)

    if (response.success) {
      productionCapacity.value = response.data
      capacityDialog.value = true
    }
    else {
      errorMessage.value = response.message || 'Gagal menghitung kapasitas produksi'
    }
  }
  catch (error: any) {
    errorMessage.value = error.message || 'Terjadi kesalahan saat menghitung kapasitas produksi'
  }
  finally {
    loading.value = false
  }
}

const validateForm = (): boolean => {
  if (formData.product_id <= 0) {
    modalErrorMessage.value = 'Produk harus dipilih'
    return false
  }
  if (formData.item_id <= 0) {
    modalErrorMessage.value = 'Item harus dipilih'
    return false
  }
  if (formData.quantity_needed <= 0) {
    modalErrorMessage.value = 'Jumlah yang dibutuhkan harus lebih besar dari 0'
    return false
  }
  if (!formData.unit.trim()) {
    modalErrorMessage.value = 'Satuan harus diisi'
    return false
  }
  return true
}

const openCreateDialog = () => {
  editMode.value = false
  selectedProductItem.value = null
  resetForm()
  dialog.value = true
}

const openEditDialog = (productItem: ProductItem) => {
  editMode.value = true
  selectedProductItem.value = productItem
  fillFormData(productItem)
  dialog.value = true
}

const openDeleteDialog = (productItem: ProductItem) => {
  selectedProductItem.value = productItem
  deleteDialog.value = true
}

const openCapacityDialog = (productOrId: any) => {
  let productId: number
  let product: any

  if (typeof productOrId === 'number') {
    productId = productOrId
    product = products.value.find(p => (p.id_product || p.id) === productId)
    if (!product) {
      errorMessage.value = 'Product tidak ditemukan'
      return
    }
  }
  else if (productOrId && typeof productOrId === 'object') {
    product = productOrId
    productId = product.id_product || product.id
  }
  else {
    errorMessage.value = 'Parameter tidak valid'
    return
  }

  if (!productId) {
    errorMessage.value = 'Product ID tidak ditemukan'
    return
  }

  selectedProduct.value = product
  getProductionCapacity(productId)
}

const closeDialog = () => {
  dialog.value = false
  clearModalError()
  resetForm()
}

const closeDeleteDialog = () => {
  deleteDialog.value = false
  selectedProductItem.value = null
}

const closeCapacityDialog = () => {
  capacityDialog.value = false
  selectedProduct.value = null
  productionCapacity.value = null
}

const resetForm = () => {
  Object.assign(formData, {
    product_id: 0,
    item_id: 0,
    quantity_needed: 0,
    unit: '',
    is_critical: false,
    notes: '',
  })
}

const fillFormData = (productItem: ProductItem) => {
  Object.assign(formData, {
    product_id: productItem.product_id,
    item_id: productItem.item_id,
    quantity_needed: productItem.quantity_needed,
    unit: productItem.unit,
    is_critical: productItem.is_critical,
    notes: productItem.notes || '',
  })
}

const clearModalError = () => {
  modalErrorMessage.value = ''
}

const onItemChange = (itemId: number) => {
  const selectedItem = items.value.find(item => (item.id_item || item.id) === itemId)
  if (selectedItem)
    formData.unit = selectedItem.unit
}

const onPageChange = (page: number) => {
  currentPage.value = page
  fetchProductItemsList()
}

const onItemsPerPageChange = (perPage: number) => {
  itemsPerPage.value = perPage
  currentPage.value = 1
  fetchProductItemsList()
}

const handleFiltersUpdate = () => {
  currentPage.value = 1
  fetchProductItemsList()
}

export const useProductItems = () => {
  return {
    productItemsList,
    products,
    items,
    loading,
    saveLoading,
    deleteLoading,
    dialog,
    deleteDialog,
    capacityDialog,
    editMode,
    selectedProductItem,
    selectedProduct,
    productionCapacity,
    currentPage,
    totalItems,
    itemsPerPage,
    filters,
    errorMessage,
    successMessage,
    modalErrorMessage,
    formData,
    canCreateEdit,
    productOptions,
    itemOptions,
    fetchProductItemsList,
    fetchProductItemsForComposition: async (params?: { page?: number; per_page?: number; critical_only?: boolean }) => {
      loading.value = true
      errorMessage.value = ''
      try {
        const queryParams = {
          page: params?.page || 1,
          per_page: params?.per_page || 15,
          critical_only: params?.critical_only || false
        }
        const response = await ProductItemsApi.getAll(queryParams)
        if (response.success) {
          const apiData = response.data?.data || response.data || []
          productItemsList.value = apiData
          currentPage.value = response.data?.current_page || 1
          totalItems.value = response.data?.total || 0
          itemsPerPage.value = response.data?.per_page || 15
          filters.critical_only = queryParams.critical_only
          if (products.value.length === 0) {
            await fetchProducts()
          }
          if (items.value.length === 0) {
            await fetchItems()
          }
        } else {
          productItemsList.value = []
        }
      } catch (error: any) {
        errorMessage.value = error.message || 'Gagal memuat data komposisi produk'
        productItemsList.value = []
      } finally {
        loading.value = false
      }
    },
    fetchProducts,
    fetchItems,
    saveProductItem,
    deleteProductItem,
    getProductionCapacity,
    openCreateDialog,
    openEditDialog,
    openDeleteDialog,
    openCapacityDialog,
    closeDialog,
    closeDeleteDialog,
    closeCapacityDialog,
    clearModalError,
    onItemChange,
    onPageChange,
    onItemsPerPageChange,
    handleFiltersUpdate,
  }
}
