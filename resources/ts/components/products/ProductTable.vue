<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between">
      <div class="d-flex align-center gap-2">
        <VIcon
          icon="tabler-list"
          class="coffee-icon"
        />
        Data Produk
        <VChip
          v-if="totalItems"
          size="small"
          color="coffee"
          variant="tonal"
        >
          {{ totalItems }} item
        </VChip>
      </div>
      
      <div class="d-flex align-center gap-2">
        <!-- Bulk Actions -->
        <VBtn
          v-if="selectedProducts.length > 0"
          prepend-icon="tabler-trash"
          color="error"
          variant="outlined"
          @click="$emit('bulk-delete')"
        >
          Hapus Terpilih ({{ selectedProducts.length }})
        </VBtn>
        
        <!-- Add Product Button -->
        <VBtn
          prepend-icon="tabler-plus"
          color="coffee"
          @click="$emit('add-product')"
        >
          Tambah Produk
        </VBtn>
      </div>
    </VCardTitle>

    <VDivider />

    <!-- Data Table -->
    <VDataTableServer
      :model-value="selectedProducts"
      @update:model-value="$emit('update:selected-products', $event)"
      :headers="headers"
      :items="products"
      :items-length="totalItems"
      :loading="loading"
      :items-per-page="itemsPerPage"
      :page="currentPage"
      show-select
      item-value="id"
      class="products-table"
      @update:page="$emit('update:page', $event)"
      @update:items-per-page="$emit('update:items-per-page', $event)"
      @update:sort-by="$emit('update:sort-by', $event)"
    >
      <!-- Loading -->
      <template #loading>
        <VSkeletonLoader type="table-row@10" />
      </template>

      <!-- No Data -->
      <template #no-data>
        <div class="text-center py-8">
          <VIcon
            icon="tabler-package-off"
            size="64"
            class="mb-4 text-disabled"
          />
          <h6 class="text-h6 mb-2">
            Tidak ada produk
          </h6>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Belum ada produk yang ditambahkan atau sesuai dengan filter yang dipilih.
          </p>
          <VBtn
            prepend-icon="tabler-plus"
            color="coffee"
            @click="$emit('add-product')"
          >
            Tambah Produk Pertama
          </VBtn>
        </div>
      </template>

      <!-- Product Image & Name -->
      <template #item.name="{ item }">
        <div class="d-flex align-center gap-3 py-2">
          <VAvatar
            :image="item.image_url"
            size="40"
            rounded
            class="product-avatar"
          >
            <VIcon
              v-if="!item.image_url"
              icon="tabler-package"
            />
          </VAvatar>
          <div>
            <div class="font-weight-medium">
              {{ item.name }}
            </div>
            <div class="text-caption text-medium-emphasis">
              SKU: {{ item.sku || '-' }}
            </div>
          </div>
        </div>
      </template>

      <!-- Category -->
      <template #item.category="{ item }">
        <VChip
          :color="'coffee'"
          size="small"
          variant="tonal"
        >
          {{ item.category?.name || '-' }}
        </VChip>
      </template>

      <!-- Price -->
      <template #item.price="{ item }">
        <div class="text-end">
          <div class="font-weight-medium">
            {{ formatCurrency(item.price) }}
          </div>
          <div class="text-caption text-medium-emphasis d-flex align-center justify-end gap-1">
            <span>HPP: {{ formatCurrency(getProductHPP(item)) }}</span>
            <VProgressCircular
              v-if="hppLoading[item.id_product || item.id] && getProductHPP(item) > 0"
              indeterminate
              size="12"
              width="2"
            />
          </div>
        </div>
      </template>

      <!-- Margin -->
      <template #item.profit_margin="{ item }">
        <div class="text-center profit-margin-container">
          <VChip
            :color="getMarginColor(getProductMargin(item))"
            size="small"
            variant="tonal"
            class="profit-margin-chip"
          >
            {{ getProductMargin(item) }}%
          </VChip>
        </div>
      </template>

      <!-- Stock -->
      <template #item.stock="{ item }">
        <div class="text-center">
          <VChip
            :color="item.stock_status === 'out_of_stock' ? 'error' : item.stock_status === 'low_stock' ? 'warning' : 'success'"
            size="small"
            variant="tonal"
          >
            {{ item.stock_status === 'out_of_stock' ? 'Habis' : item.stock_status === 'low_stock' ? 'Rendah' : 'Aman' }}
          </VChip>
          <div v-if="item.min_stock" class="text-caption text-medium-emphasis mt-1">
            Min: {{ item.min_stock }}
          </div>
        </div>
      </template>

      <!-- Status -->
      <template #item.active="{ item }">
        <div class="d-flex align-center gap-2">
          <VChip
            :color="item.active ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            {{ item.active ? 'Aktif' : 'Tidak Aktif' }}
          </VChip>
          
          <VTooltip
            v-if="item.featured"
            text="Produk Unggulan"
          >
            <template #activator="{ props }">
              <VIcon
                v-bind="props"
                icon="tabler-star-filled"
                color="warning"
                size="small"
              />
            </template>
          </VTooltip>
        </div>
      </template>

      <!-- Station Availability -->
      <template #item.station_availability="{ item }">
        <div class="d-flex flex-column gap-1">
          <VChip
            v-if="item.available_in_kitchen"
            color="orange"
            size="x-small"
            variant="tonal"
          >
            <VIcon icon="tabler-tools-kitchen-2" size="12" class="me-1" />
            Kitchen
          </VChip>
          <VChip
            v-if="item.available_in_bar"
            color="purple"
            size="x-small"
            variant="tonal"
          >
            <VIcon icon="tabler-glass-cocktail" size="12" class="me-1" />
            Bar
          </VChip>
          <div v-if="!item.available_in_kitchen && !item.available_in_bar" class="text-caption text-medium-emphasis">
            Tidak Tersedia
          </div>
        </div>
      </template>

      <!-- Created Date -->
      <template #item.created_at="{ item }">
        <div class="text-body-2">
          {{ formatDate(item.created_at ?? '') }}
        </div>
        <div class="text-caption text-medium-emphasis">
          {{ formatTime(item?.created_at ?? '') }}
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="d-flex align-center gap-1">
          <!-- Komposisi Management -->
          <VTooltip text="Kelola Komposisi">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-chef-hat"
                size="small"
                variant="text"
                color="primary"
                @click="$emit('open-composition-dialog', item)"
              />
            </template>
          </VTooltip>

          <!-- Variant Management -->
          <VTooltip text="Kelola Variant">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-versions"
                size="small"
                variant="text"
                color="secondary"
                @click="$emit('open-variant-dialog', item)"
              />
            </template>
          </VTooltip>

          <!-- View -->
          <!-- <VTooltip text="Lihat Detail">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-eye"
                size="small"
                variant="text"
                color="info"
                @click="$emit('view-product', item)"
              />
            </template>
          </VTooltip> -->

          <!-- Edit -->
          <VTooltip text="Edit Produk">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-edit"
                size="small"
                variant="text"
                color="coffee"
                @click="$emit('edit-product', item)"
              />
            </template>
          </VTooltip>

          <!-- Toggle Active -->
          <VTooltip :text="item.active ? 'Nonaktifkan' : 'Aktifkan'">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                :icon="item.active ? 'tabler-toggle-right' : 'tabler-toggle-left'"
                size="small"
                variant="text"
                :color="item.active ? 'success' : 'error'"
                :loading="toggleLoading?.[item.id_product] || false"
                @click="$emit('toggle-active', item)"
              />
            </template>
          </VTooltip>

          <!-- Toggle Featured -->
          <VTooltip :text="item.featured ? 'Hapus dari Unggulan' : 'Jadikan Unggulan'">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                :icon="item.featured ? 'tabler-star-filled' : 'tabler-star'"
                size="small"
                variant="text"
                :color="item.featured ? 'warning' : 'default'"
                @click="$emit('toggle-featured', item)"
              />
            </template>
          </VTooltip>

          <!-- Delete -->
          <VTooltip text="Hapus Produk">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-trash"
                size="small"
                variant="text"
                color="error"
                @click="$emit('delete-product', item)"
              />
            </template>
          </VTooltip>
        </div>
      </template>
    </VDataTableServer>
  </VCard>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watchEffect } from 'vue'
import { Product } from '@/composables/useProducts'
import { useHPP } from '@/composables/useHPP'

// Props
const props = defineProps<{
  products: Product[]
  totalItems: number
  loading: boolean
  currentPage: number
  itemsPerPage: number
  selectedProducts: number[]
  toggleLoading?: Record<number, boolean>
}>()

// Emits
defineEmits<{
  'add-product': []
  'view-product': [product: Product]
  'edit-product': [product: Product]
  'manage-recipes': [product: Product]
  'open-recipe-dialog': [product: Product]
  'open-composition-dialog': [product: Product]
  'open-variant-dialog': [product: Product]
  'delete-product': [product: Product]
  'toggle-active': [product: Product]
  'toggle-featured': [product: Product]
  'bulk-delete': []
  'update:page': [page: number]
  'update:items-per-page': [itemsPerPage: number]
  'update:sort-by': [sortBy: any]
  'update:selected-products': [selectedProducts: number[]]
}>()

// HPP Composable
const { currentHPPBreakdown, getProductHPPBreakdown } = useHPP()

// Store for HPP data per product with caching
const hppData = ref<Record<number, number>>({})
const hppLoading = ref<Record<number, boolean>>({})
const hppCache = ref<Record<number, { data: number; timestamp: number; attempted: boolean }>>({})

// Cache duration in milliseconds (5 minutes)
const CACHE_DURATION = 5 * 60 * 1000
const BATCH_DELAY = 1000 // 1 second delay for batching

// Track which products we've already attempted to load
const loadAttempted = ref<Set<number>>(new Set())
const loadQueue = ref<Set<number>>(new Set())
let batchTimeout: NodeJS.Timeout | null = null

// Check if cache is still valid
const isCacheValid = (productId: number): boolean => {
  const cached = hppCache.value[productId]
  if (!cached) return false
  return Date.now() - cached.timestamp < CACHE_DURATION
}

// Process queued products in batch (only for products without backend HPP)
const processBatch = async () => {
  if (loadQueue.value.size === 0) return

  const productsToLoad = Array.from(loadQueue.value)
  loadQueue.value.clear()

  // Filter products that actually need HPP loading (don't have backend HPP)
  const productsNeedingHPP = productsToLoad.filter(productId => {
    const product = props.products.find(p => (p.id_product || p.id) === productId)
    // Only load if product doesn't have HPP from backend
    return product && (product.hpp === undefined || product.hpp === null)
  })

  if (productsNeedingHPP.length === 0) {
    return
  }


  // Process in smaller batches to avoid overwhelming the server
  const concurrencyLimit = 3 // Reduced from 5
  for (let i = 0; i < productsNeedingHPP.length; i += concurrencyLimit) {
    const batch = productsNeedingHPP.slice(i, i + concurrencyLimit)
    const batchPromises = batch.map(async (productId) => {
      if (hppLoading.value[productId] || isCacheValid(productId)) {
        return // Skip if already loading or cached
      }

      hppLoading.value[productId] = true
      loadAttempted.value.add(productId)

      try {
        await getProductHPPBreakdown(productId, 'latest')
        if (currentHPPBreakdown.value?.total_hpp) {
          const hppValue = currentHPPBreakdown.value.total_hpp
          hppData.value[productId] = hppValue
          hppCache.value[productId] = {
            data: hppValue,
            timestamp: Date.now(),
            attempted: true
          }
        } else {
          // Mark as attempted even if no data found
          hppCache.value[productId] = {
            data: 0,
            timestamp: Date.now(),
            attempted: true
          }
        }
      } catch (error) {
        console.warn(`Could not load HPP for product ${productId}:`, error)
        // Mark as attempted to avoid retrying immediately
        hppCache.value[productId] = {
          data: 0,
          timestamp: Date.now(),
          attempted: true
        }
      } finally {
        hppLoading.value[productId] = false
      }
    })
    
    // Wait for current batch to complete before starting next batch
    await Promise.all(batchPromises)
    
    // Add small delay between batches
    if (i + concurrencyLimit < productsToLoad.length) {
      await new Promise(resolve => setTimeout(resolve, 500))
    }
  }
}

// Queue product for HPP loading
const queueProductForHPP = (productId: number) => {
  // Skip if already cached and valid
  if (isCacheValid(productId)) {
    hppData.value[productId] = hppCache.value[productId].data
    return
  }

  // Skip if already attempted recently (within cache duration)
  if (loadAttempted.value.has(productId)) {
    return
  }

  // Add to queue
  loadQueue.value.add(productId)

  // Clear existing timeout and set new one
  if (batchTimeout) {
    clearTimeout(batchTimeout)
  }

  batchTimeout = setTimeout(() => {
    processBatch()
  }, BATCH_DELAY)
}

// Load HPP for visible products with smart caching
const loadHPPForProducts = () => {
  for (const product of props.products) {
    const productId = product.id_product || product.id
    if (productId) {
      // Check cache first for products without backend HPP
      if (isCacheValid(productId)) {
        hppData.value[productId] = hppCache.value[productId].data
      } else if (!loadAttempted.value.has(productId) && !hppLoading.value[productId]) {
        queueProductForHPP(productId)
      }
    }
  }
}

// Helper function to get real-time HPP
const getProductHPP = (product: Product): number => {
  const productId = product.id_product || product.id
  return hppData.value[productId] || product.cost || 0
}

// Helper function to get real-time margin
const getProductMargin = (product: Product): number => {
  const hpp = getProductHPP(product)
  const price = product.price || 0
  if (price <= 0 || hpp <= 0) return 0
  return Math.round(((price - hpp) / price) * 100)
}

// Watch for products changes and load HPP with throttling
watchEffect(() => {
  if (props.products && props.products.length > 0) {
    loadHPPForProducts()
  }
})

// Load HPP on mount
onMounted(() => {
  if (props.products && props.products.length > 0) {
    loadHPPForProducts()
  }
})

// Expose method to refresh HPP data with cache invalidation
const refreshHPPData = async (productId?: number) => {
  if (productId) {
    // Clear cache for specific product
    delete hppCache.value[productId]
    delete hppData.value[productId]
    loadAttempted.value.delete(productId)
    
    const product = props.products.find(p => (p.id_product || p.id) === productId)
    if (product) {
      queueProductForHPP(productId)
    }
  } else {
    // Clear all cache
    hppCache.value = {}
    hppData.value = {}
    loadAttempted.value.clear()
    loadQueue.value.clear()
    
    // Reload all products
    loadHPPForProducts()
  }
}

// Expose the refresh function to parent component
defineExpose({
  refreshHPPData
})

// Table headers
const headers = [
  {
    title: 'Produk',
    key: 'name',
    sortable: true,
    width: '250px'
  },
  {
    title: 'Kategori',
    key: 'category',
    sortable: false,
    width: '120px'
  },
  {
    title: 'Harga',
    key: 'price',
    sortable: true,
    align: 'end' as const,
    width: '130px'
  },
  {
    title: 'Margin',
    key: 'profit_margin',
    sortable: true,
    align: 'center' as const,
    width: '100px'
  },
  {
    title: 'Stok',
    key: 'stock',
    sortable: true,
    align: 'center' as const,
    width: '100px'
  },
  {
    title: 'Status',
    key: 'active',
    sortable: true,
    width: '140px'
  },
  {
    title: 'Station',
    key: 'station_availability',
    sortable: false,
    width: '150px'
  },
  {
    title: 'Dibuat',
    key: 'created_at',
    sortable: true,
    width: '120px'
  },
  {
    title: 'Aksi',
    key: 'actions',
    sortable: false,
    align: 'center' as const,
    width: '200px'
  }
]

// Utils
const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatTime = (date: string): string => {
  return new Date(date).toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStockColor = (stock: number, minStock?: number): string => {
  if (stock <= 0) return 'error'
  if (minStock && stock <= minStock) return 'warning'
  return 'success'
}

const getMarginColor = (margin: number): string => {
  if (margin < 10) return 'error'      // Red for low margin
  if (margin < 25) return 'warning'    // Orange for moderate margin
  if (margin < 50) return 'success'    // Green for good margin
  return 'info'                        // Blue for excellent margin
}
</script>

<style scoped>
.coffee-icon {
  color: rgb(var(--v-theme-coffee));
}

.products-table :deep(.v-data-table__tr:hover) {
  background-color: rgba(var(--v-theme-coffee), 0.04);
}

.product-avatar {
  border: 1px solid rgba(var(--v-theme-coffee), 0.2);
}

/* Profit Margin Styling */
.profit-margin-container {
  margin-block: 8px;
  margin-inline: 0;
}

.profit-margin-chip {
  border-radius: 16px 16px 8px 8px !important;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 10%);
  font-weight: 600;
  letter-spacing: 0.5px;
  margin-block: 2px;
  margin-inline: 0;
  transition: all 0.2s ease;
}

.profit-margin-chip:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 15%);
  transform: translateY(-1px);
}

/* Rounded top for list appearance */
.products-table :deep(.v-data-table-rows-no-data) {
  border-radius: 12px 12px 0 0;
}

.products-table :deep(.v-data-table__tr:first-child) {
  border-radius: 12px 12px 0 0;
}

.products-table :deep(.v-data-table__td) {
  padding-block: 12px !important;
  padding-inline: 16px !important;
}
</style>
