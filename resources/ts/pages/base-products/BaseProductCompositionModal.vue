<template>
  <VDialog
    :model-value="show"
    max-width="1000px"
    persistent
    scrollable
    @update:model-value="!$event && $emit('close')"
  >
    <VCard class="composition-modal">
      <!-- Header with Gradient -->
      <VCardTitle class="composition-header">
        <VIcon icon="mdi-chef-hat" class="me-2" />
        <span>Kelola Komposisi Base Product</span>
      </VCardTitle>
      
      <VCardText class="pa-6">
        <!-- Selected Base Product Info Card -->
        <VCard 
          v-if="selectedBaseProduct" 
          variant="tonal" 
          color="primary" 
          class="mb-6"
        >
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-4">
              <VAvatar size="60" rounded>
                <VImg 
                  v-if="selectedBaseProduct.image_url" 
                  :src="selectedBaseProduct.image_url" 
                  :alt="selectedBaseProduct.name"
                />
                <VIcon v-else icon="tabler-package" size="30" />
              </VAvatar>
              <div class="flex-grow-1">
                <h3 class="text-h6 font-weight-bold mb-1">{{ selectedBaseProduct.name }}</h3>
                <div class="text-body-2 text-medium-emphasis">
                  SKU: {{ selectedBaseProduct.sku || 'No SKU' }}
                </div>
                <VChip 
                  :color="selectedBaseProduct.current_stock > selectedBaseProduct.min_stock ? 'success' : 'warning'"
                  size="small"
                  variant="flat"
                  class="mt-1"
                >
                  {{ selectedBaseProduct.current_stock > selectedBaseProduct.min_stock ? 'Stok Aman' : 'Stok Menipis' }}
                </VChip>
              </div>
            </div>
          </VCardText>
        </VCard>

        <!-- Stats Cards -->
        <VRow v-if="selectedBaseProduct" class="mb-6">
          <VCol cols="6" md="3">
            <VCard variant="outlined" class="stats-card">
              <VCardText class="text-center pa-3">
                <VIcon icon="mdi-package-variant" color="primary" class="mb-1" size="20" />
                <div class="text-body-1 font-weight-bold">{{ formatCurrency(selectedBaseProduct.cost_per_unit || 0) }}</div>
                <div class="text-caption text-medium-emphasis">HPP</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="6" md="3">
            <VCard variant="outlined" class="stats-card">
              <VCardText class="text-center pa-3">
                <VIcon icon="mdi-currency-usd" color="success" class="mb-1" size="20" />
                <div class="text-body-1 font-weight-bold">{{ formatCurrency(selectedBaseProduct.selling_price || 0) }}</div>
                <div class="text-caption text-medium-emphasis">Harga Jual</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="6" md="3">
            <VCard variant="outlined" class="stats-card">
              <VCardText class="text-center pa-3">
                <VIcon icon="mdi-trending-up" color="info" class="mb-1" size="20" />
                <div class="text-body-1 font-weight-bold">{{ formatCurrency(calculateMargin()) }}</div>
                <div class="text-caption text-medium-emphasis">Margin</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="6" md="3">
            <VCard variant="outlined" class="stats-card">
              <VCardText class="text-center pa-3">
                <VIcon icon="mdi-percent" color="warning" class="mb-1" size="20" />
                <div class="text-body-1 font-weight-bold">{{ calculateMarginPercentage() }}%</div>
                <div class="text-caption text-medium-emphasis">Margin %</div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <!-- Add Composition Form -->
        <VCard class="mb-6">
          <VCardTitle class="d-flex align-center gap-2 pa-4">
            <VIcon icon="mdi-plus-circle" color="primary" />
            <span>Tambah Item Komposisi</span>
          </VCardTitle>
          <VDivider />
          <VCardText class="pa-4">
            <VAlert
              v-if="Object.keys(errors).length > 0"
              type="error"
              variant="tonal"
              class="mb-4"
            >
              <div v-for="(error, field) in errors" :key="field">
                <strong>{{ field }}:</strong> {{ Array.isArray(error) ? error[0] : error }}
              </div>
            </VAlert>

            <VForm @submit.prevent="handleSubmit">
              <VRow>
                <!-- Base Product Selection (if not pre-selected) -->
                <VCol v-if="!editMode && !form.base_product_id" cols="12" md="6">
                  <VAutocomplete
                    v-model="form.base_product_id"
                    :items="baseProducts"
                    :loading="baseProductsLoading"
                    item-title="name"
                    item-value="id_base_product"
                    label="Pilih Base Product"
                    placeholder="Pilih base product"
                    prepend-inner-icon="tabler-package"
                    clearable
                    :error-messages="errors.base_product_id"
                    @update:model-value="updateSelectedBaseProduct"
                  >
                    <template #item="{ props, item }">
                      <VListItem v-bind="props">
                        <template #prepend>
                          <VAvatar size="30">
                            <VImg 
                              v-if="item.raw.image_url" 
                              :src="item.raw.image_url" 
                              :alt="item.raw.name"
                            />
                            <VIcon v-else icon="tabler-package" size="16" />
                          </VAvatar>
                        </template>
                        <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                        <VListItemSubtitle>
                          Stock: {{ formatNumber(item.raw.current_stock) }} {{ item.raw.unit }} | 
                          {{ item.raw.formatted_cost }}
                        </VListItemSubtitle>
                      </VListItem>
                    </template>
                  </VAutocomplete>
                </VCol>

                <!-- Ingredient Item Selection -->
                <VCol cols="12" :md="!editMode && !form.base_product_id ? 6 : 4">
                  <VAutocomplete
                    v-model="form.ingredient_item_id"
                    :items="ingredientItems"
                    :loading="itemsLoading"
                    item-title="name"
                    item-value="id_item"
                    label="Pilih Item"
                    placeholder="Pilih item komposisi"
                    prepend-inner-icon="tabler-package"
                    clearable
                    :error-messages="errors.ingredient_item_id"
                    :color="isDuplicateIngredient ? 'warning' : 'primary'"
                    @update:model-value="updateSelectedIngredient"
                    :no-data-text="'Tidak ada item tersedia'"
                  >
                    <template #item="{ props, item }">
                      <VListItem v-bind="props">
                        <template #prepend>
                          <VAvatar size="30">
                            <VIcon icon="tabler-package" size="16" />
                          </VAvatar>
                        </template>
                        <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                        <VListItemSubtitle>
                          Stok: {{ formatNumber(item.raw.current_stock || 0) }} {{ item.raw.unit || 'pcs' }} | 
                          Rp {{ formatCurrency(item.raw.cost_per_unit || 0) }}
                        </VListItemSubtitle>
                      </VListItem>
                    </template>
                  </VAutocomplete>
                  
                  <!-- Duplicate Warning -->
                  <VAlert
                    v-if="isDuplicateIngredient"
                    type="warning"
                    variant="tonal"
                    density="compact"
                    class="mt-2"
                  >
                    <VIcon icon="mdi-alert" class="me-2" />
                    Item ini sudah ada dalam daftar komposisi
                  </VAlert>
                </VCol>

                <!-- Quantity -->
                <VCol cols="6" :md="!editMode && !form.base_product_id ? 6 : 4">
                  <VTextField
                    v-model="form.quantity"
                    type="number"
                    step="0.01"
                    min="0"
                    label="Jumlah"
                    placeholder="1"
                    :suffix="selectedIngredient?.unit || 'pcs'"
                    prepend-inner-icon="mdi-numeric"
                    :error-messages="errors.quantity"
                    @input="calculateCost"
                  />
                </VCol>

                <!-- Unit (Read-only) -->
                <VCol cols="6" :md="!editMode && !form.base_product_id ? 6 : 2">
                  <VTextField
                    :model-value="selectedIngredient?.unit || 'pcs'"
                    label="Satuan"
                    readonly
                    prepend-inner-icon="mdi-scale-balance"
                  />
                </VCol>

                <!-- Critical Toggle -->
                <VCol cols="6" md="2">
                  <VSwitch
                    v-model="form.is_critical"
                    label="Item Kritis"
                    color="warning"
                    hide-details
                  />
                </VCol>

                <!-- Add Button -->
                <VCol cols="6" md="2" class="d-flex align-center">
                  <VBtn
                    color="primary"
                    :loading="loading"
                    :disabled="!canSubmit"
                    @click="handleSubmit"
                    block
                  >
                    <VIcon icon="mdi-plus" class="me-1" />
                    Tambah ke List
                  </VBtn>
                </VCol>
              </VRow>

              <!-- Cost Preview -->
              <VCard
                v-if="selectedIngredient && form.quantity"
                variant="tonal"
                color="success"
                class="mt-4"
              >
                <VCardText class="pa-3">
                  <VRow>
                    <VCol cols="12" md="4">
                      <div class="text-caption text-medium-emphasis">Cost per Unit:</div>
                      <div class="text-subtitle-1 font-weight-bold">
                        {{ formatCurrency(selectedIngredient?.cost_per_unit || 0) }}
                      </div>
                    </VCol>
                    <VCol cols="12" md="4">
                      <div class="text-caption text-medium-emphasis">Total Cost:</div>
                      <div class="text-h6 font-weight-bold text-primary">
                        {{ formatCurrency(calculatedCost) }}
                      </div>
                    </VCol>
                    <VCol cols="12" md="4">
                      <div class="text-caption text-medium-emphasis">Available Stock:</div>
                      <div class="text-h6 font-weight-bold" :class="maxProducibleQuantity > 0 ? 'text-success' : 'text-error'">
                        {{ maxProducibleQuantity }} portions
                      </div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </VForm>
          </VCardText>
        </VCard>

        <!-- Compositions List -->
        <VCard>
          <VCardTitle class="d-flex align-center justify-space-between pa-4">
            <div class="d-flex align-center gap-2">
              <VIcon icon="mdi-format-list-bulleted" color="primary" />
              <span>Daftar Item Komposisi ({{ existingCompositions.length }})</span>
            </div>
          </VCardTitle>
          <VDivider />
          
          <!-- Empty State -->
          <div v-if="existingCompositions.length === 0" class="text-center pa-8">
            <VIcon icon="mdi-package-variant-closed" size="64" color="grey-lighten-2" class="mb-4" />
            <h3 class="text-h6 text-medium-emphasis mb-2">Belum ada item komposisi</h3>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Tambahkan item yang dibutuhkan untuk membuat base product ini
            </p>
          </div>

          <!-- Compositions List -->
          <div v-else class="pa-4">
            <div 
              v-for="composition in existingCompositions" 
              :key="composition.id || composition.base_product_id"
              class="composition-item d-flex align-center justify-space-between pa-3 mb-3 rounded-lg border"
              :class="{ 'bg-green-lighten-5 border-success': composition.is_temporary }"
            >
              <!-- Left side: Item info -->
              <div class="d-flex align-center gap-3 flex-grow-1">
                <VIcon 
                  icon="mdi-check-circle" 
                  color="success" 
                  size="18"
                />
                <div class="flex-grow-1">
                  <div class="font-weight-medium text-body-2 mb-1">
                    {{ getIngredientName(composition) }}
                  </div>
                  <div class="text-caption text-medium-emphasis" style="font-size: 0.75rem;">
                    Dibutuhkan: {{ formatNumber(composition.quantity || 0) }} {{ getIngredientUnit(composition) }}
                    <span class="mx-2">•</span>
                    Stok: {{ formatNumber(getIngredientStock(composition)) }} {{ getIngredientUnit(composition) }}
                  </div>
                </div>
              </div>

              <!-- Right side: Unit and Actions -->
              <div class="d-flex align-center gap-2">
                <VChip 
                  size="x-small" 
                  variant="outlined"
                  color="primary"
                >
                  {{ getIngredientUnit(composition) }}
                </VChip>
                <VBtn
                  icon
                  size="x-small"
                  variant="text"
                  color="error"
                  @click="removeComposition(composition)"
                >
                  <VIcon icon="mdi-delete" size="16" />
                </VBtn>
              </div>
            </div>
          </div>
        </VCard>
      </VCardText>
      
      <VDivider />
      
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn 
          variant="outlined" 
          @click="$emit('close')"
          :disabled="loading"
        >
          Batal
        </VBtn>
        <VBtn 
          color="primary" 
          @click="saveAllCompositions"
          :loading="loading"
          :disabled="existingCompositions.length === 0"
        >
          <VIcon icon="mdi-content-save" class="me-1" />
          Simpan Komposisi ({{ existingCompositions.filter(c => c.is_temporary).length }})
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import axios from 'axios'

// Types
interface BaseProduct {
  id_base_product: number
  name: string
  sku?: string
  image_url?: string
  current_stock: number
  min_stock: number
  unit: string
  cost_per_unit: number
  selling_price?: number
  formatted_cost: string
}

interface Composition {
  id?: number
  base_product_id: number
  ingredient_base_product_id?: number
  ingredient_item_id?: number
  quantity: number
  is_active: boolean
  is_critical?: boolean
  notes?: string
  ingredient?: BaseProduct
  ingredient_item?: any
  ingredient_base_product?: BaseProduct
  total_cost?: number
  is_temporary?: boolean
}

interface Props {
  show: boolean
  composition?: Composition | null
  baseProducts: BaseProduct[]
  baseProductsLoading?: boolean
  editMode?: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'save', composition: any): void
}

const props = withDefaults(defineProps<Props>(), {
  baseProductsLoading: false,
  editMode: false
})

const emit = defineEmits<Emits>()

// Form state
const form = reactive({
  base_product_id: 0,
  ingredient_base_product_id: 0,
  ingredient_item_id: 0,
  quantity: 0,
  notes: '',
  is_active: true,
  is_critical: false
})

// Component state
const loading = ref(false)
const errors = ref<Record<string, string>>({})
const selectedBaseProduct = ref<BaseProduct | null>(null)
const selectedIngredient = ref<any>(null)
const existingCompositions = ref<Composition[]>([])
const items = ref<any[]>([])
const itemsLoading = ref(false)

// Computed properties
const ingredientItems = computed(() => {
  return items.value || []
})

const canSubmit = computed(() => {
  return form.base_product_id && 
         (form.ingredient_item_id || form.ingredient_base_product_id) && 
         form.quantity > 0 &&
         !isDuplicateIngredient.value
})

const calculatedCost = computed(() => {
  if (!selectedIngredient.value || !form.quantity) return 0
  
  try {
    const itemCost = selectedIngredient.value.cost_per_unit || 0
    const quantity = Number(form.quantity) || 0
    return itemCost * quantity
  } catch (error) {
    console.error('Error calculating cost:', error)
    return 0
  }
})

const maxProducibleQuantity = computed(() => {
  if (!selectedIngredient.value || !form.quantity) return 0
  
  try {
    const availableStock = Number(selectedIngredient.value.current_stock) || 0
    const requiredQuantity = Number(form.quantity) || 0
    return requiredQuantity > 0 ? Math.floor(availableStock / requiredQuantity) : 0
  } catch (error) {
    console.error('Error calculating max quantity:', error)
    return 0
  }
})

// Check if current selected ingredient already exists in compositions
const isDuplicateIngredient = computed(() => {
  if (!form.ingredient_item_id && !form.ingredient_base_product_id) return false
  
  const duplicate = existingCompositions.value.some(comp => {
    if (form.ingredient_item_id && comp.ingredient_item_id === form.ingredient_item_id) {
      console.log('Duplicate found - ingredient_item_id:', form.ingredient_item_id, 'in composition:', comp)
      return true
    }
    if (form.ingredient_base_product_id && comp.ingredient_base_product_id === form.ingredient_base_product_id) {
      console.log('Duplicate found - ingredient_base_product_id:', form.ingredient_base_product_id, 'in composition:', comp)
      return true
    }
    return false
  })
  
  console.log('Duplicate check:', {
    ingredient_item_id: form.ingredient_item_id,
    ingredient_base_product_id: form.ingredient_base_product_id,
    existingCompositions: existingCompositions.value.length,
    isDuplicate: duplicate
  })
  
  return duplicate
})

// Helper functions
const formatNumber = (value: number | string | null | undefined): string => {
  if (value === null || value === undefined) return '0'
  const num = typeof value === 'string' ? parseFloat(value) : Number(value)
  return isNaN(num) ? '0' : num.toLocaleString('id-ID')
}

const formatCurrency = (value: number | string | null | undefined): string => {
  if (value === null || value === undefined) return 'Rp 0'
  const num = typeof value === 'string' ? parseFloat(value) : Number(value)
  return isNaN(num) ? 'Rp 0' : `Rp ${num.toLocaleString('id-ID')}`
}

// Helper functions for composition display
const getIngredientName = (composition: any): string => {
  try {
    if (composition.ingredient_item?.name) return composition.ingredient_item.name
    if (composition.ingredient_base_product?.name) return composition.ingredient_base_product.name
    return 'Unknown Item'
  } catch (error) {
    return 'Unknown Item'
  }
}

const getIngredientStock = (composition: any): number => {
  try {
    if (composition.ingredient_item?.current_stock !== undefined) return composition.ingredient_item.current_stock
    if (composition.ingredient_base_product?.current_stock !== undefined) return composition.ingredient_base_product.current_stock
    return 0
  } catch (error) {
    return 0
  }
}

const getIngredientUnit = (composition: any): string => {
  try {
    if (composition.ingredient_item?.unit) return composition.ingredient_item.unit
    if (composition.ingredient_base_product?.unit) return composition.ingredient_base_product.unit
    return 'pcs'
  } catch (error) {
    return 'pcs'
  }
}

const calculateMargin = (): number => {
  if (!selectedBaseProduct.value) return 0
  const sellingPrice = selectedBaseProduct.value.selling_price || 0
  const costPrice = selectedBaseProduct.value.cost_per_unit || 0
  return sellingPrice - costPrice
}

const calculateMarginPercentage = (): string => {
  if (!selectedBaseProduct.value || !selectedBaseProduct.value.selling_price) return '0'
  const sellingPrice = selectedBaseProduct.value.selling_price
  const costPrice = selectedBaseProduct.value.cost_per_unit || 0
  if (sellingPrice === 0) return '0'
  const percentage = ((sellingPrice - costPrice) / sellingPrice) * 100
  return percentage.toFixed(1)
}

const resetForm = () => {
  Object.keys(form).forEach(key => {
    if (key === 'is_active') {
      form[key] = true
    } else if (key === 'is_critical') {
      form[key] = false
    } else if (key === 'quantity' || key === 'base_product_id' || key === 'ingredient_item_id' || key === 'ingredient_base_product_id') {
      form[key] = 0
    } else {
      form[key] = ''
    }
  })
  selectedIngredient.value = null
  errors.value = {}
}

const updateSelectedBaseProduct = async () => {
  const baseProductId = Number(form.base_product_id)
  if (baseProductId) {
    selectedBaseProduct.value = props.baseProducts.find(bp => bp.id_base_product === baseProductId) || null
    // Load items first, then load existing compositions to ensure proper ingredient data
    await loadItems()
    await loadExistingCompositions()
  } else {
    selectedBaseProduct.value = null
    existingCompositions.value = []
  }
}

const updateSelectedIngredient = () => {
  const ingredientId = Number(form.ingredient_item_id)
  if (ingredientId) {
    selectedIngredient.value = items.value.find(item => item.id_item === ingredientId) || null
  } else {
    selectedIngredient.value = null
  }
}

const loadItems = async () => {
  try {
    itemsLoading.value = true
    const response = await axios.get('/api/items')
    if (response.data.success) {
      const itemsData = response.data.data.data || response.data.data || []
      items.value = itemsData
    }
  } catch (error) {
    console.error('Error loading items:', error)
    items.value = []
  } finally {
    itemsLoading.value = false
  }
}

const loadExistingCompositions = async () => {
  if (!form.base_product_id) return
  
  try {
    console.log('Loading existing compositions for base_product_id:', form.base_product_id)
    const response = await axios.get(`/api/base-product-compositions?base_product_id=${form.base_product_id}`)
    console.log('API Response:', response.data)
    
    if (response.data.success) {
      const compositions = response.data.data.data || []
      console.log('Raw compositions from API:', compositions)
      
      // Mark existing compositions as saved (not temporary) and ensure proper data structure
      compositions.forEach(comp => {
        comp.is_temporary = false
        console.log('Processing composition:', {
          id: comp.id,
          ingredient_item_id: comp.ingredient_item_id,
          ingredient_base_product_id: comp.ingredient_base_product_id,
          ingredient_item: comp.ingredient_item,
          ingredient_base_product: comp.ingredient_base_product
        })
        
        // Ensure ingredient data is properly loaded
        if (comp.ingredient_item_id && !comp.ingredient_item) {
          // Find matching item data
          const matchingItem = items.value.find(item => item.id_item === comp.ingredient_item_id)
          if (matchingItem) {
            comp.ingredient_item = matchingItem
            console.log('Linked ingredient item:', matchingItem.name)
          } else {
            console.warn('Could not find matching item for id:', comp.ingredient_item_id)
          }
        }
      })
      
      existingCompositions.value = compositions
      console.log('Final existingCompositions:', existingCompositions.value)
    }
  } catch (error) {
    console.error('Error loading compositions:', error)
    if (error.response) {
      console.error('Error response:', error.response.data)
    }
  }
}

// Watch for modal open to ensure data is loaded
watch(() => props.show, (newValue) => {
  if (newValue) {
    console.log('Modal opened, ensuring data is loaded')
    loadItems()
    if (form.base_product_id) {
      loadExistingCompositions()
    }
  }
}, { immediate: true })

watch(() => props.composition, (newValue) => {
  if (newValue) {
    // Populate form with existing data
    Object.keys(form).forEach(key => {
      const value = newValue[key]
      if (key === 'is_active') {
        form[key] = value !== undefined ? value : true
      } else if (key === 'is_critical') {
        form[key] = value !== undefined ? value : false
      } else if (key === 'quantity' || key === 'base_product_id' || key === 'ingredient_item_id') {
        form[key] = value !== undefined ? Number(value) : 0
      } else {
        form[key] = value || ''
      }
    })
    updateSelectedBaseProduct()
    updateSelectedIngredient()
  } else {
    // Reset form for create mode
    resetForm()
  }
  errors.value = {}
}, { immediate: true })

const calculateCost = () => {
  // This method is called when quantity changes to trigger reactivity
  // The actual calculation is done in the computed property
}

const handleSubmit = async () => {
  console.log('=== HANDLE SUBMIT START ===')
  console.log('Form state:', {
    base_product_id: form.base_product_id,
    ingredient_item_id: form.ingredient_item_id,
    ingredient_base_product_id: form.ingredient_base_product_id,
    quantity: form.quantity
  })
  console.log('Existing compositions count:', existingCompositions.value.length)
  console.log('Can submit:', canSubmit.value)
  console.log('Is duplicate:', isDuplicateIngredient.value)
  
  if (!canSubmit.value) {
    console.log('Cannot submit - validation failed')
    return
  }

  // Check for duplicate in existing compositions (both saved and temporary)
  const isDuplicate = existingCompositions.value.some(comp => {
    if (form.ingredient_item_id && comp.ingredient_item_id === form.ingredient_item_id) {
      console.log('DUPLICATE FOUND - ingredient_item_id match:', form.ingredient_item_id)
      return true
    }
    if (form.ingredient_base_product_id && comp.ingredient_base_product_id === form.ingredient_base_product_id) {
      console.log('DUPLICATE FOUND - ingredient_base_product_id match:', form.ingredient_base_product_id)
      return true
    }
    return false
  })

  if (isDuplicate) {
    console.log('Duplicate detected, showing error')
    errors.value = { 
      general: 'Item ini sudah ada dalam daftar komposisi. Silakan pilih item yang berbeda.' 
    }
    return
  }

  loading.value = true
  errors.value = {}

  try {
    // Create temporary composition object for local list
    const newComposition = {
      id: Date.now(), // temporary ID for local identification
      base_product_id: form.base_product_id,
      ingredient_base_product_id: form.ingredient_base_product_id || null,
      ingredient_item_id: form.ingredient_item_id || null,
      quantity: Number(form.quantity),
      notes: form.notes || null,
      is_active: form.is_active,
      is_critical: form.is_critical,
      // Include selected ingredient data for display
      ingredient_item: form.ingredient_item_id ? selectedIngredient.value : null,
      ingredient_base_product: form.ingredient_base_product_id ? selectedIngredient.value : null,
      total_cost: calculatedCost.value,
      // Temporary flag to indicate unsaved
      is_temporary: true
    }

    console.log('Adding composition to list:', newComposition)

    // Add to local list
    existingCompositions.value.push(newComposition)
    
    console.log('Total compositions:', existingCompositions.value.length)

    // Reset form for next item
    form.ingredient_base_product_id = 0
    form.ingredient_item_id = 0
    form.quantity = 0
    form.notes = ''
    form.is_critical = false
    selectedIngredient.value = null

  } catch (error: any) {
    console.error('Error adding composition:', error)
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: error.response?.data?.message || 'Failed to add composition' }
    }
  } finally {
    loading.value = false
  }
}

const removeComposition = (composition: Composition) => {
  const index = existingCompositions.value.findIndex(c => c.id === composition.id)
  if (index > -1) {
    existingCompositions.value.splice(index, 1)
  }
}

const saveAllCompositions = async () => {
  if (existingCompositions.value.length === 0) return

  loading.value = true
  errors.value = {}
  let successCount = 0
  let errorMessages: string[] = []

  try {
    // Save all temporary/unsaved compositions
    const compositionsToSave = existingCompositions.value.filter(comp => 
      comp.is_temporary || !comp.id || comp.id > 1000000
    )
    
    for (let i = compositionsToSave.length - 1; i >= 0; i--) {
      const composition = compositionsToSave[i]
      try {
        const payload: any = {
          base_product_id: composition.base_product_id,
          quantity: composition.quantity,
          is_active: composition.is_active,
          is_critical: composition.is_critical,
          notes: composition.notes
        }

        // Only include relevant ingredient field
        if (composition.ingredient_item_id) {
          payload.ingredient_item_id = composition.ingredient_item_id
        }
        if (composition.ingredient_base_product_id) {
          payload.ingredient_base_product_id = composition.ingredient_base_product_id
        }

        await axios.post('/api/base-product-compositions', payload)
        successCount++
        
        // Remove from temporary list since it's now saved
        const originalIndex = existingCompositions.value.findIndex(c => c.id === composition.id)
        if (originalIndex > -1) {
          existingCompositions.value.splice(originalIndex, 1)
        }
        
      } catch (itemError: any) {
        console.error('Error saving individual composition:', itemError)
        const ingredientName = getIngredientName(composition)
        
        if (itemError.response?.status === 422 && itemError.response?.data?.message?.includes('already exists')) {
          errorMessages.push(`${ingredientName}: Item sudah ada dalam komposisi`)
          // Remove duplicate item from local list
          const originalIndex = existingCompositions.value.findIndex(c => c.id === composition.id)
          if (originalIndex > -1) {
            existingCompositions.value.splice(originalIndex, 1)
          }
        } else {
          errorMessages.push(`${ingredientName}: ${itemError.response?.data?.message || 'Gagal menyimpan'}`)
        }
      }
    }

    // Show results
    if (successCount > 0 && errorMessages.length === 0) {
      // All successful
      emit('save', { success: true })
      emit('close')
    } else if (successCount > 0 && errorMessages.length > 0) {
      // Partial success
      errors.value = { 
        general: `${successCount} item berhasil disimpan. Errors: ${errorMessages.join(', ')}` 
      }
      // Reload to refresh the list
      await loadExistingCompositions()
    } else {
      // All failed
      errors.value = { 
        general: `Gagal menyimpan komposisi: ${errorMessages.join(', ')}` 
      }
    }
  } catch (error: any) {
    console.error('Error saving compositions:', error)
    errors.value = { general: 'Failed to save compositions: ' + (error.message || 'Unknown error') }
  } finally {
    loading.value = false
  }
}

// Debug function
const debugLogState = () => {
  console.log('=== DEBUG STATE ===')
  console.log('Form:', form)
  console.log('Existing compositions:', existingCompositions.value)
  console.log('Selected ingredient:', selectedIngredient.value)
  console.log('Is duplicate:', isDuplicateIngredient.value)
  console.log('Can submit:', canSubmit.value)
  console.log('Items loaded:', items.value.length)
}

// Make console available in template
window.console = console
</script>

<style scoped>
.composition-modal {
  border-radius: 12px;
}

.composition-header {
  background: linear-gradient(135deg, #D4A574 0%, #C4956C 100%);
  color: white;
  padding: 16px 24px;
}

.composition-header .v-icon {
  color: white;
}

.stats-card {
  transition: all 0.3s ease;
}

.stats-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.composition-item {
  transition: all 0.2s ease;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
}

.composition-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border-color: rgb(var(--v-theme-primary));
}

.composition-item.bg-green-lighten-5 {
  background-color: rgba(76, 175, 80, 0.08);
  border-color: rgba(76, 175, 80, 0.3);
}

.composition-item.bg-green-lighten-5:hover {
  background-color: rgba(76, 175, 80, 0.12);
  border-color: rgba(76, 175, 80, 0.5);
}
</style>
