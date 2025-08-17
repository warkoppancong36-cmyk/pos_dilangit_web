// Variants Composable - State Management
import type { Variant, VariantAttributes, VariantBulkCreateData, VariantCreateData, VariantFilters, VariantFormData, VariantStats, VariantUpdateData } from '@/utils/api/VariantsApi'
import { VariantsApi } from '@/utils/api/VariantsApi'
import { computed, reactive, ref } from 'vue'

export const useVariants = () => {
  // State
  const variantsList = ref<Variant[]>([])
  const loading = ref(false)
  const saveLoading = ref(false)
  const deleteLoading = ref(false)
  const bulkCreateLoading = ref(false)
  const stats = ref<VariantStats>({
    total_variants: 0,
    active_variants: 0,
    inactive_variants: 0,
    low_stock_variants: 0,
    total_products_with_variants: 0,
    average_price: 0,
    highest_price: 0,
    lowest_price: 0
  })

  // Dialog states
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const bulkCreateDialog = ref(false)
  const editMode = ref(false)
  const selectedVariant = ref<Variant | null>(null)
  const selectedVariants = ref<number[]>([])

  // Pagination
  const currentPage = ref(1)
  const totalItems = ref(0)
  const itemsPerPage = ref(15)

  // Filters
  const filters = reactive<VariantFilters>({
    search: '',
    product_id: undefined,
    status: undefined,
    variant_filters: {},
    stock_status: undefined,
    sort_by: 'created_at',
    sort_order: 'desc'
  })

  // Variant attributes for filtering
  const variantAttributes = ref<VariantAttributes>({})

  // Messages
  const errorMessage = ref('')
  const successMessage = ref('')
  const modalErrorMessage = ref('')

  // Form data for single variant
  const formData = reactive<VariantFormData>({
    id_product: 0,
    name: '',
    variant_values: {},
    price: 0,
    cost_price: 0,
    sku: '',
    barcode: '',
    image: '',
    active: true,
    reorder_level: 10,
    max_stock_level: 100
  })

  // Bulk create form data
  const bulkCreateData = reactive<VariantBulkCreateData>({
    id_product: 0,
    base_price: 0,
    base_cost_price: 0,
    attributes: [],
    price_adjustments: {}
  })

  // Validation rules
  const nameRules = [
    (v: string) => !!v || 'Nama variant wajib diisi',
    (v: string) => v.length >= 2 || 'Nama variant minimal 2 karakter',
    (v: string) => v.length <= 255 || 'Nama variant maksimal 255 karakter'
  ]

  const priceRules = [
    (v: number) => !!v || 'Harga wajib diisi',
    (v: number) => v > 0 || 'Harga harus lebih dari 0',
    (v: number) => v <= 99999999.99 || 'Harga terlalu besar'
  ]

  const skuRules = [
    (v: string) => {
      if (!v) return true // SKU is optional
      const validation = VariantsApi.validateSku(v)
      return validation.valid || validation.error || 'SKU tidak valid'
    }
  ]

  const barcodeRules = [
    (v: string) => {
      if (!v) return true // Barcode is optional
      const validation = VariantsApi.validateBarcode(v)
      return validation.valid || validation.error || 'Barcode tidak valid'
    }
  ]

  // Computed properties
  const canCreateEdit = computed(() => true) // TODO: Add role-based permissions
  const hasSelectedVariants = computed(() => selectedVariants.value.length > 0)
  const filteredVariants = computed(() => {
    if (!filters.search) return variantsList.value
    
    return variantsList.value.filter(variant => 
      variant.name.toLowerCase().includes(filters.search!.toLowerCase()) ||
      variant.sku.toLowerCase().includes(filters.search!.toLowerCase()) ||
      variant.product?.name.toLowerCase().includes(filters.search!.toLowerCase())
    )
  })

  // Fetch methods
  const fetchVariantsList = async () => {
    try {
      loading.value = true
      errorMessage.value = ''

      const params: VariantFilters = {
        ...filters,
        page: currentPage.value,
        per_page: itemsPerPage.value
      }

      const response = await VariantsApi.getAll(params)
      
      if (response.success) {
        variantsList.value = response.data || []
        totalItems.value = response.pagination?.total || 0
      } else {
        throw new Error(response.message)
      }
    } catch (error: any) {
      console.error('Error fetching variants:', error)
      errorMessage.value = error.message || 'Gagal memuat data variant'
    } finally {
      loading.value = false
    }
  }

  const fetchStats = async () => {
    try {
      const response = await VariantsApi.getStats()
      if (response.success) {
        stats.value = response.data || stats.value
      }
    } catch (error: any) {
      console.error('Error fetching stats:', error)
    }
  }

  const fetchVariantAttributes = async () => {
    try {
      const response = await VariantsApi.getAttributes()
      if (response.success) {
        variantAttributes.value = response.data || {}
      }
    } catch (error: any) {
      console.error('Error fetching variant attributes:', error)
    }
  }

  const fetchVariantsByProduct = async (productId: number) => {
    try {
      loading.value = true
      const response = await VariantsApi.getByProduct(productId)
      
      if (response.success) {
        variantsList.value = response.data || []
      } else {
        throw new Error(response.message)
      }
    } catch (error: any) {
      console.error('Error fetching variants by product:', error)
      errorMessage.value = error.message || 'Gagal memuat data variant'
    } finally {
      loading.value = false
    }
  }

  // CRUD operations
  const saveVariant = async (variantData: VariantCreateData | VariantUpdateData) => {
    try {
      saveLoading.value = true
      modalErrorMessage.value = ''

      let response
      if (editMode.value && selectedVariant.value) {
        response = await VariantsApi.update(selectedVariant.value.id_variant, variantData as VariantUpdateData)
      } else {
        response = await VariantsApi.create(variantData as VariantCreateData)
      }

      if (response.success) {
        successMessage.value = response.message || 'Variant berhasil disimpan'
        closeDialog()
        await fetchVariantsList()
        await fetchStats()
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      console.error('Error saving variant:', error)
      modalErrorMessage.value = error.errors || error.message
    } finally {
      saveLoading.value = false
    }
  }

  const bulkCreateVariants = async (data: VariantBulkCreateData) => {
    try {
      bulkCreateLoading.value = true
      modalErrorMessage.value = ''

      const response = await VariantsApi.bulkCreate(data)

      if (response.success) {
        successMessage.value = response.message || 'Variants berhasil dibuat'
        closeBulkCreateDialog()
        await fetchVariantsList()
        await fetchStats()
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      console.error('Error bulk creating variants:', error)
      modalErrorMessage.value = error.errors || error.message
    } finally {
      bulkCreateLoading.value = false
    }
  }

  const deleteVariant = async () => {
    if (!selectedVariant.value) return

    try {
      deleteLoading.value = true
      
      const response = await VariantsApi.delete(selectedVariant.value.id_variant)
      
      if (response.success) {
        successMessage.value = response.message
        deleteDialog.value = false
        await fetchVariantsList()
        await fetchStats()
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      console.error('Error deleting variant:', error)
      errorMessage.value = error.message || 'Gagal menghapus variant'
    } finally {
      deleteLoading.value = false
    }
  }

  // Dialog management
  const openCreateDialog = (productId?: number) => {
    editMode.value = false
    selectedVariant.value = null
    
    // Reset form
    Object.assign(formData, {
      id_product: productId || 0,
      name: '',
      variant_values: {},
      price: 0,
      cost_price: 0,
      sku: '',
      barcode: '',
      image: '',
      active: true,
      reorder_level: 10,
      max_stock_level: 100
    })
    
    dialog.value = true
  }

  const openEditDialog = (variant: Variant) => {
    editMode.value = true
    selectedVariant.value = variant
    
    // Populate form
    Object.assign(formData, {
      id_product: variant.id_product,
      name: variant.name,
      variant_values: { ...variant.variant_values },
      price: variant.price,
      cost_price: variant.cost_price || 0,
      sku: variant.sku,
      barcode: variant.barcode || '',
      image: variant.image || '',
      active: variant.active,
      reorder_level: variant.stock_info.reorder_level,
      max_stock_level: 100 // TODO: Get from inventory
    })
    
    dialog.value = true
  }

  const openDeleteDialog = (variant: Variant) => {
    selectedVariant.value = variant
    deleteDialog.value = true
  }

  const openBulkCreateDialog = (productId: number) => {
    Object.assign(bulkCreateData, {
      id_product: productId,
      base_price: 0,
      base_cost_price: 0,
      attributes: [
        { name: 'size', values: ['Small', 'Medium', 'Large'] },
        { name: 'temperature', values: ['Hot', 'Ice'] }
      ],
      price_adjustments: {}
    })
    bulkCreateDialog.value = true
  }

  const closeDialog = () => {
    dialog.value = false
    modalErrorMessage.value = ''
  }

  const closeDeleteDialog = () => {
    deleteDialog.value = false
    selectedVariant.value = null
  }

  const closeBulkCreateDialog = () => {
    bulkCreateDialog.value = false
    modalErrorMessage.value = ''
  }

  const clearModalError = () => {
    modalErrorMessage.value = ''
  }

  // Pagination
  const onPageChange = (page: number) => {
    currentPage.value = page
    fetchVariantsList()
  }

  // Filters
  const handleFiltersUpdate = (newFilters: Partial<VariantFilters>) => {
    Object.assign(filters, newFilters)
    currentPage.value = 1
    fetchVariantsList()
  }

  const clearFilters = () => {
    Object.assign(filters, {
      search: '',
      product_id: undefined,
      status: undefined,
      variant_filters: {},
      stock_status: undefined,
      sort_by: 'created_at',
      sort_order: 'desc'
    })
    currentPage.value = 1
    fetchVariantsList()
  }

  // Utility methods
  const formatPrice = (price: number): string => {
    return VariantsApi.formatPrice(price)
  }

  const calculateProfitMargin = (price: number, costPrice: number): number | null => {
    return VariantsApi.calculateProfitMargin(price, costPrice)
  }

  const generateVariantName = (productName: string, variantValues: Record<string, string>): string => {
    return VariantsApi.generateVariantName(productName, variantValues)
  }

  const addVariantAttribute = () => {
    bulkCreateData.attributes.push({ name: '', values: [] })
  }

  const removeVariantAttribute = (index: number) => {
    bulkCreateData.attributes.splice(index, 1)
  }

  const addAttributeValue = (attributeIndex: number, value: string) => {
    if (value.trim() && !bulkCreateData.attributes[attributeIndex].values.includes(value.trim())) {
      bulkCreateData.attributes[attributeIndex].values.push(value.trim())
    }
  }

  const removeAttributeValue = (attributeIndex: number, valueIndex: number) => {
    bulkCreateData.attributes[attributeIndex].values.splice(valueIndex, 1)
  }

  return {
    // State
    variantsList,
    loading,
    saveLoading,
    deleteLoading,
    bulkCreateLoading,
    stats,
    dialog,
    deleteDialog,
    bulkCreateDialog,
    editMode,
    selectedVariant,
    selectedVariants,
    currentPage,
    totalItems,
    itemsPerPage,
    filters,
    variantAttributes,
    errorMessage,
    successMessage,
    modalErrorMessage,
    formData,
    bulkCreateData,

    // Computed
    canCreateEdit,
    hasSelectedVariants,
    filteredVariants,

    // Validation rules
    nameRules,
    priceRules,
    skuRules,
    barcodeRules,

    // Methods
    fetchVariantsList,
    fetchStats,
    fetchVariantAttributes,
    fetchVariantsByProduct,
    saveVariant,
    bulkCreateVariants,
    deleteVariant,
    openCreateDialog,
    openEditDialog,
    openDeleteDialog,
    openBulkCreateDialog,
    closeDialog,
    closeDeleteDialog,
    closeBulkCreateDialog,
    clearModalError,
    onPageChange,
    handleFiltersUpdate,
    clearFilters,
    formatPrice,
    calculateProfitMargin,
    generateVariantName,
    addVariantAttribute,
    removeVariantAttribute,
    addAttributeValue,
    removeAttributeValue
  }
}
