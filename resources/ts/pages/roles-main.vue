<template>
  <div>
    <!-- Header Section -->
    <VCard class="mb-6">
      <VCardTitle class="d-flex justify-space-between align-center pa-6">
        <div>
          <h1 class="text-h4 font-weight-bold mb-2">
            <VIcon icon="tabler-users-group" class="me-3 text-primary" />
            Role Management
          </h1>
          <p class="text-subtitle-1 text-medium-emphasis">
            Kelola role pengguna dan izin akses sistem
          </p>
        </div>
        <div class="d-flex gap-3">
          <VBtn
            color="primary"
            prepend-icon="tabler-plus"
            @click="showCreateModal = true"
          >
            Tambah Role
          </VBtn>
          <VBtn
            variant="outlined"
            color="secondary"
            prepend-icon="tabler-refresh"
            :loading="loading"
            @click="refreshData"
          >
            Refresh
          </VBtn>
        </div>
      </VCardTitle>
    </VCard>

    <!-- Statistics Cards -->
    <VRow class="mb-6">
      <VCol cols="12" md="4">
        <VCard>
          <VCardText class="text-center pa-6">
            <VAvatar size="56" color="primary" class="mb-4">
              <VIcon icon="tabler-users-group" size="28" />
            </VAvatar>
            <div class="text-h4 font-weight-bold">{{ roles.length }}</div>
            <div class="text-subtitle-2 text-medium-emphasis">Total Roles</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" md="4">
        <VCard>
          <VCardText class="text-center pa-6">
            <VAvatar size="56" color="success" class="mb-4">
              <VIcon icon="tabler-check-circle" size="28" />
            </VAvatar>
            <div class="text-h4 font-weight-bold">{{ activeRolesCount }}</div>
            <div class="text-subtitle-2 text-medium-emphasis">Active Roles</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" md="4">
        <VCard>
          <VCardText class="text-center pa-6">
            <VAvatar size="56" color="info" class="mb-4">
              <VIcon icon="tabler-shield-check" size="28" />
            </VAvatar>
            <div class="text-h4 font-weight-bold">{{ permissionsCount }}</div>
            <div class="text-subtitle-2 text-medium-emphasis">Total Permissions</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Search and Filter Section -->
    <VCard class="mb-6">
      <VCardTitle class="pa-6 pb-4">
        <VIcon icon="tabler-filter" class="me-2" />
        Filter & Pencarian
      </VCardTitle>
      <VCardText class="pa-6 pt-0">
        <VRow>
          <!-- Search Field -->
          <VCol cols="12" md="6">
            <VTextField
              v-model="searchQuery"
              label="Cari role..."
              prepend-inner-icon="tabler-search"
              variant="outlined"
              density="compact"
              clearable
              @input="debounceSearch"
            />
          </VCol>
          
          <!-- Status Filter -->
          <VCol cols="12" md="4">
            <VSelect
              v-model="statusFilter"
              label="Status"
              :items="[
                { title: 'Active', value: 'active' },
                { title: 'Inactive', value: 'inactive' }
              ]"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="applyFilters"
            >
              <template #prepend-item>
                <VListItem title="Semua Status" value="" />
                <VDivider />
              </template>
            </VSelect>
          </VCol>
          
          <!-- Actions -->
          <VCol cols="12" md="2">
            <VBtn
              variant="outlined"
              color="secondary"
              @click="clearFilters"
              block
            >
              Clear
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Content Area -->
    <VCard>
      <!-- Loading State -->
      <div v-if="loading && !hasRoles" class="text-center pa-12">
        <VProgressCircular
          indeterminate
          color="primary"
          size="64"
        />
        <div class="text-h6 mt-4">Memuat roles...</div>
      </div>

      <!-- Empty State -->
      <div v-else-if="isEmpty" class="text-center pa-12">
        <VAvatar size="120" color="grey-lighten-3" class="mb-6">
          <VIcon icon="tabler-users-group" size="60" color="grey-darken-1" />
        </VAvatar>
        <h3 class="text-h5 font-weight-bold mb-4">Tidak ada roles ditemukan</h3>
        <p class="text-body-1 text-medium-emphasis mb-6">
          {{ searchQuery ? 'Tidak ada roles yang sesuai dengan pencarian Anda' : 'Mulai dengan menambahkan role pertama' }}
        </p>
        <VBtn 
          v-if="!searchQuery"
          color="primary"
          prepend-icon="tabler-plus"
          @click="showCreateModal = true"
        >
          Tambah Role Pertama
        </VBtn>
      </div>

      <!-- Roles Table -->
      <div v-else>
        <VDataTable
          :headers="tableHeaders"
          :items="filteredRoles"
          :loading="loading"
          item-value="id"
          <!-- Loading State -->
          <template #loading>
            <div class="text-center pa-6">
              <VProgressCircular
                indeterminate
                color="primary"
                size="40"
              />
              <div class="text-body-2 mt-2">Memuat roles...</div>
            </div>
          </template>

          <!-- Table Row Template -->
          <template #item="{ item }">
            <tr>
              <td class="pa-4">
                <div class="d-flex align-center">
                  <VAvatar color="primary" class="me-3">
                    <VIcon icon="tabler-users-group" />
                  </VAvatar>
                  <div>
                    <div class="text-body-1 font-weight-medium">{{ item.display_name || item.name }}</div>
                    <div class="text-body-2 text-medium-emphasis">{{ item.description }}</div>
                  </div>
                </div>
              </td>
              <td class="pa-4">
                <div class="d-flex align-center">
                  <span class="text-body-1">{{ item.users_count || 0 }}</span>
                  <VBtn
                    v-if="(item.users_count ?? 0) > 0"
                    variant="text"
                    size="small"
                    color="primary"
                    class="ms-2"
                    @click="viewRoleUsers(item)"
                  >
                    Lihat
                  </VBtn>
                </div>
              </td>
              <td class="pa-4">
                <div class="d-flex align-center">
                  <span class="text-body-1">{{ item.permissions_count || 0 }}</span>
                  <VBtn
                    variant="text"
                    size="small"
                    color="primary"
                    class="ms-2"
                    @click="managePermissions(item)"
                  >
                    Kelola
                  </VBtn>
                </div>
              </td>
              <td class="pa-4">
                <VChip
                  :color="item.is_active ? 'success' : 'error'"
                  variant="tonal"
                  size="small"
                >
                  {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </VChip>
              </td>
              <td class="pa-4">
                <span class="text-body-2 text-medium-emphasis">{{ formatDate(item.created_at) }}</span>
              </td>
              <td class="pa-4">
                <div class="d-flex align-center justify-end">
                  <VTooltip text="Edit Role">
                    <template #activator="{ props }">
                      <VBtn
                        v-bind="props"
                        icon="tabler-edit"
                        variant="text"
                        size="small"
                        color="primary"
                        @click="editRole(item)"
                      />
                    </template>
                  </VTooltip>
                  
                  <VTooltip :text="item.is_active ? 'Nonaktifkan' : 'Aktifkan'">
                    <template #activator="{ props }">
                      <VBtn
                        v-bind="props"
                        :icon="item.is_active ? 'tabler-x' : 'tabler-check'"
                        variant="text"
                        size="small"
                        :color="item.is_active ? 'error' : 'success'"
                        @click="toggleRoleStatus(item)"
                      />
                    </template>
                  </VTooltip>
                  
                  <VTooltip text="Hapus Role">
                    <template #activator="{ props }">
                      <VBtn
                        v-bind="props"
                        icon="tabler-trash"
                        variant="text"
                        size="small"
                        color="error"
                        :disabled="(item.users_count ?? 0) > 0"
                        @click="deleteRoleConfirm(item)"
                      />
                    </template>
                  </VTooltip>
                </div>
              </td>
            </tr>
          </template>
        </VDataTable>
      </div>
    </VCard>

    <!-- Modals and Dialogs -->

    <!-- Create/Edit Role Modal -->
    <RoleFormModal
      :model-value="showCreateModal || showEditModal"
      :role="selectedRole"
      :mode="showEditModal ? 'edit' : 'create'"
      @update:model-value="closeModal"
      @saved="onRoleSaved"
    />

    <!-- Permission Management Modal -->
    <PermissionManagementModal
      :model-value="showPermissionModal"
      :role="selectedRole"
      @update:model-value="val => showPermissionModal = val"
      @updated="onPermissionsUpdated"
    />

    <!-- Delete Confirmation -->
    <SimpleConfirmDialog
      :show="showDeleteConfirm"
      title="Delete Role"
      :message="`Are you sure you want to delete the role '${roleToDelete?.display_name || roleToDelete?.name}'? This action cannot be undone.`"
      confirm-text="Delete"
      confirm-class="bg-red-600 hover:bg-red-700"
      @confirm="confirmDelete"
      @cancel="showDeleteConfirm = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useRoles } from '@/composables/useRoles'
import { usePermissions } from '@/composables/usePermissions'
import type { Role } from '@/composables/useRoles'
import RoleFormModal from '@/components/roles/RoleFormModal.vue'
import PermissionManagementModal from '@/components/roles/PermissionManagementModal.vue'
import SimpleConfirmDialog from '@/components/common/SimpleConfirmDialog.vue'

// Composables
const {
  roles,
  loading: rolesLoading,
  fetchRoles,
  deleteRole,
  updateRole
} = useRoles()

const {
  permissions,
  fetchPermissions
} = usePermissions()

// State
const searchQuery = ref('')
const statusFilter = ref('')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showPermissionModal = ref(false)
const showDeleteConfirm = ref(false)
const selectedRole = ref<Role | null>(null)
const roleToDelete = ref<Role | null>(null)

// Table headers for VDataTable
const tableHeaders = [
  { title: 'Role', key: 'name', sortable: true },
  { title: 'Users', key: 'users_count', sortable: true },
  { title: 'Permissions', key: 'permissions_count', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Created', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Computed
const loading = computed(() => rolesLoading.value)

const hasRoles = computed(() => Array.isArray(roles.value) && roles.value.length > 0)

const isEmpty = computed(() => {
  return !loading.value && !hasRoles.value
})

const filteredRoles = computed(() => {
  if (!Array.isArray(roles.value)) return []
  
  let filtered = roles.value
  
  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(role => 
      role.name.toLowerCase().includes(query) ||
      role.display_name?.toLowerCase().includes(query) ||
      role.description?.toLowerCase().includes(query)
    )
  }
  
  // Apply status filter
  if (statusFilter.value) {
    if (statusFilter.value === 'active') {
      filtered = filtered.filter(role => role.is_active)
    } else if (statusFilter.value === 'inactive') {
      filtered = filtered.filter(role => !role.is_active)
    }
  }
  
  return filtered
})

const activeRolesCount = computed(() => 
  Array.isArray(roles.value) ? roles.value.filter(role => role.is_active).length : 0
)

const permissionsCount = computed(() => 
  Array.isArray(permissions.value) ? permissions.value.length : 0
)

// Methods
const refreshData = async () => {
  await Promise.all([
    fetchRoles(),
    fetchPermissions()
  ])
}

const debounceSearch = useDebounceFn(() => {
  // Search is automatically handled by computed property
}, 300)

const applyFilters = () => {
  // Filters are automatically applied by computed property
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
}

const editRole = (role: Role) => {
  selectedRole.value = role
  showEditModal.value = true
}

const managePermissions = (role: Role) => {
  selectedRole.value = role
  showPermissionModal.value = true
}

const deleteRoleConfirm = (role: Role) => {
  roleToDelete.value = role
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  if (roleToDelete.value) {
    await deleteRole(roleToDelete.value.id)
    showDeleteConfirm.value = false
    roleToDelete.value = null
  }
}

const toggleRoleStatus = async (role: Role) => {
  await updateRole(role.id, {
    is_active: !role.is_active
  })
}

const viewRoleUsers = (role: Role) => {
  // Navigate to users page with role filter
  // This could be implemented based on your routing setup
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  selectedRole.value = null
}

const onRoleSaved = () => {
  closeModal()
  refreshData()
}

const onPermissionsUpdated = () => {
  showPermissionModal.value = false
  selectedRole.value = null
  refreshData()
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  refreshData()
})
</script>

<style scoped>
.page-header {
  margin-bottom: 1.5rem;
}

.role-management {
  padding: 1.5rem;
  max-width: 80rem;
  margin-left: auto;
  margin-right: auto;
}
</style>
