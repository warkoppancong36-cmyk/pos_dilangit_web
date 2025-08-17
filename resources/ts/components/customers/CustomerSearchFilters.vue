<template>
  <VCard class="mb-6">
    <VCardText>
      <VRow>
        <!-- Search Input -->
        <VCol cols="12" md="4">
          <VTextField
            v-model="localFilters.search"
            label="Cari customer..."
            placeholder="Nama, email, telepon, atau kode customer"
            prepend-inner-icon="tabler-search"
            clearable
            variant="outlined"
            @update:model-value="debouncedSearch"
          />
        </VCol>

        <!-- Status Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.status"
            label="Status"
            :items="statusOptions"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>

        <!-- Gender Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.gender"
            label="Jenis Kelamin"
            :items="genderOptions"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>

        <!-- Loyalty Level Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.loyalty_level"
            label="Level Loyalitas"
            :items="loyaltyLevelOptions"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          >
            <template #item="{ props, item }">
              <VListItem v-bind="props">
                <template #prepend>
                  <VChip
                    :color="item.raw.color"
                    size="x-small"
                    class="me-2"
                  />
                </template>
              </VListItem>
            </template>
          </VSelect>
        </VCol>

        <!-- More Filters Button -->
        <VCol cols="12" md="2">
          <VBtn
            :variant="showAdvancedFilters ? 'elevated' : 'outlined'"
            :color="showAdvancedFilters ? 'primary' : 'default'"
            block
            @click="showAdvancedFilters = !showAdvancedFilters"
          >
            <VIcon
              :icon="showAdvancedFilters ? 'tabler-filter-minus' : 'tabler-filter-plus'"
              class="me-1"
            />
            Filter Lanjutan
          </VBtn>
        </VCol>
      </VRow>

      <!-- Advanced Filters -->
      <VExpandTransition>
        <div v-show="showAdvancedFilters">
          <VDivider class="my-4" />
          <VRow>
            <!-- City Filter -->
            <VCol cols="12" md="3">
              <VTextField
                v-model="localFilters.city"
                label="Kota"
                placeholder="Filter berdasarkan kota"
                prepend-inner-icon="tabler-map-pin"
                clearable
                variant="outlined"
                @update:model-value="debouncedSearch"
              />
            </VCol>

            <!-- Recent Days Filter -->
            <VCol cols="12" md="3">
              <VSelect
                v-model="localFilters.recent_days"
                label="Aktivitas Terakhir"
                :items="recentDaysOptions"
                clearable
                variant="outlined"
                @update:model-value="onFilterChange"
              />
            </VCol>

            <!-- Minimum Visits Filter -->
            <VCol cols="12" md="2">
              <VTextField
                v-model.number="localFilters.min_visits"
                label="Min. Kunjungan"
                type="number"
                min="0"
                placeholder="0"
                prepend-inner-icon="tabler-user-check"
                clearable
                variant="outlined"
                @update:model-value="debouncedSearch"
              />
            </VCol>

            <!-- Sort By -->
            <VCol cols="12" md="2">
              <VSelect
                v-model="localFilters.sort_by"
                label="Urutkan"
                :items="sortByOptions"
                variant="outlined"
                @update:model-value="onFilterChange"
              />
            </VCol>

            <!-- Sort Order -->
            <VCol cols="12" md="2">
              <VSelect
                v-model="localFilters.sort_order"
                label="Urutan"
                :items="sortOrderOptions"
                variant="outlined"
                @update:model-value="onFilterChange"
              />
            </VCol>
          </VRow>

          <!-- Filter Actions -->
          <VRow class="mt-2">
            <VCol cols="12">
              <div class="d-flex gap-2">
                <VBtn
                  variant="outlined"
                  color="secondary"
                  prepend-icon="tabler-refresh"
                  @click="resetFilters"
                >
                  Reset Filter
                </VBtn>
                
                <VSpacer />
                
                <VChip
                  v-if="activeFiltersCount > 0"
                  color="primary"
                  variant="elevated"
                  size="small"
                >
                  {{ activeFiltersCount }} filter aktif
                </VChip>
              </div>
            </VCol>
          </VRow>
        </div>
      </VExpandTransition>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
import type { CustomerFilters } from '@/composables/useCustomers';
import { computed, ref, watch } from 'vue';

interface Props {
  filters: CustomerFilters
  genderOptions: Array<{ title: string; value: string }>
  loyaltyLevelOptions: Array<{ title: string; value: string; color: string }>
}

interface Emits {
  (e: 'update:filters', filters: Partial<CustomerFilters>): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local state
const showAdvancedFilters = ref(false)
const localFilters = ref<CustomerFilters>({ ...props.filters })

// Watch for external filter changes
watch(
  () => props.filters,
  (newFilters) => {
    localFilters.value = { ...newFilters }
  },
  { deep: true }
)

// Options
const statusOptions = [
  { title: 'Aktif', value: 'active' },
  { title: 'Tidak Aktif', value: 'inactive' }
]

const recentDaysOptions = [
  { title: '7 hari terakhir', value: 7 },
  { title: '30 hari terakhir', value: 30 },
  { title: '90 hari terakhir', value: 90 },
  { title: '180 hari terakhir', value: 180 },
  { title: '1 tahun terakhir', value: 365 }
]

const sortByOptions = [
  { title: 'Tanggal Dibuat', value: 'created_at' },
  { title: 'Nama', value: 'name' },
  { title: 'Kunjungan Terakhir', value: 'last_visit' },
  { title: 'Total Kunjungan', value: 'total_visits' },
  { title: 'Total Pengeluaran', value: 'total_spent' },
  { title: 'Kode Customer', value: 'customer_code' }
]

const sortOrderOptions = [
  { title: 'Terbaru', value: 'desc' },
  { title: 'Terlama', value: 'asc' }
]

// Computed
const activeFiltersCount = computed(() => {
  let count = 0
  if (localFilters.value.search) count++
  if (localFilters.value.status) count++
  if (localFilters.value.gender) count++
  if (localFilters.value.city) count++
  if (localFilters.value.loyalty_level) count++
  if (localFilters.value.recent_days) count++
  if (localFilters.value.min_visits) count++
  return count
})

// Debounced search
let searchTimeout: NodeJS.Timeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    onFilterChange()
  }, 500)
}

// Methods
const onFilterChange = () => {
  emit('update:filters', localFilters.value)
}

const resetFilters = () => {
  localFilters.value = {
    search: '',
    status: undefined,
    gender: undefined,
    city: '',
    loyalty_level: undefined,
    recent_days: undefined,
    min_visits: undefined,
    sort_by: 'created_at',
    sort_order: 'desc'
  }
  showAdvancedFilters.value = false
  onFilterChange()
}
</script>
