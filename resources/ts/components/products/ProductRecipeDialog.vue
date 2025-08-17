<template>
  <VDialog
    v-model="localValue"
    max-width="1000"
    persistent
    class="recipe-dialog"
  >
    <VCard class="recipe-card">
      <VCardTitle class="bg-primary text-white pa-6">
        <div class="d-flex align-center">
          <VIcon 
            icon="tabler-chef-hat" 
            class="me-3"
            size="32"
          />
          <div>
            <div class="text-h5 font-weight-bold">
              {{ editMode ? 'Edit' : 'Buat' }} Resep Produk
            </div>
            <div class="text-subtitle-1 opacity-90">
              {{ productName || 'Produk Baru' }}
            </div>
          </div>
        </div>
      </VCardTitle>

      <VCardText class="pa-0">
        <VForm @submit.prevent="handleSave">
          <!-- Product Info Section -->
          <div class="pa-6 border-b">
            <VRow>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="formData.name"
                  label="Nama Resep"
                  variant="outlined"
                  density="compact"
                  required
                  :rules="[v => !!v || 'Nama resep wajib diisi']"
                />
              </VCol>
              <VCol cols="12" md="3">
                <VTextField
                  v-model.number="formData.portion_size"
                  label="Ukuran Porsi"
                  variant="outlined"
                  density="compact"
                  type="number"
                  min="1"
                  required
                  :rules="[v => v > 0 || 'Ukuran porsi harus lebih dari 0']"
                />
              </VCol>
              <VCol cols="12" md="3">
                <VSelect
                  v-model="formData.portion_unit"
                  label="Satuan Porsi"
                  variant="outlined"
                  density="compact"
                  :items="portionUnitOptions"
                  required
                />
              </VCol>
            </VRow>

            <VRow>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="formData.difficulty_level"
                  label="Tingkat Kesulitan"
                  variant="outlined"
                  density="compact"
                  :items="difficultyOptions"
                  item-title="title"
                  item-value="value"
                  required
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField
                  v-model.number="formData.preparation_time"
                  label="Waktu Persiapan (menit)"
                  variant="outlined"
                  density="compact"
                  type="number"
                  min="0"
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="formData.notes"
                  label="Catatan Tambahan"
                  variant="outlined"
                  density="compact"
                  placeholder="Opsional"
                />
              </VCol>
            </VRow>

            <VRow>
              <VCol cols="12">
                <VTextarea
                  v-model="formData.description"
                  label="Deskripsi Resep"
                  variant="outlined"
                  density="compact"
                  rows="3"
                  auto-grow
                  counter="500"
                  :rules="[v => !v || v.length <= 500 || 'Deskripsi maksimal 500 karakter']"
                />
              </VCol>
            </VRow>
          </div>

          <!-- Recipe Items Section -->
          <div class="pa-6 border-b">
            <div class="d-flex align-center justify-space-between mb-4">
              <div>
                <h3 class="text-h6 font-weight-bold">Bahan-Bahan</h3>
                <p class="text-body-2 text-medium-emphasis">
                  Pilih bahan dan tentukan jumlah yang dibutuhkan
                </p>
              </div>
              <VBtn
                color="primary"
                variant="outlined"
                size="small"
                @click="addRecipeItem"
                prepend-icon="tabler-plus"
              >
                Tambah Bahan
              </VBtn>
            </div>

            <!-- Items List -->
            <div v-if="formData.items.length === 0" class="text-center py-8">
              <VIcon 
                icon="tabler-bowl" 
                size="64" 
                class="text-grey-lighten-1 mb-4"
              />
              <p class="text-body-1 text-medium-emphasis">
                Belum ada bahan yang ditambahkan
              </p>
              <VBtn
                color="primary"
                variant="outlined"
                @click="addRecipeItem"
                prepend-icon="tabler-plus"
              >
                Tambah Bahan Pertama
              </VBtn>
            </div>

            <div v-else>
              <VRow 
                v-for="(item, index) in formData.items" 
                :key="index"
                class="mb-4 align-center"
              >
                <VCol cols="12" md="4">
                  <VAutocomplete
                    v-model="item.item_id"
                    :items="itemOptions"
                    item-title="text"
                    item-value="id"
                    label="Pilih Bahan"
                    variant="outlined"
                    density="compact"
                    required
                    clearable
                    :rules="[v => !!v || 'Bahan wajib dipilih']"
                    @update:model-value="updateItemUnit(item)"
                  >
                    <template #item="{ props: itemProps, item: optionItem }">
                      <VListItem 
                        v-bind="itemProps"
                        :disabled="optionItem.raw.disabled"
                      >
                        <template #title>
                          {{ optionItem.raw.text }}
                        </template>
                        <template #subtitle v-if="optionItem.raw.disabled">
                          <span class="text-error">Stok habis</span>
                        </template>
                      </VListItem>
                    </template>
                  </VAutocomplete>
                </VCol>

                <VCol cols="12" md="4">
                  <VTextField
                    v-model.number="item.quantity"
                    label="Jumlah"
                    variant="outlined"
                    density="compact"
                    type="number"
                    min="0"
                    :max="getItemStock(item.item_id)"
                    step="0.01"
                    required
                    :color="getInputColor(item.item_id, item.quantity)"
                    :rules="[
                      v => v > 0 || 'Jumlah harus lebih dari 0',
                      v => v <= getItemStock(item.item_id) || `Jumlah tidak boleh melebihi stok tersedia (${getItemStock(item.item_id)})`
                    ]"
                    :error="isItemOutOfStock(item.item_id, item.quantity)"
                    :error-messages="isItemOutOfStock(item.item_id, item.quantity) ? `Stok tidak cukup (${getItemStock(item.item_id)} tersedia)` : []"
                    :hint="`Stok tersedia: ${getItemStock(item.item_id)} | Akan menggunakan: ${getStockPercentageUsed(item.item_id, item.quantity)}%`"
                    persistent-hint
                  />
                </VCol>

                <VCol cols="12" md="2">
                  <VTextField
                    v-model="item.unit"
                    label="Satuan"
                    variant="outlined"
                    density="compact"
                    readonly
                  />
                </VCol>

                <VCol cols="12" md="2">
                  <div class="text-caption text-medium-emphasis">Biaya</div>
                  <div class="text-h6 font-weight-bold">
                    {{
                      item.item_id && item.quantity > 0 && Array.isArray(availableItems)
                        ? formatCurrency((availableItems.find(i => i.id_item === item.item_id)?.cost_per_unit || 0) * item.quantity)
                        : formatCurrency(0)
                    }}
                  </div>
                  <VBtn
                    color="error"
                    variant="text"
                    size="small"
                    icon="tabler-trash"
                    @click="removeRecipeItem(index)"
                  />
                </VCol>
              </VRow>

              <!-- Total Cost Summary -->
              <VDivider class="my-4" />
              <VRow>
                <VCol cols="12" md="6">
                  <VCard variant="outlined" class="pa-4">
                    <div class="text-subtitle-2 text-medium-emphasis mb-2">
                      Ringkasan Biaya
                    </div>
                    <div class="d-flex justify-space-between align-center mb-2">
                      <span>Total Biaya Resep:</span>
                      <span class="text-h6 font-weight-bold">
                        {{ formatCurrency(totalRecipeCost) }}
                      </span>
                    </div>
                    <div class="d-flex justify-space-between align-center">
                      <span>Biaya per Porsi:</span>
                      <span class="text-subtitle-1 font-weight-bold text-success">
                        {{ formatCurrency(costPerPortion) }}
                      </span>
                    </div>
                  </VCard>
                </VCol>
              </VRow>
            </div>
          </div>

          <!-- Instructions Section -->
          <div class="pa-6">
            <div class="d-flex align-center justify-space-between mb-4">
              <div>
                <h3 class="text-h6 font-weight-bold">Instruksi Pembuatan</h3>
                <p class="text-body-2 text-medium-emphasis">
                  Langkah-langkah untuk membuat produk
                </p>
              </div>
              <VBtn
                color="primary"
                variant="outlined"
                size="small"
                @click="addInstruction"
                prepend-icon="tabler-plus"
              >
                Tambah Langkah
              </VBtn>
            </div>

            <div v-if="!formData.instructions || formData.instructions.length === 0" class="text-center py-8">
              <VIcon 
                icon="tabler-list-numbers" 
                size="64" 
                class="text-grey-lighten-1 mb-4"
              />
              <p class="text-body-1 text-medium-emphasis">
                Belum ada instruksi yang ditambahkan
              </p>
              <VBtn
                color="primary"
                variant="outlined"
                @click="addInstruction"
                prepend-icon="tabler-plus"
              >
                Tambah Instruksi Pertama
              </VBtn>
            </div>

            <div v-else>
              <div 
                v-for="(instruction, index) in formData.instructions" 
                :key="index"
                class="mb-4"
              >
                <div class="d-flex align-start">
                  <VChip
                    :color="'primary'"
                    size="small"
                    class="me-3 mt-3"
                  >
                    {{ index + 1 }}
                  </VChip>
                  <VTextarea
                    v-model="formData.instructions[index]"
                    :label="`Langkah ${index + 1}`"
                    variant="outlined"
                    density="compact"
                    rows="2"
                    auto-grow
                    class="flex-grow-1"
                    required
                    :rules="[v => !!v || 'Instruksi tidak boleh kosong']"
                  />
                  <VBtn
                    v-if="formData.instructions.length > 1"
                    color="error"
                    variant="text"
                    size="small"
                    icon="tabler-trash"
                    class="ms-2 mt-3"
                    @click="removeInstruction(index)"
                  />
                </div>
              </div>
            </div>
          </div>
        </VForm>
      </VCardText>

      <!-- Actions -->
      <VCardActions class="pa-6">
        <!-- Stock Validation Alert -->
        <VCol cols="12" v-if="stockValidationErrors.length > 0" class="px-0">
          <VAlert
            type="error"
            variant="tonal"
            density="compact"
          >
            <VAlertTitle class="mb-2">Stok Tidak Mencukupi</VAlertTitle>
            <div class="text-body-2">
              <div v-for="error in stockValidationErrors" :key="error" class="mb-1">
                • {{ error }}
              </div>
            </div>
          </VAlert>
        </VCol>
        
        <VSpacer />
        <VBtn
          variant="outlined"
          class="coffee-secondary"
          @click="localValue = false"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          class="coffee-primary"
          :loading="loading"
          :disabled="!canSave"
          @click="handleSave"
        >
          {{ editMode ? 'Perbarui' : 'Simpan' }} Resep
        </VBtn>
      </VCardActions>
    </VCard>

    <!-- Error Snackbar -->
    <VSnackbar
      :model-value="!!errorMessage"
      @update:model-value="!$event && emit('clear-error')"
      color="error"
      location="top right"
      :timeout="6000"
      vertical
      multi-line
      min-width="320"
      max-width="500"
    >
      <div class="d-flex align-center">
        <VIcon 
          icon="tabler-alert-circle" 
          class="me-3"
          size="28"
        />
        <div>
          <div class="text-h6 font-weight-bold">Error!</div>
          <div class="text-subtitle-1">{{ errorMessage }}</div>
        </div>
      </div>
      
      <template #actions>
        <VBtn
          icon="tabler-x"
          size="small"
          variant="text"
          @click="emit('clear-error')"
        />
      </template>
    </VSnackbar>
  </VDialog>
</template>

<script setup lang="ts">
import type { Item } from '@/composables/useItems'
import type { ProductRecipeFormData } from '@/composables/useProductRecipes'
import { formatCurrency } from '@/utils/helpers'
import { computed } from 'vue'

interface Props {
  modelValue: boolean
  editMode?: boolean
  loading?: boolean
  formData: ProductRecipeFormData
  availableItems: Item[]
  errorMessage?: string
  productName?: string
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'close'): void
  (e: 'save'): void
  (e: 'clear-error'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const localValue = computed({
  get: () => props.modelValue,
  set: (value: boolean) => {
    if (!value) {
      emit('close')
    }
    emit('update:modelValue', value)
  }
})

// Helper functions for stock validation
const getAvailableStock = (itemId: number): number => {
  if (!itemId || !Array.isArray(props.availableItems)) return 0
  const item = props.availableItems.find(i => i.id_item === itemId)
  return item?.current_stock || 0
}

const getItemStock = (itemId: number): number => {
  return getAvailableStock(itemId)
}

const isItemOutOfStock = (itemId: number, quantity: number): boolean => {
  if (!itemId || !quantity || quantity <= 0) return false
  return quantity > getAvailableStock(itemId)
}

const getInputColor = (itemId: number, quantity: number): string => {
  if (!itemId || !quantity || quantity <= 0) return 'primary'
  
  const availableStock = getAvailableStock(itemId)
  const percentage = (quantity / availableStock) * 100
  
  if (quantity > availableStock) return 'error'
  if (percentage > 80) return 'warning'
  return 'primary'
}

const getStockPercentageUsed = (itemId: number, quantity: number): string => {
  if (!itemId || !quantity || quantity <= 0) return '0'
  
  const availableStock = getAvailableStock(itemId)
  if (availableStock <= 0) return '0'
  
  const percentage = (quantity / availableStock) * 100
  return percentage.toFixed(1)
}

const formatNumberToLocale = (num: number): string => {
  return new Intl.NumberFormat('id-ID').format(num)
}

// Computed properties
const itemOptions = computed(() => {
  if (!Array.isArray(props.availableItems)) {
    return []
  }
  
  return props.availableItems.map(item => {
    const stockStatus = item.current_stock <= 0 ? '(HABIS)' : 
                       item.current_stock <= 10 ? '(STOK RENDAH)' : 
                       `(Stok: ${item.current_stock})`
    
    const isDisabled = item.current_stock <= 0
    
    return {
      id: item.id_item,
      text: `${item.name} (${formatNumberToLocale(getAvailableStock(item.id_item))} ${item.unit})`,
      item,
      disabled: isDisabled
    }
  })
})

const difficultyOptions = [
  { title: 'Mudah', value: 'easy', color: 'success' },
  { title: 'Sedang', value: 'medium', color: 'warning' },
  { title: 'Sulit', value: 'hard', color: 'error' }
]

const portionUnitOptions = [
  'porsi', 'cup', 'liter', 'ml', 'pcs', 'pack'
]

// Calculate total cost of recipe
const totalRecipeCost = computed(() => {
  if (!Array.isArray(props.availableItems)) {
    return 0
  }
  
  return props.formData.items.reduce((total, recipeItem) => {
    const item = props.availableItems.find(i => i.id_item === recipeItem.item_id)
    if (item && recipeItem.quantity > 0) {
      return total + (item.cost_per_unit * recipeItem.quantity)
    }
    return total
  }, 0)
})

const costPerPortion = computed(() => {
  const portionSize = props.formData.portion_size || 1
  return totalRecipeCost.value / portionSize
})

const canSave = computed(() => {
  return props.formData.items.every(item => {
    if (item.item_id && item.quantity > 0) {
      return !isItemOutOfStock(item.item_id, item.quantity)
    }
    return true
  })
})

const stockValidationErrors = computed(() => {
  const errors: string[] = []
  props.formData.items.forEach(item => {
    if (item.item_id && item.quantity > 0 && isItemOutOfStock(item.item_id, item.quantity)) {
      const itemData = props.availableItems.find(i => i.id_item === item.item_id)
      const availableStock = getAvailableStock(item.item_id)
      if (itemData) {
        errors.push(`${itemData.name}: maksimal ${formatNumberToLocale(availableStock)} ${itemData.unit}`)
      }
    }
  })
  return errors
})

// Form handlers
const updateItemUnit = (item: any) => {
  if (item.item_id && Array.isArray(props.availableItems)) {
    const selectedItem = props.availableItems.find(i => i.id_item === item.item_id)
    if (selectedItem) {
      item.unit = selectedItem.unit
    }
  }
}

const handleSave = () => {
  emit('clear-error')
  
  // Validasi stok sebelum submit
  const invalidItems = props.formData.items.filter(item => {
    if (item.item_id && item.quantity > 0) {
      return isItemOutOfStock(item.item_id, item.quantity)
    }
    return false
  })
  
  if (invalidItems.length > 0) {
    // Keep dialog open - validation errors will be shown in alert
    emit('update:modelValue', true)
    return
  }
  
  emit('save')
}

const addRecipeItem = () => {
  props.formData.items.push({
    product_id: props.formData.product_id,
    item_id: 0,
    quantity: 1,
    unit: '',
    notes: ''
  })
}

const removeRecipeItem = (index: number) => {
  props.formData.items.splice(index, 1)
}

const addInstruction = () => {
  if (!props.formData.instructions) {
    props.formData.instructions = []
  }
  props.formData.instructions.push('')
}

const removeInstruction = (index: number) => {
  if (props.formData.instructions && props.formData.instructions.length > 1) {
    props.formData.instructions.splice(index, 1)
  }
}
</script>

<style scoped>
/* ProductRecipeDialog styles handled by @core/dialog-styles.scss */
</style>
