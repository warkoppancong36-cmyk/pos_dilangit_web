<template>
  <div class="discount-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Kelola Diskon</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola kode diskon dan potongan harga untuk coffee shop Anda</p>
      </div>
      <div class="d-flex gap-3 align-center">
        <VBtn
          color="primary"
          prepend-icon="mdi-plus"
          @click="openCreateDialog"
        >
          Tambah Diskon
        </VBtn>
      </div>
    </div>

    <!-- Statistics Cards -->
    <VRow class="mb-6">
      <VCol cols="12" md="3">
        <VCard color="primary" variant="tonal">
          <VCardText class="text-center">
            <div class="text-h3 font-weight-bold">{{ stats?.total_discounts || 0 }}</div>
            <div class="text-body-2 text-medium-emphasis">Total Diskon</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3">
        <VCard color="success" variant="tonal">
          <VCardText class="text-center">
            <div class="text-h3 font-weight-bold">{{ stats?.active_discounts || 0 }}</div>
            <div class="text-body-2 text-medium-emphasis">Aktif</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3">
        <VCard color="warning" variant="tonal">
          <VCardText class="text-center">
            <div class="text-h3 font-weight-bold">{{ stats?.total_usage || 0 }}</div>
            <div class="text-body-2 text-medium-emphasis">Terpakai</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3">
        <VCard color="error" variant="tonal">
          <VCardText class="text-center">
            <div class="text-h3 font-weight-bold">{{ stats?.expired_discounts || 0 }}</div>
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
              label="Cari diskon..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              clearable
              @input="debouncedSearch"
              @click:clear="filters.search = ''; fetchDiscounts()"
            />
          </VCol>
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.type"
              :items="discountTypes"
              label="Tipe"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="fetchDiscounts"
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
              @update:model-value="fetchDiscounts"
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
              @update:model-value="fetchDiscounts"
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
          :items="discounts"
          :items-length="totalItems"
          :loading="loading"
          class="discount-table"
          @update:options="updateOptions"
        >
          <!-- Code -->
          <template #item.code="{ item }">
            <div class="d-flex align-center">
              <VChip
                size="small"
                color="primary"
                variant="tonal"
              >
                {{ item.code }}
              </VChip>
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

          <!-- Value -->
          <template #item.value="{ item }">
            <span class="font-weight-medium">
              {{ formatDiscountValue(item) }}
            </span>
          </template>

          <!-- Usage -->
          <template #item.usage="{ item }">
            <div class="text-center">
              <div class="font-weight-medium">{{ item.used_count }}</div>
              <div v-if="item.usage_limit" class="text-caption text-medium-emphasis">
                / {{ item.usage_limit }}
              </div>
              <div v-else class="text-caption text-medium-emphasis">
                Unlimited
              </div>
            </div>
          </template>

          <!-- Valid Period -->
          <template #item.valid_period="{ item }">
            <div class="text-sm">
              <div>{{ formatDate(item.valid_from) }}</div>
              <div class="text-medium-emphasis">
                {{ formatDate(item.valid_until) }}
              </div>
            </div>
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
                @click="viewDiscount(item)"
              />
              <VBtn
                icon="mdi-pencil"
                size="small"
                variant="text"
                @click="editDiscount(item)"
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
                @click="duplicateDiscount(item)"
              />
              <VBtn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="deleteDiscount(item)"
              />
            </div>
          </template>
        </VDataTableServer>
      </VCardText>
    </VCard>

    <!-- Create/Edit Dialog -->
    <DiscountDialog
      v-model="showDialog"
      :discount="selectedDiscount"
      :mode="dialogMode"
      @saved="onDiscountSaved"
    />

    <!-- View Dialog -->
    <DiscountViewDialog
      v-model="showViewDialog"
      :discount="selectedDiscount"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="showDeleteDialog"
      :loading="deleteLoading"
      :item-name="selectedDiscount?.name || ''"
      title="Konfirmasi Hapus Diskon"
      @confirm="confirmDelete"
      @cancel="cancelDelete"
    />
  </div>
</template>

<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import DiscountDialog from '@/components/discounts/DiscountDialog.vue'
import DiscountViewDialog from '@/components/discounts/DiscountViewDialog.vue'
import DiscountsApi, { type Discount, type DiscountFilters, type DiscountStats } from '@/utils/api/DiscountsApi'
import { useDebounceFn } from '@vueuse/core'
import { onMounted, ref } from 'vue'

// Data
const discounts = ref<Discount[]>([])
const loading = ref(false)
const stats = ref<DiscountStats | null>(null)

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)

// Filters
const filters = ref<DiscountFilters>({
  search: '',
  type: 'all',
  status: 'all',
  sort_by: 'created_at',
  sort_order: 'desc'
})

// Dialog
const showDialog = ref(false)
const showViewDialog = ref(false)
const showDeleteDialog = ref(false)
const selectedDiscount = ref<Discount | null>(null)
const dialogMode = ref<'create' | 'edit'>('create')
const deleteLoading = ref(false)

// Constants
const discountTypes = [
  { title: 'Semua Tipe', value: 'all' },
  { title: 'Persentase', value: 'percentage' },
  { title: 'Jumlah Tetap', value: 'fixed_amount' },
  { title: 'Beli X Dapat Y', value: 'buy_x_get_y' }
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
  { title: 'Kode', value: 'code' },
  { title: 'Nilai', value: 'value' },
  { title: 'Penggunaan', value: 'used_count' },
  { title: 'Tanggal Berlaku', value: 'valid_from' }
]

const headers = [
  { title: 'Kode', key: 'code', sortable: false },
  { title: 'Nama', key: 'name', sortable: true },
  { title: 'Tipe', key: 'type', sortable: false },
  { title: 'Nilai', key: 'value', sortable: true },
  { title: 'Penggunaan', key: 'usage', sortable: false },
  { title: 'Periode Berlaku', key: 'valid_period', sortable: false },
  { title: 'Status', key: 'status', sortable: false },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center' as const }
] as const

// Computed
const debouncedSearch = useDebounceFn(() => {
  currentPage.value = 1
  fetchDiscounts()
}, 500)

// Methods
const fetchDiscounts = async () => {
  try {
    loading.value = true
    
    const discountFilters: DiscountFilters = {
      search: filters.value.search || undefined,
      type: filters.value.type === 'all' ? undefined : filters.value.type,
      status: filters.value.status === 'all' ? undefined : filters.value.status,
      sort_by: filters.value.sort_by || 'created_at',
      sort_order: 'desc',
      page: currentPage.value,
      per_page: itemsPerPage.value
    }
    
    const result = await DiscountsApi.getDiscounts(discountFilters)
    
    discounts.value = result.data
    totalItems.value = result.total
    
  } catch (error) {
    console.error('Failed to fetch discounts:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    stats.value = await DiscountsApi.getStats()
  } catch (error) {
    console.error('Failed to fetch discount stats:', error)
  }
}

const refreshData = async () => {
  await Promise.all([
    fetchDiscounts(),
    fetchStats()
  ])
}

const updateOptions = (options: any) => {
  currentPage.value = options.page
  itemsPerPage.value = options.itemsPerPage
  fetchDiscounts()
}

const openCreateDialog = () => {
  selectedDiscount.value = null
  dialogMode.value = 'create'
  showDialog.value = true
}

const viewDiscount = (discount: Discount) => {
  selectedDiscount.value = discount
  showViewDialog.value = true
}

const editDiscount = (discount: Discount) => {
  selectedDiscount.value = discount
  dialogMode.value = 'edit'
  showDialog.value = true
}

const duplicateDiscount = async (discount: Discount) => {
  try {
    await DiscountsApi.duplicate(discount.id_discount)
    await refreshData()
  } catch (error) {
    console.error('Failed to duplicate discount:', error)
  }
}

const toggleStatus = async (discount: Discount) => {
  try {
    await DiscountsApi.toggleStatus(discount.id_discount)
    await fetchDiscounts()
  } catch (error) {
    console.error('Failed to toggle discount status:', error)
  }
}

const deleteDiscount = (discount: Discount) => {
  selectedDiscount.value = discount
  showDeleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedDiscount.value) return
  
  try {
    deleteLoading.value = true
    await DiscountsApi.deleteDiscount(selectedDiscount.value.id_discount)
    await refreshData()
    showDeleteDialog.value = false
  } catch (error) {
    console.error('Failed to delete discount:', error)
  } finally {
    deleteLoading.value = false
  }
}

const cancelDelete = () => {
  selectedDiscount.value = null
  showDeleteDialog.value = false
}

const onDiscountSaved = () => {
  showDialog.value = false
  refreshData()
}

// Utility functions
const formatDiscountValue = (discount: Discount): string => {
  return DiscountsApi.formatDiscountValue(discount)
}

const getTypeLabel = (type: string): string => {
  return DiscountsApi.getDiscountTypeLabel(type)
}

const getTypeColor = (type: string): string => {
  const colors = {
    percentage: 'primary',
    fixed_amount: 'success',
    buy_x_get_y: 'warning'
  }
  return colors[type as keyof typeof colors] || 'secondary'
}

const getStatusColor = (status: string | undefined): string => {
  return DiscountsApi.getStatusColor(status || 'inactive')
}

const getStatusLabel = (status: string | undefined): string => {
  return DiscountsApi.getStatusLabel(status || 'inactive')
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('id-ID')
}

// Lifecycle
onMounted(() => {
  refreshData()
})
</script>

<style scoped>
.discount-table :deep(.v-data-table__tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.04);
}

.discount-table :deep(.v-data-table__td) {
  padding: 12px 16px !important;
}
</style>
