<template>
  <VCard>
    <!-- Table Header with Bulk Actions -->
    <VCardText v-if="hasSelectedCustomers" class="pb-2">
      <div class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2">
          <VChip
            color="primary"
            variant="elevated"
            size="small"
          >
            {{ selectedCustomers.length }} dipilih
          </VChip>
          
          <VBtn
            variant="outlined"
            color="error"
            size="small"
            prepend-icon="tabler-trash"
            :loading="deleteLoading"
            @click="$emit('bulk-delete')"
          >
            Hapus Terpilih
          </VBtn>
        </div>
        
        <VBtn
          variant="text"
          size="small"
          @click="selectedCustomers = []"
        >
          Batal Pilih
        </VBtn>
      </div>
    </VCardText>

    <!-- Data Table -->
    <VDataTable
      v-model="selectedCustomers"
      :headers="headers"
      :items="customers"
      :loading="loading"
      :items-per-page="itemsPerPage"
      :page="currentPage"
      :server-items-length="totalItems"
      show-select
      item-value="id_customer"
      class="text-no-wrap"
      @update:page="onPageChange"
      @update:items-per-page="onItemsPerPageChange"
    >
      <!-- Customer Code -->
      <template #item.customer_code="{ item }">
        <div class="d-flex align-center">
          <VChip
            color="primary"
            variant="outlined"
            size="small"
            class="font-weight-medium"
          >
            {{ item.customer_code }}
          </VChip>
        </div>
      </template>

      <!-- Name with Email -->
      <template #item.name="{ item }">
        <div class="d-flex flex-column">
          <span class="font-weight-medium">{{ item.name }}</span>
          <small class="text-medium-emphasis">{{ item.email || '-' }}</small>
        </div>
      </template>

      <!-- Contact Info -->
      <template #item.contact="{ item }">
        <div class="d-flex flex-column">
          <span>{{ item.phone || '-' }}</span>
          <small class="text-medium-emphasis">{{ item.city || '-' }}</small>
        </div>
      </template>

      <!-- Gender -->
      <template #item.gender="{ item }">
        <VChip
          v-if="item.gender"
          :color="getGenderColor(item.gender)"
          variant="tonal"
          size="small"
        >
          {{ getGenderLabel(item.gender) }}
        </VChip>
        <span v-else class="text-medium-emphasis">-</span>
      </template>

      <!-- Age -->
      <template #item.age="{ item }">
        <span v-if="item.age">{{ item.age }} tahun</span>
        <span v-else class="text-medium-emphasis">-</span>
      </template>

      <!-- Status -->
      <template #item.active="{ item }">
        <VChip
          :color="item.active ? 'success' : 'error'"
          variant="tonal"
          size="small"
        >
          {{ item.active ? 'Aktif' : 'Tidak Aktif' }}
        </VChip>
      </template>

      <!-- Customer Status -->
      <template #item.customer_status="{ item }">
        <VChip
          :color="getStatusColor(item.customer_status || '')"
          variant="tonal"
          size="small"
        >
          {{ getStatusLabel(item.customer_status || '') }}
        </VChip>
      </template>

      <!-- Loyalty Level -->
      <template #item.loyalty_level="{ item }">
        <VChip
          :color="getLoyaltyColor(item.loyalty_level || '')"
          variant="elevated"
          size="small"
        >
          {{ getLoyaltyLabel(item.loyalty_level || '') }}
        </VChip>
      </template>

      <!-- Total Visits -->
      <template #item.total_visits="{ item }">
        <div class="d-flex align-center">
          <VIcon
            icon="tabler-user-check"
            size="16"
            class="me-1 text-medium-emphasis"
          />
          <span class="font-weight-medium">{{ item.total_visits }}</span>
        </div>
      </template>

      <!-- Total Spent -->
      <template #item.total_spent="{ item }">
        <div class="text-end">
          <span class="font-weight-medium text-success">
            {{ formatCurrency(item.total_spent) }}
          </span>
        </div>
      </template>

      <!-- Last Visit -->
      <template #item.last_visit="{ item }">
        <span v-if="item.last_visit" class="text-caption">
          {{ formatDate(item.last_visit) }}
        </span>
        <span v-else class="text-medium-emphasis">Belum pernah</span>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="d-flex align-center gap-1">
          <!-- Toggle Active Status -->
          <VTooltip text="Toggle Status">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                :icon="item.active ? 'tabler-eye-off' : 'tabler-eye'"
                :color="item.active ? 'warning' : 'success'"
                variant="text"
                size="small"
                :loading="toggleLoading[item.id_customer]"
                @click="$emit('toggle-status', item)"
              />
            </template>
          </VTooltip>

          <!-- Edit -->
          <VTooltip text="Edit Customer">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-edit"
                color="primary"
                variant="text"
                size="small"
                @click="$emit('edit', item)"
              />
            </template>
          </VTooltip>

          <!-- Delete -->
          <VTooltip text="Hapus Customer">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-trash"
                color="error"
                variant="text"
                size="small"
                @click="$emit('delete', item)"
              />
            </template>
          </VTooltip>

          <!-- More Actions Menu -->
          <VMenu>
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-dots-vertical"
                variant="text"
                size="small"
              />
            </template>
            
            <VList density="compact">
              <VListItem
                prepend-icon="tabler-eye"
                title="Lihat Detail"
                @click="$emit('view-detail', item)"
              />
              <VListItem
                prepend-icon="tabler-history"
                title="Riwayat Transaksi"
                @click="$emit('view-history', item)"
              />
              <VListItem
                prepend-icon="tabler-notes"
                title="Catatan"
                @click="$emit('view-notes', item)"
              />
            </VList>
          </VMenu>
        </div>
      </template>

      <!-- No Data -->
      <template #no-data>
        <div class="text-center py-8">
          <VIcon
            icon="tabler-users-off"
            size="48"
            class="text-medium-emphasis mb-4"
          />
          <div class="text-h6 text-medium-emphasis mb-2">
            Tidak ada customer ditemukan
          </div>
          <div class="text-body-2 text-medium-emphasis">
            Silahkan ubah filter pencarian atau tambah customer baru
          </div>
        </div>
      </template>

      <!-- Loading -->
      <template #loading>
        <div class="text-center py-8">
          <VProgressCircular
            indeterminate
            color="primary"
            size="40"
          />
          <div class="mt-4 text-medium-emphasis">
            Memuat data customer...
          </div>
        </div>
      </template>
    </VDataTable>
  </VCard>
</template>

<script setup lang="ts">
import type { Customer } from '@/composables/useCustomers'
import { computed } from 'vue'

interface Props {
  customers: Customer[]
  loading: boolean
  deleteLoading: boolean
  toggleLoading: Record<number, boolean>
  currentPage: number
  totalItems: number
  itemsPerPage: number
  selectedCustomers: number[]
}

interface Emits {
  (e: 'edit', customer: Customer): void
  (e: 'delete', customer: Customer): void
  (e: 'bulk-delete'): void
  (e: 'toggle-status', customer: Customer): void
  (e: 'view-detail', customer: Customer): void
  (e: 'view-history', customer: Customer): void
  (e: 'view-notes', customer: Customer): void
  (e: 'page-change', page: number): void
  (e: 'items-per-page-change', itemsPerPage: number): void
  (e: 'update:selected-customers', customers: number[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Table headers
const headers = [
  { title: 'Kode', key: 'customer_code', sortable: false, width: 120 },
  { title: 'Nama', key: 'name', sortable: false, width: 200 },
  { title: 'Kontak', key: 'contact', sortable: false, width: 150 },
  { title: 'Gender', key: 'gender', sortable: false, width: 100 },
  { title: 'Umur', key: 'age', sortable: false, width: 80 },
  { title: 'Status', key: 'active', sortable: false, width: 100 },
  { title: 'Customer Status', key: 'customer_status', sortable: false, width: 120 },
  { title: 'Level', key: 'loyalty_level', sortable: false, width: 100 },
  { title: 'Kunjungan', key: 'total_visits', sortable: false, width: 100 },
  { title: 'Total Belanja', key: 'total_spent', sortable: false, width: 130 },
  { title: 'Terakhir', key: 'last_visit', sortable: false, width: 120 },
  { title: 'Aksi', key: 'actions', sortable: false, width: 150 }
]

// Computed
const selectedCustomers = computed({
  get: () => props.selectedCustomers,
  set: (value) => emit('update:selected-customers', value)
})

const hasSelectedCustomers = computed(() => props.selectedCustomers.length > 0)

// Methods
const onPageChange = (page: number) => {
  emit('page-change', page)
}

const onItemsPerPageChange = (itemsPerPage: number) => {
  emit('items-per-page-change', itemsPerPage)
}

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }).format(date)
}

const getGenderColor = (gender: string): string => {
  switch (gender) {
    case 'male': return 'blue'
    case 'female': return 'pink'
    case 'other': return 'purple'
    default: return 'grey'
  }
}

const getGenderLabel = (gender: string): string => {
  switch (gender) {
    case 'male': return 'L'
    case 'female': return 'P'
    case 'other': return 'Lainnya'
    default: return '-'
  }
}

const getStatusColor = (status: string): string => {
  switch (status) {
    case 'active': return 'success'
    case 'regular': return 'info'
    case 'inactive': return 'warning'
    case 'dormant': return 'error'
    default: return 'grey'
  }
}

const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'active': return 'Aktif'
    case 'regular': return 'Reguler'
    case 'inactive': return 'Tidak Aktif'
    case 'dormant': return 'Dorman'
    default: return '-'
  }
}

const getLoyaltyColor = (level: string): string => {
  switch (level) {
    case 'platinum': return 'purple'
    case 'gold': return 'amber'
    case 'silver': return 'blue-grey'
    case 'bronze': return 'orange'
    case 'basic': return 'grey'
    default: return 'grey'
  }
}

const getLoyaltyLabel = (level: string): string => {
  switch (level) {
    case 'platinum': return 'Platinum'
    case 'gold': return 'Gold'
    case 'silver': return 'Silver'
    case 'bronze': return 'Bronze'
    case 'basic': return 'Basic'
    default: return '-'
  }
}
</script>
