<template>
  <VCard class="variant-table-card">
    <!-- Table Header with Actions -->
    <VCardText class="pb-0">
      <VRow class="align-center">
        <VCol cols="12" md="6">
          <VTextField
            v-model="searchQuery"
            label="Cari variant..."
            prepend-inner-icon="tabler-search"
            variant="outlined"
            density="compact"
            clearable
            class="search-field"
            @update:modelValue="handleSearch"
          />
        </VCol>
        <VCol cols="12" md="6" class="text-md-end">
          <div class="d-flex gap-2 justify-end">
            <VBtn
              v-if="canCreateEdit"
              @click="emit('openBulkCreate')"
              variant="outlined"
              color="info"
              prepend-icon="tabler-stack-2"
              class="text-none"
            >
              Buat Sekaligus
            </VBtn>
            <VBtn
              v-if="canCreateEdit"
              @click="emit('openCreate')"
              variant="flat"
              color="primary"
              prepend-icon="tabler-plus"
              class="text-none"
            >
              Tambah Variant
            </VBtn>
          </div>
        </VCol>
      </VRow>
    </VCardText>

    <!-- Filters Section -->
    <VCardText class="py-2">
      <VRow class="align-center">
        <VCol cols="12" md="3">
          <VSelect
            v-model="localFilters.product_id"
            :items="productOptions"
            item-title="name"
            item-value="id"
            label="Filter Produk"
            variant="outlined"
            density="compact"
            clearable
            @update:modelValue="handleFilterChange"
          />
        </VCol>
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.status"
            :items="statusOptions"
            label="Status"
            variant="outlined"
            density="compact"
            clearable
            @update:modelValue="handleFilterChange"
          />
        </VCol>
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.stock_status"
            :items="stockStatusOptions"
            label="Status Stok"
            variant="outlined"
            density="compact"
            clearable
            @update:modelValue="handleFilterChange"
          />
        </VCol>
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.sort_by"
            :items="sortOptions"
            label="Urutkan"
            variant="outlined"
            density="compact"
            @update:modelValue="handleFilterChange"
          />
        </VCol>
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.sort_order"
            :items="sortOrderOptions"
            label="Urutan"
            variant="outlined"
            density="compact"
            @update:modelValue="handleFilterChange"
          />
        </VCol>
        <VCol cols="12" md="1">
          <VBtn
            @click="clearFilters"
            icon
            variant="outlined"
            color="secondary"
            size="small"
          >
            <VIcon icon="tabler-filter-off" />
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>

    <!-- Data Table -->
    <VDataTableServer
      v-model:items-per-page="localItemsPerPage"
      :headers="headers"
      :items="variantsList"
      :items-length="totalItems"
      :loading="loading"
      :page="currentPage"
      class="variant-data-table"
      density="compact"
      @update:page="handlePageChange"
      @update:items-per-page="handleItemsPerPageChange"
    >
      <!-- Product Column -->
      <template #item.product="{ item }">
        <div class="d-flex align-center">
          <VAvatar
            size="32"
            class="me-2"
            :color="item.product?.name ? 'primary' : 'grey'"
            variant="tonal"
          >
            <VIcon 
              :icon="item.product?.name ? 'tabler-coffee' : 'tabler-package'"
              size="18"
            />
          </VAvatar>
          <div>
            <div class="font-weight-medium">
              {{ item.product?.name || 'Unknown Product' }}
            </div>
            <div class="text-caption text-medium-emphasis">
              ID: {{ item.id_product }}
            </div>
          </div>
        </div>
      </template>

      <!-- Variant Name Column -->
      <template #item.name="{ item }">
        <div>
          <div class="font-weight-medium">{{ item.name }}</div>
          <div class="text-caption text-medium-emphasis">
            SKU: {{ item.sku || 'N/A' }}
          </div>
        </div>
      </template>

      <!-- Variant Attributes Column -->
      <template #item.variant_values="{ item }">
        <div class="d-flex flex-wrap gap-1">
          <VChip
            v-for="(value, key) in item.variant_values"
            :key="key"
            size="x-small"
            color="info"
            variant="outlined"
            class="text-capitalize"
          >
            {{ key }}: {{ value }}
          </VChip>
        </div>
      </template>

      <!-- Price Column -->
      <template #item.price="{ item }">
        <div>
          <div class="font-weight-bold text-success">
            {{ formatPrice(item.price) }}
          </div>
          <div 
            v-if="item.cost_price && item.cost_price > 0"
            class="text-caption text-medium-emphasis"
          >
            Modal: {{ formatPrice(item.cost_price) }}
          </div>
        </div>
      </template>

      <!-- Profit Margin Column -->
      <template #item.profit_margin="{ item }">
        <div v-if="item.profit_margin !== null" class="profit-margin-container">
          <VChip
            :color="getProfitMarginColor(item.profit_margin)"
            size="small"
            variant="tonal"
            class="profit-margin-chip"
          >
            {{ item.profit_margin }}%
          </VChip>
        </div>
        <span v-else class="text-medium-emphasis">N/A</span>
      </template>

      <!-- Stock Info Column -->
      <template #item.stock_info="{ item }">
        <div>
          <VChip
            :color="getStockStatusColor(item.stock_info)"
            size="small"
            variant="tonal"
          >
            {{ item.stock_info.current_stock || 0 }}
          </VChip>
          <div class="text-caption text-medium-emphasis mt-1">
            Min: {{ item.stock_info.reorder_level || 0 }}
          </div>
        </div>
      </template>

      <!-- Status Column -->
      <template #item.active="{ item }">
        <VChip
          :color="item.active ? 'success' : 'warning'"
          size="small"
          variant="tonal"
        >
          {{ item.active ? 'Aktif' : 'Nonaktif' }}
        </VChip>
      </template>

      <!-- Actions Column -->
      <template #item.actions="{ item }">
        <div class="d-flex gap-1">
          <VBtn
            v-if="canCreateEdit"
            @click="emit('edit', item)"
            icon
            variant="text"
            color="primary"
            size="small"
          >
            <VIcon 
              icon="tabler-edit"
              size="18"
            />
          </VBtn>
          <VBtn
            v-if="canCreateEdit"
            @click="emit('delete', item)"
            icon
            variant="text"
            color="error"
            size="small"
          >
            <VIcon 
              icon="tabler-trash"
              size="18"
            />
          </VBtn>
          <VBtn
            @click="viewDetails(item)"
            icon
            variant="text"
            color="info"
            size="small"
          >
            <VIcon 
              icon="tabler-eye"
              size="18"
            />
          </VBtn>
        </div>
      </template>

      <!-- No Data -->
      <template #no-data>
        <div class="text-center py-8">
          <VIcon 
            icon="tabler-package-off"
            size="48"
            class="text-medium-emphasis mb-2"
          />
          <div class="text-h6 text-medium-emphasis">
            Tidak ada data variant
          </div>
          <div class="text-body-2 text-medium-emphasis mb-4">
            {{ searchQuery ? 'Tidak ditemukan variant yang sesuai dengan pencarian' : 'Belum ada variant yang dibuat' }}
          </div>
          <VBtn
            v-if="canCreateEdit && !searchQuery"
            @click="emit('openCreate')"
            variant="flat"
            color="primary"
            prepend-icon="tabler-plus"
          >
            Tambah Variant Pertama
          </VBtn>
        </div>
      </template>

      <!-- Loading -->
      <template #loading>
        <div class="text-center py-8">
          <VProgressCircular
            indeterminate
            color="primary"
            size="48"
          />
          <div class="text-body-2 text-medium-emphasis mt-2">
            Memuat data variant...
          </div>
        </div>
      </template>
    </VDataTableServer>
  </VCard>

  <!-- Variant Details Dialog -->
  <VDialog
    v-model="detailsDialog"
    max-width="600"
    class="variant-details-dialog"
  >
    <VCard v-if="selectedVariant">
      <VCardTitle class="dialog-title d-flex align-center">
        <VIcon 
          icon="tabler-info-circle"
          class="me-3"
        />
        Detail Variant
        <VSpacer />
        <VBtn
          @click="detailsDialog = false"
          icon
          variant="text"
          size="small"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>
      
      <VDivider />
      
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VList density="compact">
              <VListItem>
                <VListItemTitle class="text-caption text-medium-emphasis">
                  Produk
                </VListItemTitle>
                <VListItemSubtitle class="font-weight-medium">
                  {{ selectedVariant.product?.name }}
                </VListItemSubtitle>
              </VListItem>
              
              <VListItem>
                <VListItemTitle class="text-caption text-medium-emphasis">
                  Nama Variant
                </VListItemTitle>
                <VListItemSubtitle class="font-weight-medium">
                  {{ selectedVariant.name }}
                </VListItemSubtitle>
              </VListItem>
              
              <VListItem>
                <VListItemTitle class="text-caption text-medium-emphasis">
                  SKU
                </VListItemTitle>
                <VListItemSubtitle class="font-weight-medium">
                  {{ selectedVariant.sku || 'Tidak ada' }}
                </VListItemSubtitle>
              </VListItem>
              
              <VListItem>
                <VListItemTitle class="text-caption text-medium-emphasis">
                  Barcode
                </VListItemTitle>
                <VListItemSubtitle class="font-weight-medium">
                  {{ selectedVariant.barcode || 'Tidak ada' }}
                </VListItemSubtitle>
              </VListItem>
            </VList>
          </VCol>
          
          <VCol cols="12" md="6">
            <VList density="compact">
              <VListItem>
                <VListItemTitle class="text-caption text-medium-emphasis">
                  Harga Jual
                </VListItemTitle>
                <VListItemSubtitle class="font-weight-medium text-success">
                  {{ formatPrice(selectedVariant.price) }}
                </VListItemSubtitle>
              </VListItem>
              
              <VListItem>
                <VListItemTitle class="text-caption text-medium-emphasis">
                  Harga Modal
                </VListItemTitle>
                <VListItemSubtitle class="font-weight-medium">
                  {{ selectedVariant.cost_price ? formatPrice(selectedVariant.cost_price) : 'Tidak diset' }}
                </VListItemSubtitle>
              </VListItem>
              
              <VListItem>
                <VListItemTitle class="text-caption text-medium-emphasis">
                  Margin Keuntungan
                </VListItemTitle>
                <VListItemSubtitle>
                  <VChip
                    v-if="selectedVariant.profit_margin !== null"
                    :color="getProfitMarginColor(selectedVariant.profit_margin)"
                    size="small"
                    variant="tonal"
                    class="profit-margin-chip"
                  >
                    {{ selectedVariant.profit_margin }}%
                  </VChip>
                  <span v-else>Tidak dapat dihitung</span>
                </VListItemSubtitle>
              </VListItem>
              
              <VListItem>
                <VListItemTitle class="text-caption text-medium-emphasis">
                  Status
                </VListItemTitle>
                <VListItemSubtitle>
                  <VChip
                    :color="selectedVariant.active ? 'success' : 'warning'"
                    size="small"
                    variant="tonal"
                  >
                    {{ selectedVariant.active ? 'Aktif' : 'Nonaktif' }}
                  </VChip>
                </VListItemSubtitle>
              </VListItem>
            </VList>
          </VCol>
          
          <!-- Variant Attributes -->
          <VCol cols="12">
            <VDivider class="my-4" />
            <div class="text-subtitle-2 mb-2">Atribut Variant:</div>
            <div class="d-flex flex-wrap gap-2">
              <VChip
                v-for="(value, key) in selectedVariant.variant_values"
                :key="key"
                color="primary"
                variant="outlined"
              >
                {{ key }}: {{ value }}
              </VChip>
            </div>
          </VCol>
          
          <!-- Stock Information -->
          <VCol cols="12">
            <VDivider class="my-4" />
            <div class="text-subtitle-2 mb-2">Informasi Stok:</div>
            <VRow>
              <VCol cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Stok Saat Ini</div>
                <div class="text-h6">{{ selectedVariant.stock_info?.current_stock || 0 }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Batas Minimum</div>
                <div class="text-h6">{{ selectedVariant.stock_info?.reorder_level || 0 }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Stok Tersedia</div>
                <div class="text-h6">{{ selectedVariant.stock_info?.available_stock || 0 }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-medium-emphasis">Stok Dipending</div>
                <div class="text-h6">{{ selectedVariant.stock_info?.reserved_stock || 0 }}</div>
              </VCol>
            </VRow>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import type { Variant, VariantFilters } from '@/utils/api/VariantsApi'
import { ref, watch } from 'vue'

// Define interfaces for props
interface Product {
  id: number
  name: string
}

interface Props {
  variantsList: Variant[]
  loading?: boolean
  totalItems?: number
  currentPage?: number
  itemsPerPage?: number
  filters: VariantFilters
  productOptions?: Product[]
  canCreateEdit?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  totalItems: 0,
  currentPage: 1,
  itemsPerPage: 15,
  productOptions: () => [],
  canCreateEdit: true
})

const emit = defineEmits<{
  'openCreate': []
  'openBulkCreate': []
  'edit': [variant: Variant]
  'delete': [variant: Variant]
  'pageChange': [page: number]
  'itemsPerPageChange': [itemsPerPage: number]
  'filtersUpdate': [filters: Partial<VariantFilters>]
  'clearFilters': []
}>()

// Local state
const searchQuery = ref(props.filters.search || '')
const detailsDialog = ref(false)
const selectedVariant = ref<Variant | null>(null)

// Local filters for immediate UI feedback
const localFilters = ref<VariantFilters>({ ...props.filters })
const localItemsPerPage = ref(props.itemsPerPage)

// Table headers
const headers = [
  { title: 'Produk', key: 'product', sortable: false },
  { title: 'Nama Variant', key: 'name', sortable: true },
  { title: 'Atribut', key: 'variant_values', sortable: false },
  { title: 'Harga', key: 'price', sortable: true },
  { title: 'Margin', key: 'profit_margin', sortable: false },
  { title: 'Stok', key: 'stock_info', sortable: false },
  { title: 'Status', key: 'active', sortable: true },
  { title: 'Aksi', key: 'actions', sortable: false, width: '120px' }
]

// Filter options
const statusOptions = [
  { title: 'Aktif', value: 'active' },
  { title: 'Nonaktif', value: 'inactive' }
]

const stockStatusOptions = [
  { title: 'Stok Rendah', value: 'low_stock' },
  { title: 'Stok Tersedia', value: 'in_stock' }
]

const sortOptions = [
  { title: 'Nama', value: 'name' },
  { title: 'Harga', value: 'price' },
  { title: 'Tanggal Dibuat', value: 'created_at' },
  { title: 'SKU', value: 'sku' }
]

const sortOrderOptions = [
  { title: 'A-Z / Terkecil', value: 'asc' },
  { title: 'Z-A / Terbesar', value: 'desc' }
]

// Utility methods
const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(price)
}

const getProfitMarginColor = (margin: number | undefined): string => {
  if (margin === undefined || margin === null) return 'grey'
  if (margin < 10) return 'error'
  if (margin < 20) return 'warning'
  return 'success'
}

const getStockStatusColor = (stockInfo: any): string => {
  const currentStock = stockInfo?.current_stock || 0
  const reorderLevel = stockInfo?.reorder_level || 0
  
  if (currentStock === 0) return 'error'
  if (currentStock <= reorderLevel) return 'warning'
  return 'success'
}

const viewDetails = (variant: Variant) => {
  selectedVariant.value = variant
  detailsDialog.value = true
}

// Event handlers
const handleSearch = (query: string) => {
  localFilters.value.search = query
  handleFilterChange()
}

const handleFilterChange = () => {
  emit('filtersUpdate', { ...localFilters.value })
}

const clearFilters = () => {
  searchQuery.value = ''
  localFilters.value = {
    search: '',
    product_id: undefined,
    status: undefined,
    variant_filters: {},
    stock_status: undefined,
    sort_by: 'created_at',
    sort_order: 'desc'
  }
  emit('clearFilters')
}

const handlePageChange = (page: number) => {
  emit('pageChange', page)
}

const handleItemsPerPageChange = (itemsPerPage: number) => {
  localItemsPerPage.value = itemsPerPage
  emit('itemsPerPageChange', itemsPerPage)
}

// Watch for external filter changes
watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...newFilters }
  searchQuery.value = newFilters.search || ''
}, { deep: true })

watch(() => props.itemsPerPage, (newItemsPerPage) => {
  localItemsPerPage.value = newItemsPerPage
})
</script>

<style lang="scss" scoped>
.variant-table-card {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.search-field {
  max-width: 400px;
}

/* Profit Margin Styling */
.profit-margin-container {
  margin: 8px 0;
  text-align: center;
}

.profit-margin-chip {
  border-radius: 16px 16px 8px 8px !important;
  margin: 2px 0;
  font-weight: 600;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: all 0.2s ease;
}

.profit-margin-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.variant-data-table {
  :deep(.v-data-table__wrapper) {
    border-radius: 8px;
  }
  
  :deep(.v-data-table-header) {
    background-color: rgba(var(--v-theme-surface), 0.8);
  }
  
  :deep(.v-data-table__tr:first-child) {
    border-radius: 12px 12px 0 0;
  }
  
  :deep(.v-data-table__td) {
    padding: 12px 8px;
  }
  
  :deep(.v-data-table__th) {
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.8) !important;
  }
}

// Variant details dialog
.variant-details-dialog {
  :deep(.v-dialog) {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  }
}

// Coffee theme enhancements
.v-btn {
  border-radius: 8px;
  text-transform: none;
}

.v-chip {
  border-radius: 6px;
}

.v-card {
  border-radius: 12px;
}

.v-text-field {
  :deep(.v-field) {
    border-radius: 8px;
  }
}

// Action buttons
.v-btn[variant="text"] {
  min-width: auto;
  
  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.08);
  }
}

// Price styling
.text-success {
  color: rgb(var(--v-theme-success)) !important;
}

// Responsive adjustments
@media (max-width: 960px) {
  .variant-data-table {
    :deep(.v-data-table__td) {
      padding: 8px 4px;
    }
  }
}
</style>
