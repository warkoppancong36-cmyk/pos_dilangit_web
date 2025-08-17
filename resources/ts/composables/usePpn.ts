import { useAuthStore } from '@/stores/auth'
import { PpnApi } from '@/utils/api/PpnApi'
import { computed, ref, watch } from 'vue'

export interface Ppn {
  id_ppn: number
  name: string
  nominal: number
  description?: string
  active: boolean
  status?: string
  formatted_nominal: string
  rate: number
  creator?: {
    id: number
    username: string
  }
  updater?: {
    id: number
    username: string
  }
  created_at: string
  updated_at: string
}

export interface PpnFormData {
  name: string
  nominal: number
  description: string
  active: boolean
  status: string
}

export interface PaginatedPpnResponse {
  data: Ppn[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface PpnFilters {
  search: string
  active: 'all' | 'active' | 'inactive'
  sortBy: string
  sortOrder: 'asc' | 'desc'
}

export const usePpn = () => {
  const authStore = useAuthStore()

  // State
  const ppnList = ref<Ppn[]>([])
  const loading = ref(false)
  const saveLoading = ref(false)
  const deleteLoading = ref(false)
  const toggleLoading = ref<{ [key: number]: boolean }>({})

  // Dialog states
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const editMode = ref(false)
  const selectedPpn = ref<Ppn | null>(null)

  // Pagination
  const currentPage = ref(1)
  const totalPages = ref(1)
  const totalItems = ref(0)
  const itemsPerPage = ref(15)

  // Filters
  const filters = ref<PpnFilters>({
    search: '',
    active: 'all',
    sortBy: 'created_at',
    sortOrder: 'desc'
  })

  // Messages
  const errorMessage = ref('')
  const successMessage = ref('')
  const modalErrorMessage = ref('')

  // Form data
  const formData = ref<PpnFormData>({
    name: '',
    nominal: 0,
    description: '',
    active: true,
    status: ''
  })

  // Computed
  const canCreateEdit = computed(() => {
    return authStore.user?.role?.name === 'admin' || authStore.user?.role?.name === 'manager'
  })

  const canDelete = computed(() => {
    return authStore.user?.role?.name === 'admin'
  })

  const totalActivePpn = computed(() => {
    return ppnList.value.filter(ppn => ppn.active).length
  })

  const totalInactivePpn = computed(() => {
    return ppnList.value.filter(ppn => !ppn.active).length
  })

  const averageNominal = computed(() => {
    if (ppnList.value.length === 0) return 0
    const total = ppnList.value.reduce((sum, ppn) => sum + ppn.nominal, 0)
    return Math.round((total / ppnList.value.length) * 100) / 100
  })

  // Form validation rules
  const nameRules = [
    (v: string) => !!v || 'Nama wajib diisi',
    (v: string) => v.length <= 255 || 'Nama maksimal 255 karakter'
  ]

  const nominalRules = [
    (v: number) => v !== null && v !== undefined || 'Nominal wajib diisi',
    (v: number) => v >= 0 || 'Nominal harus 0 atau lebih besar',
    (v: number) => v <= 100 || 'Nominal maksimal 100'
  ]

  // Helper functions
  const clearMessages = () => {
    setTimeout(() => {
      errorMessage.value = ''
      successMessage.value = ''
    }, 5000)
  }

  const clearModalError = () => {
    modalErrorMessage.value = ''
  }

  // API functions
  const fetchPpnList = async () => {
    loading.value = true
    try {
      const response = await PpnApi.getPpnList({
        page: currentPage.value,
        per_page: itemsPerPage.value,
        search: filters.value.search,
        active: filters.value.active !== 'all' ? filters.value.active === 'active' : undefined,
        sort_by: filters.value.sortBy,
        sort_order: filters.value.sortOrder
      })

      ppnList.value = response.data
      currentPage.value = response.current_page
      totalPages.value = response.last_page
      totalItems.value = response.total
    } catch (error: any) {
      console.error('Error fetching PPN list:', error)
      errorMessage.value = error.message || 'Gagal memuat data PPN'
      clearMessages()
    } finally {
      loading.value = false
    }
  }

  const savePpn = async (validatedData?: PpnFormData) => {
    saveLoading.value = true
    modalErrorMessage.value = ''
    
    try {
      // Use validated data if provided, otherwise use form data
      const dataToSave = validatedData || formData.value
      
      if (editMode.value && selectedPpn.value) {
        await PpnApi.updatePpn(selectedPpn.value.id_ppn, dataToSave)
        successMessage.value = 'PPN berhasil diperbarui'
      } else {
        await PpnApi.createPpn(dataToSave)
        successMessage.value = 'PPN berhasil ditambahkan'
      }
      
      await fetchPpnList()
      closeDialog()
      clearMessages()
    } catch (error: any) {
      console.error('Error saving PPN:', error)
      modalErrorMessage.value = error.message || 'Gagal menyimpan data PPN'
    } finally {
      saveLoading.value = false
    }
  }

  const deletePpn = async () => {
    if (!selectedPpn.value) return
    
    deleteLoading.value = true
    try {
      await PpnApi.deletePpn(selectedPpn.value.id_ppn)
      await fetchPpnList()
      deleteDialog.value = false
      successMessage.value = 'PPN berhasil dihapus'
      clearMessages()
    } catch (error: any) {
      console.error('Error deleting PPN:', error)
      errorMessage.value = error.message || 'Gagal menghapus data PPN'
      clearMessages()
    } finally {
      deleteLoading.value = false
    }
  }

  const toggleActiveStatus = async (ppn: Ppn) => {
    toggleLoading.value[ppn.id_ppn] = true
    try {
      await PpnApi.togglePpnStatus(ppn.id_ppn)
      await fetchPpnList()
      successMessage.value = `Status PPN berhasil ${ppn.active ? 'dinonaktifkan' : 'diaktifkan'}`
      clearMessages()
    } catch (error: any) {
      console.error('Error toggling PPN status:', error)
      errorMessage.value = error.message || 'Gagal mengubah status PPN'
      clearMessages()
    } finally {
      toggleLoading.value[ppn.id_ppn] = false
    }
  }

  // Dialog functions
  const openCreateDialog = () => {
    editMode.value = false
    modalErrorMessage.value = ''
    formData.value = {
      name: '',
      nominal: 0,
      description: '',
      active: true,
      status: ''
    }
    dialog.value = true
  }

  const openEditDialog = (ppn: Ppn) => {
    editMode.value = true
    selectedPpn.value = ppn
    modalErrorMessage.value = ''
    formData.value = {
      name: ppn.name,
      nominal: ppn.nominal,
      description: ppn.description || '',
      active: ppn.active,
      status: ppn.status || ''
    }
    dialog.value = true
  }

  const openDeleteDialog = (ppn: Ppn) => {
    selectedPpn.value = ppn
    deleteDialog.value = true
  }

  const closeDialog = () => {
    dialog.value = false
    modalErrorMessage.value = ''
  }

  // Event handlers
  const onPageChange = (page: number) => {
    currentPage.value = page
    fetchPpnList()
  }

  const onSearch = () => {
    currentPage.value = 1
    fetchPpnList()
  }

  const onFilterChange = () => {
    currentPage.value = 1
    fetchPpnList()
  }

  const onSortChange = () => {
    fetchPpnList()
  }

  // Watch for dialog changes to clear errors
  watch(dialog, (newValue) => {
    if (!newValue) {
      modalErrorMessage.value = ''
    }
  })

  return {
    // State
    ppnList,
    loading,
    saveLoading,
    deleteLoading,
    toggleLoading,
    
    // Dialog states
    dialog,
    deleteDialog,
    editMode,
    selectedPpn,
    
    // Pagination
    currentPage,
    totalPages,
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
    nominalRules,
    
    // Computed
    canCreateEdit,
    canDelete,
    totalActivePpn,
    totalInactivePpn,
    averageNominal,
    
    // Functions
    fetchPpnList,
    savePpn,
    deletePpn,
    toggleActiveStatus,
    openCreateDialog,
    openEditDialog,
    openDeleteDialog,
    closeDialog,
    clearModalError,
    onPageChange,
    onSearch,
    onFilterChange,
    onSortChange
  }
}
