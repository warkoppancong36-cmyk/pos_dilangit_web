<template>
  <VContainer fluid>
    <!-- Header -->
    <VRow class="mb-4">
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center">
          <div>
            <h1 class="text-h4 font-weight-bold">Purchase Management</h1>
            <p class="text-body-1 text-medium-emphasis">Kelola pembelian barang dari supplier</p>
          </div>
          <div class="d-flex gap-2 align-center">
            <VBtn
              color="success"
              variant="elevated"
              size="large"
              :loading="exportLoading"
              @click="openExportDialog"
            >
              <VIcon start>tabler-file-spreadsheet</VIcon>
              Export Excel
            </VBtn>
            <VBtn
              color="primary"
              size="large"
              @click="openCreateDialog"
            >
              <VIcon start>mdi-plus</VIcon>
              Buat Purchase Order
            </VBtn>
          </div>
        </div>
      </VCol>
    </VRow>

    <!-- Statistics Cards -->
    <VRow class="mb-6">
      <VCol cols="12" sm="6" md="3">
        <VCard color="primary">
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-white mb-1">Total Purchase</p>
                <p class="text-h4 font-weight-bold text-white">{{ statistics.total_purchases || 0 }}</p>
              </div>
              <VIcon size="40" class="text-white">mdi-cart</VIcon>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <VCard color="warning">
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-white mb-1">Pending</p>
                <p class="text-h4 font-weight-bold text-white">{{ statistics.pending_purchases || 0 }}</p>
              </div>
              <VIcon size="40" class="text-white">mdi-clock</VIcon>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <VCard color="success">
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-white mb-1">Completed</p>
                <p class="text-h4 font-weight-bold text-white">{{ statistics.completed_purchases || 0 }}</p>
              </div>
              <VIcon size="40" class="text-white">mdi-check-circle</VIcon>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <VCard color="info">
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-white mb-1">Bulan Ini</p>
                <p class="text-h4 font-weight-bold text-white">{{ formatCurrency(statistics.total_amount_this_month || 0) }}</p>
              </div>
              <VIcon size="40" class="text-white">mdi-currency-usd</VIcon>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filters -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VTextField
              v-model="filters.search"
              label="Cari Purchase, Supplier, atau Item"
              placeholder="Masukkan nomor purchase, nama supplier, atau nama item"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              clearable
              @click:clear="filters.search = ''"
            />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.status"
              label="Status"
              :items="statusOptions"
              item-title="label"
              item-value="value"
              variant="outlined"
              density="compact"
              clearable
            />
          </VCol>
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.supplier_id"
              label="Supplier"
              :items="suppliers"
              item-title="name"
              item-value="id_supplier"
              variant="outlined"
              density="compact"
              clearable
            />
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="3">
            <VTextField
              v-model="filters.start_date"
              label="Tanggal Mulai"
              type="date"
              variant="outlined"
              density="compact"
              clearable
            />
          </VCol>
          <VCol cols="12" md="3">
            <VTextField
              v-model="filters.end_date"
              label="Tanggal Akhir"
              type="date"
              variant="outlined"
              density="compact"
              clearable
            />
          </VCol>
          <VCol cols="12" md="6" class="d-flex align-center gap-2">
            <VBtn
              color="primary"
              variant="tonal"
              prepend-icon="mdi-refresh"
              @click="loadPurchases"
            >
              Refresh
            </VBtn>
            <VBtn
              color="secondary"
              variant="outlined"
              prepend-icon="mdi-filter-off"
              @click="clearFilters"
            >
              Clear Filters
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Info Card -->
    <VAlert
      type="info"
      variant="tonal"
      class="mb-4"
      density="compact"
    >
      <template #text>
        <div class="d-flex align-center gap-2">
          <VIcon icon="mdi-information" size="16" />
          <span class="text-caption">
            <strong>Tips:</strong> Gunakan kotak pencarian untuk mencari purchase berdasarkan nomor purchase, nama supplier, atau nama item yang dibeli.
          </span>
        </div>
      </template>
    </VAlert>

    <!-- Purchase Table -->
    <VCard>
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="purchases"
        :items-length="totalItems"
        :loading="loading"
        item-value="id_purchase"
        @update:options="loadPurchases"
      >
        <template #item.purchase_info="{ item }">
          <div class="d-flex flex-column">
            <VBtn
              variant="text"
              color="primary"
              size="small"
              class="justify-start pa-0"
              @click="viewPurchase(item)"
            >
              {{ item.purchase_number }}
            </VBtn>
            <div class="mt-1">
              <p class="mb-0 font-weight-medium text-body-2">{{ item.supplier?.name || '-' }}</p>
              <p class="text-caption text-medium-emphasis mb-0">{{ item.supplier?.phone || '-' }}</p>
            </div>
          </div>
        </template>

        <template #item.items="{ item }">
          <div class="max-width-200">
            <div v-if="item.items && item.items.length > 0" class="d-flex flex-column ga-1">
              <div v-for="(purchaseItem, index) in item.items.slice(0, 2)" :key="index" class="d-flex align-center ga-1">
                <VChip
                  size="x-small"
                  color="primary"
                  variant="tonal"
                >
                  {{ purchaseItem.item?.name || 'Unknown Item' }}
                </VChip>
                <span class="text-caption">x{{ formatQuantity(purchaseItem.quantity_ordered) }}</span>
              </div>
              <div v-if="item.items.length > 2" class="text-caption text-medium-emphasis">
                +{{ item.items.length - 2 }} item lainnya
              </div>
            </div>
            <span v-else class="text-caption text-medium-emphasis">Tidak ada item</span>
          </div>
        </template>

        <template #item.purchase_date="{ item }">
          {{ formatDate(item.purchase_date) }}
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="getStatusColor(item.status)"
            size="small"
            variant="elevated"
          >
            {{ getStatusLabel(item.status) }}
          </VChip>
        </template>

        <template #item.total_amount="{ item }">
          <span class="font-weight-medium">{{ formatCurrency(item.total_amount) }}</span>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon="mdi-eye"
              size="small"
              variant="text"
              color="info"
              @click="viewPurchase(item)"
            />
            <VBtn
              v-if="canEdit(item)"
              icon="mdi-pencil"
              size="small"
              variant="text"
              color="primary"
              @click="editPurchase(item)"
            />
            <VBtn
              v-if="['ordered', 'received', 'completed'].includes(item.status)"
              icon="mdi-truck-delivery"
              size="small"
              variant="text"
              color="success"
              @click="receivePurchase(item)"
            />
            <VBtn
              v-if="canDelete(item)"
              icon="mdi-delete"
              size="small"
              variant="text"
              color="error"
              @click="deletePurchase(item)"
            />
            <VMenu>
              <template #activator="{ props }">
                <VBtn
                  icon="mdi-dots-vertical"
                  size="small"
                  variant="text"
                  v-bind="props"
                />
              </template>
              <VList>
                <VListItem @click="updateStatus(item, 'ordered')" v-if="item.status === 'pending'">
                  <VListItemTitle>Mark as Ordered</VListItemTitle>
                </VListItem>
                <VListItem @click="updateStatus(item, 'received')" v-if="item.status === 'ordered'">
                  <VListItemTitle>Mark as Received</VListItemTitle>
                </VListItem>
                <VListItem @click="updateStatus(item, 'completed')" v-if="item.status === 'received'">
                  <VListItemTitle>Mark as Completed</VListItemTitle>
                </VListItem>
                <VListItem @click="updateStatus(item, 'cancelled')" v-if="['pending', 'ordered'].includes(item.status)">
                  <VListItemTitle class="text-error">Cancel Purchase</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </div>
        </template>

        <template #bottom>
          <div class="d-flex justify-center pt-2">
            <VPagination
              v-model="page"
              :length="Math.ceil(totalItems / itemsPerPage)"
              :total-visible="5"
            />
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Purchase Dialog -->
    <PurchaseDialog
      v-model="dialogOpen"
      :purchase="selectedPurchase"
      :mode="dialogMode"
      @saved="handlePurchaseSaved"
    />

    <!-- View Purchase Dialog -->
    <PurchaseViewDialog
      v-model="viewDialogOpen"
      :purchase="selectedPurchase"
    />

    <!-- Receive Purchase Dialog -->
    <PurchaseReceiveDialog
      v-model="receiveDialogOpen"
      :purchase="selectedPurchase"
      @success="onReceiveSuccess"
    />

    <!-- Delete Confirmation Dialog -->
    <VDialog
      v-model="deleteDialogOpen"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="text-h5 d-flex align-center">
          <VIcon color="error" class="me-2">mdi-delete-alert</VIcon>
          Konfirmasi Hapus Purchase
        </VCardTitle>
        
        <VCardText>
          <VAlert
            type="warning"
            variant="tonal"
            class="mb-4"
          >
            <VAlertTitle>Peringatan!</VAlertTitle>
            Tindakan ini tidak dapat dibatalkan. Purchase yang sudah dihapus tidak dapat dikembalikan.
          </VAlert>
          
          <p class="text-body-1">
            Apakah Anda yakin ingin menghapus purchase berikut?
          </p>
          
          <div class="bg-grey-lighten-4 pa-3 rounded mt-3" v-if="purchaseToDelete">
            <div class="d-flex justify-space-between mb-2">
              <span class="font-weight-medium">Purchase Number:</span>
              <span>{{ purchaseToDelete.purchase_number }}</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="font-weight-medium">Supplier:</span>
              <span>{{ purchaseToDelete.supplier?.name }}</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="font-weight-medium">Total Item:</span>
              <span>{{ getTotalItems(purchaseToDelete) }} item</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="font-weight-medium">Total Harga:</span>
              <span>{{ formatCurrency(purchaseToDelete.total_amount) }}</span>
            </div>
            <div class="d-flex justify-space-between">
              <span class="font-weight-medium">Status:</span>
              <VChip 
                :color="getStatusColor(purchaseToDelete.status)" 
                size="small"
              >
                {{ getStatusLabel(purchaseToDelete.status) }}
              </VChip>
            </div>
            
            <!-- Items breakdown -->
            <VDivider class="my-3" />
            <div class="text-caption text-medium-emphasis mb-2">Detail Items:</div>
            <div class="max-height-150 overflow-y-auto">
              <div 
                v-for="item in purchaseToDelete.items" 
                :key="item.id_purchase_item"
                class="d-flex justify-space-between text-caption mb-1"
              >
                <span>{{ item.item?.name || 'Unknown Item' }}</span>
                <span>{{ formatQuantity(item.quantity_ordered) }} x {{ formatCurrency(item.unit_cost) }}</span>
              </div>
            </div>
          </div>
        </VCardText>
        
        <VCardActions>
          <VSpacer />
          <VBtn
            color="grey"
            variant="text"
            @click="deleteDialogOpen = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            variant="flat"
            :loading="deleteLoading"
            @click="confirmDeletePurchase"
          >
            <VIcon start>mdi-delete</VIcon>
            Hapus Purchase
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Export Dialog -->
    <VDialog
      v-model="exportDialog"
      max-width="500"
      persistent
    >
      <VCard>
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon color="success">tabler-file-spreadsheet</VIcon>
          Export Data Pembelian ke Excel
        </VCardTitle>

        <VDivider />

        <VCardText>
          <div class="mb-4">
            <h6 class="text-h6 mb-2">Filter yang akan diterapkan:</h6>
            <div class="d-flex flex-column gap-2">
              <VChip
                v-if="filters.search"
                color="primary"
                variant="tonal"
                size="small"
                prepend-icon="tabler-search"
              >
                Pencarian: "{{ filters.search }}"
              </VChip>

              <VChip
                v-if="filters.status"
                color="info"
                variant="tonal"
                size="small"
                prepend-icon="tabler-list-check"
              >
                Status: {{ getStatusLabel(filters.status) }}
              </VChip>

              <VChip
                v-if="filters.supplier_id"
                color="warning"
                variant="tonal"
                size="small"
                prepend-icon="tabler-truck"
              >
                Supplier: {{ getSupplierName(filters.supplier_id) }}
              </VChip>

              <VChip
                v-if="filters.start_date"
                color="primary"
                variant="tonal"
                size="small"
                prepend-icon="tabler-calendar-event"
              >
                Dari: {{ filters.start_date }}
              </VChip>

              <VChip
                v-if="filters.end_date"
                color="primary"
                variant="tonal"
                size="small"
                prepend-icon="tabler-calendar-check"
              >
                Sampai: {{ filters.end_date }}
              </VChip>

              <VChip
                v-if="!filters.search && !filters.status && !filters.supplier_id && !filters.start_date && !filters.end_date"
                color="success"
                variant="tonal"
                size="small"
                prepend-icon="tabler-check"
              >
                Semua Data (Tanpa Filter)
              </VChip>
            </div>
          </div>

          <VAlert
            type="info"
            variant="tonal"
            class="mb-4"
          >
            Data yang akan di-export mengikuti filter yang sedang aktif.
            File Excel berisi 2 sheet: ringkasan per purchase dan detail item pembelian.
          </VAlert>
        </VCardText>

        <VDivider />

        <VCardActions>
          <VSpacer />
          <VBtn
            variant="outlined"
            :disabled="exportLoading"
            @click="closeExportDialog"
          >
            Batal
          </VBtn>
          <VBtn
            color="success"
            variant="elevated"
            :loading="exportLoading"
            @click="exportToExcel(); closeExportDialog()"
          >
            <VIcon start>tabler-file-spreadsheet</VIcon>
            Export ke Excel
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Snackbar -->
    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="4000"
      location="top right"
    >
      {{ snackbar.message }}
    </VSnackbar>
  </VContainer>
</template>

<script setup lang="ts">
import PurchaseDialog from '@/components/purchases/PurchaseDialog.vue'
import PurchaseReceiveDialog from '@/components/purchases/PurchaseReceiveDialog.vue'
import PurchaseViewDialog from '@/components/purchases/PurchaseViewDialog.vue'
import { getAuthToken } from '@/utils/auth'
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'

// Data
const purchases = ref<any[]>([])
const suppliers = ref<any[]>([])
const statistics = ref<any>({})
const loading = ref(false)
const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Dialog states
const dialogOpen = ref(false)
const viewDialogOpen = ref(false)
const receiveDialogOpen = ref(false)
const deleteDialogOpen = ref(false)

// Export states
const exportLoading = ref(false)
const exportDialog = ref(false)
const selectedPurchase = ref<any>(null)
const purchaseToDelete = ref<any>(null)
const deleteLoading = ref(false)
const dialogMode = ref<'create' | 'edit' | 'view'>('create')

// Filters
const filters = ref({
  search: '',
  status: '',
  supplier_id: '',
  start_date: '',
  end_date: ''
})

// Table headers
const headers = [
  { title: 'Purchase & Supplier', key: 'purchase_info', sortable: false },
  { title: 'Items', key: 'items', sortable: false },
  { title: 'Tanggal', key: 'purchase_date', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Total', key: 'total_amount', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '150px' }
]

// Status options
const statusOptions = [
  { label: 'Semua Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Ordered', value: 'ordered' },
  { label: 'Received', value: 'received' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
]

// Methods
const loadPurchases = async () => {
  try {
    loading.value = true
    
    // Clean params - only send non-empty values
    const cleanParams = {
      page: page.value,
      per_page: itemsPerPage.value,
    }
    
    // Add only non-empty filter values
    Object.keys(filters.value).forEach(key => {
      if (filters.value[key] && filters.value[key] !== '') {
        cleanParams[key] = filters.value[key]
      }
    })

    const token = getAuthToken()
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }

    const response = await axios.get('/api/purchases', { 
      params: cleanParams,
      headers 
    })
    
    purchases.value = response.data.data.data || []
    totalItems.value = response.data.data.total || 0
    
  } catch (error) {
    console.error('Error loading purchases:', error)
    
    // Show error snackbar
    snackbar.value = {
      show: true,
      message: 'Error loading purchases: ' + (error.response?.data?.message || error.message),
      color: 'error'
    }
  } finally {
    loading.value = false
  }
}

const loadSuppliers = async () => {
  try {
    const token = getAuthToken()
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }

    // Request all suppliers without pagination and status filter
    const response = await axios.get('/api/suppliers?per_page=all', { headers })
    suppliers.value = response.data.data || []
  } catch (error) {
    console.error('Error loading suppliers:', error)
  }
}

const loadStatistics = async () => {
  try {
    const token = getAuthToken()
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }

    const response = await axios.get('/api/purchases/statistics', { headers })
    statistics.value = response.data.data || {}
  } catch (error) {
    console.error('Error loading statistics:', error)
  }
}

const clearFilters = () => {
  filters.value = {
    search: '',
    status: '',
    supplier_id: '',
    start_date: '',
    end_date: ''
  }
  loadPurchases()
}

// Export methods
const openExportDialog = () => {
  exportDialog.value = true
}

const closeExportDialog = () => {
  exportDialog.value = false
}

const getSupplierName = (supplierId: any) => {
  const supplier = suppliers.value.find(s => s.id_supplier === supplierId)
  return supplier?.name || supplierId
}

const parseAmount = (value: any) => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  return isNaN(num) ? 0 : num
}

// Fetch ALL purchases for export in chunks so no single request can hang or
// hit server time/memory limits on large datasets
const fetchAllPurchasesForExport = async () => {
  const baseParams: Record<string, any> = {}

  Object.entries(filters.value).forEach(([key, value]) => {
    if (value && value !== '') {
      baseParams[key] = value
    }
  })

  const token = getAuthToken()
  const requestHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }

  if (token) {
    requestHeaders['Authorization'] = `Bearer ${token}`
  }

  const allData: any[] = []
  const perPage = 500
  let currentPage = 1
  let lastPage = 1

  do {
    const response = await axios.get('/api/purchases', {
      params: { ...baseParams, page: currentPage, per_page: perPage },
      headers: requestHeaders,
      timeout: 60000
    })

    const payload = response.data.data
    allData.push(...(payload.data || []))
    lastPage = payload.last_page || 1
    currentPage++
  } while (currentPage <= lastPage)

  // Remove duplicates based on id_purchase
  return allData.filter((purchase: any, index: number, self: any[]) =>
    index === self.findIndex((t: any) => t.id_purchase === purchase.id_purchase)
  )
}

const exportToExcel = async () => {
  try {
    exportLoading.value = true

    // Fetch ALL purchases for export (not limited to current page)
    const allPurchases = await fetchAllPurchasesForExport()

    // Dynamic import for better performance
    const XLSX = await import('xlsx')

    // Summary statistics
    const totalPurchases = allPurchases.length
    const totalValue = allPurchases.reduce((sum: number, purchase: any) =>
      sum + parseAmount(purchase.total_amount), 0
    )
    const statusCounts: Record<string, number> = {}
    allPurchases.forEach((purchase: any) => {
      statusCounts[purchase.status] = (statusCounts[purchase.status] || 0) + 1
    })

    // Sheet 1: one row per purchase
    const purchaseRows = allPurchases.map((purchase: any, index: number) => ({
      'No': index + 1,
      'No. Purchase': purchase.purchase_number,
      'Supplier': purchase.supplier?.name || '-',
      'Telepon Supplier': purchase.supplier?.phone || '-',
      'Tanggal': formatDate(purchase.purchase_date),
      'Status': getStatusLabel(purchase.status),
      'Jenis Item': getTotalVariety(purchase),
      'Total Qty': getTotalItems(purchase),
      'Subtotal': parseAmount(purchase.subtotal),
      'Pajak': parseAmount(purchase.tax_amount),
      'Diskon': parseAmount(purchase.discount_amount),
      'Total': parseAmount(purchase.total_amount),
      'Catatan': purchase.notes || '-',
      'Dibuat': purchase.created_at ? new Date(purchase.created_at).toLocaleDateString('id-ID') : '-'
    }))

    // Sheet 2: one row per purchase item (detail)
    const itemRows: Record<string, any>[] = []
    allPurchases.forEach((purchase: any) => {
      (purchase.items || []).forEach((purchaseItem: any) => {
        itemRows.push({
          'No': itemRows.length + 1,
          'No. Purchase': purchase.purchase_number,
          'Supplier': purchase.supplier?.name || '-',
          'Tanggal': formatDate(purchase.purchase_date),
          'Status Purchase': getStatusLabel(purchase.status),
          'Nama Item': purchaseItem.item?.name || 'Unknown Item',
          'Kode Item': purchaseItem.item?.item_code || '-',
          'Satuan': purchaseItem.item?.unit || '-',
          'Qty Dipesan': parseAmount(purchaseItem.quantity_ordered),
          'Qty Diterima': parseAmount(purchaseItem.quantity_received),
          'Harga Satuan': parseAmount(purchaseItem.unit_cost),
          'Total Harga': parseAmount(purchaseItem.total_cost) || parseAmount(purchaseItem.quantity_ordered) * parseAmount(purchaseItem.unit_cost)
        })
      })
    })

    // Filter info for report header
    const filterInfo: string[] = []
    if (filters.value.search) {
      filterInfo.push(`Pencarian: "${filters.value.search}"`)
    }
    if (filters.value.status) {
      filterInfo.push(`Status: ${getStatusLabel(filters.value.status)}`)
    }
    if (filters.value.supplier_id) {
      filterInfo.push(`Supplier: ${getSupplierName(filters.value.supplier_id)}`)
    }
    if (filters.value.start_date) {
      filterInfo.push(`Dari: ${filters.value.start_date}`)
    }
    if (filters.value.end_date) {
      filterInfo.push(`Sampai: ${filters.value.end_date}`)
    }

    const titleData = [
      ['LAPORAN DATA PEMBELIAN'],
      ['Tanggal Export:', new Date().toLocaleDateString('id-ID', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
      })],
      ['Total Purchase:', totalPurchases],
      ['Total Nilai Pembelian:', `Rp ${totalValue.toLocaleString('id-ID')}`],
      ['Status:', `Pending: ${statusCounts.pending || 0}, Dipesan: ${statusCounts.ordered || 0}, Diterima: ${statusCounts.received || 0}, Selesai: ${statusCounts.completed || 0}, Dibatalkan: ${statusCounts.cancelled || 0}`],
      ['Filter Diterapkan:', filterInfo.length > 0 ? filterInfo.join(', ') : 'Semua Data'],
      [], // Empty row
    ]

    const workbook = XLSX.utils.book_new()

    const purchaseSheet = XLSX.utils.aoa_to_sheet(titleData)
    XLSX.utils.sheet_add_json(purchaseSheet, purchaseRows, { origin: 'A8' })
    purchaseSheet['!cols'] = [
      { wch: 5 },   // No
      { wch: 20 },  // No. Purchase
      { wch: 25 },  // Supplier
      { wch: 16 },  // Telepon Supplier
      { wch: 12 },  // Tanggal
      { wch: 12 },  // Status
      { wch: 10 },  // Jenis Item
      { wch: 10 },  // Total Qty
      { wch: 15 },  // Subtotal
      { wch: 12 },  // Pajak
      { wch: 12 },  // Diskon
      { wch: 15 },  // Total
      { wch: 30 },  // Catatan
      { wch: 12 },  // Dibuat
    ]
    XLSX.utils.book_append_sheet(workbook, purchaseSheet, 'Data Pembelian')

    const itemSheet = XLSX.utils.json_to_sheet(itemRows)
    itemSheet['!cols'] = [
      { wch: 5 },   // No
      { wch: 20 },  // No. Purchase
      { wch: 25 },  // Supplier
      { wch: 12 },  // Tanggal
      { wch: 14 },  // Status Purchase
      { wch: 30 },  // Nama Item
      { wch: 18 },  // Kode Item
      { wch: 8 },   // Satuan
      { wch: 12 },  // Qty Dipesan
      { wch: 12 },  // Qty Diterima
      { wch: 15 },  // Harga Satuan
      { wch: 15 },  // Total Harga
    ]
    XLSX.utils.book_append_sheet(workbook, itemSheet, 'Detail Item')

    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')
    XLSX.writeFile(workbook, `purchase-report-${timestamp}.xlsx`)

    snackbar.value = {
      show: true,
      message: `Laporan berhasil di-export ke Excel dengan ${totalPurchases} purchase lengkap!`,
      color: 'success'
    }
  } catch (error: any) {
    console.error('Export error:', error)
    snackbar.value = {
      show: true,
      message: 'Gagal mengexport laporan: ' + (error.response?.data?.message || error.message || 'Unknown error'),
      color: 'error'
    }
  } finally {
    exportLoading.value = false
  }
}

const openCreateDialog = () => {
  selectedPurchase.value = null
  dialogMode.value = 'create'
  dialogOpen.value = true
}

const editPurchase = (purchase) => {
  selectedPurchase.value = purchase
  dialogMode.value = 'edit'
  dialogOpen.value = true
}

const viewPurchase = (purchase) => {
  selectedPurchase.value = purchase
  viewDialogOpen.value = true
}

const receivePurchase = (purchase) => {
  selectedPurchase.value = purchase
  receiveDialogOpen.value = true
}

const deletePurchase = (purchase) => {
  purchaseToDelete.value = purchase
  deleteDialogOpen.value = true
}

const confirmDeletePurchase = async () => {
  if (!purchaseToDelete.value) return
  
  deleteLoading.value = true
  
  try {
    const token = getAuthToken()
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }

    await axios.delete(`/api/purchases/${purchaseToDelete.value.id_purchase}`, { headers })
    
    // Show success message
    snackbar.value = {
      show: true,
      message: `Purchase ${purchaseToDelete.value.purchase_number} berhasil dihapus`,
      color: 'success'
    }
    
    // Close dialog and refresh data
    deleteDialogOpen.value = false
    purchaseToDelete.value = null
    loadPurchases()
    loadStatistics()
    
  } catch (error) {
    console.error('Error deleting purchase:', error)
    snackbar.value = {
      show: true,
      message: 'Error menghapus purchase: ' + (error.response?.data?.message || error.message),
      color: 'error'
    }
  } finally {
    deleteLoading.value = false
  }
}

const updateStatus = async (purchase, newStatus) => {
  try {
    const token = getAuthToken()
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }

    await axios.patch(`/api/purchases/${purchase.id_purchase}/status`, {
      status: newStatus
    }, { headers })
    
    loadPurchases()
    loadStatistics()
    
    // Show success snackbar
    snackbar.value = {
      show: true,
      message: `Purchase status berhasil diubah ke ${newStatus}!`,
      color: 'success'
    }
    
  } catch (error) {
    console.error('Error updating purchase status:', error)
    
    // Show error snackbar
    snackbar.value = {
      show: true,
      message: 'Error updating status: ' + (error.response?.data?.message || error.message),
      color: 'error'
    }
  }
}

const handlePurchaseSaved = () => {
  dialogOpen.value = false
  loadPurchases()
  loadStatistics()
  
  // Show success snackbar
  snackbar.value = {
    show: true,
    message: dialogMode.value === 'create' ? 'Purchase order berhasil dibuat!' : 'Purchase order berhasil diperbarui!',
    color: 'success'
  }
}

const handleReceiveSuccess = () => {
  receiveDialogOpen.value = false
  loadPurchases()
  loadStatistics()
  
  // Show success snackbar
  snackbar.value = {
    show: true,
    message: 'Items berhasil diterima!',
    color: 'success'
  }
}

// Helper methods
const formatCurrency = (amount) => {
  const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  if (isNaN(numericAmount)) return 'Rp 0'
  
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(numericAmount)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID')
}

const formatQuantity = (quantity) => {
  const numericQuantity = typeof quantity === 'string' ? parseFloat(quantity) : quantity
  if (isNaN(numericQuantity)) return 0
  return Math.floor(numericQuantity) // Convert to integer
}

const getTotalItems = (purchase) => {
  if (!purchase.items || !Array.isArray(purchase.items)) {
    return 0
  }
  const total = purchase.items.reduce((total, item) => {
    const quantity = parseInt(item.quantity_ordered) || 0
    return total + quantity
  }, 0)
  return total
}

const getTotalVariety = (purchase) => {
  if (!purchase.items || !Array.isArray(purchase.items)) {
    return 0
  }
  return purchase.items.length
}

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    ordered: 'info',
    received: 'primary',
    completed: 'success',
    cancelled: 'error'
  }
  return colors[status] || 'default'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    ordered: 'Dipesan',
    received: 'Diterima',
    completed: 'Selesai',
    cancelled: 'Dibatalkan'
  }
  return labels[status] || status
}

const canEdit = (purchase) => {
  // Allow edit for pending, ordered, received, and completed status
  const result = ['pending', 'ordered', 'received', 'completed'].includes(purchase.status)
  return result
}

const canDelete = (purchase) => {
  // Allow delete for pending, received, and completed status
  // Don't allow delete for 'ordered' (in process)
  return ['pending', 'received', 'completed'].includes(purchase.status)
}

// Watchers
watch(filters, () => {
  page.value = 1
  loadPurchases()
}, { deep: true })

// Lifecycle
onMounted(() => {
  loadPurchases()
  loadSuppliers()
  loadStatistics()
})
</script>

<style scoped>
.v-data-table {
  background-color: transparent;
}

.max-height-150 {
  max-height: 150px;
}

.max-width-200 {
  max-width: 200px;
}
</style>
