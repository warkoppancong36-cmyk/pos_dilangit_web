<template>
  <v-dialog v-model="dialog" max-width="800px" persistent>
    <v-card>
      <!-- Header with variant info -->
      <v-card-title class="d-flex align-center justify-space-between" style="background-color: #D4A574; color: white;">
        <div class="d-flex align-center gap-2">
          <v-icon icon="mdi-delete" color="white" />
          <span>Kelola Komposisi Item</span>
        </div>
        <v-btn
          icon="mdi-close"
          variant="text"
          color="white"
          @click="closeDialog"
          :disabled="loading"
        />
      </v-card-title>

      <!-- Variant Info Section -->
      <v-card-text class="pa-0">
        <div class="pa-4" style="background-color: #2A2A2A;">
          <div v-if="variant" class="d-flex align-center gap-3">
            <v-icon icon="mdi-coffee" size="32" color="white" />
            <div>
              <h3 class="text-h6 text-white font-weight-bold">{{ variant.name }}</h3>
              <div class="d-flex align-center gap-4 mt-1">
                <span class="text-caption text-grey-lighten-1">SKU: {{ variant.sku }}</span>
                <span class="text-caption text-grey-lighten-1">Harga: {{ formatRupiah(variant.price || 0) }}</span>
                <v-chip color="success" size="small" variant="tonal">
                  Stok Aman
                </v-chip>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Item Form Section -->
        <div class="pa-4">
          <div class="d-flex align-center justify-space-between mb-4">
            <div class="d-flex align-center gap-2">
              <v-icon icon="mdi-plus-circle" color="success" />
              <h4 class="text-h6">Tambah Item Komposisi</h4>
            </div>
            
            <!-- Items Stock Info -->
            <div v-if="availableItems.length > 0" class="d-flex align-center gap-2">
              <v-chip 
                size="small" 
                color="success" 
                variant="tonal"
                prepend-icon="mdi-check-circle"
              >
                {{ availableItemsWithStock.length }} Tersedia
              </v-chip>
              <v-chip 
                v-if="availableItems.length - availableItemsWithStock.length > 0"
                size="small" 
                color="error" 
                variant="tonal"
                prepend-icon="mdi-alert-circle"
              >
                {{ availableItems.length - availableItemsWithStock.length }} Stok Habis
              </v-chip>
            </div>
          </div>

          <v-form ref="form" @submit.prevent="handleSave">
            <v-row>
              <!-- Item Selection -->
              <v-col cols="12" md="4">
                <v-autocomplete
                  v-model="formData.item_id"
                  :items="availableItemsWithStock"
                  item-title="name"
                  item-value="id_item"
                  label="Pilih Item"
                  variant="outlined"
                  density="compact"
                  :rules="[v => !!v || 'Item wajib dipilih']"
                  :loading="loadingItems"
                  :disabled="loadingItems || (availableItems && availableItems.length === 0)"
                  :no-data-text="getNoDataText()"
                  placeholder="Ketik untuk mencari item dengan stok..."
                  clearable
                  :menu-props="{ maxHeight: '300px' }"
                  hide-details="auto"
                  :custom-filter="customFilter"
                >
                  <template #prepend-inner>
                    <v-icon icon="mdi-package" size="16" />
                  </template>
                  
                  <!-- Custom item display -->
                  <template #item="{ props, item }">
                    <v-list-item
                      v-bind="props"
                      :title="item.raw.name"
                      :disabled="getItemStock(item.raw) <= 0"
                      class="pa-3"
                      :class="{ 'text-disabled': getItemStock(item.raw) <= 0 }"
                    >
                      <template #prepend>
                        <v-icon 
                          icon="mdi-package-variant" 
                          :color="getItemStock(item.raw) <= 0 ? 'grey-darken-2' : 'grey-lighten-1'" 
                          class="me-3" 
                        />
                      </template>
                      
                      <template #subtitle>
                        <div class="text-caption" :class="getItemStock(item.raw) <= 0 ? 'text-disabled' : 'text-medium-emphasis'">
                          <span v-if="getItemStock(item.raw) <= 0" class="text-error">
                            <v-icon icon="mdi-alert-circle" size="12" class="me-1" />
                            Stok Habis
                          </span>
                          <span v-else>
                            Stok: {{ getItemStock(item.raw) }} {{ item.raw.unit }}
                          </span>
                        </div>
                      </template>
                      
                      <template #append>
                        <v-chip 
                          v-if="getItemStock(item.raw) > 0"
                          size="x-small" 
                          :color="getStockColor(item.raw.inventory?.stock_status || item.raw.stock_status)"
                          variant="dot"
                        >
                          {{ getStockLabel(item.raw.inventory?.stock_status || item.raw.stock_status) }}
                        </v-chip>
                        <v-chip 
                          v-else
                          size="x-small" 
                          color="error"
                          variant="outlined"
                          prepend-icon="mdi-close-circle"
                        >
                          Habis
                        </v-chip>
                      </template>
                    </v-list-item>
                  </template>
                  
                  <!-- Custom selection display -->
                  <template #selection="{ item }">
                    <div class="d-flex align-center">
                      <v-icon icon="mdi-package-variant" size="16" class="me-2" />
                      <span>{{ item.raw.name }}</span>
                    </div>
                  </template>
                  
                  <!-- No data template -->
                  <template #no-data>
                    <div class="pa-4 text-center">
                      <v-icon icon="mdi-magnify" size="48" color="grey" class="mb-2" />
                      <div class="text-body-2 text-medium-emphasis">
                        Tidak ada item yang ditemukan
                      </div>
                      <div class="text-caption text-disabled">
                        Coba kata kunci yang berbeda
                      </div>
                    </div>
                  </template>
                </v-autocomplete>
              </v-col>

              <!-- Quantity -->
              <v-col cols="12" md="2">
                <v-text-field
                  v-model.number="formData.quantity"
                  label="Jumlah"
                  variant="outlined"
                  density="compact"
                  type="number"
                  min="0.01"
                  step="0.01"
                  :rules="[v => !!v || 'Jumlah wajib diisi', v => v > 0 || 'Jumlah harus lebih dari 0']"
                />
              </v-col>

              <!-- Unit -->
              <v-col cols="12" md="2">
                <v-text-field
                  :model-value="selectedItem?.unit || 'pcs'"
                  label="Satuan"
                  variant="outlined"
                  density="compact"
                  readonly
                  :disabled="!selectedItem"
                  placeholder="Pilih item terlebih dahulu"
                >
                  <template #prepend-inner>
                    <v-icon icon="mdi-weight" size="16" />
                  </template>
                </v-text-field>
              </v-col>

              <!-- Critical Item Toggle -->
              <v-col cols="12" md="2" class="d-flex align-center">
                <v-switch
                  v-model="formData.is_critical"
                  label="Item Kritis"
                  color="warning"
                  density="compact"
                  :true-value="true"
                  :false-value="false"
                />
              </v-col>

              <!-- Add Button -->
              <v-col cols="12" md="2" class="d-flex align-center">
                <v-btn
                  color="#D4A574"
                  @click="handleSave"
                  :loading="loading"
                  :disabled="!formData.item_id || !formData.quantity"
                  block
                >
                  <v-icon icon="mdi-plus" class="me-1" />
                  Tambah
                </v-btn>
              </v-col>
            </v-row>
          </v-form>
        </div>

        <!-- Composition List Section -->
        <div class="pa-4 pt-0">
          <div class="d-flex align-center justify-between mb-4">
            <h4 class="text-h6">Daftar Item Komposisi ({{ compositionItems.length }})</h4>
          </div>

          <!-- Composition Items List -->
          <div v-if="compositionItems.length > 0" class="composition-list">
            <v-card
              v-for="(item, index) in compositionItems"
              :key="index"
              variant="outlined"
              class="mb-3"
            >
              <v-card-text class="pa-3">
                <div class="d-flex align-center justify-between">
                  <div class="d-flex align-center gap-3">
                    <v-icon icon="mdi-check-circle" color="success" />
                    <div>
                      <div class="font-weight-medium">{{ item.name }}</div>
                      <div class="text-caption text-medium-emphasis">
                        Dibutuhkan: {{ item.quantity }} {{ item.unit }} • 
                        Stok: {{ item.stock }} {{ item.unit }} • 
                        {{ formatRupiah(item.price) }}
                      </div>
                    </div>
                  </div>
                  <div class="d-flex align-center gap-2">
                    <v-btn
                      icon="mdi-pencil"
                      size="small"
                      variant="text"
                      color="primary"
                      @click="editItem(item, index)"
                    />
                    <v-btn
                      icon="mdi-delete"
                      size="small"
                      variant="text"
                      color="error"
                      @click="removeItem(index)"
                    />
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </div>

          <!-- Empty State -->
          <v-card v-else variant="outlined" class="text-center pa-6">
            <v-icon icon="mdi-package-variant-closed" size="48" color="grey" class="mb-3" />
            <div class="text-body-2 text-medium-emphasis">
              Belum ada item komposisi ditambahkan
            </div>
          </v-card>
        </div>
      </v-card-text>

      <!-- Actions -->
      <v-card-actions class="px-6 pb-6">
        <v-btn
          variant="outlined"
          color="secondary"
          prepend-icon="mdi-wrench"
        >
          Setting HPP
        </v-btn>
        <v-spacer />
        <v-btn
          variant="outlined"
          @click="closeDialog"
          :disabled="loading"
        >
          Batal
        </v-btn>
        <v-btn
          color="#D4A574"
          @click="saveComposition"
          :loading="loading"
          :disabled="compositionItems.length === 0"
        >
          Simpan Komposisi
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

interface Variant {
  id?: number
  id_variant?: number
  name: string
  sku: string
  price?: number
}

interface Item {
  id_item: number
  name: string
  unit: string
  cost_per_unit?: number
  formatted_cost_per_unit?: string
  stock_status?: string
  current_stock?: number
  inventory?: {
    current_stock: number
    available_stock: number
    stock_status: string
  } | null
}

interface CompositionItem {
  id?: number
  name: string
  quantity: number
  unit: string
  stock: number
  price: number
  is_critical: boolean
}

interface Props {
  modelValue: boolean
  variant?: Variant | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'save'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Refs
const form = ref()
const loading = ref(false)
const loadingItems = ref(false)
const availableItems = ref<Item[]>([])
const compositionItems = ref<CompositionItem[]>([])

// Dialog state
const dialog = computed({
  get: () => {
    console.log('🔵 [DEBUG] Dialog getter called, props.modelValue:', props.modelValue)
    return props.modelValue
  },
  set: (value) => {
    console.log('🔵 [DEBUG] Dialog setter called with value:', value)
    emit('update:modelValue', value)
  }
})

// Form data
const defaultFormData = () => ({
  item_id: null as number | null,
  quantity: 1,
  is_critical: false
})

const formData = ref(defaultFormData())

// Computed
const selectedItem = computed(() => {
  if (!formData.value.item_id || !Array.isArray(availableItems.value)) return null
  return availableItems.value.find(item => item.id_item === formData.value.item_id)
})

// Filter items with stock > 0 only
const availableItemsWithStock = computed(() => {
  if (!Array.isArray(availableItems.value)) return []
  return availableItems.value.filter(item => getItemStock(item) > 0)
})

// Helper function to get item stock
const getItemStock = (item: Item): number => {
  return item.inventory?.current_stock || item.current_stock || 0
}

// Get no data text based on loading state and available items
const getNoDataText = (): string => {
  if (loadingItems.value) return 'Memuat items...'
  if (!availableItems.value || availableItems.value.length === 0) return 'Tidak ada item tersedia'
  if (availableItemsWithStock.value.length === 0) return 'Semua item sedang stok habis'
  return 'Tidak ada item yang ditemukan'
}

// Methods
const formatRupiah = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}

const getStockColor = (stockStatus: string): string => {
  switch (stockStatus) {
    case 'overstock':
      return 'success'
    case 'in_stock':
      return 'primary'
    case 'low_stock':
      return 'warning'
    case 'out_of_stock':
      return 'error'
    default:
      return 'grey'
  }
}

const getStockLabel = (stockStatus: string): string => {
  switch (stockStatus) {
    case 'overstock':
      return 'Berlebih'
    case 'in_stock':
      return 'Tersedia'
    case 'low_stock':
      return 'Menipis'
    case 'out_of_stock':
      return 'Habis'
    default:
      return 'Unknown'
  }
}

const customFilter = (value: string, query: string, item?: any): boolean => {
  if (!query) return true
  
  const searchQuery = query.toLowerCase().trim()
  const itemName = item?.raw?.name?.toLowerCase() || ''
  const itemCode = item?.raw?.item_code?.toLowerCase() || ''
  const itemUnit = item?.raw?.unit?.toLowerCase() || ''
  const itemDescription = item?.raw?.description?.toLowerCase() || ''
  
  // Search in multiple fields
  return itemName.includes(searchQuery) ||
         itemCode.includes(searchQuery) ||
         itemUnit.includes(searchQuery) ||
         itemDescription.includes(searchQuery)
}

const loadAvailableItems = async () => {
  loadingItems.value = true
  console.log('Loading available items...')
  try {
    // Use per_page=all to get all items without pagination
    const response = await axios.get('/api/items?per_page=all')
    console.log('Items API response:', response.data)
    
    // API returns paginated data: response.data.data.data
    const items = response.data?.data?.data || response.data?.data || response.data || []
    availableItems.value = Array.isArray(items) ? items : []
    
    console.log('Available items loaded:', availableItems.value.length, 'items')
    console.log('Sample items:', availableItems.value.slice(0, 3))
  } catch (error) {
    console.error('Error loading items:', error)
    if (error.response) {
      console.error('API Error Status:', error.response.status)
      console.error('API Error Data:', error.response.data)
    }
    availableItems.value = []
  } finally {
    loadingItems.value = false
  }
}

const loadCompositionItems = async () => {
  if (!props.variant?.id && !props.variant?.id_variant) return
  
  try {
    const variantId = props.variant.id || props.variant.id_variant
    console.log('Loading composition items for variant:', variantId)
    
    const response = await axios.get(`/api/variant-items/variant/${variantId}`)
    console.log('Composition API response:', response.data)
    
    // The API returns data.composition, not data directly
    const items = response.data?.data?.composition || response.data?.composition || []
    
    // Ensure items is an array before mapping
    if (!Array.isArray(items)) {
      console.warn('API response composition is not an array:', items)
      compositionItems.value = []
      return
    }
    
    // Transform the API response to match our interface
    compositionItems.value = items.map((item: any) => ({
      id: item.id,
      name: item.item?.name || item.name,
      quantity: item.quantity || item.qty,
      unit: item.unit || item.item?.unit || 'pcs',
      stock: item.item?.inventory?.current_stock || item.item?.current_stock || item.stock || 0,
      price: item.item?.cost_per_unit || item.item?.price || item.price || 0,
      is_critical: item.is_critical || false
    }))
    
    console.log('Composition items loaded:', compositionItems.value)
  } catch (error) {
    console.error('Error loading composition items:', error)
    if (error.response) {
      console.error('API Error Status:', error.response.status)
      console.error('API Error Data:', error.response.data)
    }
    // Don't show error to user if variant has no composition yet
    compositionItems.value = []
  }
}

const handleSave = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  if (!selectedItem.value) return

  // Add item to composition list
  const newItem: CompositionItem = {
    name: selectedItem.value.name,
    quantity: formData.value.quantity,
    unit: selectedItem.value.unit,
    stock: selectedItem.value.inventory?.current_stock || selectedItem.value.current_stock || 0,
    price: selectedItem.value.cost_per_unit || 0,
    is_critical: formData.value.is_critical
  }

  compositionItems.value.push(newItem)
  
  // Reset form
  formData.value = defaultFormData()
}

const editItem = (item: CompositionItem, index: number) => {
  console.log('Edit item:', item, index)
}

const removeItem = async (index: number) => {
  const item = compositionItems.value[index]
  
  // If item has an ID, it exists in database and needs to be deleted via API
  if (item.id) {
    try {
      await axios.delete(`/api/variant-items/${item.id}`)
      console.log('Item deleted from database:', item.name)
    } catch (error) {
      console.error('Error deleting item from database:', error)
      alert('Gagal menghapus item dari database')
      return
    }
  }
  
  // Remove from local array
  compositionItems.value.splice(index, 1)
}

const saveComposition = async () => {
  if (!props.variant?.id && !props.variant?.id_variant) {
    console.error('No variant ID found')
    return
  }

  loading.value = true
  try {
    const variantId = props.variant.id || props.variant.id_variant
    
  // Debug: Check if we have valid items to save
  console.log('🔍 [DEBUG] Composition items count:', compositionItems.value.length)
  console.log('🔍 [DEBUG] Raw composition items:', JSON.stringify(compositionItems.value, null, 2))
  console.log('🔍 [DEBUG] Available items count:', availableItems.value.length)
  console.log('🔍 [DEBUG] Available items sample:', availableItems.value.slice(0, 3))
  
  const payload = {
      id_variant: variantId,
      items: compositionItems.value.map(item => {
        const foundItem = availableItems.value.find(ai => ai.name === item.name)
        console.log(`Mapping item "${item.name}":`)
        console.log(`  - Found item:`, foundItem?.id_item)
        console.log(`  - Quantity:`, item.quantity)
        console.log(`  - Unit:`, item.unit)
        console.log(`  - Is critical:`, item.is_critical)
        
        const mappedItem = {
          id_item: foundItem?.id_item,
          quantity_needed: Number(item.quantity), // Ensure it's a number
          unit: item.unit,
          is_critical: item.is_critical || false
        }
        
        console.log(`  - Mapped result:`, mappedItem)
        return mappedItem
      }).filter(item => item.id_item && item.quantity_needed > 0) // Remove items without valid id_item or quantity
    }

    console.log('Final payload before sending:', JSON.stringify(payload, null, 2))
    
    // Validate payload before sending
    if (!payload.id_variant) {
      throw new Error('Variant ID is missing')
    }
    
    if (payload.items.length === 0) {
      throw new Error('No valid items to save')
    }
    
    // Additional validation for each item
    for (let i = 0; i < payload.items.length; i++) {
      const item = payload.items[i]
      if (!item.id_item) {
        throw new Error(`Item ${i + 1}: Missing id_item`)
      }
      if (!item.quantity_needed || item.quantity_needed <= 0) {
        throw new Error(`Item ${i + 1}: Missing or invalid quantity_needed`)
      }
      if (!item.unit) {
        throw new Error(`Item ${i + 1}: Missing unit`)
      }
    }
    
    // Use bulk-store API endpoint for saving composition
    const response = await axios.post('/api/variant-items/bulk-store', payload)
    
    console.log('Composition saved successfully:', response.data)
    
    emit('save')
    closeDialog()
  } catch (error: any) {
    console.error('Error saving composition:', error)
    // Show user-friendly error message
    alert('Gagal menyimpan komposisi: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

const closeDialog = () => {
  dialog.value = false
  formData.value = defaultFormData()
  compositionItems.value = []
}

// Watchers
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    formData.value = defaultFormData()
    loadAvailableItems()
    loadCompositionItems()
  }
})

// Watch for item selection changes to update unit
watch(() => formData.value.item_id, (newItemId) => {
  if (newItemId && selectedItem.value) {
    console.log('Item selected:', selectedItem.value.name, 'Unit:', selectedItem.value.unit)
  }
})

// Load items on mount
onMounted(() => {
  loadAvailableItems()
})
</script>

<style scoped>
.composition-list {
  max-height: 300px;
  overflow-y: auto;
}
</style>
