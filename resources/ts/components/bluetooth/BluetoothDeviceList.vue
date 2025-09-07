<template>
  <VCard>
    <VDataTable
      :headers="headers"
      :items="devices"
      :loading="loading"
      item-value="id_bluetooth_device"
      class="text-no-wrap"
    >
      <!-- Device Name Column -->
      <template #item.device_name="{ item }">
        <div class="d-flex align-center">
          <VAvatar
            size="32"
            :color="getDeviceTypeColor(item.device_type)"
            variant="tonal"
            class="me-3"
          >
            <VIcon :icon="getDeviceTypeIcon(item.device_type)" size="16" />
          </VAvatar>
          <div>
            <div class="font-weight-semibold">{{ item.device_name }}</div>
            <div class="text-caption text-medium-emphasis">{{ item.device_address }}</div>
          </div>
        </div>
      </template>

      <!-- Device Type Column -->
      <template #item.device_type="{ item }">
        <VChip
          :color="getDeviceTypeColor(item.device_type)"
          variant="tonal"
          size="small"
        >
          <VIcon :icon="getDeviceTypeIcon(item.device_type)" start size="16" />
          {{ item.device_type_label || item.device_type }}
        </VChip>
      </template>

      <!-- Manufacturer Column -->
      <template #item.manufacturer="{ item }">
        <div>
          <div v-if="item.manufacturer" class="font-weight-medium">{{ item.manufacturer }}</div>
          <div v-if="item.model" class="text-caption text-medium-emphasis">{{ item.model }}</div>
          <span v-if="!item.manufacturer && !item.model" class="text-medium-emphasis">-</span>
        </div>
      </template>

      <!-- Status Column -->
      <template #item.is_active="{ item }">
        <div class="d-flex align-center gap-2">
          <VChip
            :color="item.is_active ? 'success' : 'error'"
            variant="tonal"
            size="small"
          >
            {{ item.is_active ? 'Aktif' : 'Tidak Aktif' }}
          </VChip>
          <VChip
            v-if="item.is_default"
            color="primary"
            variant="outlined"
            size="small"
          >
            <VIcon icon="mdi-star" start size="14" />
            Default
          </VChip>
        </div>
      </template>

      <!-- Last Connected Column -->
      <template #item.last_connected_at="{ item }">
        <div v-if="item.last_connected_at" class="text-sm">
          {{ formatDate(item.last_connected_at) }}
        </div>
        <span v-else class="text-medium-emphasis">Belum pernah</span>
      </template>

      <!-- Notes Column -->
      <template #item.notes="{ item }">
        <div v-if="item.notes" class="text-sm max-width-200">
          {{ truncateText(item.notes, 50) }}
        </div>
        <span v-else class="text-medium-emphasis">-</span>
      </template>

      <!-- Actions Column -->
      <template #item.actions="{ item }">
        <div class="d-flex gap-1">
          <VTooltip text="Edit Perangkat">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon
                variant="text"
                color="primary"
                size="small"
                @click="$emit('edit', item)"
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
                :loading="testLoading === item.id_bluetooth_device"
                @click="$emit('test-connection', item)"
              >
                <VIcon icon="mdi-lan-connect" />
              </VBtn>
            </template>
          </VTooltip>

          <VTooltip v-if="!item.is_default" text="Jadikan Default">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon
                variant="text"
                color="warning"
                size="small"
                @click="$emit('set-default', item)"
              >
                <VIcon icon="mdi-star-outline" />
              </VBtn>
            </template>
          </VTooltip>

          <VTooltip :text="item.is_active ? 'Nonaktifkan' : 'Aktifkan'">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon
                variant="text"
                :color="item.is_active ? 'warning' : 'success'"
                size="small"
                :loading="toggleLoading === item.id_bluetooth_device"
                @click="$emit('toggle', item)"
              >
                <VIcon :icon="item.is_active ? 'mdi-pause' : 'mdi-play'" />
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
                :loading="deleteLoading === item.id_bluetooth_device"
                @click="$emit('delete', item)"
              >
                <VIcon icon="mdi-delete" />
              </VBtn>
            </template>
          </VTooltip>
        </div>
      </template>

      <!-- Empty State -->
      <template #no-data>
        <div class="text-center pa-12">
          <VIcon icon="mdi-bluetooth-off" size="64" class="text-medium-emphasis mb-4" />
          <h3 class="text-h5 mb-2">Belum Ada Perangkat</h3>
          <p class="text-body-1 text-medium-emphasis mb-4">Belum ada perangkat bluetooth yang terdaftar</p>
          <VBtn color="primary" prepend-icon="mdi-plus" @click="$emit('create')">
            Tambah Perangkat Pertama
          </VBtn>
        </div>
      </template>
    </VDataTable>
  </VCard>
</template>

<script setup lang="ts">
import type { BluetoothDevice } from '@/composables/useBluetoothDevices'

interface Props {
  devices: BluetoothDevice[]
  loading: boolean
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

const headers = [
  { title: 'Perangkat', key: 'device_name', sortable: true },
  { title: 'Tipe', key: 'device_type', sortable: true },
  { title: 'Manufacturer', key: 'manufacturer', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Terakhir Terhubung', key: 'last_connected_at', sortable: true },
  { title: 'Catatan', key: 'notes', sortable: false },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'end' }
]

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

const truncateText = (text: string, maxLength: number) => {
  if (text.length <= maxLength) return text
  return text.slice(0, maxLength) + '...'
}
</script>

<style scoped>
.max-width-200 {
  max-width: 200px;
  word-wrap: break-word;
}
</style>
