<template>
  <VDialog
    v-model="localDialog"
    max-width="1200"
    scrollable
  >
    <VCard class="coffee-dialog">
      <VCardTitle class="d-flex align-center justify-space-between coffee-header">
        <div class="d-flex align-items-center gap-2">
          <VIcon
            icon="tabler-history"
            class="text-white"
          />
          <div>
            <div class="coffee-title">Riwayat Transaksi</div>
            <div class="coffee-subtitle">Daftar transaksi dan pembayaran</div>
          </div>
        </div>
        <VBtn
          icon="tabler-x"
          variant="text"
          color="white"
          @click="closeDialog"
        />
      </VCardTitle>
      
      <!-- Filters -->
      <VCardText class="pb-0 px-6">
        <VRow>
          <VCol cols="12" md="4">
            <VTextField
              v-model="searchQuery"
              label="Cari transaksi..."
              variant="outlined"
              density="compact"
              prepend-inner-icon="tabler-search"
              clearable
              hide-details
            />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect
              v-model="statusFilter"
              :items="statusOptions"
              label="Status"
              variant="outlined"
              density="compact"
              clearable
              hide-details
            />
          </VCol>
          <VCol cols="12" md="3">
            <VTextField
              v-model="dateFrom"
              label="Dari Tanggal"
              type="date"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="3">
            <VTextField
              v-model="dateTo"
              label="Sampai Tanggal"
              type="date"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
        </VRow>
      </VCardText>
      
      <VCardText class="px-6">
        <!-- Summary Cards -->
        <VRow class="mb-4">
          <VCol cols="12" md="3">
            <VCard variant="tonal" color="primary">
              <VCardText class="text-center">
                <h3 class="text-h4">{{ summary.total_orders }}</h3>
                <p class="text-body-2 mb-0">Total Transaksi</p>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" md="3">
            <VCard variant="tonal" color="success">
              <VCardText class="text-center">
                <h3 class="text-h4">{{ formatCurrency(summary.total_revenue) }}</h3>
                <p class="text-body-2 mb-0">Total Pendapatan</p>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" md="3">
            <VCard variant="tonal" color="info">
              <VCardText class="text-center">
                <h3 class="text-h4">{{ formatCurrency(summary.average_order) }}</h3>
                <p class="text-body-2 mb-0">Rata-rata per Transaksi</p>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" md="3">
            <VCard variant="tonal" color="warning">
              <VCardText class="text-center">
                <h3 class="text-h4">{{ summary.pending_orders }}</h3>
                <p class="text-body-2 mb-0">Transaksi Pending</p>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
        
        <!-- Transactions Table -->
        <VDataTable
          :headers="headers"
          :items="filteredTransactions"
          :loading="loading"
          :items-per-page="10"
          class="elevation-1"
        >
          <!-- Order Number Column -->
          <template #item.order_number="{ item }">
            <VBtn
              variant="text"
              color="primary"
              size="small"
              @click="viewOrderDetail(item)"
            >
              {{ item.order_number }}
            </VBtn>
          </template>
          
          <!-- Customer Column -->
          <template #item.customer="{ item }">
            <div v-if="item.customer">
              <div class="font-weight-medium">{{ item.customer.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.customer.phone }}</div>
            </div>
            <span v-else class="text-medium-emphasis">-</span>
          </template>
          
          <!-- Status Column -->
          <template #item.status="{ item }">
            <VChip
              :color="getStatusColor(item.status)"
              size="small"
              variant="tonal"
            >
              {{ getStatusText(item.status) }}
            </VChip>
          </template>
          
          <!-- Total Amount Column -->
          <template #item.total_amount="{ item }">
            <span class="font-weight-medium">
              {{ formatCurrency(item.total_amount) }}
            </span>
          </template>
          
          <!-- Payment Method Column -->
          <template #item.payment_method="{ item }">
            <div v-if="item.payments && item.payments.length > 0">
              <VChip
                v-for="payment in item.payments"
                :key="payment.id_payment"
                size="small"
                variant="outlined"
                class="ma-1"
              >
                {{ getPaymentMethodText(payment.payment_method) }}
              </VChip>
            </div>
            <span v-else class="text-medium-emphasis">-</span>
          </template>
          
          <!-- Created At Column -->
          <template #item.created_at="{ item }">
            <div>
              <div class="text-body-2">{{ formatDate(item.created_at) }}</div>
              <div class="text-caption text-medium-emphasis">{{ formatTime(item.created_at) }}</div>
            </div>
          </template>
          
          <!-- Actions Column -->
          <template #item.actions="{ item }">
            <VBtn
              icon="tabler-eye"
              variant="text"
              size="small"
              @click="viewOrderDetail(item)"
            />
            <VBtn
              icon="tabler-printer"
              variant="text"
              size="small"
              @click="printOrder(item)"
            />
            <VBtn
              v-if="item.status === 'pending'"
              icon="tabler-edit"
              variant="text"
              size="small"
              color="primary"
              @click="editOrder(item)"
            />
          </template>
        </VDataTable>
      </VCardText>
    </VCard>
    
    <!-- Order Detail Dialog -->
    <OrderDetailDialog
      v-model="detailDialog"
      :order="selectedOrder"
      @order-updated="onOrderUpdated"
    />

    <!-- Edit Order Dialog -->
    <EditOrderDialog
      v-model="editDialog"
      :order="selectedOrder"
      @order-updated="onOrderUpdated"
    />
  </VDialog>
</template>

<script setup lang="ts">
import { PosApi } from '@/utils/api/PosApi';
import { computed, onMounted, ref, watch } from 'vue';
import EditOrderDialog from './EditOrderDialog.vue';
import OrderDetailDialog from './OrderDetailDialog.vue';

// Props
interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// Reactive data
const loading = ref(false)
const transactions = ref<any[]>([])
const searchQuery = ref('')
const statusFilter = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const detailDialog = ref(false)
const editDialog = ref(false)
const selectedOrder = ref<any>(null)
const summary = ref({
  total_orders: 0,
  total_revenue: 0,
  average_order: 0,
  pending_orders: 0
})

// Table headers
const headers = [
  { title: 'No. Pesanan', key: 'order_number', sortable: true },
  { title: 'Pelanggan', key: 'customer', sortable: false },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Total', key: 'total_amount', sortable: true },
  { title: 'Pembayaran', key: 'payment_method', sortable: false },
  { title: 'Tanggal', key: 'created_at', sortable: true },
  { title: 'Aksi', key: 'actions', sortable: false, width: '120px' }
]

// Status options
const statusOptions = [
  { title: 'Pending', value: 'pending' },
  { title: 'Diproses', value: 'preparing' },
  { title: 'Selesai', value: 'completed' },
  { title: 'Dibatalkan', value: 'cancelled' }
]

// Computed
const localDialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const filteredTransactions = computed(() => {
  let filtered = transactions.value

  // Filter by search query
  if (searchQuery.value) {
    const search = searchQuery.value.toLowerCase()
    filtered = filtered.filter(transaction => {
      // Safe check for order_number
      const orderMatch = transaction.order_number?.toLowerCase()?.includes(search) || false
      
      // Safe check for customer name
      const customerNameMatch = transaction.customer?.name?.toLowerCase()?.includes(search) || false
      
      // Safe check for customer phone
      const customerPhoneMatch = transaction.customer?.phone?.toLowerCase()?.includes(search) || false
      
      return orderMatch || customerNameMatch || customerPhoneMatch
    })
  }

  // Filter by status
  if (statusFilter.value) {
    filtered = filtered.filter(transaction => transaction.status === statusFilter.value)
  }

  // Filter by date range
  if (dateFrom.value) {
    filtered = filtered.filter(transaction => 
      new Date(transaction.created_at) >= new Date(dateFrom.value)
    )
  }

  if (dateTo.value) {
    filtered = filtered.filter(transaction => 
      new Date(transaction.created_at) <= new Date(dateTo.value + ' 23:59:59')
    )
  }

  return filtered
})

// Methods
const loadTransactions = async () => {
  try {
    loading.value = true
    const response = await PosApi.getOrders()
    
    console.log('API Response structure:', response)
    
    // Data dari paginatedResponse berada di response.data (bukan response.data.data)
    if (response.success && Array.isArray(response.data)) {
      transactions.value = response.data
    } else {
      console.warn('Unexpected response structure:', response)
      transactions.value = []
    }
    
    console.log('Loaded transactions:', transactions.value.length)
    
    // Calculate summary
    calculateSummary()
  } catch (error) {
    console.error('Error loading transactions:', error)
    transactions.value = []
  } finally {
    loading.value = false
  }
}

const calculateSummary = () => {
  const filtered = filteredTransactions.value
  
  // Safe calculation with NaN handling
  const totalRevenue = filtered.reduce((sum, t) => {
    const amount = typeof t.total_amount === 'string' ? parseFloat(t.total_amount) : t.total_amount
    return sum + (isNaN(amount) || !isFinite(amount) ? 0 : amount)
  }, 0)
  
  const averageOrder = filtered.length > 0 && totalRevenue > 0 ? 
    totalRevenue / filtered.length : 0
  
  summary.value = {
    total_orders: filtered.length,
    total_revenue: totalRevenue,
    average_order: averageOrder,
    pending_orders: filtered.filter(t => t.status === 'pending').length
  }
}

const viewOrderDetail = (order: any) => {
  selectedOrder.value = order
  detailDialog.value = true
}

const printOrder = (order: any) => {
  // Implement print functionality
  console.log('Printing order:', order.order_number)
}

const editOrder = (order: any) => {
  selectedOrder.value = order
  editDialog.value = true
}

const onOrderUpdated = (updatedOrder: any) => {
  // Update the order in the transactions list
  const index = transactions.value.findIndex(t => t.id_order === updatedOrder.id_order)
  if (index !== -1) {
    transactions.value[index] = updatedOrder
  }
  // Recalculate summary
  calculateSummary()
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

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatTime = (date: string): string => {
  return new Date(date).toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const closeDialog = () => {
  localDialog.value = false
}

// Set default date range (today)
const setDefaultDates = () => {
  const today = new Date().toISOString().split('T')[0]
  // Set dari awal bulan sampai hari ini untuk menampilkan lebih banyak data
  const startOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1)
    .toISOString().split('T')[0]
  
  dateFrom.value = startOfMonth
  dateTo.value = today
}

// Lifecycle
onMounted(() => {
  setDefaultDates()
})

// Watch for dialog changes
watch(localDialog, (newValue) => {
  if (newValue) {
    loadTransactions()
  }
})

// Watch for filter changes
watch([searchQuery, statusFilter, dateFrom, dateTo], () => {
  calculateSummary()
})
</script>

<style scoped>
.v-data-table {
  border-radius: 8px;
  block-size: calc(100vh - 400px) !important;
}

.v-data-table :deep(.v-data-table__wrapper) {
  border-radius: 8px;
  max-block-size: calc(100vh - 400px) !important;
  overflow-y: auto;
}

.v-data-table :deep(.v-data-table-header) {
  background-color: rgba(var(--v-theme-primary), 0.05);
}

.v-data-table :deep(.v-data-table-rows-loading) {
  block-size: 200px;
}

/* Coffee Theme Styling */
.coffee-dialog {
  border-radius: 16px;
  block-size: 90vh !important;
}

.coffee-header {
  background: linear-gradient(135deg, #6f4e37 0%, #8b4513 100%);
  color: white;
  padding-block: 20px;
  padding-inline: 24px;
}

.coffee-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
}

.coffee-subtitle {
  margin: 0;
  font-size: 1rem;
  font-weight: 500;
  line-height: 1.2;
  opacity: 0.9;
}
</style>
