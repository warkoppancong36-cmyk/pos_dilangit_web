<template>
  <div class="base-product-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Kelola Base Product</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola bahan dasar untuk komposisi produk coffee shop Anda</p>
      </div>
      <div class="d-flex gap-3 align-center">
        <VBtn
          color="primary"
          prepend-icon="mdi-plus"
          class="coffee-btn"
          :loading="categoriesLoading"
          :disabled="categoriesLoading"
          @click="openCreateModal"
        >
          Tambah Base Product
        </VBtn>
      </div>
    </div>

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
                @click="openStockModal(item)"
                color="primary"
                variant="text"
                size="small"
                icon="mdi-package-variant"
              />
              <VBtn
                @click="openEditModal(item)"
                color="primary"
                variant="text"
                size="small"
                icon="mdi-pencil"
              />
              <VBtn
                @click="deleteBaseProduct(item)"
                color="error"
                variant="text"
                size="small"
                icon="mdi-delete"
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
    </div>

    <!-- Create/Edit Modal -->
    <BaseProductModal
      :show="showModal"
      :base-product="selectedBaseProduct"
      :categories="categories"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Stock Update Modal -->
    <StockUpdateModal
      :show="showStockModal"
      :base-product="selectedBaseProduct"
      @close="closeStockModal"
      @updated="handleStockUpdated"
    />
</template>

<script>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { debounce } from 'lodash'
import BaseProductModal from './BaseProductModal.vue'
import StockUpdateModal from './StockUpdateModal.vue'
import { useBaseProductStore } from '@/stores/baseProduct'
import { useNotification } from '@/composables/useNotification'
import { CategoriesApi } from '@/utils/api/CategoriesApi'

export default {
  name: 'BaseProductsManagement',
  components: {
    BaseProductModal,
    StockUpdateModal
  },
  setup() {
    const baseProductStore = useBaseProductStore()
    const { showSuccess, showError } = useNotification()
    
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
    const showModal = ref(false)
    const showStockModal = ref(false)
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
        console.log('Loading categories from CategoriesApi...')
        categoriesLoading.value = true
        const response = await CategoriesApi.getCategories({
          page: 1,
          per_page: 100,
          status: 'active'
        })
        console.log('Categories response:', response)
        
        if (response.success && response.data) {
          categories.value = response.data
          console.log('Categories loaded successfully:', categories.value.length, 'items')
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
      console.log('Opening create modal, categories:', categories.value)
      selectedBaseProduct.value = null
      showModal.value = true
    }

    const openEditModal = (baseProduct) => {
      console.log('Opening edit modal, categories:', categories.value)
      selectedBaseProduct.value = baseProduct
      showModal.value = true
    }

    const openStockModal = (baseProduct) => {
      selectedBaseProduct.value = baseProduct
      showStockModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      selectedBaseProduct.value = null
    }

    const closeStockModal = () => {
      showStockModal.value = false
      selectedBaseProduct.value = null
    }

    const handleSaved = () => {
      closeModal()
      loadBaseProducts(pagination.value.current_page)
      showSuccess('Base product saved successfully')
    }

    const handleStockUpdated = () => {
      closeStockModal()
      loadBaseProducts(pagination.value.current_page)
      showSuccess('Stock updated successfully')
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

    // Lifecycle
    onMounted(async () => {
      console.log('Component mounted, loading data...')
      await loadCategories()
      await loadBaseProducts()
    })

    // Watch categories for debugging
    watch(categories, (newCategories) => {
      console.log('Categories updated:', newCategories)
    }, { deep: true })

    return {
      loading,
      baseProducts,
      categories,
      categoriesLoading,
      showModal,
      showStockModal,
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
      openStockModal,
      closeModal,
      closeStockModal,
      handleSaved,
      handleStockUpdated,
      deleteBaseProduct,
      getStatusBadgeClass,
      getStatusLabel,
      getStockStatusColor,
      formatNumber
    }
  }
}
</script>
