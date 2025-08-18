import { ref, computed, reactive } from 'vue'
import type { Ref } from 'vue'
import { $api } from '@/utils/api'

export interface Role {
  id: number
  name: string
  display_name?: string
  description?: string
  is_active: boolean
  sort_order: number
  users_count?: number
  permissions_count?: number
  created_at: string
  updated_at: string
  permissions?: Permission[]
  users?: User[]
}

export interface Permission {
  id_permission: number
  name: string
  display_name: string
  module: string
  action: string
  description?: string
  is_active: boolean
}

export interface User {
  id: number
  name: string
  email: string
}

export interface RoleFilters {
  search?: string
  is_active?: boolean
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

export interface CreateRoleData {
  name: string
  display_name?: string
  description?: string
  is_active?: boolean
  sort_order?: number
}

export function useRoles() {
  const roles: Ref<Role[]> = ref([])
  const role: Ref<Role | null> = ref(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0
  })

  const filters = reactive<RoleFilters>({
    search: '',
    is_active: undefined,
    sort_by: 'sort_order',
    sort_order: 'asc'
  })

  // Computed
  const hasRoles = computed(() => roles.value.length > 0)
  const isEmpty = computed(() => !loading.value && !hasRoles.value)
  const activeRoles = computed(() => roles.value.filter(role => role.is_active))
  const inactiveRoles = computed(() => roles.value.filter(role => !role.is_active))

  // Methods
  const fetchRoles = async (page = 1) => {
    loading.value = true
    error.value = null

    try {
      const params = new URLSearchParams({
        page: page.toString(),
        per_page: pagination.per_page.toString(),
        ...Object.fromEntries(
          Object.entries(filters).filter(([_, value]) => 
            value !== undefined && value !== '' && value !== null
          )
        )
      })

      const response = await $api(`/api/roles?${params}`)
      
      roles.value = response.data
      
      // Update pagination
      Object.assign(pagination, {
        current_page: response.current_page,
        last_page: response.last_page,
        per_page: response.per_page,
        total: response.total,
        from: response.from,
        to: response.to
      })
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch roles'
      roles.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchRole = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/roles/${id}`)
      role.value = response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch role'
      role.value = null
    } finally {
      loading.value = false
    }
  }

  const createRole = async (data: CreateRoleData) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api('/api/roles', {
        method: 'POST',
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      // Add to roles list
      roles.value.push(response.data)
      
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to create role'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateRole = async (id: number, data: Partial<Role>) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/roles/${id}`, {
        method: 'PUT',
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      // Update in roles list
      const index = roles.value.findIndex(r => r.id === id)
      if (index !== -1) {
        roles.value[index] = response.data
      }

      // Update current role if it's the same
      if (role.value && role.value.id === id) {
        role.value = response.data
      }

      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to update role'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteRole = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      await $api(`/api/roles/${id}`, {
        method: 'DELETE'
      })

      // Remove from roles list
      roles.value = roles.value.filter(r => r.id !== id)

      // Clear current role if it's the same
      if (role.value && role.value.id === id) {
        role.value = null
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to delete role'
      throw err
    } finally {
      loading.value = false
    }
  }

  const assignPermissions = async (roleId: number, permissionIds: number[]) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/roles/${roleId}/permissions`, {
        method: 'POST',
        body: JSON.stringify({ permission_ids: permissionIds }),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      // Update role in list
      const index = roles.value.findIndex(r => r.id === roleId)
      if (index !== -1) {
        roles.value[index] = response.data
      }

      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to assign permissions'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getRolePermissions = async (roleId: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/roles/${roleId}/permissions`)
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch role permissions'
      return []
    } finally {
      loading.value = false
    }
  }

  const getRoleUsers = async (roleId: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/roles/${roleId}/users`)
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch role users'
      return []
    } finally {
      loading.value = false
    }
  }

  const duplicateRole = async (id: number, newName: string) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/roles/${id}/duplicate`, {
        method: 'POST',
        body: JSON.stringify({ name: newName }),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      // Add to roles list
      roles.value.push(response.data)
      
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to duplicate role'
      throw err
    } finally {
      loading.value = false
    }
  }

  const searchRoles = async (query: string) => {
    filters.search = query
    await fetchRoles(1)
  }

  const updateRolePermissions = async (roleId: number, permissionIds: number[]) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/roles/${roleId}/permissions`, {
        method: 'PUT',
        body: { permission_ids: permissionIds }
      })

      // Update the role in the local state
      const roleIndex = roles.value.findIndex(r => r.id === roleId)
      if (roleIndex !== -1) {
        roles.value[roleIndex] = { ...roles.value[roleIndex], ...response.data }
      }

      return response
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to update role permissions'
      throw err
    } finally {
      loading.value = false
    }
  }

  const filterRoles = async (newFilters: Partial<RoleFilters>) => {
    Object.assign(filters, newFilters)
    await fetchRoles(1)
  }

  const resetFilters = async () => {
    Object.assign(filters, {
      search: '',
      is_active: undefined,
      sort_by: 'sort_order',
      sort_order: 'asc'
    })
    await fetchRoles(1)
  }

  const sortRoles = (sortBy: string, sortOrder: 'asc' | 'desc' = 'asc') => {
    roles.value.sort((a, b) => {
      let aValue = a[sortBy as keyof Role]
      let bValue = b[sortBy as keyof Role]

      // Handle different data types
      if (typeof aValue === 'string' && typeof bValue === 'string') {
        aValue = aValue.toLowerCase()
        bValue = bValue.toLowerCase()
      }

      if (sortOrder === 'asc') {
        return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
      } else {
        return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
      }
    })
  }

  return {
    // State
    roles,
    role,
    loading,
    error,
    pagination,
    filters,

    // Computed
    hasRoles,
    isEmpty,
    activeRoles,
    inactiveRoles,

    // Methods
    fetchRoles,
    fetchRole,
    createRole,
    updateRole,
    updateRolePermissions,
    deleteRole,
    assignPermissions,
    getRolePermissions,
    getRoleUsers,
    duplicateRole,
    searchRoles,
    filterRoles,
    resetFilters,
    sortRoles
  }
}
