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
                <p class="text-body-2 text-medium-emphasis mb-0">
                  Harga: {{ formatRupiah(product?.price || 0) }}
                </p>
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
                <VTextField
                  v-model="newItem.unit"
                  label="Satuan"
                  variant="outlined"
                  placeholder="kg, pcs, ml"
                  :readonly="!!selectedItemUnit"
                  :hint="selectedItemUnit ? `Satuan dari item: ${selectedItemUnit}` : ''"
                  persistent-hint
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
                    <span>Dibutuhkan: {{ item.quantity_needed }} {{ item.unit }}</span>
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
    @hpp-updated="emit('refresh')"
    @price-updated="emit('refresh')"
  />
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { formatRupiah } from '@/@core/utils/formatters'
import { ProductItemsApi } from '@/utils/api/ProductItemsApi'
import { ItemsApi } from '@/utils/api/ItemsApi'
import HPPBreakdownDialog from '@/components/hpp/HPPBreakdownDialog.vue'

interface CompositionItem {
  id?: string
  id_product_item?: string
  item?: {
    id: string
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
    console.log('🔄 Fetching composition for product ID:', productId)
    
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
      console.log('✅ Initial composition loaded:', initialCompositionItems.value.length, 'items')
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
         newItem.value.quantity > 0 && 
         newItem.value.unit.trim().length > 0
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

// Watch for item selection to auto-fill unit
watch(() => newItem.value.itemId, (newItemId) => {
  if (newItemId) {
    const selectedItem = availableItems.value.find(item => item.id === newItemId)
    if (selectedItem?.unit) {
      newItem.value.unit = selectedItem.unit
    }
  }
})

// Watch for props changes
watch(() => props.items, (newItems) => {
  if (newItems && newItems.length > 0) {
  }
  compositionItems.value = [...newItems]
}, { immediate: true })

// Watch for dialog opening to fetch composition data
watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.product) {
    fetchProductComposition()
  }
})

// Fetch available items when component mounts
onMounted(() => {
  fetchAvailableItems()
})

// HPP Dialog functions
const openHPPDialog = () => {
  hppDialog.value = true
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

  // Check if item already exists in composition
  const existingItem = compositionItems.value.find(item => 
    item.item?.id === selectedItem.id
  )
  
  if (existingItem) {
    errorMessage.value = `Item "${selectedItem.name}" sudah ada dalam komposisi produk ini`
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
  console.log('🔍 EditItem called with:', item)
  console.log('🔍 Item structure:', JSON.stringify(item, null, 2))
  
  // Check if available items are loaded
  if (availableItems.value.length === 0) {
    console.warn('⚠️ Available items not loaded yet, fetching...')
    fetchAvailableItems().then(() => {
      console.log('✅ Available items loaded, retrying edit')
      editItem(item, index) // Retry after loading
    })
    return
  }
  
  editMode.value = true
  editIndex.value = index
  
  // Get item ID - should be in item.item.id based on our data structure
  let itemId = item.item?.id || null
  
  // Convert to string to match availableItems format
  if (itemId !== null) {
    itemId = itemId.toString()
  }
  
  console.log('🎯 Found itemId (converted to string):', itemId)
  console.log('🔍 Available items for reference:', availableItems.value.map(ai => ({ id: ai.id, name: ai.name })))
  
  // Fill form with item data
  newItem.value = {
    itemId: itemId,
    quantity: item.quantity_needed,
    unit: item.unit,
    isCritical: item.is_critical
  }
  
  console.log('📝 Form data set for edit:', newItem.value)
  
  // Verify that the itemId exists in available items
  const foundInAvailable = availableItems.value.find(ai => ai.id === itemId)
  if (!foundInAvailable) {
    console.warn('⚠️ Item ID not found in available items, trying alternative matching...')
    
    // Try to find by name as backup
    const foundByName = availableItems.value.find(ai => ai.name === item.item?.name)
    if (foundByName) {
      console.log('✅ Found item by name matching, using ID:', foundByName.id)
      newItem.value.itemId = foundByName.id
    } else {
      console.error('❌ Could not find item in available items by ID or name')
    }
  } else {
    console.log('✅ Item found in available items:', foundInAvailable.name)
  }
}

const updateItem = () => {
  if (!canAddItem.value || editIndex.value === -1) return

  const selectedItem = availableItems.value.find(item => item.id === newItem.value.itemId)
  if (!selectedItem) return

  // Update the item
  compositionItems.value[editIndex.value] = {
    ...compositionItems.value[editIndex.value],
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
    console.log('💾 Saving composition for product:', props.product.name)
    console.log('📦 Items to save:', compositionItems.value)
    
    // Prepare data for API
    const productId = parseInt(String(props.product?.id_product || props.product?.id || '0'))
    
    // Get existing items to determine which are new, updated, or deleted
    // Use initialCompositionItems (from API) instead of props.items (which might be empty)
    const existingItems = initialCompositionItems.value || []
    const currentItems = compositionItems.value
    
    console.log('🔄 Comparing items for save:', {
      existing_count: existingItems.length,
      current_count: currentItems.length,
      existing_items: existingItems.map(item => ({
        id: item.id_product_item,
        item_id: item.item?.id,
        item_name: item.item?.name
      })),
      current_items: currentItems.map(item => ({
        id: item.id_product_item,
        item_id: item.item?.id,
        item_name: item.item?.name
      }))
    })
    
    // Process each current item (create or update)
    for (const item of currentItems) {
      const existingItem = existingItems.find(existing => {
        // Try multiple matching strategies
        if (existing.id_product_item === item.id_product_item) {
          return true // Exact ID match
        }
        
        // If item has a valid (non-temp) ID but no direct match, 
        // try matching by item_id only (since all are for same product)
        if (item.id_product_item && 
            !item.id_product_item.toString().startsWith('temp_') &&
            existing.item?.id === item.item?.id) {
          return true // Same item
        }
        
        return false
      })
      
      console.log('🔍 Processing item:', {
        item_name: item.item?.name,
        item_id_product_item: item.id_product_item,
        existing_found: !!existingItem,
        existing_id: existingItem?.id_product_item,
        is_temp: item.id_product_item?.toString().startsWith('temp_'),
        matching_strategy: existingItem ? 
          (existingItem.id_product_item === item.id_product_item ? 'exact_id' : 'item_product_match') : 
          'no_match'
      })
      
      const apiData: ProductItemFormData = {
        product_id: productId,
        item_id: parseInt(item.item?.id || '0'),
        quantity_needed: item.quantity_needed,
        unit: item.unit,
        is_critical: item.is_critical,
        notes: item.notes || ''
      }
      
      console.log('📤 API Data:', apiData)
      
      try {
        if (existingItem && !item.id_product_item?.toString().startsWith('temp_')) {
          // Update existing item
          console.log('✏️ Updating existing item:', {
            id: existingItem.id_product_item,
            item_name: item.item?.name,
            current_stock: item.item?.inventory?.current_stock
          })
          await ProductItemsApi.update(parseInt(existingItem.id_product_item || '0'), apiData)
        } else {
          // Create new item
          console.log('➕ Creating new item:', {
            item_name: item.item?.name,
            current_stock: item.item?.inventory?.current_stock,
            id_product_item: item.id_product_item,
            is_temp: item.id_product_item?.toString().startsWith('temp_'),
            existing_item_found: !!existingItem
          })
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
    
    // Delete items that were removed
    for (const existingItem of existingItems) {
      const stillExists = currentItems.find(current => 
        current.id_product_item === existingItem.id_product_item
      )
      
      if (!stillExists && existingItem.id_product_item) {
        try {
          console.log('🗑️ Deleting item:', existingItem.id_product_item)
          await ProductItemsApi.delete(parseInt(existingItem.id_product_item))
        } catch (deleteError: any) {
          console.error('❌ Error deleting item:', deleteError)
          errorMessage.value = `Gagal menghapus item: ${deleteError.message}`
          errorSnackbar.value = true
        }
      }
    }
    
    console.log('✅ Composition saved successfully!')
    
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
