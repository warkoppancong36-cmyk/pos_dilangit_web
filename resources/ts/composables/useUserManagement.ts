import { ref, computed } from 'vue'
import axios from 'axios'

export interface User {
    id: number
    name: string
    username: string
    email: string
    phone?: string
    role_id: number
    role?: {
        id: number
        name: string
    }
    is_active: boolean
    created_at: string
    updated_at: string
    last_login_at?: string
}

export interface Role {
    id: number
    name: string
    description?: string
}

export interface UserFilters {
    search?: string
    status?: 'all' | 'active' | 'inactive'
    role_id?: number
    sort_by?: string
    sort_order?: 'asc' | 'desc'
    page?: number
    per_page?: number
}

export interface UserFormData {
    name: string
    username: string
    email: string
    phone: string
    password: string
    role_id: number | null
    is_active: boolean
}

export const useUserManagement = () => {
    // State
    const users = ref<User[]>([])
    const roles = ref<Role[]>([])
    const loading = ref(false)
    const saveLoading = ref(false)
    const deleteLoading = ref(false)
    const toggleLoading = ref<{ [key: number]: boolean }>({})

    // Dialog states
    const dialog = ref(false)
    const deleteDialog = ref(false)
    const editMode = ref(false)
    const selectedUser = ref<User | null>(null)

    // Pagination
    const currentPage = ref(1)
    const totalItems = ref(0)
    const itemsPerPage = ref(15)

    // Filters
    const filters = ref<UserFilters>({
        search: '',
        status: 'all',
        role_id: undefined,
        sort_by: 'created_at',
        sort_order: 'desc',
        page: 1,
        per_page: 15
    })

    // Messages
    const errorMessage = ref('')
    const successMessage = ref('')
    const modalErrorMessage = ref('')

    // Form data
    const formData = ref<UserFormData>({
        name: '',
        username: '',
        email: '',
        phone: '',
        password: '',
        role_id: null,
        is_active: true
    })

    // Validation rules
    const nameRules = [
        (v: string) => !!v || 'Nama wajib diisi',
        (v: string) => v.length >= 2 || 'Nama minimal 2 karakter'
    ]

    const usernameRules = [
        (v: string) => !!v || 'Username wajib diisi',
        (v: string) => v.length >= 3 || 'Username minimal 3 karakter'
    ]

    const emailRules = [
        (v: string) => !!v || 'Email wajib diisi',
        (v: string) => /.+@.+\..+/.test(v) || 'Email harus valid'
    ]

    const passwordRules = [
        (v: string) => {
            if (editMode.value && !v) return true // Optional for edit
            return !!v || 'Password wajib diisi'
        },
        (v: string) => {
            if (editMode.value && !v) return true // Optional for edit
            return v.length >= 6 || 'Password minimal 6 karakter'
        }
    ]

    const roleRules = [
        (v: number) => !!v || 'Role wajib dipilih'
    ]

    // Computed
    const canCreateEdit = computed(() => true) // Add permission logic here
    const canDelete = computed(() => true) // Add permission logic here

    const totalActiveUsers = computed(() => users.value.filter(user => user.is_active).length)
    const totalInactiveUsers = computed(() => users.value.filter(user => !user.is_active).length)

    // Functions
    const fetchUsers = async () => {
        try {
            loading.value = true
            errorMessage.value = ''

            const params = {
                ...filters.value,
                page: currentPage.value,
                per_page: itemsPerPage.value
            }

            const response = await axios.get('/api/users', { params })

            if (response.data.success) {
                users.value = response.data.data.data || response.data.data
                totalItems.value = response.data.data.total || users.value.length

                if (response.data.data.current_page) {
                    currentPage.value = response.data.data.current_page
                }
            }
        } catch (error: any) {
            console.error('Error fetching users:', error)
            errorMessage.value = error.response?.data?.message || 'Gagal memuat data pengguna'
            users.value = []
        } finally {
            loading.value = false
        }
    }

    const fetchRoles = async () => {
        try {
            const response = await axios.get('/api/users/roles')

            if (response.data.success) {
                roles.value = response.data.data
            }
        } catch (error: any) {
            console.error('Error fetching roles:', error)
        }
    }

    const saveUser = async () => {
        try {
            saveLoading.value = true
            modalErrorMessage.value = ''

            const payload: any = { ...formData.value }

            // Remove password if empty in edit mode
            if (editMode.value && !payload.password) {
                delete payload.password
            }

            let response
            if (editMode.value && selectedUser.value) {
                response = await axios.put(`/api/users/${selectedUser.value.id}`, payload)
            } else {
                response = await axios.post('/api/users', payload)
            }

            if (response.data.success) {
                successMessage.value = editMode.value ? 'Pengguna berhasil diperbarui' : 'Pengguna berhasil ditambahkan'
                closeDialog()
                await fetchUsers()
            }
        } catch (error: any) {
            console.error('Error saving user:', error)
            modalErrorMessage.value = error.response?.data?.message || 'Gagal menyimpan pengguna'
        } finally {
            saveLoading.value = false
        }
    }

    const deleteUser = async () => {
        if (!selectedUser.value) return

        try {
            deleteLoading.value = true

            const response = await axios.delete(`/api/users/${selectedUser.value.id}`)

            if (response.data.success) {
                successMessage.value = 'Pengguna berhasil dihapus'
                deleteDialog.value = false
                selectedUser.value = null
                await fetchUsers()
            }
        } catch (error: any) {
            console.error('Error deleting user:', error)
            errorMessage.value = error.response?.data?.message || 'Gagal menghapus pengguna'
        } finally {
            deleteLoading.value = false
        }
    }

    const toggleActiveStatus = async (user: User) => {
        try {
            toggleLoading.value[user.id] = true

            const response = await axios.post(`/api/users/${user.id}/toggle-active`)

            if (response.data.success) {
                const status = response.data.data.is_active ? 'diaktifkan' : 'dinonaktifkan'
                successMessage.value = `Pengguna berhasil ${status}`
                await fetchUsers()
            }
        } catch (error: any) {
            console.error('Error toggling user status:', error)
            errorMessage.value = error.response?.data?.message || 'Gagal mengubah status pengguna'
        } finally {
            toggleLoading.value[user.id] = false
        }
    }

    const bulkDeleteUsers = async (userIds: number[]) => {
        try {
            loading.value = true

            const response = await axios.post('/api/users/bulk-delete', {
                user_ids: userIds
            })

            if (response.data.success) {
                successMessage.value = `${response.data.data.deleted_count} pengguna berhasil dihapus`
                await fetchUsers()
            }
        } catch (error: any) {
            console.error('Error bulk deleting users:', error)
            errorMessage.value = error.response?.data?.message || 'Gagal menghapus pengguna'
        } finally {
            loading.value = false
        }
    }

    // Dialog functions
    const openCreateDialog = () => {
        editMode.value = false
        selectedUser.value = null
        formData.value = {
            name: '',
            username: '',
            email: '',
            phone: '',
            password: '',
            role_id: null,
            is_active: true
        }
        modalErrorMessage.value = ''
        dialog.value = true
    }

    const openEditDialog = (user: User) => {
        editMode.value = true
        selectedUser.value = user
        formData.value = {
            name: user.name,
            username: user.username,
            email: user.email,
            phone: user.phone || '',
            password: '',
            role_id: user.role_id,
            is_active: user.is_active
        }
        modalErrorMessage.value = ''
        dialog.value = true
    }

    const openDeleteDialog = (user: User) => {
        selectedUser.value = user
        deleteDialog.value = true
    }

    const closeDialog = () => {
        dialog.value = false
        editMode.value = false
        selectedUser.value = null
        modalErrorMessage.value = ''

        // Reset form
        formData.value = {
            name: '',
            username: '',
            email: '',
            phone: '',
            password: '',
            role_id: null,
            is_active: true
        }
    }

    const clearModalError = () => {
        modalErrorMessage.value = ''
    }

    // Event handlers
    const onPageChange = (page: number) => {
        currentPage.value = page
        fetchUsers()
    }

    const onSearch = () => {
        currentPage.value = 1
        fetchUsers()
    }

    const onFilterChange = () => {
        currentPage.value = 1
        fetchUsers()
    }

    const onSortChange = () => {
        currentPage.value = 1
        fetchUsers()
    }

    return {
        // State
        users,
        roles,
        loading,
        saveLoading,
        deleteLoading,
        toggleLoading,

        // Dialog states
        dialog,
        deleteDialog,
        editMode,
        selectedUser,

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
        usernameRules,
        emailRules,
        passwordRules,
        roleRules,

        // Computed
        canCreateEdit,
        canDelete,
        totalActiveUsers,
        totalInactiveUsers,

        // Functions
        fetchUsers,
        fetchRoles,
        saveUser,
        deleteUser,
        toggleActiveStatus,
        bulkDeleteUsers,
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
