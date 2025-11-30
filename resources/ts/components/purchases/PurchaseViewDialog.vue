<template>
  <VDialog
    v-model="localDialog"
    max-width="900"
    persistent
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2">
          <VIcon>mdi-file-document-outline</VIcon>
          <span>Purchase Order Detail</span>
        </div>
        <VBtn
          icon="mdi-close"
          size="small"
          variant="text"
          @click="closeDialog"
        />
      </VCardTitle>

      <VCardText v-if="purchase">
        <!-- Header Information -->
        <VRow class="mb-6">
          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1">Purchase Information</VCardTitle>
              <VCardText>
                <div class="mb-2">
                  <strong>Purchase Number:</strong><br>
                  <span class="text-primary font-weight-medium">{{ purchase.purchase_number }}</span>
                </div>
                <div class="mb-2">
                  <strong>Status:</strong><br>
                  <VChip
                    :color="getStatusColor(purchase.status)"
                    size="small"
                    variant="elevated"
                  >
                    {{ getStatusLabel(purchase.status) }}
                  </VChip>
                </div>
                <div class="mb-2">
                  <strong>Tanggal Purchase:</strong><br>
                  {{ formatDate(purchase.purchase_date) }}
                </div>
                <div class="mb-2" v-if="purchase.expected_delivery_date">
                  <strong>Tanggal Kirim Diharapkan:</strong><br>
                  {{ formatDate(purchase.expected_delivery_date) }}
                </div>
                <div v-if="purchase.actual_delivery_date">
                  <strong>Tanggal Kirim Aktual:</strong><br>
                  {{ formatDate(purchase.actual_delivery_date) }}
                </div>
              </VCardText>
            </VCard>
          </VCol>

          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1">Supplier Information</VCardTitle>
              <VCardText>
                <div class="mb-2">
                  <strong>Nama Supplier:</strong><br>
                  {{ purchase.supplier?.name || '-' }}
                </div>
                <div class="mb-2">
                  <strong>Telepon:</strong><br>
                  {{ purchase.supplier?.phone || '-' }}
                </div>
                <div class="mb-2">
                  <strong>Email:</strong><br>
                  {{ purchase.supplier?.email || '-' }}
                </div>
                <div>
                  <strong>Alamat:</strong><br>
                  {{ purchase.supplier?.address || '-' }}
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <!-- Purchase Items -->
        <VCard variant="outlined" class="mb-6">
          <VCardTitle class="text-subtitle-1">Items Purchase</VCardTitle>
          <VTable>
            <thead>
              <tr>
                <th>Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Total Cost</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in purchase.items" :key="item.id_purchase_item">
                <td>
                  <div>
                    <p class="mb-1 font-weight-medium">{{ item.item?.name || '-' }}</p>
                    <p class="text-caption text-medium-emphasis mb-0" v-if="item.item?.code">
                      Code: {{ item.item.code }}
                    </p>
                    <p class="text-caption text-medium-emphasis mb-0" v-if="item.item?.description">
                      {{ item.item.description }}
                    </p>
                  </div>
                </td>
                <td class="text-center">
                  <VChip size="small" variant="outlined">
                    {{ Math.floor(item.quantity_ordered) }} {{ item.unit || 'pcs' }}
                  </VChip>
                  <br v-if="item.quantity_received > 0">
                  <VChip v-if="item.quantity_received > 0" size="x-small" variant="tonal" color="success" class="mt-1">
                    Received: {{ Math.floor(item.quantity_received) }}
                  </VChip>
                </td>
                <td class="text-right font-weight-medium">
                  {{ formatCurrency(item.unit_cost) }}
                </td>
                <td class="text-right font-weight-medium">
                  {{ formatCurrency(item.total_cost) }}
                </td>
                <td class="text-center">
                  <VChip
                    :color="getStatusColor(item.status)"
                    size="small"
                    variant="tonal"
                  >
                    {{ getStatusLabel(item.status) }}
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>

        <!-- Purchase Summary -->
        <VRow>
          <VCol cols="12" md="6">
            <VCard variant="outlined" v-if="purchase.notes">
              <VCardTitle class="text-subtitle-1">Catatan</VCardTitle>
              <VCardText>
                <p class="mb-0">{{ purchase.notes }}</p>
              </VCardText>
            </VCard>

            <VCard variant="outlined" class="mt-4">
              <VCardTitle class="text-subtitle-1">Purchase History</VCardTitle>
              <VCardText>
                <div class="mb-2">
                  <strong>Dibuat oleh:</strong><br>
                  {{ purchase.creator?.name || '-' }} • {{ formatDateTime(purchase.created_at) }}
                </div>
                <div v-if="purchase.updated_at && purchase.updated_at !== purchase.created_at">
                  <strong>Diupdate oleh:</strong><br>
                  {{ purchase.updater?.name || '-' }} • {{ formatDateTime(purchase.updated_at) }}
                </div>
              </VCardText>
            </VCard>
          </VCol>

          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1">Total Summary</VCardTitle>
              <VCardText>
                <div class="d-flex justify-space-between mb-2">
                  <span>Subtotal:</span>
                  <span class="font-weight-medium">{{ formatCurrency(purchase.subtotal) }}</span>
                </div>
                <div class="d-flex justify-space-between mb-2" v-if="purchase.discount_amount > 0">
                  <span>Diskon:</span>
                  <span class="text-success">-{{ formatCurrency(purchase.discount_amount) }}</span>
                </div>
                <!-- <div class="d-flex justify-space-between mb-2">
                  <span>PPN (11%):</span>
                  <span>{{ formatCurrency(purchase.tax_amount) }}</span>
                </div> -->
                <VDivider class="my-3" />
                <div class="d-flex justify-space-between">
                  <span class="text-h6 font-weight-bold">Total:</span>
                  <span class="text-h6 font-weight-bold text-primary">
                    {{ formatCurrency(purchase.total_amount) }}
                  </span>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="text"
          @click="closeDialog"
        >
          Tutup
        </VBtn>
        <VBtn
          v-if="canPrint"
          color="primary"
          variant="tonal"
          prepend-icon="mdi-printer"
          @click="printPurchase"
        >
          Print
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Props
interface Props {
  modelValue: boolean
  purchase?: any
}

const props = withDefaults(defineProps<Props>(), {
  purchase: null
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// Dialog state
const localDialog = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

// Computed
const canPrint = computed(() => {
  return props.purchase && props.purchase.status !== 'cancelled'
})

// Methods
const closeDialog = () => {
  localDialog.value = false
}

const formatCurrency = (amount: number | string): string => {
  const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  if (isNaN(numericAmount) || numericAmount === null || numericAmount === undefined) return 'Rp 0'
  
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(numericAmount)
}

const formatDate = (date: string | Date): string => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}

const formatDateTime = (datetime: string | Date): string => {
  if (!datetime) return '-'
  return new Date(datetime).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusColor = (status: string): string => {
  const colors: Record<string, string> = {
    pending: 'warning',
    partial: 'info',
    received: 'success',
    cancelled: 'error'
  }
  return colors[status] || 'default'
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    pending: 'Pending',
    partial: 'Sebagian',
    received: 'Diterima',
    cancelled: 'Dibatalkan'
  }
  return labels[status] || status
}

const printPurchase = () => {
  // Implement print functionality
  // This could open a print-friendly view or generate PDF
  window.print()
}
</script>

<style scoped>
.v-table {
  background-color: transparent;
}

@media print {
  .v-card-actions {
    display: none !important;
  }
}
</style>
