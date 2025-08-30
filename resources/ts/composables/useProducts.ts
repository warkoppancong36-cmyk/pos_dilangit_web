import type { Category } from '@/composables/useCategories'
import { CategoriesApi } from '@/utils/api/CategoriesApi'
import { ProductsApi } from '@/utils/api/ProductsApi'
import { computed, reactive, ref } from 'vue'

export type { Category }

export type ProductStatus = 'draft' | 'published' | 'archived'
export type StockStatus = 'in_stock' | 'low_stock' | 'out_of_stock'

export interface ProductDimensions {
  length?: number
  width?: number
  height?: number
}

export interface ProductFormData {
  id?: number
  name: string
  sku?: string
  description?: string
  category_id: number
  brand?: string
  price: number
  cost?: number
  min_stock?: number
  unit?: string
  barcode?: string
  weight?: number
  dimensions?: ProductDimensions
  status: ProductStatus
  active: boolean
  featured: boolean
  available_in_kitchen: boolean
  available_in_bar: boolean
  meta_title?: string
  meta_description?: string
  tags?: string[]
  image?: string
  image_url?: string
  created_by?: number
  updated_by?: number
  deleted_by?: number
  created_at?: string
  updated_at?: string
  deleted_at?: string
  category?: Category
}

export interface Product extends ProductFormData {
  id: number
  id_product: number
  category?: Category
  created_by?: number
  updated_by?: number
  deleted_by?: number
  creator?: { id: number; name: string; email: string }
  updater?: { id: number; name: string; email: string }
  deleter?: { id: number; name: string; email: string }
  created_at: string
  updated_at: string
  image_url?: string
  stock_status?: string
  formatted_price?: string
  formatted_cost?: string
  profit_margin?: number
  stock_value?: number
  // HPP (Harga Pokok Produksi) fields from backend
  hpp?: number
  hpp_breakdown?: {
    items: Array<{
      item_name: string
      item_code: string
      quantity_needed: number
      unit: string
      cost_per_unit: number
      total_cost: number
      is_critical: boolean
      notes?: string
    }>
    total_hpp: number
    method: string
    calculated_at: string
  }
  profit_percentage?: number
}

export interface ProductFilters {
  search?: string
  category_id?: number | null
  status?: ProductStatus
  min_price?: number | null
  max_price?: number | null
  featured?: boolean | string | null
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  active?: boolean | string | null
  stock_status?: StockStatus | 'all' | null
  available_in_kitchen?: boolean | string | null
  available_in_bar?: boolean | string | null
  station?: 'kitchen' | 'bar' | 'both' | null
  sortBy?: string
  sortOrder?: string
  per_page?: number
  page?: number
}

export interface ProductStats {
  total_products: number
  published_products: number
  draft_products: number
  archived_products: number
  active_products: number
  inactive_products: number
  featured_products: number
  low_stock_products: number
  out_of_stock_products: number
  total_stock_value: number
  average_price: number
  total_categories: number
  average_margin: number
  highest_margin: number
  lowest_margin: number
  profitable_products: number
}

export const useProducts = () => {
  const productsList = ref<Product[]>([])
  const categories = ref<Category[]>([])
  const loading = ref(false)
  const saveLoading = ref(false)
  const deleteLoading = ref(false)
  const toggleLoading = ref<Record<number, boolean>>({})
  const stats = ref<ProductStats>({
    total_products: 0,
    published_products: 0,
    draft_products: 0,
    archived_products: 0,
    active_products: 0,
    inactive_products: 0,
    featured_products: 0,
    low_stock_products: 0,
    out_of_stock_products: 0,
    total_stock_value: 0,
    average_price: 0,
    total_categories: 0,
    average_margin: 0,
    highest_margin: 0,
    lowest_margin: 0,
    profitable_products: 0
  })

  const dialog = ref(false)
  const deleteDialog = ref(false)
  const editMode = ref(false)
  const selectedProduct = ref<Product | null>(null)
  const selectedProducts = ref<number[]>([])

  const currentPage = ref(1)
  const totalItems = ref(0)
  const itemsPerPage = ref(15)

  const filters = reactive<ProductFilters>({
    search: '',
    active: 'all',
    category_id: null,
    stock_status: 'all',
    featured: 'all',
    sortBy: 'created_at',
    sortOrder: 'desc'
  })

  const errorMessage = ref('')
  const successMessage = ref('')
  const modalErrorMessage = ref('')

  const formData = reactive<ProductFormData>({
    name: '',
    description: '',
    price: 0,
    cost: 0,
    sku: '',
    barcode: '',
    min_stock: 0,
    weight: 0,
    dimensions: {
      length: undefined,
      width: undefined,
      height: undefined
    },
    category_id: 0,
    brand: '',
    unit: 'pcs',
    status: 'published',
    active: true,
    featured: false,
    available_in_kitchen: true,
    available_in_bar: true,
    tags: [],
    meta_title: '',
    meta_description: ''
  })

  const selectedImage = ref<File | null>(null)
  const imagePreview = ref<string>('')

  const nameRules = [
    (v: string) => !!v || 'Nama produk wajib diisi',
    (v: string) => v.length >= 2 || 'Nama produk minimal 2 karakter',
    (v: string) => v.length <= 255 || 'Nama produk maksimal 255 karakter'
  ]

  const categoryRules = [
    (v: string | number) => !!v || 'Kategori wajib dipilih'
  ]

  const skuRules = [
    (v: string) => {
      if (!v) return true
      const validation = ProductsApi.validateSKU(v)
      return validation.valid || validation.error
    }
  ]

  const canCreateEdit = computed(() => true)
  const canDelete = computed(() => true)

  const totalActiveProducts = computed(() => stats.value.active_products)
  const totalInactiveProducts = computed(() => stats.value.inactive_products)
  const totalFeaturedProducts = computed(() => stats.value.featured_products)
  const totalLowStockProducts = computed(() => stats.value.low_stock_products)
  const totalOutOfStockProducts = computed(() => stats.value.out_of_stock_products)
  const averagePrice = computed(() => stats.value.average_price)
  const totalStockValue = computed(() => stats.value.total_stock_value)

  const hasSelectedProducts = computed(() => selectedProducts.value.length > 0)

  const filteredProductsCount = computed(() => productsList.value.length)

  const fetchProductsList = async () => {
    try {
      loading.value = true
      errorMessage.value = ''

      const params = {
        ...filters,
        page: currentPage.value,
        per_page: itemsPerPage.value
      }

      // Clean up parameters but preserve search even if empty
      Object.keys(params).forEach(key => {
        const value = params[key as keyof typeof params]
        // Don't delete search parameter - let backend handle empty search
        if (key === 'search') {
          return
        }
        // Delete other empty/null parameters
        if (value === '' || value === 'all' || value === null || value === undefined) {
          delete params[key as keyof typeof params]
        }
      })

      console.log('Fetching products with params:', params)

      const response = await ProductsApi.getAll(params)
      if (response.success) {
        productsList.value = response.data as any
        if (response.pagination) {
          totalItems.value = response.pagination.total
          currentPage.value = response.pagination.current_page
        }
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      errorMessage.value = error.message || 'Gagal mengambil data produk'
      productsList.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchCategories = async () => {
    try {
      const response = await CategoriesApi.getCategories({
        page: 1,
        per_page: 100,
        status: 'active'
      })
      if (response.success) {
        categories.value = response.data
      }
    } catch (error) { }
  }

  const fetchStats = async () => {
    try {
      const response = await ProductsApi.getStats()
      if (response.success) {
        stats.value = response.data
      }
    } catch (error: any) { }
  }

  const saveProduct = async () => {
    try {
      saveLoading.value = true
      modalErrorMessage.value = ''

      const productData = { ...formData }
      if (productData.price) productData.price = Number(productData.price)
      if (productData.cost) productData.cost = Number(productData.cost)
      if (productData.min_stock) productData.min_stock = Number(productData.min_stock)
      if (productData.weight) productData.weight = Number(productData.weight)
      if (productData.category_id) productData.category_id = Number(productData.category_id)

      let response
      if (editMode.value && selectedProduct.value) {
        response = await ProductsApi.update(selectedProduct.value.id_product, productData, selectedImage.value || undefined)
      } else {
        response = await ProductsApi.create(productData, selectedImage.value || undefined)
      }

      if (response.success) {
        successMessage.value = response.message || 'Product saved successfully'
        closeDialog()
        await fetchProductsList()
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      modalErrorMessage.value = error.errors || error.message
    } finally {
      saveLoading.value = false
    }
  }

  const deleteProduct = async () => {
    if (!selectedProduct.value) return

    try {
      deleteLoading.value = true
      const response = await ProductsApi.delete(selectedProduct.value.id_product)
      if (response.success) {
        successMessage.value = response.message
        deleteDialog.value = false
        await fetchProductsList()
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      errorMessage.value = error.message || 'Gagal menghapus produk'
    } finally {
      deleteLoading.value = false
    }
  }

  const bulkDeleteProducts = async () => {
    if (selectedProducts.value.length === 0) return

    try {
      deleteLoading.value = true
      const response = await ProductsApi.bulkDelete(selectedProducts.value)
      if (response.success) {
        successMessage.value = response.message
        selectedProducts.value = []
        await fetchProductsList()
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      errorMessage.value = error.message || 'Gagal menghapus produk'
    } finally {
      deleteLoading.value = false
    }
  }

  const toggleActiveStatus = async (product: Product) => {
    try {
      toggleLoading.value[product.id_product] = true
      const response = await ProductsApi.toggleActive(product.id_product)
      if (response.success) {
        const index = productsList.value.findIndex(p => p.id_product === product.id_product)
        if (index !== -1) {
          productsList.value[index] = response.data
        }
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      errorMessage.value = error.message || 'Gagal mengubah status produk'
    } finally {
      toggleLoading.value[product.id_product] = false
    }
  }

  const toggleFeaturedStatus = async (product: Product) => {
    try {
      toggleLoading.value[product.id_product] = true
      const response = await ProductsApi.toggleFeatured(product.id_product)
      if (response.success) {
        const index = productsList.value.findIndex(p => p.id_product === product.id_product)
        if (index !== -1) {
          productsList.value[index] = response.data as any
        }
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      errorMessage.value = error.message || 'Gagal mengubah status produk unggulan'
    } finally {
      toggleLoading.value[product.id_product] = false
    }
  }

  const openCreateDialog = () => {
    editMode.value = false
    selectedProduct.value = null
    resetFormData()
    dialog.value = true
  }

  const openEditDialog = (product: Product) => {
    editMode.value = true
    selectedProduct.value = product
    populateFormData(product)
    dialog.value = true
  }

  const openDeleteDialog = (product: Product) => {
    selectedProduct.value = product
    deleteDialog.value = true
  }

  const closeDialog = () => {
    dialog.value = false
    modalErrorMessage.value = ''
    resetFormData()
  }

  const resetFormData = () => {
    Object.assign(formData, {
      name: '',
      description: '',
      price: '',
      cost: '',
      sku: '',
      barcode: '',
      stock: '',
      min_stock: '',
      weight: '',
      dimensions: {
        length: undefined,
        width: undefined,
        height: undefined
      },
      category_id: '',
      brand: '',
      unit: 'pcs',
      status: 'published',
      active: true,
      featured: false,
      tags: [],
      meta_title: '',
      meta_description: ''
    })
    selectedImage.value = null
    imagePreview.value = ''
  }

  const populateFormData = (product: Product) => {
    Object.assign(formData, {
      name: product.name,
      description: product.description || '',
      price: product.price,
      cost: product.cost || '',
      sku: product.sku || '',
      barcode: product.barcode || '',
      min_stock: product.min_stock || '',
      weight: product.weight || '',
      dimensions: product.dimensions || { length: undefined, width: undefined, height: undefined },
      category_id: product.category_id,
      brand: product.brand || '',
      unit: product.unit || 'pcs',
      status: product.status,
      active: product.active,
      featured: product.featured,
      available_in_kitchen: product.available_in_kitchen,
      available_in_bar: product.available_in_bar,
      tags: product.tags || [],
      meta_title: product.meta_title || '',
      meta_description: product.meta_description || ''
    })
    selectedImage.value = null
    imagePreview.value = product.image_url || ''
  }

  const handleImageUpload = (files: File[]) => {
    if (files.length === 0) return
    const file = files[0]
    const validation = ProductsApi.validateImage(file)
    if (!validation.valid) {
      modalErrorMessage.value = validation.error || 'File tidak valid'
      return
    }
    selectedImage.value = file
    imagePreview.value = URL.createObjectURL(file)
    modalErrorMessage.value = ''
  }

  const removeImage = () => {
    selectedImage.value = null
    imagePreview.value = ''
  }

  const generateSKU = () => {
    if (formData.name) {
      formData.sku = ProductsApi.generateSKU(formData.name)
    }
  }

  const clearModalError = () => {
    modalErrorMessage.value = ''
  }

  const onPageChange = (page: number) => {
    currentPage.value = page
    fetchProductsList()
  }

  const updateOptions = (options: any) => {
    console.log('updateOptions called with:', options)
    currentPage.value = options.page
    itemsPerPage.value = options.itemsPerPage
    fetchProductsList()
  }

  const onSearch = () => {
    currentPage.value = 1
    fetchProductsList()
  }

  const onFilterChange = () => {
    currentPage.value = 1
    fetchProductsList()
  }

  const handleFiltersUpdate = (newFilters: ProductFilters) => {
    Object.assign(filters, newFilters)
    onFilterChange()
  }

  const onSortChange = () => {
    currentPage.value = 1
    fetchProductsList()
  }

  const resetFilters = () => {
    Object.assign(filters, {
      search: '',
      active: 'all',
      category_id: null,
      stock_status: 'all',
      featured: 'all',
      sortBy: 'created_at',
      sortOrder: 'desc'
    })
    currentPage.value = 1
    fetchProductsList()
  }

  const toggleProductSelection = (productId: number) => {
    const index = selectedProducts.value.indexOf(productId)
    if (index > -1) {
      selectedProducts.value.splice(index, 1)
    } else {
      selectedProducts.value.push(productId)
    }
  }

  const selectAllProducts = () => {
    if (selectedProducts.value.length === productsList.value.length) {
      selectedProducts.value = []
    } else {
      selectedProducts.value = productsList.value.map(p => p.id_product)
    }
  }

  return {
    productsList,
    categories,
    loading,
    saveLoading,
    deleteLoading,
    toggleLoading,
    stats,
    dialog,
    deleteDialog,
    editMode,
    selectedProduct,
    selectedProducts,
    currentPage,
    totalItems,
    itemsPerPage,
    filters,
    errorMessage,
    successMessage,
    modalErrorMessage,
    formData,
    selectedImage,
    imagePreview,
    nameRules,
    categoryRules,
    skuRules,
    canCreateEdit,
    canDelete,
    totalActiveProducts,
    totalInactiveProducts,
    totalFeaturedProducts,
    totalLowStockProducts,
    totalOutOfStockProducts,
    averagePrice,
    totalStockValue,
    hasSelectedProducts,
    filteredProductsCount,
    fetchProductsList,
    fetchCategories,
    fetchStats,
    saveProduct,
    deleteProduct,
    bulkDeleteProducts,
    toggleActiveStatus,
    toggleFeaturedStatus,
    openCreateDialog,
    openEditDialog,
    openDeleteDialog,
    closeDialog,
    handleImageUpload,
    removeImage,
    generateSKU,
    clearModalError,
    onPageChange,
    updateOptions,
    onSearch,
    onFilterChange,
    handleFiltersUpdate,
    onSortChange,
    resetFilters,
    toggleProductSelection,
    selectAllProducts
  }
}
