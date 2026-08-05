<script setup lang="ts">
import { onMounted } from 'vue'
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import ItemDialog from '@/components/items/ItemDialog.vue'
import ItemSearchFilters from '@/components/items/ItemSearchFilters.vue'
import ItemStatsCards from '@/components/items/ItemStatsCards.vue'
import ItemTable from '@/components/items/ItemTable.vue'
import { useItems } from '@/composables/useItems'

const {
  itemsList,
  loading,
  saveLoading,
  deleteLoading,
  exportLoading,
  stats,
  dialog,
  deleteDialog,
  exportDialog,
  editMode,
  selectedItem,
  selectedItems,
  currentPage,
  totalItems,
  itemsPerPage,
  filters,
  errorMessage,
  successMessage,
  modalErrorMessage,
  formData,
  canCreateEdit,
  hasSelectedItems,
  stockStatusOptions,
  unitOptions,
  fetchItemsList,
  fetchStats,
  exportToExcel,
  openExportDialog,
  closeExportDialog,
  saveItem,
  deleteItem,
  openCreateDialog,
  openEditDialog,
  openDeleteDialog,
  closeDialog,
  clearModalError,
  onPageChange,
  onItemsPerPageChange,
  handleFiltersUpdate,
} = useItems()

const confirmDelete = async () => {
  await deleteItem()
}

onMounted(() => {
  fetchItemsList()
  fetchStats()
})
</script>

<template>
  <div class="item-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">
          Kelola Item
        </h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">
          Kelola bahan baku dan item untuk operasional coffee shop Anda
        </p>
      </div>
      <div class="d-flex gap-3 align-center">
        <div
          v-if="hasSelectedItems"
          class="d-flex gap-2"
        >
          <VChip
            color="primary"
            size="small"
          >
            {{ selectedItems.length }} dipilih
          </VChip>
          <VBtn
            color="error"
            variant="outlined"
            size="small"
            prepend-icon="mdi-delete"
            @click="openDeleteDialog"
          >
            Hapus Terpilih
          </VBtn>
        </div>
        <VBtn
          v-if="canCreateEdit"
          color="primary"
          prepend-icon="mdi-plus"
          class="coffee-btn"
          @click="openCreateDialog"
        >
          Tambah Item
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

    <ItemStatsCards :stats="stats" />

    <ItemSearchFilters
      :filters="filters"
      @update:filters="handleFiltersUpdate"
    />

    <ItemTable
      :items="itemsList"
      :loading="loading"
      :export-loading="exportLoading"
      :current-page="currentPage"
      :total-items="totalItems"
      :items-per-page="itemsPerPage"
      :selected-items="selectedItems"
      @update:page="onPageChange"
      @update:items-per-page="onItemsPerPageChange"
      @add-item="openCreateDialog"
      @export-excel="openExportDialog"
      @edit-item="openEditDialog"
      @delete-item="openDeleteDialog"
      @update:selected-items="(items: number[]) => selectedItems = items"
    />

    <ItemDialog
      v-model="dialog"
      :edit-mode="editMode"
      :form-data="formData"
      :loading="saveLoading"
      :error-message="modalErrorMessage"
      @update:model-value="dialog = $event"
      @close="closeDialog"
      @save="saveItem"
      @clear-error="clearModalError"
      @update-form="(field: string, value: any) => (formData as any)[field] = value"
    />

    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus Item"
      :item-name="selectedItem?.name"
      :loading="deleteLoading"
      confirm-text="Hapus Item"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    />

    <!-- Export Dialog -->
    <VDialog
      v-model="exportDialog"
      max-width="500"
      persistent
    >
      <VCard>
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon
            icon="tabler-file-spreadsheet"
            color="success"
          />
          Export Data Item ke Excel
        </VCardTitle>

        <VDivider />

        <VCardText>
          <div class="mb-4">
            <h6 class="text-h6 mb-2">
              Filter yang akan diterapkan:
            </h6>
            <div class="d-flex flex-column gap-2">
              <VChip
                v-if="filters.search"
                color="primary"
                variant="tonal"
                size="small"
                prepend-icon="tabler-search"
              >
                Pencarian: "{{ filters.search }}"
              </VChip>

              <VChip
                v-if="filters.active !== undefined && filters.active !== 'all' && filters.active !== ''"
                color="info"
                variant="tonal"
                size="small"
                prepend-icon="tabler-toggle-right"
              >
                Status: {{ filters.active === true || filters.active === 'true' ? 'Aktif' : 'Nonaktif' }}
              </VChip>

              <VChip
                v-if="filters.stock_status && filters.stock_status !== 'all'"
                color="warning"
                variant="tonal"
                size="small"
                prepend-icon="tabler-package"
              >
                Stok: {{ filters.stock_status === 'in_stock' ? 'Stok Tersedia' : filters.stock_status === 'low_stock' ? 'Stok Rendah' : 'Stok Habis' }}
              </VChip>

              <VChip
                v-if="filters.station && filters.station !== 'all'"
                color="primary"
                variant="tonal"
                size="small"
                prepend-icon="tabler-building-store"
              >
                Station: {{ filters.station === 'kitchen' ? 'Kitchen Only' : filters.station === 'bar' ? 'Bar Only' : 'Kitchen & Bar' }}
              </VChip>

              <VChip
                v-if="!filters.search && (filters.active === undefined || filters.active === 'all' || filters.active === '') && (!filters.stock_status || filters.stock_status === 'all') && (!filters.station || filters.station === 'all')"
                color="success"
                variant="tonal"
                size="small"
                prepend-icon="tabler-check"
              >
                Semua Data (Tanpa Filter)
              </VChip>
            </div>
          </div>

          <VAlert
            type="info"
            variant="tonal"
            class="mb-4"
          >
            Data yang akan di-export mengikuti filter yang sedang aktif.
            File Excel akan berisi kolom Station (Kitchen & Bar) untuk setiap item.
          </VAlert>
        </VCardText>

        <VDivider />

        <VCardActions>
          <VSpacer />
          <VBtn
            variant="outlined"
            :disabled="exportLoading"
            @click="closeExportDialog"
          >
            Batal
          </VBtn>
          <VBtn
            color="success"
            variant="elevated"
            prepend-icon="tabler-file-spreadsheet"
            :loading="exportLoading"
            @click="exportToExcel(); closeExportDialog()"
          >
            Export ke Excel
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped lang="scss">
.item-management {
  .coffee-title {
    color: rgb(var(--v-theme-primary));
    font-family: Inter, sans-serif;
  }

  .coffee-subtitle {
    margin-block-start: 4px;
    opacity: 0.8;
  }
}
</style>
