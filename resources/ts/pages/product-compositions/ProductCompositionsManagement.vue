<template>
  <div class="product-compositions-management">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="px-4 sm:px-6 lg:max-w-6xl lg:mx-auto lg:px-8">
        <div class="py-6 md:flex md:items-center md:justify-between lg:border-t lg:border-gray-200">
          <div class="flex-1 min-w-0">
            <div class="flex items-center">
              <div>
                <div class="flex items-center">
                  <h1 class="ml-3 text-2xl font-bold leading-7 text-gray-900 sm:leading-9 sm:truncate">
                    Product Compositions
                  </h1>
                </div>
                <dl class="mt-6 flex flex-col sm:ml-3 sm:mt-1 sm:flex-row sm:flex-wrap">
                  <dt class="sr-only">Total</dt>
                  <dd class="flex items-center text-sm text-gray-500 font-medium capitalize sm:mr-6">
                    <VIcon icon="tabler-check" class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
                    {{ pagination.total || 0 }} compositions
                  </dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="mt-6 flex space-x-3 md:mt-0 md:ml-4">
            <button
              @click="openCreateModal"
              type="button"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <VIcon icon="tabler-plus" class="-ml-1 mr-2 h-5 w-5" />
              Add Composition
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow mb-6">
      <div class="px-4 sm:px-6 lg:max-w-6xl lg:mx-auto lg:px-8 py-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Product Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Product</label>
            <select
              v-model="filters.product_id"
              @change="loadCompositions"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="">All Products</option>
              <option v-for="product in products" :key="product.id_product" :value="product.id_product">
                {{ product.name }}
              </option>
            </select>
          </div>

          <!-- Base Product Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Base Product</label>
            <select
              v-model="filters.base_product_id"
              @change="loadCompositions"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="">All Base Products</option>
              <option v-for="baseProduct in baseProducts" :key="baseProduct.id_base_product" :value="baseProduct.id_base_product">
                {{ baseProduct.name }}
              </option>
            </select>
          </div>

          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select
              v-model="filters.is_active"
              @change="loadCompositions"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="">All</option>
              <option value="true">Active</option>
              <option value="false">Inactive</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:max-w-6xl lg:mx-auto lg:px-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        <p class="mt-2 text-sm text-gray-500">Loading compositions...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!compositions.length" class="text-center py-12">
        <VIcon icon="tabler-assembly" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No compositions</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating a product composition.</p>
        <div class="mt-6">
          <button
            @click="openCreateModal"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <VIcon icon="tabler-plus" class="-ml-1 mr-2 h-5 w-5" />
            Add Composition
          </button>
        </div>
      </div>

      <!-- Compositions by Product -->
      <div v-else class="space-y-6">
        <div v-for="(productCompositions, productName) in groupedCompositions" :key="productName" class="bg-white shadow overflow-hidden sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ productName }}</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
              {{ productCompositions.length }} base product{{ productCompositions.length !== 1 ? 's' : '' }} composition
            </p>
          </div>
          <div class="border-t border-gray-200">
            <dl>
              <div v-for="(composition, index) in productCompositions" :key="composition.id_composition" :class="{ 'bg-gray-50': index % 2 === 0, 'bg-white': index % 2 !== 0 }" class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500 flex items-center">
                  <img 
                    v-if="composition.base_product?.image_url" 
                    :src="composition.base_product.image_url" 
                    :alt="composition.base_product.name"
                    class="h-8 w-8 rounded object-cover mr-3"
                  >
                  <div v-else class="h-8 w-8 rounded bg-gray-200 flex items-center justify-center mr-3">
                    <VIcon icon="tabler-package" class="h-4 w-4 text-gray-400" />
                  </div>
                  <div>
                    <p class="font-medium">{{ composition.base_product?.name }}</p>
                    <p class="text-xs text-gray-400">{{ composition.base_product?.sku }}</p>
                  </div>
                </dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-1">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium">{{ formatNumber(composition.quantity) }} {{ composition.base_product?.unit }}</p>
                      <p class="text-xs text-gray-500">per 1 {{ composition.product?.name }}</p>
                    </div>
                    <div class="text-right">
                      <p class="text-sm font-medium text-gray-900">{{ composition.formatted_cost_per_unit }}</p>
                      <p class="text-xs text-gray-500">Cost contribution</p>
                    </div>
                  </div>
                  
                  <!-- Stock availability -->
                  <div class="mt-2">
                    <div class="flex items-center justify-between text-xs">
                      <span class="text-gray-500">Available stock:</span>
                      <span 
                        :class="getStockStatusClass(composition.base_product?.current_stock, composition.quantity)"
                        class="font-medium"
                      >
                        {{ formatNumber(composition.base_product?.current_stock || 0) }} {{ composition.base_product?.unit }}
                      </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                      <span class="text-gray-500">Can produce:</span>
                      <span class="font-medium text-gray-900">
                        {{ formatNumber(composition.max_producible_quantity) }} units
                      </span>
                    </div>
                  </div>
                </dd>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-1 flex items-center justify-end space-x-2">
                  <span 
                    v-if="!composition.is_active"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                  >
                    Inactive
                  </span>
                  <button
                    @click="openEditModal(composition)"
                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteComposition(composition)"
                    class="text-red-600 hover:text-red-900 text-sm font-medium"
                  >
                    Delete
                  </button>
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex justify-between items-center">
            <div class="text-sm text-gray-700">
              Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
            </div>
            <div class="flex space-x-2">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="px-3 py-1 text-sm border rounded disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Previous
              </button>
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-1 text-sm border rounded disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <CompositionModal
      :show="showModal"
      :composition="selectedComposition"
      :products="products"
      :base-products="baseProducts"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import CompositionModal from './CompositionModal.vue'
import { useBaseProductStore } from '@/stores/baseProduct'
import { useNotification } from '@/composables/useNotification'

export default {
  name: 'ProductCompositionsManagement',
  components: {
    CompositionModal
  },
  setup() {
    const baseProductStore = useBaseProductStore()
    const { showSuccess, showError } = useNotification()
    
    // Reactive data
    const loading = computed(() => baseProductStore.loading)
    const compositions = computed(() => baseProductStore.compositions)
    const pagination = computed(() => baseProductStore.pagination)
    const products = ref([])
    const baseProducts = ref([])
    const showModal = ref(false)
    const selectedComposition = ref(null)
    
    const filters = reactive({
      product_id: '',
      base_product_id: '',
      is_active: ''
    })

    // Computed
    const groupedCompositions = computed(() => {
      const grouped = {}
      compositions.value.forEach(composition => {
        const productName = composition.product?.name || 'Unknown Product'
        if (!grouped[productName]) {
          grouped[productName] = []
        }
        grouped[productName].push(composition)
      })
      return grouped
    })

    // Methods
    const loadCompositions = async (page = 1) => {
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

        await baseProductStore.fetchCompositions(params)
      } catch (error) {
        console.error('Error loading compositions:', error)
        showError('Failed to load compositions')
      }
    }

    const loadProducts = async () => {
      try {
        const response = await baseProductStore.fetchProducts()
        products.value = response.data
      } catch (error) {
        console.error('Error loading products:', error)
      }
    }

    const loadBaseProducts = async () => {
      try {
        const response = await baseProductStore.fetchAvailableBaseProducts()
        baseProducts.value = response.data
      } catch (error) {
        console.error('Error loading base products:', error)
      }
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        loadCompositions(page)
      }
    }

    const openCreateModal = () => {
      selectedComposition.value = null
      showModal.value = true
    }

    const openEditModal = (composition) => {
      selectedComposition.value = composition
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      selectedComposition.value = null
    }

    const handleSaved = () => {
      closeModal()
      loadCompositions(pagination.value.current_page)
      showSuccess('Composition saved successfully')
    }

    const deleteComposition = async (composition) => {
      if (!confirm(`Are you sure you want to delete this composition?`)) {
        return
      }

      try {
        await baseProductStore.deleteComposition(composition.id_composition)
        showSuccess('Composition deleted successfully')
        loadCompositions(pagination.value.current_page)
      } catch (error) {
        console.error('Error deleting composition:', error)
        showError(error.response?.data?.message || 'Failed to delete composition')
      }
    }

    const getStockStatusClass = (currentStock, requiredQuantity) => {
      const stock = parseFloat(currentStock) || 0
      const required = parseFloat(requiredQuantity) || 0
      
      if (stock >= required) {
        return 'text-green-600'
      } else if (stock > 0) {
        return 'text-yellow-600'
      } else {
        return 'text-red-600'
      }
    }

    const formatNumber = (number) => {
      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3
      }).format(number || 0)
    }

    // Lifecycle
    onMounted(() => {
      loadCompositions()
      loadProducts()
      loadBaseProducts()
    })

    return {
      loading,
      compositions,
      products,
      baseProducts,
      showModal,
      selectedComposition,
      filters,
      pagination,
      groupedCompositions,
      loadCompositions,
      changePage,
      openCreateModal,
      openEditModal,
      closeModal,
      handleSaved,
      deleteComposition,
      getStockStatusClass,
      formatNumber
    }
  }
}
</script>
