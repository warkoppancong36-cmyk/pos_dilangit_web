<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold mb-1">HPP Management</h1>
        <p class="text-body-1 text-grey-darken-1 mb-0">
          Manage Harga Pokok Produksi (Cost of Goods Sold) for all products
        </p>
      </div>
      
      <div class="d-flex gap-3">
        <VBtn
          color="orange"
          variant="outlined"
          prepend-icon="mdi-package-variant-closed"
          @click="$router.push('/product-items-management')"
        >
          Komposisi Produk 123
        </VBtn>
        
        <VBtn
          color="primary"
          variant="elevated"
          prepend-icon="mdi-plus"
          @click="$router.push('/products-management')"
        >
          Manage Products
        </VBtn>
      </div>
    </div>

    <!-- HPP Dashboard -->
    <HPPDashboard />

    <!-- Products with HPP -->
    <VCard class="mt-6">
      <VCardTitle class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-3">
          <VIcon icon="mdi-package-variant" size="24" />
          <span>Products HPP Overview</span>
        </div>
        
        <div class="d-flex align-center gap-2">
          <VTextField
            v-model="searchQuery"
            prepend-inner-icon="mdi-magnify"
            label="Search products..."
            variant="outlined"
            density="compact"
            hide-details
            clearable
            style="inline-size: 300px;"
          />
          
          <VBtn
            color="primary"
            variant="text"
            @click="loadProducts"
            :loading="productsLoading"
            icon="mdi-refresh"
          />
        </div>
      </VCardTitle>

      <VCardText>
        <!-- Filters -->
        <VRow class="mb-4">
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.category"
              :items="categoryOptions"
              label="Category"
              variant="outlined"
              density="compact"
              clearable
              hide-details
            />
          </VCol>
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.hppStatus"
              :items="hppStatusOptions"
              label="HPP Status"
              variant="outlined"
              density="compact"
              clearable
              hide-details
            />
          </VCol>
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.marginRange"
              :items="marginRangeOptions"
              label="Margin Range"
              variant="outlined"
              density="compact"
              clearable
              hide-details
            />
          </VCol>
          <VCol cols="12" md="3">
            <VBtn
              color="error"
              variant="text"
              @click="clearFilters"
              prepend-icon="mdi-filter-off"
              block
            >
              Clear Filters
            </VBtn>
          </VCol>
        </VRow>

        <!-- Products Data Table -->
        <VDataTable
          :headers="productHeaders"
          :items="filteredProducts"
          :loading="productsLoading"
          :items-per-page="itemsPerPage"
          :search="searchQuery"
          class="elevation-1"
        >
          <template #item.name="{ item }">
            <div class="d-flex flex-column">
              <span class="font-weight-medium">{{ item.name }}</span>
              <span class="text-caption text-grey-darken-1">{{ item.sku }}</span>
            </div>
          </template>

          <template #item.current_price="{ item }">
            <span class="font-weight-bold text-primary">
              {{ formatCurrency(item.price || 0) }}
            </span>
          </template>

          <template #item.current_cost="{ item }">
            <span class="font-weight-medium">
              {{ formatCurrency(item.cost || 0) }}
            </span>
          </template>

          <template #item.margin="{ item }">
            <div class="d-flex flex-column align-end">
              <VChip
                :color="getMarginColor(item.margin_amount)"
                variant="tonal"
                size="small"
                class="mb-1"
              >
                {{ formatCurrency(item.margin_amount) }}
              </VChip>
              <span class="text-caption">
                {{ item.margin_percent.toFixed(1) }}%
              </span>
            </div>
          </template>

          <template #item.hpp_status="{ item }">
            <VChip
              :color="item.hpp_status.color"
              :prepend-icon="item.hpp_status.icon"
              variant="tonal"
              size="small"
            >
              {{ item.hpp_status.text }}
            </VChip>
          </template>

          <template #item.actions="{ item }">
            <HPPProductActions
              :product="item"
              @hpp-updated="loadProducts"
              @price-updated="loadProducts"
              @product-updated="loadProducts"
            />
          </template>

          <template #bottom>
            <div class="v-data-table__footer">
              <VDivider />
              <div class="pa-4 d-flex justify-space-between align-center">
                <div class="text-body-2">
                  Showing {{ filteredProducts.length }} of {{ products.length }} products
                </div>
                <VPagination
                  v-if="totalPages > 1"
                  v-model="currentPage"
                  :length="totalPages"
                  :total-visible="7"
                  size="small"
                />
              </div>
            </div>
          </template>
        </VDataTable>
      </VCardText>
    </VCard>
  </div>
</template>

<script setup lang="ts">
import HPPDashboard from '@/components/hpp/HPPDashboard.vue'
import HPPProductActions from '@/components/hpp/HPPProductActions.vue'
import { useHPP } from '@/composables/useHPP'
import { computed, onMounted, ref } from 'vue'

// Composables
const { formatCurrency } = useHPP()

// State
const products = ref<any[]>([])
const productsLoading = ref(false)
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(15)

// Filters
const filters = ref({
  category: null,
  hppStatus: null,
  marginRange: null,
})

// Filter Options
const categoryOptions = ref([
  { title: 'All Categories', value: null },
  { title: 'Food & Beverage', value: 'food' },
  { title: 'Electronics', value: 'electronics' },
  { title: 'Clothing', value: 'clothing' },
  { title: 'Others', value: 'others' },
])

const hppStatusOptions = [
  { title: 'All Status', value: null },
  { title: 'Good Margin', value: 'good' },
  { title: 'Low Margin', value: 'low' },
  { title: 'No HPP Data', value: 'no_hpp' },
]

const marginRangeOptions = [
  { title: 'All Ranges', value: null },
  { title: '< 10%', value: 'low' },
  { title: '10% - 30%', value: 'medium' },
  { title: '> 30%', value: 'high' },
]

// Headers
const productHeaders = [
  { title: 'Product', value: 'name', sortable: true, width: '200px' },
  { title: 'Price', value: 'current_price', align: 'end', sortable: true },
  { title: 'HPP Cost', value: 'current_cost', align: 'end', sortable: true },
  { title: 'Margin', value: 'margin', align: 'end', sortable: true },
  { title: 'Status', value: 'hpp_status', align: 'center' },
  { title: 'Actions', value: 'actions', sortable: false, width: '250px' },
]

// Computed
const filteredProducts = computed(() => {
  let filtered = [...products.value]

  // Apply filters
  if (filters.value.hppStatus) {
    filtered = filtered.filter(product => {
      if (filters.value.hppStatus === 'good') {
        return product.margin_percent >= 10
      } else if (filters.value.hppStatus === 'low') {
        return product.margin_percent < 10 && product.cost > 0
      } else if (filters.value.hppStatus === 'no_hpp') {
        return product.cost === 0
      }
      return true
    })
  }

  if (filters.value.marginRange) {
    filtered = filtered.filter(product => {
      if (filters.value.marginRange === 'low') {
        return product.margin_percent < 10
      } else if (filters.value.marginRange === 'medium') {
        return product.margin_percent >= 10 && product.margin_percent <= 30
      } else if (filters.value.marginRange === 'high') {
        return product.margin_percent > 30
      }
      return true
    })
  }

  return filtered
})

const totalPages = computed(() => {
  return Math.ceil(filteredProducts.value.length / itemsPerPage.value)
})

// Methods
const loadProducts = async () => {
  productsLoading.value = true
  try {
    const response = await fetch('/api/products', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    
    if (data.success) {
      products.value = data.data.map((product: any) => ({
        ...product,
        margin_amount: (product.price || 0) - (product.cost || 0),
        margin_percent: product.cost > 0 ? (((product.price || 0) - (product.cost || 0)) / product.cost) * 100 : 0,
        hpp_status: getHPPStatus(product),
      }))
    }
  } catch (error) {
    console.error('Error loading products:', error)
  } finally {
    productsLoading.value = false
  }
}

const getHPPStatus = (product: any) => {
  const cost = product.cost || 0
  const marginPercent = cost > 0 ? (((product.price || 0) - cost) / cost) * 100 : 0
  
  if (cost === 0) {
    return {
      color: 'error',
      icon: 'mdi-alert-circle',
      text: 'No HPP',
    }
  } else if (marginPercent < 10) {
    return {
      color: 'warning',
      icon: 'mdi-alert',
      text: 'Low Margin',
    }
  } else {
    return {
      color: 'success',
      icon: 'mdi-check-circle',
      text: 'Good Margin',
    }
  }
}

const getMarginColor = (margin: number): string => {
  if (margin > 0) return 'success'
  if (margin < 0) return 'error'
  return 'grey'
}

const clearFilters = () => {
  filters.value = {
    category: null,
    hppStatus: null,
    marginRange: null,
  }
}

// Lifecycle
onMounted(() => {
  loadProducts()
})
</script>

<style scoped>
.v-data-table__footer {
  border-block-start: 1px solid rgb(var(--v-theme-surface-variant));
}
</style>
