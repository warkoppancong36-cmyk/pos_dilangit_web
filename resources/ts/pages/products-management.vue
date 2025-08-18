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
  openCreateDialog,
  openEditDialog,
  openDeleteDialog,
  closeDialog,
  handleImageUpload,
  clearModalError,
  onPageChange
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

// Composition dialog state
const compositionDialog = ref(false)
const selectedCompositionProduct = ref(null)

const confirmDelete = async () => {
  await deleteProduct()
}

const confirmBulkDelete = async () => {
  await bulkDeleteProducts()
}

const handleOpenRecipeDialog = (product: any) => {
  console.log('🍳 Opening komposisi dialog for product:', product.name)
  const productId = product.id_product || product.id
  openRecipeCreateDialog(productId)
}

const handleOpenCompositionDialog = async (product: any) => {
  console.log('🧑‍🍳 Opening composition dialog for product:', product.name)
  selectedCompositionProduct.value = product
  
  // Fetch composition data for this specific product
  try {
    await fetchProductItemsForComposition({
      page: 1,
      per_page: 50,
      critical_only: false
    })
  } catch (error) {
    console.error('Error fetching composition items:', error)
  }
  
  compositionDialog.value = true
}

const getProductCompositionItems = (product: any) => {
  if (!product) return []
  
  // Filter product items yang sesuai dengan product ID
  const productId = product.id_product || product.id
  return productItemsList.value.filter(item => 
    item.product?.id_product === productId || item.product_id === productId
  ) || []
}

const handleFiltersUpdate = (newFilters: any) => {
  Object.assign(filters, newFilters)
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
  console.log('🔄 Tab changed to:', newTab)
  
  if (newTab === 'compositions') {
    console.log('🧑‍🍳 Loading composition data...')
    await fetchProductItemsForComposition({
      page: 1,
      per_page: 15,
      critical_only: false
    })
  }
}, { immediate: false })

onMounted(() => {
  fetchProductsList()
  fetchCategories()
  fetchStats()
  fetchAvailableItems()
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
        />

        <ProductTable
          :products="productsList"
          :loading="loading"
          :current-page="currentPage"
          :total-items="totalItems"
          :items-per-page="itemsPerPage"
          :selected-products="selectedProducts"
          :toggle-loading="toggleLoading"
          @update:page="onPageChange"
          @add-product="openCreateDialog"
          @edit-product="openEditDialog"
          @open-recipe-dialog="handleOpenRecipeDialog"
          @open-composition-dialog="handleOpenCompositionDialog"
          @delete-product="openDeleteDialog"
          @toggle-active="toggleActiveStatus"
          @toggle-featured="toggleFeaturedStatus"
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
      @save="() => { fetchProductsList(); compositionDialog = false }"
      @refresh="fetchProductsList"
    />

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
