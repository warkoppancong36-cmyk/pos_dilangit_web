<script setup lang="ts">
/* eslint-disable vue/custom-event-name-casing */
import type { ProductItem } from '@/composables/useProductItems'
import { computed } from 'vue'

interface ProductGroup {
  product: any
  items: ProductItem[]
}

interface Props {
  productItems: ProductItem[]
  loading: boolean
  currentPage: number
  itemsPerPage: number
  totalItems: number
  products: any[]
  items: any[]
}

interface Emits {
  (e: 'edit', productItem: any): void
  (e: 'delete', productItem: any): void
  (e: 'check-capacity', product: any): void
  (e: 'view-hpp', product: any): void
  (e: 'update:page', page: number): void
}

const props = defineProps<Props>()

const emit = defineEmits<Emits>()

const handleEditClick = (productItem: ProductItem) => {
  console.log('🔧 EDIT CLICKED!', productItem)
  console.log(`EDIT CLICKED: ${productItem.item?.name}`)
  emit('edit', productItem)
}

const handleDeleteClick = (productItem: ProductItem) => {
  console.log('🗑️ DELETE CLICKED!', productItem)
  console.log(`DELETE CLICKED: ${productItem.item?.name}`)
  emit('delete', productItem)
}

const handleCapacityClick = (product: any) => {
  console.log('📊 CAPACITY CLICKED!', product)
  console.log(`CAPACITY CLICKED: ${product.name}`)
  emit('check-capacity', product)
}

// Group product items by product
const groupedProductItems = computed<ProductGroup[]>(() => {
  console.log('🔍 Grouping product items...')
  console.log('📦 Available products:', props.products.length)
  console.log('🧰 Available items:', props.items.length)
  console.log('🔗 Product items to group:', props.productItems.length)
  
  const groups: { [key: number]: ProductGroup } = {}

  props.productItems.forEach(productItem => {
    const productId = productItem.product_id
    
    // PERBAIKAN: Gunakan product dari ProductItem response jika ada
    let product = productItem.product || props.products.find(p => p.id_product === productId)
    
    console.log(`Looking for product ID ${productId}:`, product ? `Found: ${product.name}` : 'NOT FOUND')

    if (!groups[productId]) {
      groups[productId] = {
        product: product || { id_product: productId, name: 'Unknown Product', sku: 'N/A' },
        items: [],
      }
    }

    // PERBAIKAN: Gunakan item dari ProductItem response jika ada
    const item = productItem.item || props.items.find(i => i.id_item === productItem.item_id)
    console.log(`Looking for item ID ${productItem.item_id}:`, item ? `Found: ${item.name}` : 'NOT FOUND')

    groups[productId].items.push({
      ...productItem,
      item,
    })
  })

  const result = Object.values(groups)
  console.log('✅ Final grouped result:', result)
  return result
})

// Utility functions
const formatNumber = (value: number | string | null | undefined): string => {
  if (value === null || value === undefined || value === '') {
    return '0'
  }
  
  const num = typeof value === 'string' ? Number.parseFloat(value) : value
  
  if (isNaN(num)) {
    return '0'
  }

  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 3,
  }).format(num)
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount)
}

const getTotalCost = (items: ProductItem[]): number => {
  // Cost tidak lagi dihitung dari ProductItem karena field sudah dihapus
  return 0
}

const hasLowStock = (items: ProductItem[]): boolean => {
  return items.some(item => item.item?.is_low_stock)
}

const getStockColor = (items: ProductItem[]): string => {
  const hasLow = hasLowStock(items)

  if (hasLow)
    return 'warning'

  return 'success'
}

const getStockStatus = (items: ProductItem[]): string => {
  const hasLow = hasLowStock(items)

  if (hasLow)
    return 'Stok Rendah'

  return 'Stok Aman'
}

const getStockTextColor = (item: any): string => {
  if (item.inventory?.is_low_stock || item.is_low_stock)
    return 'text-warning'

  return 'text-success'
}

const getStockIcon = (item: any): string => {
  if (item.inventory?.is_low_stock || item.is_low_stock)
    return 'mdi-alert-circle'

  return 'mdi-check-circle'
}

const getStockPercentage = (item: any): number => {
  if (!item || !item.inventory || !item.inventory.reorder_level || item.inventory.reorder_level === 0 || item.inventory.current_stock === null || item.inventory.current_stock === undefined) {
    return 0
  }

  // Persentase berdasarkan reorder level (lebih meaningful)
  // > 100% = Stok aman (di atas reorder level)
  // < 100% = Perlu restock segera
  return Math.round((item.inventory.current_stock / item.inventory.reorder_level) * 100)
}
</script>

<template>
  <div class="product-item-group-view pa-4">
    <template v-if="loading">
      <VRow>
        <VCol
          v-for="n in 3"
          :key="n"
          cols="12"
          md="6"
          lg="4"
        >
          <VCard
            elevation="0"
            variant="outlined"
            class="mb-4"
          >
            <VCardText>
              <VSkeleton
                type="text"
                width="60%"
                class="mb-2"
              />
              <VSkeleton
                type="text"
                width="40%"
                class="mb-4"
              />
              <VSkeleton
                v-for="i in 3"
                :key="i"
                type="text"
                width="100%"
                class="mb-1"
              />
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <template v-else-if="groupedProductItems.length === 0">
      <VCard
        elevation="0"
        variant="outlined"
        class="text-center pa-8"
      >
        <VIcon
          icon="mdi-package-variant-closed"
          size="64"
          color="grey-lighten-1"
          class="mb-4"
        />
        <h3 class="text-h6 text-grey-darken-1 mb-2">
          Belum Ada Komposisi Produk
        </h3>
        <p class="text-body-2 text-grey">
          Mulai tambahkan komposisi produk untuk mengelola item yang dibutuhkan
        </p>
      </VCard>
    </template>

    <template v-else>
      <VRow>
        <VCol
          v-for="productGroup in groupedProductItems"
          :key="productGroup.product.id_product"
          cols="12"
          md="6"
          lg="4"
        >
          <VCard
            elevation="1"
            variant="flat"
            class="mb-4 product-card"
            :class="{ 'border-warning': hasLowStock(productGroup.items) }"
          >
            <!-- Product Header -->
            <VCardText class="pb-2">
              <div class="d-flex justify-space-between align-start mb-3">
                <div class="flex-grow-1">
                  <h3 class="text-h6 font-weight-bold mb-1">
                    {{ productGroup.product.name }}
                  </h3>
                  <p class="text-body-2 text-grey mb-0">
                    {{ productGroup.product.sku }}
                  </p>
                </div>
                <VChip
                  :color="getStockColor(productGroup.items)"
                  variant="tonal"
                  size="small"
                >
                  {{ getStockStatus(productGroup.items) }}
                </VChip>
              </div>

              <VDivider class="my-3" />

              <!-- Items List -->
              <div class="items-list">
                <h4 class="text-subtitle-2 font-weight-bold mb-3 text-grey-darken-1">
                  Item Komposisi ({{ productGroup.items.length }})
                </h4>

                <div
                  v-for="productItem in productGroup.items"
                  :key="productItem.id_product_item"
                  class="item-row mb-3"
                >
                  <div class="d-flex justify-space-between align-center">
                    <div class="flex-grow-1">
                      <div class="d-flex align-center mb-1">
                        <h5 class="text-body-1 font-weight-medium">
                          {{ productItem.item?.name || 'Unknown Item' }}
                        </h5>
                        <VChip
                          v-if="productItem.is_critical"
                          color="error"
                          variant="tonal"
                          size="x-small"
                          class="ml-2"
                        >
                          Kritis
                        </VChip>
                      </div>

                      <div class="d-flex align-center text-body-2 text-grey">
                        <VIcon
                          icon="mdi-scale"
                          size="16"
                          class="mr-1"
                        />
                        <span>
                          {{ formatNumber(productItem.quantity_needed) }} {{ productItem.item?.unit }}
                        </span>
                      </div>

                      <!-- Stock Information -->
                      <div
                        v-if="productItem.item"
                        class="d-flex align-center text-caption mt-1"
                        :class="getStockTextColor(productItem.item)"
                      >
                        <VIcon
                          :icon="getStockIcon(productItem.item)"
                          size="12"
                          class="mr-1"
                        />
                        <span>
                          Stok: {{ formatNumber(productItem.item.inventory?.current_stock) }} {{ productItem.item.unit }}
                        </span>
                      </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex align-center">
                      <VBtn
                        icon
                        variant="text"
                        size="small"
                        color="primary"
                        @click="handleEditClick(productItem)"
                      >
                        <VIcon
                          icon="mdi-pencil"
                          size="16"
                        />
                        <VTooltip
                          activator="parent"
                          location="top"
                        >
                          Edit
                        </VTooltip>
                      </VBtn>

                      <VBtn
                        icon
                        variant="text"
                        size="small"
                        color="info"
                        :disabled="!productItem.item?.id_item"
                        @click="handleCapacityClick(productGroup.product)"
                      >
                        <VIcon
                          icon="mdi-calculator"
                          size="16"
                        />
                        <VTooltip
                          activator="parent"
                          location="top"
                        >
                          Cek Kapasitas
                        </VTooltip>
                      </VBtn>

                      <VBtn
                        icon
                        variant="text"
                        size="small"
                        color="error"
                        @click="handleDeleteClick(productItem)"
                      >
                        <VIcon
                          icon="mdi-delete"
                          size="16"
                        />
                        <VTooltip
                          activator="parent"
                          location="top"
                        >
                          Hapus
                        </VTooltip>
                      </VBtn>
                    </div>
                  </div>

                  <VDivider
                    v-if="productGroup.items.indexOf(productItem) < productGroup.items.length - 1"
                    class="mt-2"
                  />
                </div>
              </div>
            </VCardText>

            <!-- Card Footer -->
            <VCardActions class="pt-0 px-4 pb-3">
              <VBtn
                variant="tonal"
                color="orange"
                size="small"
                prepend-icon="mdi-calculator-variant"
                @click="$emit('view-hpp', productGroup.product)"
              >
                Setting HPP
              </VBtn>

              <VSpacer />

              <div class="text-caption text-grey">
                Harga: {{ formatCurrency(productGroup.product.price || 0) }}
              </div>
            </VCardActions>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <!-- Pagination -->
    <div
      v-if="totalItems > itemsPerPage"
      class="d-flex justify-center mt-4"
    >
      <VPagination
        :model-value="currentPage"
        :length="Math.ceil(totalItems / itemsPerPage)"
        @update:model-value="$emit('update:page', $event)"
      />
    </div>
  </div>
</template>

<style scoped>
.product-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
  transition: all 0.2s ease;
}

.product-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 10%);
  transform: translateY(-2px);
}

.border-warning {
  border-color: rgb(var(--v-theme-warning)) !important;
}

.item-row {
  padding-block: 8px;
}

.items-list {
  max-block-size: 400px;
  overflow-y: auto;
}

.coffee-title {
  color: rgb(var(--v-theme-primary));
}

.coffee-subtitle {
  color: rgb(var(--v-theme-on-surface-variant));
}

.item-row .d-flex.align-center {
  position: relative;
  z-index: 1;
}

/* Ensure card actions are clickable */
.v-card-actions {
  position: relative;
  z-index: 2;
}

.v-card-actions .v-btn {
  position: relative;
  z-index: 3;
}
</style>
