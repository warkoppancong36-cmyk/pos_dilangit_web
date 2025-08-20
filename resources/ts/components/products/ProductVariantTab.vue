<template>
  <div class="product-variant-tab">
    <!-- Header -->
    <VCard class="mb-6" elevation="0" variant="outlined">
      <VCardTitle class="d-flex align-center justify-space-between">
        <div>
          <h2 class="text-h5 font-weight-bold">Variant Produk</h2>
          <p class="text-body-2 text-medium-emphasis">
            Kelola variant dan komposisi untuk produk Anda
          </p>
        </div>
        <div class="d-flex gap-2">
          <VBtn
            variant="outlined"
            prepend-icon="mdi-refresh"
            @click="loadVariants"
            :loading="loading"
          >
            Refresh
          </VBtn>
          <VBtn
            color="primary"
            prepend-icon="mdi-plus"
            @click="openCreateVariantDialog"
          >
            Tambah Variant
          </VBtn>
        </div>
      </VCardTitle>
    </VCard>

    <!-- Filters -->
    <VCard class="mb-6" elevation="0" variant="outlined">
      <VCardText>
        <VRow>
          <VCol cols="12" md="3">
            <VTextField
              v-model="filters.search"
              label="Cari Variant"
              placeholder="Nama variant, SKU, atau produk..."
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-magnify"
              hide-details
              clearable
            />
          </VCol>
          
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.stockStatus"
              :items="stockStatusOptions"
              label="Status Stok"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            />
          </VCol>
          
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.activeStatus"
              :items="[
                { title: 'Semua Status', value: null },
                { title: 'Aktif', value: true },
                { title: 'Nonaktif', value: false }
              ]"
              label="Status Aktif"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            />
          </VCol>
          
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.sortBy"
              :items="sortOptions"
              label="Urutkan"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
        </VRow>
        
        <!-- Active Filters -->
        <div v-if="hasActiveFilters" class="mt-4 d-flex align-center gap-2">
          <span class="text-body-2">Filter aktif:</span>
          <VChip
            v-if="filters.search"
            size="small"
            closable
            @click:close="filters.search = ''"
          >
            Pencarian: {{ filters.search }}
          </VChip>
          <VChip
            v-if="filters.stockStatus"
            size="small"
            closable
            @click:close="filters.stockStatus = ''"
          >
            Stok: {{ stockStatusOptions.find(s => s.value === filters.stockStatus)?.title }}
          </VChip>
          <VChip
            v-if="filters.activeStatus !== null"
            size="small"
            closable
            @click:close="filters.activeStatus = null"
          >
            Status: {{ filters.activeStatus ? 'Aktif' : 'Nonaktif' }}
          </VChip>
          <VBtn
            variant="text"
            size="small"
            color="error"
            @click="clearAllFilters"
          >
            Hapus Semua Filter
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <!-- No Results -->
    <VAlert
      v-if="!loading && filteredGroupedVariants.length === 0 && hasActiveFilters"
      type="info"
      variant="outlined"
      class="mb-6"
      title="Tidak ada hasil"
      text="Tidak ada variant yang sesuai dengan filter yang dipilih. Coba ubah filter pencarian."
    />

    <!-- Loading State -->
    <div v-if="loading" class="text-center pa-12">
      <VProgressCircular
        color="primary"
        indeterminate
        size="64"
      />
      <div class="mt-4 text-h6">Memuat variant...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="groupedVariants.length === 0" class="text-center pa-12">
      <VIcon
        icon="mdi-tune-variant"
        size="96"
        class="text-disabled mb-6"
      />
      <h3 class="text-h5 font-weight-bold mb-4">Belum Ada Variant Produk</h3>
      <p class="text-body-1 text-medium-emphasis mb-6">
        Buat variant untuk produk Anda agar dapat menawarkan pilihan yang beragam
      </p>
      <VBtn
        color="primary"
        size="large"
        prepend-icon="mdi-plus"
        @click="openCreateVariantDialog"
      >
        Buat Variant Pertama
      </VBtn>
    </div>

    <!-- Variant Cards Grid -->
    <VRow v-else>
      <VCol
        v-for="productGroup in filteredGroupedVariants"
        :key="productGroup.product.id_product"
        cols="12"
        md="6"
        lg="4"
      >
        <VCard
          class="variant-group-card h-100"
          elevation="2"
          :class="{ 'border-error': hasVariantWithCriticalStock(productGroup.variants) }"
        >
          <!-- Product Header -->
          <VCardTitle class="pa-4 pb-2">
            <div class="d-flex align-center gap-3">
              <VAvatar
                :image="productGroup.product.image_url"
                size="48"
                class="flex-shrink-0"
              >
                <VIcon v-if="!productGroup.product.image_url" icon="mdi-coffee" />
              </VAvatar>
              <div class="flex-grow-1 min-width-0">
                <h3 class="text-h6 font-weight-bold text-truncate">
                  {{ productGroup.product.name }}
                </h3>
                <p class="text-body-2 text-medium-emphasis mb-0">
                  SKU: {{ productGroup.product.sku || '-' }}
                </p>
              </div>
              <VChip
                :color="getProductVariantStatusColor(productGroup.variants)"
                size="small"
                variant="tonal"
              >
                {{ productGroup.variants.length }} Variant
              </VChip>
            </div>
          </VCardTitle>

          <VDivider />

          <!-- Variants List -->
          <VCardText class="pa-4">
            <VList class="pa-0">
              <VListItem
                v-for="(variant, index) in productGroup.variants"
                :key="variant.id_variant"
                class="pa-2 rounded-lg variant-item"
                :class="{ 'mb-2': index < productGroup.variants.length - 1 }"
              >
                <template #prepend>
                  <VAvatar
                    size="32"
                    :color="variant.is_active ? 'success' : 'error'"
                    variant="tonal"
                  >
                    <VIcon
                      :icon="variant.is_active ? 'mdi-check' : 'mdi-close'"
                      size="16"
                    />
                  </VAvatar>
                </template>

                <VListItemTitle class="text-sm font-weight-medium">
                  {{ variant.name }}
                </VListItemTitle>
                
                <VListItemSubtitle>
                  <div class="d-flex flex-column gap-1">
                    <span class="text-xs">SKU: {{ variant.sku }}</span>
                    <div class="d-flex align-center gap-2">
                      <span class="text-primary font-weight-medium">
                        {{ formatRupiah(variant.price || 0) }}
                      </span>
                      <VChip
                        v-if="variant.composition_summary"
                        :color="getStockStatusColor(variant.composition_summary.stock_status)"
                        size="x-small"
                        variant="tonal"
                      >
                        {{ getStockStatusText(variant.composition_summary.stock_status) }}
                      </VChip>
                    </div>
                  </div>
                </VListItemSubtitle>

                <template #append>
                  <div class="d-flex gap-1">
                    <!-- Composition Dialog -->
                    <VTooltip text="Kelola Komposisi">
                      <template #activator="{ props }">
                        <VBtn
                          v-bind="props"
                          icon="mdi-chef-hat"
                          size="small"
                          variant="text"
                          color="primary"
                          @click="openCompositionDialog(variant)"
                        />
                      </template>
                    </VTooltip>

                    <!-- Delete -->
                    <VTooltip text="Hapus Variant">
                      <template #activator="{ props }">
                        <VBtn
                          v-bind="props"
                          icon="mdi-delete"
                          size="small"
                          variant="text"
                          color="error"
                          @click="openDeleteDialog(variant)"
                        />
                      </template>
                    </VTooltip>
                  </div>
                </template>
              </VListItem>
            </VList>
          </VCardText>

          <VDivider />

          <!-- Card Footer -->
          <VCardActions class="pa-4">
            <div class="d-flex align-center justify-space-between w-100">
              <div>
                <VChip
                  :color="getOverallVariantStatus(productGroup.variants).color"
                  size="small"
                  variant="tonal"
                >
                  <VIcon
                    :icon="getOverallVariantStatus(productGroup.variants).icon"
                    size="14"
                    class="me-1"
                  />
                  {{ getOverallVariantStatus(productGroup.variants).text }}
                </VChip>
              </div>
              <div class="d-flex gap-1">
                <!-- HPP Setting untuk produk (ambil variant pertama) -->
                <VTooltip text="Setting HPP Produk">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="mdi-calculator-variant"
                      size="small"
                      variant="text"
                      color="warning"
                      @click="openProductHPPDialog(productGroup.variants[0])"
                    />
                  </template>
                </VTooltip>

                <!-- Tambah Variant -->
                <VTooltip text="Tambah Variant">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="mdi-plus"
                      size="small"
                      variant="text"
                      color="primary"
                      @click="openCreateVariantDialog"
                    />
                  </template>
                </VTooltip>

                <!-- Settings Produk - disabled for now -->
                <VTooltip text="Pengaturan Produk (Coming Soon)">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="mdi-cog"
                      size="small"
                      variant="text"
                      color="secondary"
                      disabled
                    />
                  </template>
                </VTooltip>
              </div>
            </div>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Composition Dialog -->
    <AddItemCompositionDialog
      v-model="compositionDialog"
      :variant="selectedVariant"
      @save="handleCompositionSave"
    />

    <!-- HPP Dialog untuk individual variant -->
    <HPPVariantBreakdownDialog
      v-model="hppDialog"
      :variant-id="selectedVariant?.id_variant"
      :variant-name="selectedVariant?.name"
      @hpp-updated="loadVariants"
      @price-updated="loadVariants"
    />

    <!-- Create Variant Dialog -->
    <CreateVariantDialog
      v-model="createVariantDialog"
      @save="handleVariantCreated"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus Variant"
      :item-name="selectedVariant?.name"
      :loading="deleteLoading"
      confirm-text="Hapus Variant"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { formatRupiah } from '@/@core/utils/formatters'
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import AddItemCompositionDialog from './AddItemCompositionDialog.vue'
import HPPVariantBreakdownDialog from '@/components/hpp/HPPVariantBreakdownDialog.vue'
import CreateVariantDialog from './CreateVariantDialog.vue'

interface Product {
  id: number
  id_product: number
  name: string
  sku?: string
  image_url?: string
}

interface Variant {
  id: number
  id_variant: number
  id_product: number
  name: string
  sku: string
  price: number
  cost_price?: number
  image?: string
  is_active: boolean
  product?: Product
  composition_summary?: {
    total_items: number
    critical_items: number
    total_cost: number
    stock_status: string
  }
}

interface ProductGroup {
  product: Product
  variants: Variant[]
}

interface Props {
  loading?: boolean
}

interface Emits {
  (e: 'refresh'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const loading = ref(false)
const variants = ref<Variant[]>([])
const deleteDialog = ref(false)
const deleteLoading = ref(false)
const selectedVariant = ref<Variant | null>(null)

// Dialog state
const compositionDialog = ref(false)
const hppDialog = ref(false)
const createVariantDialog = ref(false)

// Filter state
const filters = ref({
  search: '',
  stockStatus: '',
  activeStatus: null as boolean | null,
  sortBy: 'name_asc'
})

// Filter options
const stockStatusOptions = [
  { title: 'Semua Status', value: '' },
  { title: 'Stok Aman', value: 'safe' },
  { title: 'Stok Kritis', value: 'critical' },
  { title: 'Stok Habis', value: 'out' }
]

const sortOptions = [
  { title: 'Nama Produk A-Z', value: 'name_asc' },
  { title: 'Nama Produk Z-A', value: 'name_desc' },
  { title: 'Variant Terbanyak', value: 'variants_desc' },
  { title: 'Variant Tersedikit', value: 'variants_asc' }
]

// Computed
const hasActiveFilters = computed(() => {
  return !!(filters.value.search || filters.value.stockStatus || filters.value.activeStatus !== null || filters.value.sortBy !== 'name_asc')
})

const groupedVariants = computed(() => {
  const groups: { [key: number]: ProductGroup } = {}
  
  variants.value.forEach(variant => {
    const productId = variant.id_product
    if (!groups[productId]) {
      groups[productId] = {
        product: variant.product || {
          id: variant.id_product,
          id_product: variant.id_product,
          name: `Product ${variant.id_product}`,
          sku: ''
        },
        variants: []
      }
    }
    groups[productId].variants.push(variant)
  })
  
  return Object.values(groups)
})

const filteredGroupedVariants = computed(() => {
  let filtered = [...groupedVariants.value]

  // Search filter
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(group => 
      group.product.name.toLowerCase().includes(search) ||
      group.product.sku?.toLowerCase().includes(search) ||
      group.variants.some(variant => 
        variant.name.toLowerCase().includes(search) ||
        variant.sku.toLowerCase().includes(search)
      )
    )
  }

  // Stock status filter
  if (filters.value.stockStatus) {
    filtered = filtered.filter(group =>
      group.variants.some(variant => {
        const status = variant.composition_summary?.stock_status || 'safe'
        return status === filters.value.stockStatus
      })
    )
  }

  // Active status filter
  if (filters.value.activeStatus !== null) {
    filtered = filtered.filter(group =>
      group.variants.some(variant => variant.is_active === filters.value.activeStatus)
    )
  }

  // Sort
  switch (filters.value.sortBy) {
    case 'name_asc':
      filtered.sort((a, b) => a.product.name.localeCompare(b.product.name))
      break
    case 'name_desc':
      filtered.sort((a, b) => b.product.name.localeCompare(a.product.name))
      break
    case 'variants_desc':
      filtered.sort((a, b) => b.variants.length - a.variants.length)
      break
    case 'variants_asc':
      filtered.sort((a, b) => a.variants.length - b.variants.length)
      break
  }

  return filtered
})

// Methods
const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
  // Implement toast notifications here if needed
}

const loadVariants = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/variants', {
      params: {
        per_page: 1000,
        with_product: true,
        with_composition: true
      }
    })
    
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to load variants')
    }
    
    const rawVariants = response.data.data?.data || response.data.data || []
    variants.value = rawVariants.map((variant: any) => ({
      ...variant,
      is_active: variant.active !== undefined ? variant.active : variant.is_active,
      id: variant.id_variant || variant.id,
      id_product: variant.id_product || variant.product_id
    }))
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || error.message || 'Gagal memuat data variant'
    showNotification(errorMessage, 'error')
    variants.value = []
  } finally {
    loading.value = false
  }
}

const openCompositionDialog = (variant: Variant) => {
  selectedVariant.value = variant
  compositionDialog.value = true
}

const openProductHPPDialog = (firstVariant: Variant) => {
  if (firstVariant) {
    selectedVariant.value = firstVariant
    hppDialog.value = true
  }
}

const openHPPDialog = (variant: Variant) => {
  selectedVariant.value = variant
  hppDialog.value = true
}

const openDeleteDialog = (variant: Variant) => {
  selectedVariant.value = variant
  deleteDialog.value = true
}

const openCreateVariantDialog = () => {
  createVariantDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedVariant.value) return
  
  try {
    deleteLoading.value = true
    await axios.delete(`/api/variants/${selectedVariant.value.id_variant}`)
    showNotification('Variant berhasil dihapus', 'success')
    await loadVariants()
    deleteDialog.value = false
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || 'Gagal menghapus variant'
    showNotification(errorMessage, 'error')
  } finally {
    deleteLoading.value = false
  }
}

const getStockStatusColor = (status: string) => {
  switch (status) {
    case 'critical': return 'error'
    case 'out': return 'error'
    case 'safe': return 'success'
    default: return 'primary'
  }
}

const getStockStatusText = (status: string) => {
  switch (status) {
    case 'critical': return 'Kritis'
    case 'out': return 'Habis'
    case 'safe': return 'Aman'
    default: return 'Normal'
  }
}

const getProductVariantStatusColor = (variants: Variant[]) => {
  const activeCount = variants.filter(v => v.is_active).length
  if (activeCount === 0) return 'error'
  if (activeCount === variants.length) return 'success'
  return 'warning'
}

const hasVariantWithCriticalStock = (variants: Variant[]) => {
  return variants.some(variant => {
    const status = variant.composition_summary?.stock_status
    return status === 'critical' || status === 'out'
  })
}

const getOverallVariantStatus = (variants: Variant[]) => {
  const activeCount = variants.filter(v => v.is_active).length
  const criticalCount = variants.filter(v => {
    const status = v.composition_summary?.stock_status
    return status === 'critical' || status === 'out'
  }).length

  if (criticalCount > 0) {
    return {
      text: `${criticalCount} Stok Kritis`,
      color: 'error',
      icon: 'mdi-alert-circle'
    }
  }

  if (activeCount === variants.length) {
    return {
      text: 'Semua Aktif',
      color: 'success',
      icon: 'mdi-check-circle'
    }
  }

  if (activeCount === 0) {
    return {
      text: 'Semua Nonaktif',
      color: 'error',
      icon: 'mdi-close-circle'
    }
  }

  return {
    text: `${activeCount}/${variants.length} Aktif`,
    color: 'warning',
    icon: 'mdi-minus-circle'
  }
}

const clearAllFilters = () => {
  filters.value = {
    search: '',
    stockStatus: '',
    activeStatus: null,
    sortBy: 'name_asc'
  }
}

const handleCompositionSave = () => {
  loadVariants()
  emit('refresh')
}

const handleVariantCreated = () => {
  console.log('Variant created, refreshing data...')
  loadVariants()
  emit('refresh')
  // Dialog will be closed automatically from CreateVariantDialog component
}

// Lifecycle
onMounted(() => {
  loadVariants()
})
</script>

<style scoped>
.variant-group-card {
  transition: all 0.3s ease;
  cursor: pointer;
}

.variant-group-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.variant-item {
  transition: background-color 0.2s ease;
}

.variant-item:hover {
  background-color: rgba(var(--v-theme-surface-variant), 0.1);
}

.border-error {
  border: 1px solid rgb(var(--v-theme-error)) !important;
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
