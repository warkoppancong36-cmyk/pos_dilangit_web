<template>
  <div class="base-product-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Kelola Base Product</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola bahan dasar untuk komposisi produk coffee shop Anda</p>
      </div>
      <div class="d-flex gap-3 align-center">
        <VBtn
          v-if="activeTab === 'base-products'"
          color="primary"
          prepend-icon="mdi-plus"
          class="coffee-btn"
          :loading="categoriesLoading"
          :disabled="categoriesLoading"
          @click="openCreateModal"
        >
          Tambah Base Product
        </VBtn>
        <VBtn
          v-if="activeTab === 'compositions'"
          color="primary"
          prepend-icon="tabler-vector-triangle"
          class="coffee-btn"
          :loading="loading"
          :disabled="loading"
          @click="openCompositionCreateDialog"
        >
          Tambah Komposisi
        </VBtn>
      </div>
    </div>

    <!-- Error Alert -->
    <VAlert
      v-if="errorMessage"
      type="error"
      variant="outlined"
      class="mb-4"
      :text="errorMessage"
      closable
      @click:close="errorMessage = ''"
    />

    <!-- Success Alert -->
    <VAlert
      v-if="successMessage"
      type="success"
      variant="outlined"
      class="mb-4"
      :text="successMessage"
      closable
      @click:close="successMessage = ''"
    />

    <!-- Tabs -->
    <VTabs v-model="activeTab" class="mb-6">
      <VTab value="base-products">
        <VIcon icon="tabler-package" class="me-2" />
        Base Products
      </VTab>
      <VTab value="compositions">
        <VIcon icon="tabler-vector-triangle" class="me-2" />
        Komposisi
      </VTab>
    </VTabs>

    <!-- Tab Content -->
    <VWindow v-model="activeTab">
      <!-- Base Products Tab -->
      <VWindowItem value="base-products">
        <!-- Filters -->
        <VCard class="mb-6">
          <VCardText>
            <VRow>
              <VCol cols="12" md="3">
                <VTextField
                  v-model="filters.search"
                  @input="debouncedSearch"
                  label="Search"
                  placeholder="Search by name, SKU..."
                  prepend-inner-icon="mdi-magnify"
                  clearable
                  hide-details
                />
              </VCol>

              <VCol cols="12" md="3">
                <VSelect
                  v-model="filters.category_id"
                  @update:model-value="loadBaseProducts"
                  :items="categoryItems"
                  label="Category"
                  clearable
                  hide-details
                />
              </VCol>

              <VCol cols="12" md="3">
                <VSelect
                  v-model="filters.stock_status"
                  @update:model-value="loadBaseProducts"
                  :items="stockStatusItems"
                  label="Stock Status"
                  clearable
                  hide-details
                />
              </VCol>

              <VCol cols="12" md="3">
                <VSelect
                  v-model="filters.is_active"
                  @update:model-value="loadBaseProducts"
                  :items="statusItems"
                  label="Status"
                  clearable
                  hide-details
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Main Content -->
        <VCard>
          <VDataTableServer
            v-model:page="pagination.current_page"
              v-model:items-per-page="pagination.per_page"
              :headers="headers"
              :items="baseProducts || []"
              :items-length="pagination.total"
              :loading="loading"
              item-value="id_base_product"
              @update:options="loadBaseProducts"
            >
            <!-- Image -->
            <template #item.image_url="{ item }">
              <VAvatar size="40" rounded>
                <VImg
                  v-if="item.image_url"
                  :src="item.image_url"
                  :alt="item.name"
                />
                <VIcon v-else icon="mdi-package-variant" />
              </VAvatar>
            </template>

            <!-- Name with SKU -->
            <template #item.name="{ item }">
              <div>
                <div class="font-medium">{{ item.name }}</div>
                <div class="text-sm text-gray-500">SKU: {{ item.sku || 'No SKU' }}</div>
              </div>
            </template>

            <!-- Category -->
            <template #item.category="{ item }">
              <VChip
                v-if="item.category"
                size="small"
                variant="outlined"
              >
                {{ item.category.name }}
              </VChip>
              <span v-else class="text-gray-400">No Category</span>
            </template>

            <!-- Stock -->
            <template #item.stock="{ item }">
              <div>
                <div class="font-medium">{{ formatNumber(item.current_stock) }} {{ item.unit }}</div>
                <div class="text-sm text-gray-500">Min: {{ formatNumber(item.min_stock) }}</div>
              </div>
            </template>

            <!-- Stock Status -->
            <template #item.stock_status="{ item }">
              <VChip
                :color="getStockStatusColor(item.stock_status)"
                size="small"
                variant="flat"
              >
                {{ getStatusLabel(item.stock_status) }}
              </VChip>
            </template>

            <!-- Cost -->
            <template #item.cost="{ item }">
              <div class="text-right">
                <div class="font-medium">{{ item.formatted_cost }}</div>
                <div class="text-sm text-gray-500">per {{ item.unit }}</div>
              </div>
            </template>

            <!-- Status -->
            <template #item.is_active="{ item }">
              <VChip
                :color="item.is_active ? 'success' : 'error'"
                size="small"
                variant="flat"
              >
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </VChip>
            </template>

            <!-- Actions -->
            <template #item.actions="{ item }">
              <div class="d-flex gap-2">
                <VBtn
                  @click="openCompositionCreateDialogForProduct(item)"
                  color="success"
                  variant="text"
                  size="small"
                  icon="tabler-vector-triangle"
                  title="Add Composition"
                />
                <VBtn
                  @click="openEditModal(item)"
                  color="primary"
                  variant="text"
                  size="small"
                  icon="mdi-pencil"
                  title="Edit Product"
                />
                <VBtn
                  @click="deleteBaseProduct(item)"
                  color="error"
                  variant="text"
                  size="small"
                  icon="mdi-delete"
                  title="Delete Product"
                />
              </div>
            </template>

            <!-- Empty state -->
            <template #no-data>
              <div class="text-center py-12">
                <VIcon icon="mdi-package-variant" size="64" class="text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">No base products</h3>
                <p class="text-gray-500 mb-6">Get started by creating a new base product.</p>
                <VBtn
                  @click="openCreateModal"
                  color="primary"
                  prepend-icon="mdi-plus"
                >
                  Add Base Product
                </VBtn>
              </div>
            </template>
          </VDataTableServer>
        </VCard>
      </VWindowItem>

      <!-- Compositions Tab -->
      <VWindowItem value="compositions">
        <!-- Composition Filters -->
        <VCard class="mb-6">
          <VCardText>
            <VRow>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="compositionFilters.search"
                  @input="debouncedCompositionSearch"
                  label="Search"
                  placeholder="Search compositions..."
                  prepend-inner-icon="mdi-magnify"
                  clearable
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="4">
                <VAutocomplete
                  v-model="compositionFilters.base_product_id"
                  @update:model-value="loadCompositions"
                  :items="baseProducts"
                  item-title="name"
                  item-value="id_base_product"
                  label="Base Product"
                  clearable
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="compositionFilters.is_active"
                  @update:model-value="loadCompositions"
                  :items="statusItems"
                  label="Status"
                  clearable
                  hide-details
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Compositions Table -->
        <VCard>
          <VDataTableServer
            v-model:page="compositionPagination.current_page"
            v-model:items-per-page="compositionPagination.per_page"
            :headers="compositionHeaders"
            :items="compositions || []"
            :items-length="compositionPagination.total"
            :loading="compositionsLoading"
            item-value="id"
            @update:options="loadCompositions"
          >
            <!-- Base Product -->
            <template #item.base_product="{ item }">
              <div class="d-flex align-center gap-3">
                <VAvatar size="30">
                  <VImg
                    v-if="item.base_product?.image_url"
                    :src="item.base_product.image_url"
                    :alt="item.base_product.name"
                  />
                  <VIcon v-else icon="tabler-package" size="16" />
                </VAvatar>
                <div>
                  <div class="font-medium">{{ item.base_product?.name }}</div>
                  <div class="text-sm text-gray-500">{{ item.base_product?.sku }}</div>
                </div>
              </div>
            </template>

            <!-- Ingredient -->
            <template #item.ingredient="{ item }">
              <div class="d-flex align-center gap-3">
                <VAvatar size="30">
                  <VImg
                    v-if="item.ingredient_base_product?.image_url"
                    :src="item.ingredient_base_product.image_url"
                    :alt="item.ingredient_base_product.name"
                  />
                  <VIcon v-else icon="tabler-bottle" size="16" />
                </VAvatar>
                <div>
                  <div class="font-medium">{{ item.ingredient_base_product?.name }}</div>
                  <div class="text-sm text-gray-500">{{ item.ingredient_base_product?.sku }}</div>
                </div>
              </div>
            </template>

            <!-- Quantity -->
            <template #item.quantity="{ item }">
              <div>
                <div class="font-medium">{{ formatNumber(item.quantity) }}</div>
                <div class="text-sm text-gray-500">{{ item.ingredient_base_product?.unit }}</div>
              </div>
            </template>

            <!-- Total Cost -->
            <template #item.total_cost="{ item }">
              <div class="text-right font-medium">
                {{ formatCurrency((item.ingredient_base_product?.cost_per_unit || 0) * item.quantity) }}
              </div>
            </template>

            <!-- Status -->
            <template #item.is_active="{ item }">
              <VChip
                :color="item.is_active ? 'success' : 'error'"
                size="small"
                variant="flat"
              >
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </VChip>
            </template>

            <!-- Actions -->
            <template #item.actions="{ item }">
              <div class="d-flex gap-2">
                <VBtn
                  @click="openCompositionEditDialog(item)"
                  color="primary"
                  variant="text"
                  size="small"
                  icon="tabler-edit"
                />
                <VBtn
                  @click="deleteComposition(item)"
                  color="error"
                  variant="text"
                  size="small"
                  icon="tabler-trash"
                />
              </div>
            </template>

            <!-- Empty state -->
            <template #no-data>
              <div class="text-center py-12">
                <VIcon icon="tabler-vector-triangle" size="64" class="text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">No compositions</h3>
                <p class="text-gray-500 mb-6">Create compositions to define ingredient relationships.</p>
                <VBtn
                  @click="openCompositionCreateDialog"
                  color="primary"
                  prepend-icon="tabler-plus"
                >
                  Add Composition
                </VBtn>
              </div>
            </template>
          </VDataTableServer>
        </VCard>
      </VWindowItem>
    </VWindow>

    <!-- Create/Edit Modal -->
    <BaseProductModal
      :show="showModal"
      :base-product="selectedBaseProduct"
      :categories="categories"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Composition Modal -->
    <BaseProductCompositionModal
      :show="compositionDialog"
      :composition="selectedComposition"
      :base-products="baseProducts"
      :base-products-loading="loading"
      :edit-mode="compositionEditMode"
      @close="closeCompositionDialog"
      @saved="handleCompositionSaved"
    />
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { debounce } from 'lodash'
import BaseProductModal from './BaseProductModal.vue'
import BaseProductCompositionModal from './BaseProductCompositionModal.vue'
import { useBaseProductStore } from '@/stores/baseProduct'
import { useNotification } from '@/composables/useNotification'
import { CategoriesApi } from '@/utils/api/CategoriesApi'
import axios from 'axios'

export default {
  name: 'BaseProductsManagement',
  components: {
    BaseProductModal,
    BaseProductCompositionModal
  },
  setup() {
    const baseProductStore = useBaseProductStore()
    const { showSuccess, showError } = useNotification()
    
    // State for success/error messages
    const successMessage = ref('')
    const errorMessage = ref('')
    
    // Reactive data
    const loading = computed(() => baseProductStore?.loading ?? false)
    const baseProducts = computed(() => baseProductStore?.baseProducts ?? [])
    const pagination = computed(() => baseProductStore?.pagination ?? {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    })
    const categories = ref([])
    const categoriesLoading = ref(false)
    
    // Tab management
    const activeTab = ref('base-products')
    
    // Composition state
    const compositions = ref([])
    const compositionsLoading = ref(false)
    const compositionDialog = ref(false)
    const compositionEditMode = ref(false)
    const selectedComposition = ref(null)
    const compositionPagination = reactive({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    })
    
    // Composition filters
    const compositionFilters = reactive({
      search: '',
      base_product_id: '',
      is_active: ''
    })
    
    // Composition headers
    const compositionHeaders = ref([
      { title: 'Base Product', key: 'base_product', sortable: false },
      { title: 'Ingredient', key: 'ingredient', sortable: false },
      { title: 'Quantity', key: 'quantity', sortable: true },
      { title: 'Total Cost', key: 'total_cost', sortable: false },
      { title: 'Status', key: 'is_active', sortable: true },
      { title: 'Actions', key: 'actions', sortable: false, width: 120 }
    ])
    const showModal = ref(false)
    const selectedBaseProduct = ref(null)
    
    const filters = reactive({
      search: '',
      category_id: '',
      stock_status: '',
      is_active: ''
    })

    // Data table headers
    const headers = [
      { title: '', key: 'image_url', sortable: false, width: '60px' },
      { title: 'Name', key: 'name', sortable: true },
      { title: 'Category', key: 'category', sortable: false },
      { title: 'Stock', key: 'stock', sortable: false },
      { title: 'Stock Status', key: 'stock_status', sortable: true },
      { title: 'Cost', key: 'cost', sortable: false, align: 'end' },
      { title: 'Status', key: 'is_active', sortable: true },
      { title: 'Actions', key: 'actions', sortable: false, width: '120px' }
    ]

    // Select items
    const categoryItems = computed(() => [
      { title: 'All Categories', value: '' },
      ...(categories.value || []).map(cat => ({
        title: cat.name,
        value: cat.id_category
      }))
    ])

    const stockStatusItems = [
      { title: 'All Status', value: '' },
      { title: 'In Stock', value: 'in_stock' },
      { title: 'Low Stock', value: 'low_stock' },
      { title: 'Out of Stock', value: 'out_of_stock' }
    ]

    const statusItems = [
      { title: 'All', value: '' },
      { title: 'Active', value: 'true' },
      { title: 'Inactive', value: 'false' }
    ]

    // Methods
    const loadBaseProducts = async (page = 1) => {
      try {
        const params = {
          page,
          per_page: pagination.value.per_page,
          ...filters
        }
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key]
          }
        })

        await baseProductStore.fetchBaseProducts(params)
      } catch (error) {
        console.error('Error loading base products:', error)
        showError('Failed to load base products')
      }
    }

    const loadCategories = async () => {
      try {
        categoriesLoading.value = true
        const response = await CategoriesApi.getCategories({
          page: 1,
          per_page: 100,
          status: 'active'
        })
        
        if (response.success && response.data) {
          categories.value = response.data
        } else {
          console.warn('No categories data in response')
        }
      } catch (error) {
        console.error('Error loading categories:', error)
        showError('Failed to load categories')
      } finally {
        categoriesLoading.value = false
      }
    }

    const debouncedSearch = debounce(() => {
      loadBaseProducts(1)
    }, 300)

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        loadBaseProducts(page)
      }
    }

    const openCreateModal = () => {
      selectedBaseProduct.value = null
      showModal.value = true
    }

    const openEditModal = (baseProduct) => {
      selectedBaseProduct.value = baseProduct
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      selectedBaseProduct.value = null
    }

    const handleSaved = () => {
      closeModal()
      loadBaseProducts(pagination.value.current_page)
      showSuccess('Base product saved successfully')
    }

    const deleteBaseProduct = async (baseProduct) => {
      if (!confirm(`Are you sure you want to delete "${baseProduct.name}"?`)) {
        return
      }

      try {
        await baseProductStore.deleteBaseProduct(baseProduct.id_base_product)
        showSuccess('Base product deleted successfully')
        loadBaseProducts(pagination.value.current_page)
      } catch (error) {
        console.error('Error deleting base product:', error)
        showError(error.response?.data?.message || 'Failed to delete base product')
      }
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        'in_stock': 'bg-green-100 text-green-800',
        'low_stock': 'bg-yellow-100 text-yellow-800',
        'out_of_stock': 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getStatusLabel = (status) => {
      const labels = {
        'in_stock': 'In Stock',
        'low_stock': 'Low Stock',
        'out_of_stock': 'Out of Stock'
      }
      return labels[status] || 'Unknown'
    }

    const getStockStatusColor = (status) => {
      const colors = {
        'in_stock': 'success',
        'low_stock': 'warning',
        'out_of_stock': 'error'
      }
      return colors[status] || 'default'
    }

    const formatNumber = (number) => {
      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3
      }).format(number || 0)
    }

    const formatCurrency = (number) => {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(number || 0)
    }

    // Composition methods
    const loadCompositions = async (page = 1) => {
      try {
        compositionsLoading.value = true
        const params = {
          page: page || compositionPagination.current_page,
          per_page: compositionPagination.per_page,
          ...compositionFilters
        }
        
        const response = await axios.get('/api/base-product-compositions', { params })
        
        if (response.data.success) {
          compositions.value = response.data.data.data || []
          Object.assign(compositionPagination, {
            current_page: response.data.data.current_page || 1,
            last_page: response.data.data.last_page || 1,
            per_page: response.data.data.per_page || 15,
            total: response.data.data.total || 0,
            from: response.data.data.from || 0,
            to: response.data.data.to || 0
          })
        }
      } catch (error) {
        console.error('Error loading compositions:', error)
        showError('Failed to load compositions')
      } finally {
        compositionsLoading.value = false
      }
    }

    const debouncedCompositionSearch = debounce(() => {
      loadCompositions(1)
    }, 300)

    const openCompositionCreateDialog = () => {
      selectedComposition.value = null
      compositionEditMode.value = false
      compositionDialog.value = true
    }

    const openCompositionCreateDialogForProduct = (baseProduct) => {
      selectedComposition.value = {
        base_product_id: baseProduct.id_base_product,
        ingredient_base_product_id: 0,
        quantity: 0,
        is_active: true,
        is_critical: false
      }
      compositionEditMode.value = false
      compositionDialog.value = true
    }

    const openCompositionEditDialog = (composition) => {
      selectedComposition.value = composition
      compositionEditMode.value = true
      compositionDialog.value = true
    }

    const closeCompositionDialog = () => {
      compositionDialog.value = false
      selectedComposition.value = null
      compositionEditMode.value = false
    }

    const handleCompositionSaved = async (result) => {
      if (result.success) {
        successMessage.value = result.message || 'Komposisi berhasil disimpan'
        // Refresh the compositions data
        await loadCompositions()
        closeCompositionDialog()
      } else {
        errorMessage.value = result.message || 'Gagal menyimpan komposisi'
      }
    }

    const deleteComposition = async (composition) => {
      if (confirm(`Are you sure you want to delete this composition?`)) {
        try {
          const response = await axios.delete(`/api/base-product-compositions/${composition.id}`)
          if (response.data.success) {
            showSuccess('Composition deleted successfully')
            await loadCompositions()
          }
        } catch (error) {
          console.error('Error deleting composition:', error)
          showError('Failed to delete composition')
        }
      }
    }

    // Watch active tab to load data
    watch(activeTab, (newTab) => {
      if (newTab === 'compositions') {
        loadCompositions()
      }
    })

    // Lifecycle
    onMounted(async () => {
      await loadCategories()
      await loadBaseProducts()
    })

    // Watch categories for debugging
    watch(categories, (newCategories) => {
    }, { deep: true })

    return {
      // Base products
      loading,
      baseProducts,
      categories,
      categoriesLoading,
      showModal,
      selectedBaseProduct,
      filters,
      pagination,
      headers,
      categoryItems,
      stockStatusItems,
      statusItems,
      loadBaseProducts,
      debouncedSearch,
      changePage,
      openCreateModal,
      openEditModal,
      closeModal,
      handleSaved,
      deleteBaseProduct,
      getStatusBadgeClass,
      getStatusLabel,
      getStockStatusColor,
      formatNumber,
      
      // Messages
      successMessage,
      errorMessage,
      
      // Tabs
      activeTab,
      
      // Compositions
      compositions,
      compositionsLoading,
      compositionDialog,
      compositionEditMode,
      selectedComposition,
      compositionPagination,
      compositionFilters,
      compositionHeaders,
      loadCompositions,
      debouncedCompositionSearch,
      openCompositionCreateDialog,
      openCompositionCreateDialogForProduct,
      openCompositionEditDialog,
      closeCompositionDialog,
      handleCompositionSaved,
      deleteComposition,
      formatCurrency
    }
  }
}
</script>
