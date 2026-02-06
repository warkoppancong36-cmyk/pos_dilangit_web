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
          <VCol cols="12" md="3">
            <VSelect
              v-model="selectedPeriod"
              :items="periodOptions"
              item-title="title"
              item-value="value"
              label="Pilih Periode"
              variant="outlined"
              density="compact"
              @update:model-value="onPeriodChange"
            />
          </VCol>

          <VCol cols="12" md="2" v-if="selectedPeriod === 'month'">
            <VSelect
              v-model="selectedYear"
              :items="yearOptions"
              label="Tahun"
              variant="outlined"
              density="compact"
              @update:model-value="onYearChange"
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

          <VCol cols="12" md="3" v-if="selectedPeriod === 'month'">
            <VSelect
              v-model="selectedMonth"
              :items="monthOptions"
              item-title="title"
              item-value="value"
              label="Pilih Bulan"
              variant="outlined"
              density="compact"
              @update:model-value="loadReportData"
            />
          </VCol>

          <!-- Hour Filter -->
          <VCol cols="12" md="2">
            <VTextField
              v-model="hourStart"
              type="time"
              label="Jam Mulai"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="onHourChange"
            >
              <template #prepend-inner>
                <VIcon icon="mdi-clock-outline" size="20" />
              </template>
            </VTextField>
          </VCol>
          
          <VCol cols="12" md="2">
            <VTextField
              v-model="hourEnd"
              type="time"
              label="Jam Akhir"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="onHourChange"
            >
              <template #prepend-inner>
                <VIcon icon="mdi-clock-outline" size="20" />
              </template>
            </VTextField>
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

       <!-- Today's Sales Cards -->
      <VRow class="mb-6">
        <VCol cols="12" md="4">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-calendar-today" size="32" class="text-purple mb-2" />
              <div class="text-h5 font-weight-bold text-purple">
                {{ formatCurrency(todaySalesData.total_sales) }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Penjualan Hari Ini</div>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ todaySalesData.total_orders }} order | {{ getSelectedPeriodLabel() }}
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="4">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-filter-variant" size="32" class="text-success mb-2" />
              <div class="text-h5 font-weight-bold text-success">
                {{ formatCurrency(reportData?.summary?.total_revenue || 0) }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Total Penjualan (Filter)</div>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ reportData?.summary?.total_orders || 0 }} order | {{ getSelectedPeriodLabel() }}
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="4">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-chart-bar" size="32" class="text-indigo mb-2" />
              <div class="text-h5 font-weight-bold text-indigo">
                {{ formatCurrency(getDailyAverageSales()) }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Rata-rata Penjualan Harian</div>
              <div class="text-caption text-medium-emphasis mt-1">
                Per {{ getTotalDaysInPeriod() }} hari aktif
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Payment Methods and Order Types Analytics Cards -->
      <VRow class="mb-6">
        <!-- Payment Methods Card -->
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle class="pb-2">
              <VIcon icon="mdi-credit-card" class="me-2" />
              Metode Pembayaran
            </VCardTitle>
            <VCardText>
              <div v-if="paymentMethodData.length > 0">
                <div 
                  v-for="(item, index) in paymentMethodData" 
                  :key="index"
                  class="d-flex justify-space-between align-center mb-3"
                >
                  <div class="d-flex align-center">
                    <VIcon 
                      :icon="getPaymentIcon(item.payment_method)" 
                      :color="getPaymentMethodColor(index)"
                      class="me-2"
                    />
                    <span class="text-body-2">{{ getPaymentLabel(item.payment_method) }}</span>
                  </div>
                  <div class="text-end">
                    <div class="text-body-2 font-weight-bold">{{ item.total_amount_formatted }}</div>
                    <div class="text-caption text-medium-emphasis">{{ item.order_count }} order</div>
                  </div>
                </div>
              </div>
              
              <!-- NO DATA STATE -->
              <div v-else class="text-center py-4">
                <VIcon icon="mdi-cash-off" size="48" class="text-grey-lighten-1 mb-2" />
                <div class="text-body-2 text-medium-emphasis">Tidak ada pembayaran hari ini</div>
                <div class="text-h6 font-weight-bold text-grey">Rp 0</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Order Types Card -->
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle class="pb-2">
              <VIcon icon="mdi-food" class="me-2" />
              Tipe Order
            </VCardTitle>
            <VCardText>
              <div v-if="orderTypeData.length > 0">
                <div 
                  v-for="(item, index) in orderTypeData" 
                  :key="index"
                  class="d-flex justify-space-between align-center mb-3"
                >
                  <div class="d-flex align-center">
                    <VIcon 
                      icon="mdi-food" 
                      :color="getOrderTypeColor(index)"
                      class="me-2"
                    />
                    <span class="text-body-2 text-capitalize">{{ item.order_type }}</span>
                  </div>
                  <div class="text-end">
                    <div class="text-body-2 font-weight-bold">{{ item.total_amount_formatted }}</div>
                    <div class="text-caption text-medium-emphasis">{{ item.order_count }} order</div>
                  </div>
                </div>
              </div>
              
              <!-- NO DATA STATE -->
              <div v-else class="text-center py-4">
                <VIcon icon="mdi-food-off" size="48" class="text-grey-lighten-1 mb-2" />
                <div class="text-body-2 text-medium-emphasis">Tidak ada pesanan hari ini</div>
                <div class="text-h6 font-weight-bold text-grey">Rp 0</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <VRow class="mb-6">
        <VCol cols="6" md="3">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-currency-usd" size="32" class="text-success mb-2" />
              <div class="text-h5 font-weight-bold text-success">
                {{ formatCurrency(reportData.summary.total_revenue) }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Total Pendapatan</div>
              <div v-if="reportData.summary.growth_percentage !== undefined" class="mt-1">
                <VChip 
                  :color="reportData.summary.growth_percentage >= 0 ? 'success' : 'error'" 
                  size="x-small" 
                  variant="tonal"
                >
                  {{ reportData.summary.growth_percentage >= 0 ? '+' : '' }}{{ reportData.summary.growth_percentage }}%
                </VChip>
              </div>
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
              <div class="text-caption text-medium-emphasis mt-1">
                AOV: {{ formatCurrency(reportData.summary.average_order) }}
              </div>
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
              <div class="text-caption text-medium-emphasis mt-1">
                {{ reportData.summary.completed_orders }} selesai
              </div>
            </VCardText>
          </VCard>
        </VCol>
        
        <VCol cols="6" md="3">
          <VCard>
            <VCardText class="text-center">
              <VIcon icon="mdi-account-group" size="32" class="text-warning mb-2" />
              <div class="text-h5 font-weight-bold text-warning">
                {{ reportData.customer_behavior?.new_customers || 0 }}
              </div>
              <div class="text-body-2 text-medium-emphasis">Customer Baru</div>
              <div class="text-caption text-medium-emphasis mt-1">
                Retention: {{ reportData.customer_behavior?.retention_rate || 0 }}%
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>


      <!-- Charts Row - Moved to Top Priority -->
      <VRow class="mb-6">
        <VCol cols="12">
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
      </VRow>

      <!-- Top Data Tables - After Charts -->
      <VRow class="mb-6">
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
        
        <VCol cols="12" md="4">
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
        
        <VCol cols="12" md="4">
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

      <!-- All Products Sold Table -->
      <VRow class="mb-6" v-if="reportData.all_products && reportData.all_products.length > 0">
        <VCol cols="12">
          <VCard>
            <VCardTitle class="d-flex align-center justify-space-between">
              <div class="d-flex align-center gap-2">
                <VIcon icon="mdi-package-variant" />
                Semua Produk Terjual ({{ reportData.all_products.length }} produk)
              </div>
              <VTextField
                v-model="allProductsSearch"
                prepend-inner-icon="mdi-magnify"
                label="Cari produk..."
                placeholder="Ketik nama produk"
                single-line
                hide-details
                density="compact"
                variant="outlined"
                clearable
                style="max-width: 300px;"
              />
            </VCardTitle>
            <VCardText>
              <VDataTable
                :headers="allProductsHeaders"
                :items="reportData.all_products"
                :search="allProductsSearch"
                :items-per-page="10"
                class="elevation-0"
                no-data-text="Tidak ada data produk"
              >
                <template #item.no="{ index }">
                  {{ index + 1 }}
                </template>
                <template #item.quantity="{ item }">
                  <VChip size="small" color="primary" variant="tonal">
                    {{ (item as any).quantity }}
                  </VChip>
                </template>
                <template #item.revenue="{ item }">
                  <span class="font-weight-bold text-success">
                    {{ (item as any).revenue }}
                  </span>
                </template>
                <template #item.avg_price="{ item }">
                  <span class="text-body-2">
                    {{ (item as any).avg_price }}
                  </span>
                </template>
              </VDataTable>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Business Analytics Section -->
      <VRow class="mb-6">
        <!-- Peak Hours Analysis -->
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle class="d-flex align-center gap-2">
              <VIcon icon="mdi-clock-outline" />
              Jam Ramai (Peak Hours)
            </VCardTitle>
            <VCardText>
              <div v-if="reportData.peak_hours && reportData.peak_hours.length > 0">
                <div v-for="hour in getTopHours(reportData.peak_hours)" :key="hour.hour" class="mb-2">
                  <div class="d-flex justify-space-between align-center">
                    <div class="d-flex align-center gap-2">
                      <VIcon :icon="getHourIcon(hour.hour)" size="16" />
                      <span class="font-weight-medium">{{ hour.hour_display }}</span>
                    </div>
                    <div class="text-end">
                      <div class="text-body-2 font-weight-bold">{{ hour.order_count }} order</div>
                      <div class="text-caption text-success">{{ formatCurrency(hour.revenue) }}</div>
                    </div>
                  </div>
                  <VProgressLinear
                    :model-value="getHourPercentage(hour.order_count, reportData.peak_hours)"
                    color="primary"
                    height="4"
                    class="mt-1"
                  />
                </div>
              </div>
              <div v-else class="text-center py-4">
                <p class="text-body-2 text-medium-emphasis">Tidak ada data jam ramai</p>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Payment Methods -->
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle class="d-flex align-center gap-2">
              <VIcon icon="mdi-credit-card-outline" />
              Metode Pembayaran
            </VCardTitle>
            <VCardText>
              <div v-if="reportData.payment_methods && reportData.payment_methods.length > 0">
                <div v-for="method in reportData.payment_methods" :key="method.method" class="mb-3">
                  <div class="d-flex justify-space-between align-center mb-1">
                    <div class="d-flex align-center gap-2">
                      <VIcon :icon="getPaymentIcon(method.method)" size="16" />
                      <span class="font-weight-medium">{{ getPaymentLabel(method.method) }}</span>
                    </div>
                    <div class="text-end">
                      <VChip size="small" color="primary" variant="tonal">
                        {{ method.percentage }}%
                      </VChip>
                    </div>
                  </div>
                  <div class="d-flex justify-space-between align-center">
                    <span class="text-body-2">{{ method.order_count }} transaksi</span>
                    <span class="text-body-2 font-weight-bold text-success">{{ formatCurrency(method.revenue) }}</span>
                  </div>
                  <VProgressLinear
                    :model-value="method.percentage"
                    color="success"
                    height="4"
                    class="mt-1"
                  />
                </div>
              </div>
              <div v-else class="text-center py-4">
                <p class="text-body-2 text-medium-emphasis">Tidak ada data metode pembayaran</p>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Sales by Day of Week -->
      <VRow class="mb-6" v-if="reportData.sales_by_day && reportData.sales_by_day.length > 0">
        <VCol cols="12">
          <VCard>
            <VCardTitle class="d-flex align-center gap-2">
              <VIcon icon="mdi-calendar-week" />
              Performa Penjualan per Hari
            </VCardTitle>
            <VCardText>
              <VRow>
                <VCol v-for="day in reportData.sales_by_day" :key="day.day_name" cols="6" md="1.7">
                  <div class="text-center">
                    <div class="text-h6 font-weight-bold text-primary">{{ day.order_count }}</div>
                    <div class="text-caption text-medium-emphasis mb-1">{{ day.day_name }}</div>
                    <div class="text-body-2 font-weight-medium text-success">{{ formatCurrency(day.revenue) }}</div>
                    <VProgressLinear
                      :model-value="getDayPercentage(day.order_count, reportData.sales_by_day)"
                      color="primary"
                      height="3"
                      class="mt-1"
                    />
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Category Performance -->
      <VRow class="mb-6" v-if="reportData.category_performance && reportData.category_performance.length > 0">
        <VCol cols="12">
          <VCard>
            <VCardTitle class="d-flex align-center gap-2">
              <VIcon icon="mdi-shape-outline" />
              Performa Kategori Produk
            </VCardTitle>
            <VCardText>
              <VDataTable
                :headers="categoryHeaders"
                :items="reportData.category_performance"
                :items-per-page="10"
                class="elevation-0"
                no-data-text="Tidak ada data kategori"
              >
                <template #item.category="{ item }">
                  <div class="d-flex align-center gap-2">
                    <VIcon icon="mdi-tag" size="16" color="primary" />
                    <span class="font-weight-medium">{{ (item as any).category }}</span>
                  </div>
                </template>
                <template #item.orders_count="{ item }">
                  <VChip size="small" color="info" variant="tonal">
                    {{ (item as any).orders_count }}
                  </VChip>
                </template>
                <template #item.quantity_sold="{ item }">
                  <span class="font-weight-bold">{{ (item as any).quantity_sold }}</span>
                </template>
                <template #item.revenue="{ item }">
                  <span class="font-weight-bold text-success">{{ formatCurrency((item as any).revenue) }}</span>
                </template>
                <template #item.avg_price="{ item }">
                  <span class="text-body-2">{{ formatCurrency((item as any).avg_price) }}</span>
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
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Chart from 'chart.js/auto'

const router = useRouter()

// State
const isLoading = ref(false)
const isExporting = ref(false)
const reportData = ref<any>(null)
const todaySalesData = ref({
  total_sales: 0,
  total_orders: 0,
  avg_order_value: 0
})
const paymentMethodData = ref<any[]>([])
const orderTypeData = ref<any[]>([])
const selectedPeriod = ref('today')
const selectedYear = ref(new Date().getFullYear())
const selectedMonth = ref('')
const customStartDate = ref('')
const customEndDate = ref('')
const hourStart = ref('')
const hourEnd = ref('')
const allProductsSearch = ref('')

// Chart reference
const dailySalesChart = ref<HTMLCanvasElement>()
let chartInstance: Chart | null = null

// Options
const periodOptions = [
  { title: 'Hari Ini', value: 'today' },
  { title: 'Filter per Bulan', value: 'month' },
  { title: 'Custom Range', value: 'custom' }
]

// Generate year options (current year and 5 years back)
const currentYear = new Date().getFullYear()
const yearOptions = Array.from({ length: 6 }, (_, i) => currentYear - i)

// Generate month options based on selected year (reactive)
const monthOptions = computed(() => {
  const year = selectedYear.value
  return [
    { title: 'Januari', value: `${year}-01` },
    { title: 'Februari', value: `${year}-02` },
    { title: 'Maret', value: `${year}-03` },
    { title: 'April', value: `${year}-04` },
    { title: 'Mei', value: `${year}-05` },
    { title: 'Juni', value: `${year}-06` },
    { title: 'Juli', value: `${year}-07` },
    { title: 'Agustus', value: `${year}-08` },
    { title: 'September', value: `${year}-09` },
    { title: 'Oktober', value: `${year}-10` },
    { title: 'November', value: `${year}-11` },
    { title: 'Desember', value: `${year}-12` }
  ]
})

// Table headers
const customerHeaders = [
  { title: 'Customer', key: 'name', align: 'start' as const },
  { title: 'Total Order', key: 'orders', align: 'center' as const },
  { title: 'Total Belanja', key: 'revenue', align: 'end' as const },
  { title: 'AOV', key: 'avg_order', align: 'end' as const }
]

const dailyHeaders = [
  { title: 'Tanggal', key: 'date', align: 'start' as const },
  { title: 'Pendapatan', key: 'total', align: 'end' as const }
]

const categoryHeaders = [
  { title: 'Kategori', key: 'category', align: 'start' as const },
  { title: 'Order', key: 'orders_count', align: 'center' as const },
  { title: 'Qty Terjual', key: 'quantity_sold', align: 'center' as const },
  { title: 'Revenue', key: 'revenue', align: 'end' as const },
  { title: 'Harga Rata2', key: 'avg_price', align: 'end' as const }
]

const allProductsHeaders = [
  { title: 'No', key: 'no', align: 'start' as const, sortable: false },
  { title: 'Nama Produk', key: 'name', align: 'start' as const },
  { title: 'Jumlah Terjual', key: 'quantity', align: 'center' as const },
  { title: 'Total Pendapatan', key: 'revenue', align: 'end' as const },
  { title: 'Harga Rata-rata', key: 'avg_price', align: 'end' as const }
]

// Utility functions
const formatCurrency = (value: number | string) => {
  // If value is a formatted string with Rp, return as-is
  if (typeof value === 'string') {
    if (value.trim().startsWith('Rp')) {
      return value
    }

    const s = value.trim()
    // Smart numeric parsing that handles multiple formats (ID/EN)
    const commaCount = (s.match(/,/g) || []).length
    const dotCount = (s.match(/\./g) || []).length
    let parsed = 0

    if (commaCount > 0 && dotCount > 0) {
      // Mixed separators - determine decimal by the last separator
      if (s.lastIndexOf(',') > s.lastIndexOf('.')) {
        // Format like 1.234.567,89 -> remove dots as thousand separators, replace comma as decimal
        parsed = parseFloat(s.replace(/\./g, '').replace(/,/g, '.')) || 0
      } else {
        // Format like 1,234,567.89 -> remove commas
        parsed = parseFloat(s.replace(/,/g, '')) || 0
      }
    } else if (dotCount > 1) {
      // Many dots - assume they are thousands separators
      parsed = parseFloat(s.replace(/\./g, '')) || 0
    } else if (commaCount > 1) {
      // Many commas - assume they are thousands separators
      parsed = parseFloat(s.replace(/,/g, '')) || 0
    } else if (commaCount === 1 && dotCount === 0) {
      // single comma -> could be decimal (ID uses comma as decimal) or thousands if small numbers - treat as decimal
      parsed = parseFloat(s.replace(/,/g, '.')) || 0
    } else {
      parsed = parseFloat(s) || 0
    }

    const numeric = Math.round(parsed)
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(numeric)
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

// Analytics utility functions
const getTopHours = (hours: any[]) => {
  return hours.sort((a, b) => b.order_count - a.order_count).slice(0, 5)
}

const getHourPercentage = (orderCount: number, hours: any[]) => {
  const maxOrders = Math.max(...hours.map(h => h.order_count))
  return maxOrders > 0 ? (orderCount / maxOrders) * 100 : 0
}

const getDayPercentage = (orderCount: number, days: any[]) => {
  const maxOrders = Math.max(...days.map(d => d.order_count))
  return maxOrders > 0 ? (orderCount / maxOrders) * 100 : 0
}

const getHourIcon = (hour: number) => {
  if (hour >= 6 && hour < 12) return 'mdi-weather-sunny'
  if (hour >= 12 && hour < 18) return 'mdi-weather-partly-cloudy'
  if (hour >= 18 && hour < 22) return 'mdi-weather-night'
  return 'mdi-weather-night'
}

const getPaymentIcon = (method: string) => {
  switch (method?.toLowerCase()) {
    case 'cash':
    case 'tunai':
      return 'mdi-cash'
    case 'card':
    case 'kartu':
      return 'mdi-credit-card'
    case 'digital':
    case 'e-wallet':
      return 'mdi-cellphone'
    case 'transfer':
      return 'mdi-bank-transfer'
    default:
      return 'mdi-credit-card-outline'
  }
}

const getPaymentLabel = (method: string) => {
  switch (method?.toLowerCase()) {
    case 'cash':
      return 'Tunai'
    case 'card':
      return 'Kartu'
    case 'digital':
      return 'Digital'
    case 'transfer':
      return 'Transfer'
    default:
      return method || 'Lainnya'
  }
}

// Color mapping for payment methods
const getPaymentMethodColor = (index: number): string => {
  const colors = [
    '#1976D2', // Blue
    '#388E3C', // Green
    '#F57C00', // Orange
    '#7B1FA2', // Purple
    '#D32F2F', // Red
    '#303F9F', // Indigo
    '#00796B', // Teal
    '#F57F17', // Yellow
    '#5D4037', // Brown
    '#616161'  // Grey
  ]
  return colors[index % colors.length]
}

// Color mapping for order types
const getOrderTypeColor = (index: number): string => {
  const colors = [
    '#2E7D32', // Dark Green
    '#1565C0', // Dark Blue
    '#E65100', // Dark Orange
    '#6A1B9A', // Dark Purple
    '#C62828', // Dark Red
    '#283593', // Dark Indigo
    '#00695C', // Dark Teal
    '#F9A825', // Dark Yellow
    '#4E342E', // Dark Brown
    '#424242'  // Dark Grey
  ]
  return colors[index % colors.length]
}

// Daily sales calculation functions
const getDailyTotalSales = () => {
  // Use data from API if available
  if (reportData.value?.summary?.daily_total_sales !== undefined) {
    return reportData.value.summary.daily_total_sales
  }
  
  // Fallback to calculation from daily_sales data
  if (!reportData.value?.daily_sales || !Array.isArray(reportData.value.daily_sales)) {
    return 0
  }
  
  return reportData.value.daily_sales.reduce((total, day) => {
    return total + (parseFloat(day.revenue) || 0)
  }, 0)
}

const getDailyAverageSales = () => {
  // Use data from API if available
  if (reportData.value?.summary?.daily_average_sales !== undefined) {
    return reportData.value.summary.daily_average_sales
  }
  
  // Fallback to calculation from daily_sales data
  if (!reportData.value?.daily_sales || !Array.isArray(reportData.value.daily_sales)) {
    return 0
  }
  
  const activeDays = reportData.value.daily_sales.filter(day => parseFloat(day.revenue) > 0)
  
  if (activeDays.length === 0) {
    return 0
  }
  
  const totalSales = activeDays.reduce((total, day) => {
    return total + (parseFloat(day.revenue) || 0)
  }, 0)
  
  return totalSales / activeDays.length
}

const getTotalDaysInPeriod = () => {
  // Use data from API if available
  if (reportData.value?.summary?.active_days_count !== undefined) {
    return reportData.value.summary.active_days_count
  }
  
  // Fallback to calculation from daily_sales data
  if (!reportData.value?.daily_sales || !Array.isArray(reportData.value.daily_sales)) {
    return 0
  }
  
  return reportData.value.daily_sales.filter(day => parseFloat(day.revenue) > 0).length
}

const getSelectedPeriodLabel = () => {
  if (selectedPeriod.value === 'custom' && customStartDate.value && customEndDate.value) {
    return `${formatDate(customStartDate.value)} - ${formatDate(customEndDate.value)}`
  } else if (selectedPeriod.value === 'today') {
    // Single day label
    const today = new Date().toISOString().slice(0, 10)
    return `${formatDate(today)}`
  } else if (selectedPeriod.value === 'month' && selectedMonth.value) {
    const [year, month] = selectedMonth.value.split('-')
    const monthNames = [
      'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ]
    return `${monthNames[parseInt(month) - 1]} ${year}`
  }
  return selectedPeriod.value
}

// Event handlers
const onPeriodChange = () => {
  if (selectedPeriod.value === 'month') {
    // Set default to current month of selected year
    selectedMonth.value = `${selectedYear.value}-${String(new Date().getMonth() + 1).padStart(2, '0')}`
  } else if (selectedPeriod.value === 'today') {
    const today = new Date().toISOString().slice(0, 10)
    customStartDate.value = today
    customEndDate.value = today
  }
  loadReportData()
}

const onYearChange = () => {
  // Update month value to selected year when year changes
  const currentMonth = selectedMonth.value.split('-')[1] || '01'
  selectedMonth.value = `${selectedYear.value}-${currentMonth}`
  loadReportData()
}

const onHourChange = () => {
  // Reload report when hour filter changes (both must be filled)
  if ((hourStart.value && hourEnd.value) || (!hourStart.value && !hourEnd.value)) {
    loadReportData()
  }
}

// Returns report params based on selected filters (used by all report-related API calls)
const getReportParams = () => {
  let params: any = {}
  if (selectedPeriod.value === 'custom') {
    if (customStartDate.value && customEndDate.value) {
      params.start_date = customStartDate.value
      params.end_date = customEndDate.value
    }
  } else if (selectedPeriod.value === 'month') {
    if (selectedMonth.value) {
      params.month = selectedMonth.value
    }
  } else if (selectedPeriod.value === 'today') {
    // Set start_date and end_date both to today
    const today = new Date().toISOString().slice(0, 10)
    params.start_date = today
    params.end_date = today
  }

  if (hourStart.value && hourEnd.value) {
    params.hour_start = hourStart.value
    params.hour_end = hourEnd.value
  }
  return params
}

// Returns params mapped for the dashboard analytics endpoint
const getDashboardParams = () => {
  const params: any = {}

  // When using custom range, pass start_date and end_date
  if (selectedPeriod.value === 'custom') {
    if (customStartDate.value && customEndDate.value) {
      params.start_date = customStartDate.value
      params.end_date = customEndDate.value
    }
  } else if (selectedMonth.value) {
    // For a specific month (YYYY-MM), compute first and last day
    const [year, month] = selectedMonth.value.split('-')
    const start = new Date(Number(year), Number(month) - 1, 1)
    const end = new Date(Number(year), Number(month), 0) // Last day of month
    params.start_date = start.toISOString().slice(0, 10)
    params.end_date = end.toISOString().slice(0, 10)
  }

  if (hourStart.value && hourEnd.value) {
    params.hour_start = hourStart.value
    params.hour_end = hourEnd.value
  }

  return params
}

// Load report data
const loadReportData = async () => {
  if (isLoading.value) return
  
  isLoading.value = true
  
    try {
    const params: any = getReportParams()
    // Validate filter inputs: if custom, ensure both dates are provided; if month, ensure month is selected
    if (selectedPeriod.value === 'custom' && (!customStartDate.value || !customEndDate.value)) {
      isLoading.value = false
      return
    }
    if (selectedPeriod.value === 'month' && !selectedMonth.value) {
      isLoading.value = false
      return
    }
    
    const response = await axios.get('/api/reports/sales', { params })
    
      if (response.data.success) {
      reportData.value = response.data.data

      // Ensure the 'Penjualan Hari Ini' card reflects currently selected filters (date + hour)
      // Always call loadTodaySales with the current report params so the card will display totals
      // for the selected range/time instead of the default calendar day.
  const filteredParams = getDashboardParams()
      // If reportData returned a summary, set it first for a quick render.
      if (reportData.value && reportData.value.summary) {
        todaySalesData.value = {
          total_sales: reportData.value.summary.total_revenue || 0,
          total_orders: reportData.value.summary.total_orders || 0,
          avg_order_value: reportData.value.summary.average_order || 0
        }
      }
      // Always re-query the sales endpoint with the same params to ensure the today's sales card
      // is correctly calculated and consistent with filters (especially for time-of-day filter)
      await loadTodaySales(filteredParams)
      
  // Load analytics data (use same filter params)
  const analyticsParams = getReportParams()
  await loadPaymentMethodAnalytics(analyticsParams)
  await loadOrderTypeAnalytics(analyticsParams)
      
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

  // Load today's sales data (keep behavior aligned with Dashboard 'today_sales')
  const loadTodaySales = async (params: any = {}) => {
  try {
    // For better parity with the Dashboard behavior, call the dashboard endpoint
    // which returns a dedicated `today_sales` and `today_orders` in summary.
    const response = await axios.get('/api/dashboard/analytics', { params })
    if (response.data.success) {
      const summary = response.data.data.summary || {}
      // dashboard summary contains today_sales.value and today_orders.value
      const today = summary.today_sales || {}
      const todayOrders = summary.today_orders || {}
      todaySalesData.value = {
        total_sales: today.value || 0,
        total_orders: todayOrders.value || 0,
        avg_order_value: (today.value && todayOrders.value) ? Math.round(today.value / Math.max(1, todayOrders.value)) : 0
      }
      return
    }
  } catch (error) {
    console.error('Error loading today sales:', error)
    // Set default values on error
    todaySalesData.value = {
      total_sales: 0,
      total_orders: 0,
      avg_order_value: 0
    }
  }
}

// Load payment method analytics
const loadPaymentMethodAnalytics = async (params: any = {}) => {
  try {
    const response = await axios.get('/api/reports/payment-methods', { params })
    console.log('Payment Methods Response:', response.data)
    
    if (response.data.success) {
      paymentMethodData.value = response.data.data.data || []
    } else {
      paymentMethodData.value = []
    }
  } catch (error) {
    console.error('Error loading payment method analytics:', error)
    paymentMethodData.value = []
  }
}

// Load order type analytics  
const loadOrderTypeAnalytics = async (params: any = {}) => {
  try {
    const response = await axios.get('/api/reports/order-types', { params })
    console.log('Order Types Response:', response.data)
    
    if (response.data.success) {
      orderTypeData.value = response.data.data.data || []
    } else {
      orderTypeData.value = []
    }
  } catch (error) {
    console.error('Error loading order type analytics:', error)
    orderTypeData.value = []
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
    // Handle backend formatted currency properly
    if (typeof item.total === 'string' && item.total.includes('Rp')) {
      // Extract all digits from "Rp 123.456" format
      const digitsOnly = item.total.replace(/\D/g, '') // Remove all non-digits
      return parseInt(digitsOnly, 10) || 0
    }
    // If it's already a number
    return typeof item.total === 'number' ? item.total : 0
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
  
  // Helper function to properly format currency for Excel
  const formatExcelCurrency = (value: any): string => {
    if (!value && value !== 0) return 'Rp 0'
    
    let numValue = 0
    
    try {
      // If it's already a formatted string with Rp (dari backend formatRupiah)
      if (typeof value === 'string' && value.includes('Rp')) {
        // Backend formatRupiah menggunakan format "Rp 123.456" dengan titik sebagai pemisah ribuan
        // Extract semua digit saja
        const digitsOnly = value.replace(/\D/g, '') // Remove all non-digits
        numValue = parseInt(digitsOnly, 10)
      } 
      // If it's a regular string number
      else if (typeof value === 'string') {
        // Keep decimal point, only remove other non-numeric chars except dot
        numValue = parseFloat(value.replace(/[^\d.]/g, ''))
      }
      // If it's already a number
      else if (typeof value === 'number') {
        numValue = value
      }
      
      // Validate the number
      if (isNaN(numValue) || !isFinite(numValue) || numValue < 0) {
        numValue = 0
      }
      
      // Format dengan pemisah ribuan Indonesia (titik)
      const formatted = `Rp ${numValue.toLocaleString('id-ID').replace(/,/g, '.')}`
      return formatted
      
    } catch (error) {
      return 'Rp 0'
    }
  }
  
  try {
    // Dynamic import for better performance
    const XLSX = await import('xlsx')
    
    // Fetch transaction history data using FAST RAW SQL endpoint
    let transactionHistory: any[] = []
    try {
      let params: any = {}
      
      if (selectedPeriod.value === 'custom') {
        if (customStartDate.value && customEndDate.value) {
          params.start_date = customStartDate.value
          params.end_date = customEndDate.value
        }
      } else {
        if (selectedMonth.value) {
          params.month = selectedMonth.value
        }
      }
      
      // Add hour filter if provided
      if (hourStart.value && hourEnd.value) {
        params.hour_start = hourStart.value
        params.hour_end = hourEnd.value
      }
      
      // Set reasonable limit
      params.per_page = 500
      
      console.log('🚀 Fetching transaction history (FAST MODE) with params:', params)
      
      const startTime = performance.now()
      
      // Use new fast endpoint with raw SQL
      const transactionResponse = await axios.get('/api/pos/orders/history/export', { 
        params,
        timeout: 15000 // 15 seconds should be enough now
      })
      
      const loadTime = Math.round(performance.now() - startTime)
      console.log(`⚡ Transaction data loaded in ${loadTime}ms`)
      console.log('Transaction response:', transactionResponse.data)
      
      if (transactionResponse.data && transactionResponse.data.data) {
        transactionHistory = Array.isArray(transactionResponse.data.data) 
          ? transactionResponse.data.data 
          : []
      }
      
      console.log(`✅ Fetched ${transactionHistory.length} transactions for export`)
    } catch (error: any) {
      console.error('❌ Failed to fetch transaction history:', error)
      if (error.code === 'ECONNABORTED') {
        console.warn('⏱️ Request timeout')
      }
      // Continue with empty data instead of failing the entire export
      transactionHistory = []
    }
    
    // Get current period info
    const periodInfo = selectedPeriod.value === 'custom' 
      ? `${customStartDate.value} s/d ${customEndDate.value}`
      : selectedPeriod.value === 'month' 
        ? `Bulan ${selectedMonth.value}`
        : `Hari Ini (${new Date().toLocaleDateString('id-ID')})`
    
    // Prepare Summary Data with Enhanced Analytics
    const summaryData = [
      ['LAPORAN PENJUALAN ENHANCED'],
      ['Periode:', periodInfo],
      ['Tanggal Export:', new Date().toLocaleDateString('id-ID', { 
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
      })],
      [],
      ['RINGKASAN PENJUALAN'],
      ['Total Order:', reportData.value.summary.total_orders],
      ['Total Pendapatan:', formatExcelCurrency(reportData.value.summary.total_revenue)],
      ['Rata-rata Order (AOV):', formatExcelCurrency(reportData.value.summary.average_order)],
      ['Total Item Terjual:', reportData.value.summary.total_items],
      ['Order Selesai:', reportData.value.summary.completed_orders],
      ['Order Dibatalkan:', reportData.value.summary.cancelled_orders],
      reportData.value.summary.growth_percentage !== undefined ? ['Growth vs Periode Sebelumnya:', `${reportData.value.summary.growth_percentage}%`] : [],
      [],
      ['ANALISIS CUSTOMER'],
      ['Customer Baru:', reportData.value.customer_behavior?.new_customers || 0],
      ['Returning Customer:', reportData.value.customer_behavior?.returning_customers || 0],
      ['One-time Customer:', reportData.value.customer_behavior?.one_time_customers || 0],
      ['Retention Rate:', `${reportData.value.customer_behavior?.retention_rate || 0}%`],
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
          formatExcelCurrency(item.total)
        ])
      ]
      
      const dailySheet = XLSX.utils.aoa_to_sheet(dailySalesData)
      dailySheet['!cols'] = [{ wch: 15 }, { wch: 20 }]
      XLSX.utils.book_append_sheet(workbook, dailySheet, 'Penjualan Harian')
    }
    
    // 3. Top Products Sheet (Top 20)
    if (reportData.value.top_products && reportData.value.top_products.length > 0) {
      const topProductsData = [
        ['TOP 20 PRODUK TERLARIS'],
        ['Periode:', periodInfo],
        [],
        ['No', 'Nama Produk', 'Jumlah Terjual', 'Total Pendapatan', 'Harga Rata-rata'],
        ...reportData.value.top_products.map((item: any, index: number) => [
          index + 1,
          item.name,
          item.quantity,
          formatExcelCurrency(item.revenue),
          item.avg_price || '-'
        ])
      ]

      const productsSheet = XLSX.utils.aoa_to_sheet(topProductsData)
      productsSheet['!cols'] = [{ wch: 5 }, { wch: 30 }, { wch: 15 }, { wch: 20 }, { wch: 18 }]
      XLSX.utils.book_append_sheet(workbook, productsSheet, 'Top 20 Produk')
    }

    // 3b. ALL Products Sheet (Semua Produk Terjual)
    if (reportData.value.all_products && reportData.value.all_products.length > 0) {
      const allProductsData = [
        ['SEMUA PRODUK TERJUAL'],
        ['Periode:', periodInfo],
        ['Total Produk:', reportData.value.all_products.length],
        [],
        ['No', 'Nama Produk', 'Jumlah Terjual', 'Total Pendapatan', 'Harga Rata-rata'],
        ...reportData.value.all_products.map((item: any, index: number) => [
          index + 1,
          item.name,
          item.quantity,
          formatExcelCurrency(item.revenue),
          item.avg_price || '-'
        ])
      ]

      const allProductsSheet = XLSX.utils.aoa_to_sheet(allProductsData)
      allProductsSheet['!cols'] = [{ wch: 5 }, { wch: 30 }, { wch: 15 }, { wch: 20 }, { wch: 18 }]
      XLSX.utils.book_append_sheet(workbook, allProductsSheet, 'Semua Produk')
    }
    
    // 4. Enhanced Top Customers Sheet
    if (reportData.value.top_customers && reportData.value.top_customers.length > 0) {
      const topCustomersData = [
        ['CUSTOMER TERBAIK - ANALISIS LENGKAP'],
        ['Periode:', periodInfo],
        [],
        ['No', 'Nama Customer', 'Jumlah Order', 'Total Belanja', 'AOV', 'Customer Lifetime (hari)'],
        ...reportData.value.top_customers.map((item: any, index: number) => [
          index + 1,
          item.name,
          item.orders,
          formatExcelCurrency(item.revenue),
          formatExcelCurrency(item.avg_order),
          item.customer_lifetime || 0
        ])
      ]
      
      const customersSheet = XLSX.utils.aoa_to_sheet(topCustomersData)
      customersSheet['!cols'] = [{ wch: 5 }, { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 15 }, { wch: 20 }]
      XLSX.utils.book_append_sheet(workbook, customersSheet, 'Customer Analytics')
    }

    // 5. Peak Hours Analysis Sheet
    if (reportData.value.peak_hours && reportData.value.peak_hours.length > 0) {
      const peakHoursData = [
        ['ANALISIS JAM RAMAI'],
        ['Periode:', periodInfo],
        [],
        ['Jam', 'Jumlah Order', 'Total Revenue', 'AOV'],
        ...reportData.value.peak_hours.map((item: any) => [
          item.hour_display,
          item.order_count,
          formatExcelCurrency(item.revenue),
          formatExcelCurrency(item.avg_order)
        ])
      ]
      
      const hoursSheet = XLSX.utils.aoa_to_sheet(peakHoursData)
      hoursSheet['!cols'] = [{ wch: 10 }, { wch: 15 }, { wch: 20 }, { wch: 15 }]
      XLSX.utils.book_append_sheet(workbook, hoursSheet, 'Peak Hours')
    }

    // 6. Sales by Day of Week Sheet
    if (reportData.value.sales_by_day && reportData.value.sales_by_day.length > 0) {
      const dayAnalysisData = [
        ['ANALISIS PENJUALAN PER HARI'],
        ['Periode:', periodInfo],
        [],
        ['Hari', 'Jumlah Order', 'Total Revenue', 'AOV'],
        ...reportData.value.sales_by_day.map((item: any) => [
          item.day_name,
          item.order_count,
          formatExcelCurrency(item.revenue),
          formatExcelCurrency(item.avg_order)
        ])
      ]
      
      const daysSheet = XLSX.utils.aoa_to_sheet(dayAnalysisData)
      daysSheet['!cols'] = [{ wch: 15 }, { wch: 15 }, { wch: 20 }, { wch: 15 }]
      XLSX.utils.book_append_sheet(workbook, daysSheet, 'Sales by Day')
    }

    // 7. Payment Methods Analysis Sheet
    if (reportData.value.payment_methods && reportData.value.payment_methods.length > 0) {
      const paymentData = [
        ['ANALISIS METODE PEMBAYARAN'],
        ['Periode:', periodInfo],
        [],
        ['Metode', 'Jumlah Order', 'Total Revenue', 'AOV', 'Persentase'],
        ...reportData.value.payment_methods.map((item: any) => [
          getPaymentLabel(item.method),
          item.order_count,
          formatExcelCurrency(item.revenue),
          formatExcelCurrency(item.avg_order),
          `${item.percentage}%`
        ])
      ]
      
      const paymentSheet = XLSX.utils.aoa_to_sheet(paymentData)
      paymentSheet['!cols'] = [{ wch: 15 }, { wch: 15 }, { wch: 20 }, { wch: 15 }, { wch: 12 }]
      XLSX.utils.book_append_sheet(workbook, paymentSheet, 'Payment Methods')
    }

    // 8. Category Performance Sheet
    if (reportData.value.category_performance && reportData.value.category_performance.length > 0) {
      const categoryData = [
        ['PERFORMA KATEGORI PRODUK'],
        ['Periode:', periodInfo],
        [],
        ['Kategori', 'Order dengan Kategori', 'Qty Terjual', 'Total Revenue', 'Harga Rata-rata'],
        ...reportData.value.category_performance.map((item: any) => [
          item.category,
          item.orders_count,
          item.quantity_sold,
          formatExcelCurrency(item.revenue),
          formatExcelCurrency(item.avg_price)
        ])
      ]
      
      const categorySheet = XLSX.utils.aoa_to_sheet(categoryData)
      categorySheet['!cols'] = [{ wch: 20 }, { wch: 18 }, { wch: 15 }, { wch: 20 }, { wch: 18 }]
      XLSX.utils.book_append_sheet(workbook, categorySheet, 'Category Performance')
    }

    // 9. Transaction History Sheet - ALWAYS ADD THIS SHEET
    console.log('📊 Creating Transaction History Sheet with', transactionHistory.length, 'transactions')
    
    // Prepare header - base columns + dynamic item columns
    const baseHeaders = ['No', 'Order ID', 'Tanggal', 'Jam', 'Customer', 'Table', 'Tipe Order', 'Total', 'Pembayaran', 'Status']
    
    // Find max number of items in any order to determine columns needed
    let maxItems = 0
    if (transactionHistory && transactionHistory.length > 0) {
      transactionHistory.forEach((order: any) => {
        const itemCount = order.items?.length || 0
        if (itemCount > maxItems) maxItems = itemCount
      })
    }
    
    // Add item columns (Item 1, Item 2, Item 3, ...)
    const itemHeaders = []
    for (let i = 1; i <= maxItems; i++) {
      itemHeaders.push(`Item ${i}`)
    }
    
    const fullHeaders = [...baseHeaders, ...itemHeaders]
    
    const transactionData = [
      ['RIWAYAT TRANSAKSI'],
      ['Periode:', periodInfo],
      [],
      fullHeaders
    ]
    
    if (transactionHistory && transactionHistory.length > 0) {
      transactionHistory.forEach((order: any, index: number) => {
        try {
          // Use order_date from cache table, fallback to created_at if not available
          const dateTimeStr = order.order_date || order.created_at
          const orderDate = new Date(dateTimeStr)
          const dateStr = orderDate.toLocaleDateString('id-ID')
          const timeStr = order.order_time
            ? new Date(`2000-01-01 ${order.order_time}`).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
            : orderDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
          
          // Get customer name (from raw SQL)
          const customerName = order.customer_name || 'Guest'
          
          // Get table number
          const tableNumber = order.table_number || '-'
          
          // Get order type
          let orderType = 'Dine In'
          if (order.order_type === 'takeaway') orderType = 'Takeaway'
          else if (order.order_type === 'delivery') orderType = 'Delivery'
          
          // Get payment method (from raw SQL - comes as comma-separated string)
          let paymentMethod = 'Cash'
          if (order.payment_methods) {
            const methods = order.payment_methods.split(',')
            const method = methods[0] // Use first payment method
            if (method === 'qris') paymentMethod = 'QRIS'
            else if (method === 'debit') paymentMethod = 'Debit Card'
            else if (method === 'credit') paymentMethod = 'Credit Card'
            else if (method === 'transfer') paymentMethod = 'Transfer Bank'
          }
          
          // Get status
          let status = order.status || 'pending'
          if (status === 'completed') status = 'Selesai'
          else if (status === 'cancelled') status = 'Dibatalkan'
          else if (status === 'pending') status = 'Pending'
          else if (status === 'processing') status = 'Diproses'
          
          // Build base row
          const rowData = [
            index + 1,
            order.order_number || order.id_order || order.id,
            dateStr,
            timeStr,
            customerName,
            tableNumber,
            orderType,
            formatExcelCurrency(order.total_amount || order.total),
            paymentMethod,
            status
          ]
          
          // Add items (spread horizontally)
          const items = order.items || []
          for (let i = 0; i < maxItems; i++) {
            if (i < items.length) {
              const item = items[i]
              const itemText = `${item.name} (${item.quantity}x)`
              rowData.push(itemText)
            } else {
              rowData.push('') // Empty cell if no item
            }
          }
          
          transactionData.push(rowData)
        } catch (error) {
          console.error('Error processing order:', order, error)
        }
      })
    } else {
      // Add empty row with message if no data
      transactionData.push(['', 'Tidak ada data transaksi untuk periode ini'])
    }
    
    const transactionSheet = XLSX.utils.aoa_to_sheet(transactionData)
    
    // Set column widths
    const colWidths = [
      { wch: 5 },  // No
      { wch: 12 }, // Order ID
      { wch: 12 }, // Tanggal
      { wch: 8 },  // Jam
      { wch: 20 }, // Customer
      { wch: 10 }, // Table
      { wch: 12 }, // Tipe Order
      { wch: 18 }, // Total
      { wch: 15 }, // Pembayaran
      { wch: 12 }  // Status
    ]
    
    // Add width for item columns
    for (let i = 0; i < maxItems; i++) {
      colWidths.push({ wch: 30 }) // 30 characters wide for each item
    }
    
    transactionSheet['!cols'] = colWidths
    XLSX.utils.book_append_sheet(workbook, transactionSheet, 'Riwayat Transaksi')
    console.log('✅ Transaction History Sheet added to workbook')

    // 10. Business Insights Sheet
    const insightsData = [
      ['BUSINESS INSIGHTS & RECOMMENDATIONS'],
      ['Periode:', periodInfo],
      [],
      ['RINGKASAN PERFORMA'],
      ['Growth Rate:', reportData.value.summary.growth_percentage !== undefined ? `${reportData.value.summary.growth_percentage}%` : 'N/A'],
      ['Customer Retention:', `${reportData.value.customer_behavior?.retention_rate || 0}%`],
      [],
      ['TOP PERFORMERS'],
      ['Jam Tersibuk:', reportData.value.peak_hours && reportData.value.peak_hours.length > 0 ? 
        getTopHours(reportData.value.peak_hours)[0]?.hour_display : 'N/A'],
      ['Hari Terbaik:', reportData.value.sales_by_day && reportData.value.sales_by_day.length > 0 ? 
        reportData.value.sales_by_day.sort((a: any, b: any) => b.order_count - a.order_count)[0]?.day_name : 'N/A'],
      ['Metode Bayar Favorit:', reportData.value.payment_methods && reportData.value.payment_methods.length > 0 ? 
        getPaymentLabel(reportData.value.payment_methods[0]?.method) : 'N/A'],
      [],
      ['REKOMENDASI BISNIS'],
      ['1. Optimasi Operasional:', 'Focus staff scheduling pada jam dan hari ramai'],
      ['2. Customer Retention:', 'Implementasi loyalty program untuk meningkatkan retention'],
      ['3. Product Strategy:', 'Push produk top-selling dan evaluasi slow-moving items'],
      ['4. Payment Strategy:', 'Optimalkan metode pembayaran yang paling disukai customer'],
      ['5. Marketing Time:', 'Jalankan promosi saat jam dan hari dengan traffic rendah']
    ]

    const insightsSheet = XLSX.utils.aoa_to_sheet(insightsData)
    insightsSheet['!cols'] = [{ wch: 25 }, { wch: 50 }]
    XLSX.utils.book_append_sheet(workbook, insightsSheet, 'Business Insights')
    
    // Generate filename with timestamp
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')
    const filename = `laporan-penjualan-enhanced-analytics-${timestamp}.xlsx`
    
    // Download file
    XLSX.writeFile(workbook, filename)
    
    console.log('✅ Enhanced Sales Analytics report exported successfully:', filename)
    
  } catch (error) {
    console.error('Error exporting sales report:', error)
  } finally {
    isExporting.value = false
  }
}

// Lifecycle
onMounted(() => {
  const now = new Date()
  const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  // If default period is today, set customStartDate/customEndDate to today to reflect that
  if (selectedPeriod.value === 'today') {
    const today = now.toISOString().slice(0, 10)
    customStartDate.value = today
    customEndDate.value = today
  } else if (selectedPeriod.value === 'month') {
    selectedMonth.value = currentMonth
  }
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
