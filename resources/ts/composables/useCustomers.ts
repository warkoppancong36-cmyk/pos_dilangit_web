import { CustomersApi } from '@/utils/api/CustomersApi'
import { computed, ref } from 'vue'

// Types
export interface Customer {
  id_customer: number
  customer_code: string
  name: string
  email?: string
  phone?: string
  birth_date?: string
  gender?: 'male' | 'female'
  address?: string
  city?: string
  postal_code?: string
  active: boolean
  total_visits: number
  total_spent: number
  last_visit?: string
  preferences?: Record<string, any>
  notes?: string
  created_by?: number
  updated_by?: number
  deleted_by?: number
  creator?: { id: number; name: string; email: string }
  updater?: { id: number; name: string; email: string }
  deleter?: { id: number; name: string; email: string }
  created_at: string
  updated_at: string
  // Computed attributes
  formatted_total_spent?: string
  age?: number
  customer_status?: string
  loyalty_level?: string
  full_address?: string
}

export interface CustomerFormData {
  name: string
  email?: string
  phone?: string
  birth_date?: string
  gender?: 'male' | 'female'
  address?: string
  city?: string
  postal_code?: string
  preferences?: Record<string, any>
  notes?: string
  active?: boolean
}

export interface CustomerFilters {
  search?: string
  status?: 'active' | 'inactive'
  gender?: 'male' | 'female' 
  city?: string
  loyalty_level?: 'basic' | 'bronze' | 'silver' | 'gold' | 'platinum'
  recent_days?: number
  min_visits?: number
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface CustomerStats {
  total_customers: number
  active_customers: number
  inactive_customers: number
  recent_customers: number
  frequent_customers: number
  total_spent_all: number
  average_spent_per_customer: number
  total_visits_all: number
  average_visits_per_customer: number
  customers_by_loyalty: {
    platinum: number
    gold: number
    silver: number
    bronze: number
    basic: number
  }
  customers_by_gender: {
    male: number
    female: number
    other: number
    unspecified: number
  }
  top_cities: string[]
}

export const useCustomers = () => {
  // State
  const customersList = ref<Customer[]>([])
  const loading = ref(false)
  const saveLoading = ref(false)
  const deleteLoading = ref(false)
  const toggleLoading = ref<Record<number, boolean>>({})
  const stats = ref<CustomerStats>({
    total_customers: 0,
    active_customers: 0,
    inactive_customers: 0,
    recent_customers: 0,
    frequent_customers: 0,
    total_spent_all: 0,
    average_spent_per_customer: 0,
    total_visits_all: 0,
    average_visits_per_customer: 0,
    customers_by_loyalty: {
      platinum: 0,
      gold: 0,
      silver: 0,
      bronze: 0,
      basic: 0
    },
    customers_by_gender: {
      male: 0,
      female: 0,
      other: 0,
      unspecified: 0
    },
    top_cities: []
  })

  // Dialog states
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const editMode = ref(false)
  const selectedCustomer = ref<Customer | null>(null)
  const selectedCustomers = ref<number[]>([])

  // Pagination
  const currentPage = ref(1)
  const totalItems = ref(0)
  const itemsPerPage = ref(15)

  // Filters
  const filters = ref<CustomerFilters>({
    search: '',
    status: undefined,
    gender: undefined,
    city: '',
    loyalty_level: undefined,
    sort_by: 'created_at',
    sort_order: 'desc'
  })

  // Messages
  const errorMessage = ref('')
  const successMessage = ref('')
  const modalErrorMessage = ref('')

  // Form
  const formData = ref<CustomerFormData>({
    name: '',
    email: '',
    phone: '',
    birth_date: '',
    gender: undefined,
    address: '',
    city: '',
    postal_code: '',
    preferences: {},
    notes: '',
    active: true
  })

  // Computed
  const hasSelectedCustomers = computed(() => selectedCustomers.value.length > 0)

  // Validation rules
  const nameRules = [
    (v: string) => !!v || 'Nama customer wajib diisi',
    (v: string) => (v && v.length >= 2) || 'Nama minimal 2 karakter'
  ]

  const emailRules = [
    (v: string) => !v || /.+@.+\..+/.test(v) || 'Email tidak valid'
  ]

  const phoneRules = [
    (v: string) => !v || /^[0-9+\-\s()]+$/.test(v) || 'Nomor telepon tidak valid'
  ]

  // Gender options
  const genderOptions = [
    { title: 'Laki-laki', value: 'male' },
    { title: 'Perempuan', value: 'female' },
  ]

  // Loyalty level options
  const loyaltyLevelOptions = [
    { title: 'Basic', value: 'basic', color: 'grey' },
    { title: 'Bronze', value: 'bronze', color: 'orange' },
    { title: 'Silver', value: 'silver', color: 'blue-grey' },
    { title: 'Gold', value: 'gold', color: 'amber' },
    { title: 'Platinum', value: 'platinum', color: 'purple' }
  ]

  // Methods
  const fetchCustomersList = async () => {
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
        if (value === '' || value === undefined || value === null) {
          delete params[key as keyof typeof params]
        }
      })

      const response = await CustomersApi.getAll(params)
      
      if (response.success) {
        customersList.value = response.data
        totalItems.value = response.total
        currentPage.value = response.current_page
      } else {
        throw new Error(response.message || 'Gagal mengambil data customer')
      }
    } catch (error: any) {
      console.error('Error fetching customers:', error)
      errorMessage.value = CustomersApi.formatError(error) || 'Gagal mengambil data customer'
      customersList.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchStats = async () => {
    try {
      const response = await CustomersApi.getStats()
      if (response.success) {
        stats.value = response.data
      }
    } catch (error: any) {
      console.error('Error fetching customer stats:', error)
    }
  }

  const saveCustomer = async () => {
    saveLoading.value = true
    modalErrorMessage.value = ''
    
    try {
      const response = editMode.value 
        ? await CustomersApi.update(selectedCustomer.value!.id_customer, formData.value)
        : await CustomersApi.create(formData.value)
      
      if (response.success) {
        successMessage.value = editMode.value ? 'Customer berhasil diperbarui' : 'Customer berhasil ditambahkan'
        closeDialog()
        await fetchCustomersList()
        await fetchStats()
      } else {
        throw new Error(response.message || 'Gagal menyimpan customer')
      }
    } catch (error: any) {
      console.error('Error saving customer:', error)
      modalErrorMessage.value = CustomersApi.formatError(error) || 'Gagal menyimpan customer'
    } finally {
      saveLoading.value = false
    }
  }

  const deleteCustomer = async () => {
    if (!selectedCustomer.value) return
    
    deleteLoading.value = true
    
    try {
      const response = await CustomersApi.delete(selectedCustomer.value.id_customer)
      
      if (response.success) {
        successMessage.value = 'Customer berhasil dihapus'
        deleteDialog.value = false
        selectedCustomer.value = null
        await fetchCustomersList()
        await fetchStats()
      } else {
        throw new Error(response.message || 'Gagal menghapus customer')
      }
    } catch (error: any) {
      console.error('Error deleting customer:', error)
      errorMessage.value = CustomersApi.formatError(error) || 'Gagal menghapus customer'
    } finally {
      deleteLoading.value = false
    }
  }

  const bulkDeleteCustomers = async () => {
    if (selectedCustomers.value.length === 0) return
    
    deleteLoading.value = true
    
    try {
      const response = await CustomersApi.bulkDelete(selectedCustomers.value)
      
      if (response.success) {
        successMessage.value = `${selectedCustomers.value.length} customer berhasil dihapus`
        selectedCustomers.value = []
        await fetchCustomersList()
        await fetchStats()
      } else {
        throw new Error(response.message || 'Gagal menghapus customer')
      }
    } catch (error: any) {
      console.error('Error bulk deleting customers:', error)
      errorMessage.value = CustomersApi.formatError(error) || 'Gagal menghapus customer'
    } finally {
      deleteLoading.value = false
    }
  }

  const toggleActiveStatus = async (customer: Customer) => {
    const customerId = customer.id_customer
    toggleLoading.value[customerId] = true
    
    try {
      const response = await CustomersApi.toggleActive(customerId)
      
      if (response.success) {
        const status = response.data.active ? 'diaktifkan' : 'dinonaktifkan'
        successMessage.value = `Customer berhasil ${status}`
        await fetchCustomersList()
        await fetchStats()
      } else {
        throw new Error(response.message || 'Gagal mengubah status customer')
      }
    } catch (error: any) {
      console.error('Error toggling customer status:', error)
      errorMessage.value = CustomersApi.formatError(error) || 'Gagal mengubah status customer'
    } finally {
      toggleLoading.value[customerId] = false
    }
  }

  const searchSuggestions = async (query: string) => {
    if (query.length < 2) return []
    
    try {
      const response = await CustomersApi.getSearchSuggestions(query)
      return response.success ? response.data : []
    } catch (error: any) {
      console.error('Error fetching customer suggestions:', error)
      return []
    }
  }

  // Dialog methods
  const openCreateDialog = () => {
    editMode.value = false
    selectedCustomer.value = null
    formData.value = {
      name: '',
      email: '',
      phone: '',
      birth_date: '',
      gender: undefined,
      address: '',
      city: '',
      postal_code: '',
      preferences: {},
      notes: '',
      active: true
    }
    modalErrorMessage.value = ''
    dialog.value = true
  }

  const openEditDialog = (customer: Customer) => {
    editMode.value = true
    selectedCustomer.value = customer
    formData.value = {
      name: customer.name,
      email: customer.email || '',
      phone: customer.phone || '',
      birth_date: customer.birth_date || '',
      gender: customer.gender,
      address: customer.address || '',
      city: customer.city || '',
      postal_code: customer.postal_code || '',
      preferences: customer.preferences || {},
      notes: customer.notes || '',
      active: customer.active
    }
    modalErrorMessage.value = ''
    dialog.value = true
  }

  const openDeleteDialog = (customer: Customer) => {
    selectedCustomer.value = customer
    deleteDialog.value = true
  }

  const closeDialog = () => {
    dialog.value = false
    setTimeout(() => {
      editMode.value = false
      selectedCustomer.value = null
      modalErrorMessage.value = ''
    }, 300)
  }

  const clearModalError = () => {
    modalErrorMessage.value = ''
  }

  // Pagination and filters
  const onPageChange = (page: number) => {
    currentPage.value = page
    fetchCustomersList()
  }

  const handleFiltersUpdate = (newFilters: Partial<CustomerFilters>) => {
    filters.value = { ...filters.value, ...newFilters }
    currentPage.value = 1
    fetchCustomersList()
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

  const getLoyaltyColor = (level: string): string => {
    const option = loyaltyLevelOptions.find(opt => opt.value === level)
    return option?.color || 'grey'
  }

  const getStatusColor = (status: string): string => {
    switch (status) {
      case 'active': return 'success'
      case 'regular': return 'info'
      case 'inactive': return 'warning'
      case 'dormant': return 'error'
      default: return 'grey'
    }
  }

  return {
    // State
    customersList,
    loading,
    saveLoading,
    deleteLoading,
    toggleLoading,
    stats,
    dialog,
    deleteDialog,
    editMode,
    selectedCustomer,
    selectedCustomers,
    currentPage,
    totalItems,
    itemsPerPage,
    filters,
    errorMessage,
    successMessage,
    modalErrorMessage,
    formData,

    // Computed
    hasSelectedCustomers,

    // Validation
    nameRules,
    emailRules,
    phoneRules,

    // Options
    genderOptions,
    loyaltyLevelOptions,

    // Methods
    fetchCustomersList,
    fetchStats,
    saveCustomer,
    deleteCustomer,
    bulkDeleteCustomers,
    toggleActiveStatus,
    searchSuggestions,
    openCreateDialog,
    openEditDialog,
    openDeleteDialog,
    closeDialog,
    clearModalError,
    onPageChange,
    handleFiltersUpdate,
    formatCurrency,
    getLoyaltyColor,
    getStatusColor,
  }
}
