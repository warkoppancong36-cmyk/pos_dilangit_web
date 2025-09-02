<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  name: reports-purchases
</route>

<template>
  <div class="purchase-report-page">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center mb-6 ga-4">
      <div>
        <div class="d-flex align-center mb-2">
          <VBtn 
            icon="mdi-arrow-left" 
            variant="text" 
            size="small"
            @click="router.back()"
            class="me-2"
          />
          <h1 class="text-h4 font-weight-bold">Laporan Pembelian</h1>
        </div>
        <p class="text-body-1 text-medium-emphasis">
          Analisis lengkap pembelian dan performa supplier
        </p>
      </div>
      
      <VBtn
        color="primary"
        prepend-icon="mdi-file-excel"
        @click="exportToExcel"
        :loading="isExporting"
      >
        Export Excel
      </VBtn>
    </div>

    <!-- Filter Controls -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VSelect
              v-model="selectedPeriod"
              :items="periodOptions"
              label="Pilih Periode"
              variant="outlined"
              density="compact"
              @update:model-value="onPeriodChange"
            />
          </VCol>
          
          <VCol cols="12" md="3" v-if="selectedPeriod === 'custom'">
            <VTextField
              v-model="customStartDate"
              type="date"
              label="Tanggal Mulai"
              variant="outlined"
              density="compact"
              @update:model-value="loadReportData"
            />
          </VCol>
          
          <VCol cols="12" md="3" v-if="selectedPeriod === 'custom'">
            <VTextField
              v-model="customEndDate"
              type="date"
              label="Tanggal Akhir"
              variant="outlined"
              density="compact"
              @update:model-value="loadReportData"
            />
          </VCol>
          
          <VCol cols="12" md="3" v-else>
            <VSelect
              v-model="selectedMonth"
              :items="monthOptions"
              label="Pilih Bulan"
              variant="outlined"
              density="compact"
              @update:model-value="loadReportData"
            />
          </VCol>
          
          <VCol cols="12" md="2">
            <VBtn
              color="primary"
              block
              @click="loadReportData"
              :loading="isLoading"
            >
              Refresh
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-8">
      <VProgressCircular indeterminate color="primary" size="64" />
      <p class="text-body-1 mt-4">Memuat data laporan...</p>
    </div>

    <!-- Report Content -->
    <div v-else-if="reportData">
      <!-- Summary Cards -->
      <VRow class="mb-6">
        <VCol cols="6" md="3">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-currency-usd" size="32" class="text-primary mb-2" />
              <div class="text-h5 font-weight-bold text-primary">
                {{ formatCurrency(reportData.summary.total_amount) }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Total Pembelian</div>
            </VCardText>
          </VCard>
        </VCol>
        
        <VCol cols="6" md="3">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-cart" size="32" class="text-success mb-2" />
              <div class="text-h5 font-weight-bold text-success">
                {{ reportData.summary.total_purchases }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Total Purchase</div>
            </VCardText>
          </VCard>
        </VCol>
        
        <VCol cols="6" md="3">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-package" size="32" class="text-info mb-2" />
              <div class="text-h5 font-weight-bold text-info">
                {{ reportData.summary.total_items }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Total Item</div>
            </VCardText>
          </VCard>
        </VCol>
        
        <VCol cols="6" md="3">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-calculator" size="32" class="text-warning mb-2" />
              <div class="text-h5 font-weight-bold text-warning">
                {{ formatCurrency(reportData.summary.average_purchase) }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Rata-rata Purchase</div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Charts and Status -->
      <VRow class="mb-6">
        <VCol cols="12" md="8">
          <VCard>
            <VCardTitle>Trend Pembelian Harian</VCardTitle>
            <VCardText>
              <div v-if="reportData && reportData.daily_purchases && reportData.daily_purchases.length > 0">
                <canvas ref="dailyPurchaseChart" class="w-100" style="max-height: 300px;"></canvas>
              </div>
              <div v-else class="text-center py-8">
                <VIcon icon="mdi-chart-line" size="48" class="text-medium-emphasis mb-2" />
                <p class="text-body-2 text-medium-emphasis">Tidak ada data pembelian harian</p>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        
        <VCol cols="12" md="4">
          <VCard>
            <VCardTitle>Status Pembelian</VCardTitle>
            <VCardText>
              <div v-for="status in reportData.status_breakdown" :key="status.status" class="mb-3">
                <div class="d-flex justify-space-between align-center mb-1">
                  <div class="d-flex align-center">
                    <VChip 
                      :color="getStatusColor(status.status)" 
                      size="small" 
                      variant="tonal"
                      class="me-2"
                    >
                      {{ status.status }}
                    </VChip>
                    <span class="text-caption">{{ status.count }} purchase</span>
                  </div>
                  <span class="font-weight-bold">
                    {{ formatCurrency(status.total_amount) }}
                  </span>
                </div>
                <VProgressLinear
                  :model-value="(status.total_amount / reportData.summary.total_amount) * 100"
                  :color="getStatusColor(status.status)"
                  height="4"
                  rounded
                />
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Top Suppliers and Items -->
      <VRow class="mb-6">
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle>Top Supplier</VCardTitle>
            <VCardText>
              <VDataTable
                :headers="supplierHeaders"
                :items="reportData.top_suppliers"
                :items-per-page="5"
                class="elevation-0"
                no-data-text="Tidak ada data supplier"
              >
                <template #item.total_purchases="{ item }">
                  <VChip size="small" color="primary" variant="tonal">
                    {{ (item as any).total_purchases }}
                  </VChip>
                </template>
                <template #item.total_amount="{ item }">
                  <span class="font-weight-bold text-primary">
                    {{ formatCurrency((item as any).total_amount) }}
                  </span>
                </template>
              </VDataTable>
            </VCardText>
          </VCard>
        </VCol>
        
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle>Top Item Dibeli</VCardTitle>
            <VCardText>
              <div class="top-items-container">
                <div v-for="(item, index) in reportData.top_items" :key="item.id" class="mb-3">
                  <div class="d-flex justify-space-between align-center">
                    <div class="d-flex align-center">
                      <VChip 
                        :color="getTopItemColor(index)" 
                        size="small" 
                        class="me-2"
                      >
                        {{ index + 1 }}
                      </VChip>
                      <div>
                        <div class="font-weight-medium">{{ item.name }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ item.total_purchased }} dibeli
                        </div>
                      </div>
                    </div>
                    <div class="text-end">
                      <div class="font-weight-bold text-primary">
                        {{ formatCurrency(item.total_amount) }}
                      </div>
                    </div>
                  </div>
                  <VDivider v-if="index < reportData.top_items.length - 1" class="mt-3" />
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Daily Purchases -->
      <VRow>
        <VCol cols="12">
          <VCard>
            <VCardTitle>Pembelian per Hari</VCardTitle>
            <VCardText>
              <VDataTable
                :headers="dailyHeaders"
                :items="reportData.daily_purchases"
                :items-per-page="10"
                class="elevation-0"
                no-data-text="Tidak ada data pembelian"
              >
                <template #item.date="{ item }">
                  {{ formatDate((item as any).date) }}
                </template>
                <template #item.purchase_count="{ item }">
                  <VChip size="small" color="info" variant="tonal">
                    {{ (item as any).purchase_count }}
                  </VChip>
                </template>
                <template #item.total_amount="{ item }">
                  <span class="font-weight-bold text-primary">
                    {{ formatCurrency((item as any).total_amount) }}
                  </span>
                </template>
              </VDataTable>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <VIcon icon="mdi-cart-variant" size="64" class="text-medium-emphasis mb-4" />
      <h3 class="text-h6 mb-2">Tidak ada data laporan</h3>
      <p class="text-body-2 text-medium-emphasis">
        Silakan pilih periode yang berbeda atau periksa kembali filter Anda
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Chart from 'chart.js/auto'

const router = useRouter()

// State
const isLoading = ref(false)
const isExporting = ref(false)
const reportData = ref<any>(null)
const selectedPeriod = ref('month')
const selectedMonth = ref('')
const customStartDate = ref('')
const customEndDate = ref('')

// Chart reference
const dailyPurchaseChart = ref<HTMLCanvasElement>()
let chartInstance: Chart | null = null

// Options
const periodOptions = [
  { title: 'Filter per Bulan', value: 'month' },
  { title: 'Custom Range', value: 'custom' }
]

// Generate month options based on current year
const currentYear = new Date().getFullYear()
const monthOptions = [
  { title: 'Januari', value: `${currentYear}-01` },
  { title: 'Februari', value: `${currentYear}-02` },
  { title: 'Maret', value: `${currentYear}-03` },
  { title: 'April', value: `${currentYear}-04` },
  { title: 'Mei', value: `${currentYear}-05` },
  { title: 'Juni', value: `${currentYear}-06` },
  { title: 'Juli', value: `${currentYear}-07` },
  { title: 'Agustus', value: `${currentYear}-08` },
  { title: 'September', value: `${currentYear}-09` },
  { title: 'Oktober', value: `${currentYear}-10` },
  { title: 'November', value: `${currentYear}-11` },
  { title: 'Desember', value: `${currentYear}-12` }
]

// Table headers
const supplierHeaders = [
  { title: 'Supplier', key: 'name', align: 'start' as const },
  { title: 'Total Purchase', key: 'total_purchases', align: 'center' as const },
  { title: 'Total Amount', key: 'total_amount', align: 'end' as const }
]

const dailyHeaders = [
  { title: 'Tanggal', key: 'date', align: 'start' as const },
  { title: 'Purchase', key: 'purchase_count', align: 'center' as const },
  { title: 'Total Amount', key: 'total_amount', align: 'end' as const }
]

// Utility functions
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const getStatusColor = (status: string) => {
  const colorMap: Record<string, string> = {
    'pending': 'warning',
    'approved': 'info',
    'received': 'success',
    'cancelled': 'error'
  }
  return colorMap[status.toLowerCase()] || 'primary'
}

const getTopItemColor = (index: number) => {
  const colors = ['warning', 'success', 'info', 'secondary', 'primary']
  return colors[index] || 'primary'
}

// Event handlers
const onPeriodChange = () => {
  if (selectedPeriod.value === 'month') {
    // Set default to current month
    const currentMonth = new Date().toISOString().slice(0, 7)
    selectedMonth.value = currentMonth
  }
  loadReportData()
}

// Load report data
const loadReportData = async () => {
  if (isLoading.value) return
  
  isLoading.value = true
  
  try {
    let params: any = {}
    
    if (selectedPeriod.value === 'custom') {
      if (customStartDate.value && customEndDate.value) {
        params.start_date = customStartDate.value
        params.end_date = customEndDate.value
      } else {
        isLoading.value = false
        return
      }
    } else {
      if (selectedMonth.value) {
        params.month = selectedMonth.value
      } else {
        isLoading.value = false
        return
      }
    }
    
    const response = await axios.get('/api/reports/purchases', { params })
    
    if (response.data.success) {
      reportData.value = response.data.data
      
      await nextTick()
      // Add delay to ensure DOM is ready
      setTimeout(() => {
        createDailyPurchaseChart()
      }, 100)
    }
  } catch (error) {
    console.error('Error loading purchase report:', error)
  } finally {
    isLoading.value = false
  }
}

// Create daily purchase chart
const createDailyPurchaseChart = () => {
  if (!dailyPurchaseChart.value || !reportData.value) {
    return
  }
  
  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  const ctx = dailyPurchaseChart.value.getContext('2d')
  if (!ctx) {
    return
  }
  
  // Prepare chart data
  const chartLabels = reportData.value.daily_purchases.map((item: any) => formatDate(item.date))
  const chartData = reportData.value.daily_purchases.map((item: any) => {
    // Convert string to number if needed
    const value = typeof item.total_amount === 'string' ? parseFloat(item.total_amount.replace(/[^\d.-]/g, '')) : item.total_amount
    return value || 0
  })
  
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartLabels,
      datasets: [{
        label: 'Pembelian Harian',
        data: chartData,
        borderColor: 'rgb(33, 150, 243)',
        backgroundColor: 'rgba(33, 150, 243, 0.1)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return formatCurrency(value as number)
            }
          }
        }
      }
    }
  })
}

// Export to Excel
const exportToExcel = async () => {
  if (!reportData.value) {
    console.warn('No purchase data to export')
    return
  }
  
  isExporting.value = true
  
  try {
    // Dynamic import for better performance
    const XLSX = await import('xlsx')
    
    // Get current period info
    const periodInfo = selectedPeriod.value === 'custom' 
      ? `${customStartDate.value} s/d ${customEndDate.value}`
      : `Bulan ${selectedMonth.value}`
    
    // Prepare Summary Data
    const summaryData = [
      ['LAPORAN PEMBELIAN'],
      ['Periode:', periodInfo],
      ['Tanggal Export:', new Date().toLocaleDateString('id-ID', { 
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
      })],
      [],
      ['RINGKASAN PEMBELIAN'],
      ['Total Purchase:', reportData.value.summary.total_purchases],
      ['Total Amount:', Math.round(parseFloat(reportData.value.summary.total_amount.replace(/[^\d.-]/g, '')))],
      ['Rata-rata Purchase:', Math.round(parseFloat(reportData.value.summary.avg_purchase_value.replace(/[^\d.-]/g, '')))],
      ['Purchase Selesai:', reportData.value.summary.completed_purchases],
      ['Purchase Pending:', reportData.value.summary.pending_purchases],
      ['Purchase Dibatalkan:', reportData.value.summary.cancelled_purchases],
      [], // Empty row
    ]
    
    // Create workbook
    const workbook = XLSX.utils.book_new()
    
    // 1. Summary Sheet
    const summarySheet = XLSX.utils.aoa_to_sheet(summaryData)
    summarySheet['!cols'] = [{ wch: 20 }, { wch: 25 }]
    XLSX.utils.book_append_sheet(workbook, summarySheet, 'Ringkasan')
    
    // 2. Daily Purchases Sheet
    if (reportData.value.daily_purchases && reportData.value.daily_purchases.length > 0) {
      const dailyPurchasesData = [
        ['PEMBELIAN HARIAN'],
        ['Periode:', periodInfo],
        [],
        ['Tanggal', 'Jumlah Purchase', 'Total Amount', 'Rata-rata'],
        ...reportData.value.daily_purchases.map((item: any) => [
          new Date(item.date).toLocaleDateString('id-ID'),
          item.purchase_count,
          Math.round(typeof item.total_amount === 'string' ? parseFloat(item.total_amount.replace(/[^\d.-]/g, '')) : item.total_amount),
          Math.round(typeof item.avg_purchase_value === 'string' ? parseFloat(item.avg_purchase_value.replace(/[^\d.-]/g, '')) : item.avg_purchase_value)
        ])
      ]
      
      const dailySheet = XLSX.utils.aoa_to_sheet(dailyPurchasesData)
      dailySheet['!cols'] = [{ wch: 15 }, { wch: 15 }, { wch: 20 }, { wch: 20 }]
      XLSX.utils.book_append_sheet(workbook, dailySheet, 'Pembelian Harian')
    }
    
    // 3. Status Breakdown Sheet
    if (reportData.value.status_breakdown && reportData.value.status_breakdown.length > 0) {
      const statusData = [
        ['BREAKDOWN STATUS PEMBELIAN'],
        ['Periode:', periodInfo],
        [],
        ['Status', 'Jumlah Purchase', 'Total Amount'],
        ...reportData.value.status_breakdown.map((item: any) => [
          item.status,
          item.purchase_count,
          Math.round(typeof item.total_amount === 'string' ? parseFloat(item.total_amount.replace(/[^\d.-]/g, '')) : item.total_amount)
        ])
      ]
      
      const statusSheet = XLSX.utils.aoa_to_sheet(statusData)
      statusSheet['!cols'] = [{ wch: 20 }, { wch: 15 }, { wch: 20 }]
      XLSX.utils.book_append_sheet(workbook, statusSheet, 'Status Breakdown')
    }
    
    // 4. Top Items Sheet
    if (reportData.value.top_items && reportData.value.top_items.length > 0) {
      const topItemsData = [
        ['ITEM PALING BANYAK DIBELI'],
        ['Periode:', periodInfo],
        [],
        ['No', 'Nama Item', 'Total Dibeli', 'Total Amount', 'Rata-rata Unit Cost'],
        ...reportData.value.top_items.map((item: any, index: number) => [
          index + 1,
          item.name,
          Math.round(parseFloat(item.total_purchased)),
          Math.round(typeof item.total_amount === 'string' ? parseFloat(item.total_amount.replace(/[^\d.-]/g, '')) : item.total_amount),
          Math.round(parseFloat(item.avg_unit_cost))
        ])
      ]
      
      const itemsSheet = XLSX.utils.aoa_to_sheet(topItemsData)
      itemsSheet['!cols'] = [{ wch: 5 }, { wch: 35 }, { wch: 15 }, { wch: 20 }, { wch: 20 }]
      XLSX.utils.book_append_sheet(workbook, itemsSheet, 'Top Items')
    }
    
    // 5. Top Suppliers Sheet
    if (reportData.value.top_suppliers && reportData.value.top_suppliers.length > 0) {
      const topSuppliersData = [
        ['SUPPLIER TERBAIK'],
        ['Periode:', periodInfo],
        [],
        ['No', 'Nama Supplier', 'Jumlah Purchase', 'Total Amount'],
        ...reportData.value.top_suppliers.map((item: any, index: number) => [
          index + 1,
          item.name,
          item.total_purchases,
          Math.round(typeof item.total_amount === 'string' ? parseFloat(item.total_amount.replace(/[^\d.-]/g, '')) : item.total_amount)
        ])
      ]
      
      const suppliersSheet = XLSX.utils.aoa_to_sheet(topSuppliersData)
      suppliersSheet['!cols'] = [{ wch: 5 }, { wch: 30 }, { wch: 15 }, { wch: 20 }]
      XLSX.utils.book_append_sheet(workbook, suppliersSheet, 'Top Suppliers')
    }
    
    // Generate filename with timestamp
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')
    const filename = `laporan-pembelian-${timestamp}.xlsx`
    
    // Download file
    XLSX.writeFile(workbook, filename)
    
    console.log('✅ Purchase report exported successfully:', filename)
    
  } catch (error) {
    console.error('Error exporting purchase report:', error)
  } finally {
    isExporting.value = false
  }
}

// Lifecycle
onMounted(() => {
  // Set default month to current month
  const now = new Date()
  const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  selectedMonth.value = currentMonth
  loadReportData()
})
</script>

<style scoped lang="scss">
.purchase-report-page {
  canvas {
    max-height: 300px;
  }
  
  .top-items-container {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 8px;
    
    // Custom scrollbar
    &::-webkit-scrollbar {
      width: 6px;
    }
    
    &::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
      border-radius: 3px;
    }
    
    &::-webkit-scrollbar-thumb {
      background: rgba(0, 0, 0, 0.2);
      border-radius: 3px;
      
      &:hover {
        background: rgba(0, 0, 0, 0.3);
      }
    }
  }
}

// Responsive adjustments
@media (max-width: 600px) {
  .purchase-report-page {
    .text-h4 {
      font-size: 1.5rem !important;
    }
    
    .text-h5 {
      font-size: 1.25rem !important;
    }
    
    .top-items-container {
      max-height: 300px;
    }
  }
}
</style>
