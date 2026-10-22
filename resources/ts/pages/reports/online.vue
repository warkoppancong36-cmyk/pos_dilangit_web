<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  name: reports-online
</route>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Chart from 'chart.js/auto'

interface PlatformSummary {
  platform: string
  label: string
  order_count: number
  total_revenue: number
  average_order_value: number
  revenue_percentage: number
}

interface DailyRow {
  date: string
  label: string
  gofood: number
  grabfood: number
  shopeefood: number
  total: number
  order_count: number
}

interface TopProduct {
  name: string
  quantity: number
  revenue: number
}

interface OnlineSalesReport {
  summary: {
    total_orders: number
    total_revenue: number
    average_order_value: number
    all_sales_revenue: number
    online_share_percentage: number
  }
  platforms: PlatformSummary[]
  daily: DailyRow[]
  top_products: Record<string, TopProduct[]>
  start_date: string
  end_date: string
  period_formatted: string
}

const router = useRouter()

const PLATFORM_COLORS: Record<string, string> = {
  gofood: '#E82529',
  grabfood: '#00B14F',
  shopeefood: '#F57C00',
}

// Tanggal lokal (WIB), bukan toISOString() yang memakai UTC dan bisa
// menghasilkan tanggal kemarin sebelum jam 07.00.
const toLocalDate = (date: Date) => {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')

  return `${y}-${m}-${d}`
}

const now = new Date()
const selectedPeriod = ref<'today' | 'month' | 'custom'>('month')
const selectedYear = ref(now.getFullYear())
const selectedMonth = ref(`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`)
const customStartDate = ref('')
const customEndDate = ref('')

const periodOptions = [
  { title: 'Hari Ini', value: 'today' },
  { title: 'Filter per Bulan', value: 'month' },
  { title: 'Custom Range', value: 'custom' },
]

const yearOptions = Array.from({ length: 6 }, (_, i) => now.getFullYear() - i)

const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

const monthOptions = computed(() =>
  monthNames.map((title, index) => ({
    title,
    value: `${selectedYear.value}-${String(index + 1).padStart(2, '0')}`,
  })),
)

const report = ref<OnlineSalesReport | null>(null)
const isLoading = ref(false)
const isExporting = ref(false)
const errorMessage = ref('')
const dailyChartCanvas = ref<HTMLCanvasElement | null>(null)
let dailyChart: Chart | null = null

const formatCurrency = (value: number) =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value || 0)

const formatPercent = (value: number) => `${(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`

const platformColor = (platform: string) => PLATFORM_COLORS[platform] || '#757575'

const summaryCards = computed(() => {
  if (!report.value)
    return []
  const summary = report.value.summary

  return [
    {
      title: 'Total Penjualan Online',
      value: formatCurrency(summary.total_revenue),
      icon: 'mdi-cash-multiple',
      color: 'success',
      caption: '',
    },
    {
      title: 'Jumlah Order',
      value: summary.total_orders.toLocaleString('id-ID'),
      icon: 'mdi-receipt-text-outline',
      color: 'primary',
      caption: '',
    },
    {
      title: 'Rata-rata per Order',
      value: formatCurrency(summary.average_order_value),
      icon: 'mdi-scale-balance',
      color: 'info',
      caption: '',
    },
    {
      title: 'Porsi dari Semua Penjualan',
      value: formatPercent(summary.online_share_percentage),
      icon: 'mdi-chart-pie',
      color: 'warning',
      caption: `dari total ${formatCurrency(summary.all_sales_revenue)}`,
    },
  ]
})

const getParams = () => {
  if (selectedPeriod.value === 'today') {
    const today = toLocalDate(new Date())

    return { start_date: today, end_date: today }
  }
  if (selectedPeriod.value === 'custom') {
    if (customStartDate.value && customEndDate.value)
      return { start_date: customStartDate.value, end_date: customEndDate.value }

    return null
  }

  return { month: selectedMonth.value }
}

const renderDailyChart = async () => {
  await nextTick()
  dailyChart?.destroy()
  dailyChart = null

  if (!report.value || !dailyChartCanvas.value || report.value.summary.total_orders === 0)
    return

  const daily = report.value.daily

  dailyChart = new Chart(dailyChartCanvas.value, {
    type: 'bar',
    data: {
      labels: daily.map(row => row.label),
      datasets: report.value.platforms.map(platform => ({
        label: platform.label,
        data: daily.map(row => Number(row[platform.platform as 'gofood' | 'grabfood' | 'shopeefood'] || 0)),
        backgroundColor: platformColor(platform.platform),
        stack: 'online',
      })),
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: { stacked: true },
        y: {
          stacked: true,
          ticks: {
            callback: value => formatCurrency(Number(value)),
          },
        },
      },
      plugins: {
        tooltip: {
          callbacks: {
            label: context => `${context.dataset.label}: ${formatCurrency(Number(context.raw))}`,
          },
        },
      },
    },
  })
}

const loadReport = async () => {
  const params = getParams()
  if (!params)
    return

  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await axios.get('/api/reports/online-sales', { params })
    const data = response.data?.data

    // Server lama (rute belum ada) mengembalikan halaman HTML dengan status
    // 200, bukan data laporan. Tampilkan pesan, jangan halaman kosong.
    if (!data || typeof data !== 'object' || !data.summary) {
      report.value = null
      errorMessage.value = 'Server belum menyediakan laporan penjualan online. Pastikan backend terbaru sudah di-deploy dan cache rute diperbarui (php artisan optimize).'

      return
    }

    report.value = data
    await renderDailyChart()
  }
  catch (error: any) {
    report.value = null
    errorMessage.value = error?.response?.data?.message || 'Gagal memuat laporan penjualan online.'
  }
  finally {
    isLoading.value = false
  }
}

const exportToExcel = async () => {
  if (!report.value)
    return

  isExporting.value = true
  try {
    const XLSX = await import('xlsx')
    const data = report.value
    const workbook = XLSX.utils.book_new()

    const summarySheet = XLSX.utils.aoa_to_sheet([
      ['Laporan Penjualan Online'],
      ['Periode', data.period_formatted],
      [],
      ['Total Penjualan Online', data.summary.total_revenue],
      ['Jumlah Order', data.summary.total_orders],
      ['Rata-rata per Order', data.summary.average_order_value],
      ['Total Semua Penjualan', data.summary.all_sales_revenue],
      ['Porsi Online (%)', data.summary.online_share_percentage],
      [],
      ['Platform', 'Jumlah Order', 'Omzet', 'Rata-rata per Order', 'Porsi dari Online (%)'],
      ...data.platforms.map(p => [p.label, p.order_count, p.total_revenue, p.average_order_value, p.revenue_percentage]),
    ])

    XLSX.utils.book_append_sheet(workbook, summarySheet, 'Ringkasan')

    const dailySheet = XLSX.utils.aoa_to_sheet([
      ['Tanggal', ...data.platforms.map(p => p.label), 'Total', 'Jumlah Order'],
      ...data.daily.map(row => [
        row.date,
        ...data.platforms.map(p => Number(row[p.platform as 'gofood' | 'grabfood' | 'shopeefood'] || 0)),
        row.total,
        row.order_count,
      ]),
    ])

    XLSX.utils.book_append_sheet(workbook, dailySheet, 'Harian')

    const topRows: (string | number)[][] = [['Platform', 'Menu', 'Porsi', 'Omzet']]

    data.platforms.forEach(platform => {
      (data.top_products[platform.platform] || []).forEach(item => {
        topRows.push([platform.label, item.name, item.quantity, item.revenue])
      })
    })
    XLSX.utils.book_append_sheet(workbook, XLSX.utils.aoa_to_sheet(topRows), 'Menu Terlaris')

    XLSX.writeFile(workbook, `penjualan-online-${data.start_date}-sd-${data.end_date}.xlsx`)
  }
  finally {
    isExporting.value = false
  }
}

onMounted(loadReport)
onBeforeUnmount(() => dailyChart?.destroy())
</script>

<template>
  <div class="online-report-page">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center mb-6 ga-4">
      <div>
        <div class="d-flex align-center mb-2">
          <VBtn
            icon="mdi-arrow-left"
            variant="text"
            size="small"
            class="me-2"
            @click="router.back()"
          />
          <h1 class="text-h4 font-weight-bold">
            Laporan Penjualan Online
          </h1>
        </div>
        <p class="text-body-1 text-medium-emphasis">
          Penjualan lewat GoFood, GrabFood, dan ShopeeFood
          <span v-if="report"> · {{ report.period_formatted }}</span>
        </p>
      </div>

      <VBtn
        color="success"
        prepend-icon="mdi-file-excel"
        :loading="isExporting"
        :disabled="!report"
        @click="exportToExcel"
      >
        Export Excel
      </VBtn>
    </div>

    <!-- Filter -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            md="3"
          >
            <VSelect
              v-model="selectedPeriod"
              :items="periodOptions"
              item-title="title"
              item-value="value"
              label="Pilih Periode"
              variant="outlined"
              density="compact"
              @update:model-value="loadReport"
            />
          </VCol>

          <template v-if="selectedPeriod === 'month'">
            <VCol
              cols="12"
              md="2"
            >
              <VSelect
                v-model="selectedYear"
                :items="yearOptions"
                label="Tahun"
                variant="outlined"
                density="compact"
                @update:model-value="loadReport"
              />
            </VCol>
            <VCol
              cols="12"
              md="3"
            >
              <VSelect
                v-model="selectedMonth"
                :items="monthOptions"
                item-title="title"
                item-value="value"
                label="Pilih Bulan"
                variant="outlined"
                density="compact"
                @update:model-value="loadReport"
              />
            </VCol>
          </template>

          <template v-if="selectedPeriod === 'custom'">
            <VCol
              cols="12"
              md="3"
            >
              <AppDateTimePicker
                v-model="customStartDate"
                label="Tanggal Mulai"
                placeholder="Pilih tanggal"
                density="compact"
                @update:model-value="loadReport"
              />
            </VCol>
            <VCol
              cols="12"
              md="3"
            >
              <AppDateTimePicker
                v-model="customEndDate"
                label="Tanggal Akhir"
                placeholder="Pilih tanggal"
                density="compact"
                @update:model-value="loadReport"
              />
            </VCol>
          </template>

          <VCol
            cols="12"
            md="2"
          >
            <VBtn
              color="primary"
              block
              prepend-icon="mdi-refresh"
              :loading="isLoading"
              @click="loadReport"
            >
              Muat Ulang
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-6"
    >
      {{ errorMessage }}
    </VAlert>

    <template v-if="report">
      <!-- Ringkasan -->
      <VRow class="mb-2">
        <VCol
          v-for="card in summaryCards"
          :key="card.title"
          cols="6"
          md="3"
        >
          <VCard class="h-100">
            <VCardText>
              <div class="d-flex align-center mb-2">
                <VIcon
                  :icon="card.icon"
                  :color="card.color"
                  class="me-2"
                />
                <span class="text-body-2 text-medium-emphasis">{{ card.title }}</span>
              </div>
              <div class="text-h5 font-weight-bold">
                {{ card.value }}
              </div>
              <div
                v-if="card.caption"
                class="text-caption text-medium-emphasis mt-1"
              >
                {{ card.caption }}
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Per platform -->
      <VRow class="mb-2">
        <VCol
          v-for="platform in report.platforms"
          :key="platform.platform"
          cols="12"
          md="4"
        >
          <VCard
            class="h-100 platform-card"
            :style="{ borderTopColor: platformColor(platform.platform) }"
          >
            <VCardText>
              <div class="d-flex justify-space-between align-center mb-3">
                <span
                  class="text-h6 font-weight-bold"
                  :style="{ color: platformColor(platform.platform) }"
                >
                  {{ platform.label }}
                </span>
                <VChip
                  size="small"
                  variant="tonal"
                >
                  {{ formatPercent(platform.revenue_percentage) }} dari online
                </VChip>
              </div>
              <div class="text-h5 font-weight-bold mb-1">
                {{ formatCurrency(platform.total_revenue) }}
              </div>
              <div class="text-body-2 text-medium-emphasis">
                {{ platform.order_count }} order · rata-rata {{ formatCurrency(platform.average_order_value) }}
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Tren harian -->
      <VCard class="mb-6">
        <VCardTitle class="d-flex align-center">
          <VIcon
            icon="mdi-chart-bar-stacked"
            class="me-2"
          />
          Tren Harian
        </VCardTitle>
        <VCardText>
          <div
            v-if="report.summary.total_orders === 0"
            class="text-center text-medium-emphasis py-8"
          >
            Belum ada penjualan online di periode ini.
          </div>
          <div
            v-else
            class="chart-wrapper"
          >
            <canvas ref="dailyChartCanvas" />
          </div>
        </VCardText>
      </VCard>

      <!-- Menu terlaris per platform -->
      <VRow>
        <VCol
          v-for="platform in report.platforms"
          :key="`top-${platform.platform}`"
          cols="12"
          md="4"
        >
          <VCard class="h-100">
            <VCardTitle :style="{ color: platformColor(platform.platform) }">
              Menu Terlaris {{ platform.label }}
            </VCardTitle>
            <VCardText>
              <VTable
                v-if="(report.top_products[platform.platform] || []).length > 0"
                density="compact"
              >
                <thead>
                  <tr>
                    <th>Menu</th>
                    <th class="text-center">
                      Porsi
                    </th>
                    <th class="text-end">
                      Omzet
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="item in report.top_products[platform.platform]"
                    :key="item.name"
                  >
                    <td>{{ item.name }}</td>
                    <td class="text-center">
                      {{ item.quantity }}
                    </td>
                    <td class="text-end">
                      {{ formatCurrency(item.revenue) }}
                    </td>
                  </tr>
                </tbody>
              </VTable>
              <div
                v-else
                class="text-center text-medium-emphasis py-6"
              >
                Belum ada penjualan.
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <div
      v-else-if="isLoading"
      class="text-center py-12"
    >
      <VProgressCircular
        indeterminate
        color="primary"
      />
    </div>
  </div>
</template>

<style scoped>
.platform-card {
  border-top: 4px solid transparent;
}

.chart-wrapper {
  position: relative;
  height: 320px;
}
</style>
