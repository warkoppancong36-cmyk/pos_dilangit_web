<template>
  <VCard class="mb-6">
    <VCardTitle class="d-flex align-center gap-2">
      <VIcon
        icon="tabler-filter"
        class="coffee-icon"
      />
      Filter & Pencarian Produk
    </VCardTitle>
    
    <VCardText>
      <VRow>
        <!-- Search -->
        <VCol cols="12" md="4">
          <VTextField
            v-model="searchQuery"
            label="Cari produk..."
            placeholder="Nama, SKU, atau deskripsi (min. 3 karakter)"
            prepend-inner-icon="tabler-search"
            :loading="isSearching"
            clearable
            variant="outlined"
            :hint="searchQuery.length > 0 && searchQuery.length < 3 ? 
              `Ketik ${3 - searchQuery.length} karakter lagi untuk mencari` : ''"
            persistent-hint
          />
        </VCol>

        <!-- Category Filter -->
        <VCol cols="12" md="3">
          <VSelect
            v-model="localFilters.category_id"
            :items="categories"
            item-title="name"
            item-value="id_category"
            label="Kategori"
            placeholder="Semua kategori"
            prepend-inner-icon="tabler-category"
            :loading="categoriesLoading"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          >
            <template #no-data>
              <VListItem>
                <VListItemTitle>{{ categoriesLoading ? 'Memuat kategori...' : 'Tidak ada kategori' }}</VListItemTitle>
              </VListItem>
            </template>
          </VSelect>
        </VCol>

        <!-- Status Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.active"
            :items="statusOptions"
            label="Status"
            placeholder="Semua status"
            prepend-inner-icon="tabler-toggle-left"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>

        <!-- Stock Status Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.stock_status"
            :items="stockOptions"
            label="Stok"
            placeholder="Semua stok"
            prepend-inner-icon="tabler-package"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>

        <!-- Station Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.station"
            :items="stationOptions"
            label="Station"
            placeholder="Semua station"
            prepend-inner-icon="tabler-tools-kitchen-2"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>

        <!-- Actions -->
        <VCol cols="12" md="1" class="d-flex align-center">
          <VBtn
            icon="tabler-refresh"
            variant="outlined"
            color="coffee"
            @click="resetFilters"
          />
        </VCol>
      </VRow>

      <!-- Additional Filters Row -->
      <VRow class="mt-3">
        <!-- Price Range -->
        <VCol cols="12" md="3">
          <VTextField
            v-model="localFilters.min_price"
            label="Harga Minimum"
            placeholder="0"
            prepend-inner-icon="tabler-currency-dollar"
            type="number"
            variant="outlined"
            @input="onFilterChange"
          />
        </VCol>

        <VCol cols="12" md="3">
          <VTextField
            v-model="localFilters.max_price"
            label="Harga Maksimum"
            placeholder="Tidak terbatas"
            prepend-inner-icon="tabler-currency-dollar"
            type="number"
            variant="outlined"
            @input="onFilterChange"
          />
        </VCol>

        <!-- Sort Order -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.sort_order"
            :items="orderOptions"
            label="Urutan"
            placeholder="Terbaru"
            prepend-inner-icon="tabler-arrow-up"
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>
      </VRow>

      <!-- Active Filters Display -->
      <div v-if="hasActiveFilters" class="mt-4">
        <div class="d-flex align-center gap-2 mb-2">
          <VIcon
            icon="tabler-filter-check"
            size="small"
            class="coffee-icon"
          />
          <span class="text-sm font-weight-medium">Filter Aktif:</span>
        </div>
        
        <div class="d-flex flex-wrap gap-2">
          <VChip
            v-if="searchQuery"
            closable
            size="small"
            color="coffee"
            @click:close="searchQuery = ''; localFilters.search = ''; onFilterChange()"
          >
            Pencarian: {{ searchQuery }}
          </VChip>
          
          <VChip
            v-if="localFilters.category_id"
            closable
            size="small"
            color="coffee"
            @click:close="localFilters.category_id = null; onFilterChange()"
          >
            Kategori: {{ getCategoryName(localFilters.category_id) }}
          </VChip>
          
          <VChip
            v-if="localFilters.active"
            closable
            size="small"
            color="coffee"
            @click:close="localFilters.active = null; onFilterChange()"
          >
            Status: {{ getStatusLabel(localFilters.active) }}
          </VChip>
          
          <VChip
            v-if="localFilters.stock_status"
            closable
            size="small"
            color="coffee"
            @click:close="localFilters.stock_status = null; onFilterChange()"
          >
            Stok: {{ getStockLabel(localFilters.stock_status) }}
          </VChip>
        
        </div>
      </div>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
import { useCategories } from '@/composables/useCategories';
// Update the import path if the file exists elsewhere, or create the file if missing
import type { ProductFilters } from '@/composables/useProducts';
import { watchDebounced } from '@vueuse/core';
import { computed, onMounted, reactive, ref, watch } from 'vue';

// Props
const props = defineProps<{
  filters: ProductFilters
}>()

// Emits
const emit = defineEmits<{
  'update:filters': [filters: ProductFilters]
  'search': [query: string]
}>()

// Use categories composable
const { categories, loading: categoriesLoading, fetchAllCategories } = useCategories()

// Search state
const isSearching = ref(false)
const searchQuery = ref('')

// Fetch categories on mount
onMounted(async () => {
  await fetchAllCategories() // Use fetchAllCategories instead of fetchCategories
  // Initialize search query with existing filter
  searchQuery.value = localFilters.search || ''
})

// Local reactive filters
const localFilters = reactive<ProductFilters>({ ...props.filters })

// Watch for external filter changes
watch(() => props.filters, (newFilters) => {
  Object.assign(localFilters, newFilters)
  searchQuery.value = newFilters.search || ''
}, { deep: true })

// Debounced search - trigger search when user types 3+ characters
watchDebounced(
  searchQuery,
  (newQuery) => {
    localFilters.search = newQuery
    
    // Only trigger search if query is 3+ characters or empty (to clear search)
    if (newQuery.length >= 3 || newQuery.length === 0) {
      isSearching.value = true
      emit('search', newQuery)
      onFilterChange()
      
      // Reset searching state after a short delay
      setTimeout(() => {
        isSearching.value = false
      }, 500)
    } else {
    }
  },
  { debounce: 500, maxWait: 1000 }
)

// Filter options
const statusOptions = [
  { title: 'Aktif', value: 'active' },
  { title: 'Tidak Aktif', value: 'inactive' }
]

const stockOptions = [
  { title: 'Tersedia', value: 'in_stock' },
  { title: 'Stok Rendah', value: 'low_stock' },
  { title: 'Habis', value: 'out_of_stock' }
]

const stationOptions = [
  { title: 'Kitchen Only', value: 'kitchen' },
  { title: 'Bar Only', value: 'bar' },
  { title: 'Kitchen & Bar', value: 'both' }
]

const featuredOptions = [
  { title: 'Ya', value: true },
  { title: 'Tidak', value: false }
]

const sortOptions = [
  { title: 'Nama', value: 'name' },
  { title: 'Harga', value: 'price' },
  { title: 'Stok', value: 'stock' },
  { title: 'Tanggal Dibuat', value: 'created_at' },
  { title: 'Tanggal Diperbarui', value: 'updated_at' }
]

const orderOptions = [
  { title: 'Terbaru', value: 'desc' },
  { title: 'Terlama', value: 'asc' }
]

// Computed
const hasActiveFilters = computed(() => {
  return !!(
    searchQuery.value ||
    localFilters.category_id ||
    localFilters.active ||
    localFilters.stock_status ||
    localFilters.station ||
    localFilters.featured !== null ||
    localFilters.min_price ||
    localFilters.max_price
  )
})

// Methods
const onFilterChange = () => {
  emit('update:filters', { ...localFilters })
}

const resetFilters = () => {
  searchQuery.value = ''
  Object.assign(localFilters, {
    search: '',
    category_id: null,
    active: null,
    stock_status: null,
    featured: null,
    min_price: null,
    max_price: null,
    sort_by: 'created_at',
    sort_order: 'desc'
  })
  onFilterChange()
}

const getCategoryName = (id: number): string => {
  const category = categories.value.find(c => c.id_category === id)
  return category?.name || 'Unknown'
}

const getStatusLabel = (value: string | boolean | null | undefined): string => {
  if (!value) return 'Semua'
  const option = statusOptions.find(o => o.value === value)
  return option?.title || String(value)
}

const getStockLabel = (value: string): string => {
  const option = stockOptions.find(o => o.value === value)
  return option?.title || value
}

const getFeaturedLabel = (value: boolean): string => {
  const option = featuredOptions.find(o => o.value === value)
  return option?.title || String(value)
}
</script>

<style scoped>
.coffee-icon {
  color: rgb(var(--v-theme-coffee));
}
</style>
