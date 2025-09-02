<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  name: reports-sales
</route>

<template>
  <div class="sales-report-page">
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
          <h1 class="text-h4 font-weight-bold">Laporan Penjualan</h1>
        </div>
        <p class="text-body-1 text-medium-emphasis">
          Analisis lengkap penjualan dan performa produk
        </p>
      </div>
      
      <VBtn
        color="success"
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
              <VIcon icon="mdi-currency-usd" size="32" class="text-success mb-2" />
              <div class="text-h5 font-weight-bold text-success">
                {{ formatCurrency(reportData.summary.total_revenue) }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Total Pendapatan</div>
            </VCardText>
          </VCard>
        </VCol>
        
        <VCol cols="6" md="3">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-cart" size="32" class="text-primary mb-2" />
              <div class="text-h5 font-weight-bold text-primary">
                {{ reportData.summary.total_orders }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Total Order</div>
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
                {{ formatCurrency(reportData.summary.average_order) }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Rata-rata Order</div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Charts Row -->
      <VRow class="mb-6">
        <VCol cols="12" md="8">
          <VCard>
            <VCardTitle>Trend Penjualan Harian</VCardTitle>
            <VCardText>
              <div v-if="reportData && reportData.daily_sales && reportData.daily_sales.length > 0">
                <canvas ref="dailySalesChart" class="w-100" style="max-height: 300px;"></canvas>
              </div>
              <div v-else class="text-center py-8">
                <VIcon icon="mdi-chart-line" size="48" class="text-medium-emphasis mb-2" />
                <p class="text-body-2 text-medium-emphasis">Tidak ada data penjualan harian</p>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        
        <VCol cols="12" md="4">
          <VCard>
            <VCardTitle>Top 5 Produk</VCardTitle>
            <VCardText>
              <div class="top-products-container">
                <div v-for="(product, index) in reportData.top_products" :key="product.id" class="mb-3">
                  <div class="d-flex justify-space-between align-center">
                    <div class="d-flex align-center">
                      <VChip 
                        :color="getTopProductColor(index)" 
                        size="small" 
                        class="me-2"
                      >
                        {{ index + 1 }}
                      </VChip>
                      <div>
                        <div class="font-weight-medium">{{ product.name }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ product.quantity }} terjual
                        </div>
                      </div>
                    </div>
                    <div class="text-end">
                      <div class="font-weight-bold text-success">
                        {{ formatCurrency(product.revenue) }}
                      </div>
                    </div>
                  </div>
                  <VDivider v-if="index < reportData.top_products.length - 1" class="mt-3" />
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Top Customers -->
      <VRow>
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle>Top Customer</VCardTitle>
            <VCardText>
              <VDataTable
                :headers="customerHeaders"
                :items="reportData.top_customers"
                :items-per-page="5"
                class="elevation-0"
                no-data-text="Tidak ada data customer"
              >
                <template #item.orders="{ item }">
                  <VChip size="small" color="primary" variant="tonal">
                    {{ (item as any).orders }}
                  </VChip>
                </template>
                <template #item.revenue="{ item }">
                  <span class="font-weight-bold text-success">
                    {{ formatCurrency((item as any).revenue) }}
                  </span>
                </template>
              </VDataTable>
            </VCardText>
          </VCard>
        </VCol>
        
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle>Penjualan per Hari</VCardTitle>
            <VCardText>
              <VDataTable
                :headers="dailyHeaders"
                :items="reportData.daily_sales"
                :items-per-page="7"
                class="elevation-0"
                no-data-text="Tidak ada data penjualan"
              >
                <template #item.date="{ item }">
                  {{ formatDate((item as any).date) }}
                </template>
                <template #item.total="{ item }">
                  <span class="font-weight-bold text-success">
                    {{ formatCurrency((item as any).total) }}
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
      <VIcon icon="mdi-chart-line-variant" size="64" class="text-medium-emphasis mb-4" />
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
const dailySalesChart = ref<HTMLCanvasElement>()
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
const customerHeaders = [
  { title: 'Customer', key: 'name', align: 'start' as const },
  { title: 'Total Order', key: 'orders', align: 'center' as const },
  { title: 'Total Belanja', key: 'revenue', align: 'end' as const }
]

const dailyHeaders = [
  { title: 'Tanggal', key: 'date', align: 'start' as const },
  { title: 'Pendapatan', key: 'total', align: 'end' as const }
]

// Utility functions
const formatCurrency = (value: number | string) => {
  // If value is already formatted string, return as is
  if (typeof value === 'string') {
    return value.startsWith('Rp') ? value : `Rp ${value}`
  }
  
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

const getTopProductColor = (index: number) => {
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
    
    const response = await axios.get('/api/reports/sales', { params })
    
    if (response.data.success) {
      reportData.value = response.data.data
      
      await nextTick()
      // Add delay to ensure DOM is ready
      setTimeout(() => {
        createDailySalesChart()
      }, 100)
    }
  } catch (error) {
    console.error('Error loading sales report:', error)
  } finally {
    isLoading.value = false
  }
}

// Create daily sales chart
const createDailySalesChart = () => {
  if (!dailySalesChart.value || !reportData.value) {
    return
  }
  
  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  const ctx = dailySalesChart.value.getContext('2d')
  if (!ctx) {
    return
  }
  
  // Prepare chart data
  const chartLabels = reportData.value.daily_sales.map((item: any) => formatDate(item.date))
  const chartData = reportData.value.daily_sales.map((item: any) => {
    // Convert string to number if needed
    const value = typeof item.total === 'string' ? parseFloat(item.total.replace(/[^\d.-]/g, '')) : item.total
    return value || 0
  })
  
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartLabels,
      datasets: [{
        label: 'Pendapatan Harian',
        data: chartData,
        borderColor: 'rgb(76, 175, 80)',
        backgroundColor: 'rgba(76, 175, 80, 0.1)',
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
    console.warn('No data to export')
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
      ['LAPORAN PENJUALAN'],
      ['Periode:', periodInfo],
      ['Tanggal Export:', new Date().toLocaleDateString('id-ID', { 
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
      })],
      [],
      ['RINGKASAN PENJUALAN'],
      ['Total Order:', reportData.value.summary.total_orders],
      ['Total Pendapatan:', Math.round(parseFloat(reportData.value.summary.total_revenue.replace(/[^\d.-]/g, '')))],
      ['Rata-rata Order:', Math.round(parseFloat(reportData.value.summary.average_order.replace(/[^\d.-]/g, '')))],
      ['Total Item Terjual:', reportData.value.summary.total_items],
      ['Order Selesai:', reportData.value.summary.completed_orders],
      ['Order Dibatalkan:', reportData.value.summary.cancelled_orders],
      [], // Empty row
    ]
    
    // Create workbook
    const workbook = XLSX.utils.book_new()
    
    // 1. Summary Sheet
    const summarySheet = XLSX.utils.aoa_to_sheet(summaryData)
    summarySheet['!cols'] = [{ wch: 20 }, { wch: 25 }]
    XLSX.utils.book_append_sheet(workbook, summarySheet, 'Ringkasan')
    
    // 2. Daily Sales Sheet
    if (reportData.value.daily_sales && reportData.value.daily_sales.length > 0) {
      const dailySalesData = [
        ['PENJUALAN HARIAN'],
        ['Periode:', periodInfo],
        [],
        ['Tanggal', 'Total Penjualan'],
        ...reportData.value.daily_sales.map((item: any) => [
          new Date(item.date).toLocaleDateString('id-ID'),
          Math.round(typeof item.total === 'string' ? parseFloat(item.total.replace(/[^\d.-]/g, '')) : item.total)
        ])
      ]
      
      const dailySheet = XLSX.utils.aoa_to_sheet(dailySalesData)
      dailySheet['!cols'] = [{ wch: 15 }, { wch: 20 }]
      XLSX.utils.book_append_sheet(workbook, dailySheet, 'Penjualan Harian')
    }
    
    // 3. Top Products Sheet
    if (reportData.value.top_products && reportData.value.top_products.length > 0) {
      const topProductsData = [
        ['PRODUK TERLARIS'],
        ['Periode:', periodInfo],
        [],
        ['No', 'Nama Produk', 'Jumlah Terjual', 'Total Pendapatan'],
        ...reportData.value.top_products.map((item: any, index: number) => [
          index + 1,
          item.name,
          item.quantity,
          Math.round(typeof item.revenue === 'string' ? parseFloat(item.revenue.replace(/[^\d.-]/g, '')) : (item.revenue || 0))
        ])
      ]
      
      const productsSheet = XLSX.utils.aoa_to_sheet(topProductsData)
      productsSheet['!cols'] = [{ wch: 5 }, { wch: 30 }, { wch: 15 }, { wch: 20 }]
      XLSX.utils.book_append_sheet(workbook, productsSheet, 'Produk Terlaris')
    }
    
    // 4. Top Customers Sheet
    if (reportData.value.top_customers && reportData.value.top_customers.length > 0) {
      const topCustomersData = [
        ['CUSTOMER TERBAIK'],
        ['Periode:', periodInfo],
        [],
        ['No', 'Nama Customer', 'Jumlah Order', 'Total Belanja'],
        ...reportData.value.top_customers.map((item: any, index: number) => [
          index + 1,
          item.name,
          item.orders,
          Math.round(typeof item.revenue === 'string' ? parseFloat(item.revenue.replace(/[^\d.-]/g, '')) : (item.revenue || 0))
        ])
      ]
      
      const customersSheet = XLSX.utils.aoa_to_sheet(topCustomersData)
      customersSheet['!cols'] = [{ wch: 5 }, { wch: 25 }, { wch: 15 }, { wch: 20 }]
      XLSX.utils.book_append_sheet(workbook, customersSheet, 'Customer Terbaik')
    }
    
    // Generate filename with timestamp
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')
    const filename = `laporan-penjualan-${timestamp}.xlsx`
    
    // Download file
    XLSX.writeFile(workbook, filename)
    
    console.log('✅ Sales report exported successfully:', filename)
    
  } catch (error) {
    console.error('Error exporting sales report:', error)
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
.sales-report-page {
  canvas {
    max-height: 300px;
  }
  
  .top-products-container {
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
  .sales-report-page {
    .text-h4 {
      font-size: 1.5rem !important;
    }
    
    .text-h5 {
      font-size: 1.25rem !important;
    }
    
    .top-products-container {
      max-height: 300px;
    }
  }
}
</style>
