<template>
  <VDialog
    v-model="localDialog"
    max-width="700"
    scrollable
  >
    <VCard v-if="order" class="coffee-dialog">
      <VCardTitle class="d-flex align-center justify-space-between coffee-header">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="mdi-receipt-text"
            class="text-white"
          />
          <span class="text-white">
            Detail Pesanan {{ order.order_number }}
          </span>
        </div>
        <VBtn
          icon="tabler-x"
          variant="text"
          color="white"
          @click="closeDialog"
        />
      </VCardTitle>

      <VDivider />
      
      <VCardText>
        <!-- Order Info -->
        <VRow class="mb-4">
          <VCol cols="12" md="6">
            <VCard variant="tonal" color="primary">
              <VCardText>
                <h6 class="text-h6 mb-2">Informasi Pesanan</h6>
                <div class="info-item">
                  <span class="label">No. Pesanan:</span>
                  <span class="value">{{ order.order_number }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Status:</span>
                  <VChip
                    :color="getStatusColor(order.status)"
                    size="small"
                    variant="tonal"
                  >
                    {{ getStatusText(order.status) }}
                  </VChip>
                </div>
                <div class="info-item">
                  <span class="label">Tanggal:</span>
                  <span class="value">{{ formatDateTime(order.created_at) }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Kasir:</span>
                  <span class="value">{{ order.user?.name || '-' }}</span>
                </div>
              </VCardText>
            </VCard>
          </VCol>
          
          <VCol cols="12" md="6">
            <VCard variant="tonal" color="info">
              <VCardText>
                <h6 class="text-h6 mb-2">Informasi Pelanggan</h6>
                <div v-if="order.customer">
                  <div class="info-item">
                    <span class="label">Nama:</span>
                    <span class="value">{{ order.customer.name }}</span>
                  </div>
                  <div class="info-item">
                    <span class="label">Telepon:</span>
                    <span class="value">{{ order.customer.phone }}</span>
                  </div>
                  <div class="info-item">
                    <span class="label">Email:</span>
                    <span class="value">{{ order.customer.email || '-' }}</span>
                  </div>
                </div>
                <div v-else class="text-center text-medium-emphasis">
                  <VIcon icon="tabler-user-question" size="32" class="mb-2" />
                  <p class="text-body-2 mb-0">Pelanggan Umum</p>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
        
        <!-- Order Items -->
        <VCard class="mb-4">
          <VCardTitle>Item Pesanan</VCardTitle>
          <VCardText class="pa-0">
            <VTable>
              <thead>
                <tr>
                  <th>Produk</th>
                  <th class="text-center">Qty</th>
                  <th class="text-right">Harga Satuan</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in order.order_items" :key="item.id_order_item">
                  <td>
                    <div class="d-flex align-center">
                      <VImg
                        :src="item.product?.image || '/images/misc/no-image.png'"
                        :alt="item.product?.name"
                        width="40"
                        height="40"
                        class="rounded me-3"
                      />
                      <div>
                        <div class="font-weight-medium">{{ item.product?.name }}</div>
                        <div v-if="item.variant" class="text-caption text-medium-emphasis">
                          {{ item.variant.name }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="text-center">{{ item.quantity }}</td>
                  <td class="text-right">{{ formatCurrency(item.unit_price) }}</td>
                  <td class="text-right font-weight-medium">
                    {{ formatCurrency(item.total_price) }}
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
        
        <!-- Order Summary -->
        <VCard class="mb-4">
          <VCardTitle>Ringkasan Pembayaran</VCardTitle>
          <VCardText>
            <div class="summary-row">
              <span>Subtotal:</span>
              <span>{{ formatCurrency(order.subtotal) }}</span>
            </div>
            
            <div v-if="order.discount_amount > 0" class="summary-row text-success">
              <span>Diskon ({{ order.discount_type === 'percentage' ? order.discount_amount + '%' : 'Nominal' }}):</span>
              <span>-{{ formatCurrency(order.discount_amount) }}</span>
            </div>
            
            <div v-if="order.tax_amount > 0" class="summary-row">
              <span>Pajak:</span>
              <span>{{ formatCurrency(order.tax_amount) }}</span>
            </div>
            
            <VDivider class="my-3" />
            
            <div class="summary-row total-row">
              <span class="text-h6">Total:</span>
              <span class="text-h6 text-primary font-weight-bold">
                {{ formatCurrency(order.total_amount) }}
              </span>
            </div>
          </VCardText>
        </VCard>
        
        <!-- Payment Information -->
        <VCard v-if="order.payments && order.payments.length > 0" class="mb-4">
          <VCardTitle>Informasi Pembayaran</VCardTitle>
          <VCardText class="pa-0">
            <VTable>
              <thead>
                <tr>
                  <th>Metode</th>
                  <th>Jumlah</th>
                  <th>Status</th>
                  <th>Referensi</th>
                  <th>Tanggal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="payment in order.payments" :key="payment.id_payment">
                  <td>
                    <VChip
                      size="small"
                      variant="outlined"
                    >
                      {{ getPaymentMethodText(payment.payment_method) }}
                    </VChip>
                  </td>
                  <td class="font-weight-medium">
                    {{ formatCurrency(payment.amount) }}
                  </td>
                  <td>
                    <VChip
                      :color="getPaymentStatusColor(payment.status)"
                      size="small"
                      variant="tonal"
                    >
                      {{ getPaymentStatusText(payment.status) }}
                    </VChip>
                  </td>
                  <td>{{ payment.reference_number || '-' }}</td>
                  <td>{{ formatDateTime(payment.payment_date) }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
        
        <!-- Notes -->
        <VCard v-if="order.notes">
          <VCardTitle>Catatan</VCardTitle>
          <VCardText>
            <p class="text-body-1 mb-0">{{ order.notes }}</p>
          </VCardText>
        </VCard>
      </VCardText>
      
      <VCardActions class="pa-4">
        <VBtn
          variant="outlined"
          prepend-icon="tabler-printer"
          @click="printOrder"
        >
          Cetak Struk
        </VBtn>
        
        <VSpacer />
        
        <VBtn
          v-if="canEditOrder"
          color="warning"
          variant="outlined"
          prepend-icon="tabler-edit"
          @click="editOrder"
        >
          Edit
        </VBtn>
        
        <VBtn
          v-if="canCancelOrder"
          color="error"
          variant="outlined"
          prepend-icon="tabler-x"
          :disabled="isLoading"
          @click="showCancelConfirm = true"
        >
          Batalkan
        </VBtn>

        <VBtn
          v-if="canCompleteOrder"
          color="success"
          variant="flat"
          prepend-icon="tabler-check"
          :disabled="isLoading"
          @click="completeOrder"
        >
          Selesaikan
        </VBtn>        <VBtn
          variant="outlined"
          @click="closeDialog"
        >
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Cancel Confirmation Dialog -->
  <VDialog
    v-model="showCancelConfirm"
    max-width="450"
    persistent
  >
    <VCard>
      <VCardTitle class="d-flex align-center text-error">
        <VIcon icon="mdi-alert-circle" class="me-2" />
        Konfirmasi Pembatalan
      </VCardTitle>
      
      <VCardText>
        <p class="text-body-1 mb-2">
          Apakah Anda yakin ingin membatalkan pesanan 
          <strong>{{ order?.order_number }}</strong>?
        </p>
        <p class="text-body-2 text-medium-emphasis">
          Tindakan ini tidak dapat diurungkan dan pesanan akan dibatalkan secara permanen.
        </p>
      </VCardText>
      
      <VCardActions>
        <VSpacer />
        <VBtn
          variant="outlined"
          :disabled="isLoading"
          @click="showCancelConfirm = false"
        >
          Batal
        </VBtn>
        <VBtn
          color="error"
          variant="flat"
          :loading="isLoading"
          @click="cancelOrder"
        >
          Ya, Batalkan Pesanan
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { PosApi } from '@/utils/api/PosApi';
import { computed, ref } from 'vue';

// Props
interface Props {
  modelValue: boolean
  order: any
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'order-updated': [order: any]
}>()

// Reactive data
const isLoading = ref(false)
const showCancelConfirm = ref(false)

// Computed
const localDialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const canCancelOrder = computed(() => {
  return props.order && ['pending', 'preparing'].includes(props.order.status)
})

const canCompleteOrder = computed(() => {
  return props.order && ['preparing', 'ready'].includes(props.order.status)
})

const canEditOrder = computed(() => {
  return props.order && props.order.status === 'pending'
})

// Methods
const closeDialog = () => {
  localDialog.value = false
}

const printOrder = () => {
  // Implement print functionality
  console.log('Printing order:', props.order.order_number)
  window.print()
}

const editOrder = () => {
  // Implement edit functionality
  console.log('Editing order:', props.order.order_number)
  // You could emit an event or navigate to edit page
}

const cancelOrder = async () => {
  try {
    isLoading.value = true
    await PosApi.updateOrderStatus(props.order.id_order, { status: 'cancelled' })
    emit('order-updated', { ...props.order, status: 'cancelled' })
    showCancelConfirm.value = false
    closeDialog()
    // Show success message (you can add toast notification here)
    console.log('Order cancelled successfully')
  } catch (error) {
    console.error('Error cancelling order:', error)
    // Show error message (you can add toast notification here)
  } finally {
    isLoading.value = false
  }
}

const completeOrder = async () => {
  try {
    isLoading.value = true
    await PosApi.updateOrderStatus(props.order.id_order, { status: 'completed' })
    emit('order-updated', { ...props.order, status: 'completed' })
    closeDialog()
    console.log('Order completed successfully')
  } catch (error) {
    console.error('Error completing order:', error)
  } finally {
    isLoading.value = false
  }
}

const getStatusColor = (status: string) => {
  const colors = {
    pending: 'warning',
    preparing: 'info',
    completed: 'success',
    cancelled: 'error'
  }
  return colors[status as keyof typeof colors] || 'default'
}

const getStatusText = (status: string) => {
  const texts = {
    pending: 'Pending',
    preparing: 'Diproses',
    completed: 'Selesai',
    cancelled: 'Dibatalkan'
  }
  return texts[status as keyof typeof texts] || status
}

const getPaymentMethodText = (method: string) => {
  const methods = {
    cash: 'Tunai',
    card: 'Kartu',
    digital_wallet: 'E-Wallet',
    qris: 'QRIS',
    bank_transfer: 'Transfer'
  }
  return methods[method as keyof typeof methods] || method
}

const getPaymentStatusColor = (status: string) => {
  const colors = {
    pending: 'warning',
    completed: 'success',
    failed: 'error',
    cancelled: 'error'
  }
  return colors[status as keyof typeof colors] || 'default'
}

const getPaymentStatusText = (status: string) => {
  const texts = {
    pending: 'Menunggu',
    completed: 'Berhasil',
    failed: 'Gagal',
    cancelled: 'Dibatalkan'
  }
  return texts[status as keyof typeof texts] || status
}

const formatCurrency = (amount: number | string): string => {
  // Convert string to number if needed
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  
  // Check if the result is a valid number
  if (isNaN(numAmount) || !isFinite(numAmount)) {
    return 'Rp 0'
  }
  
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(numAmount)
}

const formatDateTime = (date: string): string => {
  return new Date(date).toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.info-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block-end: 8px;
}

.info-item .label {
  color: rgba(var(--v-theme-on-surface), 0.7);
  font-weight: 500;
}

.info-item .value {
  font-weight: 600;
}

.summary-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block-end: 8px;
}

.total-row {
  margin-block-end: 0;
  padding-block-start: 8px;
}

.v-table th {
  color: rgba(var(--v-theme-on-surface), 0.8);
  font-weight: 600;
}

.v-table td {
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.v-table tbody tr:last-child td {
  border-block-end: none;
}

@media print {
  .v-card-actions {
    display: none !important;
  }

  .v-card-title .v-btn {
    display: none !important;
  }
}
</style>
