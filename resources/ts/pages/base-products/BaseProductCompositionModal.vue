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
              <VCardText class="text-center pa-4">
                <VIcon icon="mdi-package-variant" color="primary" class="mb-2" />
                <div class="text-h6 font-weight-bold">{{ formatCurrency(selectedBaseProduct.cost_per_unit || 0) }}</div>
                <div class="text-caption text-medium-emphasis">HPP</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="6" md="3">
            <VCard variant="outlined" class="stats-card">
              <VCardText class="text-center pa-4">
                <VIcon icon="mdi-currency-usd" color="success" class="mb-2" />
                <div class="text-h6 font-weight-bold">{{ formatCurrency(selectedBaseProduct.selling_price || 0) }}</div>
                <div class="text-caption text-medium-emphasis">Harga Jual</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="6" md="3">
            <VCard variant="outlined" class="stats-card">
              <VCardText class="text-center pa-4">
                <VIcon icon="mdi-trending-up" color="info" class="mb-2" />
                <div class="text-h6 font-weight-bold">{{ formatCurrency(calculateMargin()) }}</div>
                <div class="text-caption text-medium-emphasis">Margin</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="6" md="3">
            <VCard variant="outlined" class="stats-card">
              <VCardText class="text-center pa-4">
                <VIcon icon="mdi-percent" color="warning" class="mb-2" />
                <div class="text-h6 font-weight-bold">{{ calculateMarginPercentage() }}%</div>
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
                    Tambah
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

          <!-- Compositions Table -->
          <VTable v-else class="compositions-table">
            <thead>
              <tr>
                <th class="text-left">Item</th>
                <th class="text-center">Jumlah</th>
                <th class="text-center">Satuan</th>
                <th class="text-right">Cost</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="composition in existingCompositions" :key="composition.id">
                <td>
                  <div class="d-flex align-center gap-3">
                    <VAvatar size="32">
                      <VImg 
                        v-if="composition.ingredient?.image_url" 
                        :src="composition.ingredient.image_url" 
                        :alt="composition.ingredient.name"
                      />
                      <VIcon v-else icon="tabler-bottle" size="16" />
                    </VAvatar>
                    <div>
                      <div class="font-weight-medium">{{ composition.ingredient?.name }}</div>
                      <div class="text-caption text-medium-emphasis">
                        Stock: {{ formatNumber(composition.ingredient?.current_stock) }} {{ composition.ingredient?.unit }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="text-center">
                  <span class="font-weight-medium">{{ formatNumber(composition.quantity) }}</span>
                </td>
                <td class="text-center">
                  <VChip size="small" variant="outlined">
                    {{ composition.ingredient?.unit }}
                  </VChip>
                </td>
                <td class="text-right">
                  <div class="font-weight-medium">{{ formatCurrency(composition.total_cost) }}</div>
                </td>
                <td class="text-center">
                  <VChip 
                    :color="composition.is_active ? 'success' : 'error'"
                    size="small"
                    variant="flat"
                  >
                    {{ composition.is_active ? 'Aktif' : 'Nonaktif' }}
                  </VChip>
                  <VChip 
                    v-if="composition.is_critical"
                    color="warning"
                    size="small"
                    variant="flat"
                    class="ml-1"
                  >
                    Kritis
                  </VChip>
                </td>
                <td class="text-center">
                  <VBtn
                    icon
                    size="small"
                    variant="text"
                    color="error"
                    @click="removeComposition(composition)"
                  >
                    <VIcon icon="mdi-delete" />
                  </VBtn>
                </td>
              </tr>
            </tbody>
          </VTable>
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
          Simpan Komposisi
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
  ingredient_base_product_id: number
  quantity: number
  is_active: boolean
  is_critical?: boolean
  notes?: string
  ingredient?: BaseProduct
  total_cost?: number
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
  return form.base_product_id && form.ingredient_item_id && form.quantity > 0
})

const calculatedCost = computed(() => {
  if (!selectedIngredient.value || !form.quantity) return 0
  const itemCost = selectedIngredient.value.cost_per_unit || 0
  return parseFloat(itemCost.toString()) * parseFloat(form.quantity.toString())
})

const maxProducibleQuantity = computed(() => {
  if (!selectedIngredient.value || !form.quantity) return 0
  const availableStock = parseFloat(selectedIngredient.value.current_stock.toString()) || 0
  const requiredQuantity = parseFloat(form.quantity.toString()) || 0
  return requiredQuantity > 0 ? Math.floor(availableStock / requiredQuantity) : 0
})

// Helper functions
const formatNumber = (value: number | string): string => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  return isNaN(num) ? '0' : num.toLocaleString('id-ID')
}

const formatCurrency = (value: number | string): string => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  return isNaN(num) ? 'Rp 0' : `Rp ${num.toLocaleString('id-ID')}`
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
    } else if (key === 'quantity' || key === 'base_product_id' || key === 'ingredient_item_id') {
      form[key] = 0
    } else {
      form[key] = ''
    }
  })
  selectedIngredient.value = null
  errors.value = {}
}

const updateSelectedBaseProduct = () => {
  const baseProductId = Number(form.base_product_id)
  if (baseProductId) {
    selectedBaseProduct.value = props.baseProducts.find(bp => bp.id_base_product === baseProductId) || null
    loadExistingCompositions()
    loadItems() // Load items when base product is selected
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
    const response = await axios.get(`/api/base-product-compositions?base_product_id=${form.base_product_id}`)
    if (response.data.success) {
      existingCompositions.value = response.data.data.data || []
    }
  } catch (error) {
    console.error('Error loading compositions:', error)
  }
}

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
  if (!canSubmit.value) return

  loading.value = true
  errors.value = {}

  try {
    const payload = {
      base_product_id: form.base_product_id,
      ingredient_item_id: form.ingredient_item_id,
      quantity: Number(form.quantity),
      is_active: form.is_active,
      is_critical: form.is_critical,
      notes: form.notes || null
    }

    // Add to local list instead of saving immediately
    const newComposition: Composition = {
      ...payload,
      id: Date.now(), // temporary ID
      ingredient: selectedIngredient.value!,
      total_cost: calculatedCost.value
    }

    existingCompositions.value.push(newComposition)

    // Reset form for next item
    form.ingredient_base_product_id = 0
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

  try {
    // Save all compositions
    for (const composition of existingCompositions.value) {
      if (!composition.id || composition.id > 1000000) { // temporary ID
        const payload = {
          base_product_id: composition.base_product_id,
          ingredient_base_product_id: composition.ingredient_base_product_id,
          quantity: composition.quantity,
          is_active: composition.is_active,
          is_critical: composition.is_critical,
          notes: composition.notes
        }
        await axios.post('/api/base-product-compositions', payload)
      }
    }

    emit('save', { success: true })
    emit('close')
  } catch (error: any) {
    console.error('Error saving compositions:', error)
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: error.response?.data?.message || 'Failed to save compositions' }
    }
  } finally {
    loading.value = false
  }
}
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

.compositions-table {
  border-radius: 8px;
}

.compositions-table thead th {
  background-color: rgb(var(--v-theme-surface-variant));
  font-weight: 600;
  padding: 16px;
}

.compositions-table tbody td {
  padding: 12px 16px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
