<script setup lang="ts">
import { computed, defineEmits, ref } from 'vue'
import { formatRupiah } from '@/@core/utils/formatters'
import ProductCompositionDialog from './ProductCompositionDialog.vue'

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
const selectedProduct = ref(null)
const selectedProductItems = ref([])

// Open composition dialog
const openCompositionDialog = (product: any) => {
  selectedProduct.value = product
  selectedProductItems.value = product.items || []
  compositionDialog.value = true
}

// Handle composition save
const handleCompositionSave = (data: any) => {
  console.log('Composition saved:', data)
  emit('refresh')
}

// Group product items by product
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
  
  return Array.from(grouped.values())
})

const getStockStatus = (product: any) => {
  // Cek stok dari semua item dalam komposisi
  let hasLowStock = false
  
  product.items.forEach((item: ProductItem) => {
    if (item.is_critical || (item.item?.inventory && item.item.inventory.current_stock < item.quantity_needed)) {
      hasLowStock = true
    }
  })

  if (hasLowStock) {
    return { text: 'Stok Kritis', color: 'error' }
  }
  return { text: 'Stok Aman', color: 'success' }
}

const getItemsText = (totalItems: number) => {
  return `Item Komposisi (${totalItems})`
}
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
        <VChip
          :color="compositionData.length > 0 ? 'success' : 'default'"
          variant="tonal"
        >
          {{ compositionData.length }} Produk dengan Komposisi
        </VChip>
        <VBtn
          variant="outlined"
          prepend-icon="mdi-refresh"
          @click="emit('refresh')"
          :loading="loading"
        >
          Refresh
        </VBtn>
      </VCardTitle>
    </VCard>

    <!-- Filters -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VSelect
              label="Filter Produk"
              :items="['Semua Produk', 'Stok Kritis', 'Stok Aman']"
              variant="outlined"
              prepend-inner-icon="mdi-filter"
            />
          </VCol>
          <VCol cols="12" md="4">
            <VSelect
              label="Filter Item"
              :items="['Semua Item', 'Bahan Utama', 'Bahan Tambahan']"
              variant="outlined"
              prepend-inner-icon="mdi-food"
            />
          </VCol>
          <VCol cols="12" md="4">
            <VCheckbox
              label="Hanya Item Kritis"
              density="comfortable"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Error State -->
    <VAlert
      v-if="error"
      type="error"
      variant="outlined"
      class="mb-6"
      :text="error"
      closable
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
      >
        Buat Komposisi Pertama
      </VBtn>
    </div>

    <!-- Composition Cards Grid -->
    <VRow v-else>
      <VCol
        v-for="product in compositionData"
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
                    {{ item.quantity_needed }} {{ item.unit }}
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

            <!-- Setting HPP Button -->
            <VBtn
              block
              variant="outlined"
              color="secondary"
              size="small"
              prepend-icon="mdi-calculator"
              class="mb-2"
            >
              Setting HPP
            </VBtn>

            <!-- Action Buttons -->
            <div class="d-flex gap-2">
              <VBtn
                color="primary"
                size="small"
                prepend-icon="mdi-cog"
                @click="openCompositionDialog(product)"
              >
                Kelola
              </VBtn>
              <VBtn
                color="secondary"
                variant="outlined"
                size="small"
                prepend-icon="mdi-eye"
                @click="emit('view-details', product)"
              >
                Detail
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Composition Management Dialog -->
    <ProductCompositionDialog
      v-model="compositionDialog"
      :product="selectedProduct"
      :items="selectedProductItems"
      @save="handleCompositionSave"
      @refresh="emit('refresh')"
    />
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
