<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ProductsApi } from '@/utils/api/ProductsApi'
import { formatRupiah } from '@/@core/utils/formatters'

interface Product {
  id_product: number
  id?: number
  name: string
  sku?: string
  price?: number
  image_url?: string
  formatted_price?: string
}

interface Emits {
  (e: 'select', product: Product): void
}

const emit = defineEmits<Emits>()

// State
const products = ref<Product[]>([])
const loading = ref(false)
const search = ref('')

// Fetch products
const fetchProducts = async () => {
  try {
    loading.value = true
    const response = await ProductsApi.getAll({
      per_page: 100,
      search: search.value,
      active: true
    })
    
    if (response.success) {
      products.value = response.data || []
    }
  } catch (error) {
    console.error('Error fetching products:', error)
  } finally {
    loading.value = false
  }
}

// Handle product selection
const selectProduct = (product: Product) => {
  emit('select', product)
}

// Lifecycle
onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <div class="product-selection">
    <!-- Search -->
    <VTextField
      v-model="search"
      label="Cari produk..."
      prepend-inner-icon="mdi-magnify"
      variant="outlined"
      density="compact"
      clearable
      class="mb-3"
      @update:model-value="fetchProducts"
    />

    <!-- Loading -->
    <div v-if="loading" class="text-center pa-4">
      <VProgressCircular indeterminate color="primary" />
      <div class="mt-2">Memuat produk...</div>
    </div>

    <!-- Products List -->
    <div v-else-if="products.length > 0" class="product-list" style="max-height: 400px; overflow-y: auto;">
      <VList density="compact">
        <VListItem
          v-for="product in products"
          :key="product.id_product || product.id"
          class="product-item"
          @click="selectProduct(product)"
          style="cursor: pointer;"
          :ripple="true"
        >
          <template #prepend>
            <VAvatar
              v-if="product.image_url"
              :image="product.image_url"
              size="32"
              class="me-3"
            />
            <VIcon
              v-else
              icon="mdi-package-variant"
              size="32"
              color="grey-lighten-1"
              class="me-3"
            />
          </template>
          
          <VListItemTitle class="text-body-2">{{ product.name }}</VListItemTitle>
          <VListItemSubtitle v-if="product.sku" class="text-caption">
            SKU: {{ product.sku }}
          </VListItemSubtitle>
          
          <template #append>
            <div class="d-flex flex-column align-end">
              <div v-if="product.price" class="text-body-2 text-primary font-weight-medium">
                {{ product.formatted_price || formatRupiah(product.price) }}
              </div>
              <VIcon icon="mdi-chevron-right" size="16" color="grey-lighten-1" />
            </div>
          </template>
        </VListItem>
      </VList>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center pa-8">
      <VIcon icon="mdi-package-variant-closed" size="64" color="grey-lighten-2" />
      <div class="text-h6 mt-2 text-medium-emphasis">
        {{ search ? 'Tidak ada produk ditemukan' : 'Belum ada produk' }}
      </div>
      <div class="text-body-2 text-medium-emphasis">
        {{ search ? 'Coba ubah kata kunci pencarian' : 'Tambah produk terlebih dahulu' }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-item:hover {
  background-color: rgba(var(--v-theme-primary), 0.08) !important;
}
</style>
