<template>
  <v-dialog v-model="dialog" max-width="800px" persistent>
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2">
          <v-icon
            :icon="isEdit ? 'mdi-pencil' : 'mdi-plus'"
          />
          <span>
            {{ isEdit ? 'Edit Variant' : 'Tambah Variant Baru' }}
          </span>
        </div>
        <v-btn
          icon="mdi-close"
          variant="text"
          @click="closeDialog"
          :disabled="loading"
        />
      </v-card-title>

      <v-divider />

      <v-card-text class="pt-4">
        <v-form ref="form" @submit.prevent="handleSave">
          <v-row>

            <!-- Variant Name -->
            <v-col cols="12">
              <v-text-field
                v-model="formData.name"
                label="Nama Variant *"
                variant="outlined"
                :rules="[v => !!v || 'Nama variant wajib diisi']"
                placeholder="Contoh: Mangga Manis, Mangga Asam, dll"
              />
              <div class="text-caption text-medium-emphasis mt-1">
                Contoh: {{ exampleText }}
              </div>
            </v-col>

            <!-- SKU (Auto-generated) -->
            <v-col cols="12">
              <v-text-field
                v-model="formData.sku"
                label="SKU"
                variant="outlined"
                readonly
                placeholder="Auto-generated"
                hint="SKU akan digenerate otomatis berdasarkan nama variant"
                persistent-hint
              >
                <template #append-inner>
                  <v-btn
                    icon="mdi-refresh"
                    size="small"
                    variant="text"
                    @click="generateSku"
                    :disabled="!formData.name || isEdit"
                    title="Generate SKU manually"
                  />
                </template>
              </v-text-field>
            </v-col>

            <!-- Status -->
            <v-col cols="12">
              <v-switch
                v-model="formData.is_active"
                label="Status Aktif"
                color="primary"
                :true-value="true"
                :false-value="false"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions class="px-6 pb-6">
        <v-spacer />
        <v-btn
          variant="outlined"
          @click="closeDialog"
          :disabled="loading"
        >
          Batal
        </v-btn>
        <v-btn
          color="primary"
          @click="handleSave"
          :loading="loading"
        >
          {{ isEdit ? 'Update' : 'Simpan' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import axios from 'axios'

interface Variant {
  id?: number
  id_variant?: number
  product_id: number
  name: string
  sku: string
  price?: number
  is_active: boolean
  attributes?: Record<string, any>
  variant_values?: Record<string, string>
}

interface Product {
  id: number
  id_product?: number
  name: string
  code?: string
}

interface Props {
  modelValue: boolean
  variant?: Variant | null
  products?: Product[]
  productId?: number
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

// Dialog state
const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Form data
const defaultFormData = () => ({
  id: undefined as number | undefined,
  product_id: props.productId || (props.products?.[0]?.id) || 0,
  name: '',
  sku: '',
  price: 0,
  is_active: true,
  attributes: {},
  variant_values: {}
})

const formData = ref<Variant>(defaultFormData())

// Computed properties
const isEdit = computed(() => !!props.variant?.id || !!props.variant?.id_variant)

const selectedProductName = computed(() => {
  const productId = formData.value.product_id
  const product = props.products?.find(p => p.id === productId)
  return product?.name || 'Tidak diketahui'
})

const exampleText = computed(() => {
  const productName = selectedProductName.value
  if (productName && productName !== 'Tidak diketahui') {
    return `${productName} Manis, ${productName} Asam, dll`
  }
  return 'Mangga Manis, Mangga Asam, dll'
})

// Helper functions
const clearFormData = () => {
  Object.assign(formData.value, defaultFormData())
}

const generateSku = () => {
  console.log({
    name: formData.value.name,
    product_id: formData.value.product_id,
    productsLength: props.products?.length || 0,
    isEdit: isEdit.value,
    products: props.products
  })
  
  if (!formData.value.name) {
    console.warn('⚠️ Cannot generate SKU: No variant name provided')
    return
  }
  
  if (!formData.value.product_id) {
    console.warn('⚠️ Cannot generate SKU: No product ID provided')
    return
  }
  
  const product = props.products?.find(p => p.id === formData.value.product_id || p.id_product === formData.value.product_id)
  
  if (!product) {
    console.warn('⚠️ Cannot generate SKU: Product not found in products array')
    
    // Fallback: generate SKU without product code
    const variantName = formData.value.name
      .replace(/\s+/g, '')
      .substring(0, 6)
      .toUpperCase()
    
    const generatedSku = `PRD-${variantName}`
    formData.value.sku = generatedSku
    return
  }
  
  // Generate SKU based on product code and variant name
  const productCode = product.code || 
                      product.name?.substring(0, 3).toUpperCase() || 
                      'PRD'
  
  const variantName = formData.value.name
    .replace(/\s+/g, '')
    .substring(0, 3)
    .toUpperCase()
  
  const generatedSku = `${productCode}-${variantName}`
  
  formData.value.sku = generatedSku
}

const handleSave = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return


  // Get product_id with fallback
  let productId = formData.value.product_id
  if (!productId && props.productId) {
    productId = props.productId
  }
  if (!productId && props.products && props.products.length > 0) {
    productId = props.products[0].id
  }

  if (!productId) {
    console.error('No valid product ID found')
    return
  }

  formData.value.product_id = productId

  loading.value = true
  try {
    // Prepare variant_values from name
    const variantValues: Record<string, string> = {
      'Variant': formData.value.name
    }

    // Backend expects these specific field names
    const payload = {
      id_product: productId,
      name: formData.value.name,
      sku: formData.value.sku || '',
      price: formData.value.price || 0,
      variant_values: variantValues,
      active: formData.value.is_active !== false
    }


    if (isEdit.value) {
      const editPayload = {
        ...payload,
        active: formData.value.is_active
      }
      await axios.put(`/api/variants/${formData.value.id}`, editPayload)
    } else {
      await axios.post('/api/variants', payload)
    }

    emit('save')
    closeDialog()
  } catch (error: any) {
    console.error('Error saving variant:', error)
  } finally {
    loading.value = false
  }
}

const closeDialog = () => {
  dialog.value = false
  nextTick(() => {
    clearFormData()
  })
}

// Methods
const loadFormData = () => {
  console.log({
    variant: props.variant, 
    productId: props.productId, 
    products: props.products?.length || 0,
    productsData: props.products 
  })
}
  
  
  if (props.variant) {
    // Mode edit - load variant data
    const newData = {
      ...props.variant,
      id: props.variant.id || props.variant.id_variant,
      attributes: props.variant.attributes || {}
    }
    Object.assign(formData.value, newData)
  } else {
    // Mode create - reset form and set default values
    clearFormData()
    
    // Try multiple ways to get product ID
    let resolvedProductId = null
    
    // Method 1: From productId prop
    if (props.productId) {
      resolvedProductId = props.productId
    }
    // Method 2: From products array with different field names
    else if (props.products && props.products.length > 0) {
      const product = props.products[0]
      resolvedProductId = product.id || product.id_product
    }
    
    if (resolvedProductId) {
      formData.value.product_id = resolvedProductId
    } else {
      console.error('❌ No valid product ID found in props!')
    }
    
    // Generate SKU after setting product
    if (formData.value.product_id) {
      generateSku()
    }
  }

// Watchers
watch(() => props.modelValue, (newVal, oldVal) => {
  if (newVal) {
    loadFormData()
  }
})

watch(() => props.variant, (newVal, oldVal) => {
  if (props.modelValue) {
    loadFormData()
  }
})

watch(() => formData.value.name, (newName) => {
  if (!isEdit.value && newName) {
    generateSku()
  }
})

// Also watch for product_id changes to regenerate SKU
watch(() => formData.value.product_id, (newProductId) => {
  if (!isEdit.value && newProductId && formData.value.name) {
    generateSku()
  }
})
</script>
