<template>
  <div class="variant-table">
    <!-- Header -->
    <VCard class="mb-6">
      <VCardTitle class="d-flex align-center justify-between">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="mdi-package-variant"
            class="coffee-icon"
          />
          <div>
            <h2 class="text-h5 font-weight-bold">Variant Produk - {{ product?.name }}</h2>
            <p class="text-body-2 text-medium-emphasis">
              Kelola variant dan komposisi untuk produk {{ product?.name }}
            </p>
          </div>
          <VChip
            v-if="variants.length"
            size="small"
            color="primary"
            variant="tonal"
          >
            {{ variants.length }} variant
          </VChip>
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
    <VCard v-else-if="filteredVariants.length === 0" class="text-center pa-12">
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
    </VCard>

    <!-- Variant Table -->
    <VCard v-else>
      <VDataTable
        :headers="tableHeaders"
        :items="filteredVariants"
        :loading="loading"
        item-value="id"
        show-select
        class="text-no-wrap"
      >
        <!-- Name Column -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3">
            <VAvatar
              :image="product?.image_url"
              size="38"
              variant="tonal"
              color="primary"
            >
              <VIcon
                v-if="!product?.image_url"
                icon="mdi-package-variant"
                size="20"
              />
            </VAvatar>
            <div>
              <h6 class="text-h6 font-weight-medium">
                {{ item.name }}
              </h6>
              <span class="text-sm text-medium-emphasis">{{ item.sku }}</span>
            </div>
          </div>
        </template>

        <!-- Price Column -->
        <template #item.price="{ item }">
          <span class="text-high-emphasis font-weight-medium">
            {{ formatRupiah(item.price) }}
          </span>
        </template>

        <!-- Stock Column -->
        <template #item.current_stock="{ item }">
          <VChip
            :color="getStockStatus(item).color"
            size="small"
            variant="tonal"
          >
            {{ item.current_stock || 0 }} unit
          </VChip>
        </template>

        <!-- Status Column -->
        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
          </VChip>
        </template>

        <!-- Composition Column -->
        <template #item.composition="{ item }">
          <VChip
            v-if="item.variant_items_count > 0"
            color="success"
            size="small"
            variant="tonal"
            @click="openCompositionDialog(item)"
            class="cursor-pointer"
          >
            {{ item.variant_items_count }} Item
          </VChip>
          <VChip
            v-else
            color="warning"
            size="small"
            variant="tonal"
          >
            Belum ada
          </VChip>
        </template>

        <!-- Actions Column -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <!-- View Detail -->
            <VTooltip text="Lihat Detail">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  color="info"
                  @click="openDetailDialog(item)"
                />
              </template>
            </VTooltip>

            <!-- Manage Composition -->
            <VTooltip text="Kelola Komposisi">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon="mdi-puzzle"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="openCompositionDialog(item)"
                />
              </template>
            </VTooltip>

            <!-- Edit -->
            <VTooltip text="Edit Variant">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  color="secondary"
                  @click="openEditDialog(item)"
                />
              </template>
            </VTooltip>

            <!-- Delete -->
            <VTooltip text="Hapus Variant">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="emit('delete-variant', item)"
                />
              </template>
            </VTooltip>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Form Dialog -->
    <VariantFormDialog
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

    <!-- Management Dialog -->
    <VariantManagementDialog
      v-model="managementDialog"
      :product="product"
      @refresh="emit('refresh')"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import VariantFormDialog from './VariantFormDialog.vue'
import VariantCompositionDialog from './VariantCompositionDialog.vue'
import VariantDetailDialog from './VariantDetailDialog.vue'
import VariantManagementDialog from './VariantManagementDialog.vue'

interface Variant {
  id: number
  id_variant: number
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
  id_product?: number
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

// Table headers
const tableHeaders = [
  { title: 'Variant', key: 'name', sortable: true },
  { title: 'Harga', key: 'price', sortable: true },
  { title: 'Stok', key: 'current_stock', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Komposisi', key: 'composition', sortable: false },
  { title: 'Aksi', key: 'actions', sortable: false }
]

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

// Filtered variants
const filteredVariants = computed(() => {
  let filtered = [...props.variants]

  // Apply search filter
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(variant => 
      variant.name.toLowerCase().includes(search) ||
      variant.sku.toLowerCase().includes(search)
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
  if (filters.value.sortBy) {
    const [field, direction] = filters.value.sortBy.split('_')
    filtered.sort((a, b) => {
      let aVal, bVal
      
      switch (field) {
        case 'name':
          aVal = a.name.toLowerCase()
          bVal = b.name.toLowerCase()
          break
        case 'price':
          aVal = a.price
          bVal = b.price
          break
        case 'stock':
          aVal = a.current_stock
          bVal = b.current_stock
          break
        default:
          return 0
      }
      
      if (direction === 'asc') {
        return aVal < bVal ? -1 : aVal > bVal ? 1 : 0
      } else {
        return aVal > bVal ? -1 : aVal < bVal ? 1 : 0
      }
    })
  }
  
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
  return { text: 'Stok Aman', color: 'success', value: 'safe' }
}

const formatRupiah = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: null,
    stockStatus: '',
    sortBy: 'name_asc'
  }
}

// Dialog handlers
const openCreateDialog = () => {
  selectedVariant.value = null
  editMode.value = false
  formDialog.value = true
}

const openEditDialog = (variant: Variant) => {
  console.log('🔵 ProductVariantTable - openEditDialog called with variant:', variant)
  selectedVariant.value = { ...variant }
  editMode.value = true
  formDialog.value = true
  console.log('🔵 ProductVariantTable - selectedVariant set to:', selectedVariant.value)
  console.log('🔵 ProductVariantTable - editMode:', editMode.value)
  console.log('🔵 ProductVariantTable - formDialog:', formDialog.value)
}

const openDetailDialog = (variant: Variant) => {
  selectedVariant.value = variant
  detailDialog.value = true
}

const openCompositionDialog = (variant: Variant) => {
  selectedVariant.value = variant
  compositionDialog.value = true
}

const openManagementDialog = () => {
  managementDialog.value = true
}

const handleSave = () => {
  formDialog.value = false
  emit('refresh')
}

const handleCompositionSave = () => {
  compositionDialog.value = false
  emit('refresh')
}
</script>

<style scoped>
.variant-table {
  .cursor-pointer {
    cursor: pointer;
  }
}
</style>
