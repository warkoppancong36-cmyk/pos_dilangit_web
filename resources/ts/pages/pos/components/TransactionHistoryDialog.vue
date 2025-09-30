<template>
  <VDialog
    v-model="localDialog"
    max-width="1400"
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
          <VCol cols="12" md="3">
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
          <VCol cols="12" md="2">
            <VTextField
              v-model="dateFrom"
              label="Dari Tanggal"
              type="date"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="2">
            <VTextField
              v-model="dateTo"
              label="Sampai Tanggal"
              type="date"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="1">
            <VBtn
              color="success"
              variant="tonal"
              prepend-icon="tabler-download"
              @click="exportTransactions"
              :loading="exportLoading"
              block
            >
              Export
            </VBtn>
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
          :items="transactions"
          :loading="loading"
          :items-per-page="pagination.per_page === -1 ? transactions.length : pagination.per_page"
          :page="pagination.current_page"
          :items-length="pagination.total"
          :server-items-length="pagination.total"
          :items-per-page-options="itemsPerPageOptions"
          :show-select="false"
          class="elevation-1"
          @update:page="onPageChange"
          @update:items-per-page="onItemsPerPageChange"
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
        
        <!-- Custom "All" Mode Indicator -->
        <div v-if="pagination.per_page === -1" class="pa-4 text-center border-t">
          <VChip color="success" variant="tonal" size="large">
            <VIcon start icon="tabler-check-circle" />
            Menampilkan semua {{ transactions.length }} transaksi
          </VChip>
        </div>
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
const categoryFilter = ref('')
const dateFrom = ref(new Date().toISOString().split('T')[0]) // Default: today
const dateTo = ref(new Date().toISOString().split('T')[0])   // Default: today
const detailDialog = ref(false)
const editDialog = ref(false)
const selectedOrder = ref<any>(null)
const exportLoading = ref(false)
const pagination = ref({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1
})
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

// Items per page options
const itemsPerPageOptions = [
  { value: 10, title: '10' },
  { value: 25, title: '25' },
  { value: 50, title: '50' },
  { value: 100, title: '100' },
  { value: -1, title: 'All' }
]

// Computed
const localDialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Methods
const loadTransactions = async (page: number = 1, perPage: number = 15) => {
  try {
    loading.value = true
    
    console.log('📅 LoadTransactions dateFrom:', dateFrom.value)
    console.log('📅 LoadTransactions dateTo:', dateTo.value)
    
    // Build query parameters
    const params: any = {
      page,
      per_page: perPage >= 999999 ? 'all' : perPage // Send 'all' for very large numbers
    }
    
    // Add search filters
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    if (statusFilter.value) {
      params.status = statusFilter.value
    }
    if (categoryFilter.value) {
      params.category_id = categoryFilter.value
    }
    if (dateFrom.value) {
      params.date_from = dateFrom.value
    }
    if (dateTo.value) {
      params.date_to = dateTo.value
    }
    
    const response = await PosApi.getOrders(params)
    
    if (response.success) {
      // Check if response has pagination structure
      const responseData = response.data as any
      if (responseData?.data && Array.isArray(responseData.data)) {
        // Paginated response
        transactions.value = responseData.data
        pagination.value = {
          current_page: responseData.current_page || 1,
          per_page: perPage >= 999999 ? -1 : (responseData.per_page || 15), // Set -1 for "All"
          total: responseData.total || 0,
          last_page: responseData.last_page || 1
        }
      } else if (Array.isArray(responseData)) {
        // Direct array response (fallback)
        transactions.value = responseData
        pagination.value = {
          current_page: 1,
          per_page: perPage >= 999999 ? -1 : responseData.length,
          total: responseData.length,
          last_page: 1
        }
      } else {
        console.warn('Unexpected response structure:', response)
        transactions.value = []
        pagination.value = {
          current_page: 1,
          per_page: 15,
          total: 0,
          last_page: 1
        }
      }
    } else {
      transactions.value = []
      pagination.value = {
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1
      }
    }
    
    // Calculate summary
    calculateSummary()
  } catch (error) {
    console.error('Error loading transactions:', error)
    transactions.value = []
    pagination.value = {
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1
    }
  } finally {
    loading.value = false
  }
}

const onPageChange = (page: number) => {
  loadTransactions(page, pagination.value.per_page)
}

const onItemsPerPageChange = async (perPage: number) => {
  // Handle "All" option (value -1)
  if (perPage === -1) {
    // Show confirmation for loading all data
    const totalItems = pagination.value.total
    if (totalItems > 1000) {
      const confirm = window.confirm(
        `Anda akan memuat semua ${totalItems} transaksi. Ini mungkin memakan waktu lama. Lanjutkan?`
      )
      if (!confirm) {
        return // Cancel loading
      }
    }
    // Load all items by using a very large number or special parameter
    await loadTransactions(1, 999999) // Use a large number to get all items
  } else {
    await loadTransactions(1, perPage)
  }
}

const calculateSummary = () => {
  const currentTransactions = transactions.value
  
  // Safe calculation with NaN handling
  const totalRevenue = currentTransactions.reduce((sum, t) => {
    const amount = typeof t.total_amount === 'string' ? parseFloat(t.total_amount) : t.total_amount
    return sum + (isNaN(amount) || !isFinite(amount) ? 0 : amount)
  }, 0)
  
  const averageOrder = currentTransactions.length > 0 && totalRevenue > 0 ? 
    totalRevenue / currentTransactions.length : 0
  
  summary.value = {
    total_orders: pagination.value.total, // Use total from pagination (all pages)
    total_revenue: totalRevenue,
    average_order: averageOrder,
    pending_orders: currentTransactions.filter(t => t.status === 'pending').length
  }
}

const viewOrderDetail = (order: any) => {
  selectedOrder.value = order
  detailDialog.value = true
}

const printOrder = (order: any) => {
  // Implement print functionality
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


// Export transactions to Excel
const exportTransactions = async () => {
  try {
    exportLoading.value = true
    
    // Build query parameters - same as loadTransactions
    const params: any = {}
    
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    if (statusFilter.value) {
      params.payment_status = statusFilter.value  // Map status to payment_status for export API
    }
    if (categoryFilter.value) {
      params.category_id = categoryFilter.value
    }
    if (dateFrom.value) {
      params.start_date = dateFrom.value
    }
    if (dateTo.value) {
      params.end_date = dateTo.value
    }
    
    const result = await PosApi.exportOrdersToExcel(params)
    
  } catch (error) {
  
  } finally {
    exportLoading.value = false
  }
}

const closeDialog = () => {
  localDialog.value = false
}

// Set default date range (only if empty)
const setDefaultDates = () => {
  const today = new Date().toISOString().split('T')[0]
  // Set dari awal bulan sampai hari ini untuk menampilkan lebih banyak data
  const startOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1)
    .toISOString().split('T')[0]
  
  // Only set default if values are empty
  if (!dateFrom.value) {
    dateFrom.value = startOfMonth
  }
  if (!dateTo.value) {
    dateTo.value = today
  }
}

// Watch for dialog changes
watch(localDialog, (newValue) => {
  if (newValue) {
    loadTransactions()
  }
})

// Watch for filter changes - reload data from backend
let filterTimeout: ReturnType<typeof setTimeout> | null = null
watch([searchQuery, statusFilter, categoryFilter, dateFrom, dateTo], () => {
  // Debounce filter changes
  if (filterTimeout) {
    clearTimeout(filterTimeout)
  }
  
  filterTimeout = setTimeout(() => {
    loadTransactions(1, pagination.value.per_page) // Reset to page 1 when filtering
  }, 500) // 500ms debounce
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
