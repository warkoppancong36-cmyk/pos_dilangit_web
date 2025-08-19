<script setup lang="ts">
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import ProductItemDialog from '@/components/product-items/ProductItemDialog.vue'
import ProductItemGroupView from '@/components/product-items/ProductItemGroupView.vue'
import ProductionCapacityDialog from '@/components/product-items/ProductionCapacityDialog.vue'
import { useProductItems } from '@/composables/useProductItems'
import { onMounted, ref, computed } from 'vue'
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

// Detail dialog state
const detailDialog = ref(false)
const selectedProductForDetail = ref(null)

// Filter utilities
const hasActiveFilters = computed(() => {
  return !!(filters.search || filters.product_id || filters.item_id || filters.critical_only || filters.stock_status || filters.sort_by)
})

const activeFiltersCount = computed(() => {
  let count = 0
  if (filters.search) count++
  if (filters.product_id) count++
  if (filters.item_id) count++
  if (filters.critical_only) count++
  if (filters.stock_status) count++
  if (filters.sort_by) count++
  return count
})

const clearAllFilters = () => {
  filters.search = ''
  filters.product_id = null
  filters.item_id = null
  filters.critical_only = false
  filters.stock_status = null
  filters.sort_by = null
  handleFiltersUpdate()
}

// Helper functions for detail dialog
const getProductComposition = (productId: number) => {
  return productItemsList.value.filter(item => item.product_id === productId)
}

const getItemStockColor = (item: any) => {
  if (!item?.inventory) return 'grey'
  const stock = item.inventory.current_stock || 0
  const minStock = item.min_stock || 0
  
  if (stock <= 0) return 'error'
  if (stock <= minStock) return 'warning'
  return 'success'
}

const getItemStockStatus = (item: any) => {
  if (!item?.inventory) return 'Tidak ada data'
  const stock = item.inventory.current_stock || 0
  const minStock = item.min_stock || 0
  
  if (stock <= 0) return 'Habis'
  if (stock <= minStock) return 'Rendah'
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

const handleViewDetail = (product: any) => {
  console.log('👁️ Viewing product detail:', product.name)
  selectedProductForDetail.value = product
  detailDialog.value = true
}

onMounted(async () => {
  // Load products and items first
  await Promise.all([
    fetchProducts(),
    fetchItems()
  ])
  
  // Then load product items after products and items are available
  await fetchProductItemsList()
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
            md="3"
          >
            <VTextField
              v-model="filters.search"
              label="Cari Produk"
              placeholder="Nama produk atau SKU..."
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-magnify"
              hide-details
              clearable
              @update:model-value="handleFiltersUpdate"
            />
          </VCol>

          <VCol
            cols="12"
            md="3"
          >
            <VSelect
              v-model="filters.product_id"
              :items="productOptions"
              label="Filter Produk"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @update:model-value="handleFiltersUpdate"
            />
          </VCol>

          <VCol
            cols="12"
            md="3"
          >
            <VSelect
              v-model="filters.item_id"
              :items="itemOptions"
              label="Filter Item"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @update:model-value="handleFiltersUpdate"
            />
          </VCol>

          <VCol
            cols="12"
            md="3"
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

        <!-- Additional Filter Row -->
        <VRow class="mt-3">
          <VCol
            cols="12"
            md="3"
          >
            <VSelect
              v-model="filters.stock_status"
              :items="[
                { title: 'Semua Status', value: null },
                { title: 'Stok Aman', value: 'safe' },
                { title: 'Stok Rendah', value: 'low' },
                { title: 'Stok Habis', value: 'out' }
              ]"
              label="Status Stok"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @update:model-value="handleFiltersUpdate"
            />
          </VCol>

          <VCol
            cols="12"
            md="3"
          >
            <VSelect
              v-model="filters.sort_by"
              :items="[
                { title: 'Nama Produk A-Z', value: 'name_asc' },
                { title: 'Nama Produk Z-A', value: 'name_desc' },
                { title: 'Terbaru Dibuat', value: 'created_desc' },
                { title: 'Terlama Dibuat', value: 'created_asc' },
                { title: 'Harga Tertinggi', value: 'price_desc' },
                { title: 'Harga Terendah', value: 'price_asc' }
              ]"
              label="Urutkan"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="handleFiltersUpdate"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
            class="d-flex align-center gap-2"
          >
            <VBtn
              variant="outlined"
              color="grey"
              size="small"
              prepend-icon="mdi-filter-off"
              @click="clearAllFilters"
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
          @view-detail="handleViewDetail"
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

    <!-- Product Detail Dialog -->
    <VDialog
      v-model="detailDialog"
      max-width="800"
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
            <span>Detail Produk</span>
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
            <!-- Product Info -->
            <VRow>
              <VCol cols="12">
                <h2 class="text-h5 font-weight-bold mb-2">
                  {{ selectedProductForDetail.name }}
                </h2>
                <VChip
                  :color="selectedProductForDetail.active ? 'success' : 'error'"
                  variant="tonal"
                  size="small"
                  class="mb-4"
                >
                  {{ selectedProductForDetail.active ? 'Aktif' : 'Tidak Aktif' }}
                </VChip>
              </VCol>
            </VRow>

            <!-- Basic Info -->
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VCard
                  variant="outlined"
                  class="mb-4"
                >
                  <VCardTitle class="text-h6">
                    Informasi Dasar
                  </VCardTitle>
                  <VCardText>
                    <div class="mb-3">
                      <strong>SKU:</strong>
                      <span class="ml-2">{{ selectedProductForDetail.sku || '-' }}</span>
                    </div>
                    <div class="mb-3">
                      <strong>Barcode:</strong>
                      <span class="ml-2">{{ selectedProductForDetail.barcode || '-' }}</span>
                    </div>
                    <div class="mb-3">
                      <strong>Brand:</strong>
                      <span class="ml-2">{{ selectedProductForDetail.brand || '-' }}</span>
                    </div>
                    <div class="mb-3">
                      <strong>Kategori:</strong>
                      <span class="ml-2">{{ selectedProductForDetail.category?.name || '-' }}</span>
                    </div>
                    <div v-if="selectedProductForDetail.description">
                      <strong>Deskripsi:</strong>
                      <p class="mt-1 text-body-2">{{ selectedProductForDetail.description }}</p>
                    </div>
                  </VCardText>
                </VCard>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VCard
                  variant="outlined"
                  class="mb-4"
                >
                  <VCardTitle class="text-h6">
                    Harga & Stok
                  </VCardTitle>
                  <VCardText>
                    <div class="mb-3">
                      <strong>Harga Jual:</strong>
                      <span class="ml-2 text-h6 text-primary">Rp {{ Number(selectedProductForDetail.price || 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="mb-3">
                      <strong>Harga Pokok:</strong>
                      <span class="ml-2">Rp {{ Number(selectedProductForDetail.cost || 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="mb-3">
                      <strong>Markup:</strong>
                      <span class="ml-2">{{ selectedProductForDetail.markup_percentage || 0 }}%</span>
                    </div>
                    <div class="mb-3">
                      <strong>Berat:</strong>
                      <span class="ml-2">{{ selectedProductForDetail.weight || '-' }} gram</span>
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
                    Komposisi Item
                  </VCardTitle>
                  <VCardText>
                    <template v-if="getProductComposition(selectedProductForDetail.id_product).length > 0">
                      <VList>
                        <VListItem
                          v-for="(item, index) in getProductComposition(selectedProductForDetail.id_product)"
                          :key="item.id_product_item"
                          class="px-0"
                        >
                          <template #prepend>
                            <VAvatar
                              size="40"
                              color="primary"
                              variant="tonal"
                            >
                              <VIcon icon="mdi-package-variant" />
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
                            <div class="d-flex align-center mt-1">
                              <VIcon
                                icon="mdi-scale"
                                size="14"
                                class="mr-1"
                              />
                              <span class="mr-4">
                                Kebutuhan: {{ Number(item.quantity_needed || 0).toLocaleString('id-ID') }} {{ item.item?.unit }}
                              </span>
                              
                              <VIcon
                                icon="mdi-warehouse"
                                size="14"
                                class="mr-1"
                              />
                              <span
                                :class="getItemStockClass(item.item)"
                              >
                                Stok: {{ Number(item.item?.inventory?.current_stock || 0).toLocaleString('id-ID') }} {{ item.item?.unit }}
                              </span>
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
                            v-if="index < getProductComposition(selectedProductForDetail.id_product).length - 1"
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
            color="primary"
            variant="elevated"
            prepend-icon="mdi-pencil"
            @click="() => {
              detailDialog = false
              // Navigate to product edit or open edit dialog
              router.push(`/products-management?edit=${selectedProductForDetail.id_product}`)
            }"
          >
            Edit Produk
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
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
