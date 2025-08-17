<route lang="yaml">
meta:
  layout: default
</route>

<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import ProductRecipeDialog from '@/components/products/ProductRecipeDialog.vue'
import ProductRecipeList from '@/components/products/ProductRecipeList.vue'
import { useProductRecipes } from '@/composables/useProductRecipes'
import { useProducts } from '@/composables/useProducts'
import { formatCurrency } from '@/utils/helpers'
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

// Router instance
const router = useRouter()

// Get product ID from route params
const route = useRoute()
const productId = computed(() => {
  const id = Number(route.params.id)
  console.log('🔗 Route params:', route.params)
  console.log('🆔 Product ID from route:', id, typeof id)
  console.log('🌐 Current route:', route.path)
  return id
})

const {
  // Recipe state
  recipesList,
  availableItems,
  loading,
  saveLoading,
  deleteLoading,
  stats,
  dialog,
  deleteDialog,
  editMode,
  selectedRecipe,
  formData,
  errorMessage,
  successMessage,
  modalErrorMessage,
  
  // Computed
  canCreateEdit,
  
  // Methods
  fetchRecipes,
  fetchAvailableItems,
  fetchStats,
  saveRecipe,
  deleteRecipe,
  openCreateDialog,
  openEditDialog,
  openDeleteDialog,
  closeDialog,
  clearModalError
} = useProductRecipes()

// Get product info
const { productsList, fetchProductsList, loading: productsLoading } = useProducts()
const currentProduct = computed(() => {
  console.log('🔍 Computing current product...')
  console.log('📦 ProductId:', productId.value, typeof productId.value)
  console.log('📦 Products list:', productsList.value.length, 'items')
  
  if (productsList.value.length > 0) {
    console.log('📋 First few products:', productsList.value.slice(0, 3).map(p => ({
      id: p.id,
      id_product: p.id_product, 
      name: p.name,
      sku: p.sku
    })))
  }
  
  const product = productsList.value.find(p => {
    const matchById = p.id === productId.value
    const matchByIdProduct = p.id_product === productId.value
    console.log(`🔍 Checking product ${p.name}: id=${p.id}, id_product=${p.id_product}, matchById=${matchById}, matchByIdProduct=${matchByIdProduct}`)
    return matchById || matchByIdProduct
  })
  
  console.log('✅ Found product:', product ? `${product.name} (ID: ${product.id || product.id_product})` : 'Not found')
  
  // Fallback data for testing if no product found
  if (!product && productsList.value.length === 0) {
    console.log('🔄 Using fallback product data')
    return {
      id: productId.value,
      name: 'Loading...', 
      sku: 'LOADING',
      price: 0,
      active: true,
      description: 'Memuat data produk...'
    }
  }
  
  return product
})

const confirmDelete = async () => {
  await deleteRecipe()
}

onMounted(async () => {
  console.log('🚀 Component mounted, productId:', productId.value)
  
  try {
    await Promise.all([
      fetchProductsList(),
      fetchRecipes(productId.value),
      fetchAvailableItems(),
      fetchStats()
    ])
    
    console.log('✅ All data loaded')
    console.log('📦 Products loaded:', productsList.value.length)
    console.log('🔍 Looking for product ID:', productId.value)
    console.log('📋 Available products:', productsList.value.map(p => ({ id: p.id, id_product: p.id_product, name: p.name })))
  } catch (error) {
    console.error('❌ Error loading data:', error)
  }
})
</script>

<template>
  <VContainer fluid class="product-recipe-management pa-6">
    <!-- Header -->
    <div class="d-flex align-items-center mb-6">
      <VBtn
        icon="tabler-arrow-left"
        variant="text"
        @click="router.go(-1)"
        class="me-3"
      />
      <div class="flex-grow-1">
        <h1 class="text-h4 font-weight-bold coffee-title">
          Resep Produk
          <span v-if="currentProduct" class="text-primary">
            - {{ currentProduct.name }}
          </span>
        </h1>
        <div v-if="currentProduct" class="d-flex align-items-center gap-2 mt-1">
          <VChip
            color="primary"
            size="small"
            variant="tonal"
            prepend-icon="tabler-package"
          >
            {{ currentProduct.sku }}
          </VChip>
          <VChip
            :color="currentProduct.active ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            {{ currentProduct.active ? 'Aktif' : 'Nonaktif' }}
          </VChip>
          <VChip
            color="warning"
            size="small"
            variant="tonal"
            prepend-icon="tabler-currency-dollar"
          >
            {{ formatCurrency(currentProduct.price) }}
          </VChip>
        </div>
        <p class="text-body-2 text-medium-emphasis coffee-subtitle mt-2">
          <span v-if="productsLoading">
            Memuat informasi produk...
          </span>
          <span v-else-if="currentProduct && currentProduct.description">
            {{ currentProduct.description }}
          </span>
          <span v-else-if="currentProduct">
            Kelola resep untuk produk {{ currentProduct.name }}
          </span>
          <span v-else class="text-error">
            ⚠️ Produk tidak ditemukan (ID: {{ productId }})
          </span>
        </p>
      </div>
      <div class="d-flex gap-3 align-center">
        <VBtn
          v-if="canCreateEdit"
          color="primary"
          prepend-icon="tabler-plus"
          class="coffee-btn"
          @click="openCreateDialog(productId)"
        >
          Tambah Resep
        </VBtn>
      </div>
    </div>

    <!-- Alert Messages -->
    <VAlert
      v-if="errorMessage"
      type="error"
      variant="outlined"
      class="mb-4"
      :text="errorMessage"
      closable
      @click:close="errorMessage = ''"
    />

    <VAlert
      v-if="successMessage"
      type="success"
      variant="outlined"
      class="mb-4"
      :text="successMessage"
      closable
      @click:close="successMessage = ''"
    />

    <!-- Product Summary Card (Compact) -->
    <VCard v-if="currentProduct" class="mb-6" variant="tonal" color="primary">
      <VCardText class="pa-4">
        <VRow align="center">
          <VCol>
            <div class="d-flex align-items-center gap-3">
              <VIcon icon="tabler-chef-hat" size="24" class="text-white" />
              <div>
                <div class="text-subtitle-1 font-weight-bold text-white">
                  Membuat Resep untuk: {{ currentProduct.name }}
                </div>
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    
    <!-- Product Loading State -->
    <VCard v-else-if="productsLoading" class="mb-6" variant="outlined">
      <VCardText class="pa-4 text-center">
        <VProgressCircular indeterminate color="primary" class="mb-2" />
        <div class="text-body-2 text-medium-emphasis">Memuat informasi produk...</div>
      </VCardText>
    </VCard>
    
    <!-- Product Not Found -->
    <VAlert
      v-else-if="!productsLoading && !currentProduct"
      type="error"
      variant="tonal"
      class="mb-6"
      title="Produk Tidak Ditemukan"
    >
      <div class="mb-3">
        Produk dengan ID {{ productId }} tidak ditemukan. 
      </div>
      <div class="mb-3">
        <strong>Produk yang tersedia:</strong>
        <ul class="mt-2">
          <li v-for="product in productsList.slice(0, 5)" :key="product.id_product">
            <VBtn
              variant="text"
              size="small"
              :to="`/product-recipes/${product.id_product}`"
              class="pa-1"
            >
              {{ product.name }} (ID: {{ product.id_product }})
            </VBtn>
          </li>
        </ul>
      </div>
      <template #append>
        <VBtn
          variant="text"
          size="small"
          @click="router.push('/inventory-management')"
        >
          Kembali ke Inventory
        </VBtn>
      </template>
    </VAlert>

    <!-- Recipe Statistics Section -->
    <div class="mb-4">
      <div class="d-flex align-items-center mb-3">
        <VIcon icon="tabler-chart-bar" size="20" class="me-2 text-primary" />
        <h2 class="text-h6 font-weight-bold mb-0">
          Statistik Resep
          <span v-if="currentProduct" class="text-body-2 text-medium-emphasis">
            untuk {{ currentProduct.name }}
          </span>
        </h2>
      </div>
    </div>

    <!-- Recipe Stats -->
    <VRow class="mb-6">
      <VCol cols="12" md="3">
        <VCard class="h-100" variant="outlined">
          <VCardText class="text-center pa-4">
            <VIcon
              icon="tabler-chef-hat"
              size="32"
              class="coffee-icon mb-2"
            />
            <div class="text-h4 font-weight-bold">{{ stats.total_recipes }}</div>
            <div class="text-caption text-medium-emphasis">Total Resep</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3">
        <VCard class="h-100" variant="outlined">
          <VCardText class="text-center pa-4">
            <VIcon
              icon="tabler-check"
              size="32"
              color="success"
              class="mb-2"
            />
            <div class="text-h4 font-weight-bold">{{ stats.active_recipes }}</div>
            <div class="text-caption text-medium-emphasis">Resep Aktif</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3">
        <VCard class="h-100" variant="outlined">
          <VCardText class="text-center pa-4">
            <VIcon
              icon="tabler-list-details"
              size="32"
              color="info"
              class="mb-2"
            />
            <div class="text-h4 font-weight-bold">{{ stats.total_items_used }}</div>
            <div class="text-caption text-medium-emphasis">Item Digunakan</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3">
        <VCard class="h-100" variant="outlined">
          <VCardText class="text-center pa-4">
            <VIcon
              icon="tabler-currency-dollar"
              size="32"
              color="warning"
              class="mb-2"
            />
            <div class="text-h4 font-weight-bold">
              {{ formatCurrency(stats.avg_cost_per_recipe) }}
            </div>
            <div class="text-caption text-medium-emphasis">Rata-rata Biaya</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Recipe List Section -->
    <div class="mb-4">
      <div class="d-flex align-items-center justify-space-between mb-3">
        <div class="d-flex align-items-center">
          <VIcon icon="tabler-list" size="20" class="me-2 text-primary" />
          <h2 class="text-h6 font-weight-bold mb-0">
            Daftar Resep
            <span v-if="currentProduct" class="text-body-2 text-medium-emphasis">
              {{ currentProduct.name }}
            </span>
          </h2>
        </div>
        <VChip
          v-if="recipesList.length > 0"
          color="primary"
          size="small"
          variant="tonal"
        >
          {{ recipesList.length }} Resep
        </VChip>
      </div>
    </div>

    <!-- Recipe List -->
    <ProductRecipeList
      :recipes="recipesList"
      :loading="loading"
      :can-edit="canCreateEdit"
      @edit-recipe="openEditDialog"
      @delete-recipe="openDeleteDialog"
      @duplicate-recipe="(recipe) => {
        openCreateDialog(productId)
        // Copy recipe data
        Object.assign(formData, {
          ...recipe,
          id: undefined,
          name: `${recipe.name} (Copy)`,
          active: true
        })
      }"
    />

    <!-- Recipe Dialog -->
    <ProductRecipeDialog
      v-model="dialog"
      :edit-mode="editMode"
      :form-data="formData"
      :available-items="availableItems"
      :loading="saveLoading"
      :error-message="modalErrorMessage"
      :product-name="currentProduct?.name"
      @update:model-value="dialog = $event"
      @close="closeDialog"
      @save="saveRecipe"
      @clear-error="clearModalError"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus Resep"
      :item-name="selectedRecipe?.name"
      :loading="deleteLoading"
      confirm-text="Hapus Resep"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    >
      <template #content>
        <div class="mb-4">
          <VAlert
            color="warning"
            variant="tonal"
            class="mb-4"
          >
            <div class="d-flex align-center gap-2">
              <VIcon icon="tabler-alert-triangle" />
              <div>
                <div class="font-weight-bold">Perhatian!</div>
                <div class="text-body-2">
                  Menghapus resep akan menghilangkan semua informasi komposisi dan instruksi pembuatan.
                  Tindakan ini tidak dapat dibatalkan.
                </div>
              </div>
            </div>
          </VAlert>
          
          <div>Apakah Anda yakin ingin menghapus resep <strong>"{{ selectedRecipe?.name }}"</strong>?</div>
        </div>
      </template>
    </DeleteConfirmationDialog>
  </VContainer>
</template>

<style scoped lang="scss">
.product-recipe-management {
  .coffee-title {
    color: rgb(var(--v-theme-primary));
    font-family: 'Inter', sans-serif;
  }

  .coffee-subtitle {
    opacity: 0.8;
    margin-top: 4px;
  }

  .coffee-icon {
    color: #B07124;
  }
}
</style>
