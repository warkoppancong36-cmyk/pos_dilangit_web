<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import ProductItemDialog from '@/components/product-items/ProductItemDialog.vue'
import ProductItemGroupView from '@/components/product-items/ProductItemGroupView.vue'
import ProductionCapacityDialog from '@/components/product-items/ProductionCapacityDialog.vue'
import { useProductItems } from '@/composables/useProductItems'
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const {
  productItemsList,
  products,
  items,
  loading,
  saveLoading,
  deleteLoading,
  dialog,
  deleteDialog,
  capacityDialog,
  editMode,
  selectedProductItem,
  selectedProduct,
  productionCapacity,
  currentPage,
  totalItems,
  itemsPerPage,
  filters,
  errorMessage,
  successMessage,
  modalErrorMessage,
  formData,
  canCreateEdit,
  productOptions,
  itemOptions,
  fetchProductItemsList,
  fetchProducts,
  fetchItems,
  saveProductItem,
  deleteProductItem,
  openCreateDialog,
  openEditDialog,
  openDeleteDialog,
  openCapacityDialog,
  onItemChange,
  onPageChange,
  handleFiltersUpdate,
} = useProductItems()

const confirmDelete = async () => {
  await deleteProductItem()
}

const handleEdit = (productItem: any) => {
  openEditDialog(productItem)
}

const handleDelete = (productItem: any) => {
  openDeleteDialog(productItem)
}

const handleCheckCapacity = (product: any) => {
  openCapacityDialog(product)
}

const handleViewHpp = (product: any) => {
  // Navigate ke halaman HPP management
  console.log('🏃‍♂️ Navigating to HPP management for product:', product.name)
  router.push('/hpp-management')
}

onMounted(() => {
  fetchProductItemsList()
  fetchProducts()
  fetchItems()
})
</script>

<template>
  <div class="product-item-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">
          Komposisi Produk
        </h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">
          Kelola hubungan antara produk dan item yang dibutuhkan untuk produksi
        </p>
      </div>
      <div class="d-flex gap-3 align-center">
        <VBtn
          v-if="canCreateEdit"
          color="primary"
          prepend-icon="mdi-plus"
          class="coffee-btn"
          @click="openCreateDialog"
        >
          Tambah Komposisi
        </VBtn>
      </div>
    </div>

    <!-- Error/Success Messages -->
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

    <!-- Filters -->
    <VCard
      class="mb-6"
      elevation="0"
      variant="outlined"
    >
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            md="4"
          >
            <VSelect
              v-model="filters.product_id"
              :items="productOptions"
              label="Filter Produk"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="handleFiltersUpdate"
            />
          </VCol>

          <VCol
            cols="12"
            md="4"
          >
            <VSelect
              v-model="filters.item_id"
              :items="itemOptions"
              label="Filter Item"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="handleFiltersUpdate"
            />
          </VCol>

          <VCol
            cols="12"
            md="4"
            class="d-flex align-center"
          >
            <VCheckbox
              v-model="filters.critical_only"
              label="Hanya Item Kritis"
              density="compact"
              hide-details
              @update:model-value="handleFiltersUpdate"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Product Items Group View -->
    <VCard
      elevation="0"
      variant="outlined"
    >
      <VCardText class="pa-0">
        <ProductItemGroupView
          :product-items="productItemsList"
          :loading="loading"
          :current-page="currentPage"
          :items-per-page="itemsPerPage"
          :total-items="totalItems"
          :products="products"
          :items="items"
          @edit="handleEdit"
          @delete="handleDelete"
          @check-capacity="handleCheckCapacity"
          @view-hpp="handleViewHpp"
          @update:page="onPageChange"
        />
      </VCardText>
    </VCard>

    <!-- Create/Edit Dialog -->
    <ProductItemDialog
      :dialog="dialog"
      :edit-mode="editMode"
      :loading="saveLoading"
      :form-data="formData"
      :products="products"
      :items="items"
      :error-message="modalErrorMessage"
      @save="saveProductItem"
      @update:dialog="(value) => dialog = value"
      @update:error-message="(msg) => modalErrorMessage = msg"
      @update:form-data="(data) => Object.assign(formData, data)"
      @item-change="onItemChange"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      :loading="deleteLoading"
      :item-name="`${selectedProductItem?.product?.name} - ${selectedProductItem?.item?.name}` || ''"
      item-type="komposisi"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    />

    <!-- Production Capacity Dialog -->
    <ProductionCapacityDialog
      :dialog="capacityDialog"
      :selected-product="selectedProduct"
      :production-capacity="productionCapacity"
      :loading="loading"
      @update:dialog="(value) => capacityDialog = value"
    />
  </div>
</template>

<style scoped lang="scss">
.product-item-management {
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
