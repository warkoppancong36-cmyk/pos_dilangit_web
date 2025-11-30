<script setup lang="ts">
import { InventoryApi } from '@/utils/api/InventoryApi'
import { computed, ref, watch } from 'vue'

// Local interface for movements data from API
interface MovementItem {
  id_movement: number
  id_inventory: number
  movement_type: string
  quantity: number
  stock_before: number
  stock_after: number
  unit_cost?: number
  total_cost?: number
  reference_type?: string
  reference_id?: number
  batch_number?: string
  expiry_date?: string
  notes?: string
  created_by: number
  movement_date?: string
  created_at: string
  updated_at: string
  user?: { id: number; name: string; email?: string }
}

interface Props {
  modelValue: boolean
  inventoryId?: string
  inventoryName?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const movements = ref<MovementItem[]>([])
const loading = ref(false)
const errorMessage = ref('')

const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const fetchMovements = async () => {
  if (!props.inventoryId) return

  try {
    loading.value = true
    errorMessage.value = ''
    
    const response = await InventoryApi.getMovements({
      id_inventory: parseInt(props.inventoryId),
      page: 1,
      per_page: 50
    }) as { data?: MovementItem[]; [key: string]: any }
    // Handle Laravel pagination response
    if (response.data && Array.isArray(response.data)) {
      movements.value = response.data
    } else if (response.data && response?.data?.data && Array.isArray(response?.data?.data)) {
      movements.value = response.data.data
    } else {
      movements.value = []
    }
  } catch (error: any) {
    console.error('Error fetching movements:', error)
    errorMessage.value = error.response?.data?.message || 'Gagal memuat riwayat pergerakan'
  } finally {
    loading.value = false
  }
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}

const getMovementTypeColor = (type: string): string => {
  const colors: Record<string, string> = {
    in: 'success',
    out: 'error', 
    stock_in: 'success',
    stock_out: 'error',
    adjustment: 'warning',
    transfer: 'info',
    return: 'secondary'
  }
  return colors[type] || 'primary'
}

const getMovementTypeText = (type: string): string => {
  const types: Record<string, string> = {
    in: 'Stock Masuk',
    out: 'Stock Keluar',
    stock_in: 'Stock Masuk',
    stock_out: 'Stock Keluar',
    adjustment: 'Penyesuaian',
    transfer: 'Transfer',
    return: 'Retur'
  }
  return types[type] || type
}

const getMovementIcon = (type: string): string => {
  const icons: Record<string, string> = {
    in: 'tabler-plus',
    out: 'tabler-minus',
    stock_in: 'tabler-plus',
    stock_out: 'tabler-minus',
    adjustment: 'tabler-edit',
    transfer: 'tabler-arrows-exchange',
    return: 'tabler-arrow-back'
  }
  return icons[type] || 'tabler-package'
}

// Watch for dialog open/close
watch(dialog, (newValue) => {
  if (newValue && props.inventoryId) {
    fetchMovements()
  } else {
    movements.value = []
    errorMessage.value = ''
  }
})
</script>

<template>
  <VDialog
    v-model="dialog"
    max-width="1400px"
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex align-center">
        <VIcon icon="tabler-history" class="me-2" />
        Riwayat Pergerakan - {{ inventoryName }}
      </VCardTitle>
      
      <VDivider />

      <VCardText class="pa-0">
        <!-- Error Message -->
        <VAlert
          v-if="errorMessage"
          type="error"
          variant="tonal"
          class="ma-4"
        >
          {{ errorMessage }}
        </VAlert>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-8">
          <VProgressCircular
            indeterminate
            color="primary"
            size="40"
          />
          <div class="mt-4 text-medium-emphasis">
            Memuat riwayat pergerakan...
          </div>
        </div>

        <!-- Movements Table -->
        <VDataTable
          v-else
          :headers="[
            { title: 'Tanggal', key: 'created_at', sortable: false },
            { title: 'Jenis', key: 'movement_type', sortable: false },
            { title: 'Kuantitas', key: 'quantity', sortable: false },
            { title: 'Stok Sebelum', key: 'stock_before', sortable: false },
            { title: 'Stok Sesudah', key: 'stock_after', sortable: false },
            { title: 'Harga/Unit', key: 'unit_cost', sortable: false },
            { title: 'Total Nilai', key: 'total_cost', sortable: false },
            { title: 'Catatan', key: 'notes', sortable: false }
          ]"
          :items="movements"
          :loading="loading"
          class="text-no-wrap"
          items-per-page="15"
          height="500"
          fixed-header
        >
          <!-- Date -->
          <template #item.created_at="{ item }">
            <div>
              <div class="font-weight-medium">
                {{ new Date(item.created_at).toLocaleDateString('id-ID') }}
              </div>
              <small class="text-medium-emphasis">
                {{ new Date(item.created_at).toLocaleTimeString('id-ID') }}
              </small>
            </div>
          </template>

          <!-- Movement Type -->
          <template #item.movement_type="{ item }">
            <VChip
              :color="getMovementTypeColor(item.movement_type)"
              variant="tonal"
              size="small"
              :prepend-icon="getMovementIcon(item.movement_type)"
            >
              {{ getMovementTypeText(item.movement_type) }}
            </VChip>
          </template>

          <!-- Quantity -->
          <template #item.quantity="{ item }">
            <div class="text-center">
              <VChip
                :color="(item.movement_type === 'in' || item.movement_type === 'stock_in') ? 'success' : 'error'"
                variant="outlined"
                size="small"
              >
                {{ (item.movement_type === 'in' || item.movement_type === 'stock_in') ? '+' : '-' }}{{ Math.abs(item.quantity) }}
              </VChip>
            </div>
          </template>

          <!-- Stock Before -->
          <template #item.stock_before="{ item }">
            <div class="text-center font-weight-medium">
              {{ item.stock_before }}
            </div>
          </template>

          <!-- Stock After -->
          <template #item.stock_after="{ item }">
            <div class="text-center font-weight-bold text-primary">
              {{ item.stock_after }}
            </div>
          </template>

          <!-- Cost Per Unit -->
          <!-- Unit Cost -->
          <template #item.unit_cost="{ item }">
            <div class="text-end">
              {{ item.unit_cost ? formatCurrency(item.unit_cost) : '-' }}
            </div>
          </template>          <!-- Total Cost -->
          <template #item.total_cost="{ item }">
            <div class="text-end font-weight-medium text-success">
              {{ item.total_cost ? formatCurrency(item.total_cost) : '-' }}
            </div>
          </template>

          <!-- Notes -->
          <template #item.notes="{ item }">
            <div class="max-width-200">
              <div class="font-weight-medium">{{ item.notes || '-' }}</div>
              <small v-if="item.reference_type && item.reference_id" class="text-medium-emphasis">
                Ref: {{ item.reference_type }}-{{ item.reference_id }}
              </small>
              <small v-if="item.batch_number" class="d-block text-caption">
                Batch: {{ item.batch_number }}
              </small>
              <small v-if="item.user" class="text-info d-block">
                oleh: {{ item.user.name }}
              </small>
            </div>
          </template>

          <!-- No Data -->
          <template #no-data>
            <div class="text-center py-8">
              <VIcon
                icon="tabler-history-off"
                size="48"
                class="text-medium-emphasis mb-4"
              />
              <div class="text-h6 text-medium-emphasis mb-2">
                Belum ada riwayat pergerakan
              </div>
              <div class="text-body-2 text-medium-emphasis">
                Riwayat akan muncul setelah ada pergerakan stok
              </div>
            </div>
          </template>
        </VDataTable>
      </VCardText>

      <VDivider />
      
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="outlined"
          @click="dialog = false"
        >
          Tutup
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="tabler-refresh"
          @click="fetchMovements"
        >
          Refresh
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.max-width-200 {
  max-inline-size: 200px;
  word-wrap: break-word;
}
</style>
