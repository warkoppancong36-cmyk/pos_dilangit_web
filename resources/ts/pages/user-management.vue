<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import UserDialog from '@/components/users/UserDialog.vue'
import UserDetailsDialog from '@/components/users/UserDetailsDialog.vue'
import UserSearchFilters from '@/components/users/UserSearchFilters.vue'
import UserStatsCards from '@/components/users/UserStatsCards.vue'
import UserTable from '@/components/users/UserTable.vue'
import { useUsers } from '@/composables/useUserManagement'

// Use Users composable
const {
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
} = useUsers()

// Additional state for user details dialog
const userDetailsDialog = ref(false)
const selectedUserForDetails = ref<any>(null)

// Event handlers for filters
const handleSearchUpdate = (value: string) => {
  filters.value.search = value
}

const handleStatusFilterUpdate = (value: 'all' | 'active' | 'inactive') => {
  filters.value.status = value
  onFilterChange()
}

const handleRoleFilterUpdate = (value?: number) => {
  filters.value.role_id = value
  onFilterChange()
}

const handleSortByUpdate = (value: string) => {
  filters.value.sort_by = value
  onSortChange()
}

const handleSortOrderUpdate = (value: 'asc' | 'desc') => {
  filters.value.sort_order = value
  onSortChange()
}

// User actions
const viewUser = (user: any) => {
  selectedUserForDetails.value = user
  userDetailsDialog.value = true
}

const confirmDelete = async () => {
  await deleteUser()
}

const handleBulkDelete = async (userIds: number[]) => {
  if (confirm(`Apakah Anda yakin ingin menghapus ${userIds.length} pengguna yang dipilih?`)) {
    await bulkDeleteUsers(userIds)
  }
}

onMounted(async () => {
  await fetchRoles()
  await fetchUsers()
})
</script>

<template>
  <div class="user-management">
    <!-- Page Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">User Management</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola pengguna sistem dan hak akses mereka</p>
      </div>
      <VBtn
        v-if="canCreateEdit"
        color="primary"
        prepend-icon="tabler-plus"
        class="coffee-btn"
        @click="openCreateDialog"
      >
        Tambah Pengguna Baru
      </VBtn>
    </div>

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
    <UserStatsCards
      :total-users="users.length"
      :active-users="totalActiveUsers"
      :inactive-users="totalInactiveUsers"
      :total-roles="roles.length"
    />

    <!-- Search and Filters -->
    <UserSearchFilters
      :search-value="filters.search || ''"
      :status-filter="filters.status || 'all'"
      :role-filter="filters.role_id"
      :sort-by="filters.sort_by || 'created_at'"
      :sort-order="filters.sort_order || 'desc'"
      :role-options="roles"
      @update:search="handleSearchUpdate"
      @update:status-filter="handleStatusFilterUpdate"
      @update:role-filter="handleRoleFilterUpdate"
      @update:sort-by="handleSortByUpdate"
      @update:sort-order="handleSortOrderUpdate"
      @search="onSearch"
      @refresh="fetchUsers"
    />

    <!-- Users Table -->
    <UserTable
      :user-list="users"
      :loading="loading"
      :current-page="currentPage"
      :total-items="totalItems"
      :items-per-page="itemsPerPage"
      :can-create-edit="canCreateEdit"
      :can-delete="canDelete"
      :toggle-loading="toggleLoading"
      @page-change="onPageChange"
      @view="viewUser"
      @edit="openEditDialog"
      @delete="openDeleteDialog"
      @toggle="toggleActiveStatus"
      @bulk-delete="handleBulkDelete"
    />

    <!-- Create/Edit Dialog -->
    <UserDialog
      v-model="dialog"
      :edit-mode="editMode"
      :form-data="formData"
      :loading="saveLoading"
      :error-message="modalErrorMessage"
      :role-options="roles"
      :name-rules="nameRules"
      :username-rules="usernameRules"
      :email-rules="emailRules"
      :password-rules="passwordRules"
      :role-rules="roleRules"
      @save="saveUser"
      @cancel="closeDialog"
      @clear-error="clearModalError"
    />

    <!-- User Details Dialog -->
    <UserDetailsDialog
      v-model:show="userDetailsDialog"
      :user="selectedUserForDetails"
      @edit="openEditDialog"
      @delete="openDeleteDialog"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus Pengguna"
      :item-name="selectedUser?.name"
      :loading="deleteLoading"
      confirm-text="Hapus Pengguna"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    />
  </div>
</template>

<style scoped lang="scss">
.user-management {
  .coffee-title {
    color: rgb(var(--v-theme-primary));
    font-family: 'Inter', sans-serif;
  }

  .coffee-subtitle {
    opacity: 0.8;
    margin-top: 4px;
  }
}
</style>
