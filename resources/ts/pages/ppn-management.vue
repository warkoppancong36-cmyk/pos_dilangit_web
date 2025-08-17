<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import PpnDialog from '@/components/ppn/PpnDialog.vue'
import PpnSearchFilters from '@/components/ppn/PpnSearchFilters.vue'
import PpnStatsCards from '@/components/ppn/PpnStatsCards.vue'
import PpnTable from '@/components/ppn/PpnTable.vue'
import { usePpn } from '@/composables/usePpn'
import { onMounted } from 'vue'

// Import styles
// import '@styles/index.scss'

// Use PPN composable
const {
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
} = usePpn()

// Event handlers for filters
const handleSearchUpdate = (value: string) => {
  filters.value.search = value
}

const handleActiveFilterUpdate = (value: 'all' | 'active' | 'inactive') => {
  filters.value.active = value
  onFilterChange()
}

const handleSortByUpdate = (value: string) => {
  filters.value.sortBy = value
  onSortChange()
}

const handleSortOrderUpdate = (value: 'asc' | 'desc') => {
  filters.value.sortOrder = value
  onSortChange()
}

// Confirm delete action
const confirmDelete = async () => {
  await deletePpn()
}

onMounted(() => {
  fetchPpnList()
})
</script>

<template>
  <div class="ppn-management">
    <!-- Page Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Kelola PPN</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola tarif pajak (PPN) untuk bisnis Anda</p>
      </div>
      <VBtn
        v-if="canCreateEdit"
        color="primary"
        prepend-icon="mdi-plus"
        class="coffee-btn"
        @click="openCreateDialog"
      >
        Tambah PPN Baru
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
    <PpnStatsCards
      :total-ppn="ppnList.length"
      :active-ppn="totalActivePpn"
      :inactive-ppn="totalInactivePpn"
      :average-rate="averageNominal"
    />

    <!-- Search and Filters -->
    <PpnSearchFilters
      :search-value="filters.search"
      :active-filter="filters.active"
      :sort-by="filters.sortBy"
      :sort-order="filters.sortOrder"
      @update:search="handleSearchUpdate"
      @update:active-filter="handleActiveFilterUpdate"
      @update:sort-by="handleSortByUpdate"
      @update:sort-order="handleSortOrderUpdate"
      @search="onSearch"
      @refresh="fetchPpnList"
    />

    <!-- PPN Table -->
    <PpnTable
      :ppn-list="ppnList"
      :loading="loading"
      :current-page="currentPage"
      :total-items="totalItems"
      :items-per-page="itemsPerPage"
      :can-create-edit="canCreateEdit"
      :can-delete="canDelete"
      :toggle-loading="toggleLoading"
      @page-change="onPageChange"
      @edit="openEditDialog"
      @delete="openDeleteDialog"
      @toggle="toggleActiveStatus"
    />

    <!-- Create/Edit Dialog -->
    <PpnDialog
      v-model="dialog"
      :edit-mode="editMode"
      :form-data="formData"
      :loading="saveLoading"
      :error-message="modalErrorMessage"
      @save="savePpn"
      @cancel="closeDialog"
      @clear-error="clearModalError"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus PPN"
      :item-name="selectedPpn?.name"
      :loading="deleteLoading"
      confirm-text="Hapus PPN"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    />
  </div>
</template>

<style scoped lang="scss">
.ppn-management {
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

