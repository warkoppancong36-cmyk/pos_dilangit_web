<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import ProductCompositionCards from '@/components/products/ProductCompositionCards.vue'
import ProductCompositionDialog from '@/components/products/ProductCompositionDialog.vue'
import ProductDialog from '@/components/products/ProductDialog.vue'
import ProductRecipeDialog from '@/components/products/ProductRecipeDialog.vue'
import ProductRecipeList from '@/components/products/ProductRecipeList.vue'
import ProductSearchFilters from '@/components/products/ProductSearchFilters.vue'
import ProductStatsCards from '@/components/products/ProductStatsCards.vue'
import ProductTable from '@/components/products/ProductTable.vue'
import ProductVariantTab from '@/components/products/ProductVariantTab.vue'
import { useProductRecipes } from '@/composables/useProductRecipes'
import { useProductItems } from '@/composables/useProductItems'
import { useProducts } from '@/composables/useProducts'
// import '@styles/index.scss'
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const {
  productsList,
  categories,
  loading,
  saveLoading,
  deleteLoading,
  toggleLoading,
  stats,
  dialog,
  deleteDialog,
  editMode,
  selectedProduct,
  selectedProducts,
  currentPage,
  totalItems,
  itemsPerPage,
  filters,
  errorMessage,
  successMessage,
  modalErrorMessage,
  formData,
  selectedImage,
  imagePreview,
  canCreateEdit,
  hasSelectedProducts,
  fetchProductsList,
  fetchCategories,
  fetchStats,
  saveProduct,
  deleteProduct,
  bulkDeleteProducts,
  toggleActiveStatus,
  toggleFeaturedStatus,
  toggleKitchenAvailability,
  toggleBarAvailability,
  openCreateDialog,
  openEditDialog,
  openDeleteDialog,
  closeDialog,
  handleImageUpload,
  clearModalError,
  onPageChange,
  updateOptions
} = useProducts()

// Product Recipes composable
const {
  dialog: recipeDialog,
  editMode: recipeEditMode,
  formData: recipeFormData,
  availableItems,
  saveLoading: recipeSaveLoading,
  modalErrorMessage: recipeModalErrorMessage,
  openCreateDialog: openRecipeCreateDialog,
  openEditDialog: openRecipeEditDialog,
  closeDialog: closeRecipeDialog,
  saveRecipe,
  clearModalError: clearRecipeModalError,
  fetchAvailableItems,
  fetchRecipes,
  recipesList,
  deleteRecipe
} = useProductRecipes()

// Product Items composable for composition tab
const {
  productItemsList,
  loading: itemsLoading,
  errorMessage: itemsError,
  currentPage: itemsCurrentPage,
  totalItems: itemsTotalItems,
  itemsPerPage: itemsPerPageSize,
  fetchProductItemsForComposition
} = useProductItems()

// Local state
const activeTab = ref('products')

// Component refs
const productTableRef = ref()

// Composition dialog state
const compositionDialog = ref(false)
const selectedCompositionProduct = ref(null)

// Variant dialog state
const variantDialog = ref(false)
const selectedVariantProduct = ref(null)

const confirmDelete = async () => {
  await deleteProduct()
}

const confirmBulkDelete = async () => {
  await bulkDeleteProducts()
}

const handleOpenRecipeDialog = (product: any) => {
  const productId = product.id_product || product.id
  openRecipeCreateDialog(productId)
}

const handleOpenCompositionDialog = async (product: any) => {
  selectedCompositionProduct.value = product
  
  // Fetch composition data for this specific product
  try {
    await fetchProductItemsForComposition({
      page: 1,
      per_page: 100,
      critical_only: false
    })
  } catch (error) {
  }
  
  compositionDialog.value = true
}

const handleOpenVariantDialog = (product: any) => {
  selectedVariantProduct.value = product
  variantDialog.value = true
}

const getProductCompositionItems = (product: any) => {
  if (!product) return []
  
  // Filter product items yang sesuai dengan product ID
  const productId = product.id_product || product.id
  return productItemsList.value.filter(item => 
    item.product?.id_product === productId || item.product_id === productId
  ) || []
}

// Handle composition dialog save/refresh
const handleCompositionRefresh = async () => {
  // Refresh product items for composition
  await fetchProductItemsForComposition({
    page: 1,
    per_page: 100,
    critical_only: false
  })
  
  // Refresh products list to get updated data
  await fetchProductsList()
  
  // Refresh HPP data for the updated product
  if (selectedCompositionProduct.value && productTableRef.value) {
    const productId = selectedCompositionProduct.value.id_product || selectedCompositionProduct.value.id
    await productTableRef.value.refreshHPPData(productId)
  }
}

const handleFiltersUpdate = (newFilters: any) => {
  Object.assign(filters, newFilters)
  fetchProductsList() // Trigger fetch when filters update
}

const handleSearch = (query: string) => {
  // Update search filter and fetch
  console.log('Search query received:', query)
  filters.search = query
  console.log('Filters after search update:', filters)
  fetchProductsList()
}

const removeImage = () => {
  // Implementation for removing image
  selectedImage.value = null
  if (imagePreview.value) {
    imagePreview.value = ''
  }
}

// Watch for tab changes to refresh data
watch(activeTab, async (newTab) => {
  
  if (newTab === 'compositions') {
    await fetchProductItemsForComposition({
      page: 1,
      per_page: 100,
      critical_only: false
    })
  }
}, { immediate: false })

onMounted(() => {
  fetchProductsList()
  fetchCategories()
  fetchStats()
  fetchAvailableItems()
  // Fetch product items for composition tab
  fetchProductItemsForComposition({
    page: 1,
    per_page: 100,
    critical_only: false
  })
})
</script>

<template>
  <div class="product-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Kelola Produk & Komposisi</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola produk dan komposisi untuk coffee shop Anda</p>
      </div>
      <div class="d-flex gap-3 align-center">
        <div v-if="hasSelectedProducts && activeTab === 'products'" class="d-flex gap-2">
          <VChip color="primary" size="small">
            {{ selectedProducts.length }} dipilih
          </VChip>
          <VBtn
            color="error"
            variant="outlined"
            size="small"
            prepend-icon="mdi-delete"
            @click="confirmBulkDelete"
          >
            Hapus Terpilih
          </VBtn>
        </div>
        <VBtn
          v-if="canCreateEdit && activeTab === 'products'"
          color="primary"
          prepend-icon="mdi-plus"
          class="coffee-btn"
          @click="openCreateDialog"
        >
          Tambah Produk
        </VBtn>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <VTabs v-model="activeTab" class="mb-6">
      <VTab value="products">
        <VIcon icon="mdi-coffee" class="me-2" />
        Produk
      </VTab>
      <VTab value="compositions">
        <VIcon icon="mdi-chef-hat" class="me-2" />
        Komposisi Produk
      </VTab>
      <VTab value="variants">
        <VIcon icon="mdi-tune-variant" class="me-2" />
        Variant Produk
      </VTab>
    </VTabs>

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

    <!-- Tab Content -->
    <VWindow v-model="activeTab">
      <!-- Products Tab -->
      <VWindowItem value="products">
        <ProductStatsCards :stats="stats" />

        <ProductSearchFilters
          :filters="filters"
          @update:filters="handleFiltersUpdate"
          @search="handleSearch"
        />

        <ProductTable
          ref="productTableRef"
          :products="productsList"
          :loading="loading"
          :current-page="currentPage"
          :total-items="totalItems"
          :items-per-page="itemsPerPage"
          :selected-products="selectedProducts"
          :toggle-loading="toggleLoading"
          @update:options="updateOptions"
          @add-product="openCreateDialog"
          @edit-product="openEditDialog"
          @open-recipe-dialog="handleOpenRecipeDialog"
          @open-composition-dialog="handleOpenCompositionDialog"
          @open-variant-dialog="handleOpenVariantDialog"
          @delete-product="openDeleteDialog"
          @toggle-active="toggleActiveStatus"
          @toggle-featured="toggleFeaturedStatus"
          @toggle-kitchen="toggleKitchenAvailability"
          @toggle-bar="toggleBarAvailability"
          @update:selected-products="(products: number[]) => selectedProducts = products"
        />
      </VWindowItem>

      <!-- Compositions Tab -->
      <VWindowItem value="compositions">
        <ProductCompositionCards 
          :product-items="productItemsList"
          :loading="itemsLoading"
          @refresh="fetchProductItemsForComposition"
        />
      </VWindowItem>

      <!-- Variants Tab -->
      <VWindowItem value="variants">
        <ProductVariantTab 
          :loading="loading"
          @refresh="fetchProductsList"
        />
      </VWindowItem>
    </VWindow>

    <ProductDialog
      v-model="dialog"
      :edit-mode="editMode"
      :form-data="formData"
      :categories="categories"
      :loading="saveLoading"
      :error-message="modalErrorMessage"
      :selected-image="selectedImage ? [selectedImage] : []"
      :image-preview="imagePreview"
      @update:model-value="dialog = $event"
      @close="closeDialog"
      @submit="saveProduct"
      @image-change="handleImageUpload"
      @remove-image="removeImage"
      @clear-error="clearModalError"
      @update-form="(field: string, value: any) => (formData as any)[field] = value"
    />

        <!-- Product Recipe Dialog -->
    <ProductRecipeDialog
      v-model="recipeDialog"
      :edit-mode="recipeEditMode"
      :form-data="recipeFormData"
      :available-items="availableItems"
      :loading="recipeSaveLoading"
      :error-message="recipeModalErrorMessage"
      @update:model-value="recipeDialog = $event"
      @close="closeRecipeDialog"
      @save="saveRecipe"
      @clear-error="clearRecipeModalError"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus Produk"
      :item-name="selectedProduct?.name"
      :loading="deleteLoading"
      confirm-text="Hapus Produk"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    />

    <!-- Product Composition Dialog -->
    <ProductCompositionDialog
      v-model="compositionDialog"
      :product="selectedCompositionProduct"
      :items="getProductCompositionItems(selectedCompositionProduct)"
      @save="handleCompositionRefresh"
      @refresh="handleCompositionRefresh"
    />

    <!-- Product Variant Dialog -->
    <v-dialog
      v-model="variantDialog"
      max-width="1400px"
      persistent
    >
      <v-card>
        <v-card-title class="d-flex justify-between align-center">
          <div class="d-flex align-center">
            <v-icon icon="tabler-versions" class="me-2" />
            Kelola Variant - {{ selectedVariantProduct?.name }}
          </div>
          <v-btn
            icon="tabler-x"
            variant="text"
            @click="variantDialog = false"
          />
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-0">
          <ProductVariantTab
            v-if="selectedVariantProduct"
            :product="selectedVariantProduct"
          />
        </v-card-text>

        <v-divider />

        <v-card-actions>
          <v-spacer />

          <v-btn
            color="grey-darken-1"
            variant="outlined"
            @click="variantDialog = false"
          >
            <v-icon icon="mdi-close" class="me-1" />
            Tutup
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </div>
</template>
<style scoped lang="scss">
.product-management {
  .coffee-title {
    color: rgb(var(--v-theme-primary));
    font-family: Inter, sans-serif;
  }

  .coffee-subtitle {
    margin-block-start: 4px;
    opacity: 0.8;
  }
}
</style>
