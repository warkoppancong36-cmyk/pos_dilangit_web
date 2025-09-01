<template>
  <div class="promotion-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Kelola Promosi</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola promosi dan penawaran khusus untuk coffee shop Anda</p>
      </div>
      <div class="d-flex gap-3 align-center">
        <VBtn
          color="primary"
          prepend-icon="mdi-plus"
          @click="openCreateDialog"
        >
          Tambah Promosi
        </VBtn>
      </div>
    </div>

    <!-- Statistics Cards -->
    <VRow class="mb-6">
      <VCol cols="12" md="3">
        <VCard color="primary" variant="tonal">
          <VCardText class="text-center">
            <div class="text-h3 font-weight-bold">{{ stats?.total_promotions || 0 }}</div>
            <div class="text-body-2 text-medium-emphasis">Total Promosi</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3">
        <VCard color="success" variant="tonal">
          <VCardText class="text-center">
            <div class="text-h3 font-weight-bold">{{ stats?.active_promotions || 0 }}</div>
            <div class="text-body-2 text-medium-emphasis">Aktif</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3">
        <VCard color="warning" variant="tonal">
          <VCardText class="text-center">
            <div class="text-h3 font-weight-bold">{{ stats?.scheduled_promotions || 0 }}</div>
            <div class="text-body-2 text-medium-emphasis">Terjadwal</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3">
        <VCard color="error" variant="tonal">
          <VCardText class="text-center">
            <div class="text-h3 font-weight-bold">{{ stats?.expired_promotions || 0 }}</div>
            <div class="text-body-2 text-medium-emphasis">Kedaluwarsa</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filters and Data Table -->
    <VCard>
      <VCardText>
        <!-- Filters -->
        <VRow class="mb-4">
          <VCol cols="12" md="4">
            <VTextField
              v-model="filters.search"
              label="Cari promosi..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              clearable
              @input="debouncedSearch"
            />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.type"
              :items="promotionTypes"
              label="Tipe"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="fetchPromotions"
            />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.status"
              :items="statusOptions"
              label="Status"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="fetchPromotions"
            />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.sort_by"
              :items="sortOptions"
              label="Urutkan"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="fetchPromotions"
            />
          </VCol>
          <VCol cols="12" md="2" class="d-flex align-center">
            <VBtn
              variant="outlined"
              prepend-icon="mdi-refresh"
              @click="refreshData"
            >
              Refresh
            </VBtn>
          </VCol>
        </VRow>
        <!-- Data Table -->
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="currentPage"
          :headers="headers"
          :items="promotions"
          :items-length="totalItems"
          :loading="loading"
          class="promotion-table"
          @update:options="updateOptions"
        >
          <!-- Name -->
          <template #item.name="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.name }}</div>
              <div v-if="item.description" class="text-caption text-medium-emphasis">
                {{ truncate(item.description, 50) }}
              </div>
            </div>
          </template>

          <!-- Type -->
          <template #item.type="{ item }">
            <VChip
              size="small"
              :color="getTypeColor(item.type)"
              variant="tonal"
            >
              {{ getTypeLabel(item.type) }}
            </VChip>
          </template>

          <!-- Discount -->
          <template #item.discount="{ item }">
            <span class="font-weight-medium">
              {{ formatPromotionDiscount(item) }}
            </span>
          </template>

          <!-- Schedule -->
          <template #item.schedule="{ item }">
            <div class="text-sm">
              <div v-if="item.type === 'happy_hour'">
                <VIcon icon="mdi-clock-outline" size="14" class="me-1" />
                {{ item.start_time }} - {{ item.end_time }}
              </div>
              <div v-if="item.valid_days && item.valid_days.length > 0">
                <VIcon icon="mdi-calendar" size="14" class="me-1" />
                {{ formatValidDays(item.valid_days) }}
              </div>
              <div v-if="item.valid_from">
                {{ formatDate(item.valid_from) }} - {{ formatDate(item.valid_until) }}
              </div>
            </div>
          </template>
          <template #item.priority="{ item }">
            <VChip
              size="small"
              :color="getPriorityColor(item.priority)"
              variant="tonal"
            >
              {{ item.priority }}
            </VChip>
          </template>

          <!-- Status -->
          <template #item.status="{ item }">
            <VChip
              size="small"
              :color="getStatusColor(item.status)"
              variant="tonal"
            >
              {{ getStatusLabel(item.status) }}
            </VChip>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex align-center gap-2">
              <VBtn
                icon="mdi-eye"
                size="small"
                variant="text"
                @click="viewPromotion(item)"
              />
              <VBtn
                icon="mdi-pencil"
                size="small"
                variant="text"
                @click="editPromotion(item)"
              />
              <VBtn
                :icon="item.active ? 'mdi-pause' : 'mdi-play'"
                size="small"
                variant="text"
                @click="toggleStatus(item)"
              />
              <VBtn
                icon="mdi-content-copy"
                size="small"
                variant="text"
                @click="duplicatePromotion(item)"
              />
              <VBtn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="deletePromotion(item)"
              />
            </div>
          </template>
        </VDataTableServer>
      </VCardText>
    </VCard>

    <!-- Create/Edit Dialog -->
    <PromotionDialog
      v-model="showDialog"
      :promotion="selectedPromotion"
      :mode="dialogMode"
      @saved="onPromotionSaved"
    />

    <!-- View Dialog -->
    <PromotionViewDialog
      v-model="showViewDialog"
      :promotion="selectedPromotion"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="showDeleteDialog"
      :loading="deleteLoading"
      :item-name="selectedPromotion?.name || ''"
      title="Konfirmasi Hapus Promosi"
      @confirm="confirmDelete"
      @cancel="cancelDelete"
    />
  </div>
</template>

<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import PromotionDialog from '@/components/promotions/PromotionDialog.vue'
import PromotionViewDialog from '@/components/promotions/PromotionViewDialog.vue'
import PromotionsApi, { type Promotion, type PromotionFilters, type PromotionStats } from '@/utils/api/PromotionsApi'
import { useDebounceFn } from '@vueuse/core'
import { onMounted, ref } from 'vue'

// Data
const promotions = ref<Promotion[]>([])
const loading = ref(false)
const stats = ref<PromotionStats | null>(null)

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)

// Filters
const filters = ref<PromotionFilters>({
  search: '',
  type: 'all',
  status: 'all',
  sort_by: 'created_at',
  sort_order: 'desc'
})

// Individual filter refs for template
const searchQuery = ref('')
const statusFilter = ref('all')
const typeFilter = ref('all')
const priorityFilter = ref('all')

// Dialog
const showDialog = ref(false)
const showViewDialog = ref(false)
const showDeleteDialog = ref(false)
const selectedPromotion = ref<Promotion | null>(null)
const dialogMode = ref<'create' | 'edit'>('create')
const deleteLoading = ref(false)

// Constants
const promotionTypes = [
  { title: 'Semua Tipe', value: 'all' },
  { title: 'Happy Hour', value: 'happy_hour' },
  { title: 'Beli 1 Dapat 1', value: 'buy_one_get_one' },
  { title: 'Paket Combo', value: 'combo_deal' },
  { title: 'Diskon Kategori', value: 'category_discount' },
  { title: 'Diskon Quantity', value: 'quantity_discount' }
]

const statusOptions = [
  { title: 'Semua Status', value: 'all' },
  { title: 'Aktif', value: 'active' },
  { title: 'Tidak Aktif', value: 'inactive' },
  { title: 'Kadaluarsa', value: 'expired' },
  { title: 'Terjadwal', value: 'scheduled' }
]

const sortOptions = [
  { title: 'Tanggal Dibuat', value: 'created_at' },
  { title: 'Nama', value: 'name' },
  { title: 'Prioritas', value: 'priority' },
  { title: 'Tanggal Berlaku', value: 'valid_from' }
]

const headers = [
  { title: 'Nama', key: 'name', sortable: true },
  { title: 'Tipe', key: 'type', sortable: true },
  { title: 'Diskon', key: 'discount', sortable: true },
  { title: 'Periode', key: 'valid_period', sortable: false },
  { title: 'Jam', key: 'time_range', sortable: false },
  { title: 'Prioritas', key: 'priority', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center' as const }
] as const

// Debounced search
const debouncedSearch = useDebounceFn(() => {
  currentPage.value = 1
  fetchPromotions()
}, 500)

// Methods
const fetchPromotions = async () => {
  try {
    loading.value = true
    
    const promotionFilters: PromotionFilters = {
      search: filters.value.search || undefined,
      type: filters.value.type === 'all' ? undefined : filters.value.type,
      status: filters.value.status === 'all' ? undefined : filters.value.status,
      sort_by: filters.value.sort_by || 'priority',
      sort_order: 'desc',
      page: currentPage.value,
      per_page: itemsPerPage.value
    }
    
    const result = await PromotionsApi.getPromotions(promotionFilters)
    
    promotions.value = result.data
    totalItems.value = result.total
    
  } catch (error) {
    console.error('Failed to fetch promotions:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    stats.value = await PromotionsApi.getStats()
  } catch (error) {
    console.error('Failed to fetch promotion stats:', error)
  }
}

const refreshData = async () => {
  await Promise.all([
    fetchPromotions(),
    fetchStats()
  ])
}

const updateOptions = (options: any) => {
  currentPage.value = options.page
  itemsPerPage.value = options.itemsPerPage
  fetchPromotions()
}

const openCreateDialog = () => {
  selectedPromotion.value = null
  dialogMode.value = 'create'
  showDialog.value = true
}

const viewPromotion = (promotion: Promotion) => {
  selectedPromotion.value = promotion
  showViewDialog.value = true
}

const editPromotion = (promotion: Promotion) => {
  selectedPromotion.value = promotion
  dialogMode.value = 'edit'
  showDialog.value = true
}

const duplicatePromotion = async (promotion: Promotion) => {
  try {
    await PromotionsApi.duplicate(promotion.id_promotion)
    await refreshData()
  } catch (error) {
    console.error('Failed to duplicate promotion:', error)
  }
}

const toggleStatus = async (promotion: Promotion) => {
  try {
    await PromotionsApi.toggleStatus(promotion.id_promotion)
    await fetchPromotions()
  } catch (error) {
    console.error('Failed to toggle promotion status:', error)
  }
}

const deletePromotion = (promotion: Promotion) => {
  selectedPromotion.value = promotion
  showDeleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedPromotion.value) return
  
  try {
    deleteLoading.value = true
    await PromotionsApi.deletePromotion(selectedPromotion.value.id_promotion)
    await refreshData()
    showDeleteDialog.value = false
  } catch (error) {
    console.error('Failed to delete promotion:', error)
  } finally {
    deleteLoading.value = false
  }
}

const cancelDelete = () => {
  selectedPromotion.value = null
  showDeleteDialog.value = false
}

const onPromotionSaved = () => {
  showDialog.value = false
  refreshData()
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = 'all'
  typeFilter.value = 'all'
  priorityFilter.value = 'all'
  fetchPromotions()
}

// Utility functions
const formatPromotionDiscount = (promotion: Promotion): string => {
  return PromotionsApi.formatPromotionDiscount(promotion)
}

const getTypeLabel = (type: string): string => {
  return PromotionsApi.getPromotionTypeLabel(type)
}

const getTypeColor = (type: string): string => {
  const colors = {
    happy_hour: 'orange',
    buy_one_get_one: 'purple',
    combo_deal: 'teal',
    category_discount: 'indigo',
    quantity_discount: 'pink'
  }
  return colors[type as keyof typeof colors] || 'secondary'
}

const getPriorityColor = (priority: number): string => {
  if (priority >= 8) return 'error'
  if (priority >= 5) return 'warning'
  return 'success'
}

const getStatusColor = (status: string | undefined): string => {
  return PromotionsApi.getStatusColor(status || 'inactive')
}

const getStatusLabel = (status: string | undefined): string => {
  return PromotionsApi.getStatusLabel(status || 'inactive')
}

const formatDate = (dateString: string | undefined): string => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID')
}

const formatValidDays = (days: string[]): string => {
  const dayNames = {
    'monday': 'Sen',
    'tuesday': 'Sel',
    'wednesday': 'Rab',
    'thursday': 'Kam',
    'friday': 'Jum',
    'saturday': 'Sab',
    'sunday': 'Min'
  }
  
  return days.map(day => dayNames[day as keyof typeof dayNames] || day).join(', ')
}

const truncate = (text: string, length: number): string => {
  return text.length > length ? text.substring(0, length) + '...' : text
}

// Lifecycle
onMounted(() => {
  refreshData()
})
</script>

<style scoped>
.promotion-table :deep(.v-data-table__tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.04);
}

.promotion-table :deep(.v-data-table__td) {
  padding: 12px 16px !important;
}
</style>
