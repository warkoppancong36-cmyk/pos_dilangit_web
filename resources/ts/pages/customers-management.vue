<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import CustomerDialog from '@/components/customers/CustomerDialog.vue'
import CustomerSearchFilters from '@/components/customers/CustomerSearchFilters.vue'
import CustomerStatsCards from '@/components/customers/CustomerStatsCards.vue'
import CustomerTable from '@/components/customers/CustomerTable.vue'
import { useCustomers, type Customer, type CustomerFormData } from '@/composables/useCustomers'
import { onMounted } from 'vue'

const {
  customersList,
  loading,
  saveLoading,
  deleteLoading,
  toggleLoading,
  stats,
  dialog,
  deleteDialog,
  editMode,
  selectedCustomer,
  selectedCustomers,
  currentPage,
  totalItems,
  itemsPerPage,
  filters,
  errorMessage,
  successMessage,
  modalErrorMessage,
  formData,
  hasSelectedCustomers,
  nameRules,
  emailRules,
  phoneRules,
  genderOptions,
  loyaltyLevelOptions,
  fetchCustomersList,
  fetchStats,
  saveCustomer,
  deleteCustomer,
  bulkDeleteCustomers,
  toggleActiveStatus,
  openCreateDialog,
  openEditDialog,
  openDeleteDialog,
  closeDialog,
  clearModalError,
  onPageChange,
  handleFiltersUpdate,
} = useCustomers()

const confirmDelete = async () => {
  await deleteCustomer()
}

const confirmBulkDelete = async () => {
  await bulkDeleteCustomers()
}

onMounted(() => {
  fetchCustomersList()
  fetchStats()
})
</script>

<template>
  <div class="customer-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Kelola Customer</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola data pelanggan dan riwayat transaksi</p>
      </div>
      <div class="d-flex gap-3 align-center">
        <div v-if="hasSelectedCustomers" class="d-flex gap-2">
          <VChip color="primary" size="small">
            {{ selectedCustomers.length }} dipilih
          </VChip>
          <VBtn
            color="error"
            variant="outlined"
            size="small"
            prepend-icon="mdi-delete"
            @click="confirmBulkDelete"
          >
            Hapus Terpilih
          </VBtn>
        </div>
        <VBtn
          color="primary"
          prepend-icon="mdi-plus"
          class="coffee-btn"
          @click="openCreateDialog"
        >
          Tambah Customer
        </VBtn>
      </div>
    </div>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="outlined"
      class="mb-4"
      :text="errorMessage"
      closable
      @click:close="errorMessage = ''"
    />

    <VAlert
      v-if="successMessage"
      type="success"
      variant="outlined"
      class="mb-4"
      :text="successMessage"
      closable
      @click:close="successMessage = ''"
    />

    <CustomerStatsCards :stats="stats" />

    <CustomerSearchFilters
      :filters="filters"
      :gender-options="genderOptions"
      :loyalty-level-options="loyaltyLevelOptions"
      @update:filters="handleFiltersUpdate"
    />

    <CustomerTable
      :customers="customersList"
      :loading="loading"
      :delete-loading="deleteLoading"
      :toggle-loading="toggleLoading"
      :current-page="currentPage"
      :total-items="totalItems"
      :items-per-page="itemsPerPage"
      :selected-customers="selectedCustomers"
      @edit="openEditDialog"
      @delete="openDeleteDialog"
      @bulk-delete="confirmBulkDelete"
      @toggle-status="toggleActiveStatus"
      @page-change="onPageChange"
      @items-per-page-change="(value: number) => handleFiltersUpdate({ per_page: value })"
      @update:selected-customers="(customers: number[]) => selectedCustomers = customers"
      @view-detail="(customer: Customer) => console.log('View detail:', customer)"
      @view-history="(customer: Customer) => console.log('View history:', customer)"
      @view-notes="(customer: Customer) => console.log('View notes:', customer)"
    />

    <CustomerDialog
      :dialog="dialog"
      :edit-mode="editMode"
      :selected-customer="selectedCustomer"
      :form-data="formData"
      :save-loading="saveLoading"
      :modal-error-message="modalErrorMessage"
      :name-rules="nameRules"
      :email-rules="emailRules"
      :phone-rules="phoneRules"
      :gender-options="genderOptions"
      @update:dialog="dialog = $event"
      @update:form-data="(data: CustomerFormData) => Object.assign(formData, data)"
      @save="saveCustomer"
      @close="closeDialog"
      @clear-modal-error="clearModalError"
    />

    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus Customer"
      :item-name="selectedCustomer?.name"
      :loading="deleteLoading"
      confirm-text="Hapus Customer"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    />
  </div>
</template>

<style scoped lang="scss">
.customer-management {
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
