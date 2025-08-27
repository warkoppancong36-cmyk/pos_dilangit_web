import { MESSAGES } from '@/constants/categories'
import { useAuthStore } from '@/stores/auth'
import { CategoriesApi } from '@/utils/api/CategoriesApi'
import { computed, ref } from 'vue'

// Types
export interface Category {
  id_category: number
  name: string
  description: string | null
  image: string | null
  active: boolean
  created_by: number
  updated_by: number | null
  created_at: string
  updated_at: string
  deleted_at: string | null
  created_by_user?: { id: number; name: string }
  updated_by_user?: { id: number; name: string }
  image_url?: string
}

export interface CategoryFormData {
  name: string
  description: string
  image: File | null
  active: boolean
}

export interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export const useCategories = () => {
  // Store
  const authStore = useAuthStore()

  // Reactive data
  const categories = ref<Category[]>([])
  const loading = ref(false)
  const saveLoading = ref(false)
  const deleteLoading = ref(false)
  const toggleLoading = ref<Record<number, boolean>>({})

  // Dialog and form states
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const editMode = ref(false)
  const selectedCategory = ref<Category | null>(null)

  // Form data
  const formData = ref<CategoryFormData>({
    name: '',
    description: '',
    image: null,
    active: true
  })

  const imagePreview = ref<string | null>(null)

  // Search and filters
  const search = ref('')
  const statusFilter = ref('all')
  const perPage = ref(10)
  const viewMode = ref('grid')

  // Pagination
  const currentPage = ref(1)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  })

  // Messages
  const successMessage = ref('')
  const errorMessage = ref('')
  const modalErrorMessage = ref('')

  // Computed properties
  const canCreateEdit = computed(() =>
    authStore.user?.role?.name === 'admin' || authStore.user?.role?.name === 'manager'
  )

  const isDialogOpen = computed(() => dialog.value)
  const totalPages = computed(() => pagination.value.last_page)

  // Statistics
  const totalCategories = computed(() => categories.value.length)
  const activeCategories = computed(() => categories.value.filter(c => c.active).length)
  const inactiveCategories = computed(() => categories.value.filter(c => !c.active).length)
  const totalData = computed(() => pagination.value.total)

  // Methods
  const clearMessages = () => {
    setTimeout(() => {
      successMessage.value = ''
      errorMessage.value = ''
    }, 5000)
  }

  const fetchCategories = async () => {
    loading.value = true
    try {
      const params = {
        page: currentPage.value,
        per_page: perPage.value,
        search: search.value?.trim() || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined
      }

      const response = await CategoriesApi.getCategories(params)
      if (response.success) {
        categories.value = response.data
        pagination.value = response.pagination
      } else {
        errorMessage.value = response.message || MESSAGES.ERROR.FETCH
        clearMessages()
      }
    } catch (error: any) {
      errorMessage.value = CategoriesApi.handleError(error)
      clearMessages()
    } finally {
      loading.value = false
    }
  }

  const fetchAllCategories = async () => {
    try {
      loading.value = true
      const params = {
        page: 1,
        per_page: 100, // Get more categories for dropdown
        status: 'active' // Only active categories
      }

      const response = await CategoriesApi.getCategories(params)
      if (response.success) {
        categories.value = response.data
        if (response.pagination) {
          pagination.value = response.pagination
        }
      } else {
        errorMessage.value = response.message || MESSAGES.ERROR.FETCH
        clearMessages()
      }
    } catch (error: any) {
      errorMessage.value = CategoriesApi.handleError(error)
      clearMessages()
    } finally {
      loading.value = false
    }
  }

  const handleImageUpload = (files: File[]) => {
    const file = files[0]
    if (file) {
      const validation = CategoriesApi.validateImage(file)
      if (!validation.valid) {
        modalErrorMessage.value = validation.error!
        return
      }

      formData.value.image = file
      const reader = new FileReader()
      reader.onload = (e) => {
        imagePreview.value = e.target?.result as string
      }
      reader.readAsDataURL(file)
    }
  }

  const removeImage = () => {
    formData.value.image = null
    imagePreview.value = null
    const fileInput = document.querySelector('input[type="file"]') as HTMLInputElement
    if (fileInput) fileInput.value = ''
  }

  const saveCategory = async (validatedData?: CategoryFormData) => {
    saveLoading.value = true
    modalErrorMessage.value = ''
    try {
      // Use validated data if provided, otherwise use form data
      const dataToSave = validatedData || formData.value

      const response = editMode.value && selectedCategory.value
        ? await CategoriesApi.updateCategory(selectedCategory.value.id_category, dataToSave)
        : await CategoriesApi.createCategory(dataToSave)

      if (response.success) {
        await fetchCategories()
        dialog.value = false
        successMessage.value = editMode.value ? MESSAGES.SUCCESS.UPDATED : MESSAGES.SUCCESS.CREATED
        clearMessages()
      }
    } catch (error: any) {
      modalErrorMessage.value = CategoriesApi.handleError(error)
    } finally {
      saveLoading.value = false
    }
  }

  const deleteCategory = (category: Category) => {
    selectedCategory.value = category
    deleteDialog.value = true
  }

  const confirmDelete = async () => {
    if (!selectedCategory.value) return
    deleteLoading.value = true
    try {
      const response = await CategoriesApi.deleteCategory(selectedCategory.value.id_category)
      if (response.success) {
        await fetchCategories()
        deleteDialog.value = false
        successMessage.value = MESSAGES.SUCCESS.DELETED
        clearMessages()
      }
    } catch (error: any) {
      errorMessage.value = CategoriesApi.handleError(error)
      clearMessages()
    } finally {
      deleteLoading.value = false
    }
  }

  const toggleActiveStatus = async (category: Category) => {
    toggleLoading.value[category.id_category] = true
    try {
      const response = await CategoriesApi.toggleActiveStatus(category.id_category)
      if (response.success) {
        await fetchCategories()
        successMessage.value = category.active ? MESSAGES.SUCCESS.DEACTIVATED : MESSAGES.SUCCESS.ACTIVATED
        clearMessages()
      }
    } catch (error: any) {
      errorMessage.value = CategoriesApi.handleError(error)
      clearMessages()
    } finally {
      toggleLoading.value[category.id_category] = false
    }
  }

  const openCreateDialog = () => {
    editMode.value = false
    modalErrorMessage.value = ''
    formData.value = { name: '', description: '', image: null, active: true }
    imagePreview.value = null
    dialog.value = true
  }

  const openEditDialog = (category: Category) => {
    editMode.value = true
    selectedCategory.value = category
    modalErrorMessage.value = ''
    formData.value = {
      name: category.name,
      description: category.description || '',
      image: null,
      active: category.active
    }
    imagePreview.value = category.image ? category.image_url || `/storage/categories/${category.image}` : null
    dialog.value = true
  }

  const closeDialog = () => {
    dialog.value = false
    modalErrorMessage.value = ''
    imagePreview.value = null
  }

  const onPageChange = (page: number) => {
    currentPage.value = page
    fetchCategories()
  }

  const onSearch = () => {
    currentPage.value = 1
    fetchCategories()
  }

  const onFilterChange = () => {
    currentPage.value = 1
    fetchCategories()
  }

  return {
    // Reactive data
    categories,
    loading,
    saveLoading,
    deleteLoading,
    toggleLoading,
    dialog,
    deleteDialog,
    editMode,
    selectedCategory,
    formData,
    imagePreview,
    search,
    statusFilter,
    perPage,
    viewMode,
    currentPage,
    pagination,
    successMessage,
    errorMessage,
    modalErrorMessage,

    // Computed properties
    canCreateEdit,
    isDialogOpen,
    totalPages,
    totalCategories,
    activeCategories,
    inactiveCategories,
    totalData,

    // Methods
    fetchCategories,
    fetchAllCategories,
    handleImageUpload,
    removeImage,
    saveCategory,
    deleteCategory,
    confirmDelete,
    toggleActiveStatus,
    openCreateDialog,
    openEditDialog,
    closeDialog,
    onPageChange,
    onSearch,
    onFilterChange,
    clearMessages
  }
}
