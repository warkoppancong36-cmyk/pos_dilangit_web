<script setup lang="ts">
import { computed, defineEmits, ref, watch } from 'vue'
import { formatRupiah } from '@/@core/utils/formatters'
import ProductCompositionDialog from './ProductCompositionDialog.vue'
import ProductSelectionList from './ProductSelectionList.vue'

interface ProductItem {
  id_product_item: string
  quantity_needed: number
  unit: string
  is_critical: boolean
  formatted_total_cost?: string
  item?: {
    id: string
    name: string
    inventory?: {
      current_stock: number
    }
  }
  product?: {
    id_product: string
    id?: string
    name: string
    sku?: string
    price?: number
    image_url?: string
  }
}

interface Props {
  productItems: ProductItem[]
  loading?: boolean
}

interface Emits {
  (e: 'refresh'): void
  (e: 'edit-composition', product: any): void
  (e: 'view-details', product: any): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Dialog state
const compositionDialog = ref(false)
const detailDialog = ref(false)
const createCompositionDialog = ref(false)
const selectedProduct = ref(null)
const selectedProductItems = ref([])
const selectedProductForDetail = ref(null)

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(12)

// Filter state
const filters = ref({
  search: '',
  stockStatus: '',
  itemType: '',
  criticalOnly: false,
  sortBy: 'name_asc'
})

// Filter options
const stockStatusOptions = [
  { title: 'Semua Status', value: '' },
  { title: 'Stok Aman', value: 'safe' },
  { title: 'Stok Kritis', value: 'critical' },
  { title: 'Stok Habis', value: 'out' }
]

const sortOptions = [
  { title: 'Nama A-Z', value: 'name_asc' },
  { title: 'Nama Z-A', value: 'name_desc' },
  { title: 'Harga Tertinggi', value: 'price_desc' },
  { title: 'Harga Terendah', value: 'price_asc' },
  { title: 'Item Terbanyak', value: 'items_desc' },
  { title: 'Item Tersedikit', value: 'items_asc' }
]

// Open composition dialog
const openCompositionDialog = (product: any) => {
  selectedProduct.value = product
  selectedProductItems.value = product.items || []
  compositionDialog.value = true
}

// Open create composition dialog (select product first)
const openCreateCompositionDialog = () => {
  createCompositionDialog.value = true
}

// Handle product selection for new composition
const handleProductSelect = (product: any) => {
  selectedProduct.value = product
  selectedProductItems.value = []
  createCompositionDialog.value = false
  compositionDialog.value = true
}

// Handle composition save and refresh
const handleCompositionSave = () => {
  emit('refresh')
  resetPage() // Reset to first page after refresh
}

// Open detail dialog
const openDetailDialog = (product: any) => {
  selectedProductForDetail.value = product
  detailDialog.value = true
}

// Clear all filters
const clearAllFilters = () => {
  filters.value = {
    search: '',
    stockStatus: '',
    itemType: '',
    criticalOnly: false,
    sortBy: 'name_asc'
  }
}

// Check if any filter is active
const hasActiveFilters = computed(() => {
  return !!(filters.value.search || filters.value.stockStatus || filters.value.itemType || filters.value.criticalOnly || filters.value.sortBy !== 'name_asc')
})

// Count active filters
const activeFiltersCount = computed(() => {
  let count = 0
  if (filters.value.search) count++
  if (filters.value.stockStatus) count++
  if (filters.value.itemType) count++
  if (filters.value.criticalOnly) count++
  if (filters.value.sortBy !== 'name_asc') count++
  return count
})

// Group product items by product with filtering and sorting
const compositionData = computed(() => {
  const grouped = new Map()
  
  props.productItems.forEach(item => {
    if (!item.product) return
    
    const productKey = item.product.id_product || item.product.id
    if (!grouped.has(productKey)) {
      grouped.set(productKey, {
        ...item.product,
        items: [],
        totalItems: 0
      })
    }
    
    grouped.get(productKey).items.push(item)
    grouped.get(productKey).totalItems++
  })
  
  let filteredData = Array.from(grouped.values())
  
  // Apply search filter
  if (filters.value.search) {
    const searchTerm = filters.value.search.toLowerCase()
    filteredData = filteredData.filter(product => 
      product.name.toLowerCase().includes(searchTerm) ||
      (product.sku && product.sku.toLowerCase().includes(searchTerm)) ||
      product.items.some((item: ProductItem) => 
        item.item?.name.toLowerCase().includes(searchTerm)
      )
    )
  }
  
  // Apply stock status filter
  if (filters.value.stockStatus) {
    filteredData = filteredData.filter(product => {
      const stockStatus = getStockStatus(product)
      switch (filters.value.stockStatus) {
        case 'safe':
          return stockStatus.color === 'success'
        case 'critical':
          return stockStatus.color === 'warning'
        case 'out':
          return stockStatus.color === 'error'
        default:
          return true
      }
    })
  }
  
  // Apply critical items filter
  if (filters.value.criticalOnly) {
    filteredData = filteredData.filter(product =>
      product.items.some((item: ProductItem) => item.is_critical)
    )
  }
  
  // Apply sorting
  filteredData.sort((a, b) => {
    switch (filters.value.sortBy) {
      case 'name_asc':
        return a.name.localeCompare(b.name)
      case 'name_desc':
        return b.name.localeCompare(a.name)
      case 'price_asc':
        return (a.price || 0) - (b.price || 0)
      case 'price_desc':
        return (b.price || 0) - (a.price || 0)
      case 'items_asc':
        return a.totalItems - b.totalItems
      case 'items_desc':
        return b.totalItems - a.totalItems
      default:
        return a.name.localeCompare(b.name)
    }
  })
  
  return filteredData
})

// Pagination computed properties
const totalPages = computed(() => Math.ceil(compositionData.value.length / itemsPerPage.value))
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return compositionData.value.slice(start, end)
})

const getStockStatus = (product: any) => {
  // Cek stok dari semua item dalam komposisi
  let hasOutOfStock = false
  let hasLowStock = false
  
  product.items.forEach((item: ProductItem) => {
    if (item.item?.inventory) {
      const currentStock = item.item.inventory.current_stock || 0
      const needed = item.quantity_needed || 0
      
      if (currentStock <= 0) {
        hasOutOfStock = true
      } else if (currentStock < needed || item.is_critical) {
        hasLowStock = true
      }
    }
  })

  if (hasOutOfStock) {
    return { text: 'Stok Habis', color: 'error' }
  }
  if (hasLowStock) {
    return { text: 'Stok Kritis', color: 'warning' }
  }
  return { text: 'Stok Aman', color: 'success' }
}

// Helper functions for detail dialog
const getProductComposition = (productId: string) => {
  const product = compositionData.value.find(p => p.id_product === productId || p.id === productId)
  return product?.items || []
}

const getItemStockColor = (item: any) => {
  if (!item?.inventory) return 'grey'
  const stock = item.inventory.current_stock || 0
  
  if (stock <= 0) return 'error'
  if (stock <= 10) return 'warning' // Threshold bisa disesuaikan
  return 'success'
}

const getItemStockStatus = (item: any) => {
  if (!item?.inventory) return 'Tidak ada data'
  const stock = item.inventory.current_stock || 0
  
  if (stock <= 0) return 'Habis'
  if (stock <= 10) return 'Rendah'
  return 'Aman'
}

const getItemStockClass = (item: any) => {
  const color = getItemStockColor(item)
  return {
    'text-error': color === 'error',
    'text-warning': color === 'warning',
    'text-success': color === 'success',
    'text-grey': color === 'grey'
  }
}

const getItemsText = (totalItems: number) => {
  return `Item Komposisi (${totalItems})`
}

// Pagination functions
const onPageChange = (page: number) => {
  currentPage.value = page
}

// Reset page when filters change
const resetPage = () => {
  currentPage.value = 1
}

// Watch for filter changes to reset page
watch(() => [filters.value.search, filters.value.stockStatus, filters.value.criticalOnly, filters.value.sortBy], () => {
  resetPage()
})
</script>

<template>
  <div class="composition-cards">
    <!-- Header -->
    <VCard class="mb-6">
      <VCardTitle class="d-flex align-center justify-between">
        <div>
          <h2 class="text-h5 font-weight-bold">Komposisi Produk</h2>
          <p class="text-body-2 text-medium-emphasis">
            Kelola komposisi bahan untuk setiap produk
          </p>
        </div>
        <div class="d-flex gap-2">
          <VBtn
            color="primary"
            prepend-icon="mdi-plus"
            @click="openCreateCompositionDialog"
          >
            Tambah Komposisi
          </VBtn>
          <VBtn
            variant="outlined"
            prepend-icon="mdi-refresh"
            @click="emit('refresh')"
            :loading="loading"
          >
            Refresh
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
              label="Cari Produk"
              placeholder="Nama produk, SKU, atau item..."
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-magnify"
              hide-details
              clearable
            />
          </VCol>
          
          <VCol cols="12" md="3">
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
          
          <VCol cols="12" md="3" class="d-flex align-center">
            <VCheckbox
              v-model="filters.criticalOnly"
              label="Hanya Item Kritis"
              density="compact"
              hide-details
            />
          </VCol>
        </VRow>
        
        <!-- Second row for additional controls -->
        <VRow class="mt-3">
          <VCol cols="12" md="6" class="d-flex align-center gap-2">
            <VBtn
              variant="outlined"
              color="grey"
              size="small"
              prepend-icon="mdi-filter-off"
              @click="clearAllFilters"
              :disabled="!hasActiveFilters"
            >
              Reset Filter
            </VBtn>
            
            <VChip
              v-if="hasActiveFilters"
              color="primary"
              variant="tonal"
              size="small"
            >
              {{ activeFiltersCount }} filter aktif
            </VChip>
          </VCol>
          
          <VCol cols="12" md="6" class="d-flex align-center justify-end">
            <VChip
              :color="compositionData.length > 0 ? 'success' : 'default'"
              variant="tonal"
              prepend-icon="mdi-package-variant"
            >
              {{ compositionData.length }} Produk dengan Komposisi
            </VChip>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Error State -->
    <VAlert
      v-if="!loading && compositionData.length === 0 && hasActiveFilters"
      type="info"
      variant="outlined"
      class="mb-6"
      title="Tidak ada hasil"
      text="Tidak ada produk yang sesuai dengan filter yang dipilih. Coba ubah filter pencarian."
    />

    <!-- Loading State -->
    <div v-if="loading" class="text-center pa-12">
      <VProgressCircular
        color="primary"
        indeterminate
        size="64"
      />
      <div class="mt-4 text-h6">Memuat komposisi...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="compositionData.length === 0" class="text-center pa-12">
      <VIcon
        icon="mdi-chef-hat-off"
        size="96"
        class="text-disabled mb-6"
      />
      <h3 class="text-h5 font-weight-bold mb-4">Belum Ada Komposisi Produk</h3>
      <p class="text-body-1 text-medium-emphasis mb-6">
        Buat komposisi untuk produk Anda agar dapat melihat detail bahan yang dibutuhkan
      </p>
      <VBtn
        color="primary"
        size="large"
        prepend-icon="mdi-plus"
        @click="openCreateCompositionDialog"
      >
        Buat Komposisi Pertama
      </VBtn>
    </div>

    <!-- Composition Cards Grid -->
    <VRow v-else>
      <VCol
        v-for="product in paginatedData"
        :key="product.id_product"
        cols="12"
        md="6"
        lg="4"
      >
        <VCard
          class="composition-card h-100"
          elevation="2"
          :class="{ 'border-error': getStockStatus(product).color === 'error' }"
        >
          <!-- Product Header -->
          <VCardTitle class="pa-4 pb-2">
            <div class="d-flex align-center gap-3">
              <VAvatar
                :image="product.image_url"
                size="48"
                class="flex-shrink-0"
              >
                <VIcon v-if="!product.image_url" icon="mdi-coffee" />
              </VAvatar>
              <div class="flex-grow-1 min-width-0">
                <h3 class="text-h6 font-weight-bold text-truncate">
                  {{ product.name }}
                </h3>
                <p class="text-caption text-medium-emphasis mb-0">
                  {{ product.sku || 'SKU-' + product.id_product }}
                </p>
              </div>
            </div>
          </VCardTitle>

          <!-- Stock Status -->
          <VCardText class="py-2">
            <VChip
              :color="getStockStatus(product).color"
              size="small"
              variant="tonal"
              class="mb-3"
            >
              {{ getStockStatus(product).text }}
            </VChip>

            <!-- Items Count -->
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="mdi-format-list-bulleted" size="16" />
              <span class="text-body-2 font-weight-medium">
                {{ getItemsText(product.totalItems) }}
              </span>
            </div>

            <!-- Composition Items -->
            <div class="composition-items">
              <div
                v-for="item in product.items"
                :key="item.id_product_item"
                class="composition-item d-flex align-center justify-between mb-2"
              >
                <div class="d-flex align-center gap-2 flex-grow-1 min-width-0">
                  <VIcon 
                    :icon="item.item?.inventory && item.item.inventory.current_stock >= item.quantity_needed ? 'mdi-check-circle' : 'mdi-alert-circle'"
                    :color="item.item?.inventory && item.item.inventory.current_stock >= item.quantity_needed ? 'success' : 'error'"
                    size="16"
                  />
                  <span class="text-body-2 text-truncate">
                    {{ item.item?.name || 'Item tidak ditemukan' }}
                  </span>
                  <VChip
                    v-if="item.is_critical"
                    color="error"
                    size="x-small"
                    variant="tonal"
                  >
                    Kritis
                  </VChip>
                </div>
                <div class="text-end flex-shrink-0">
                  <div class="text-body-2 font-weight-medium">
                    {{ parseFloat(item.quantity_needed) }} {{ item.unit }}
                  </div>
                  <div class="text-caption text-success" v-if="item.item?.inventory">
                    Stok: {{ item.item.inventory.current_stock }} {{ item.unit }}
                  </div>
                  <div class="text-caption text-primary" v-if="item.formatted_total_cost">
                    {{ item.formatted_total_cost }}
                  </div>
                </div>
              </div>
            </div>
          </VCardText>

          <!-- Actions & Price -->
          <VCardText class="pt-0">
            <VDivider class="mb-3" />
            
            <!-- Price Info -->
            <div class="d-flex justify-between align-center mb-3">
              <span class="text-body-2">Harga:</span>
              <span class="text-h6 font-weight-bold text-primary">
                {{ formatRupiah(product.price || 0) }}
              </span>
            </div>


            <!-- Action Buttons -->
            <div class="d-flex gap-2">
              <VBtn
                color="primary"
                size="small"
                prepend-icon="mdi-eye"
                @click="openDetailDialog(product)"
              >
                Detail
              </VBtn>
              <VBtn
                color="secondary"
                variant="outlined"
                size="small"
                prepend-icon="mdi-cog"
                @click="openCompositionDialog(product)"
              >
                Kelola
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Pagination -->
    <div v-if="compositionData.length > itemsPerPage" class="d-flex justify-center mt-6">
      <VPagination
        v-model="currentPage"
        :length="totalPages"
        :total-visible="$vuetify.display.smAndDown ? 5 : 7"
        size="small"
        @update:model-value="onPageChange"
      />
    </div>

    <!-- Results Info -->
    <div v-if="compositionData.length > 0" class="text-center mt-4">
      <VChip variant="tonal" color="primary" size="small">
        Menampilkan {{ ((currentPage - 1) * itemsPerPage) + 1 }}-{{ Math.min(currentPage * itemsPerPage, compositionData.length) }} 
        dari {{ compositionData.length }} produk
      </VChip>
    </div>

    <!-- Composition Management Dialog -->
    <ProductCompositionDialog
      v-model="compositionDialog"
      :product="selectedProduct"
      :items="selectedProductItems"
      @save="handleCompositionSave"
      @refresh="emit('refresh')"
    />

    <!-- Product Detail Dialog -->
    <VDialog
      v-model="detailDialog"
      max-width="900"
      scrollable
    >
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between">
          <div class="d-flex align-center">
            <VIcon
              icon="mdi-eye"
              class="mr-2"
              color="primary"
            />
            <span>Detail Komposisi Produk</span>
          </div>
          <VBtn
            icon
            variant="text"
            @click="detailDialog = false"
          >
            <VIcon icon="mdi-close" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText v-if="selectedProductForDetail">
          <VContainer>
            <!-- Product Header Info -->
            <VRow>
              <VCol cols="12">
                <VCard variant="outlined" class="mb-4">
                  <VCardText>
                    <div class="d-flex align-center gap-4">
                      <VAvatar
                        :image="selectedProductForDetail.image_url"
                        size="80"
                      >
                        <VIcon v-if="!selectedProductForDetail.image_url" icon="mdi-coffee" size="40" />
                      </VAvatar>
                      <div class="flex-grow-1">
                        <h2 class="text-h5 font-weight-bold mb-2">
                          {{ selectedProductForDetail.name }}
                        </h2>
                        <div class="d-flex align-center gap-4 mb-2">
                          <VChip
                            :color="getStockStatus(selectedProductForDetail).color"
                            variant="tonal"
                            size="small"
                          >
                            {{ getStockStatus(selectedProductForDetail).text }}
                          </VChip>
                          <span class="text-body-2">SKU: {{ selectedProductForDetail.sku || '-' }}</span>
                        </div>
                        <div class="d-flex align-center gap-4">
                          <div class="text-h6 text-primary font-weight-bold">
                            {{ formatRupiah(selectedProductForDetail.price || 0) }}
                          </div>
                          <div class="text-body-2 text-info font-weight-medium">
                            HPP: {{ formatRupiah(selectedProductForDetail.hpp || selectedProductForDetail.cost || 0) }}
                          </div>
                          <div class="text-body-2 text-success font-weight-medium" v-if="selectedProductForDetail.price && (selectedProductForDetail.hpp || selectedProductForDetail.cost)">
                            Margin: {{ formatRupiah((selectedProductForDetail.price || 0) - (selectedProductForDetail.hpp || selectedProductForDetail.cost || 0)) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>

            <!-- Composition Items -->
            <VRow>
              <VCol cols="12">
                <VCard variant="outlined">
                  <VCardTitle class="text-h6">
                    <VIcon
                      icon="mdi-format-list-bulleted"
                      class="mr-2"
                    />
                    Komposisi Item ({{ selectedProductForDetail.totalItems || 0 }})
                  </VCardTitle>
                  <VCardText>
                    <template v-if="selectedProductForDetail.items && selectedProductForDetail.items.length > 0">
                      <VList>
                        <VListItem
                          v-for="(item, index) in selectedProductForDetail.items"
                          :key="item.id_product_item"
                          class="px-0"
                        >
                          <template #prepend>
                            <VAvatar
                              size="40"
                              :color="getItemStockColor(item.item)"
                              variant="tonal"
                            >
                              <VIcon 
                                :icon="item.item?.inventory && item.item.inventory.current_stock >= item.quantity_needed ? 'mdi-check' : 'mdi-alert'"
                              />
                            </VAvatar>
                          </template>

                          <VListItemTitle class="font-weight-medium">
                            {{ item.item?.name || 'Unknown Item' }}
                            <VChip
                              v-if="item.is_critical"
                              color="error"
                              variant="tonal"
                              size="x-small"
                              class="ml-2"
                            >
                              Kritis
                            </VChip>
                          </VListItemTitle>

                          <VListItemSubtitle>
                            <div class="d-flex align-center mt-1 gap-4">
                              <div class="d-flex align-center">
                                <VIcon
                                  icon="mdi-scale"
                                  size="14"
                                  class="mr-1"
                                />
                                <span>
                                  Kebutuhan: {{ Number(item.quantity_needed || 0).toLocaleString('id-ID') }} {{ item.unit }}
                                </span>
                              </div>
                              
                              <div class="d-flex align-center">
                                <VIcon
                                  icon="mdi-warehouse"
                                  size="14"
                                  class="mr-1"
                                />
                                <span
                                  :class="getItemStockClass(item.item)"
                                >
                                  Stok: {{ Number(item.item?.inventory?.current_stock || 0).toLocaleString('id-ID') }} {{ item.unit }}
                                </span>
                              </div>

                              <div v-if="item.formatted_total_cost" class="d-flex align-center">
                                <VIcon
                                  icon="mdi-currency-usd"
                                  size="14"
                                  class="mr-1"
                                />
                                <span class="text-primary font-weight-medium">
                                  {{ item.formatted_total_cost }}
                                </span>
                              </div>
                            </div>
                          </VListItemSubtitle>

                          <template #append>
                            <VChip
                              :color="getItemStockColor(item.item)"
                              variant="tonal"
                              size="small"
                            >
                              {{ getItemStockStatus(item.item) }}
                            </VChip>
                          </template>

                          <VDivider
                            v-if="index < selectedProductForDetail.items.length - 1"
                            class="mt-3"
                          />
                        </VListItem>
                      </VList>
                    </template>
                    <template v-else>
                      <div class="text-center py-8">
                        <VIcon
                          icon="mdi-package-variant-closed"
                          size="48"
                          color="grey-lighten-1"
                          class="mb-2"
                        />
                        <p class="text-body-1 text-grey">
                          Belum ada komposisi item untuk produk ini
                        </p>
                      </div>
                    </template>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>

            <!-- Summary Statistics -->
            <VRow class="mt-4">
              <VCol cols="12" md="2">
                <VCard variant="outlined" color="primary">
                  <VCardText class="text-center">
                    <VIcon icon="mdi-format-list-bulleted" size="24" class="mb-2" />
                    <div class="text-h6 font-weight-bold">{{ selectedProductForDetail.totalItems || 0 }}</div>
                    <div class="text-caption">Total Item</div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol cols="12" md="2">
                <VCard variant="outlined" color="warning">
                  <VCardText class="text-center">
                    <VIcon icon="mdi-alert" size="24" class="mb-2" />
                    <div class="text-h6 font-weight-bold">
                      {{ selectedProductForDetail.items?.filter((item: ProductItem) => item.is_critical).length || 0 }}
                    </div>
                    <div class="text-caption">Item Kritis</div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol cols="12" md="3">
                <VCard variant="outlined" color="info">
                  <VCardText class="text-center">
                    <VIcon icon="mdi-calculator" size="24" class="mb-2" />
                    <div class="text-h6 font-weight-bold">{{ formatRupiah(selectedProductForDetail.hpp || selectedProductForDetail.cost || 0) }}</div>
                    <div class="text-caption">HPP</div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol cols="12" md="3">
                <VCard variant="outlined" color="success">
                  <VCardText class="text-center">
                    <VIcon icon="mdi-currency-usd" size="24" class="mb-2" />
                    <div class="text-h6 font-weight-bold">{{ formatRupiah(selectedProductForDetail.price || 0) }}</div>
                    <div class="text-caption">Harga Jual</div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol cols="12" md="2">
                <VCard variant="outlined" color="purple">
                  <VCardText class="text-center">
                    <VIcon icon="mdi-trending-up" size="24" class="mb-2" />
                    <div class="text-h6 font-weight-bold">
                      {{ formatRupiah((selectedProductForDetail.price || 0) - (selectedProductForDetail.hpp || selectedProductForDetail.cost || 0)) }}
                    </div>
                    <div class="text-caption">Margin</div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
          </VContainer>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn
            variant="outlined"
            @click="detailDialog = false"
          >
            Tutup
          </VBtn>
          <VBtn
            color="secondary"
            variant="outlined"
            prepend-icon="mdi-cog"
            @click="() => {
              detailDialog = false
              openCompositionDialog(selectedProductForDetail)
            }"
          >
            Kelola Komposisi
          </VBtn>
          
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Product Selection Dialog for Create Composition -->
    <VDialog 
      v-model="createCompositionDialog" 
      max-width="600px"
      persistent
    >
      <VCard>
        <VCardTitle class="d-flex align-center justify-between pa-4">
          <span class="text-h6">Pilih Produk untuk Komposisi</span>
          <VBtn 
            variant="text" 
            icon="mdi-close" 
            size="small"
            @click="createCompositionDialog = false"
          />
        </VCardTitle>
        
        <VCardText class="pa-4">
          <ProductSelectionList @select="handleProductSelect" />
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.composition-card {
  transition: all 0.3s ease;
}

.composition-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.composition-item {
  padding: 4px 0;
  border-radius: 4px;
}

.composition-item:hover {
  background-color: rgba(var(--v-theme-primary), 0.05);
}

.border-error {
  border: 2px solid rgb(var(--v-theme-error));
}
</style>
