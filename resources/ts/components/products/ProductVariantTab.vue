<template>
  <div class="product-variant-tab">
    <ProductVariantTable
      :variants="variants"
      :product="props.product"
      :loading="loading"
      @refresh="loadVariants"
      @delete-variant="openDeleteDialog"
    />
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
  id_product?: number
  name: string
  code: string
}

interface Variant {
  id: number
  id_variant: number
  product_id: number
  name: string
  sku: string
  price: number
  cost_from_composition?: number
  current_stock: number
  min_stock: number
  is_active: boolean
  variant_items_count?: number // Keep for backward compatibility
  composition_summary?: {
    total_items: number
    critical_items: number
    total_cost: number
    stock_status: string
  }
  variant_items?: Array<any>
  updating?: boolean
}

const props = defineProps<{
  product: Product
}>()

const loading = ref(false)
const variants = ref<Variant[]>([])
const deleteDialog = ref(false)
const deleteLoading = ref(false)
const selectedVariant = ref<Variant | null>(null)

const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
  // Implement toast notifications here if needed
}

const loadVariants = async () => {
  const productId = props.product?.id_product || props.product?.id
  if (!productId) {
    return
  }
  loading.value = true
  try {
    const params = {
      product_id: productId,
      per_page: 100,
      page: 1
    }
    const response = await axios.get('/api/variants', { params })
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to load variants')
    }
    const rawVariants = response.data.data?.data || response.data.data || response.data || []
    variants.value = rawVariants.map((variant: any) => ({
      ...variant,
      is_active: variant.active,
      id: variant.id_variant || variant.id,
      product_id: variant.id_product || variant.product_id
    }))
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || error.message || 'Gagal memuat data variant'
    showNotification(errorMessage, 'error')
    variants.value = []
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {}

const editVariant = (variant: Variant) => {}

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
