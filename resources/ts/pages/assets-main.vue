<template>
  <div>
    <!-- Header Section -->
    <VCard class="mb-6">
      <VCardTitle class="d-flex justify-space-between align-center pa-6">
        <div>
          <h1 class="text-h4 font-weight-bold mb-2">
            <VIcon icon="tabler-building-store" class="me-3 text-primary" />
            Asset Management
          </h1>
          <p class="text-subtitle-1 text-medium-emphasis">
            Kelola aset fisik restoran, peralatan dapur, dan inventaris
          </p>
        </div>
        <div class="d-flex gap-3">
          <VBtn
            color="primary"
            prepend-icon="tabler-plus"
            @click="showCreateModal = true"
          >
            Tambah Asset
          </VBtn>
          <VBtn
            variant="outlined"
            color="secondary"
            prepend-icon="tabler-refresh"
            :loading="loading"
            @click="refreshAssets"
          >
            Refresh
          </VBtn>
        </div>
      </VCardTitle>
    </VCard>

    <!-- Statistics Cards -->
    <VRow class="mb-6">
      <VCol cols="12" md="3">
        <VCard>
          <VCardText class="text-center pa-6">
            <VAvatar size="56" color="primary" class="mb-4">
              <VIcon icon="tabler-box" size="28" />
            </VAvatar>
            <div class="text-h4 font-weight-bold">{{ Array.isArray(assets) ? assets.length : 0 }}</div>
            <div class="text-subtitle-2 text-medium-emphasis">Total Assets</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" md="3">
        <VCard>
          <VCardText class="text-center pa-6">
            <VAvatar size="56" color="success" class="mb-4">
              <VIcon icon="tabler-check-circle" size="28" />
            </VAvatar>
            <div class="text-h4 font-weight-bold">{{ Array.isArray(activeAssets) ? activeAssets.length : 0 }}</div>
            <div class="text-subtitle-2 text-medium-emphasis">Active Assets</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" md="3">
        <VCard>
          <VCardText class="text-center pa-6">
            <VAvatar size="56" color="warning" class="mb-4">
              <VIcon icon="tabler-tool" size="28" />
            </VAvatar>
            <div class="text-h4 font-weight-bold">{{ Array.isArray(maintenanceAssets) ? maintenanceAssets.length : 0 }}</div>
            <div class="text-subtitle-2 text-medium-emphasis">In Maintenance</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" md="3">
        <VCard>
          <VCardText class="text-center pa-6">
            <VAvatar size="56" color="info" class="mb-4">
              <VIcon icon="tabler-currency-dollar" size="28" />
            </VAvatar>
            <div class="text-h4 font-weight-bold">{{ formatRupiah(totalValue) }}</div>
            <div class="text-subtitle-2 text-medium-emphasis">Total Value</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Search and Filter Section -->
    <VCard class="mb-6">
      <VCardTitle class="pa-6 pb-4">
        <VIcon icon="tabler-filter" class="me-2" />
        Filter & Pencarian
      </VCardTitle>
      <VCardText class="pa-6 pt-0">
        <VRow>
          <!-- Search Field -->
          <VCol cols="12" md="4">
            <VTextField
              v-model="searchQuery"
              label="Cari assets..."
              prepend-inner-icon="tabler-search"
              variant="outlined"
              density="compact"
              clearable
              @input="debounceSearch"
            />
          </VCol>
          
          <!-- Category Filter -->
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.category"
              label="Kategori"
              :items="availableCategories"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="applyFilters"
            >
              <template #prepend-item>
                <VListItem title="Semua Kategori" value="" />
                <VDivider />
              </template>
            </VSelect>
          </VCol>
          
          <!-- Location Filter -->
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.location"
              label="Lokasi"
              :items="availableLocations"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="applyFilters"
            >
              <template #prepend-item>
                <VListItem title="Semua Lokasi" value="" />
                <VDivider />
              </template>
            </VSelect>
          </VCol>
          
          <!-- Status Filter -->
          <VCol cols="12" md="2">
            <VSelect
              v-model="filters.status"
              label="Status"
              :items="[
                { title: 'Active', value: 'active' },
                { title: 'Inactive', value: 'inactive' },
                { title: 'Maintenance', value: 'maintenance' },
                { title: 'Disposed', value: 'disposed' }
              ]"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="applyFilters"
            >
              <template #prepend-item>
                <VListItem title="Semua Status" value="" />
                <VDivider />
              </template>
            </VSelect>
          </VCol>
          
          <!-- View Mode & Actions -->
          <VCol cols="12" md="2">
            <div class="d-flex gap-2">
              <VBtn
                size="small"
                variant="outlined"
                color="secondary"
                @click="clearFilters"
              >
                Clear
              </VBtn>
              
              <VBtnToggle
                v-model="viewMode"
                variant="outlined"
                color="primary"
                density="compact"
              >
                <VBtn value="grid" icon="tabler-grid-dots" />
                <VBtn value="list" icon="tabler-list" />
              </VBtnToggle>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Bulk Actions Bar -->
    <VCard v-if="selectedAssets.length > 0" class="mb-6" color="blue-lighten-5">
      <VCardText class="pa-4">
        <div class="d-flex justify-space-between align-center">
          <div>
            <VChip color="primary" variant="outlined">
              {{ selectedAssets.length }} asset{{ selectedAssets.length !== 1 ? 's' : '' }} dipilih
            </VChip>
          </div>
          <div class="d-flex gap-2">
            <VBtn
              color="error"
              variant="outlined"
              prepend-icon="tabler-trash"
              @click="showBulkDeleteModal = true"
            >
              Hapus Terpilih
            </VBtn>
            <VBtn
              color="primary"
              variant="outlined"
              prepend-icon="tabler-edit"
              @click="bulkUpdateStatus"
            >
              Update Status
            </VBtn>
            <VBtn
              variant="text"
              @click="clearSelection"
            >
              Clear Selection
            </VBtn>
          </div>
        </div>
      </VCardText>
    </VCard>

    <!-- Content Area -->
    <VCard>
      <!-- Loading State -->
      <div v-if="loading && !hasAssets" class="text-center pa-12">
        <VProgressCircular
          indeterminate
          color="primary"
          size="64"
        />
        <div class="text-h6 mt-4">Memuat assets...</div>
      </div>

      <!-- Empty State -->
      <div v-else-if="isEmpty" class="text-center pa-12">
        <VAvatar size="120" color="grey-lighten-3" class="mb-6">
          <VIcon icon="tabler-box-off" size="60" color="grey-darken-1" />
        </VAvatar>
        <h3 class="text-h5 font-weight-bold mb-4">Tidak ada assets ditemukan</h3>
        <p class="text-body-1 text-medium-emphasis mb-6">
          {{ searchQuery ? 'Tidak ada assets yang sesuai dengan pencarian Anda' : 'Mulai dengan menambahkan asset fisik restoran pertama' }}
        </p>
        <VBtn 
          v-if="!searchQuery"
          color="primary"
          prepend-icon="tabler-plus"
          @click="showCreateModal = true"
        >
          Tambah Asset Pertama
        </VBtn>
      </div>

      <!-- Assets Content -->
      <div v-else>
        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="pa-6">
          <VRow>
            <VCol
              v-for="asset in assets"
              :key="asset.id"
              cols="12"
              sm="6"
              md="4"
              lg="3"
            >
              <VCard
                :class="['asset-card', { 'selected': selectedAssets.includes(asset.id) }]"
                @click="viewAsset(asset)"
                hover
              >
                <VCardText class="pa-4">
                  <div class="d-flex justify-space-between align-start mb-3">
                    <VChip
                      :color="getStatusColor(asset.status)"
                      size="small"
                      variant="tonal"
                    >
                      {{ asset.status }}
                    </VChip>
                    <VCheckbox
                      :model-value="selectedAssets.includes(asset.id)"
                      @click.stop
                      @update:model-value="toggleAssetSelection(asset.id)"
                      density="compact"
                      hide-details
                    />
                  </div>
                  
                  <div class="mb-3">
                    <h4 class="text-subtitle-1 font-weight-bold mb-1">{{ asset.name }}</h4>
                    <p class="text-body-2 text-medium-emphasis">{{ asset.asset_code }}</p>
                  </div>
                  
                  <VDivider class="mb-3" />
                  
                  <div class="text-body-2 space-y-1">
                    <div class="d-flex align-center mb-1">
                      <VIcon icon="tabler-category" size="16" class="me-2" />
                      {{ asset.category }}
                    </div>
                    <div class="d-flex align-center mb-1">
                      <VIcon icon="tabler-map-pin" size="16" class="me-2" />
                      {{ asset.location }}
                    </div>
                    <div class="d-flex align-center mb-1">
                      <VIcon icon="tabler-currency-dollar" size="16" class="me-2" />
                      {{ formatRupiah(asset.purchase_price) }}
                    </div>
                  </div>
                </VCardText>
                
                <VCardActions class="pa-4 pt-0">
                  <VBtn
                    size="small"
                    variant="outlined"
                    color="primary"
                    @click.stop="editAsset(asset)"
                  >
                    Edit
                  </VBtn>
                  <VBtn
                    size="small"
                    variant="outlined"
                    color="error"
                    @click.stop="deleteAsset(asset)"
                  >
                    Hapus
                  </VBtn>
                </VCardActions>
              </VCard>
            </VCol>
          </VRow>
        </div>

        <!-- List View -->
        <div v-else>
          <VDataTable
            :headers="tableHeaders"
            :items="assets"
            :loading="loading"
            item-value="id"
            class="elevation-0"
            show-select
            v-model="selectedAssets"
          >
            <template #item.name="{ item }">
              <div>
                <div class="font-weight-bold">{{ item.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.asset_code }}</div>
              </div>
            </template>
            
            <template #item.status="{ item }">
              <VChip
                :color="getStatusColor(item.status)"
                size="small"
                variant="tonal"
              >
                {{ item.status }}
              </VChip>
            </template>
            
            <template #item.condition="{ item }">
              <VChip
                :color="getConditionColor(item.condition)"
                size="small"
                variant="outlined"
              >
                {{ item.condition }}
              </VChip>
            </template>
            
            <template #item.purchase_price="{ item }">
              {{ formatRupiah(item.purchase_price) }}
            </template>
            
            <template #item.actions="{ item }">
              <div class="d-flex gap-1">
                <VBtn
                  size="small"
                  icon="tabler-eye"
                  variant="text"
                  @click="viewAsset(item)"
                />
                <VBtn
                  size="small"
                  icon="tabler-edit"
                  variant="text"
                  color="primary"
                  @click="editAsset(item)"
                />
                <VBtn
                  size="small"
                  icon="tabler-trash"
                  variant="text"
                  color="error"
                  @click="deleteAsset(item)"
                />
              </div>
            </template>
          </VDataTable>
        </div>

        <!-- Pagination -->
        <div v-if="hasAssets" class="pa-6 border-t">
          <div class="d-flex justify-space-between align-center">
            <div class="text-body-2 text-medium-emphasis">
              Menampilkan {{ pagination.from }} sampai {{ pagination.to }} dari {{ pagination.total }} hasil
            </div>
            
            <VPagination
              v-model="pagination.current_page"
              :length="pagination.last_page"
              :total-visible="5"
              @update:model-value="fetchAssets"
            />
          </div>
        </div>
      </div>
    </VCard>

    <!-- Create/Edit Asset Modal -->
    <AssetFormDialog
      v-model:show="showCreateModal"
      :asset="editingAsset"
      @saved="handleAssetSaved"
    />

    <!-- Asset Details Modal -->
    <AssetDetailsDialog
      v-model:show="showDetailsModal"
      :asset="selectedAsset"
      @edit="editAsset"
      @delete="deleteAsset"
    />

    <!-- Bulk Delete Confirmation Dialog -->
    <VDialog v-model="showBulkDeleteModal" max-width="500">
      <VCard>
        <VCardTitle class="text-h5">
          <VIcon icon="tabler-alert-triangle" class="me-2 text-warning" />
          Konfirmasi Hapus Massal
        </VCardTitle>
        <VCardText>
          <p class="mb-4">
            Apakah Anda yakin ingin menghapus <strong>{{ selectedAssets.length }}</strong> 
            asset{{ selectedAssets.length !== 1 ? 's' : '' }} yang dipilih?
          </p>
          <VAlert type="warning" variant="tonal" class="mb-0">
            Tindakan ini tidak dapat dibatalkan!
          </VAlert>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            @click="showBulkDeleteModal = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            :loading="loading"
            @click="confirmBulkDelete"
          >
            Hapus
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAssetsTest } from '@/composables/useAssetsTest'
import type { Asset } from '@/composables/useAssetsTest'
import { formatRupiah } from '@/@core/utils/formatters'

// Component imports
import AssetFormDialog from '@/components/assets/AssetFormDialog.vue'
import AssetDetailsDialog from '@/components/assets/AssetDetailsDialog.vue'

// Composables
const {
  assets,
  loading,
  error,
  pagination,
  filters,
  hasAssets,
  isEmpty,
  activeAssets,
  maintenanceAssets,
  fetchAssets,
  searchAssets,
  filterAssets,
  deleteAsset: deleteAssetAPI,
  bulkDeleteAssets,
  resetFilters,
  getCategories,
  getLocations
} = useAssetsTest()

// Reactive state
const searchQuery = ref('')
const viewMode = ref<'grid' | 'list'>('grid')
const selectedAssets = ref<number[]>([])
const showCreateModal = ref(false)
const showDetailsModal = ref(false)
const showBulkDeleteModal = ref(false)
const selectedAsset = ref<Asset | null>(null)
const editingAsset = ref<Asset | null>(null)

// Available filter options
const availableCategories = ref<string[]>([])
const availableLocations = ref<string[]>([])

// Table headers for list view
const tableHeaders = [
  { title: 'Asset', value: 'name', sortable: true },
  { title: 'Kategori', value: 'category', sortable: true },
  { title: 'Lokasi', value: 'location', sortable: true },
  { title: 'Status', value: 'status', sortable: true },
  { title: 'Kondisi', value: 'condition', sortable: true },
  { title: 'Harga', value: 'purchase_price', sortable: true },
  { title: 'Aksi', value: 'actions', sortable: false }
]

// Computed properties
const totalValue = computed(() => {
  if (!Array.isArray(assets.value)) return 0
  return assets.value.reduce((total: number, asset: Asset) => {
    return total + (asset.purchase_price || 0)
  }, 0)
})

// Utility functions
const getStatusColor = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'secondary'
    case 'maintenance': return 'warning'
    case 'disposed': return 'error'
    default: return 'primary'
  }
}

const getConditionColor = (condition: string) => {
  switch (condition) {
    case 'excellent': return 'success'
    case 'good': return 'info'
    case 'fair': return 'warning'
    case 'poor': return 'orange'
    case 'damaged': return 'error'
    default: return 'secondary'
  }
}

// Search debouncing
let searchTimeout: NodeJS.Timeout
const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    searchAssets(searchQuery.value)
  }, 300)
}

// Filter management
const applyFilters = () => {
  filterAssets(filters.value)
}

const clearFilters = () => {
  searchQuery.value = ''
  resetFilters()
}

// Asset selection
const toggleAssetSelection = (assetId: number) => {
  const index = selectedAssets.value.indexOf(assetId)
  if (index > -1) {
    selectedAssets.value.splice(index, 1)
  } else {
    selectedAssets.value.push(assetId)
  }
}

const clearSelection = () => {
  selectedAssets.value = []
}

// Asset actions
const viewAsset = (asset: Asset) => {
  selectedAsset.value = asset
  showDetailsModal.value = true
}

const editAsset = (asset: Asset) => {
  editingAsset.value = asset
  showCreateModal.value = true
}

const deleteAsset = async (asset: Asset) => {
  if (confirm(`Apakah Anda yakin ingin menghapus ${asset.name}?`)) {
    try {
      await deleteAssetAPI(asset.id)
      selectedAssets.value = selectedAssets.value.filter(id => id !== asset.id)
    } catch (err) {
      console.error('Error deleting asset:', err)
    }
  }
}

const confirmBulkDelete = async () => {
  try {
    await bulkDeleteAssets(selectedAssets.value)
    selectedAssets.value = []
    showBulkDeleteModal.value = false
  } catch (err) {
    console.error('Error bulk deleting assets:', err)
  }
}

const bulkUpdateStatus = () => {
  // TODO: Implement bulk status update
}

const refreshAssets = () => {
  fetchAssets()
}

const handleAssetSaved = () => {
  showCreateModal.value = false
  editingAsset.value = null
  fetchAssets()
}

// Initialize data
const loadFilterOptions = async () => {
  try {
    const [categories, locations] = await Promise.all([
      getCategories(),
      getLocations()
    ])
    availableCategories.value = categories || []
    availableLocations.value = locations || []
  } catch (err) {
    console.error('Error loading filter options:', err)
  }
}

// Lifecycle
onMounted(async () => {
  try {
    await Promise.all([
      fetchAssets(),
      loadFilterOptions()
    ])
  } catch (error) {
    console.error('Error loading data:', error)
  }
})
</script>

<style scoped>
.asset-card {
  transition: all 0.2s ease-in-out;
  cursor: pointer;
}

.asset-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.asset-card.selected {
  border: 2px solid rgb(var(--v-theme-primary));
}

.space-y-1 > * + * {
  margin-top: 0.25rem;
}
</style>
