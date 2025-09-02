<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  name: roles
</route>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import RoleSearchFilters from '@/components/roles/RoleSearchFilters.vue'
import axios from 'axios'

// Types
interface Role {
  id: number
  name: string
  display_name?: string
  description?: string
  is_active: boolean
  permissions_count?: number
  users_count?: number
  created_at?: string
  updated_at?: string
}

// State
const loading = ref(false)
const saveLoading = ref(false)
const deleteLoading = ref(false)
const toggleLoading = ref(false)

const roles = ref<Role[]>([])
const permissions = ref<any[]>([])
const rolePermissions = ref<any[]>([])
const selectedPermissions = ref<number[]>([])
const permissionSearch = ref('')

// Filter states
const filters = ref({
  search: '',
  status: 'all' as 'all' | 'active' | 'inactive',
  permission_filter: 'all',
  sort_by: 'created_at',
  sort_order: 'desc' as 'asc' | 'desc'
})

// Dialog states
const dialog = ref(false)
const deleteDialog = ref(false)
const permissionsDialog = ref(false)
const usersDialog = ref(false)
const editMode = ref(false)
const selectedRole = ref<Role | null>(null)
const permissionsLoading = ref(false)
const savePermissionsLoading = ref(false)

// Messages
const errorMessage = ref('')
const successMessage = ref('')
const modalErrorMessage = ref('')

// Form data
const formData = ref({
  name: '',
  display_name: '',
  description: '',
  is_active: true,
  permissions: [] as number[]
})

// Validation rules
const nameRules = [
  (v: string) => !!v || 'Nama role wajib diisi',
  (v: string) => v.length >= 3 || 'Nama role minimal 3 karakter'
]

const displayNameRules = [
  (v: string) => !!v || 'Display name wajib diisi'
]

// Computed
const totalActiveRoles = computed(() => 
  roles.value.filter(role => role.is_active).length
)

const totalInactiveRoles = computed(() => 
  roles.value.filter(role => !role.is_active).length
)

const totalPermissions = computed(() => 
  roles.value.reduce((sum, role) => sum + (role.permissions_count || 0), 0)
)

const adminRoles = computed(() => 
  roles.value.filter(role => 
    (role.display_name || role.name)?.toLowerCase().includes('admin')
  ).length
)

// Computed properties
const filteredRoles = computed(() => {
  let filtered = [...roles.value]
  
  // Search filter
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(role => 
      (role.name?.toLowerCase().includes(search)) ||
      (role.display_name?.toLowerCase().includes(search)) ||
      (role.description?.toLowerCase().includes(search))
    )
  }
  
  // Status filter
  if (filters.value.status !== 'all') {
    const isActive = filters.value.status === 'active'
    filtered = filtered.filter(role => role.is_active === isActive)
  }
  
  // Permission filter
  if (filters.value.permission_filter === 'with_permissions') {
    filtered = filtered.filter(role => (role.permissions_count || 0) > 0)
  } else if (filters.value.permission_filter === 'without_permissions') {
    filtered = filtered.filter(role => (role.permissions_count || 0) === 0)
  }
  
  // Sorting
  filtered.sort((a, b) => {
    const aValue = a[filters.value.sort_by as keyof Role] || 0
    const bValue = b[filters.value.sort_by as keyof Role] || 0
    
    if (filters.value.sort_order === 'asc') {
      return aValue > bValue ? 1 : -1
    } else {
      return aValue < bValue ? 1 : -1
    }
  })
  
  return filtered
})

// Permission computed properties
const filteredPermissions = computed(() => {
  if (!permissionSearch.value) return permissions.value
  
  const search = permissionSearch.value.toLowerCase()
  return permissions.value.filter(permission => 
    (permission.name?.toLowerCase().includes(search)) ||
    (permission.display_name?.toLowerCase().includes(search)) ||
    (permission.description?.toLowerCase().includes(search))
  )
})

const groupedPermissions = computed(() => {
  const groups: { [key: string]: any[] } = {}
  
  filteredPermissions.value.forEach(permission => {
    // Group by permission prefix (e.g., 'users.view' -> 'users')
    const category = permission.name?.split('.')[0] || 'other'
    
    if (!groups[category]) {
      groups[category] = []
    }
    groups[category].push(permission)
  })
  
  // Convert to array with icons
  return Object.entries(groups).map(([category, perms]) => ({
    category: category.charAt(0).toUpperCase() + category.slice(1),
    permissions: perms,
    icon: getPermissionIcon(category)
  }))
})

const getPermissionIcon = (category: string) => {
  const iconMap: { [key: string]: string } = {
    users: 'tabler-users',
    roles: 'tabler-users-group',
    products: 'tabler-box',
    categories: 'tabler-category',
    transactions: 'tabler-cash',
    reports: 'tabler-chart-bar',
    settings: 'tabler-settings',
    logs: 'tabler-history',
    inventory: 'tabler-package',
    suppliers: 'tabler-truck-delivery',
    customers: 'tabler-users',
    other: 'tabler-dots'
  }
  
  return iconMap[category] || 'tabler-shield'
}

// Functions
const fetchRoles = async () => {
  loading.value = true
  errorMessage.value = ''
  
  try {
    console.log('Fetching roles with axios...')
    const response = await axios.get('/api/roles')
    
    console.log('Roles response:', response.data)
    
    if (response.data.success) {
      roles.value = response.data.data || []
      if (roles.value.length === 0) {
        console.log('No roles found in database')
      }
    } else {
      throw new Error(response.data.message || 'API returned unsuccessful response')
    }
  } catch (error: any) {
    console.error('Failed to fetch roles:', error)
    errorMessage.value = error.response?.data?.message || error.message || 'Gagal memuat data roles'
    // Set empty array to show "no data" state instead of error
    roles.value = []
  } finally {
    loading.value = false
  }
}

const fetchPermissions = async () => {
  try {
    console.log('Fetching permissions...')
    const response = await axios.get('/api/permissions')
    
    console.log('Permissions response:', response.data)
    
    if (response.data.success) {
      permissions.value = response.data.data || []
      console.log('Permissions loaded:', permissions.value.length)
    } else {
      permissions.value = []
      console.warn('Permissions API returned unsuccessful response')
    }
  } catch (error: any) {
    console.error('Failed to fetch permissions:', error)
    permissions.value = []
    errorMessage.value = error.response?.data?.message || 'Gagal memuat permissions'
  }
}

const fetchRolePermissions = async (roleId: number) => {
  permissionsLoading.value = true
  try {
    console.log('Fetching permissions for role:', roleId)
    const response = await axios.get(`/api/roles/${roleId}/permissions`)
    
    console.log('Role permissions response:', response.data)
    
    if (response.data.success) {
      // response.data.data contains ONLY the permissions assigned to this role
      rolePermissions.value = response.data.data
      
      // Map to get the IDs of permissions that this role currently has
      selectedPermissions.value = response.data.data.map((p: any) => {
        // Try different possible ID fields from the permission object
        return p.id_permission || p.id
      })
      
      console.log('Assigned permissions for this role:', selectedPermissions.value)
      console.log('Total available permissions:', permissions.value.length)
    }
  } catch (error: any) {
    console.error('Failed to fetch role permissions:', error)
    errorMessage.value = error.response?.data?.message || 'Gagal memuat permissions role'
    // Reset to empty if error - no permissions assigned
    selectedPermissions.value = []
  } finally {
    permissionsLoading.value = false
  }
}

const saveRolePermissions = async () => {
  if (!selectedRole.value) return
  
  savePermissionsLoading.value = true
  try {
    const response = await axios.post(`/api/roles/${selectedRole.value.id}/permissions/sync`, {
      permissions: selectedPermissions.value
    })
    
    if (response.data.success) {
      successMessage.value = 'Permissions berhasil diperbarui'
      await fetchRoles() // Refresh roles list
      closePermissionsDialog()
    } else {
      throw new Error(response.data.message || 'Failed to sync permissions')
    }
  } catch (error: any) {
    console.error('Failed to sync permissions:', error)
    errorMessage.value = error.response?.data?.message || 'Gagal menyimpan permissions'
  } finally {
    savePermissionsLoading.value = false
  }
}

const saveRole = async () => {
  saveLoading.value = true
  modalErrorMessage.value = ''
  
  try {
    const payload = { ...formData.value }

    let response
    if (editMode.value && selectedRole.value) {
      response = await axios.put(`/api/roles/${selectedRole.value.id}`, payload)
    } else {
      response = await axios.post('/api/roles', payload)
    }

    if (response.data.success) {
      successMessage.value = editMode.value ? 'Role berhasil diperbarui' : 'Role berhasil ditambahkan'
      await fetchRoles() // Refresh the list
      closeDialog()
    } else {
      modalErrorMessage.value = response.data.message || 'Gagal menyimpan role'
    }
  } catch (error: any) {
    console.error('Failed to save role:', error)
    if (error.response?.data?.errors) {
      modalErrorMessage.value = Object.values(error.response.data.errors).flat().join(', ')
    } else {
      modalErrorMessage.value = error.response?.data?.message || 'Gagal menyimpan role'
    }
  } finally {
    saveLoading.value = false
  }
}

const deleteRole = async () => {
  if (!selectedRole.value) return
  
  deleteLoading.value = true
  try {
    const response = await axios.delete(`/api/roles/${selectedRole.value.id}`)
    
    if (response.data.success) {
      successMessage.value = 'Role berhasil dihapus'
      await fetchRoles() // Refresh the list
    } else {
      errorMessage.value = response.data.message || 'Gagal menghapus role'
    }
    
    deleteDialog.value = false
    selectedRole.value = null
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Gagal menghapus role'
    console.error('Failed to delete role:', error)
  } finally {
    deleteLoading.value = false
  }
}

const toggleActiveStatus = async (role: Role) => {
  toggleLoading.value = true
  try {
    const response = await axios.post(`/api/roles/${role.id}/toggle-active`)
    
    if (response.data.success) {
      successMessage.value = response.data.message || `Role ${response.data.data.is_active ? 'diaktifkan' : 'dinonaktifkan'}`
      await fetchRoles() // Refresh the list
    } else {
      errorMessage.value = response.data.message || 'Gagal mengubah status role'
    }
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengubah status role'
    console.error('Failed to toggle role status:', error)
  } finally {
    toggleLoading.value = false
  }
}

const openCreateDialog = () => {
  editMode.value = false
  selectedRole.value = null
  formData.value = {
    name: '',
    display_name: '',
    description: '',
    is_active: true,
    permissions: []
  }
  modalErrorMessage.value = ''
  dialog.value = true
}

const openEditDialog = (role: Role) => {
  editMode.value = true
  selectedRole.value = role
  formData.value = {
    name: role.name,
    display_name: role.display_name || '',
    description: role.description || '',
    is_active: role.is_active,
    permissions: [] // Would need to fetch role permissions
  }
  modalErrorMessage.value = ''
  dialog.value = true
}

const openDeleteDialog = (role: Role) => {
  selectedRole.value = role
  deleteDialog.value = true
}

const openPermissionsDialog = async (role: Role) => {
  console.log('Opening permissions dialog for role:', role)
  selectedRole.value = role
  selectedPermissions.value = [] // Clear first
  permissionsDialog.value = true
  await fetchRolePermissions(role.id)
}

const openUsersDialog = (role: Role) => {
  selectedRole.value = role
  usersDialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  editMode.value = false
  selectedRole.value = null
  modalErrorMessage.value = ''
}

const closePermissionsDialog = () => {
  console.log('Closing permissions dialog')
  permissionsDialog.value = false
  selectedRole.value = null
  selectedPermissions.value = []
  rolePermissions.value = []
  permissionSearch.value = ''
}

const togglePermission = (permissionId: number) => {
  console.log('Toggling permission:', permissionId)
  const index = selectedPermissions.value.indexOf(permissionId)
  if (index > -1) {
    selectedPermissions.value.splice(index, 1)
  } else {
    selectedPermissions.value.push(permissionId)
  }
  console.log('Selected permissions:', selectedPermissions.value)
}

const selectAllPermissions = () => {
  selectedPermissions.value = filteredPermissions.value.map(p => p.id_permission || p.id)
}

const clearAllPermissions = () => {
  selectedPermissions.value = []
}

// Filter handlers
const handleSearchUpdate = (value: string) => {
  filters.value.search = value
}

const handleStatusFilterUpdate = (value: 'all' | 'active' | 'inactive') => {
  filters.value.status = value
}

const handlePermissionFilterUpdate = (value: string) => {
  filters.value.permission_filter = value
}

const handleSortByUpdate = (value: string) => {
  filters.value.sort_by = value
}

const handleSortOrderUpdate = (value: 'asc' | 'desc') => {
  filters.value.sort_order = value
}

const onSearch = () => {
  // Search is reactive, no need to do anything
}

const closeUsersDialog = () => {
  usersDialog.value = false
  selectedRole.value = null
}

const clearModalError = () => {
  modalErrorMessage.value = ''
}

const formatDate = (date: string | null | undefined) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(async () => {
  await fetchPermissions()
  await fetchRoles()
})
</script>

<template>
  <div class="role-management">
    <!-- Page Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Role Management</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola role pengguna dan izin akses sistem</p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="tabler-plus"
        class="coffee-btn"
        @click="openCreateDialog"
      >
        Tambah Role Baru
      </VBtn>
    </div>

    <!-- Search and Filters -->
    <RoleSearchFilters
      :search-value="filters.search"
      :status-filter="filters.status"
      :permission-filter="filters.permission_filter"
      :sort-by="filters.sort_by"
      :sort-order="filters.sort_order"
      @update:search="handleSearchUpdate"
      @update:status-filter="handleStatusFilterUpdate"
      @update:permission-filter="handlePermissionFilterUpdate"
      @update:sort-by="handleSortByUpdate"
      @update:sort-order="handleSortOrderUpdate"
      @search="onSearch"
      @refresh="fetchRoles"
    />

    <!-- Error Alert -->
    <VAlert
      v-if="errorMessage"
      type="error"
      variant="outlined"
      class="mb-4"
      :text="errorMessage"
      closable
      @click:close="errorMessage = ''"
    />

    <!-- Success Alert -->
    <VAlert
      v-if="successMessage"
      type="success"
      variant="outlined"
      class="mb-4"
      :text="successMessage"
      closable
      @click:close="successMessage = ''"
    />

    <!-- Stats Cards -->
    <VRow class="mb-6">
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex align-center">
              <VAvatar color="primary" variant="tonal" class="me-4">
                <VIcon icon="tabler-users" />
              </VAvatar>
              <div>
                <span class="text-sm">Total Roles</span>
                <div class="text-h6 font-weight-medium">
                  {{ roles.length }}
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex align-center">
              <VAvatar color="success" variant="tonal" class="me-4">
                <VIcon icon="tabler-check" />
              </VAvatar>
              <div>
                <span class="text-sm">Active Roles</span>
                <div class="text-h6 font-weight-medium">
                  {{ totalActiveRoles }}
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex align-center">
              <VAvatar color="warning" variant="tonal" class="me-4">
                <VIcon icon="tabler-shield" />
              </VAvatar>
              <div>
                <span class="text-sm">Total Permissions</span>
                <div class="text-h6 font-weight-medium">
                  {{ totalPermissions }}
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex align-center">
              <VAvatar color="info" variant="tonal" class="me-4">
                <VIcon icon="tabler-crown" />
              </VAvatar>
              <div>
                <span class="text-sm">Admin Roles</span>
                <div class="text-h6 font-weight-medium">
                  {{ adminRoles }}
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Roles Table -->
    <VCard>
      <VCardTitle class="d-flex align-center pa-4">
        <VIcon icon="tabler-users" class="me-2" />
        Roles
        <VSpacer />
        <VBtn
          icon="tabler-refresh"
          variant="text"
          size="small"
          @click="fetchRoles"
          :loading="loading"
        />
      </VCardTitle>

      <VDivider />

      <VDataTable
        :headers="[
          { title: 'Role', key: 'display_name', sortable: true },
          { title: 'Description', key: 'description' },
          { title: 'Permissions', key: 'permissions_count', align: 'center' },
          { title: 'Users', key: 'users_count', align: 'center' },
          { title: 'Status', key: 'is_active', align: 'center' },
          { title: 'Created', key: 'created_at' },
          { title: 'Actions', key: 'actions', sortable: false, align: 'center' }
        ]"
        :items="filteredRoles"
        :loading="loading"
        class="elevation-0"
        item-key="id"
      >
        <template #item.display_name="{ item }">
          <div class="d-flex align-center">
            <VAvatar color="primary" variant="tonal" size="32" class="me-3">
              <VIcon icon="tabler-user" size="18" />
            </VAvatar>
            <div>
              <div class="font-weight-medium">{{ item.display_name || item.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.name }}</div>
            </div>
          </div>
        </template>

        <template #item.description="{ item }">
          <span class="text-body-2">{{ item.description || '-' }}</span>
        </template>

        <template #item.permissions_count="{ item }">
          <div class="d-flex align-center justify-center">
            <VChip
              size="small"
              color="primary"
              variant="tonal"
              @click="openPermissionsDialog(item)"
              class="cursor-pointer"
            >
              {{ item.permissions_count || 0 }}
            </VChip>
          </div>
        </template>

        <template #item.users_count="{ item }">
          <div class="d-flex align-center justify-center">
            <VChip
              size="small"
              :color="(item.users_count || 0) > 0 ? 'info' : 'default'"
              variant="tonal"
              @click="(item.users_count || 0) > 0 ? openUsersDialog(item) : null"
              :class="(item.users_count || 0) > 0 ? 'cursor-pointer' : ''"
            >
              {{ item.users_count || 0 }}
            </VChip>
          </div>
        </template>

        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
          </VChip>
        </template>

        <template #item.created_at="{ item }">
          <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center">
            <VBtn
              icon="tabler-edit"
              variant="text"
              size="small"
              color="primary"
              @click="openEditDialog(item)"
            />
            <VBtn
              :icon="item.is_active ? 'tabler-eye-off' : 'tabler-eye'"
              variant="text"
              size="small"
              :color="item.is_active ? 'warning' : 'success'"
              @click="toggleActiveStatus(item)"
              :loading="toggleLoading"
            />
            <VBtn
              icon="tabler-trash"
              variant="text"
              size="small"
              color="error"
              @click="openDeleteDialog(item)"
              :disabled="(item.users_count || 0) > 0"
            />
          </div>
        </template>

        <template #no-data>
          <div class="text-center pa-6">
            <VIcon icon="tabler-users" size="48" color="disabled" class="mb-4" />
            <div class="text-h6 text-medium-emphasis mb-2">Tidak ada role</div>
            <div class="text-body-2 text-medium-emphasis">Mulai dengan menambahkan role pertama Anda</div>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Create/Edit Role Dialog -->
    <VDialog
      v-model="dialog"
      max-width="600"
      persistent
    >
      <VCard>
        <VCardTitle class="d-flex align-center">
          <VIcon :icon="editMode ? 'tabler-edit' : 'tabler-plus'" class="me-2" />
          {{ editMode ? 'Edit Role' : 'Tambah Role Baru' }}
        </VCardTitle>

        <VDivider />

        <VCardText>
          <!-- Error Alert in Modal -->
          <VAlert
            v-if="modalErrorMessage"
            type="error"
            variant="outlined"
            class="mb-4"
            :text="modalErrorMessage"
            closable
            @click:close="clearModalError"
          />

          <VForm>
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="formData.name"
                  label="Nama Role"
                  placeholder="e.g., admin, manager"
                  :rules="nameRules"
                  variant="outlined"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="formData.display_name"
                  label="Display Name"
                  placeholder="e.g., Administrator, Manager"
                  :rules="displayNameRules"
                  variant="outlined"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="formData.description"
                  label="Deskripsi"
                  placeholder="Jelaskan fungsi dan tanggung jawab role ini"
                  variant="outlined"
                  rows="3"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="formData.is_active"
                  label="Role Aktif"
                  color="primary"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions>
          <VSpacer />
          <VBtn
            color="grey"
            variant="text"
            @click="closeDialog"
            :disabled="saveLoading"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            variant="flat"
            @click="saveRole"
            :loading="saveLoading"
          >
            {{ editMode ? 'Update' : 'Simpan' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus Role"
      :item-name="selectedRole?.display_name || selectedRole?.name"
      :loading="deleteLoading"
      confirm-text="Hapus Role"
      @confirm="deleteRole"
      @cancel="deleteDialog = false"
    />

    <!-- Permissions Management Dialog -->
    <VDialog
      v-model="permissionsDialog"
      max-width="900"
      persistent
    >
      <VCard class="permission-dialog">
        <!-- Header -->
        <VCardTitle class="pa-6 pb-4">
          <div class="d-flex align-center">
            <div class="permission-icon-wrapper me-3">
              <VIcon icon="tabler-shield-check" size="20" />
            </div>
            <div>
              <div class="text-h6 font-weight-bold mb-1">
                Kelola Permissions
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ selectedRole?.display_name || selectedRole?.name }}
              </div>
            </div>
            <VSpacer />
            <VBtn
              icon="tabler-x"
              variant="text"
              size="small"
              @click="closePermissionsDialog"
              :disabled="savePermissionsLoading"
            />
          </div>
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <div v-if="permissionsLoading" class="text-center py-12">
            <VProgressCircular indeterminate color="primary" size="40" />
            <div class="text-body-2 mt-4 text-medium-emphasis">Memuat permissions...</div>
          </div>

          <div v-else>
            <!-- Quick Stats & Search -->
            <div class="mb-6">
              <div class="d-flex align-center mb-4">
                <div class="permission-stats">
                  <VChip
                    color="primary"
                    variant="tonal"
                    size="small"
                    class="me-2"
                  >
                    <VIcon icon="tabler-check" size="16" class="me-1" />
                    {{ selectedPermissions.length }} dipilih
                  </VChip>
                  <VChip
                    color="info"
                    variant="tonal"
                    size="small"
                  >
                    <VIcon icon="tabler-shield" size="16" class="me-1" />
                    {{ permissions.length }} total
                  </VChip>
                </div>
                <VSpacer />
                <VBtn
                  v-if="selectedPermissions.length < permissions.length"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="selectAllPermissions"
                >
                  Pilih Semua
                </VBtn>
                <VBtn
                  v-if="selectedPermissions.length > 0"
                  size="small"
                  variant="text"
                  color="error"
                  @click="clearAllPermissions"
                  class="ms-2"
                >
                  Hapus Semua
                </VBtn>
              </div>

              <VTextField
                v-model="permissionSearch"
                label="Cari permission..."
                prepend-inner-icon="tabler-search"
                variant="outlined"
                density="compact"
                clearable
                hide-details
              />
            </div>

            <!-- Permissions List -->
            <div v-if="filteredPermissions.length > 0" class="permissions-grid">
              <div
                v-for="permission in filteredPermissions"
                :key="permission.id"
                class="permission-item"
              >
                <VCard
                  variant="outlined"
                  :class="{ 'permission-selected': selectedPermissions.includes(permission.id_permission || permission.id) }"
                >
                  <VCardText class="pa-4">
                    <div class="d-flex align-start">
                      <div 
                        class="permission-checkbox me-3"
                        @click="togglePermission(permission.id_permission || permission.id)"
                      >
                        <VIcon
                          :icon="selectedPermissions.includes(permission.id_permission || permission.id) ? 'tabler-checkbox' : 'tabler-square'"
                          :color="selectedPermissions.includes(permission.id_permission || permission.id) ? 'primary' : 'default'"
                          size="20"
                        />
                      </div>
                      <div class="flex-grow-1" @click="togglePermission(permission.id_permission || permission.id)">
                        <div class="text-body-1 font-weight-medium mb-1">
                          {{ permission.display_name || permission.name }}
                        </div>
                        <div class="text-caption text-medium-emphasis">
                          {{ permission.description || 'Tidak ada deskripsi' }}
                        </div>
                      </div>
                    </div>
                  </VCardText>
                </VCard>
              </div>
            </div>

            <!-- No permissions -->
            <div v-else-if="permissions.length === 0" class="text-center py-12">
              <div class="permission-empty-icon mb-4">
                <VIcon icon="tabler-shield-off" size="48" color="disabled" />
              </div>
              <div class="text-h6 text-medium-emphasis mb-2">Tidak ada permission</div>
              <div class="text-body-2 text-medium-emphasis">Permissions belum dikonfigurasi di database</div>
            </div>

            <!-- No search results -->
            <div v-else class="text-center py-8">
              <VIcon icon="tabler-search-off" size="32" color="disabled" class="mb-3" />
              <div class="text-body-1 text-medium-emphasis mb-1">Tidak ditemukan</div>
              <div class="text-caption text-medium-emphasis">Coba gunakan kata kunci lain</div>
            </div>
          </div>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-6 pt-4">
          <div class="d-flex align-center w-100">
            <div v-if="selectedPermissions.length > 0" class="permission-summary">
              <VIcon icon="tabler-info-circle" size="16" class="me-1 text-info" />
              <span class="text-caption text-medium-emphasis">
                {{ selectedPermissions.length }} permission akan disimpan
              </span>
            </div>
            
            <VSpacer />
            
            <VBtn
              color="grey-darken-1"
              variant="text"
              @click="closePermissionsDialog"
              :disabled="savePermissionsLoading"
              class="me-3"
            >
              Batal
            </VBtn>
            <VBtn
              color="primary"
              variant="flat"
              @click="saveRolePermissions"
              :loading="savePermissionsLoading"
            >
              <VIcon icon="tabler-device-floppy" class="me-2" />
              Simpan
            </VBtn>
          </div>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped lang="scss">
.role-management {
  .coffee-title {
    color: rgb(var(--v-theme-primary));
    font-family: 'Inter', sans-serif;
  }

  .coffee-subtitle {
    opacity: 0.8;
    margin-top: 4px;
  }

  .cursor-pointer {
    cursor: pointer;
  }
}

.permission-dialog {
  .permission-icon-wrapper {
    background: rgba(var(--v-theme-primary), 0.1);
    border-radius: 8px;
    padding: 8px;
    color: rgb(var(--v-theme-primary));
  }

  .permission-stats {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
    max-height: 400px;
    overflow-y: auto;
    padding-right: 4px;

    &::-webkit-scrollbar {
      width: 6px;
    }

    &::-webkit-scrollbar-track {
      background: rgba(var(--v-theme-surface-variant), 0.1);
      border-radius: 3px;
    }

    &::-webkit-scrollbar-thumb {
      background: rgba(var(--v-theme-primary), 0.2);
      border-radius: 3px;

      &:hover {
        background: rgba(var(--v-theme-primary), 0.3);
      }
    }
  }

  .permission-item {
    .v-card {
      transition: all 0.2s ease;
      cursor: pointer;
      border: 1px solid rgba(var(--v-theme-outline), 0.12);

      &:hover {
        border-color: rgba(var(--v-theme-primary), 0.3);
        box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.1);
        transform: translateY(-1px);
      }

      &.permission-selected {
        border-color: rgb(var(--v-theme-primary));
        background: rgba(var(--v-theme-primary), 0.04);
        
        &:hover {
          background: rgba(var(--v-theme-primary), 0.08);
        }
      }
    }

    .permission-checkbox {
      cursor: pointer;
      padding: 2px;
      border-radius: 4px;
      transition: background-color 0.2s ease;
      
      &:hover {
        background: rgba(var(--v-theme-primary), 0.1);
      }
    }
  }

  .permission-empty-icon {
    opacity: 0.6;
  }

  .permission-summary {
    display: flex;
    align-items: center;
    gap: 4px;
  }
}
</style>