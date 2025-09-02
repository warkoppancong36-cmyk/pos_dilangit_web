<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  name: dashboard
</route>

<template>
  <div class="dashboard-page">
    <!-- Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Dashboard Analytics</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">
          Pantau penjualan, pembelian, dan inventory coffee shop Anda
        </p>
      </div>
      <div class="d-flex gap-3 align-center">
        <VSelect
          v-model="selectedPeriod"
          :items="periodOptions"
          item-title="label"
          item-value="value"
          density="comfortable"
          variant="outlined"
          hide-details
          @update:model-value="loadDashboard"
        >
          <template #prepend-inner>
            <VIcon icon="mdi-calendar-range" />
          </template>
        </VSelect>
        <VBtn
          color="primary"
          prepend-icon="mdi-refresh"
          @click="loadDashboard"
          :loading="loading"
        >
          Refresh
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
                <div class="text-h6 font-weight-bold">Penjualan Hari Ini</div>
                <div class="text-h4 text-success mt-2">{{ formatCurrency(summaryData.today_sales?.value || 0) }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ (summaryData.today_sales?.growth ?? 0) >= 0 ? '+' : '' }}{{ summaryData.today_sales?.growth ?? 0 }}% dari kemarin
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
                <div class="text-h6 font-weight-bold">Transaksi Penjualan Hari Ini</div>
                <div class="text-h4 text-info mt-2">{{ summaryData.today_orders?.value || 0 }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ (summaryData.today_orders?.growth ?? 0) >= 0 ? '+' : '' }}{{ summaryData.today_orders?.growth ?? 0 }}% dari kemarin
                </div>
              </div>
              <VIcon icon="mdi-receipt" size="48" class="text-info" />
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" md="3">
          <VCard class="summary-card h-100">
            <VCardText class="d-flex align-center">
              <div class="flex-grow-1">
                <div class="text-h6 font-weight-bold">Nilai Inventory</div>
                <div class="text-h4 text-warning mt-2">{{ formatCurrency(summaryData.inventory_value?.value || 0) }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ summaryData.inventory_value?.low_stock_count || 0 }} item stok rendah
                </div>
              </div>
              <VIcon icon="mdi-package-variant" size="48" class="text-warning" />
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" md="3">
          <VCard class="summary-card h-100">
            <VCardText class="d-flex align-center">
              <div class="flex-grow-1">
                <div class="text-h6 font-weight-bold">Rata-rata Order</div>
                <div class="text-h4 text-primary mt-2">{{ formatCurrency(summaryData.period_summary?.avg_order_value || 0) }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ summaryData.period_summary?.total_orders || 0 }} total order
                </div>
              </div>
              <VIcon icon="mdi-chart-line" size="48" class="text-primary" />
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
              Tren Penjualan ({{ selectedPeriod }} Hari)
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
              Tren Pembelian ({{ selectedPeriod }} Hari)
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
    today_sales?: { value: number; growth: number }
    today_orders?: { value: number; growth: number }
    inventory_value?: { value: number; low_stock_count: number }
    period_summary?: { avg_order_value: number; total_orders: number }
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
  { label: '1 Tahun Terakhir', value: '365' }
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
    const response = await axios.get('/api/dashboard/analytics', {
      params: { period: selectedPeriod.value }
    })
    
    if (response.data.success) {
      dashboardData.value = response.data.data
      await nextTick()
      createCharts()
    }
  } catch (error) {
    console.error('Error loading dashboard:', error)
  } finally {
    loading.value = false
  }
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
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value: any) {
                return formatCurrency(Number(value))
              }
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
    salesPieChartInstance = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: data.map((item: any) => item.product_name || 'Unknown'),
        datasets: [{
          data: data.map((item: any) => item.total_quantity || 0),
          backgroundColor: ['#D4A574', '#E6B887', '#F2D099', '#B8956A', '#9A7F5A', '#C69C6C', '#E4C29F', '#A08660', '#BD9B73', '#F0D8B5']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
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
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value: any) {
              return formatCurrency(Number(value))
            }
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


  
  purchasePieChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map((item: any) => item.item_name || 'Unknown'),
      datasets: [{
        data: data.map((item: any) => item.total_quantity || 0),
        backgroundColor: ['#4CAF50', '#FF9800', '#F44336', '#9C27B0', '#607D8B', '#2196F3', '#FF5722', '#795548', '#E91E63', '#3F51B5']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
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


  
  inventoryChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map((item: any) => item.item_name || 'Unknown'),
      datasets: [{
        data: data.map((item: any) => item.total_stock || 0),
        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E6B887', '#D4A574', '#F2D099', '#B8956A']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
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

// Lifecycle
onMounted(() => {

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
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border-radius: 12px 12px 0 0;
      padding: 16px 24px;
      
      .v-icon {
        color: rgb(var(--v-theme-primary));
      }
    }
    
    .v-card-text {
      padding: 24px;
    }
  }
}
</style>
