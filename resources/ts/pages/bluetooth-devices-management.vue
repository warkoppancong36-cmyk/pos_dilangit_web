<script setup lang="ts">
import BluetoothDeviceDialog from '@/components/bluetooth/BluetoothDeviceDialog.vue'
import BluetoothDeviceGrid from '@/components/bluetooth/BluetoothDeviceGrid.vue'
import BluetoothDeviceList from '@/components/bluetooth/BluetoothDeviceList.vue'
import SearchFilters from '@/components/bluetooth/SearchFilters.vue'
import StatsCards from '@/components/bluetooth/StatsCards.vue'
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import { useBluetoothDevices } from '@/composables/useBluetoothDevices'
import { useDebounceFn } from '@vueuse/core'
import { onMounted, watch } from 'vue'

const {
  devices,
  loading,
  saveLoading,
  deleteLoading,
  toggleLoading,
  testLoading,
  dialog,
  deleteDialog,
  editMode,
  selectedDevice,
  formData,
  search,
  typeFilter,
  activeOnly,
  perPage,
  viewMode,
  currentPage,
  pagination,
  successMessage,
  errorMessage,
  modalErrorMessage,
  canCreateEdit,
  totalPages,
  totalDevices,
  activeDevices,
  inactiveDevices,
  printerDevices,
  otherDevices,
  fetchDevices,
  saveDevice,
  deleteDevice,
  confirmDelete,
  toggleActiveStatus,
  setAsDefault,
  testConnection,
  openCreateDialog,
  openEditDialog,
  closeDialog,
  onPageChange,
  onSearch,
  onFilterChange
} = useBluetoothDevices()

const handleSearchUpdate = (value: string) => {
  search.value = value
}

// Debounced search
const debouncedSearch = useDebounceFn(() => {
  onSearch()
}, 500)

// Watch for search changes
watch(search, () => {
  debouncedSearch()
})

const handleTypeFilterUpdate = (value: string) => {
  typeFilter.value = value
  onFilterChange()
}

const handleActiveOnlyUpdate = (value: boolean) => {
  activeOnly.value = value
  onFilterChange()
}

const handlePerPageUpdate = (value: number) => {
  perPage.value = value
  currentPage.value = 1
  onFilterChange()
}

const clearModalError = () => {
  modalErrorMessage.value = ''
}

watch(dialog, (newValue) => {
  if (!newValue) {
    modalErrorMessage.value = ''
  }
})

onMounted(() => {
  fetchDevices()
})
</script>

<template>
  <div class="bluetooth-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">🔵 Perangkat Bluetooth</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola perangkat bluetooth untuk sistem POS</p>
      </div>
      <VBtn
        v-if="canCreateEdit"
        color="primary"
        prepend-icon="mdi-plus"
        @click="openCreateDialog"
      >
        Tambah Perangkat
      </VBtn>
    </div>

    <VAlert
      v-if="successMessage"
      type="success"
      variant="outlined"
      class="mb-6"
      closable
      @click:close="successMessage = ''"
    >
      <template #prepend>
        <VIcon icon="mdi-check-circle" />
      </template>
      {{ successMessage }}
    </VAlert>
    
    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-6"
      closable
      @click:close="errorMessage = ''"
    >
      <template #prepend>
        <VIcon icon="mdi-alert-circle" />
      </template>
      {{ errorMessage }}
    </VAlert>

    <StatsCards
      :total-devices="totalDevices"
      :active-devices="activeDevices"
      :inactive-devices="inactiveDevices"
      :printer-devices="printerDevices"
    />

    <SearchFilters
      :search="search"
      :type-filter="typeFilter"
      :active-only="activeOnly"
      :per-page="perPage"
      @update:search="handleSearchUpdate"
      @update:type-filter="handleTypeFilterUpdate"
      @update:active-only="handleActiveOnlyUpdate"
      @update:per-page="handlePerPageUpdate"
      @search="onSearch"
    />

    <div class="d-flex justify-space-between align-center mb-4">
      <h2 class="text-h5 font-weight-medium">Daftar Perangkat</h2>
      <VBtnToggle v-model="viewMode" mandatory variant="outlined" divided>
        <VBtn value="grid" size="small">
          <VIcon icon="mdi-view-grid" />
          Grid
        </VBtn>
        <VBtn value="list" size="small">
          <VIcon icon="mdi-view-list" />
          List
        </VBtn>
      </VBtnToggle>
    </div>

    <VCard v-if="loading" class="text-center pa-12">
      <VProgressCircular indeterminate color="primary" size="64" />
      <div class="mt-4 text-h6">Memuat data perangkat...</div>
    </VCard>

    <template v-else>
      <BluetoothDeviceGrid
        v-if="viewMode === 'grid'"
        :devices="devices"
        :toggle-loading="toggleLoading"
        :delete-loading="deleteLoading"
        :test-loading="testLoading"
        @edit="openEditDialog"
        @delete="confirmDelete"
        @toggle="toggleActiveStatus"
        @test-connection="testConnection"
        @set-default="setAsDefault"
        @create="openCreateDialog"
      />
      
      <BluetoothDeviceList
        v-else-if="viewMode === 'list'"
        :devices="devices"
        :loading="loading"
        :toggle-loading="toggleLoading"
        :delete-loading="deleteLoading"
        :test-loading="testLoading"
        @edit="openEditDialog"
        @delete="confirmDelete"
        @toggle="toggleActiveStatus"
        @test-connection="testConnection"
        @set-default="setAsDefault"
        @create="openCreateDialog"
      />
    </template>

    <!-- Device Dialog -->
    <BluetoothDeviceDialog
      v-model="dialog"
      :edit-mode="editMode"
      :form-data="formData"
      :save-loading="saveLoading"
      :error-message="modalErrorMessage"
      @save="saveDevice"
      @close="closeDialog"
      @clear-error="clearModalError"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      :loading="deleteLoading !== null"
      :item-name="selectedDevice?.device_name || ''"
      item-type="perangkat bluetooth"
      @confirm="deleteDevice(selectedDevice!)"
      @cancel="deleteDialog = false"
    />
  </div>
</template>

<style scoped>
.coffee-title {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.coffee-subtitle {
  margin-top: 8px;
}

.bluetooth-management {
  padding: 24px;
}

@media (max-width: 960px) {
  .bluetooth-management {
    padding: 16px;
  }
}
</style>
