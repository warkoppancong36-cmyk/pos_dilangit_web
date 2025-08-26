<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import SupplierDialog from '@/components/suppliers/SupplierDialog.vue'
import SupplierSearchFilters from '@/components/suppliers/SupplierSearchFilters.vue'
import SupplierStatsCards from '@/components/suppliers/SupplierStatsCards.vue'
import SupplierTable from '@/components/suppliers/SupplierTable.vue'
import { useSuppliers } from '@/composables/useSuppliers'
import { onMounted } from 'vue'

const {
  suppliersList,
  cities,
  provinces,
  loading,
  saveLoading,
  deleteLoading,
  toggleLoading,
  stats,
  dialog,
  deleteDialog,
  editMode,
  selectedSupplier,
  selectedSuppliers,
  currentPage,
  totalItems,
  itemsPerPage,
  filters,
  errorMessage,
  successMessage,
  modalErrorMessage,
  formData,
  canCreateEdit,
  hasSelectedSuppliers,
  fetchSuppliersList,
  fetchCities,
  fetchProvinces,
  fetchStats,
  saveSupplier,
  deleteSupplier,
  toggleActiveStatus,
  openCreateDialog,
  openEditDialog,
  openDeleteDialog,
  closeDialog,
  clearModalError,
  onPageChange,
  handleFiltersUpdate,
} = useSuppliers()

const confirmDelete = async () => {
  await deleteSupplier()
}

onMounted(() => {
  fetchSuppliersList()
  fetchCities()
  fetchProvinces()
  fetchStats()
})
</script>

<template>
  <div class="supplier-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Kelola Supplier</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola data supplier dan vendor untuk coffee shop Anda</p>
      </div>
      <div class="d-flex gap-3 align-center">
        <div v-if="hasSelectedSuppliers" class="d-flex gap-2">
          <VChip color="primary" size="small">
            {{ selectedSuppliers.length }} dipilih
          </VChip>
        </div>
        <VBtn
          v-if="canCreateEdit"
          color="primary"
          variant="elevated"
          prepend-icon="tabler-plus"
          @click="openCreateDialog"
        >
          Tambah Supplier
        </VBtn>
      </div>
    </div>

    <!-- Stats Cards -->
    <SupplierStatsCards
      :stats="stats"
      :loading="loading"
      class="mb-6"
    />

    <!-- Search and Filters -->
    <SupplierSearchFilters
      :filters="filters"
      :cities="cities"
      :provinces="provinces"
      :loading="loading"
      @update:filters="handleFiltersUpdate"
      @search="fetchSuppliersList"
      @reset="fetchSuppliersList"
      class="mb-6"
    />

    <!-- Success/Error Messages -->
    <VAlert
      v-if="successMessage"
      type="success"
      variant="tonal"
      closable
      class="mb-4"
      @click:close="successMessage = ''"
    >
      {{ successMessage }}
    </VAlert>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      closable
      class="mb-4"
      @click:close="errorMessage = ''"
    >
      {{ errorMessage }}
    </VAlert>

    <!-- Suppliers Table -->
    <VCard>
      <VCardText class="pa-0">
        <SupplierTable
          :suppliers="suppliersList"
          :loading="loading"
          :toggle-loading="toggleLoading"
          :current-page="currentPage"
          :total-items="totalItems"
          :items-per-page="itemsPerPage"
          :selected-suppliers="selectedSuppliers"
          :can-create-edit="canCreateEdit"
          @edit="openEditDialog"
          @delete="openDeleteDialog"
          @toggle-active="toggleActiveStatus"
          @page-change="onPageChange"
        />
      </VCardText>
    </VCard>

    <!-- Create/Edit Dialog -->
    <SupplierDialog
      v-model="dialog"
      :supplier="selectedSupplier"
      :loading="saveLoading"
      :error-message="modalErrorMessage"
      @save="saveSupplier"
      @close="closeDialog"
      @clear-error="clearModalError"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      :loading="deleteLoading"
      :item-name="selectedSupplier?.name"
      item-type="supplier"
      @confirm="confirmDelete"
    />
  </div>
</template>

<style scoped lang="scss">
.supplier-management {
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
