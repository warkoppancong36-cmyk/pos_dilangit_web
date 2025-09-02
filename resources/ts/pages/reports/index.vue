<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  name: reports
</route>

<template>
  <div class="reports-page">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center mb-6 ga-4">
      <div>
        <h1 class="text-h4 font-weight-bold">Laporan</h1>
        <p class="text-body-1 text-medium-emphasis">
          Kelola dan lihat laporan penjualan dan pembelian
        </p>
      </div>
    </div>

    <!-- Report Menu Cards -->
    <VRow>
      <VCol cols="12" md="6">
        <VCard 
          class="report-menu-card h-100" 
          hover
          @click="navigateToSalesReport"
        >
          <VCardText class="d-flex align-center pa-6">
            <div class="flex-grow-1">
              <div class="d-flex align-center mb-3">
                <VIcon icon="mdi-chart-line" size="32" class="text-success me-3" />
                <div>
                  <h3 class="text-h6 font-weight-bold">Laporan Penjualan</h3>
                  <p class="text-body-2 text-medium-emphasis ma-0">
                    Analisis penjualan, produk terlaris, dan performa harian
                  </p>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <VChip size="small" color="success" variant="tonal">
                  <VIcon start icon="mdi-calendar-month" />
                  Filter Bulan
                </VChip>
                <VChip size="small" color="success" variant="tonal">
                  <VIcon start icon="mdi-calendar-range" />
                  Custom Range
                </VChip>
                <VChip size="small" color="success" variant="tonal">
                  <VIcon start icon="mdi-file-export" />
                  Export Excel
                </VChip>
              </div>
            </div>
            <VIcon icon="mdi-chevron-right" size="24" class="text-medium-emphasis" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard 
          class="report-menu-card h-100" 
          hover
          @click="navigateToPurchaseReport"
        >
          <VCardText class="d-flex align-center pa-6">
            <div class="flex-grow-1">
              <div class="d-flex align-center mb-3">
                <VIcon icon="mdi-cart" size="32" class="text-primary me-3" />
                <div>
                  <h3 class="text-h6 font-weight-bold">Laporan Pembelian</h3>
                  <p class="text-body-2 text-medium-emphasis ma-0">
                    Analisis pembelian, supplier, dan trend inventori
                  </p>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <VChip size="small" color="primary" variant="tonal">
                  <VIcon start icon="mdi-calendar-month" />
                  Filter Bulan
                </VChip>
                <VChip size="small" color="primary" variant="tonal">
                  <VIcon start icon="mdi-calendar-range" />
                  Custom Range
                </VChip>
                <VChip size="small" color="primary" variant="tonal">
                  <VIcon start icon="mdi-file-export" />
                  Export Excel
                </VChip>
              </div>
            </div>
            <VIcon icon="mdi-chevron-right" size="24" class="text-medium-emphasis" />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Quick Stats Row -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardTitle>
            <VIcon icon="mdi-chart-box" class="me-2" />
            Ringkasan Bulan Ini
          </VCardTitle>
          <VCardText>
            <VRow>
              <VCol cols="6" md="3">
                <div class="text-center">
                  <div class="text-h4 text-success font-weight-bold">
                    {{ formatCurrency(monthlyStats.sales || 0) }}
                  </div>
                  <div class="text-body-2 text-medium-emphasis">Total Penjualan</div>
                </div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-center">
                  <div class="text-h4 text-primary font-weight-bold">
                    {{ formatCurrency(monthlyStats.purchases || 0) }}
                  </div>
                  <div class="text-body-2 text-medium-emphasis">Total Pembelian</div>
                </div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-center">
                  <div class="text-h4 text-info font-weight-bold">
                    {{ monthlyStats.orders || 0 }}
                  </div>
                  <div class="text-body-2 text-medium-emphasis">Total Order</div>
                </div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-center">
                  <div class="text-h4 text-warning font-weight-bold">
                    {{ formatCurrency(monthlyStats.profit || 0) }}
                  </div>
                  <div class="text-body-2 text-medium-emphasis">Estimasi Profit</div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

// State
const monthlyStats = ref({
  sales: 0,
  purchases: 0,
  orders: 0,
  profit: 0
})

// Format currency
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

// Navigation functions
const navigateToSalesReport = () => {
  router.push('/reports/sales')
}

const navigateToPurchaseReport = () => {
  router.push('/reports/purchases')
}

// Load monthly statistics
const loadMonthlyStats = async () => {
  try {
    const currentYear = new Date().getFullYear()
    const targetMonth = `${currentYear}-08` // Use August of current year where data exists
    
    // Load sales data
    const salesResponse = await axios.get('/api/reports/sales', {
      params: { month: targetMonth }
    })
    
    // Load purchase data
    const purchaseResponse = await axios.get('/api/reports/purchases', {
      params: { month: targetMonth }
    })
    
    if (salesResponse.data.success && purchaseResponse.data.success) {
      const salesData = salesResponse.data.data.summary
      const purchaseData = purchaseResponse.data.data.summary
      
      monthlyStats.value = {
        sales: salesData.total_revenue || 0,
        purchases: purchaseData.total_amount || 0,
        orders: salesData.total_orders || 0,
        profit: (salesData.total_revenue || 0) - (purchaseData.total_amount || 0)
      }
    }
  } catch (error) {
    console.error('Error loading monthly stats:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadMonthlyStats()
})
</script>

<style scoped lang="scss">
.reports-page {
  .report-menu-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    
    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      border-color: rgb(var(--v-theme-primary));
    }
    
    .v-card-text {
      padding: 24px;
    }
  }
}

// Responsive adjustments
@media (max-width: 600px) {
  .reports-page {
    .text-h4 {
      font-size: 1.5rem !important;
    }
    
    .report-menu-card .v-card-text {
      padding: 16px !important;
    }
  }
}
</style>
