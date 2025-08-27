<script setup lang="ts">
import { computed, defineEmits, ref } from 'vue'
import { formatRupiah } from '@/@core/utils/formatters'
import VariantFormDialog from './VariantFormDialog.vue'
import VariantCompositionDialog from './VariantCompositionDialog.vue'
import VariantDetailDialog from './VariantDetailDialog.vue'
import VariantManagementDialog from './VariantManagementDialog.vue'

interface Variant {
  id: number  // From accessor (same as id_variant)
  id_variant: number  // Primary key from Laravel Model  
  product_id: number
  name: string
  sku: string
  price: number
  cost_from_composition?: number
  current_stock: number
  min_stock: number
  is_active: boolean
  variant_items_count: number
  product?: {
    id: number
    name: string
    code: string
    image_url?: string
  }
}

interface Product {
  id: number
  id_product?: number  // Laravel primary key
  name: string
  code?: string
  image_url?: string
}

interface Props {
  variants: Variant[]
  product: Product
  loading?: boolean
}

interface Emits {
  (e: 'refresh'): void
  (e: 'create-variant'): void
  (e: 'edit-variant', variant: Variant): void
  (e: 'delete-variant', variant: Variant): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Debug: Check if product has id

// Dialog state
const formDialog = ref(false)
const compositionDialog = ref(false)
const detailDialog = ref(false)
const managementDialog = ref(false)
const selectedVariant = ref<Variant | null>(null)
const editMode = ref(false)

// Filter state
const filters = ref({
  search: '',
  status: null as boolean | null,
  stockStatus: '',
  sortBy: 'name_asc'
})

// Filter options
const statusOptions = [
  { title: 'Aktif', value: true },
  { title: 'Tidak Aktif', value: false }
]

const stockStatusOptions = [
  { title: 'Stok Aman', value: 'safe' },
  { title: 'Stok Rendah', value: 'low' },
  { title: 'Stok Habis', value: 'out' }
]

const sortOptions = [
  { title: 'Nama (A-Z)', value: 'name_asc' },
  { title: 'Nama (Z-A)', value: 'name_desc' },
  { title: 'Harga (Rendah-Tinggi)', value: 'price_asc' },
  { title: 'Harga (Tinggi-Rendah)', value: 'price_desc' },
  { title: 'Stok (Rendah-Tinggi)', value: 'stock_asc' },
  { title: 'Stok (Tinggi-Rendah)', value: 'stock_desc' }
]

// Check if any filter is active
const hasActiveFilters = computed(() => {
  return !!(filters.value.search || filters.value.status !== null || filters.value.stockStatus || filters.value.sortBy !== 'name_asc')
})

// Count active filters
const activeFiltersCount = computed(() => {
  let count = 0
  if (filters.value.search) count++
  if (filters.value.status !== null) count++
  if (filters.value.stockStatus) count++
  if (filters.value.sortBy !== 'name_asc') count++
  return count
})

// Filtered and sorted variants
const filteredVariants = computed(() => {
  let filtered = [...props.variants]
  
  // Apply search filter
  if (filters.value.search) {
    const searchTerm = filters.value.search.toLowerCase()
    filtered = filtered.filter(variant => 
      variant.name.toLowerCase().includes(searchTerm) ||
      variant.sku.toLowerCase().includes(searchTerm)
    )
  }
  
  // Apply status filter
  if (filters.value.status !== null) {
    filtered = filtered.filter(variant => variant.is_active === filters.value.status)
  }
  
  // Apply stock status filter
  if (filters.value.stockStatus) {
    filtered = filtered.filter(variant => {
      const stockStatus = getStockStatus(variant)
      return stockStatus.value === filters.value.stockStatus
    })
  }
  
  // Apply sorting
  filtered.sort((a, b) => {
    switch (filters.value.sortBy) {
      case 'name_desc':
        return b.name.localeCompare(a.name)
      case 'price_asc':
        return a.price - b.price
      case 'price_desc':
        return b.price - a.price
      case 'stock_asc':
        return a.current_stock - b.current_stock
      case 'stock_desc':
        return b.current_stock - a.current_stock
      default:
        return a.name.localeCompare(b.name)
    }
  })
  
  return filtered
})

// Helper functions
const getStockStatus = (variant: Variant) => {
  if (variant.current_stock <= 0) {
    return { text: 'Stok Habis', color: 'error', value: 'out' }
  }
  if (variant.current_stock <= variant.min_stock) {
    return { text: 'Stok Rendah', color: 'warning', value: 'low' }
  }
  return { text: 'Stok Aman 123', color: 'success', value: 'safe' }
}

const getMarginColor = (price: number, cost: number) => {
  if (!cost) return 'grey'
  const margin = ((price - cost) / price) * 100
  if (margin < 10) return 'error'
  if (margin < 20) return 'warning'
  return 'success'
}

const getMarginText = (price: number, cost: number) => {
  if (!cost) return 'N/A'
  const margin = ((price - cost) / price) * 100
  return margin.toFixed(1) + '%'
}

// Dialog handlers
const openCreateDialog = () => {
  
  selectedVariant.value = null
  editMode.value = false
  formDialog.value = true
  
}

const openEditDialog = (variant: Variant) => {
  selectedVariant.value = { ...variant }
  editMode.value = true
  formDialog.value = true
}

const openCompositionDialog = (variant: Variant) => {
  selectedVariant.value = variant
  compositionDialog.value = true
}

const openDetailDialog = (variant: Variant) => {
  selectedVariant.value = variant
  detailDialog.value = true
}

const handleSave = () => {
  formDialog.value = false
  emit('refresh')
}

const handleCompositionSave = () => {
  compositionDialog.value = false
  emit('refresh')
}

const openManagementDialog = () => {
  managementDialog.value = true
}

const handleManagementSave = () => {
  managementDialog.value = false
  emit('refresh')
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: null,
    stockStatus: '',
    sortBy: 'name_asc'
  }
}
</script>

<template>
  <div class="variant-cards">
    <!-- Header -->
    <VCard class="mb-6">
      <VCardTitle class="d-flex align-center justify-between">
        <div>
          <h2 class="text-h5 font-weight-bold">Variant Produk - {{ product?.name }}</h2>
          <p class="text-body-2 text-medium-emphasis">
            Kelola variant dan komposisi untuk produk {{ product?.name }}
          </p>
        </div>
        <div class="d-flex gap-2">
          <VBtn
            variant="outlined"
            prepend-icon="mdi-refresh"
            @click="emit('refresh')"
            :loading="loading"
          >
            Refresh
          </VBtn>
          <VBtn
            color="info"
            variant="outlined"
            prepend-icon="mdi-cog"
            @click="openManagementDialog"
          >
            Kelola Variant
          </VBtn>
          <VBtn
            color="primary"
            prepend-icon="mdi-plus"
            @click="openCreateDialog"
          >
            Tambah Variant
          </VBtn>
        </div>
      </VCardTitle>
    </VCard>

    <!-- Filters -->
    <VCard class="mb-6" elevation="0" variant="outlined">
      <VCardText>
        <VRow>
          <VCol cols="12" md="3">
            <VTextField
              v-model="filters.search"
              label="Cari Variant"
              placeholder="Nama variant atau SKU..."
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-magnify"
              hide-details
              clearable
            />
          </VCol>
          
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.status"
              :items="statusOptions"
              label="Status"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-toggle-switch"
              hide-details
              clearable
            />
          </VCol>
          
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.stockStatus"
              :items="stockStatusOptions"
              label="Status Stok"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-chart-line"
              hide-details
              clearable
            />
          </VCol>
          
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.sortBy"
              :items="sortOptions"
              label="Urutkan"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-sort"
              hide-details
            />
          </VCol>
          
          <VCol cols="12" md="2">
            <div class="d-flex align-center gap-2">
              <VBtn
                v-if="hasActiveFilters"
                variant="outlined"
                color="secondary"
                @click="resetFilters"
                block
              >
                Reset ({{ activeFiltersCount }})
              </VBtn>
              <VChip
                v-else
                variant="outlined"
                size="small"
              >
                {{ filteredVariants.length }} variant
              </VChip>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Loading State -->
    <div v-if="loading" class="text-center pa-12">
      <VProgressCircular
        indeterminate
        size="64"
        color="primary"
      />
      <p class="text-h6 mt-4">Memuat variant...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredVariants.length === 0" class="text-center pa-12">
      <VIcon
        icon="mdi-package-variant-closed"
        size="96"
        class="text-disabled mb-6"
      />
      <h3 class="text-h5 font-weight-bold mb-4">
        {{ variants.length === 0 ? 'Belum Ada Variant' : 'Tidak Ada Variant Ditemukan' }}
      </h3>
      <p class="text-body-1 text-medium-emphasis mb-6">
        {{ variants.length === 0 
          ? `Buat variant untuk produk ${product?.name} agar dapat melihat berbagai varian yang tersedia`
          : 'Coba ubah filter pencarian untuk menemukan variant yang Anda cari'
        }}
      </p>
      <VBtn
        v-if="variants.length === 0"
        color="primary"
        size="large"
        prepend-icon="mdi-plus"
        @click="openCreateDialog"
      >
        Buat Variant Pertama
      </VBtn>
    </div>

    <!-- Variant Table -->
    <VCard v-else>
      <VCol
        v-for="variant in filteredVariants"
        :key="variant.id"
        cols="12"
        md="6"
        lg="4"
      >
        <VCard
          class="variant-card h-100"
          elevation="2"
          :class="{ 
            'border-error': getStockStatus(variant).color === 'error',
            'variant-inactive': !variant.is_active
          }"
        >
          <!-- Variant Header -->
          <VCardTitle class="pa-4 pb-2">
            <div class="d-flex align-center gap-3">
              <VAvatar
                :image="product?.image_url"
                size="48"
                class="flex-shrink-0"
              >
                <VIcon v-if="!product?.image_url" icon="mdi-package-variant" />
              </VAvatar>
              <div class="flex-grow-1 min-width-0">
                <h3 class="text-h6 font-weight-bold text-truncate">
                  {{ variant.name }}
                </h3>
                <p class="text-caption text-medium-emphasis mb-0">
                  {{ variant.sku }}
                </p>
              </div>
              <VChip
                :color="variant.is_active ? 'success' : 'error'"
                size="small"
                variant="elevated"
              >
                {{ variant.is_active ? 'Aktif' : 'Nonaktif' }}
              </VChip>
            </div>
          </VCardTitle>

          <VDivider />

          <!-- Variant Info -->
          <VCardText class="pa-4">
            <!-- Price Info -->
            <div class="mb-4">
              <div class="d-flex justify-between align-center mb-2">
                <span class="text-caption text-medium-emphasis">Harga Jual</span>
                <span class="text-h6 font-weight-bold">{{ formatRupiah(variant.price) }}</span>
              </div>
              <div v-if="variant.cost_from_composition" class="d-flex justify-between align-center mb-2">
                <span class="text-caption text-medium-emphasis">HPP</span>
                <span class="text-body-2">{{ formatRupiah(variant.cost_from_composition) }}</span>
              </div>
              <div v-if="variant.cost_from_composition" class="d-flex justify-between align-center">
                <span class="text-caption text-medium-emphasis">Margin</span>
                <VChip
                  :color="getMarginColor(variant.price, variant.cost_from_composition)"
                  size="x-small"
                  variant="elevated"
                >
                  {{ getMarginText(variant.price, variant.cost_from_composition) }}
                </VChip>
              </div>
            </div>

            <!-- Stock Info -->
            <div class="mb-4">
              <div class="d-flex justify-between align-center mb-2">
                <span class="text-caption text-medium-emphasis">Stok Saat Ini</span>
                <VChip
                  :color="getStockStatus(variant).color"
                  size="small"
                  variant="elevated"
                >
                  {{ variant.current_stock }}
                </VChip>
              </div>
              <div class="d-flex justify-between align-center">
                <span class="text-caption text-medium-emphasis">Min. Stok</span>
                <span class="text-body-2">{{ variant.min_stock }}</span>
              </div>
            </div>

            <!-- Composition Info -->
            <div class="mb-4">
              <div class="d-flex justify-between align-center">
                <span class="text-caption text-medium-emphasis">Komposisi</span>
                <VChip
                  v-if="variant.variant_items_count > 0"
                  color="info"
                  size="small"
                  variant="elevated"
                  @click="openCompositionDialog(variant)"
                  class="cursor-pointer"
                >
                  {{ variant.variant_items_count }} Item
                </VChip>
                <VChip
                  v-else
                  color="warning"
                  size="small"
                  variant="outlined"
                >
                  Belum ada
                </VChip>
              </div>
            </div>

            <!-- Stock Status Alert -->
            <VAlert
              v-if="getStockStatus(variant).color !== 'success'"
              :type="getStockStatus(variant).color as 'error' | 'warning'"
              variant="tonal"
              density="compact"
              class="mb-4"
            >
              {{ getStockStatus(variant).text }}
            </VAlert>
          </VCardText>

          <VDivider />

          <!-- Actions -->
          <VCardActions class="pa-4">
            <VTooltip text="Lihat Detail">
              <template #activator="{ props: tooltipProps }">
                <VBtn
                  v-bind="tooltipProps"
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  color="info"
                  @click="openDetailDialog(variant)"
                />
              </template>
            </VTooltip>

            <VTooltip text="Kelola Komposisi">
              <template #activator="{ props: tooltipProps }">
                <VBtn
                  v-bind="tooltipProps"
                  icon="mdi-puzzle"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="openCompositionDialog(variant)"
                />
              </template>
            </VTooltip>

            <VTooltip text="Edit Variant">
              <template #activator="{ props: tooltipProps }">
                <VBtn
                  v-bind="tooltipProps"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  color="secondary"
                  @click="openEditDialog(variant)"
                />
              </template>
            </VTooltip>

            <VSpacer />

            <VTooltip text="Hapus Variant">
              <template #activator="{ props: tooltipProps }">
                <VBtn
                  v-bind="tooltipProps"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="emit('delete-variant', variant)"
                />
              </template>
            </VTooltip>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Form Dialog -->
    <VariantFormDialog
      v-if="formDialog && product"
      v-model="formDialog"
      :variant="editMode ? selectedVariant : null"
      :products="product ? [product] : []"
      :product-id="product?.id"
      @save="handleSave"
      ref="variantFormDialogRef"
    />

    <!-- Composition Dialog -->
    <VariantCompositionDialog
      v-model="compositionDialog"
      :variant="selectedVariant"
      @save="handleCompositionSave"
    />

    <!-- Detail Dialog -->
    <VariantDetailDialog
      v-model="detailDialog"
      :variant="selectedVariant"
    />

    <!-- Variant Management Dialog -->
    <VariantManagementDialog
      v-model="managementDialog"
      :product="product"
      @save="handleManagementSave"
    />
  </div>
</template>

<style scoped lang="scss">
.variant-cards {
  .variant-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    &.border-error {
      border-color: rgb(var(--v-theme-error));
    }

    &.variant-inactive {
      opacity: 0.7;
    }
  }

  .cursor-pointer {
    cursor: pointer;
  }
}
</style>
