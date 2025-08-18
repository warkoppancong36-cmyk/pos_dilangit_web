import { ref, computed, reactive } from 'vue'
import type { Ref } from 'vue'
import { $api } from '@/utils/api'

export interface Permission {
  id: number
  name: string
  module: string
  action: string
  description?: string
  created_at: string
  updated_at: string
}

export interface UserPermission {
  id: number
  user_id: number
  permission_id: number
  granted: boolean
  granted_at?: string
  revoked_at?: string
  expires_at?: string
  created_at: string
  updated_at: string
  permission?: Permission
  user?: {
    id: number
    name: string
    email: string
  }
}

export interface RolePermission {
  id: number
  role_id: number
  permission_id: number
  granted: boolean
  granted_at?: string
  revoked_at?: string
  expires_at?: string
  created_at: string
  updated_at: string
  permission?: Permission
  role?: {
    id: number
    name: string
    description: string
  }
}

export interface EffectivePermission {
  permission: Permission
  granted: boolean
  source: 'direct' | 'role'
  source_name: string
  expires_at?: string
}

export interface PermissionAssignment {
  user_id?: number
  role_id?: number
  permission_ids: number[]
  granted: boolean
  expires_at?: string
}

export interface PermissionFilters {
  search?: string
  module?: string
  action?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

export function usePermissions() {
  const permissions: Ref<Permission[]> = ref([])
  const permission: Ref<Permission | null> = ref(null)
  const userPermissions: Ref<UserPermission[]> = ref([])
  const rolePermissions: Ref<RolePermission[]> = ref([])
  const effectivePermissions: Ref<EffectivePermission[]> = ref([])
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

  const filters = reactive<PermissionFilters>({
    search: '',
    module: '',
    action: '',
    sort_by: 'name',
    sort_order: 'asc'
  })

  // Computed
  const hasPermissions = computed(() => permissions.value.length > 0)
  const isEmpty = computed(() => !loading.value && !hasPermissions.value)
  const totalPages = computed(() => pagination.last_page)
  const hasNextPage = computed(() => pagination.current_page < pagination.last_page)
  const hasPrevPage = computed(() => pagination.current_page > 1)

  // Group permissions by module
  const permissionsByModule = computed(() => {
    const grouped: Record<string, Permission[]> = {}
    permissions.value.forEach(permission => {
      if (!grouped[permission.module]) {
        grouped[permission.module] = []
      }
      grouped[permission.module].push(permission)
    })
    return grouped
  })

  // Get unique modules
  const modules = computed(() => {
    return [...new Set(permissions.value.map(p => p.module))].sort()
  })

  // Get unique actions
  const actions = computed(() => {
    return [...new Set(permissions.value.map(p => p.action))].sort()
  })

  // Methods
  const fetchPermissions = async (page = 1) => {
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

      const response = await $api(`/api/permissions?${params}`)
      
      permissions.value = response.data
      
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
      error.value = err.message || 'Failed to fetch permissions'
      permissions.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchPermission = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/permissions/${id}`)
      permission.value = response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch permission'
      permission.value = null
    } finally {
      loading.value = false
    }
  }

  const createPermission = async (data: Omit<Permission, 'id' | 'created_at' | 'updated_at'>) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api('/api/permissions', {
        method: 'POST',
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      // Add to permissions list
      permissions.value.push(response.data)
      
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to create permission'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updatePermission = async (id: number, data: Partial<Permission>) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/permissions/${id}`, {
        method: 'PUT',
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      // Update in permissions list
      const index = permissions.value.findIndex(p => p.id === id)
      if (index !== -1) {
        permissions.value[index] = response.data
      }

      // Update current permission if it's the same
      if (permission.value && permission.value.id === id) {
        permission.value = response.data
      }

      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to update permission'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deletePermission = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      await $api(`/api/permissions/${id}`, {
        method: 'DELETE'
      })

      // Remove from permissions list
      permissions.value = permissions.value.filter(p => p.id !== id)

      // Clear current permission if it's the same
      if (permission.value && permission.value.id === id) {
        permission.value = null
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to delete permission'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchUserPermissions = async (userId: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/permissions/user/${userId}`)
      userPermissions.value = response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch user permissions'
      userPermissions.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchRolePermissions = async (roleId: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/permissions/role/${roleId}`)
      rolePermissions.value = response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch role permissions'
      rolePermissions.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchEffectivePermissions = async (userId: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api(`/api/permissions/user/${userId}/effective`)
      effectivePermissions.value = response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch effective permissions'
      effectivePermissions.value = []
    } finally {
      loading.value = false
    }
  }

  const assignToUser = async (assignment: PermissionAssignment) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api('/api/permissions/assign-to-user', {
        method: 'POST',
        body: JSON.stringify(assignment),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to assign permissions to user'
      throw err
    } finally {
      loading.value = false
    }
  }

  const assignToRole = async (assignment: PermissionAssignment) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api('/api/permissions/assign-to-role', {
        method: 'POST',
        body: JSON.stringify(assignment),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to assign permissions to role'
      throw err
    } finally {
      loading.value = false
    }
  }

  const revokeFromUser = async (userId: number, permissionIds: number[]) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api('/api/permissions/revoke-from-user', {
        method: 'POST',
        body: JSON.stringify({
          user_id: userId,
          permission_ids: permissionIds
        }),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to revoke permissions from user'
      throw err
    } finally {
      loading.value = false
    }
  }

  const revokeFromRole = async (roleId: number, permissionIds: number[]) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api('/api/permissions/revoke-from-role', {
        method: 'POST',
        body: JSON.stringify({
          role_id: roleId,
          permission_ids: permissionIds
        }),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to revoke permissions from role'
      throw err
    } finally {
      loading.value = false
    }
  }

  const bulkAssign = async (assignments: PermissionAssignment[]) => {
    loading.value = true
    error.value = null

    try {
      const response = await $api('/api/permissions/bulk-assign', {
        method: 'POST',
        body: JSON.stringify({ assignments }),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to bulk assign permissions'
      throw err
    } finally {
      loading.value = false
    }
  }

  const checkUserPermission = async (userId: number, module: string, action: string) => {
    try {
      const response = await $api('/api/permissions/check-user-permission', {
        method: 'POST',
        body: JSON.stringify({
          user_id: userId,
          module,
          action
        }),
        headers: {
          'Content-Type': 'application/json'
        }
      })

      return response.data.has_permission
    } catch (err: any) {
      error.value = err.message || 'Failed to check user permission'
      return false
    }
  }

  const getModules = async () => {
    try {
      const response = await $api('/api/permissions/modules')
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch modules'
      return []
    }
  }

  const getActions = async () => {
    try {
      const response = await $api('/api/permissions/actions')
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch actions'
      return []
    }
  }

  const searchPermissions = async (query: string) => {
    filters.search = query
    await fetchPermissions(1)
  }

  const filterPermissions = async (newFilters: Partial<PermissionFilters>) => {
    Object.assign(filters, newFilters)
    await fetchPermissions(1)
  }

  const resetFilters = async () => {
    Object.assign(filters, {
      search: '',
      module: '',
      action: '',
      sort_by: 'name',
      sort_order: 'asc'
    })
    await fetchPermissions(1)
  }

  // Navigation
  const nextPage = () => {
    if (hasNextPage.value) {
      fetchPermissions(pagination.current_page + 1)
    }
  }

  const prevPage = () => {
    if (hasPrevPage.value) {
      fetchPermissions(pagination.current_page - 1)
    }
  }

  const goToPage = (page: number) => {
    if (page >= 1 && page <= pagination.last_page) {
      fetchPermissions(page)
    }
  }

  return {
    // State
    permissions,
    permission,
    userPermissions,
    rolePermissions,
    effectivePermissions,
    loading,
    error,
    pagination,
    filters,

    // Computed
    hasPermissions,
    isEmpty,
    totalPages,
    hasNextPage,
    hasPrevPage,
    permissionsByModule,
    modules,
    actions,

    // Methods
    fetchPermissions,
    fetchPermission,
    createPermission,
    updatePermission,
    deletePermission,
    fetchUserPermissions,
    fetchRolePermissions,
    fetchEffectivePermissions,
    assignToUser,
    assignToRole,
    revokeFromUser,
    revokeFromRole,
    bulkAssign,
    checkUserPermission,
    getModules,
    getActions,
    searchPermissions,
    filterPermissions,
    resetFilters,

    // Navigation
    nextPage,
    prevPage,
    goToPage
  }
}
