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

export interface CreateUserData {
  name: string
  username: string
  email: string
  phone: string
  password: string
  role_id: number | null
  is_active: boolean
}

export interface UpdateUserData {
  name?: string
  email?: string
  password?: string
  password_confirmation?: string
  is_active?: boolean
  phone?: string
  address?: string
  role_ids?: number[]
}

export function useUsers() {
  // State
  const users = ref<User[]>([])
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
  })

  const filters = reactive<UserFilters>({
    search: '',
    status: 'all',
    role_id: undefined,
    sort_by: 'name',
    sort_order: 'asc'
  })

  // Computed
  const hasUsers = computed(() => users.value.length > 0)
  const isEmpty = computed(() => !loading.value && users.value.length === 0)
  const activeUsers = computed(() => users.value.filter(user => user.is_active))
  const inactiveUsers = computed(() => users.value.filter(user => !user.is_active))

  // Actions
  const fetchUsers = async (page = 1, params = {}) => {
    loading.value = true
    error.value = null

    try {
      const queryParams = new URLSearchParams({
        page: page.toString(),
        per_page: pagination.value.per_page.toString(),
        ...Object.fromEntries(
          Object.entries(filters).filter(([_, value]) =>
            value !== undefined && value !== '' && value !== null
          )
        ),
        ...params
      })

      const response = await $api(`/api/users?${queryParams}`)

      if (response.data) {
        users.value = response.data.data || response.data

        if (response.meta) {
          pagination.value = {
            current_page: response.meta.current_page,
            last_page: response.meta.last_page,
            per_page: response.meta.per_page,
            total: response.meta.total,
            from: response.meta.from,
            to: response.meta.to
          }
        }
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch users'
      console.error('Error fetching users:', err)
    } finally {
      loading.value = false
    }
  }

  const fetchUser = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/users/${id}`)
      user.value = response.data
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch user'
      console.error('Error fetching user:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const createUser = async (userData: CreateUserData) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api('/api/users', {
        method: 'POST',
        body: JSON.stringify(userData),
        headers: {
          'Content-Type': 'application/json'
        }
      })
      users.value.unshift(response.data)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create user'
      console.error('Error creating user:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateUser = async (id: number, userData: UpdateUserData) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/users/${id}`, {
        method: 'PUT',
        body: JSON.stringify(userData),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = response.data
      }

      if (user.value?.id === id) {
        user.value = response.data
      }

      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update user'
      console.error('Error updating user:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteUser = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      await $api(`/api/users/${id}`, {
        method: 'DELETE'
      })

      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value.splice(index, 1)
      }

      if (user.value?.id === id) {
        user.value = null
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete user'
      console.error('Error deleting user:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateUserStatus = async (id: number, isActive: boolean) => {
    return updateUser(id, { is_active: isActive })
  }

  const assignRoles = async (userId: number, roleIds: number[]) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/users/${userId}/roles`, {
        method: 'POST',
        body: JSON.stringify({ role_ids: roleIds }),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      const index = users.value.findIndex(u => u.id === userId)
      if (index !== -1) {
        users.value[index] = response.data
      }

      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to assign roles'
      console.error('Error assigning roles:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const searchUsers = async (query: string) => {
    filters.search = query
    return fetchUsers(1)
  }

  const filterUsers = async (newFilters: Partial<UserFilters>) => {
    Object.assign(filters, newFilters)
    return fetchUsers(1)
  }

  const resetFilters = () => {
    Object.assign(filters, {
      search: '',
      is_active: undefined,
      role_id: undefined,
      sort_by: 'name',
      sort_order: 'asc'
    })
  }

  return {
    // State
    users,
    user,
    loading,
    error,
    pagination,
    filters,

    // Computed
    hasUsers,
    isEmpty,
    activeUsers,
    inactiveUsers,

    // Actions
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    deleteUser,
    updateUserStatus,
    assignRoles,
    searchUsers,
    filterUsers,
    resetFilters
  }
}
