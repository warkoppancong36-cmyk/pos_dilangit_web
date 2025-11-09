<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  name: dashboard
</route>

<template>
  <div class="dashboard-page">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center mb-6 ga-4">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Dashboard Analytics</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">
          Pantau penjualan, pembelian, dan inventory coffee shop Anda
        </p>
      </div>
      
      <!-- Filter Controls - Responsive -->
      <div class="d-flex flex-column flex-sm-row gap-3 align-stretch align-sm-center w-100 w-md-auto">
        <!-- Date Range Filter -->
        <div class="d-flex flex-column flex-sm-row gap-2 align-stretch">
          <VTextField
            v-model="customStartDate"
            type="date"
            label="Tanggal Mulai"
            density="comfortable"
            variant="outlined"
            hide-details
            class="flex-grow-1"
            @change="onCustomDateChange"
          />
          <VTextField
            v-model="customEndDate"
            type="date"
            label="Tanggal Akhir"
            density="comfortable"
            variant="outlined"
            hide-details
            class="flex-grow-1"
            @change="onCustomDateChange"
          />
        </div>

        <!-- Hour Range Filter -->
        <div class="d-flex flex-column flex-sm-row gap-2 align-stretch">
          <VTextField
            v-model="hourStart"
            type="time"
            label="Jam Mulai"
            density="comfortable"
            variant="outlined"
            hide-details
            class="flex-grow-1"
            @change="onHourChange"
          >
            <template #prepend-inner>
              <VIcon icon="mdi-clock-outline" size="20" />
            </template>
          </VTextField>
          <VTextField
            v-model="hourEnd"
            type="time"
            label="Jam Akhir"
            density="comfortable"
            variant="outlined"
            hide-details
            class="flex-grow-1"
            @change="onHourChange"
          >
            <template #prepend-inner>
              <VIcon icon="mdi-clock-outline" size="20" />
            </template>
          </VTextField>
        </div>
        
        <!-- Period Quick Select -->
        <VSelect
          v-model="selectedPeriod"
          :items="periodOptions"
          item-title="label"
          item-value="value"
          density="comfortable"
          variant="outlined"
          hide-details
          class="flex-shrink-0"
          style="min-width: 180px"
          @update:model-value="onPeriodChange"
        >
          <template #prepend-inner>
            <VIcon icon="mdi-calendar-range" />
          </template>
        </VSelect>
        
        <!-- Refresh Button -->
        <VBtn
          color="primary"
          prepend-icon="mdi-refresh"
          @click="loadDashboard"
          :loading="loading"
          class="flex-shrink-0"
        >
          <span class="d-none d-sm-inline">Refresh</span>
          <VIcon v-show="$vuetify.display.xs" icon="mdi-refresh" />
        </VBtn>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <VProgressCircular indeterminate color="primary" size="64" />
      <p class="text-body-1 mt-4">Memuat data dashboard...</p>
    </div>

    <!-- Dashboard Content -->
    <div v-else>
      <!-- Summary Cards -->
      <VRow class="mb-6">
        <VCol cols="12" md="3">
          <VCard class="summary-card h-100">
            <VCardText class="d-flex align-center">
              <div class="flex-grow-1">
                <div class="text-h6 font-weight-bold">Total Pendapatan</div>
                <div class="text-h4 text-success mt-2">{{ formatCurrency(summaryData.period_summary?.total_revenue || 0) }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ (summaryData.period_summary?.growth ?? 0) >= 0 ? '+' : '' }}{{ summaryData.period_summary?.growth ?? 0 }}% dari periode sebelumnya
                </div>
              </div>
              <VIcon icon="mdi-currency-usd" size="48" class="text-success" />
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" md="3">
          <VCard class="summary-card h-100">
            <VCardText class="d-flex align-center">
              <div class="flex-grow-1">
                <div class="text-h6 font-weight-bold">Penjualan Hari Ini</div>
                <div class="text-h4 text-info mt-2">{{ formatCurrency(summaryData.today_sales?.value || 0) }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ (summaryData.today_sales?.growth ?? 0) >= 0 ? '+' : '' }}{{ summaryData.today_sales?.growth ?? 0 }}% dari kemarin
                </div>
                <div class="text-caption text-medium-emphasis mt-1">
                  {{ (summaryData.today_sales?.weekly_growth ?? 0) >= 0 ? '+' : '' }}{{ summaryData.today_sales?.weekly_growth ?? 0 }}% dari minggu lalu
                </div>
              </div>
              <VIcon icon="mdi-cash-multiple" size="48" class="text-info" />
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" md="3">
          <VCard class="summary-card h-100">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-credit-card" class="me-2" />
              Jumlah Pembayaran Hari Ini
            </VCardTitle>
            <VCardText>
              <div v-if="summaryData.payment_methods_today?.data && summaryData.payment_methods_today.data.length > 0">
                <div v-for="item in summaryData.payment_methods_today.data" :key="item.payment_method" class="d-flex justify-space-between align-center py-1">
                  <div class="d-flex align-center">
                    <div class="payment-method-color me-2" :style="`background-color: ${getPaymentMethodColor(item.payment_method)}; width: 12px; height: 12px; border-radius: 50%;`"></div>
                    <span class="text-body-2">{{ item.payment_method_formatted }}</span>
                  </div>
                  <div class="text-right">
                    <div class="text-h6 font-weight-bold">{{ item.total_amount_formatted }}</div>
                    <div class="text-caption text-medium-emphasis">{{ item.percentage }}%</div>
                  </div>
                </div>
              </div>
              
              <!-- NO DATA STATE -->
              <div v-else class="text-center py-4">
                <VIcon icon="mdi-cash-off" size="48" class="text-grey-400 mb-2" />
                <div class="text-body-2 text-medium-emphasis">Tidak ada pembayaran hari ini</div>
                <div class="text-h6 font-weight-bold mt-1">Rp 0</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" md="3">
          <VCard class="summary-card h-100">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-food" class="me-2" />
              Jenis Pesanan Hari Ini
            </VCardTitle>
            <VCardText>
              <div v-if="summaryData.order_types_today?.data && summaryData.order_types_today.data.length > 0">
                <div v-for="item in summaryData.order_types_today.data" :key="item.order_type" class="d-flex justify-space-between align-center py-1">
                  <div class="d-flex align-center">
                    <div class="order-type-color me-2" :style="`background-color: ${getOrderTypeColor(item.order_type)}; width: 12px; height: 12px; border-radius: 50%;`"></div>
                    <span class="text-body-2">{{ item.order_type_formatted }}</span>
                  </div>
                  <div class="text-right">
                    <div class="text-h6 font-weight-bold">{{ item.total_amount_formatted }}</div>
                    <div class="text-caption text-medium-emphasis">{{ item.percentage }}%</div>
                  </div>
                </div>
              </div>
              
              <!-- NO DATA STATE -->
              <div v-else class="text-center py-4">
                <VIcon icon="mdi-food-off" size="48" class="text-grey-400 mb-2" />
                <div class="text-body-2 text-medium-emphasis">Tidak ada pesanan hari ini</div>
                <div class="text-h6 font-weight-bold mt-1">Rp 0</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" md="3">
          <VCard class="summary-card h-100">
            <VCardText class="d-flex align-center">
              <div class="flex-grow-1">
                <div class="text-h6 font-weight-bold">Total Order Hari Ini</div>
                <div class="text-h4 text-secondary mt-2">{{ summaryData.today_orders?.value || 0 }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ (summaryData.today_orders?.growth ?? 0) >= 0 ? '+' : '' }}{{ summaryData.today_orders?.growth ?? 0 }}% dari kemarin
                </div>
              </div>
              <VIcon icon="mdi-receipt-text" size="48" class="text-secondary" />
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Charts Row 1 -->
      <VRow class="mb-6">
        <VCol cols="12" lg="8">
          <VCard class="chart-card">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-chart-line" class="me-2" />
              Tren Penjualan {{ getDateRangeDisplay() }}
            </VCardTitle>
            <VCardText>
              <div style="height: 300px;">
                <canvas ref="salesChart"></canvas>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" lg="4">
          <VCard class="chart-card">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-chart-pie" class="me-2" />
              Produk Terlaris
            </VCardTitle>
            <VCardText>
              <div style="height: 300px;">
                <canvas ref="salesPieChart"></canvas>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Charts Row 2 -->
      <VRow class="mb-6">
        <VCol cols="12" lg="8">
          <VCard class="chart-card">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-cart" class="me-2" />
              Tren Pembelian {{ getDateRangeDisplay() }}
            </VCardTitle>
            <VCardText>
              <div style="height: 300px;">
                <canvas ref="purchaseChart"></canvas>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" lg="4">
          <VCard class="chart-card">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-chart-donut" class="me-2" />
              Produk Terbanyak Dibeli
            </VCardTitle>
            <VCardText>
              <div style="height: 300px;">
                <canvas ref="purchasePieChart"></canvas>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Charts Row 3 -->
      <VRow class="mb-6">
        <VCol cols="12" lg="6">
          <VCard class="chart-card">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-chart-donut-variant" class="me-2" />
              Top Items by Stock
            </VCardTitle>
            <VCardText>
              <div style="height: 300px;">
                <canvas ref="inventoryChart"></canvas>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" lg="6">
          <VCard class="chart-card">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-alert-triangle" class="me-2" />
              Item Stok Rendah
            </VCardTitle>
            <VCardText>
              <VDataTable
                :headers="lowStockHeaders"
                :items="inventoryData.low_stock || []"
                hide-default-footer
                density="compact"
              >
                <template #item.current_stock="{ item }">
                  <VChip 
                    :color="item.current_stock <= 0 ? 'error' : 'warning'"
                    size="small"
                  >
                    {{ item.current_stock }}
                  </VChip>
                </template>
              </VDataTable>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Top Products & Suppliers -->
      <VRow>
        <VCol cols="12" lg="6">
          <VCard class="chart-card">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-star" class="me-2" />
              Produk Terlaris
            </VCardTitle>
            <VCardText>
              <VDataTable
                :headers="topProductsHeaders"
                :items="salesData.top_products || []"
                hide-default-footer
                density="compact"
              >
                <template #item.total_revenue="{ item }">
                  {{ formatCurrency(item.total_revenue) }}
                </template>
              </VDataTable>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" lg="6">
          <VCard class="chart-card">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="mdi-truck" class="me-2" />
              Supplier Teratas
            </VCardTitle>
            <VCardText>
              <VDataTable
                :headers="topSuppliersHeaders"
                :items="purchaseData.top_suppliers || []"
                hide-default-footer
                density="compact"
              >
                <template #item.total_amount="{ item }">
                  {{ formatCurrency(item.total_amount) }}
                </template>
              </VDataTable>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, nextTick, watch } from 'vue'
import axios from 'axios'
import Chart from 'chart.js/auto'
import { useTheme } from 'vuetify'

// Theme detection
const theme = useTheme()
const isDark = computed(() => theme.global.current.value.dark)

// Utils
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

// Types
interface DashboardData {
  summary: {
    today_sales?: { value: number; growth: number; weekly_growth: number }
    today_orders?: { value: number; growth: number; weekly_growth: number }
    inventory_value?: { value: number; low_stock_count: number }
    period_summary?: { avg_order_value: number; total_orders: number }
    payment_methods_today?: {
      data: Array<{
        payment_method: string
        payment_method_formatted: string
        transaction_count: number
        total_amount: number
        total_amount_formatted: string
        percentage: number
      }>
      total_amount: number
      total_amount_formatted: string
      method_count: number
    }
    order_types_today?: {
      data: Array<{
        order_type: string
        order_type_formatted: string
        order_count: number
        total_amount: number
        total_amount_formatted: string
        percentage: number
      }>
      total_amount: number
      total_amount_formatted: string
      type_count: number
    }
  }
  sales: {
    daily_trend: Array<{ date: string; total_revenue: number }>
    by_type: Array<{ order_type: string; total_amount: number }>
    top_products: Array<{ product_name: string; total_quantity: number; total_revenue: number }>
  }
  purchases: {
    daily_trend: Array<{ date: string; total_amount: number }>
    by_status: Array<{ status: string; count: number }>
    top_suppliers: Array<{ supplier_name: string; purchase_count: number; total_amount: number }>
    top_purchased_items: Array<{ item_name: string; total_quantity: number; total_amount: number }>
  }
  inventory: {
    by_category: Array<{ item_name: string; total_stock: number; unit?: string }>
    low_stock: Array<{ item_name: string; current_stock: number; min_stock: number }>
    movements: Array<{ date: string; movement_type: string; total_quantity: number }>
  }
  order_types: Array<{ order_type: string; count: number }>
  purchase_status: Array<{ status: string; count: number }>
}

// State
const loading = ref(false)
const selectedPeriod = ref('30')
const customStartDate = ref('')
const customEndDate = ref('')
const hourStart = ref('')
const hourEnd = ref('')
const dashboardData = ref<DashboardData | null>(null)

// Chart refs
const salesChart = ref<HTMLCanvasElement | null>(null)
const salesPieChart = ref<HTMLCanvasElement | null>(null)
const purchaseChart = ref<HTMLCanvasElement | null>(null)
const purchasePieChart = ref<HTMLCanvasElement | null>(null)
const inventoryChart = ref<HTMLCanvasElement | null>(null)

// Chart instances
let salesChartInstance: Chart | null = null
let salesPieChartInstance: Chart | null = null
let purchaseChartInstance: Chart | null = null
let purchasePieChartInstance: Chart | null = null
let inventoryChartInstance: Chart | null = null

// Period options
const periodOptions = [
  { label: '7 Hari Terakhir', value: '7' },
  { label: '30 Hari Terakhir', value: '30' },
  { label: '90 Hari Terakhir', value: '90' },
  { label: '1 Tahun Terakhir', value: '365' },
  { label: 'Custom Range', value: 'custom' }
]

// Table headers
const lowStockHeaders = [
  { title: 'Item', key: 'item_name' },
  { title: 'Stock Saat Ini', key: 'current_stock' },
  { title: 'Stock Minimum', key: 'min_stock' }
]

const topProductsHeaders = [
  { title: 'Produk', key: 'product_name' },
  { title: 'Terjual', key: 'total_quantity' },
  { title: 'Pendapatan', key: 'total_revenue' }
]

const topSuppliersHeaders = [
  { title: 'Supplier', key: 'supplier_name' },
  { title: 'Total Pembelian', key: 'purchase_count' },
  { title: 'Total Nilai', key: 'total_amount' }
]

// Computed data
const summaryData = computed(() => dashboardData.value?.summary || {} as DashboardData['summary'])
const salesData = computed(() => dashboardData.value?.sales || {} as DashboardData['sales'])
const purchaseData = computed(() => dashboardData.value?.purchases || {} as DashboardData['purchases'])
const inventoryData = computed(() => dashboardData.value?.inventory || {} as DashboardData['inventory'])

// Load dashboard data
const loadDashboard = async () => {
  loading.value = true
  try {
    const params: any = {}
    
    // Check if using custom date range
    if (selectedPeriod.value === 'custom') {
      if (customStartDate.value && customEndDate.value) {
        // Ensure proper date format (YYYY-MM-DD)
        const startDate = new Date(customStartDate.value)
        const endDate = new Date(customEndDate.value)
        
        params.start_date = startDate.toISOString().split('T')[0]
        params.end_date = endDate.toISOString().split('T')[0]
      } else {
        // Fallback to 30 days if custom dates not set
        params.period = '30'
      }
    } else {
      params.period = selectedPeriod.value
    }
    
    // Add hour filter if provided
    if (hourStart.value && hourEnd.value) {
      params.hour_start = hourStart.value
      params.hour_end = hourEnd.value
    }
    
    const response = await axios.get('/api/dashboard/analytics', { params })
    
    if (response.data.success) {
      dashboardData.value = response.data.data
      await nextTick()
      createCharts()
    } else {
      console.error('API returned error:', response.data)
    }
  } catch (error) {
    console.error('Error loading dashboard:', error)
  } finally {
    loading.value = false
  }
}

// Helper functions for date range
const onPeriodChange = (value: string) => {
  if (value !== 'custom') {
    // Clear custom dates when using preset periods
    customStartDate.value = ''
    customEndDate.value = ''
  }
  loadDashboard()
}

const onCustomDateChange = () => {
  if (customStartDate.value && customEndDate.value) {
    selectedPeriod.value = 'custom'
    loadDashboard()
  }
}

const onHourChange = () => {
  // Reload dashboard when hour filter changes
  if (hourStart.value && hourEnd.value) {
    loadDashboard()
  } else if (!hourStart.value && !hourEnd.value) {
    // Clear hour filter - reload to show all hours
    loadDashboard()
  }
}

// Initialize custom dates with default 30 days range
const initializeDates = () => {
  const endDate = new Date()
  const startDate = new Date()
  startDate.setDate(endDate.getDate() - 30)
  
  customEndDate.value = endDate.toISOString().split('T')[0]
  customStartDate.value = startDate.toISOString().split('T')[0]
}

// Get display text for date range
const getDateRangeDisplay = () => {
  let dateRange = ''
  
  if (selectedPeriod.value === 'custom' && customStartDate.value && customEndDate.value) {
    const startDate = new Date(customStartDate.value).toLocaleDateString('id-ID')
    const endDate = new Date(customEndDate.value).toLocaleDateString('id-ID')
    dateRange = `(${startDate} - ${endDate})`
  } else {
    const periodLabel = periodOptions.find(p => p.value === selectedPeriod.value)?.label || '30 Hari Terakhir'
    dateRange = `(${periodLabel})`
  }
  
  // Add hour range if provided
  if (hourStart.value && hourEnd.value) {
    dateRange += ` Jam ${hourStart.value} - ${hourEnd.value}`
  }
  
  return dateRange
}

// Color mapping for payment methods
const getPaymentMethodColor = (method: string) => {
  const colors: { [key: string]: string } = {
    'qris': '#2196F3',
    'cash': '#4CAF50',
    'credit_card': '#9C27B0',
    'debit_card': '#FF5722',
    'bank_transfer': '#607D8B',
    'e_wallet': '#FF9800',
    'gopay': '#00D982',
    'ovo': '#4C3DDB',
    'dana': '#118EEA',
    'shopeepay': '#EE4D2D'
  }
  return colors[method] || '#9E9E9E'
}

// Color mapping for order types
const getOrderTypeColor = (type: string) => {
  const colors: { [key: string]: string } = {
    'dine_in': '#4CAF50',
    'take_away': '#FF9800',
    'delivery': '#2196F3',
    'pickup': '#9C27B0'
  }
  return colors[type] || '#9E9E9E'
}

// Create charts
const createCharts = () => {
  if (!dashboardData.value) {
    return
  }
  
  nextTick(() => {
    
    // Debug canvas refs
    setTimeout(() => {
      // Create charts one by one with validation
      if (salesChart.value && dashboardData.value?.sales) {
        createSalesChart()
      }
      
      if (salesPieChart.value && salesData.value?.top_products) {
        createSalesPieChart()
      }
      
      if (purchaseChart.value && dashboardData.value?.purchases) {
        createPurchaseChart()
      }
      
      if (purchasePieChart.value && purchaseData.value?.top_purchased_items) {
        createPurchasePieChart()
      }
      
      if (inventoryChart.value && dashboardData.value?.inventory) {
        createInventoryChart()
      }
    }, 200)
  })
}

// Sales trend chart (S-curve)
const createSalesChart = () => {
  if (salesChartInstance) {
    salesChartInstance.destroy()
  }
  
  const ctx = salesChart.value?.getContext('2d')
  
  if (!ctx) {
    return
  }

  if (!salesData.value?.daily_trend || !Array.isArray(salesData.value.daily_trend)) {
    return
  }

  const data = salesData.value.daily_trend
  
  try {
    const textColor = isDark.value ? '#FFFFFF' : '#333333'
    const gridColor = isDark.value ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.1)'
    
    salesChartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: data.map((item: any) => new Date(item.date).toLocaleDateString('id-ID')),
        datasets: [{
          label: 'Penjualan Harian',
          data: data.map((item: any) => item.total_revenue),
          borderColor: '#D4A574',
          backgroundColor: 'rgba(212, 165, 116, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
            labels: {
              color: textColor
            }
          },
          tooltip: {
            titleColor: textColor,
            bodyColor: textColor
          }
        },
        scales: {
          x: {
            ticks: {
              color: textColor,
              font: {
                size: 11
              }
            },
            grid: {
              color: gridColor
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              color: textColor,
              font: {
                size: 11
              },
              callback: function(value: any) {
                return formatCurrency(Number(value))
              }
            },
            grid: {
              color: gridColor
            }
          }
        }
      }
    })
  } catch (error) {
    console.error('Error creating sales chart:', error)
  }
}

// Top selling products pie chart
const createSalesPieChart = () => {

  
  if (salesPieChartInstance) {
    salesPieChartInstance.destroy()
  }
  
  const ctx = salesPieChart.value?.getContext('2d')
  
  if (!ctx) {
    return
  }

  const data = salesData.value?.top_products
  if (!data || !Array.isArray(data)) {
    return
  }

  try {
    const legendColor = isDark.value ? '#FFFFFF' : '#333333'
    
    salesPieChartInstance = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: data.map((item: any) => item.product_name || 'Unknown'),
        datasets: [{
          data: data.map((item: any) => item.total_quantity || 0),
          backgroundColor: [
            '#FFD700', // Gold
            '#FFA500', // Orange
            '#FF8C00', // Dark Orange
            '#FFB347', // Pastel Orange
            '#FFCC99', // Peach
            '#FFE4B5', // Moccasin
            '#F0E68C', // Khaki
            '#FFD966', // Light Gold
            '#FFDB58', // Mustard
            '#FFE5B4', // Peach Puff
            '#FFC04C', // Yellow Orange
            '#FFDB99'  // Light Peach
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: legendColor,
              padding: 15,
              font: {
                size: 12,
                weight: '500'
              }
            }
          },
          tooltip: {
            backgroundColor: isDark.value ? 'rgba(33, 33, 33, 0.95)' : 'rgba(255, 255, 255, 0.95)',
            titleColor: isDark.value ? '#FFFFFF' : '#000000',
            bodyColor: isDark.value ? '#FFFFFF' : '#333333',
            borderColor: isDark.value ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)',
            borderWidth: 1,
            titleFont: {
              weight: 'bold'
            }
          }
        }
      }
    })

  } catch (error) {
    console.error('Error creating sales pie chart:', error)
  }
}

// Purchase trend chart
const createPurchaseChart = () => {
  if (purchaseChartInstance) {
    purchaseChartInstance.destroy()
  }
  
  const ctx = purchaseChart.value?.getContext('2d')
  if (!ctx || !purchaseData.value.daily_trend) return

  const data = purchaseData.value.daily_trend
  
  const textColor = isDark.value ? '#FFFFFF' : '#333333'
  const gridColor = isDark.value ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.1)'
  
  purchaseChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.map((item: any) => new Date(item.date).toLocaleDateString('id-ID')),
      datasets: [{
        label: 'Pembelian Harian',
        data: data.map((item: any) => item.total_amount),
        borderColor: '#2196F3',
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
          display: false,
          labels: {
            color: textColor
          }
        },
        tooltip: {
          titleColor: textColor,
          bodyColor: textColor
        }
      },
      scales: {
        x: {
          ticks: {
            color: textColor,
            font: {
              size: 11
            }
          },
          grid: {
            color: gridColor
          }
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: textColor,
            font: {
              size: 11
            },
            callback: function(value: any) {
              return formatCurrency(Number(value))
            }
          },
          grid: {
            color: gridColor
          }
        }
      }
    }
  })
}

// Top purchased items pie chart
const createPurchasePieChart = () => {


  
  if (purchasePieChartInstance) {
    purchasePieChartInstance.destroy()
  }
  
  const ctx = purchasePieChart.value?.getContext('2d')
  if (!ctx) {
    return
  }

  const data = purchaseData.value?.top_purchased_items
  if (!data || !Array.isArray(data)) {
    return
  }


  
  const legendColor = isDark.value ? '#FFFFFF' : '#333333'
  
  purchasePieChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map((item: any) => item.item_name || 'Unknown'),
      datasets: [{
        data: data.map((item: any) => item.total_quantity || 0),
        backgroundColor: [
          '#4CAF50', // Green
          '#66BB6A', // Light Green
          '#81C784', // Lighter Green
          '#FF9800', // Orange
          '#FFB74D', // Light Orange
          '#FFA726', // Medium Orange
          '#2196F3', // Blue
          '#42A5F5', // Light Blue
          '#64B5F6', // Lighter Blue
          '#9C27B0', // Purple
          '#AB47BC', // Light Purple
          '#BA68C8'  // Lighter Purple
        ]
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: legendColor,
            padding: 15,
            font: {
              size: 12,
              weight: '500'
            }
          }
        },
        tooltip: {
          backgroundColor: isDark.value ? 'rgba(33, 33, 33, 0.95)' : 'rgba(255, 255, 255, 0.95)',
          titleColor: isDark.value ? '#FFFFFF' : '#000000',
          bodyColor: isDark.value ? '#FFFFFF' : '#333333',
          borderColor: isDark.value ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)',
          borderWidth: 1,
          titleFont: {
            weight: 'bold'
          }
        }
      }
    }
  })
}

// Inventory pie chart
const createInventoryChart = () => {


  
  if (inventoryChartInstance) {
    inventoryChartInstance.destroy()
  }
  
  const ctx = inventoryChart.value?.getContext('2d')
  if (!ctx) {
    return
  }

  const data = inventoryData.value?.by_category
  if (!data || !Array.isArray(data)) {
    return
  }


  
  const legendColor = isDark.value ? '#FFFFFF' : '#333333'
  
  inventoryChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map((item: any) => item.item_name || 'Unknown'),
      datasets: [{
        data: data.map((item: any) => item.total_stock || 0),
        backgroundColor: [
          '#FF6B9D', // Pink
          '#FFA8C5', // Light Pink
          '#FFD1DC', // Lighter Pink
          '#36A2EB', // Blue
          '#5CB3F0', // Light Blue
          '#82C4F5', // Lighter Blue
          '#FFCE56', // Yellow
          '#FFD97D', // Light Yellow
          '#FFE5A4', // Lighter Yellow
          '#4BC0C0', // Teal
          '#6DD0D0', // Light Teal
          '#90E0E0'  // Lighter Teal
        ]
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: legendColor,
            padding: 15,
            font: {
              size: 12,
              weight: '500'
            }
          }
        },
        tooltip: {
          backgroundColor: isDark.value ? 'rgba(33, 33, 33, 0.95)' : 'rgba(255, 255, 255, 0.95)',
          titleColor: isDark.value ? '#FFFFFF' : '#000000',
          bodyColor: isDark.value ? '#FFFFFF' : '#333333',
          borderColor: isDark.value ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)',
          borderWidth: 1,
          titleFont: {
            weight: 'bold'
          }
        }
      }
    }
  })
}

// Watch for data changes
watch(dashboardData, () => {
  if (dashboardData.value) {
    nextTick(() => {
      createCharts()
    })
  }
})

// Watch for theme changes
watch(isDark, () => {
  // Recreate charts when theme changes
  nextTick(() => {
    createCharts()
  })
})

// Lifecycle
onMounted(() => {
  initializeDates()
  loadDashboard()
})
</script>

<style scoped lang="scss">
.dashboard-page {
  .coffee-title {
    color: rgb(var(--v-theme-primary));
    font-family: 'Inter', sans-serif;
  }

  .coffee-subtitle {
    opacity: 0.8;
    margin-top: 4px;
  }

  .summary-card {
    border-radius: 12px;
    transition: all 0.3s ease;
    
    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
  }

  .chart-card {
    height: 100%;
    border-radius: 12px;
    
    .v-card-title {
      border-radius: 12px 12px 0 0;
      padding: 16px 24px;
      font-weight: 600;
      
      .v-icon {
        color: rgb(var(--v-theme-primary));
      }
    }
    
    .v-card-text {
      padding: 24px;
    }
  }
  
  // Light mode card title
  .v-theme--light .chart-card .v-card-title {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #333333;
  }
  
  // Dark mode card title
  .v-theme--dark .chart-card .v-card-title {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
    color: #FFFFFF;
  }
}

// Responsive adjustments
@media (max-width: 600px) {
  .dashboard-page {
    .text-h4 {
      font-size: 1.5rem !important;
    }
    
    .coffee-subtitle {
      font-size: 0.875rem;
    }
  }
}

@media (max-width: 960px) {
  .chart-card canvas {
    height: 250px !important;
  }
}
</style>
