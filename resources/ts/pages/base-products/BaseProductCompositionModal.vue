<template>
  <VDialog
    :model-value="show"
    max-width="800px"
    persistent
    scrollable
    @update:model-value="!$event && $emit('close')"
  >
    <VCard>
      <VCardTitle class="d-flex align-center gap-2">
        <VIcon icon="tabler-vector-triangle" class="text-primary" />
        <span>{{ editMode ? 'Edit' : 'Add' }} Base Product Composition</span>
      </VCardTitle>
      
      <VDivider />
      
      <VCardText>
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
            <!-- Base Product Selection -->
            <VCol cols="12" md="6">
              <VAutocomplete
                v-model="form.base_product_id"
                :items="baseProducts"
                :loading="baseProductsLoading"
                item-title="name"
                item-value="id_base_product"
                label="Main Base Product"
                placeholder="Select base product"
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

            <!-- Ingredient Base Product Selection -->
            <VCol cols="12" md="6">
              <VAutocomplete
                v-model="form.ingredient_base_product_id"
                :items="ingredientBaseProducts"
                :loading="baseProductsLoading"
                item-title="name"
                item-value="id_base_product"
                label="Ingredient Base Product"
                placeholder="Select ingredient"
                prepend-inner-icon="tabler-bottle"
                clearable
                :error-messages="errors.ingredient_base_product_id"
                @update:model-value="updateSelectedIngredient"
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
                        <VIcon v-else icon="tabler-bottle" size="16" />
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

            <!-- Quantity -->
            <VCol cols="12" md="4">
              <VTextField
                v-model.number="form.quantity"
                type="number"
                label="Quantity Required"
                placeholder="0"
                prepend-inner-icon="tabler-hash"
                step="0.001"
                min="0"
                :error-messages="errors.quantity"
                @input="calculateCost"
              />
            </VCol>

            <!-- Unit Display -->
            <VCol cols="12" md="4">
              <VTextField
                :model-value="selectedIngredient?.unit || ''"
                label="Unit"
                readonly
                prepend-inner-icon="tabler-ruler"
                placeholder="Select ingredient first"
              />
            </VCol>

            <!-- Status -->
            <VCol cols="12" md="4">
              <VSwitch
                v-model="form.is_active"
                label="Active"
                color="success"
                :error-messages="errors.is_active"
              />
            </VCol>

            <!-- Calculated Cost Display -->
            <VCol cols="12" v-if="calculatedCost > 0">
              <VCard variant="tonal" color="info">
                <VCardText>
                  <div class="text-subtitle-2 mb-2">Cost Calculation</div>
                  <VRow>
                    <VCol cols="12" md="4">
                      <div class="text-caption text-medium-emphasis">Cost per Unit:</div>
                      <div class="text-h6 font-weight-bold">
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
            </VCol>

            <!-- Notes -->
            <VCol cols="12">
              <VTextarea
                v-model="form.notes"
                label="Notes"
                placeholder="Add any notes about this composition..."
                rows="3"
                :error-messages="errors.notes"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      
      <VDivider />
      
      <VCardActions>
        <VSpacer />
        <VBtn 
          variant="outlined" 
          @click="$emit('close')"
          :disabled="loading"
        >
          Cancel
        </VBtn>
        <VBtn 
          color="primary" 
          @click="handleSubmit"
          :loading="loading"
          :disabled="!canSubmit"
        >
          {{ editMode ? 'Update' : 'Save' }} Composition
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'

interface Props {
  show: boolean
  composition?: any
  baseProducts: any[]
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
  base_product_id: '',
  ingredient_base_product_id: '',
  quantity: 0,
  notes: '',
  is_active: true
})

// Component state
const loading = ref(false)
const errors = ref<Record<string, string>>({})
const selectedBaseProduct = ref<any>(null)
const selectedIngredient = ref<any>(null)

// Computed properties
const ingredientBaseProducts = computed(() => {
  // Filter out the selected main base product from ingredients
  return props.baseProducts.filter(bp => bp.id_base_product !== form.base_product_id)
})

const canSubmit = computed(() => {
  return form.base_product_id && form.ingredient_base_product_id && form.quantity > 0
})

const calculatedCost = computed(() => {
  if (!selectedIngredient.value || !form.quantity) return 0
  return parseFloat(selectedIngredient.value.cost_per_unit) * parseFloat(form.quantity)
})

const maxProducibleQuantity = computed(() => {
  if (!selectedIngredient.value || !form.quantity) return 0
  const availableStock = parseFloat(selectedIngredient.value.current_stock) || 0
  const requiredQuantity = parseFloat(form.quantity) || 0
  return requiredQuantity > 0 ? Math.floor(availableStock / requiredQuantity) : 0
})

// Helper functions - declared before watch to avoid hoisting issues
const resetForm = () => {
  Object.keys(form).forEach(key => {
    if (key === 'is_active') {
      form[key] = true
    } else {
      form[key] = ''
    }
  })
  selectedBaseProduct.value = null
  selectedIngredient.value = null
  errors.value = {}
}

const updateSelectedBaseProduct = () => {
  const baseProductId = form.base_product_id
  if (baseProductId) {
    selectedBaseProduct.value = props.baseProducts.find(bp => bp.id_base_product == baseProductId)
  } else {
    selectedBaseProduct.value = null
  }
}

const updateSelectedIngredient = () => {
  const ingredientId = form.ingredient_base_product_id
  if (ingredientId) {
    selectedIngredient.value = props.baseProducts.find(bp => bp.id_base_product == ingredientId)
  } else {
    selectedIngredient.value = null
  }
}

// Watch for prop changes
watch(() => props.composition, (newValue) => {
  if (newValue) {
    // Populate form with existing data
    Object.keys(form).forEach(key => {
      form[key] = newValue[key] || (key === 'is_active' ? true : '')
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

const formatNumber = (number) => {
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 3
  }).format(number || 0)
}

const formatCurrency = (number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(number || 0)
}

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  try {
    const payload = {
      base_product_id: form.base_product_id,
      ingredient_base_product_id: form.ingredient_base_product_id,
      quantity: form.quantity,
      notes: form.notes,
      is_active: form.is_active
    }

    emit('save', payload)
  } catch (error) {
    console.error('Error submitting composition:', error)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.text-caption {
  font-size: 0.75rem;
}
</style>
