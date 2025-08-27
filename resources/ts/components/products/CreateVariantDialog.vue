<template>
  <VDialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="600px"
    persistent
  >
    <VCard>
      <VCardTitle class="bg-primary text-white">
        <VIcon icon="mdi-plus-circle" class="me-2" />
        Tambah Variant Baru
        <VSpacer />
        <VBtn
          icon="mdi-close"
          variant="text"
          color="white"
          @click="closeDialog"
        />
      </VCardTitle>

      <VForm ref="formRef" @submit.prevent="saveVariant">
        <VCardText class="pa-6">
          <VRow>
            <!-- Product Selection -->
            <VCol cols="12">
              <VAutocomplete
                v-model="form.selectedProduct"
                :items="products"
                :loading="loadingProducts"
                item-title="name"
                return-object
                label="Pilih Produk *"
                placeholder="Cari produk..."
                variant="outlined"
                :rules="productRules"
                required
                no-data-text="Tidak ada produk ditemukan"
                @update:search="searchProducts"
                @update:model-value="onProductObjectChanged"
              >
                <template #item="{ props: itemProps, item }">
                  <VListItem v-bind="itemProps">
                    <template #prepend>
                      <VAvatar
                        :image="item.raw.image_url"
                        size="40"
                      >
                        <VIcon v-if="!item.raw.image_url" icon="mdi-coffee" />
                      </VAvatar>
                    </template>
                    <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                    <VListItemSubtitle>SKU: {{ item.raw.sku || '-' }}</VListItemSubtitle>
                  </VListItem>
                </template>
                <template #selection="{ item }">
                  <div class="d-flex align-center gap-2">
                    <VAvatar
                      :image="item.raw.image_url"
                      size="24"
                    >
                      <VIcon v-if="!item.raw.image_url" icon="mdi-coffee" size="16" />
                    </VAvatar>
                    <span>{{ item.raw.name }}</span>
                  </div>
                </template>
              </VAutocomplete>
            </VCol>

            <!-- Variant Name -->
            <VCol cols="12">
              <VTextField
                v-model="form.name"
                label="Nama Variant *"
                placeholder="Contoh: Large Hot, Medium Cold, dll"
                variant="outlined"
                :rules="nameRules"
                required
              />
            </VCol>

            <!-- Auto-generated SKU Info -->
            <VCol cols="12">
              <VAlert
                color="info"
                variant="tonal"
                class="mb-0"
              >
                <VAlertTitle>
                  <VIcon icon="mdi-information" class="me-2" />
                  SKU Otomatis
                </VAlertTitle>
                <div class="mt-2">
                  SKU variant akan dibuat otomatis berdasarkan nama produk dan variant
                  <div v-if="form.name && (form.selectedProduct || form.selectedProductId)" class="mt-1 font-weight-medium">
                    Preview SKU: {{ generateSKU() }}
                  </div>
                  <div v-else-if="form.name && !form.selectedProduct && !form.selectedProductId" class="mt-1 text-warning">
                    Pilih produk terlebih dahulu untuk melihat preview SKU
                  </div>
                </div>
              </VAlert>
            </VCol>

            <!-- Active Status -->
            <VCol cols="12">
              <VSwitch
                v-model="form.active"
                label="Variant Aktif"
                color="primary"
                inset
              />
            </VCol>
          </VRow>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-6">
          <VSpacer />
          <VBtn
            variant="outlined"
            @click="closeDialog"
            :disabled="loading"
          >
            Batal
          </VBtn>
          <VBtn
            type="submit"
            color="primary"
            :loading="loading"
          >
            Simpan Variant
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'

interface Product {
  id: number
  id_product: number
  name: string
  sku?: string
  image_url?: string
}

interface Props {
  modelValue: boolean
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'save'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Form refs
const formRef = ref()

// State
const loading = ref(false)
const loadingProducts = ref(false)
const products = ref<Product[]>([])
const form = ref({
  name: '',
  selectedProduct: null as Product | null,
  selectedProductId: null as number | null,
  active: true
})

// Validation rules
const nameRules = [
  (v: string) => !!v || 'Nama variant wajib diisi',
  (v: string) => v.length >= 3 || 'Nama variant minimal 3 karakter'
]

const productIdRules = [
  (v: number | null) => !!v || 'Produk wajib dipilih'
]

const productRules = [
  (v: Product | null) => !!v || 'Produk wajib dipilih'
]

// Methods
const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
  // Create and show snackbar
  const snackbar = document.createElement('div')
  snackbar.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${type === 'success' ? '#4CAF50' : '#f44336'};
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    z-index: 9999;
    font-family: 'Roboto', sans-serif;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 8px;
    animation: slideIn 0.3s ease-out;
    min-width: 300px;
  `
  
  // Add icon
  const icon = document.createElement('span')
  icon.innerHTML = type === 'success' ? '✓' : '✕'
  icon.style.cssText = `
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    font-weight: bold;
  `
  
  snackbar.appendChild(icon)
  snackbar.appendChild(document.createTextNode(message))
  
  // Add animation keyframes
  if (!document.querySelector('#snackbar-styles')) {
    const style = document.createElement('style')
    style.id = 'snackbar-styles'
    style.textContent = `
      @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
    `
    document.head.appendChild(style)
  }
  
  document.body.appendChild(snackbar)
  
  // Remove after 3 seconds with animation
  setTimeout(() => {
    snackbar.style.animation = 'slideOut 0.3s ease-in'
    setTimeout(() => {
      if (snackbar.parentNode) {
        snackbar.parentNode.removeChild(snackbar)
      }
    }, 300)
  }, 3000)
  
}

const fetchProducts = async () => {
  loadingProducts.value = true
  try {
    const response = await axios.get('/api/products')
    products.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Gagal mengambil data produk:', error)
    products.value = []
  } finally {
    loadingProducts.value = false
  }
}

const searchProducts = (search: string) => {
  if (!search && products.value.length === 0) {
    fetchProducts()
  }
}

const onProductChanged = (productId: number) => {
  form.value.selectedProductId = productId
}

const onProductObjectChanged = (product: Product | null) => {
  form.value.selectedProduct = product
  if (product) {
    form.value.selectedProductId = product.id
  } else {
    form.value.selectedProductId = null
  }
}

const getSelectedProductInfo = computed(() => {
  
  // Try using selectedProduct object first
  if (form.value.selectedProduct) {
    return form.value.selectedProduct
  }
  
  // Fallback to finding by ID
  if (form.value.selectedProductId && products.value.length > 0) {
    
    const selectedProd = products.value.find(p => p.id === form.value.selectedProductId)
    return selectedProd
  }
  
  return null
})

const generateSKU = () => {
  const productInfo = getSelectedProductInfo.value
  
  if (!productInfo || !form.value.name) {
    return ''
  }
  
  const productSku = productInfo.sku || 'PROD'
  const variantName = form.value.name.replace(/\s+/g, '').toUpperCase().substring(0, 3)
  const timestamp = Date.now().toString().slice(-4)
  const generatedSKU = `${productSku}-${variantName}-${timestamp}`
  
  return generatedSKU
}

const saveVariant = async () => {
  if (loading.value) return // Prevent multiple submissions
  
  const { valid } = await formRef.value.validate()
  if (!valid) return

  const productInfo = getSelectedProductInfo.value
  
  if (!productInfo) {
    showNotification('Produk tidak ditemukan', 'error')
    return
  }

  if (!productInfo.id && !productInfo.id_product) {
    showNotification('ID Produk tidak valid', 'error')
    console.error('Product missing ID:', productInfo)
    return
  }

  loading.value = true
  try {
    const payload = {
      id_product: productInfo.id || productInfo.id_product, // Use id or id_product from object
      name: form.value.name,
      sku: generateSKU(),
      price: 0, // Default price, bisa diatur nanti via HPP
      cost_price: null,
      barcode: null,
      variant_values: [], // Send as empty array, not object
      active: form.value.active
    }


    const response = await axios.post('/api/variants', payload)
    
    
    if (response.data.success !== false && (response.status === 200 || response.status === 201)) {
      showNotification('Variant berhasil dibuat!', 'success')
      
      // Debug: Check if variant was actually created
      
      // Emit save event to refresh parent data
      emit('save')
      
      // Close dialog after success
      closeDialog()
    } else {
      throw new Error(response.data.message || 'Gagal membuat variant')
    }
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || error.message || 'Gagal membuat variant'
    showNotification(errorMessage, 'error')
    console.error('Save variant error:', error)
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    selectedProduct: null,
    selectedProductId: null,
    active: true
  }
  formRef.value?.resetValidation()
}

const closeDialog = () => {
  emit('update:modelValue', false)
  resetForm()
}

// Watch for dialog open/close
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    resetForm()
    // Always fetch products when dialog opens
    fetchProducts()
  }
})
</script>

<style scoped>
.v-card-title {
  position: sticky;
  top: 0;
  z-index: 1;
}
</style>
