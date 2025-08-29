// Suppliers Composable - State Management
import type { Supplier, SupplierCreateData, SupplierFilters, SupplierFormData, SupplierStats, SupplierUpdateData } from '@/utils/api/SuppliersApi'
import { SuppliersApi } from '@/utils/api/SuppliersApi'
import { computed, reactive, ref } from 'vue'

export const useSuppliers = () => {
  // State
  const suppliersList = ref<Supplier[]>([])
  const cities = ref<string[]>([])
  const provinces = ref<string[]>([])
  const loading = ref(false)
  const saveLoading = ref(false)
  const deleteLoading = ref(false)
  const toggleLoading = ref<Record<number, boolean>>({})
  const stats = ref<SupplierStats>({
    total_suppliers: 0,
    active_suppliers: 0,
    inactive_suppliers: 0,
    suppliers_with_purchases: 0,
    total_cities: 0,
    total_provinces: 0
  })

  // Dialog states
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const editMode = ref(false)
  const selectedSupplier = ref<Supplier | null>(null)
  const selectedSuppliers = ref<number[]>([])

  // Pagination
  const currentPage = ref(1)
  const totalItems = ref(0)
  const itemsPerPage = ref(15)

  // Filters
  const filters = reactive<SupplierFilters>({
    search: '',
    status: undefined,
    city: '',
    province: '',
    sort_by: 'created_at',
    sort_order: 'desc'
  })

  // Messages
  const errorMessage = ref('')
  const successMessage = ref('')
  const modalErrorMessage = ref('')

  // Form data
  const formData = reactive<SupplierFormData>({
    name: '',
    code: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    city: '',
    province: '',
    postal_code: '',
    tax_number: '',
    bank_name: '',
    bank_account: '',
    bank_account_name: '',
    status: 'active',
    notes: ''
  })

  // Validation rules
  const nameRules = [
    (v: string) => !!v || 'Nama supplier wajib diisi',
    (v: string) => v.length >= 2 || 'Nama supplier minimal 2 karakter',
    (v: string) => v.length <= 255 || 'Nama supplier maksimal 255 karakter'
  ]

  const codeRules = [
    (v: string) => {
      if (!v) return true // Code is optional, will be auto-generated
      const validation = SuppliersApi.validateCode(v)
      return validation.valid || validation.error
    }
  ]

  const emailRules = [
    (v: string) => {
      if (!v) return true // Email is optional
      const validation = SuppliersApi.validateEmail(v)
      return validation.valid || validation.error
    }
  ]

  const phoneRules = [
    (v: string) => {
      if (!v) return true // Phone is optional
      const validation = SuppliersApi.validatePhone(v)
      return validation.valid || validation.error
    }
  ]

  // Computed
  const canCreateEdit = computed(() => true) // Add role-based logic here
  const canDelete = computed(() => true) // Add role-based logic here

  const totalActiveSuppliers = computed(() => stats.value.active_suppliers)
  const totalInactiveSuppliers = computed(() => stats.value.inactive_suppliers)
  const totalSuppliersWithPurchases = computed(() => stats.value.suppliers_with_purchases)
  const totalCities = computed(() => stats.value.total_cities)
  const totalProvinces = computed(() => stats.value.total_provinces)

  const hasSelectedSuppliers = computed(() => selectedSuppliers.value.length > 0)
  const filteredSuppliersCount = computed(() => suppliersList.value.length)

  // Functions
  const fetchSuppliersList = async () => {
    try {
      loading.value = true
      errorMessage.value = ''

      const params = {
        ...filters,
        page: currentPage.value,
        per_page: itemsPerPage.value
      }

      // Remove empty filters
      Object.keys(params).forEach(key => {
        if (params[key as keyof typeof params] === '' || params[key as keyof typeof params] === 'all') {
          delete params[key as keyof typeof params]
        }
      })

      console.log('🔍 Fetching suppliers with params:', params)

      const response = await SuppliersApi.getAll(params)

      console.log('📊 API Response:', {
        success: response.success,
        dataLength: response.data?.length || 0,
        pagination: response.pagination
      })

      if (response.success) {
        suppliersList.value = response.data
        if (response.pagination) {
          totalItems.value = response.pagination.total
          currentPage.value = response.pagination.current_page
          console.log('✅ Pagination updated:', {
            total: totalItems.value,
            currentPage: currentPage.value,
            itemsPerPage: itemsPerPage.value
          })
        }
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      console.error('❌ Error fetching suppliers:', error)
      errorMessage.value = error.message || 'Gagal mengambil data supplier'
      suppliersList.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchCities = async () => {
    try {
      const response = await SuppliersApi.getCities()
      if (response.success) {
        cities.value = response.data
      }
    } catch (error) {
      console.error('Error fetching cities:', error)
    }
  }

  const fetchProvinces = async () => {
    try {
      const response = await SuppliersApi.getProvinces()
      if (response.success) {
        provinces.value = response.data
      }
    } catch (error) {
      console.error('Error fetching provinces:', error)
    }
  }

  const fetchStats = async () => {
    try {
      const response = await SuppliersApi.getStats()
      if (response.success) {
        stats.value = response.data
      }
    } catch (error: any) {
      console.error('Error fetching stats:', error)
    }
  }

  const saveSupplier = async (supplierData: SupplierCreateData | SupplierUpdateData) => {
    try {
      saveLoading.value = true
      modalErrorMessage.value = ''

      let response
      if (editMode.value && selectedSupplier.value) {
        // Use id_supplier instead of id for the primary key
        response = await SuppliersApi.update(selectedSupplier.value.id_supplier, supplierData as SupplierUpdateData)
      } else {
        response = await SuppliersApi.create(supplierData as SupplierCreateData)
      }

      if (response.success) {
        successMessage.value = response.message || 'Supplier saved successfully'
        closeDialog()
        await fetchSuppliersList()
        await fetchStats()
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      console.error('Error saving supplier:', error)
      modalErrorMessage.value = error.errors || error.message
    } finally {
      saveLoading.value = false
    }
  }

  const deleteSupplier = async () => {
    if (!selectedSupplier.value) return

    try {
      deleteLoading.value = true

      const response = await SuppliersApi.delete(selectedSupplier.value.id_supplier)

      if (response.success) {
        successMessage.value = response.message
        deleteDialog.value = false
        await fetchSuppliersList()
        await fetchStats()
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      console.error('Error deleting supplier:', error)
      errorMessage.value = error.message || 'Gagal menghapus supplier'
    } finally {
      deleteLoading.value = false
    }
  }

  const toggleActiveStatus = async (supplier: Supplier) => {
    try {
      toggleLoading.value[supplier.id_supplier] = true

      const response = await SuppliersApi.toggleActive(supplier.id_supplier)

      if (response.success) {
        // Update the supplier in the list
        const index = suppliersList.value.findIndex(s => s.id_supplier === supplier.id_supplier)
        if (index !== -1) {
          suppliersList.value[index] = response.data
        }
        await fetchStats()
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      console.error('Error toggling active status:', error)
      errorMessage.value = error.message || 'Gagal mengubah status supplier'
    } finally {
      toggleLoading.value[supplier.id] = false
    }
  }

  const openCreateDialog = () => {
    editMode.value = false
    selectedSupplier.value = null
    resetFormData()
    dialog.value = true
  }

  const openEditDialog = (supplier: Supplier) => {
    editMode.value = true
    selectedSupplier.value = supplier
    populateFormData(supplier)
    dialog.value = true
  }

  const openDeleteDialog = (supplier: Supplier) => {
    selectedSupplier.value = supplier
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
      code: '',
      contact_person: '',
      phone: '',
      email: '',
      address: '',
      city: '',
      province: '',
      postal_code: '',
      tax_number: '',
      payment_terms: null,
      active: true,
      notes: ''
    })
  }

  const populateFormData = (supplier: Supplier) => {
    Object.assign(formData, {
      name: supplier.name,
      code: supplier.code || '',
      contact_person: supplier.contact_person || '',
      phone: supplier.phone || '',
      email: supplier.email || '',
      address: supplier.address || '',
      city: supplier.city || '',
      province: supplier.province || '',
      postal_code: supplier.postal_code || '',
      tax_number: supplier.tax_number || '',
      bank_name: supplier.bank_name || '',
      bank_account: supplier.bank_account || '',
      bank_account_name: supplier.bank_account_name || '',
      status: supplier.status,
      notes: supplier.notes || ''
    })
  }

  const clearModalError = () => {
    modalErrorMessage.value = ''
  }

  const onPageChange = (page: number) => {
    console.log('🔄 Page change triggered:', { from: currentPage.value, to: page })
    currentPage.value = page
    fetchSuppliersList()
  }

  const onItemsPerPageChange = (perPage: number) => {
    console.log('📄 Items per page change triggered:', { from: itemsPerPage.value, to: perPage })
    itemsPerPage.value = perPage
    currentPage.value = 1
    fetchSuppliersList()
  }

  const onSearch = () => {
    currentPage.value = 1
    fetchSuppliersList()
  }

  const onFilterChange = () => {
    currentPage.value = 1
    fetchSuppliersList()
  }

  const handleFiltersUpdate = (newFilters: Partial<SupplierFilters>) => {
    Object.assign(filters, newFilters)
    onFilterChange()
  }

  const onSortChange = () => {
    currentPage.value = 1
    fetchSuppliersList()
  }

  const resetFilters = () => {
    Object.assign(filters, {
      search: '',
      active: 'all',
      city: '',
      province: '',
      sort_by: 'created_at',
      sort_order: 'desc'
    })
    currentPage.value = 1
    fetchSuppliersList()
  }

  const toggleSupplierSelection = (supplierId: number) => {
    const index = selectedSuppliers.value.indexOf(supplierId)
    if (index > -1) {
      selectedSuppliers.value.splice(index, 1)
    } else {
      selectedSuppliers.value.push(supplierId)
    }
  }

  const selectAllSuppliers = () => {
    if (selectedSuppliers.value.length === suppliersList.value.length) {
      selectedSuppliers.value = []
    } else {
      selectedSuppliers.value = suppliersList.value.map(s => s.id)
    }
  }

  return {
    // State
    suppliersList,
    cities,
    provinces,
    loading,
    saveLoading,
    deleteLoading,
    toggleLoading,
    stats,

    // Dialog states  
    dialog,
    deleteDialog,
    editMode,
    selectedSupplier,
    selectedSuppliers,

    // Pagination
    currentPage,
    totalItems,
    itemsPerPage,

    // Filters
    filters,

    // Messages
    errorMessage,
    successMessage,
    modalErrorMessage,

    // Form
    formData,
    nameRules,
    codeRules,
    emailRules,
    phoneRules,

    // Computed
    canCreateEdit,
    canDelete,
    totalActiveSuppliers,
    totalInactiveSuppliers,
    totalSuppliersWithPurchases,
    totalCities,
    totalProvinces,
    hasSelectedSuppliers,
    filteredSuppliersCount,

    // Functions
    fetchSuppliersList,
    fetchCities,
    fetchProvinces,
    fetchStats,
    saveSupplier,
    deleteSupplier,
    toggleActiveStatus,
    openCreateDialog,
    openEditDialog,
    openDeleteDialog,
    closeDialog,
    clearModalError,
    onPageChange,
    onItemsPerPageChange,
    onSearch,
    onFilterChange,
    handleFiltersUpdate,
    onSortChange,
    resetFilters,
    toggleSupplierSelection,
    selectAllSuppliers
  }
}
