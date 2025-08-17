<script setup lang="ts">
import type { ProductItemFormData } from '@/composables/useProductItems'
import { computed, ref, watch } from 'vue'

interface Props {
  dialog: boolean
  editMode: boolean
  formData: ProductItemFormData
  loading: boolean
  products: any[]
  items: any[]
  errorMessage: string
}

interface Emits {
  (e: 'update:dialog', value: boolean): void
  (e: 'update:errorMessage', value: string): void
  (e: 'update:formData', value: ProductItemFormData): void
  (e: 'save'): void
  (e: 'itemChange', itemId: number): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Form validation
const formRef = ref()
const isFormValid = ref(false)

// Local dialog state
const localDialog = computed({
  get: () => props.dialog,
  set: value => emit('update:dialog', value),
})

// Local form data to avoid prop mutation
const localFormData = computed({
  get: () => props.formData,
  set: value => emit('update:formData', value),
})

// Form validation rules
const rules = {
  required: (value: any) => !!value || 'This field is required',
  positiveNumber: (value: number) => (value > 0) || 'Must be greater than 0',
  stockValidation: (value: number) => {
    if (!value || value <= 0) return 'Jumlah harus lebih dari 0'
    const selectedItem = getSelectedItem()
    if (!selectedItem) return true
    const availableStock = getAvailableStock(selectedItem)
    return value <= availableStock || `Jumlah tidak boleh melebihi stok tersedia (${availableStock})`
  }
}

// Helper functions for stock validation
const getSelectedItem = () => {
  return props.items.find(item => item.id_item === props.formData.item_id)
}

const getAvailableStock = (item: any): number => {
  if (!item) return 0
  // Check if item has inventory data with current_stock
  if (item.inventory) {
    return item.inventory.available_stock || item.inventory.current_stock || 0
  }
  // Fallback to direct properties
  return item.current_stock || item.available_stock || 0
}

const isQuantityExceedsStock = (): boolean => {
  const selectedItem = getSelectedItem()
  if (!selectedItem || !props.formData.quantity_needed) return false
  return props.formData.quantity_needed > getAvailableStock(selectedItem)
}

const getInputColor = (): string => {
  const selectedItem = getSelectedItem()
  if (!selectedItem || !props.formData.quantity_needed) return 'primary'
  
  const availableStock = getAvailableStock(selectedItem)
  const quantity = props.formData.quantity_needed
  const percentage = (quantity / availableStock) * 100
  
  if (quantity > availableStock) return 'error'
  if (percentage > 80) return 'warning' 
  return 'primary'
}

const getStockHint = (): string => {
  const selectedItem = getSelectedItem()
  if (!selectedItem) return ''
  
  const availableStock = getAvailableStock(selectedItem)
  const quantity = props.formData.quantity_needed || 0
  const percentage = availableStock > 0 ? ((quantity / availableStock) * 100).toFixed(1) : '0'
  
  return `Stok tersedia: ${availableStock} | Akan menggunakan: ${percentage}%`
}

// Computed property for items with stock information
const itemsWithStockInfo = computed(() => {
  return props.items.map(item => {
    const availableStock = getAvailableStock(item)
    const stockStatus = availableStock <= 0 ? 'OUT_OF_STOCK' : 
                       availableStock <= 10 ? 'LOW_STOCK' : 'IN_STOCK'
    
    const stockChipColor = stockStatus === 'OUT_OF_STOCK' ? 'error' : 
                          stockStatus === 'LOW_STOCK' ? 'warning' : 'success'
    
    const stockText = stockStatus === 'OUT_OF_STOCK' ? 'Habis' : 
                     stockStatus === 'LOW_STOCK' ? `${availableStock} (Rendah)` : `${availableStock}`

    const formattedCost = new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(item.cost_per_unit || 0)

    return {
      ...item,
      displayName: `${item.name} (${availableStock} ${item.unit})`,
      stockStatus,
      stockChipColor,
      stockText,
      formattedCost
    }
  })
})

// Update form field
const updateField = (field: keyof ProductItemFormData, value: any) => {
  const updatedData = { ...props.formData, [field]: value }

  emit('update:formData', updatedData)
}

// Handle item change
const onItemChange = (itemId: number) => {
  updateField('item_id', itemId)
  emit('itemChange', itemId)
}

// Handle save
const handleSave = async () => {
  const { valid } = await formRef.value?.validate()
  if (valid)
    emit('save')
}

// Clear error message when dialog closes
watch(localDialog, newValue => {
  if (!newValue)
    emit('update:errorMessage', '')
})
</script>

<template>
  <VDialog
    v-model="localDialog"
    persistent
    max-width="600px"
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center">
        <span class="text-h5">{{ editMode ? 'Edit Komposisi Produk' : 'Tambah Komposisi Produk' }}</span>
        <VBtn
          icon="tabler-x"
          size="small"
          variant="text"
          @click="localDialog = false"
        />
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VAlert
          v-if="errorMessage"
          type="error"
          variant="outlined"
          class="mb-4"
          :text="errorMessage"
          closable
          @click:close="() => emit('update:errorMessage', '')"
        />

        <!-- Stock Validation Alert -->
        <VAlert
          v-if="isQuantityExceedsStock()"
          type="warning"
          variant="tonal"
          density="compact"
          class="mb-4"
        >
          <VAlertTitle class="mb-2">Peringatan Stok</VAlertTitle>
          <div class="text-body-2">
            Jumlah yang dibutuhkan ({{ formData.quantity_needed }}) melebihi stok tersedia 
            ({{ getSelectedItem() ? getAvailableStock(getSelectedItem()) : 0 }}).
            <br>
            Silakan kurangi jumlah yang dibutuhkan atau pastikan stok mencukupi.
          </div>
        </VAlert>

        <VForm
          ref="formRef"
          v-model="isFormValid"
          @submit.prevent="handleSave"
        >
          <VRow>
            <VCol cols="12">
              <VSelect
                :model-value="localFormData.product_id"
                :items="products"
                item-title="name"
                item-value="id_product"
                label="Produk *"
                :rules="[rules.required]"
                variant="outlined"
                clearable
                :disabled="editMode"
                no-data-text="Tidak ada produk tersedia"
                @update:model-value="(value) => updateField('product_id', value)"
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                :model-value="localFormData.item_id"
                :items="itemsWithStockInfo"
                item-title="displayName"
                item-value="id_item"
                label="Item *"
                :rules="[rules.required]"
                variant="outlined"
                clearable
                :disabled="editMode"
                no-data-text="Tidak ada item tersedia"
                @update:model-value="onItemChange"
              >
                <template #item="{ props: itemProps, item }">
                  <VListItem 
                    v-bind="itemProps"
                    :disabled="item.raw.stockStatus === 'OUT_OF_STOCK'"
                  >
                    <template #title>
                      {{ item.raw.name }}
                    </template>
                    <template #subtitle>
                      <div class="d-flex align-center justify-space-between">
                        <span>{{ item.raw.unit }} • {{ item.raw.formattedCost }}</span>
                        <VChip
                          :color="item.raw.stockChipColor"
                          size="x-small"
                          variant="tonal"
                        >
                          {{ item.raw.stockText }}
                        </VChip>
                      </div>
                    </template>
                  </VListItem>
                </template>
              </VSelect>
            </VCol>

            <VCol cols="6">
              <VTextField
                :model-value="localFormData.quantity_needed"
                type="number"
                label="Jumlah Dibutuhkan *"
                :rules="[rules.required, rules.positiveNumber, rules.stockValidation]"
                variant="outlined"
                min="1"
                step="1"
                :max="getSelectedItem() ? getAvailableStock(getSelectedItem()) : undefined"
                :color="getInputColor()"
                :error="isQuantityExceedsStock()"
                :error-messages="isQuantityExceedsStock() ? `Stok tidak cukup (${getSelectedItem() ? getAvailableStock(getSelectedItem()) : 0} tersedia)` : []"
                :hint="getStockHint()"
                persistent-hint
                @update:model-value="(value) => updateField('quantity_needed', value ? parseInt(value) : 0)"
              />
            </VCol>

            <VCol cols="6">
              <VTextField
                :model-value="localFormData.unit"
                label="Satuan *"
                :rules="[rules.required]"
                variant="outlined"
                placeholder="e.g., kg, liter, pcs"
                @update:model-value="(value) => updateField('unit', value)"
              />
            </VCol>

            <VCol cols="12">
              <VCheckbox
                :model-value="localFormData.is_critical"
                label="Item Kritis"
                hint="Tandai jika item ini sangat penting untuk produksi"
                @update:model-value="(value) => updateField('is_critical', value)"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                :model-value="localFormData.notes"
                label="Catatan"
                variant="outlined"
                rows="3"
                placeholder="Catatan tambahan tentang hubungan produk-item ini..."
                @update:model-value="(value) => updateField('notes', value)"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="px-6 py-4">
        <VSpacer />
        <VBtn
          variant="text"
          @click="localDialog = false"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          :disabled="!isFormValid || isQuantityExceedsStock()"
          @click="handleSave"
        >
          {{ editMode ? 'Perbarui' : 'Simpan' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
