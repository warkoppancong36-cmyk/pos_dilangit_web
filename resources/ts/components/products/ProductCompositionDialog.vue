<template>
  <VDialog
    v-model="dialogModel"
    max-width="900"
    persistent
  >
    <VCard>
      <!-- Header -->
      <VCardTitle class="d-flex align-center justify-between bg-primary text-white">
        <div class="d-flex align-center gap-3">
          <VIcon icon="mdi-chef-hat" size="24" />
          <div>
            <h3 class="text-h6 font-weight-bold">Kelola Komposisi Item</h3>
            <p class="text-body-2 ma-0 opacity-90">
              {{ product?.name || 'Produk' }}
            </p>
          </div>
        </div>
        <VBtn
          icon="mdi-close"
          variant="text"
          size="small"
          @click="closeDialog"
        />
      </VCardTitle>

      <VCardText class="pa-6">
        <!-- Product Info Summary -->
        <VCard variant="tonal" class="mb-6">
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-4">
              <VAvatar
                :image="product?.image_url"
                size="64"
              >
                <VIcon v-if="!product?.image_url" icon="mdi-coffee" />
              </VAvatar>
              <div class="flex-grow-1">
                <h4 class="text-h6 font-weight-bold mb-1">{{ product?.name }}</h4>
                <p class="text-body-2 text-medium-emphasis mb-1">
                  SKU: {{ product?.sku || 'SKU-' + product?.id_product }}
                </p>
                <div class="d-flex align-center gap-4 mb-0">
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Harga: {{ formatRupiah(product?.price || 0) }}
                  </p>
                  <p class="text-body-2 text-info mb-0 font-weight-medium">
                    HPP: {{ formatRupiah(productHPP) }}
                  </p>
                  <p class="text-body-2 text-success mb-0 font-weight-medium" v-if="product?.price && productHPP">
                    Margin: {{ formatRupiah(productMargin) }}
                  </p>
                </div>
              </div>
              <VChip
                :color="getStockStatusColor()"
                variant="tonal"
              >
                {{ getStockStatusText() }}
              </VChip>
            </div>
          </VCardText>
        </VCard>

        <!-- Financial Summary Cards -->
        <VRow class="mb-6">
          <VCol cols="12" md="3">
            <VCard variant="outlined" color="info">
              <VCardText class="text-center pa-3">
                <VIcon icon="mdi-calculator" size="20" class="mb-1" />
                <div class="text-body-1 font-weight-bold">
                  {{ formatRupiah(productHPP) }}
                  <VProgressCircular
                    v-if="hppLoading"
                    indeterminate
                    size="12"
                    width="2"
                    class="ml-1"
                  />
                </div>
                <div class="text-caption">HPP</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" md="3">
            <VCard variant="outlined" color="primary">
              <VCardText class="text-center pa-3">
                <VIcon icon="mdi-currency-usd" size="20" class="mb-1" />
                <div class="text-body-1 font-weight-bold">{{ formatRupiah(product?.price || 0) }}</div>
                <div class="text-caption">Harga Jual</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" md="3">
            <VCard variant="outlined" color="success">
              <VCardText class="text-center pa-3">
                <VIcon icon="mdi-trending-up" size="20" class="mb-1" />
                <div class="text-body-1 font-weight-bold">
                  {{ formatRupiah(productMargin) }}
                </div>
                <div class="text-caption">Margin</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" md="3">
            <VCard variant="outlined" color="warning">
              <VCardText class="text-center pa-3">
                <VIcon icon="mdi-percent" size="20" class="mb-1" />
                <div class="text-body-1 font-weight-bold">
                  {{ productMarginPercentage }}%
                </div>
                <div class="text-caption">Margin %</div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <!-- Add New Item Form -->
        <VCard class="mb-6" elevation="2">
          <VCardTitle class="bg-grey-lighten-4 py-3">
            <div class="d-flex align-center justify-space-between w-100">
              <div class="d-flex align-center">
                <VIcon :icon="editMode ? 'mdi-pencil-circle' : 'mdi-plus-circle'" class="me-2" />
                {{ editMode ? 'Edit Item Komposisi' : 'Tambah Item Komposisi' }}
              </div>
              <!-- Items Stock Info -->
            </div>
          </VCardTitle>
          <VCardText class="pa-4">
            <VRow>
              <VCol cols="12" md="4">
                <VAutocomplete
                  v-model="newItem.itemId"
                  :items="availableItemsWithStock"
                  :loading="loadingItems"
                  item-title="name"
                  item-value="id"
                  label="Pilih Item"
                  placeholder="Cari item (termasuk yang stok kosong)..."
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  clearable
                  :no-data-text="getNoDataText()"
                >
                  <template #item="{ props, item }">
                    <VListItem 
                      v-bind="props"
                      :class="{ 'text-medium-emphasis': getItemStock(item.raw) <= 0 }"
                    >
                      <template #prepend>
                        <VAvatar 
                          size="32" 
                          class="me-3"
                          :color="getItemStock(item.raw) <= 0 ? 'warning' : 'primary'"
                          variant="tonal"
                        >
                          <VIcon 
                            icon="mdi-package-variant" 
                            :color="getItemStock(item.raw) <= 0 ? 'warning' : 'primary'" 
                          />
                        </VAvatar>
                      </template>
                      <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                      <VListItemSubtitle class="text-medium-emphasis">
                        <span v-if="getItemStock(item.raw) <= 0" class="text-warning">
                          <VIcon icon="mdi-alert-circle" size="12" class="me-1" />
                          Stok Kosong - Masih bisa digunakan
                        </span>
                        <span v-else>
                          Stok: {{ getItemStock(item.raw) }} {{ item.raw.unit }}
                        </span>
                      </VListItemSubtitle>
                      <template #append>
                        <VChip 
                          v-if="getItemStock(item.raw) > 0"
                          size="x-small" 
                          :color="getStockChipColor(item.raw)"
                          variant="tonal"
                        >
                          {{ getStockLabel(item.raw) }}
                        </VChip>
                        <VChip 
                          v-else
                          size="x-small" 
                          color="warning"
                          variant="tonal"
                          prepend-icon="mdi-alert"
                        >
                          Kosong
                        </VChip>
                      </template>
                    </VListItem>
                  </template>
                </VAutocomplete>
              </VCol>
              <VCol cols="12" md="2">
                <VTextField
                  v-model.number="newItem.quantity"
                  type="number"
                  label="Jumlah"
                  variant="outlined"
                  min="0"
                  step="0.01"
                />
              </VCol>
              <VCol cols="12" md="2">
                <VSwitch
                  v-model="newItem.isCritical"
                  label="Item Kritis"
                  color="error"
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="2">
                <VBtn
                  :color="editMode ? 'success' : 'primary'"
                  block
                  :prepend-icon="editMode ? 'mdi-check' : 'mdi-plus'"
                  @click="editMode ? updateItem() : addItem()"
                  :disabled="!canAddItem"
                >
                  {{ editMode ? 'Update' : 'Tambah' }}
                </VBtn>
                <VBtn
                  v-if="editMode"
                  color="error"
                  variant="outlined"
                  block
                  prepend-icon="mdi-close"
                  class="mt-2"
                  @click="cancelEdit"
                >
                  Batal
                </VBtn>
              </VCol>
            </VRow>
            
            <!-- Warning for out of stock items -->
            <VAlert
              v-if="isSelectedItemOutOfStock"
              color="warning"
              variant="tonal"
              class="mt-4"
              prepend-icon="mdi-alert"
            >
              <VAlertTitle>Item Stok Kosong</VAlertTitle>
              <div>Item yang dipilih sedang kosong stok, namun masih bisa ditambahkan ke komposisi. Pastikan untuk memperbarui stok sebelum produksi.</div>
            </VAlert>
          </VCardText>
        </VCard>

        <!-- Current Composition Items -->
        <VCard elevation="2">
          <VCardTitle class="bg-grey-lighten-4 py-3">
            <VIcon icon="mdi-format-list-bulleted" class="me-2" />
            Daftar Item Komposisi ({{ compositionItems.length }})
          </VCardTitle>
          <VCardText class="pa-0">
            <div v-if="compositionItems.length === 0" class="text-center pa-8">
              <VIcon
                icon="mdi-package-variant-closed"
                size="64"
                class="text-disabled mb-4"
              />
              <h4 class="text-h6 mb-2">Belum ada item komposisi</h4>
              <p class="text-body-2 text-medium-emphasis">
                Tambahkan item yang dibutuhkan untuk membuat produk ini
              </p>
            </div>
            
            <VList v-else>
              <VListItem
                v-for="(item, index) in compositionItems"
                :key="item.id || index"
                class="border-b"
              >
                <template #prepend>
                  <VAvatar size="40" class="me-3">
                    <VIcon 
                      :icon="item.item?.inventory && item.item.inventory.current_stock >= item.quantity_needed ? 'mdi-check-circle' : 'mdi-alert-circle'"
                      :color="item.item?.inventory && item.item.inventory.current_stock >= item.quantity_needed ? 'success' : 'error'"
                    />
                  </VAvatar>
                </template>

                <VListItemTitle class="font-weight-medium">
                  {{ item.item?.name || 'Item tidak ditemukan' }}
                  <VChip
                    v-if="item.is_critical"
                    color="error"
                    size="x-small"
                    variant="tonal"
                    class="ms-2"
                  >
                    Kritis
                  </VChip>
                </VListItemTitle>
                
                <VListItemSubtitle>
                  <div class="d-flex align-center gap-4 mt-1">
                    <span>Dibutuhkan: {{ parseFloat(item.quantity_needed.toString()) }} {{ item.unit }}</span>
                    <span v-if="item.item?.inventory" :class="item.item.inventory.current_stock >= item.quantity_needed ? 'text-success' : 'text-error'">
                      Stok: {{ item.item.inventory.current_stock }} {{ item.unit }}
                    </span>
                    <span v-if="item.formatted_total_cost" class="text-primary">
                      {{ item.formatted_total_cost }}
                    </span>
                  </div>
                </VListItemSubtitle>

                <template #append>
                  <div class="d-flex gap-2">
                    <VBtn
                      icon="mdi-pencil"
                      variant="text"
                      size="small"
                      color="primary"
                      @click="editItem(item, index)"
                    />
                    <VBtn
                      icon="mdi-delete"
                      variant="text"
                      size="small"
                      color="error"
                      @click="removeItem(index)"
                    />
                  </div>
                </template>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCardText>

      <!-- Actions -->
      <VCardActions class="pa-6 pt-0">
        <VBtn
          color="warning"
          variant="outlined"
          prepend-icon="mdi-calculator"
          @click="openHPPDialog"
        >
          Setting HPP
        </VBtn>
        <VSpacer />
        <VBtn
          variant="outlined"
          @click="closeDialog"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          @click="saveComposition"
          :loading="loading"
        >
          Simpan Komposisi
        </VBtn>
      </VCardActions>
    </VCard>

    <!-- Error Snackbar -->
    <VSnackbar
      v-model="errorSnackbar"
      color="error"
      location="top"
      :timeout="5000"
    >
      {{ errorMessage }}
      <template #actions>
        <VBtn
          color="white"
          variant="text"
          @click="errorSnackbar = false"
        >
          Tutup
        </VBtn>
      </template>
    </VSnackbar>

    <!-- Success Snackbar -->
    <VSnackbar
      v-model="successSnackbar"
      color="success"
      location="top"
      :timeout="3000"
    >
      {{ successMessage }}
      <template #actions>
        <VBtn
          color="white"
          variant="text"
          @click="successSnackbar = false"
        >
          Tutup
        </VBtn>
      </template>
    </VSnackbar>

  </VDialog>

  <!-- HPP Breakdown Dialog -->
  <HPPBreakdownDialog
    v-model="hppDialog"
    :product-id="parseInt(String(product?.id_product || product?.id || '0'))"
    :product-name="product?.product_name || product?.name"
    @hpp-updated="onHPPUpdated"
    @price-updated="emit('refresh')"
  />
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { formatRupiah } from '@/@core/utils/formatters'
import { ProductItemsApi } from '@/utils/api/ProductItemsApi'
import { ItemsApi } from '@/utils/api/ItemsApi'
import HPPBreakdownDialog from '@/components/hpp/HPPBreakdownDialog.vue'
import { useHPP } from '@/composables/useHPP'

interface CompositionItem {
  id?: string
  id_product_item?: string | number
  item_id?: string | number // Add item_id at root level (from backend)
  item?: {
    id?: string | number
    id_item?: string | number // Add id_item (from backend)
    name: string
    inventory?: {
      current_stock: number
    }
  }
  quantity_needed: number
  unit: string
  is_critical: boolean
  formatted_total_cost?: string
  notes?: string
}

interface Product {
  id_product: string
  name: string
  sku?: string
  price?: number
  hpp?: number
  cost?: number
  image_url?: string
  id?: string // for backward compatibility
  product_name?: string // for backward compatibility
}

interface AvailableItem {
  id: string | number
  name: string
  unit: string
  current_stock: number
  stock?: number
}

// New item form interface
interface NewItemForm {
  itemId: string | number | null
  quantity: number
  unit: string
  isCritical: boolean
}

interface ProductItemFormData {
  product_id: number
  item_id: number
  quantity_needed: number
  unit: string
  is_critical: boolean
  notes: string
}

interface Props {
  modelValue: boolean
  product: Product | null
  items: CompositionItem[]
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'save', data: { product: Product, items: CompositionItem[] }): void
  (e: 'refresh'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Dialog state
const dialogModel = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Form state
const loading = ref(false)
const compositionItems = ref<CompositionItem[]>([])

// Snackbar state
const errorSnackbar = ref(false)
const successSnackbar = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Edit state
const editMode = ref(false)
const editIndex = ref(-1)

// HPP Dialog state
const hppDialog = ref(false)

// HPP Composable
const {
  loading: hppLoading,
  currentHPPBreakdown,
  getProductHPPBreakdown,
  formatCurrency,
} = useHPP()

// Computed HPP values
const productHPP = computed(() => {
  return currentHPPBreakdown.value?.total_hpp || props.product?.hpp || props.product?.cost || 0
})

const productMargin = computed(() => {
  const price = props.product?.price || 0
  const hpp = productHPP.value
  return price - hpp
})

const productMarginPercentage = computed(() => {
  const price = props.product?.price || 0
  const hpp = productHPP.value
  if (price <= 0) return 0
  return Math.round(((price - hpp) / price) * 100)
})

// New item form
const newItem = ref<NewItemForm>({
  itemId: null as string | number | null,
  quantity: 1,
  unit: 'pcs',
  isCritical: false
})

// Available items from API
const availableItems = ref<AvailableItem[]>([])
const loadingItems = ref(false)

// Initial composition data for comparison during save
const initialCompositionItems = ref<CompositionItem[]>([])

// Fetch product composition from API
const fetchProductComposition = async () => {
  if (!props.product) return
  
  try {
    const productId = props.product.id_product || props.product.id
    
    // Fetch product items for this specific product
    const response = await ProductItemsApi.getAll({ 
      product_id: productId,
      page: 1,
      per_page: 100000
    })
    
    if (response.success && response.data?.data) {
      compositionItems.value = response.data.data
      // Store initial data for comparison during save
      initialCompositionItems.value = JSON.parse(JSON.stringify(response.data.data))
      
      // Debug: Log data structure
      console.log('📥 Fetched composition items:', response.data.data)
      if (response.data.data.length > 0) {
        console.log('📥 First item structure:', {
          full: response.data.data[0],
          item: response.data.data[0].item,
          item_id: response.data.data[0].item_id,
          id_product_item: response.data.data[0].id_product_item
        })
      }
    }
  } catch (error) {
    console.error('Error fetching product composition:', error)
  }
}

// Fetch available items from API
const fetchAvailableItems = async () => {
  loadingItems.value = true
  try {
    const response = await ItemsApi.getAll({ 
      page: 1, 
      per_page: 100000,
      active: true 
    })
    
    if (response.success && response.data?.data) {
      availableItems.value = response.data.data.map((item: any) => ({
        id: item.id_item?.toString() || item.id?.toString(),
        name: item.name,
        current_stock: item.inventory?.current_stock || 0,
        unit: item.unit || 'pcs'
      }))
    }
  } catch (error) {
    console.error('Error fetching available items:', error)
    // Fallback to mock data if API fails
    availableItems.value = [

    ]
  } finally {
    loadingItems.value = false
  }
}

// Computed
const canAddItem = computed(() => {
  return newItem.value.itemId && 
         newItem.value.quantity > 0
})

const selectedItemUnit = computed(() => {
  if (!newItem.value.itemId) return null
  const selectedItem = availableItems.value.find(item => item.id === newItem.value.itemId)
  return selectedItem?.unit || null
})

const selectedItemStock = computed(() => {
  if (!newItem.value.itemId) return null
  const selectedItem = availableItems.value.find(item => item.id === newItem.value.itemId)
  return selectedItem ? getItemStock(selectedItem) : null
})

const isSelectedItemOutOfStock = computed(() => {
  return selectedItemStock.value !== null && selectedItemStock.value <= 0
})

// Show all items including those with stock 0 or empty
const availableItemsWithStock = computed(() => {
  if (!Array.isArray(availableItems.value)) return []
  return availableItems.value // Remove filter to show all items
})

// Helper function to get item stock
const getItemStock = (item: AvailableItem): number => {
  return item.current_stock || item.stock || 0
}

// Get no data text based on loading state and available items
const getNoDataText = (): string => {
  if (loadingItems.value) return 'Memuat items...'
  if (!availableItems.value || availableItems.value.length === 0) return 'Tidak ada item tersedia'
  return 'Tidak ada item yang ditemukan'
}

// Get stock chip color
const getStockChipColor = (item: any): string => {
  const stock = getItemStock(item)
  if (stock > 50) return 'success'
  if (stock > 20) return 'warning'
  if (stock > 0) return 'error'
  return 'grey'
}

// Get stock label
const getStockLabel = (item: any): string => {
  const stock = getItemStock(item)
  if (stock > 50) return 'Aman'
  if (stock > 20) return 'Rendah'
  if (stock > 0) return 'Kritis'
  return 'Habis'
}

// Watch for props changes
watch(() => props.items, (newItems) => {
  if (newItems && newItems.length > 0) {
  }
  compositionItems.value = [...newItems]
}, { immediate: true })

// Watch for dialog opening to fetch composition data
watch(() => props.modelValue, async (isOpen) => {
  if (isOpen && props.product) {
    fetchProductComposition()
    // Load HPP data when dialog opens
    await loadHPPData()
  }
})

// Load HPP Data
const loadHPPData = async () => {
  if (props.product && (props.product.id_product || props.product.id)) {
    try {
      const productId = parseInt(String(props.product.id_product || props.product.id))
      await getProductHPPBreakdown(productId, 'latest') // Default to latest method
    } catch (error) {
      console.warn('Could not load HPP data:', error)
    }
  }
}

// Fetch available items when component mounts
onMounted(() => {
  fetchAvailableItems()
})

// HPP Dialog functions
const openHPPDialog = () => {
  hppDialog.value = true
}

// Handle HPP Update
const onHPPUpdated = async () => {
  // Reload HPP data after update
  await loadHPPData()
  // Emit refresh to parent
  emit('refresh')
}

// Methods
const getStockStatusColor = () => {
  const hasLowStock = compositionItems.value.some(item => 
    item.item?.inventory && item.item.inventory.current_stock < item.quantity_needed
  )
  return hasLowStock ? 'error' : 'success'
}

const getStockStatusText = () => {
  const hasLowStock = compositionItems.value.some(item => 
    item.item?.inventory && item.item.inventory.current_stock < item.quantity_needed
  )
  return hasLowStock ? 'Stok Kritis' : 'Stok Aman'
}

const addItem = () => {
  if (!canAddItem.value) return

  const selectedItem = availableItems.value.find(item => item.id === newItem.value.itemId)
  if (!selectedItem) return

  // Check if item already exists in composition - check multiple ID sources
  const existingItem = compositionItems.value.find(item => {
    const itemId = item.item?.id || item.item?.id_item || item.item_id
    return String(itemId) === String(selectedItem.id)
  })
  
  if (existingItem) {
    errorMessage.value = `Item "${selectedItem.name}" sudah ada dalam komposisi produk ini. Silakan edit item yang sudah ada.`
    errorSnackbar.value = true
    return
  }

  const newCompositionItem: CompositionItem = {
    id_product_item: `temp_${Date.now()}`,
    item: {
      id: String(selectedItem.id),
      name: selectedItem.name,
      inventory: {
        current_stock: selectedItem.current_stock || 0
      }
    },
    quantity_needed: newItem.value.quantity,
    unit: newItem.value.unit,
    is_critical: newItem.value.isCritical
  }

  compositionItems.value.push(newCompositionItem)
  
  // Reset form
  cancelEdit()
}

const editItem = (item: CompositionItem, index: number) => {
  
  // Check if available items are loaded
  if (availableItems.value.length === 0) {
    console.warn('⚠️ Available items not loaded yet, fetching...')
    fetchAvailableItems().then(() => {
      editItem(item, index) // Retry after loading
    })
    return
  }
  
  editMode.value = true
  editIndex.value = index
  
  // Get item ID from multiple possible sources
  // Backend might return item.id_item or item.id or item_id at root level
  let itemId = item.item?.id || item.item?.id_item || item.item_id || null
  
  // Convert to string to match availableItems format
  if (itemId !== null) {
    itemId = itemId.toString()
  }
  
  console.log('✏️ Edit Item:', {
    itemId,
    item: item,
    item_item: item.item,
    item_id_root: item.item_id
  })
  
  // Fill form with item data
  newItem.value = {
    itemId: itemId,
    quantity: parseFloat(item.quantity_needed.toString()) || 0, // Ensure decimal parsing
    unit: item.unit,
    isCritical: item.is_critical
  }
  
  // Verify that the itemId exists in available items
  const foundInAvailable = availableItems.value.find(ai => ai.id === itemId)
  if (!foundInAvailable) {
    console.warn('⚠️ Item ID not found in available items, trying alternative matching...')
    
    // Try to find by name as backup
    const foundByName = availableItems.value.find(ai => ai.name === item.item?.name)
    if (foundByName) {
      newItem.value.itemId = foundByName.id
    } else {
      console.error('❌ Could not find item in available items by ID or name')
    }
  } else {
  }
}

const updateItem = () => {
  if (!canAddItem.value || editIndex.value === -1) return

  const selectedItem = availableItems.value.find(item => item.id === newItem.value.itemId)
  if (!selectedItem) return

  // Get the current item being edited
  const currentItem = compositionItems.value[editIndex.value]
  
  // Get current item ID from multiple possible sources
  const currentItemId = currentItem.item?.id || currentItem.item?.id_item || currentItem.item_id
  const selectedItemId = selectedItem.id
  
  // Check if the item itself has changed (different item_id) - compare as strings
  const hasItemChanged = String(currentItemId) !== String(selectedItemId)
  
  // If item has changed, check if the new item already exists in composition
  if (hasItemChanged) {
    const duplicateItem = compositionItems.value.find((item, index) => {
      if (index === editIndex.value) return false // Skip current item being edited
      const itemId = item.item?.id || item.item?.id_item || item.item_id
      return String(itemId) === String(selectedItemId)
    })
    
    if (duplicateItem) {
      errorMessage.value = `Item "${selectedItem.name}" sudah ada dalam komposisi produk ini. Silakan edit item yang sudah ada.`
      errorSnackbar.value = true
      return
    }
  }
  
  console.log('🔄 Update Item:', {
    hasItemChanged,
    currentItemId: currentItemId,
    selectedItemId: selectedItemId,
    currentProductItemId: currentItem.id_product_item,
    currentItem: currentItem
  })
  
  // Update the item
  compositionItems.value[editIndex.value] = {
    ...compositionItems.value[editIndex.value],
    // ONLY reset id_product_item if the actual item (item_id) has changed
    // If only quantity/unit/critical changed, keep the same id_product_item for UPDATE
    id_product_item: hasItemChanged ? `temp_${Date.now()}` : currentItem.id_product_item,
    item_id: parseInt(String(selectedItemId)), // Preserve item_id for backend
    item: {
      id: String(selectedItem.id),
      id_item: parseInt(String(selectedItem.id)), // Add id_item for compatibility
      name: selectedItem.name,
      inventory: {
        current_stock: selectedItem.current_stock || 0
      }
    },
    quantity_needed: newItem.value.quantity,
    unit: newItem.value.unit,
    is_critical: newItem.value.isCritical
  }

  cancelEdit()
}

const cancelEdit = () => {
  editMode.value = false
  editIndex.value = -1
  
  // Reset form
  newItem.value = {
    itemId: null,
    quantity: 1,
    unit: 'pcs',
    isCritical: false
  }
}

const removeItem = (index: number) => {
  compositionItems.value.splice(index, 1)
}

const saveComposition = async () => {
  if (!props.product) return

  loading.value = true
  try {
    
    // Prepare data for API
    const productId = parseInt(String(props.product?.id_product || props.product?.id || '0'))
    
    // Get existing items to determine which are new, updated, or deleted
    // Use initialCompositionItems (from API) instead of props.items (which might be empty)
    const existingItems = initialCompositionItems.value || []
    const currentItems = compositionItems.value
    
    // STEP 1: Convert DELETE+CREATE scenarios to UPDATE
    // Check if any temp items are actually re-adds of deleted items
    for (const item of currentItems) {
      const isTemp = String(item.id_product_item || '').startsWith('temp_')
      
      if (isTemp) {
        const currentItemId = item.item?.id || item.item?.id_item || item.item_id
        
        // Find if this item_id existed before and is not in current list (meaning it was "deleted")
        const wasDeleted = existingItems.find(existing => {
          const existingItemId = existing.item?.id || existing.item?.id_item || existing.item_id
          const stillInList = currentItems.find(c => 
            c.id_product_item === existing.id_product_item && 
            !String(c.id_product_item).startsWith('temp_')
          )
          
          return String(existingItemId) === String(currentItemId) && !stillInList
        })
        
        if (wasDeleted) {
          console.log('🔄 Converting DELETE+CREATE to UPDATE:', {
            item_name: item.item?.name,
            old_id: wasDeleted.id_product_item,
            temp_id: item.id_product_item
          })
          // Convert temp to use existing ID
          item.id_product_item = wasDeleted.id_product_item
        }
      }
    }
    
    // STEP 2: Process each current item (create or update)
    for (const item of currentItems) {
      const existingItem = existingItems.find(existing => {
        // Match by id_product_item - normalize to number for comparison
        const existingId = existing.id_product_item
        const currentId = item.id_product_item
        
        // Skip if current is temp
        if (!currentId || String(currentId).startsWith('temp_')) {
          return false
        }
        
        // Compare as numbers to handle type mismatch
        const existingIdNum = parseInt(String(existingId))
        const currentIdNum = parseInt(String(currentId))
        
        if (!isNaN(existingIdNum) && !isNaN(currentIdNum) && existingIdNum === currentIdNum) {
          console.log('✅ Found existing match:', existingIdNum, '===', currentIdNum)
          return true
        }
        
        return false
      })
      
      console.log('🔍 Item matching result:', {
        item_name: item.item?.name,
        current_id_product_item: item.id_product_item,
        current_id_type: typeof item.id_product_item,
        existingItem_found: !!existingItem,
        existingItem_id: existingItem?.id_product_item,
        existingItem_id_type: typeof existingItem?.id_product_item
      })
      
      // Skip items with invalid item_id
      if (!item.item?.id || parseInt(item.item.id) === 0) {
        console.warn('⚠️ Skipping item with invalid item_id:', {
          item_name: item.item?.name,
          item_id: item.item?.id,
          full_item: item
        })
        continue
      }
      
      const apiData: ProductItemFormData = {
        product_id: productId,
        item_id: parseInt(item.item?.id || '0'),
        quantity_needed: parseFloat(item.quantity_needed.toString()), // Ensure decimal is preserved
        unit: item.unit || '',
        is_critical: item.is_critical,
        notes: item.notes || ''
      }
      
      console.log('🔍 Processing item:', {
        item_name: item.item?.name,
        item_id: item.item?.id,
        id_product_item: item.id_product_item,
        existingItem: existingItem ? {
          id: existingItem.id_product_item,
          item_id: existingItem.item?.id
        } : null,
        action: existingItem && existingItem.id_product_item ? 'UPDATE' : 'CREATE'
      })
      
      try {
        if (existingItem && existingItem.id_product_item && !existingItem.id_product_item.toString().startsWith('temp_')) {
          // Update existing item - use existingItem.id_product_item instead of item.id_product_item
          console.log('📝 Updating item:', existingItem.id_product_item)
          await ProductItemsApi.update(parseInt(existingItem.id_product_item), apiData)
        } else {
          // Create new item
          console.log('➕ Creating new item')
          await ProductItemsApi.create(apiData)
        }
      } catch (itemError: any) {
        console.error('❌ Error processing item:', itemError)
        
        // Check for specific error messages
        if (itemError.message?.includes('already assigned')) {
          errorMessage.value = `Item "${item.item?.name}" sudah ditambahkan ke produk ini`
        } else {
          errorMessage.value = itemError.message || 'Terjadi kesalahan saat menyimpan item'
        }
        
        errorSnackbar.value = true
        throw itemError // Re-throw to stop processing
      }
    }
    
    // STEP 3: Delete items that were truly removed (not re-added)
    for (const existingItem of existingItems) {
      const stillExists = currentItems.find(current => 
        String(current.id_product_item) === String(existingItem.id_product_item)
      )
      
      if (!stillExists && existingItem.id_product_item) {
        try {
          console.log('�️ Deleting item:', existingItem.id_product_item, existingItem.item?.name)
          await ProductItemsApi.delete(parseInt(String(existingItem.id_product_item)))
        } catch (deleteError: any) {
          console.error('❌ Error deleting item:', deleteError)
          errorMessage.value = `Gagal menghapus item: ${deleteError.message}`
          errorSnackbar.value = true
        }
      }
    }
    
    
    // Show success message
    successMessage.value = 'Komposisi produk berhasil disimpan!'
    successSnackbar.value = true
    
    emit('save', {
      product: props.product,
      items: compositionItems.value
    })
    
    emit('refresh')
    closeDialog()
  } catch (error: any) {
    console.error('❌ Error saving composition:', error)
    
    // Show error message if not already shown
    if (!errorSnackbar.value) {
      errorMessage.value = error.message || 'Terjadi kesalahan saat menyimpan komposisi'
      errorSnackbar.value = true
    }
  } finally {
    loading.value = false
  }
}

const closeDialog = () => {
  dialogModel.value = false
  // Reset form when closing
  setTimeout(() => {
    compositionItems.value = [...props.items]
    cancelEdit()
  }, 300)
}
</script>

<style scoped>
.border-b {
  border-bottom: 1px solid rgb(var(--v-theme-surface-variant));
}
</style>
