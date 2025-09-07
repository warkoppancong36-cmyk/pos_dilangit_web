<template>
  <VRow v-if="devices.length > 0">
    <VCol
      v-for="device in devices"
      :key="device.id_bluetooth_device"
      cols="12"
      md="6"
      lg="4"
    >
      <VCard class="h-100 device-card">
        <VCardText>
          <div class="d-flex align-center justify-space-between mb-3">
            <div class="d-flex align-center">
              <VAvatar
                size="40"
                :color="getDeviceTypeColor(device.device_type)"
                variant="tonal"
                class="me-3"
              >
                <VIcon :icon="getDeviceTypeIcon(device.device_type)" />
              </VAvatar>
              <div>
                <h6 class="text-h6 font-weight-semibold">{{ device.device_name }}</h6>
                <span class="text-caption text-medium-emphasis">{{ device.device_type_label || device.device_type }}</span>
              </div>
            </div>
            <VChip
              :color="device.is_active ? 'success' : 'error'"
              :variant="device.is_active ? 'tonal' : 'outlined'"
              size="small"
            >
              {{ device.is_active ? 'Aktif' : 'Tidak Aktif' }}
            </VChip>
          </div>

          <div class="mb-3">
            <div class="d-flex align-center mb-1">
              <VIcon icon="mdi-wifi" size="16" class="me-2 text-medium-emphasis" />
              <span class="text-sm">{{ device.device_address }}</span>
            </div>
            <div v-if="device.manufacturer" class="d-flex align-center mb-1">
              <VIcon icon="mdi-factory" size="16" class="me-2 text-medium-emphasis" />
              <span class="text-sm">{{ device.manufacturer }}</span>
            </div>
            <div v-if="device.model" class="d-flex align-center mb-1">
              <VIcon icon="mdi-tag-outline" size="16" class="me-2 text-medium-emphasis" />
              <span class="text-sm">{{ device.model }}</span>
            </div>
            <div v-if="device.last_connected_at" class="d-flex align-center">
              <VIcon icon="mdi-clock-outline" size="16" class="me-2 text-medium-emphasis" />
              <span class="text-sm">{{ formatDate(device.last_connected_at) }}</span>
            </div>
          </div>

          <div v-if="device.is_default" class="mb-3">
            <VChip color="primary" variant="outlined" size="small">
              <VIcon icon="mdi-star" start />
              Default
            </VChip>
          </div>

          <div v-if="device.notes" class="mb-3">
            <p class="text-sm text-medium-emphasis">{{ device.notes }}</p>
          </div>
        </VCardText>

        <VCardActions class="d-flex justify-space-between">
          <div class="d-flex gap-1">
            <VTooltip text="Edit Perangkat">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon
                  variant="text"
                  color="primary"
                  size="small"
                  @click="$emit('edit', device)"
                >
                  <VIcon icon="mdi-pencil" />
                </VBtn>
              </template>
            </VTooltip>

            <VTooltip text="Test Koneksi">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon
                  variant="text"
                  color="info"
                  size="small"
                  :loading="testLoading === device.id_bluetooth_device"
                  @click="$emit('test-connection', device)"
                >
                  <VIcon icon="mdi-lan-connect" />
                </VBtn>
              </template>
            </VTooltip>

            <VTooltip v-if="!device.is_default" text="Jadikan Default">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon
                  variant="text"
                  color="warning"
                  size="small"
                  @click="$emit('set-default', device)"
                >
                  <VIcon icon="mdi-star-outline" />
                </VBtn>
              </template>
            </VTooltip>
          </div>

          <div class="d-flex gap-1">
            <VTooltip :text="device.is_active ? 'Nonaktifkan' : 'Aktifkan'">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon
                  variant="text"
                  :color="device.is_active ? 'warning' : 'success'"
                  size="small"
                  :loading="toggleLoading === device.id_bluetooth_device"
                  @click="$emit('toggle', device)"
                >
                  <VIcon :icon="device.is_active ? 'mdi-pause' : 'mdi-play'" />
                </VBtn>
              </template>
            </VTooltip>

            <VTooltip text="Hapus Perangkat">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon
                  variant="text"
                  color="error"
                  size="small"
                  :loading="deleteLoading === device.id_bluetooth_device"
                  @click="$emit('delete', device)"
                >
                  <VIcon icon="mdi-delete" />
                </VBtn>
              </template>
            </VTooltip>
          </div>
        </VCardActions>
      </VCard>
    </VCol>
  </VRow>

  <!-- Empty State -->
  <VCard v-else class="text-center pa-12">
    <VIcon icon="mdi-bluetooth-off" size="64" class="text-medium-emphasis mb-4" />
    <h3 class="text-h5 mb-2">Belum Ada Perangkat</h3>
    <p class="text-body-1 text-medium-emphasis mb-4">Belum ada perangkat bluetooth yang terdaftar</p>
    <VBtn color="primary" prepend-icon="mdi-plus" @click="$emit('create')">
      Tambah Perangkat Pertama
    </VBtn>
  </VCard>
</template>

<script setup lang="ts">
import type { BluetoothDevice } from '@/composables/useBluetoothDevices'

interface Props {
  devices: BluetoothDevice[]
  toggleLoading: number | null
  deleteLoading: number | null
  testLoading: number | null
}

interface Emits {
  (e: 'edit', device: BluetoothDevice): void
  (e: 'delete', device: BluetoothDevice): void
  (e: 'toggle', device: BluetoothDevice): void
  (e: 'test-connection', device: BluetoothDevice): void
  (e: 'set-default', device: BluetoothDevice): void
  (e: 'create'): void
}

defineProps<Props>()
defineEmits<Emits>()

const getDeviceTypeIcon = (type: string) => {
  const icons = {
    printer: 'mdi-printer',
    scanner: 'mdi-qrcode-scan',
    cash_drawer: 'mdi-cash-register',
    scale: 'mdi-scale-bathroom',
    other: 'mdi-devices'
  }
  return icons[type as keyof typeof icons] || 'mdi-devices'
}

const getDeviceTypeColor = (type: string) => {
  const colors = {
    printer: 'primary',
    scanner: 'info',
    cash_drawer: 'success',
    scale: 'warning',
    other: 'secondary'
  }
  return colors[type as keyof typeof colors] || 'secondary'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.device-card {
  transition: transform 0.2s;
}

.device-card:hover {
  transform: translateY(-2px);
}
</style>
