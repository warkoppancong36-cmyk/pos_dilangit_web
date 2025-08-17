<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import ProductDialog from '@/components/products/ProductDialog.vue'
import ProductRecipeDialog from '@/components/products/ProductRecipeDialog.vue'
import ProductSearchFilters from '@/components/products/ProductSearchFilters.vue'
import ProductStatsCards from '@/components/products/ProductStatsCards.vue'
import ProductTable from '@/components/products/ProductTable.vue'
import { useProductRecipes } from '@/composables/useProductRecipes'
import { useProducts } from '@/composables/useProducts'
// import '@styles/index.scss'
import { onMounted } from 'vue'

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
  onPageChange,
  clearErrorMessage,
  clearSuccessMessage
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
  fetchAvailableItems
} = useProductRecipes()


const confirmDelete = async () => {
  await deleteProduct()
}

const confirmBulkDelete = async () => {
  await bulkDeleteProducts()
}

const handleOpenRecipeDialog = (product: any) => {
  console.log('🍳 Opening recipe dialog for product:', product.name)
  openRecipeCreateDialog(product.id_product || product.id)
}

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
        <h1 class="text-h4 font-weight-bold coffee-title">Kelola Produk</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola produk dan inventori untuk coffee shop Anda</p>
      </div>
      <div class="d-flex gap-3 align-center">
        <div v-if="hasSelectedProducts" class="d-flex gap-2">
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
          v-if="canCreateEdit"
          color="primary"
          prepend-icon="mdi-plus"
          class="coffee-btn"
          @click="openCreateDialog"
        >
          Tambah Produk
        </VBtn>
      </div>
    </div>

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
      @manage-recipes="(product) => {
        console.log('Product data:', product);
        console.log('Product ID:', product.id);
        console.log('Product ID_Product:', product.id_product);
        $router.push(`/product-recipes/${product.id_product || product.id}`);
      }"
      @open-recipe-dialog="handleOpenRecipeDialog"
      @delete-product="openDeleteDialog"
      @toggle-active="toggleActiveStatus"
      @toggle-featured="toggleFeaturedStatus"
      @update:selected-products="(products: number[]) => selectedProducts = products"
    />

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
