<template>
  <div class="product-variant-tab">
    <ProductVariantTable
      :variants="variants"
      :product="props.product"
      :loading="loading"
      @refresh="loadVariants"
      @delete-variant="openDeleteDialog"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus Variant"
      :item-name="selectedVariant?.name"
      :loading="deleteLoading"
      confirm-text="Hapus Variant"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ProductVariantTable from './ProductVariantTable.vue'
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'

interface Product {
  id: number
  id_product?: number  // Laravel primary key
  name: string
  code: string
}

interface Variant {
  id: number  // From accessor (same as id_variant)
  id_variant: number  // Primary key from Laravel Model
  product_id: number
  name: string
  sku: string
  price: number
  cost_from_composition?: number
  current_stock: number
  min_stock: number
  is_active: boolean
  variant_items_count: number
  updating?: boolean
}

const props = defineProps<{
  product: Product
}>()

// Data
const loading = ref(false)
const variants = ref<Variant[]>([])
const deleteDialog = ref(false)
const deleteLoading = ref(false)
const selectedVariant = ref<Variant | null>(null)

// Simple notification function
const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
  console.log(`${type.toUpperCase()}: ${message}`)
  // You can implement toast notifications here
}

// Methods
const loadVariants = async () => {
  console.log('📋 ProductVariantTab - loadVariants called with product:', props.product)
  console.log('📋 ProductVariantTab - Product keys:', Object.keys(props.product || {}))
  console.log('📋 ProductVariantTab - Product id:', props.product?.id)
  console.log('📋 ProductVariantTab - Product id_product:', props.product?.id_product)
  
  const productId = props.product?.id_product || props.product?.id
  console.log('📋 ProductVariantTab - Using productId:', productId)
  
  if (!productId) {
    console.error('No product ID provided to loadVariants')
    return
  }

  loading.value = true
  try {
    const params = {
      product_id: productId,
      per_page: 100,  // Use reasonable limit
      page: 1         // Ensure page is specified
    }

    console.log('Loading variants for product:', productId, 'with params:', params)
    const response = await axios.get('/api/variants', { params })
    
    // Handle different response structures
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to load variants')
    }
    
    const rawVariants = response.data.data?.data || response.data.data || response.data || []
    console.log('Raw variants received:', rawVariants)
    
    // Map backend 'active' field to frontend 'is_active' field
    variants.value = rawVariants.map((variant: any) => ({
      ...variant,
      is_active: variant.active, // Map active → is_active
      id: variant.id_variant || variant.id, // Map id_variant → id
      product_id: variant.id_product || variant.product_id
    }))
    
    console.log('Mapped variants:', variants.value)
    console.log('Loaded variants:', variants.value.length)
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || error.message || 'Gagal memuat data variant'
    showNotification(errorMessage, 'error')
    console.error('Error loading variants:', error)
    variants.value = []
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {
  // Event sudah di-handle langsung oleh ProductVariantCards
  // Tidak perlu action tambahan di sini
}

const editVariant = (variant: Variant) => {
  // This will be handled by ProductVariantCards component
  console.log('Edit variant:', variant.name)
}

const openDeleteDialog = (variant: Variant) => {
  selectedVariant.value = variant
  deleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedVariant.value) return

  deleteLoading.value = true
  try {
    await axios.delete(`/api/variants/${selectedVariant.value.id}`)
    showNotification('Variant berhasil dihapus')
    deleteDialog.value = false
    loadVariants()
  } catch (error) {
    showNotification('Gagal menghapus variant', 'error')
  } finally {
    deleteLoading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadVariants()
})
</script>

<style scoped>
.variant-card {
  transition: all 0.3s ease;
}

.variant-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.variant-inactive {
  opacity: 0.7;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
