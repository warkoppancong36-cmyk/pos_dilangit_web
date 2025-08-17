<template>
  <VDialog
    v-model="isDialogVisible"
    max-width="800"
    persistent
    class="variant-dialog"
  >
    <VCard class="supplier-dialog coffee-dialog">
      <VCardTitle class="pa-6 pb-4 coffee-header">
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon
              :icon="editMode ? 'tabler-edit' : 'tabler-plus'"
              class="text-white"
            />
            <span class="text-h6 text-white">
              {{ editMode ? 'Edit Variant' : 'Tambah Variant' }}
            </span>
          </div>
          <VBtn
            icon="tabler-x"
            variant="text"
            color="white"
            size="small"
            @click="closeDialog"
          />
        </div>
      </VCardTitle>

      <VDivider />

      <VCardText class="dialog-content">
        <VSnackbar
          v-model="snackbarVisible"
          color="error"
          :timeout="4000"
          location="top right"
          multi-line
          class="mb-4"
          @update:modelValue="clearModalError"
        >
          {{ modalErrorMessage }}
          <template #actions>
            <VBtn icon="tabler-x" variant="text" @click="clearModalError" />
          </template>
        </VSnackbar>

        <VForm
          ref="variantForm"
          v-model="formValid"
          fast-fail
          @submit.prevent="handleSave"
        >
          <VRow>
            <VCol v-if="!editMode" cols="12">
              <VSelect
                v-model="formData.id_product"
                :items="productItems"
                item-title="name"
                item-value="id"
                label="Pilih Produk"
                :rules="[(v) => !!v || 'Produk wajib dipilih']"
                required
                variant="outlined"
                density="comfortable"
                class="mb-2"
                :loading="!productOptions || productOptions.length === 0"
                placeholder="Pilih produk untuk variant ini"
                no-data-text="Tidak ada produk tersedia"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.name"
                label="Nama Variant"
                :rules="nameRules"
                required
                variant="outlined"
                density="comfortable"
                placeholder="e.g., Latte Large Hot"
                class="mb-2"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.sku"
                label="SKU (Opsional)"
                :rules="skuRules"
                variant="outlined"
                density="comfortable"
                placeholder="e.g., LAT-LG-HOT"
                class="mb-2"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model.number="formData.price"
                label="Harga Jual"
                :rules="priceRules"
                type="number"
                min="0"
                step="0.01"
                required
                variant="outlined"
                density="comfortable"
                prefix="Rp"
                class="mb-2"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model.number="formData.cost_price"
                label="Harga Modal"
                :rules="costPriceRules"
                type="number"
                min="0"
                step="0.01"
                variant="outlined"
                density="comfortable"
                prefix="Rp"
                class="mb-2"
              />
            </VCol>

            <VCol v-if="profitMargin !== null" cols="12">
              <VAlert
                :type="profitMarginType"
                :text="`Margin Keuntungan: ${profitMargin}%`"
                density="compact"
                class="mb-2"
              />
            </VCol>

            <VCol cols="12">
              <VCard variant="outlined" class="variant-attributes-card">
                <VCardTitle class="pa-4 pb-2">
                  <VIcon icon="tabler-tags" class="me-2" />
                  Atribut Variant
                </VCardTitle>
                <VCardText class="pt-3">
                  <VRow>
                    <VCol
                      v-for="(value, key, index) in formData.variant_values"
                      :key="key"
                      cols="12"
                      md="6"
                    >
                      <div class="d-flex align-center">
                        <VTextField
                          v-model="attributeKeys[index]"
                          label="Nama Atribut"
                          variant="outlined"
                          density="compact"
                          class="me-2"
                          @update:modelValue="updateAttributeKey(key, $event)"
                        />
                        <VTextField
                          v-model="formData.variant_values[key]"
                          label="Nilai"
                          variant="outlined"
                          density="compact"
                          class="me-2"
                        />
                        <VBtn
                          icon
                          variant="text"
                          color="error"
                          size="small"
                          @click="removeAttribute(key)"
                        >
                          <VIcon icon="tabler-trash" />
                        </VBtn>
                      </div>
                    </VCol>
                  </VRow>
                  <VBtn
                    variant="outlined"
                    color="primary"
                    class="mt-2"
                    @click="addAttribute"
                  >
                    <VIcon icon="tabler-plus" class="me-1" />
                    Tambah Atribut
                  </VBtn>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.barcode"
                label="Barcode (Opsional)"
                :rules="barcodeRules"
                variant="outlined"
                density="comfortable"
                class="mb-2"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VSwitch
                v-model="formData.active"
                label="Status Aktif"
                color="success"
                inset
                class="mb-2"
              />
            </VCol>

            <VCol cols="12">
              <VCard variant="outlined" class="stock-settings-card">
                <VCardTitle class="pa-4 pb-2">
                  <VIcon icon="tabler-package" class="me-2" />
                  Pengaturan Stok
                </VCardTitle>
                <VCardText class="pt-0">
                  <VRow>
                    <VCol cols="12" md="6">
                      <VTextField
                        v-model.number="formData.reorder_level"
                        label="Batas Minimum Stok"
                        type="number"
                        min="0"
                        variant="outlined"
                        density="comfortable"
                        hint="Stok minimum sebelum perlu reorder"
                        persistent-hint
                      />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField
                        v-model.number="formData.max_stock_level"
                        label="Maksimum Stok"
                        type="number"
                        min="0"
                        variant="outlined"
                        density="comfortable"
                        hint="Batas maksimum stok yang bisa disimpan"
                        persistent-hint
                      />
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="formData.image"
                label="URL Gambar (Opsional)"
                variant="outlined"
                density="comfortable"
                placeholder="https://example.com/image.jpg"
                class="mb-2"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="dialog-actions pa-4">
        <VSpacer />
        <VBtn
          @click="closeDialog"
          variant="outlined"
          class="me-2"
          :disabled="saveLoading"
        >
          Batal
        </VBtn>
        <VBtn
          @click="handleSave"
          :loading="saveLoading"
          variant="flat"
          color="primary"
          class="save-btn"
        >
          {{ editMode ? 'Update' : 'Simpan' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import type { Variant, VariantCreateData, VariantUpdateData } from '@/utils/api/VariantsApi'
import { computed, nextTick, ref, watch } from 'vue'

interface Product {
  id: number
  name: string
}

interface Props {
  modelValue: boolean
  editMode?: boolean
  variant?: Variant | null
  productOptions?: Product[]
  saveLoading?: boolean
  modalErrorMessage?: string
  formData: {
    id_product: number
    name: string
    variant_values: Record<string, string>
    price: number
    cost_price: number
    sku: string
    barcode: string
    image: string
    active: boolean
    reorder_level: number
    max_stock_level: number
  }
  nameRules: Array<(v: string) => boolean | string>
  priceRules: Array<(v: number) => boolean | string>
  skuRules: Array<(v: string) => boolean | string>
  barcodeRules: Array<(v: string) => boolean | string>
}

const props = withDefaults(defineProps<Props>(), {
  editMode: false,
  variant: null,
  productOptions: () => [],
  saveLoading: false,
  modalErrorMessage: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'save': [data: VariantCreateData | VariantUpdateData]
  'close': []
  'clearError': []
}>()

const snackbarVisible = computed({
  get: () => !!props.modalErrorMessage,
  set: (val: boolean) => {
    if (!val) clearModalError()
  }
})

const variantForm = ref()
const formValid = ref(false)

const isDialogVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const productItems = computed(() => {
  if (!props.productOptions || !Array.isArray(props.productOptions)) return []
  return props.productOptions
    .filter(item =>
      item &&
      typeof item === 'object' &&
      'id' in item &&
      'name' in item &&
      typeof item.id === 'number' &&
      typeof item.name === 'string' &&
      item.name.trim() !== ''
    )
    .map(item => ({
      id: item.id,
      name: item.name.trim(),
      value: item.id,
      title: item.name.trim()
    }))
})

const attributeKeys = ref<string[]>([])

const costPriceRules = [
  (v: number) => v >= 0 || 'Harga modal tidak boleh negatif'
]

const profitMargin = computed(() => {
  if (props.formData.price > 0 && props.formData.cost_price > 0) {
    const margin = ((props.formData.price - props.formData.cost_price) / props.formData.cost_price) * 100
    return Math.ceil(margin)
  }
  return null
})

const profitMarginType = computed(() => {
  if (profitMargin.value === null) return 'info'
  if (profitMargin.value < 10) return 'error'
  if (profitMargin.value < 20) return 'warning'
  return 'success'
})

watch(() => props.formData.variant_values, (newValues) => {
  attributeKeys.value = Object.keys(newValues)
}, { immediate: true, deep: true })

const handleSave = async () => {
  if (!variantForm.value) return
  const { valid } = await variantForm.value.validate()
  if (!valid) return
  const variantData = {
    id_product: props.formData.id_product,
    name: props.formData.name,
    variant_values: { ...props.formData.variant_values },
    price: props.formData.price,
    cost_price: props.formData.cost_price,
    sku: props.formData.sku || undefined,
    barcode: props.formData.barcode || undefined,
    image: props.formData.image || undefined,
    active: props.formData.active
  }
  emit('save', variantData)
}

const closeDialog = () => {
  emit('close')
}

const clearModalError = () => {
  emit('clearError')
}

const addAttribute = () => {
  const newKey = `attribute_${Date.now()}`
  props.formData.variant_values[newKey] = ''
  attributeKeys.value = Object.keys(props.formData.variant_values)
}

const removeAttribute = (key: string) => {
  delete props.formData.variant_values[key]
  attributeKeys.value = Object.keys(props.formData.variant_values)
}

const updateAttributeKey = (oldKey: string, newKey: string) => {
  if (oldKey === newKey) return
  const value = props.formData.variant_values[oldKey]
  delete props.formData.variant_values[oldKey]
  props.formData.variant_values[newKey] = value
  nextTick(() => {
    attributeKeys.value = Object.keys(props.formData.variant_values)
  })
}

watch(() => props.modelValue, (isVisible) => {
  if (
    isVisible &&
    !props.editMode &&
    Object.keys(props.formData.variant_values).length === 0
  ) {
    props.formData.variant_values = {
      size: '',
      temperature: ''
    }
    attributeKeys.value = Object.keys(props.formData.variant_values)
  }
})
</script>
