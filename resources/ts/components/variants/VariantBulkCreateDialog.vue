<template>
  <VDialog
    v-model="isDialogVisible"
    max-width="900"
    persistent
    class="variant-bulk-dialog"
  >
    <VCard class="dialog-card">
      <VCardTitle class="dialog-title d-flex align-center">
        <VIcon 
          icon="tabler-stack-2"
          class="me-3 title-icon"
        />
        <span class="title-text">Buat Variants Sekaligus</span>
        <VSpacer />
        <VBtn
          @click="closeDialog"
          icon
          variant="text"
          class="dialog-close-btn"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="dialog-content">
        <!-- Error Alert -->
        <VAlert
          v-if="modalErrorMessage"
          type="error"
          :text="modalErrorMessage"
          class="mb-4"
          closable
          @click:close="clearModalError"
        />

        <VForm
          ref="bulkForm"
          v-model="formValid"
          fast-fail
          @submit.prevent="handleSave"
        >
          <!-- Product Selection -->
          <VRow class="mb-4">
            <VCol cols="12">
              <VSelect
                v-model="bulkCreateData.id_product"
                :items="productOptions"
                item-title="name"
                item-value="id"
                label="Pilih Produk"
                :rules="[(v) => !!v || 'Produk wajib dipilih']"
                required
                variant="outlined"
                density="comfortable"
              />
            </VCol>
          </VRow>

          <!-- Base Prices -->
          <VRow class="mb-4">
            <VCol cols="12" md="6">
              <VTextField
                v-model.number="bulkCreateData.base_price"
                label="Harga Dasar"
                :rules="basePriceRules"
                type="number"
                min="0"
                step="0.01"
                required
                variant="outlined"
                density="comfortable"
                prefix="Rp"
                hint="Harga dasar yang akan disesuaikan berdasarkan atribut"
                persistent-hint
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model.number="bulkCreateData.base_cost_price"
                label="Harga Modal Dasar"
                type="number"
                min="0"
                step="0.01"
                variant="outlined"
                density="comfortable"
                prefix="Rp"
                hint="Harga modal dasar (opsional)"
                persistent-hint
              />
            </VCol>
          </VRow>

          <!-- Variant Attributes Section -->
          <VCard
            variant="outlined"
            class="attributes-section mb-4"
          >
            <VCardTitle class="pa-4 pb-2">
              <VIcon 
                icon="tabler-tags"
                class="me-2"
              />
              Atribut Variant
            </VCardTitle>
            <VCardText class="pt-0">
              <div
                v-for="(attribute, index) in bulkCreateData.attributes"
                :key="`attr-${index}`"
                class="attribute-group mb-4"
              >
                <VCard
                  variant="outlined"
                  class="attribute-card"
                >
                  <VCardText class="pa-4">
                    <div class="d-flex align-center mb-3">
                      <VTextField
                        v-model="attribute.name"
                        label="Nama Atribut"
                        :rules="attributeNameRules"
                        variant="outlined"
                        density="compact"
                        class="me-2"
                        placeholder="e.g., Size, Temperature"
                      />
                      <VBtn
                        v-if="bulkCreateData.attributes.length > 1"
                        icon
                        variant="text"
                        color="error"
                        size="small"
                        @click="removeAttribute(index)"
                      >
                        <VIcon icon="tabler-trash" />
                      </VBtn>
                    </div>

                    <!-- Attribute Values -->
                    <div class="attribute-values">
                      <VLabel class="mb-2">Nilai-nilai Atribut:</VLabel>
                      <div class="d-flex flex-wrap gap-2 mb-2">
                        <VChip
                          v-for="(value, valueIndex) in attribute.values"
                          :key="`value-${index}-${valueIndex}`"
                          closable
                          color="primary"
                          variant="outlined"
                          @click:close="removeAttributeValue(index, valueIndex)"
                        >
                          {{ value }}
                        </VChip>
                      </div>
                      <div class="d-flex align-center">
                        <VTextField
                          v-model="newAttributeValues[index]"
                          label="Tambah nilai"
                          variant="outlined"
                          density="compact"
                          class="me-2"
                          @keyup.enter="addAttributeValue(index)"
                        />
                        <VBtn
                          @click="addAttributeValue(index)"
                          variant="outlined"
                          size="small"
                          color="primary"
                        >
                          <VIcon icon="tabler-plus" />
                        </VBtn>
                      </div>
                    </div>
                  </VCardText>
                </VCard>
              </div>

              <VBtn
                @click="addAttribute"
                variant="outlined"
                color="primary"
                class="add-attribute-btn"
              >
                <VIcon 
                  icon="tabler-plus"
                  class="me-1"
                />
                Tambah Atribut
              </VBtn>
            </VCardText>
          </VCard>

          <!-- Price Adjustments Section -->
          <VCard
            variant="outlined"
            class="price-adjustments-section mb-4"
          >
            <VCardTitle class="pa-4 pb-2">
              <VIcon 
                icon="tabler-currency-dollar"
                class="me-2"
              />
              Penyesuaian Harga (Opsional)
            </VCardTitle>
            <VCardText class="pt-0">
              <VRow>
                <VCol
                  v-for="attribute in bulkCreateData.attributes"
                  :key="`price-${attribute.name}`"
                  cols="12"
                  md="6"
                >
                  <VCard
                    variant="outlined"
                    class="price-adjustment-card"
                  >
                    <VCardTitle class="text-caption pa-3 pb-1">
                      {{ attribute.name }}
                    </VCardTitle>
                    <VCardText class="pt-0 pb-3">
                      <div
                        v-for="value in attribute.values"
                        :key="`price-${attribute.name}-${value}`"
                        class="d-flex align-center mb-2"
                      >
                        <VLabel class="flex-grow-1 me-2">{{ value }}:</VLabel>
                        <VTextField
                          v-model.number="bulkCreateData.price_adjustments[`${attribute.name}:${value}`]"
                          type="number"
                          step="0.01"
                          variant="outlined"
                          density="compact"
                          prefix="Rp"
                          placeholder="0"
                          style="max-width: 120px;"
                          hint="+ untuk tambah, - untuk kurangi"
                          persistent-hint
                        />
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <!-- Preview Section -->
          <VCard
            v-if="previewVariants.length > 0"
            variant="outlined"
            class="preview-section"
          >
            <VCardTitle class="pa-4 pb-2">
              <VIcon 
                icon="tabler-eye"
                class="me-2"
              />
              Preview Variants ({{ previewVariants.length }} variants)
            </VCardTitle>
            <VCardText class="pt-0">
              <VDataTable
                :headers="previewHeaders"
                :items="previewVariants"
                density="compact"
                class="preview-table"
                :items-per-page="10"
                :show-current-page="true"
              >
                <template #item.price="{ item }">
                  <span class="font-weight-medium text-success">
                    {{ formatPrice(item.price) }}
                  </span>
                </template>
                <template #item.cost_price="{ item }">
                  <span class="text-medium-emphasis">
                    {{ formatPrice(item.cost_price || 0) }}
                  </span>
                </template>
                <template #item.variant_values="{ item }">
                  <div class="d-flex flex-wrap gap-1">
                    <VChip
                      v-for="(value, key) in item.variant_values"
                      :key="`preview-${key}`"
                      size="x-small"
                      color="primary"
                      variant="outlined"
                    >
                      {{ key }}: {{ value }}
                    </VChip>
                  </div>
                </template>
              </VDataTable>
            </VCardText>
          </VCard>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="dialog-actions pa-4">
        <div class="d-flex align-center w-100">
          <VBtn
            @click="generatePreview"
            variant="outlined"
            color="info"
            class="me-2"
            :disabled="!canGeneratePreview"
          >
            <VIcon 
              icon="tabler-refresh"
              class="me-1"
            />
            Preview
          </VBtn>
          <VSpacer />
          <VBtn
            @click="closeDialog"
            variant="outlined"
            class="me-2"
            :disabled="bulkCreateLoading"
          >
            Batal
          </VBtn>
          <VBtn
            @click="handleSave"
            :loading="bulkCreateLoading"
            :disabled="previewVariants.length === 0"
            variant="flat"
            color="primary"
            class="save-btn"
          >
            Buat {{ previewVariants.length }} Variants
          </VBtn>
        </div>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import type { VariantBulkCreateData } from '@/utils/api/VariantsApi'
import { computed, ref, watch } from 'vue'

// Define interfaces for props
interface Product {
  id: number
  name: string
}

interface PreviewVariant {
  name: string
  variant_values: Record<string, string>
  price: number
  cost_price: number
  sku: string
}

interface Props {
  modelValue: boolean
  productOptions?: Product[]
  bulkCreateLoading?: boolean
  modalErrorMessage?: string
  bulkCreateData: VariantBulkCreateData
}

const props = withDefaults(defineProps<Props>(), {
  productOptions: () => [],
  bulkCreateLoading: false,
  modalErrorMessage: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'save': [data: VariantBulkCreateData]
  'close': []
  'clearError': []
}>()

// Form reference and validation
const bulkForm = ref()
const formValid = ref(false)

// Local state
const newAttributeValues = ref<string[]>([])
const previewVariants = ref<PreviewVariant[]>([])

// Computed properties
const isDialogVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const canGeneratePreview = computed(() => {
  return props.bulkCreateData.id_product > 0 &&
         props.bulkCreateData.base_price > 0 &&
         props.bulkCreateData.attributes.some(attr => 
           attr.name.trim() && attr.values.length > 0
         )
})

// Validation rules
const basePriceRules = [
  (v: number) => !!v || 'Harga dasar wajib diisi',
  (v: number) => v > 0 || 'Harga dasar harus lebih dari 0'
]

const attributeNameRules = [
  (v: string) => !!v || 'Nama atribut wajib diisi',
  (v: string) => v.length >= 2 || 'Nama atribut minimal 2 karakter'
]

// Preview table headers
const previewHeaders = [
  { title: 'Nama', key: 'name' },
  { title: 'Atribut', key: 'variant_values' },
  { title: 'Harga', key: 'price' },
  { title: 'Harga Modal', key: 'cost_price' },
  { title: 'SKU', key: 'sku' }
]

// Methods
const addAttribute = () => {
  props.bulkCreateData.attributes.push({ name: '', values: [] })
  newAttributeValues.value.push('')
}

const removeAttribute = (index: number) => {
  props.bulkCreateData.attributes.splice(index, 1)
  newAttributeValues.value.splice(index, 1)
}

const addAttributeValue = (attributeIndex: number) => {
  const value = newAttributeValues.value[attributeIndex]?.trim()
  if (!value) return

  const attribute = props.bulkCreateData.attributes[attributeIndex]
  if (!attribute.values.includes(value)) {
    attribute.values.push(value)
    newAttributeValues.value[attributeIndex] = ''
  }
}

const removeAttributeValue = (attributeIndex: number, valueIndex: number) => {
  props.bulkCreateData.attributes[attributeIndex].values.splice(valueIndex, 1)
}

const generateCombinations = (attributes: Array<{ name: string; values: string[] }>) => {
  if (attributes.length === 0) return []

  const validAttributes = attributes.filter(attr => attr.name.trim() && attr.values.length > 0)
  if (validAttributes.length === 0) return []

  let combinations = [{}] as Array<Record<string, string>>

  for (const attribute of validAttributes) {
    const newCombinations = []
    for (const combination of combinations) {
      for (const value of attribute.values) {
        newCombinations.push({
          ...combination,
          [attribute.name]: value
        })
      }
    }
    combinations = newCombinations
  }

  return combinations
}

const calculatePrice = (basePrice: number, variantValues: Record<string, string>) => {
  let adjustedPrice = basePrice

  for (const [key, value] of Object.entries(variantValues)) {
    const adjustmentKey = `${key}:${value}`
    const adjustment = props.bulkCreateData.price_adjustments[adjustmentKey] || 0
    adjustedPrice += adjustment
  }

  return Math.max(0, adjustedPrice)
}

const generateVariantName = (productName: string, variantValues: Record<string, string>) => {
  const values = Object.values(variantValues).filter(v => v.trim())
  return values.length > 0 ? `${productName} ${values.join(' ')}` : productName
}

const generateSku = (productName: string, variantValues: Record<string, string>) => {
  const productCode = productName
    .split(' ')
    .map(word => word.substring(0, 3).toUpperCase())
    .join('')
    .substring(0, 6)

  const variantCode = Object.values(variantValues)
    .map(value => value.substring(0, 2).toUpperCase())
    .join('')

  return `${productCode}-${variantCode}-${Date.now().toString().substring(-4)}`
}

const generatePreview = () => {
  const combinations = generateCombinations(props.bulkCreateData.attributes)
  const selectedProduct = props.productOptions.find(p => p.id === props.bulkCreateData.id_product)
  const productName = selectedProduct?.name || 'Product'

  previewVariants.value = combinations.map(variantValues => {
    const price = calculatePrice(props.bulkCreateData.base_price, variantValues)
    const costPrice = props.bulkCreateData.base_cost_price || 0

    return {
      name: generateVariantName(productName, variantValues),
      variant_values: variantValues,
      price,
      cost_price: costPrice,
      sku: generateSku(productName, variantValues)
    }
  })
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(price)
}

const handleSave = async () => {
  if (!bulkForm.value) return
  
  const { valid } = await bulkForm.value.validate()
  if (!valid || previewVariants.value.length === 0) return

  emit('save', { ...props.bulkCreateData })
}

const closeDialog = () => {
  emit('close')
}

const clearModalError = () => {
  emit('clearError')
}

// Watch for changes to auto-generate preview
watch(() => [
  props.bulkCreateData.id_product,
  props.bulkCreateData.base_price,
  props.bulkCreateData.attributes,
  props.bulkCreateData.price_adjustments
], () => {
  if (canGeneratePreview.value) {
    generatePreview()
  } else {
    previewVariants.value = []
  }
}, { deep: true })

// Initialize newAttributeValues when dialog opens
watch(() => props.modelValue, (isVisible) => {
  if (isVisible) {
    newAttributeValues.value = new Array(props.bulkCreateData.attributes.length).fill('')
  }
})
</script>

<style lang="scss" scoped>
.variant-bulk-dialog {
  :deep(.v-dialog) {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  }
}

.attributes-section,
.price-adjustments-section,
.preview-section {
  border: 1px solid rgba(var(--v-border-color), 0.12);
  
  .v-card-title {
    background-color: rgba(var(--v-theme-surface), 0.8);
    border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
    font-size: 0.95rem;
    font-weight: 600;
  }
}

.attribute-card {
  background-color: rgba(var(--v-theme-surface), 0.5);
  margin-bottom: 12px;
}

.price-adjustment-card {
  background-color: rgba(var(--v-theme-surface), 0.3);
  
  .v-card-title {
    font-size: 0.85rem;
    font-weight: 500;
    background-color: transparent;
    border-bottom: none;
    color: rgba(var(--v-theme-on-surface), 0.8);
  }
}

.preview-table {
  border: 1px solid rgba(var(--v-border-color), 0.08);
  border-radius: 8px;
}

.attribute-values {
  .v-chip {
    margin: 2px;
  }
}

.add-attribute-btn {
  border-color: rgba(var(--v-theme-primary), 0.3);
  
  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.05);
  }
}

.save-btn {
  min-width: 140px;
  font-weight: 600;
}

.title-icon {
  color: rgb(var(--v-theme-primary));
}

// Price input styling
.v-text-field {
  &[prefix="Rp"] {
    :deep(.v-field__prefix) {
      color: rgb(var(--v-theme-primary));
      font-weight: 600;
    }
  }
}

// Coffee theme enhancements
.v-text-field {
  :deep(.v-field) {
    border-radius: 8px;
  }
}

.v-btn {
  border-radius: 8px;
  text-transform: none;
}

.v-alert {
  border-radius: 8px;
}

.v-card {
  border-radius: 12px;
}

.v-chip {
  border-radius: 6px;
}
</style>
