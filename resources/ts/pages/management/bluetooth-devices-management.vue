<template>
  <div>
    <!-- Header -->
    <VCard>
      <VCardText class="pb-0">
        <div class="d-flex align-center justify-space-between mb-4">
          <div>
            <h2 class="text-h5 mb-2">
              Manajemen Perangkat Bluetooth
            </h2>
            <p class="text-body-1 mb-0">
              Kelola perangkat bluetooth yang terhubung dengan sistem POS
            </p>
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
      </VCardText>
    </VCard>

    <!-- Stats Cards -->
    <StatsCards
      :total-devices="totalDevices"
      :active-devices="activeDevices"
      :inactive-devices="inactiveDevices"
      :printer-devices="printerDevices"
      :other-devices="otherDevices"
    />

    <!-- Search and Filters -->
    <SearchFilters
      v-model:search="search"
      v-model:type-filter="typeFilter"
      v-model:active-only="activeOnly"
      v-model:view-mode="viewMode"
      :device-types="deviceTypes"
      @search="onSearch"
      @filter-change="onFilterChange"
      @refresh="fetchDevices"
    />

    <!-- Success/Error Messages -->
    <VAlert
      v-if="successMessage"
      type="success"
      variant="tonal"
      class="mb-4"
      closable
      @click:close="successMessage = ''"
    >
      {{ successMessage }}
    </VAlert>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-4"
      closable
      @click:close="errorMessage = ''"
    >
      {{ errorMessage }}
    </VAlert>

    <!-- Device List/Grid -->
    <VCard>
      <VCardText>
        <!-- Grid View -->
        <BluetoothDeviceGrid
          v-if="viewMode === 'grid'"
          :devices="devices"
          :loading="loading"
          :delete-loading="deleteLoading"
          :toggle-loading="toggleLoading"
          :test-loading="testLoading"
          :can-create-edit="canCreateEdit"
          @edit="openEditDialog"
          @delete="confirmDelete"
          @toggle-active="toggleActiveStatus"
          @set-default="setAsDefault"
          @test-connection="testConnection"
        />

        <!-- List View -->
        <BluetoothDeviceList
          v-else
          :devices="devices"
          :loading="loading"
          :delete-loading="deleteLoading"
          :toggle-loading="toggleLoading"
          :test-loading="testLoading"
          :can-create-edit="canCreateEdit"
          @edit="openEditDialog"
          @delete="confirmDelete"
          @toggle-active="toggleActiveStatus"
          @set-default="setAsDefault"
          @test-connection="testConnection"
        />
      </VCardText>
    </VCard>

    <!-- Add/Edit Dialog -->
    <BluetoothDeviceDialog
      v-model="dialog"
      :edit-mode="editMode"
      :form-data="formData"
      :device-types="deviceTypes"
      :loading="saveLoading"
      :error-message="modalErrorMessage"
      @save="saveDevice"
      @close="closeDialog"
    />

    <!-- Delete Confirmation Dialog -->
    <VDialog
      v-model="deleteDialog"
      max-width="400"
    >
      <VCard>
        <VCardTitle class="text-h6">
          Hapus Perangkat Bluetooth
        </VCardTitle>
        <VCardText>
          <p class="mb-4">
            Apakah Anda yakin ingin menghapus perangkat "<strong>{{ selectedDevice?.device_name }}</strong>"?
          </p>
          <VAlert
            type="warning"
            variant="tonal"
            class="mb-0"
          >
            Tindakan ini tidak dapat dibatalkan.
          </VAlert>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            @click="deleteDialog = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            variant="flat"
            :loading="deleteLoading === selectedDevice?.id_bluetooth_device"
            @click="deleteDevice(selectedDevice!)"
          >
            Hapus
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useBluetoothDevices } from '@/composables/useBluetoothDevices'
import StatsCards from '@/components/bluetooth/StatsCards.vue'
import SearchFilters from '@/components/bluetooth/SearchFilters.vue'
import BluetoothDeviceDialog from '@/components/bluetooth/BluetoothDeviceDialog.vue'
import BluetoothDeviceGrid from '@/components/bluetooth/BluetoothDeviceGrid.vue'
import BluetoothDeviceList from '@/components/bluetooth/BluetoothDeviceList.vue'

// Use composable
const {
  // State
  devices,
  loading,
  saveLoading,
  deleteLoading,
  toggleLoading,
  testLoading,
  
  // Dialog states
  dialog,
  deleteDialog,
  editMode,
  selectedDevice,
  
  // Form data
  formData,
  
  // Filters
  search,
  typeFilter,
  activeOnly,
  viewMode,
  
  // Messages
  successMessage,
  errorMessage,
  modalErrorMessage,
  
  // Computed
  totalDevices,
  activeDevices,
  inactiveDevices,
  printerDevices,
  otherDevices,
  deviceTypes,
  canCreateEdit,
  
  // Methods
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
  onSearch,
  onFilterChange
} = useBluetoothDevices()

// Initialize
onMounted(() => {
  fetchDevices()
})

// Set page title
definePageMeta({
  title: 'Manajemen Perangkat Bluetooth'
})
</script>

<style scoped>
/* Add any additional styles if needed */
</style>
