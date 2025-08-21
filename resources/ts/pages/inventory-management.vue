<script setup lang="ts">
import InventoryMovementsDialog from '@/components/InventoryMovementsDialog.vue'
import { useInventory } from '@/composables/useInventory'
import { useStockMovements } from '@/composables/useStockMovements'
import { computed, onMounted, watch } from 'vue'
import { useDisplay } from 'vuetify'

// Vuetify display composable for responsive design
const { xs } = useDisplay()

// Function to refresh inventory data
const refreshCurrentView = () => {
  fetchInventoryList()
  fetchStats()
  fetchLowStockAlerts()
}

const {
  inventoryList,
  loading,
  saveLoading,
  stats,
  lowStockItems,
  alertsLoading,
  stockUpdateDialog,
  reorderDialog,
  movementsDialog,
  selectedInventory,
  selectedItems,
  currentPage,
  totalItems,
  itemsPerPage,
  filters,
  errorMessage,
  successMessage,
  modalErrorMessage,
  stockUpdateForm,
  reorderForm,
  hasSelectedItems,
  totalStockValue,
  lowStockCount,
  outOfStockCount,
  stockStatusOptions,
  fetchInventoryList,
  fetchStats,
  fetchLowStockAlerts,
  updateStock,
  setReorderLevel,
  openStockUpdateDialog,
  openReorderDialog,
  openMovementsDialog,
  closeStockUpdateDialog,
  closeReorderDialog,
  closeMovementsDialog,
  clearModalError,
  onPageChange,
  onItemsPerPageChange,
  handleFiltersUpdate,
  formatCurrency,
  getStockStatusColor,
  getStockStatusText,
  // Pagination computed
  totalPages,
  hasNextPage,
  hasPrevPage,
  paginationInfo
} = useInventory()

// Stock movements for enhanced functionality
const {
  recordStockMovement,
  openStockInDialog,
  openStockOutDialog,
  openAdjustmentDialog,
  getStockStatus,
  getStockPercentage,
  movementDialog,
  adjustmentDialog,
  selectedItem,
  movementFormData,
  saveLoading: movementSaveLoading,
  closeMovementDialog,
  successMessage: stockMovementSuccessMessage,
  modalErrorMessage: stockMovementErrorMessage,
  clearModalError: clearStockMovementError,
} = useStockMovements()

// Helper function to convert inventory item to Item format for stock movements
const convertInventoryItemToItem = (inventoryItem: any) => {
  if (!inventoryItem.item) return null
  
  return {
    id_item: inventoryItem.item.id_item,
    item_code: inventoryItem.item.item_code,
    name: inventoryItem.item.name,
    description: inventoryItem.item.description || '',
    unit: inventoryItem.item.unit,
    cost_per_unit: parseFloat(inventoryItem.item.cost_per_unit),
    current_stock: inventoryItem.current_stock,
    minimum_stock: inventoryItem.reorder_level || 0,
    active: true,
    storage_location: inventoryItem.item.storage_location || '',
    expiry_date: inventoryItem.item.expiry_date || null,
    properties: inventoryItem.item.properties || null,
    created_by: inventoryItem.item.created_by || null,
    updated_by: inventoryItem.item.updated_by || null,
    created_at: inventoryItem.item.created_at,
    updated_at: inventoryItem.item.updated_at,
    deleted_at: inventoryItem.item.deleted_at || null
  }
}

// Stock movement handlers for inventory items
const handleStockIn = (inventoryItem: any) => {
  const item = convertInventoryItemToItem(inventoryItem)
  if (item) {
    openStockInDialog(item)
  }
}

const handleStockOut = (inventoryItem: any) => {
  const item = convertInventoryItemToItem(inventoryItem)
  if (item) {
    openStockOutDialog(item)
  }
}

const handleAdjustment = (inventoryItem: any) => {
  const item = convertInventoryItemToItem(inventoryItem)
  if (item) {
    openAdjustmentDialog(item)
  }
}

// Computed properties for inventory view
const currentStats = computed(() => {
  return stats.value
})

const currentData = computed(() => {
  return inventoryList.value
})

const currentLoading = computed(() => {
  return loading.value
})

onMounted(() => {
  fetchInventoryList()
  fetchStats()
  fetchLowStockAlerts()
})

// Debug watcher for totalItems
watch(totalItems, (newValue, oldValue) => {
  console.log('🔍 totalItems changed:', { oldValue, newValue })
  console.log('📊 VDataTable will receive server-items-length:', newValue)
}, { immediate: true })
</script>

<template>
  <div class="inventory-management">
    <!-- Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold inventory-title">
          Kelola Inventory
        </h1>
        <p class="text-body-1 text-medium-emphasis inventory-subtitle">
          Kelola stok barang dan pergerakan inventory
        </p>
      </div>
      <div class="d-flex gap-3 align-center">
        <VBtn
          color="primary"
          prepend-icon="tabler-refresh"
          variant="outlined"
          @click="refreshCurrentView"
        >
          Refresh Data
        </VBtn>
      </div>
    </div>

    <!-- Alert Messages -->
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

    <VAlert
      v-if="stockMovementSuccessMessage"
      type="success"
      variant="outlined"
      class="mb-4"
      :text="stockMovementSuccessMessage"
      closable
      @click:close="stockMovementSuccessMessage = ''"
    />

    <!-- Statistics Cards -->
    <VRow class="mb-6">
      <VCol
        cols="12"
        md="3"
      >
        <VCard class="stats-card">
          <VCardText>
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-h4 font-weight-bold text-primary">
                  {{ currentStats.total_items || 0 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Total Produk
                </div>
              </div>
              <VIcon
                icon="tabler-package"
                size="48"
                class="text-primary opacity-50"
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="3"
      >
        <VCard class="stats-card">
          <VCardText>
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-h4 font-weight-bold text-warning">
                  {{ lowStockCount || 0 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Stok Menipis
                </div>
              </div>
              <VIcon
                icon="tabler-alert-triangle"
                size="48"
                class="text-warning opacity-50"
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="3"
      >
        <VCard class="stats-card">
          <VCardText>
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-h4 font-weight-bold text-error">
                  {{ outOfStockCount || 0 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Stok Habis
                </div>
              </div>
              <VIcon
                icon="tabler-x-circle"
                size="48"
                class="text-error opacity-50"
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="3"
      >
        <VCard class="stats-card">
          <VCardText>
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-h4 font-weight-bold text-success">
                  {{ formatCurrency(totalStockValue || 0) }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Nilai Stok
                </div>
              </div>
              <VIcon
                icon="tabler-currency-dollar"
                size="48"
                class="text-success opacity-50"
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Stock Movement Info Card -->
    <VCard class="mb-6 info-card" color="info" variant="tonal">
      <VCardText>
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-3">
            <VIcon
              icon="tabler-info-circle"
              size="32"
              class="text-info"
            />
            <div>
              <div class="text-subtitle-1 font-weight-medium text-info">
                Sistem Stock Movement Otomatis
              </div>
              <div class="text-body-2 text-medium-emphasis">
                Stok akan bertambah otomatis saat purchasing dan berkurang saat penjualan POS.
                Gunakan tombol <strong>Penyesuaian</strong> untuk koreksi manual jika diperlukan.
              </div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <VChip color="success" variant="tonal" size="small">
              <VIcon icon="tabler-shopping-cart" class="me-1" />
              Purchase → Stok +
            </VChip>
            <VChip color="error" variant="tonal" size="small">
              <VIcon icon="tabler-cash" class="me-1" />
              POS Sale → Stok -
            </VChip>
          </div>
        </div>
      </VCardText>
    </VCard>

    <!-- Low Stock Alerts -->
    <VCard
      v-if="lowStockItems.length > 0"
      class="mb-6 low-stock-alert"
    >
          <VCardTitle class="d-flex align-center">
            <VIcon
              icon="tabler-alert-triangle"
              class="text-warning me-2"
            />
            Peringatan Stok Menipis ({{ lowStockItems.length }} produk)
          </VCardTitle>
          <VCardText>
            <div class="d-flex flex-wrap gap-2">
              <VChip
                v-for="item in lowStockItems.slice(0, 10)"
                :key="item.id_inventory"
                color="warning"
                variant="outlined"
                size="small"
              >
                {{ item.product?.name }}
                ({{ item.current_stock }}/{{ item.reorder_level }})
              </VChip>
              <VChip
                v-if="lowStockItems.length > 10"
                color="warning"
                variant="tonal"
                size="small"
              >
                +{{ lowStockItems.length - 10 }} lainnya
              </VChip>
            </div>
          </VCardText>
        </VCard>

        <!-- Info Card saat tidak ada data -->
        <VCard
          v-if="!loading && inventoryList.length === 0 && !errorMessage"
          class="mb-6"
        >
          <VCardText class="text-center py-6">
            <VIcon
              icon="tabler-info-circle"
              size="48"
              class="text-info mb-3"
            />
            <div class="text-h6 mb-2">
              Sistem Inventory Management Siap Digunakan
            </div>
            <div class="text-body-2 text-medium-emphasis">
              Buat produk dan variant terlebih dahulu untuk mulai melacak inventory.
            </div>
          </VCardText>
        </VCard>

        <!-- Filters (only show when there's data or loading) -->
        <VCard
          v-if="loading || inventoryList.length > 0"
          class="mb-6"
        >
          <VCardText>
            <VRow>
              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  v-model="filters.search"
                  label="Cari inventory produk..."
                  placeholder="Nama produk, SKU, atau variant"
                  prepend-inner-icon="tabler-search"
                  clearable
                  variant="outlined"
                  @update:model-value="handleFiltersUpdate({ search: $event })"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <VSelect
                  v-model="filters.stock_status"
                  label="Status Stok"
                  :items="stockStatusOptions"
                  clearable
                  variant="outlined"
                  @update:model-value="handleFiltersUpdate({ stock_status: $event })"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
              >
                <VSelect
                  v-model="filters.sort_by"
                  label="Urutkan"
                  :items="[
                    { title: 'Stok Tersedia', value: 'current_stock' },
                    { title: 'Nama Produk', value: 'product_name' },
                    { title: 'Terakhir Diperbarui', value: 'updated_at' },
                  ]"
                  variant="outlined"
                  @update:model-value="handleFiltersUpdate({ sort_by: $event })"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
              >
                <VSelect
                  v-model="filters.sort_order"
                  label="Urutan"
                  :items="[
                    { title: 'A-Z / 0-9', value: 'asc' },
                    { title: 'Z-A / 9-0', value: 'desc' },
                  ]"
                  variant="outlined"
                  @update:model-value="handleFiltersUpdate({ sort_order: $event })"
                />
              </VCol>

              <VCol
                cols="12"
                md="1"
              >
                <VBtn
                  variant="outlined"
                  color="secondary"
                  block
                  @click="handleFiltersUpdate({ search: '', stock_status: 'all', sort_by: 'current_stock', sort_order: 'asc' })"
                >
                  Reset
                </VBtn>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Inventory Table -->
        <VCard class="products-inventory-table">
          <VCardTitle class="d-flex align-center gap-2 coffee-header">
            <VIcon
              icon="tabler-package"
              class="text-white"
            />
            <span class="text-white">Inventory Produk</span>
            <VSpacer />
            <VChip
              color="white"
              size="small"
              variant="tonal"
            >
              {{ totalItems }} Produk
            </VChip>
          </VCardTitle>

          <VDivider />

          <VDataTableServer
            :headers="[
              { title: 'Produk/Variant', key: 'item_name', sortable: false },
              { title: 'SKU', key: 'sku', sortable: false },
              { title: 'Stok Saat Ini', key: 'current_stock', sortable: false },
              { title: 'Stok Tersedia', key: 'available_stock', sortable: false },
              { title: 'Status', key: 'status', sortable: false },
              { title: 'Reorder Level', key: 'reorder_level', sortable: false },
              { title: 'Harga Rata-rata', key: 'average_cost', sortable: false },
              { title: 'Nilai Stok', key: 'stock_value', sortable: false },
              { title: 'Aksi', key: 'actions', sortable: false },
            ]"
            :items="inventoryList"
            :loading="loading"
            :items-length="totalItems"
            :items-per-page="itemsPerPage"
            :page="currentPage"
            :items-per-page-options="[10, 15, 25, 50, 100]"
            :items-per-page-text="'Items per page:'"
            :page-text="'{0}-{1} of {2}'"
            :no-data-text="'Tidak ada data inventory'"
            class="text-no-wrap"
            @update:page="onPageChange"
            @update:items-per-page="onItemsPerPageChange"
          >
            <!-- Item Name -->
            <template #item.item_name="{ item }">
              <div class="d-flex align-center">
                <div>
                  <div class="font-weight-medium">
                    {{ item.item?.name || item.product?.name || 'Unknown Item' }}
                  </div>
                  <small class="text-medium-emphasis">
                    {{ item.item?.description || item.product?.category?.name || '-' }}
                  </small>
                </div>
              </div>
            </template>

            <!-- SKU -->
            <template #item.sku="{ item }">
              <VChip
                variant="outlined"
                size="small"
                color="primary"
              >
                {{ item.item?.item_code || item.product?.sku || 'N/A' }}
              </VChip>
            </template>

            <!-- Current Stock -->
            <template #item.current_stock="{ item }">
              <div class="text-center">
                <div class="text-h6 font-weight-bold">
                  {{ item.current_stock }}
                </div>
                <small
                  v-if="item.reserved_stock > 0"
                  class="text-warning"
                >
                  ({{ item.reserved_stock }} reserved)
                </small>
              </div>
            </template>

            <!-- Available Stock -->
            <template #item.available_stock="{ item }">
              <div class="text-center">
                <VChip
                  :color="item.available_stock > 0 ? 'success' : 'error'"
                  variant="tonal"
                  size="small"
                >
                  {{ item.available_stock }}
                </VChip>
              </div>
            </template>

            <!-- Status -->
            <template #item.status="{ item }">
              <VChip
                :color="getStockStatusColor(item)"
                variant="tonal"
                size="small"
              >
                {{ getStockStatusText(item) }}
              </VChip>
            </template>

            <!-- Reorder Level -->
            <template #item.reorder_level="{ item }">
              <div class="text-center">
                {{ item.reorder_level || '-' }}
              </div>
            </template>

            <!-- Average Cost -->
            <template #item.average_cost="{ item }">
              <div class="text-end">
                {{ formatCurrency(item.average_cost) }}
              </div>
            </template>

            <!-- Stock Value -->
            <template #item.stock_value="{ item }">
              <div class="text-end font-weight-medium text-success">
                {{ formatCurrency(item.current_stock * item.average_cost) }}
              </div>
            </template>

            <!-- Actions -->
            <template #item.actions="{ item }">
              <div class="d-flex align-center gap-1">
                <!-- Adjustment -->
                <VTooltip text="Penyesuaian Stok">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="tabler-adjustments"
                      color="warning"
                      variant="text"
                      size="small"
                      :disabled="!item.item"
                      @click="handleAdjustment(item)"
                    />
                  </template>
                </VTooltip>

                <VTooltip text="Update Stok">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="tabler-edit"
                      color="primary"
                      variant="text"
                      size="small"
                      @click="openStockUpdateDialog(item)"
                    />
                  </template>
                </VTooltip>

                <VTooltip text="Set Reorder Level">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="tabler-settings"
                      color="info"
                      variant="text"
                      size="small"
                      @click="openReorderDialog(item)"
                    />
                  </template>
                </VTooltip>

                <VTooltip text="Lihat Riwayat">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="tabler-history"
                      color="secondary"
                      variant="text"
                      size="small"
                      @click="openMovementsDialog(item)"
                    />
                  </template>
                </VTooltip>
              </div>
            </template>

            <!-- No Data -->
            <template #no-data>
              <div class="text-center py-12">
                <VIcon
                  icon="tabler-package-off"
                  size="64"
                  class="text-medium-emphasis mb-4"
                />
                <div class="text-h5 text-medium-emphasis mb-2">
                  Belum Ada Data Inventory
                </div>
                <div class="text-body-1 text-medium-emphasis mb-4">
                  Data inventory akan muncul setelah produk dan variant dibuat.<br>
                  Silakan buat produk terlebih dahulu di menu <strong>Kelola Produk</strong>.
                </div>
                <div class="d-flex justify-center gap-3">
                  <VBtn
                    color="primary"
                    prepend-icon="tabler-plus"
                    to="/products-management"
                  >
                    Buat Produk Baru
                  </VBtn>
                  <VBtn
                    variant="outlined"
                    prepend-icon="tabler-refresh"
                    @click="fetchInventoryList(); fetchStats(); fetchLowStockAlerts()"
                  >
                    Refresh Data
                  </VBtn>
                </div>
              </div>
            </template>

            <!-- Loading -->
            <template #loading>
              <div class="text-center py-8">
                <VProgressCircular
                  indeterminate
                  color="primary"
                  size="40"
                />
                <div class="mt-4 text-medium-emphasis">
                  Memuat data inventory...
                </div>
              </div>
            </template>
          </VDataTableServer>
          
          <!-- Total Data Info -->
          <VCardText 
            v-if="!loading && inventoryList.length > 0"
            class="text-center py-2 text-caption text-medium-emphasis"
          >
            Menampilkan {{ inventoryList.length }} dari {{ totalItems }} total produk inventory
            <span v-if="filters.search || filters.stock_status !== 'all'">
              (hasil pencarian/filter)
            </span>
            <br>
            <small class="text-xs text-info">
              Debug: Page {{ currentPage }}/{{ totalPages }} | Items per page: {{ itemsPerPage }} | 
              Has Next: {{ hasNextPage }} | Has Prev: {{ hasPrevPage }}
            </small>
          </VCardText>
        </VCard>

        <!-- Stock Update Dialog -->
        <VDialog
          v-model="stockUpdateDialog"
          max-width="800px"
          :fullscreen="xs"
          persistent
          class="stock-dialog"
        >
          <VCard>
            <VCardTitle>
              Update Stok - {{ selectedInventory?.product?.name }}
            </VCardTitle>
            <VDivider />
            <VForm @submit.prevent="updateStock">
              <VCardText>
                <VAlert
                  v-if="modalErrorMessage"
                  type="error"
                  variant="tonal"
                  closable
                  class="mb-4"
                  @click:close="clearModalError"
                >
                  {{ modalErrorMessage }}
                </VAlert>

                <VRow>
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VSelect
                      v-model="stockUpdateForm.movement_type"
                      label="Jenis Pergerakan *"
                      :items="[
                        { title: 'Stock Masuk', value: 'stock_in' },
                        { title: 'Stock Keluar', value: 'stock_out' },
                        { title: 'Penyesuaian', value: 'adjustment' },
                        { title: 'Transfer', value: 'transfer' },
                        { title: 'Retur', value: 'return' },
                      ]"
                      variant="outlined"
                    />
                  </VCol>
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model.number="stockUpdateForm.quantity"
                      label="Kuantitas *"
                      type="number"
                      min="1"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VRow>
                  <VCol cols="12">
                    <VTextField
                      v-model="stockUpdateForm.reason"
                      label="Alasan *"
                      placeholder="Contoh: Pembelian dari supplier, Penjualan, dll"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VRow>
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="stockUpdateForm.reference_number"
                      label="Nomor Referensi"
                      placeholder="PO-001, SO-001, dll"
                      variant="outlined"
                    />
                  </VCol>
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model.number="stockUpdateForm.cost_per_unit"
                      label="Harga per Unit"
                      type="number"
                      min="0"
                      step="0.01"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VRow>
                  <VCol cols="12">
                    <VTextarea
                      v-model="stockUpdateForm.notes"
                      label="Catatan"
                      rows="2"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>
              </VCardText>

              <VDivider />
              <VCardActions class="pa-4">
                <VSpacer />
                <VBtn
                  variant="outlined"
                  @click="closeStockUpdateDialog"
                >
                  Batal
                </VBtn>
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="saveLoading"
                >
                  Update Stok
                </VBtn>
              </VCardActions>
            </VForm>
          </VCard>
        </VDialog>

        <!-- Set Reorder Level Dialog -->
        <VDialog
          v-model="reorderDialog"
          max-width="600px"
          :fullscreen="xs"
          persistent
          class="stock-dialog"
        >
          <VCard>
            <VCardTitle>
              Set Reorder Level - {{ selectedInventory?.product?.name }}
            </VCardTitle>
            <VDivider />
            <VForm @submit.prevent="setReorderLevel">
              <VCardText>
                <VAlert
                  v-if="modalErrorMessage"
                  type="error"
                  variant="tonal"
                  closable
                  class="mb-4"
                  @click:close="clearModalError"
                >
                  {{ modalErrorMessage }}
                </VAlert>

                <VRow>
                  <VCol cols="12">
                    <VTextField
                      v-model.number="reorderForm.reorder_level"
                      label="Reorder Level *"
                      type="number"
                      min="0"
                      hint="Stok minimum sebelum perlu restock"
                      persistent-hint
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VRow>
                  <VCol cols="12">
                    <VTextField
                      v-model.number="reorderForm.max_stock_level"
                      label="Max Stock Level"
                      type="number"
                      min="0"
                      hint="Stok maksimum yang diizinkan"
                      persistent-hint
                      variant="outlined"
                    />
                  </VCol>
                </VRow>
              </VCardText>

              <VDivider />
              <VCardActions class="pa-4">
                <VSpacer />
                <VBtn
                  variant="outlined"
                  @click="closeReorderDialog"
                >
                  Batal
                </VBtn>
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="saveLoading"
                >
                  Simpan
                </VBtn>
              </VCardActions>
            </VForm>
          </VCard>
        </VDialog>

    <!-- Movements History Dialog -->
    <!-- Movements History Dialog -->
    <InventoryMovementsDialog
      v-model="movementsDialog"
      :inventory-id="selectedInventory?.id_inventory?.toString()"
      :inventory-name="selectedInventory?.item?.name"
    />

    <!-- Stock Adjustment Dialog -->
    <VDialog
      v-model="adjustmentDialog"
      max-width="600px"
      :fullscreen="xs"
      persistent
      class="stock-dialog"
    >
      <VCard class="stock-movement-dialog coffee-dialog">
        <VCardTitle class="d-flex align-center justify-space-between coffee-header">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-adjustments"
              class="text-white"
            />
            <span class="text-white">
              Penyesuaian Stok
            </span>
          </div>
          <VBtn
            icon="tabler-x"
            variant="text"
            color="white"
            @click="closeMovementDialog"
          />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <VForm @submit.prevent="recordStockMovement">
            <!-- Item Info -->
            <VAlert
              v-if="selectedItem"
              color="info"
              variant="tonal"
              class="mb-4"
            >
              <div class="d-flex align-center gap-3">
                <VIcon icon="tabler-package" />
                <div>
                  <div class="font-weight-bold">{{ selectedItem.name }}</div>
                  <div class="text-caption">{{ selectedItem.item_code }}</div>
                  <div class="text-caption">Stok Saat Ini: {{ selectedItem.current_stock }} {{ selectedItem.unit }}</div>
                </div>
              </div>
            </VAlert>

            <!-- Error Alert -->
            <VAlert
              v-if="stockMovementErrorMessage"
              type="error"
              variant="outlined"
              class="mb-4"
              :text="stockMovementErrorMessage"
              closable
              @click:close="clearStockMovementError"
            />

            <!-- Quantity Input -->
            <VTextField
              v-model.number="movementFormData.quantity"
              label="Stok Baru"
              type="number"
              min="0"
              step="0.001"
              variant="outlined"
              :suffix="selectedItem?.unit"
              class="mb-4"
              required
            />

            <!-- Notes -->
            <VTextarea
              v-model="movementFormData.notes"
              label="Catatan (opsional)"
              variant="outlined"
              rows="3"
              class="mb-4"
            />

            <!-- Actions -->
            <div class="d-flex gap-3 justify-end">
              <VBtn
                variant="outlined"
                @click="closeMovementDialog"
              >
                Batal
              </VBtn>
              <VBtn
                type="submit"
                color="primary"
                class="coffee-primary"
                :loading="movementSaveLoading"
              >
                Simpan Penyesuaian
              </VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 
    Stock Movement Dialog - Commented out as stock movements are now automatic
    from purchasing and POS transactions. Only adjustment dialog is kept.
    
    <VDialog
      v-model="movementDialog"
      max-width="800px"
      :fullscreen="xs"
      persistent
      class="stock-dialog"
    >
      <VCard class="stock-movement-dialog coffee-dialog">
        <VCardTitle class="d-flex align-center justify-space-between coffee-header">
          <div class="d-flex align-center gap-2">
            <VIcon
              :icon="movementFormData.type === 'in' ? 'tabler-plus' : 'tabler-minus'"
              class="text-white"
            />
            <span class="text-white">
              {{
                movementFormData.type === 'in' ? 'Stok Masuk'
                : movementFormData.type === 'out' ? 'Stok Keluar'
                  : 'Penyesuaian Stok'
              }}
            </span>
          </div>
          <VBtn
            icon="tabler-x"
            variant="text"
            color="white"
            @click="closeMovementDialog"
          />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <VForm @submit.prevent="recordStockMovement">
            <VAlert
              v-if="selectedItem"
              color="info"
              variant="tonal"
              class="mb-4"
            >
              <div class="d-flex align-center justify-space-between">
                <div>
                  <div class="font-weight-medium">{{ selectedItem.name }}</div>
                  <div class="text-caption">Stok Saat Ini: {{ selectedItem.current_stock }} {{ selectedItem.unit }}</div>
                </div>
                <div class="text-right">
                  <div class="text-caption text-medium-emphasis">SKU</div>
                  <div class="font-weight-medium">{{ selectedItem.item_code }}</div>
                </div>
              </div>
            </VAlert>

            <VAlert
              v-if="modalErrorMessage"
              type="error"
              variant="outlined"
              class="mb-4"
              :text="modalErrorMessage"
              closable
              @click:close="clearModalError"
            />

            <VRow>
              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="movementFormData.quantity"
                  :label="movementFormData.type === 'adjustment' ? 'Stok Baru' : 'Jumlah'"
                  type="number"
                  :step="movementFormData.type === 'adjustment' ? '1' : '0.001'"
                  min="0"
                  variant="outlined"
                  :suffix="selectedItem?.unit || 'pcs'"
                  required
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="movementFormData.cost_per_unit"
                  label="Harga per Unit"
                  type="number"
                  step="0.01"
                  min="0"
                  variant="outlined"
                  prefix="Rp"
                  :readonly="movementFormData.type !== 'in'"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="movementFormData.notes"
                  label="Catatan"
                  placeholder="Masukkan catatan untuk pergerakan stok..."
                  rows="3"
                  variant="outlined"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-6">
          <VSpacer />
          <VBtn
            variant="outlined"
            class="coffee-secondary"
            @click="closeMovementDialog"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            class="coffee-primary"
            :loading="movementSaveLoading"
            @click="recordStockMovement"
          >
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
    -->    <!-- Stock Movement Dialog -->
    <VDialog
      v-model="movementDialog"
      max-width="800px"
      :fullscreen="xs"
      persistent
      class="stock-dialog"
    >
      <VCard class="stock-movement-dialog coffee-dialog">
        <VCardTitle class="d-flex align-center justify-space-between coffee-header">
          <div class="d-flex align-center gap-2">
            <VIcon
              :icon="movementFormData.type === 'in' ? 'tabler-plus' : 'tabler-minus'"
              class="text-white"
            />
            <span class="text-white">
              {{
                movementFormData.type === 'in' ? 'Stok Masuk'
                : movementFormData.type === 'out' ? 'Stok Keluar'
                  : 'Penyesuaian Stok'
              }}
            </span>
          </div>
          <VBtn
            icon="tabler-x"
            variant="text"
            color="white"
            @click="closeMovementDialog"
          />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <VForm @submit.prevent="recordStockMovement">
            <!-- Item Info -->
            <VAlert
              v-if="selectedItem"
              color="info"
              variant="tonal"
              class="mb-4"
            >
              <div class="d-flex align-center gap-3">
                <VAvatar
                  color="primary"
                  size="40"
                  variant="tonal"
                >
                  <VIcon icon="tabler-package" />
                </VAvatar>
                <div>
                  <div class="font-weight-bold">
                    {{ selectedItem.name }}
                  </div>
                  <div class="text-caption">
                    Stok saat ini: {{ selectedItem.current_stock }} {{ selectedItem.unit }}
                  </div>
                </div>
              </div>
            </VAlert>

            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model.number="movementFormData.quantity"
                  :label="`Jumlah ${movementFormData.type === 'in' ? 'Masuk' : movementFormData.type === 'out' ? 'Keluar' : 'Akhir'}`"
                  type="number"
                  variant="outlined"
                  required
                  :min="movementFormData.type === 'adjustment' ? 0 : 1"
                  :rules="[v => v > 0 || 'Jumlah harus lebih dari 0']"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model.number="movementFormData.cost_per_unit"
                  label="Harga per Unit"
                  type="number"
                  variant="outlined"
                  prefix="Rp"
                  :readonly="movementFormData.type !== 'in'"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="movementFormData.notes"
                  label="Catatan"
                  placeholder="Masukkan catatan untuk pergerakan stok..."
                  rows="3"
                  variant="outlined"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-6">
          <VSpacer />
          <VBtn
            variant="outlined"
            class="coffee-secondary"
            @click="closeMovementDialog"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            class="coffee-primary"
            :loading="movementSaveLoading"
            @click="recordStockMovement"
          >
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped lang="scss">
.inventory-management {
  .inventory-title {
    color: rgb(var(--v-theme-primary));
    font-family: Inter, sans-serif;
  }

  .inventory-subtitle {
    margin-block-start: 4px;
    opacity: 0.8;
  }

  .stats-card {
    border-inline-start: 4px solid rgb(var(--v-theme-primary));
    transition: transform 0.2s ease;

    &:hover {
      transform: translateY(-2px);
    }
  }

  .low-stock-alert {
    background: rgb(var(--v-theme-warning) / 5%);
    border-inline-start: 4px solid rgb(var(--v-theme-warning));
  }

  // Coffee Dialog Theme
  .coffee-dialog {
    .coffee-header {
      background: linear-gradient(135deg, #b07124 0%, #8d7053 100%);
      color: white;
    }

    .coffee-primary {
      border: none;
      background: linear-gradient(135deg, #b07124 0%, #8d7053 100%);
      color: white;

      &:hover {
        background: linear-gradient(135deg, #9a5e1f 0%, #7a5d47 100%);
        box-shadow: 0 4px 12px rgba(176, 113, 36, 40%);
      }
    }

    .coffee-secondary {
      border-color: #b07124;
      color: #b07124;

      &:hover {
        background-color: rgba(176, 113, 36, 10%);
      }
    }
  }

  // Stock Dialog styling
  .stock-dialog {
    .v-dialog {
      &.v-dialog--fullscreen {
        .v-card {
          display: flex;
          flex-direction: column;
          block-size: 100vh;

          .v-card-text {
            flex: 1;
            overflow-y: auto;
          }
        }
      }
    }
  }

  // Tabs styling
  .tabs-container {
    .v-tab {
      font-weight: 500;
      letter-spacing: normal;
      text-transform: none !important;

      &--selected {
        color: rgb(var(--v-theme-primary)) !important;
      }

      .v-chip {
        transition: all 0.2s ease;
      }
    }

    .v-tabs-slider {
      background-color: rgb(var(--v-theme-primary));
    }
  }

  // Distinguish between products and items tables
  .products-inventory-table {
    .coffee-header {
      background: linear-gradient(135deg, #b07124 0%, #8d7053 100%);
    }

    .products-table {
      .v-data-table__td {
        border-block-end: 1px solid rgba(176, 113, 36, 10%);
      }
    }
  }
}
</style>
