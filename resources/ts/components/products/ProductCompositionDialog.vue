<template>
  <VDialog
    v-model="dialogModel"
    max-width="900"
    persistent
  >
    <VCard>
      <!-- Header -->
      <VCardTitle class="d-flex align-center justify-between bg-primary text-white">
        <div class="d-flex align-center gap-3">
          <VIcon icon="mdi-chef-hat" size="24" />
          <div>
            <h3 class="text-h6 font-weight-bold">Kelola Komposisi Item</h3>
            <p class="text-body-2 ma-0 opacity-90">
              {{ product?.name || 'Produk' }}
            </p>
          </div>
        </div>
        <VBtn
          icon="mdi-close"
          variant="text"
          size="small"
          @click="closeDialog"
        />
      </VCardTitle>

      <VCardText class="pa-6">
        <!-- Product Info Summary -->
        <VCard variant="tonal" class="mb-6">
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-4">
              <VAvatar
                :image="product?.image_url"
                size="64"
              >
                <VIcon v-if="!product?.image_url" icon="mdi-coffee" />
              </VAvatar>
              <div class="flex-grow-1">
                <h4 class="text-h6 font-weight-bold mb-1">{{ product?.name }}</h4>
                <p class="text-body-2 text-medium-emphasis mb-1">
                  SKU: {{ product?.sku || 'SKU-' + product?.id_product }}
                </p>
                <p class="text-body-2 text-medium-emphasis mb-0">
                  Harga: {{ formatRupiah(product?.price || 0) }}
                </p>
              </div>
              <VChip
                :color="getStockStatusColor()"
                variant="tonal"
              >
                {{ getStockStatusText() }}
              </VChip>
            </div>
          </VCardText>
        </VCard>

        <!-- Add New Item Form -->
        <VCard class="mb-6" elevation="2">
          <VCardTitle class="bg-grey-lighten-4 py-3">
            <VIcon icon="mdi-plus-circle" class="me-2" />
            Tambah Item Komposisi
          </VCardTitle>
          <VCardText class="pa-4">
            <VRow>
              <VCol cols="12" md="4">
                <VAutocomplete
                  v-model="newItem.itemId"
                  :items="availableItems"
                  item-title="name"
                  item-value="id"
                  label="Pilih Item"
                  placeholder="Cari item..."
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  clearable
                >
                  <template #item="{ props, item }">
                    <VListItem v-bind="props">
                      <template #prepend>
                        <VAvatar size="32" class="me-3">
                          <VIcon icon="mdi-package-variant" />
                        </VAvatar>
                      </template>
                      <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                      <VListItemSubtitle>
                        Stok: {{ item.raw.current_stock }} {{ item.raw.unit }}
                      </VListItemSubtitle>
                    </VListItem>
                  </template>
                </VAutocomplete>
              </VCol>
              <VCol cols="12" md="2">
                <VTextField
                  v-model.number="newItem.quantity"
                  type="number"
                  label="Jumlah"
                  variant="outlined"
                  min="0"
                  step="0.01"
                />
              </VCol>
              <VCol cols="12" md="2">
                <VTextField
                  v-model="newItem.unit"
                  label="Satuan"
                  variant="outlined"
                  placeholder="kg, pcs, ml"
                />
              </VCol>
              <VCol cols="12" md="2">
                <VSwitch
                  v-model="newItem.isCritical"
                  label="Item Kritis"
                  color="error"
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="2">
                <VBtn
                  color="primary"
                  block
                  prepend-icon="mdi-plus"
                  @click="addItem"
                  :disabled="!canAddItem"
                >
                  Tambah
                </VBtn>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Current Composition Items -->
        <VCard elevation="2">
          <VCardTitle class="bg-grey-lighten-4 py-3">
            <VIcon icon="mdi-format-list-bulleted" class="me-2" />
            Daftar Item Komposisi ({{ compositionItems.length }})
          </VCardTitle>
          <VCardText class="pa-0">
            <div v-if="compositionItems.length === 0" class="text-center pa-8">
              <VIcon
                icon="mdi-package-variant-closed"
                size="64"
                class="text-disabled mb-4"
              />
              <h4 class="text-h6 mb-2">Belum ada item komposisi</h4>
              <p class="text-body-2 text-medium-emphasis">
                Tambahkan item yang dibutuhkan untuk membuat produk ini
              </p>
            </div>
            
            <VList v-else>
              <VListItem
                v-for="(item, index) in compositionItems"
                :key="item.id || index"
                class="border-b"
              >
                <template #prepend>
                  <VAvatar size="40" class="me-3">
                    <VIcon 
                      :icon="item.item?.inventory && item.item.inventory.current_stock >= item.quantity_needed ? 'mdi-check-circle' : 'mdi-alert-circle'"
                      :color="item.item?.inventory && item.item.inventory.current_stock >= item.quantity_needed ? 'success' : 'error'"
                    />
                  </VAvatar>
                </template>

                <VListItemTitle class="font-weight-medium">
                  {{ item.item?.name || 'Item tidak ditemukan' }}
                  <VChip
                    v-if="item.is_critical"
                    color="error"
                    size="x-small"
                    variant="tonal"
                    class="ms-2"
                  >
                    Kritis
                  </VChip>
                </VListItemTitle>
                
                <VListItemSubtitle>
                  <div class="d-flex align-center gap-4 mt-1">
                    <span>Dibutuhkan: {{ item.quantity_needed }} {{ item.unit }}</span>
                    <span v-if="item.item?.inventory" :class="item.item.inventory.current_stock >= item.quantity_needed ? 'text-success' : 'text-error'">
                      Stok: {{ item.item.inventory.current_stock }} {{ item.unit }}
                    </span>
                    <span v-if="item.formatted_total_cost" class="text-primary">
                      {{ item.formatted_total_cost }}
                    </span>
                  </div>
                </VListItemSubtitle>

                <template #append>
                  <div class="d-flex gap-2">
                    <VBtn
                      icon="mdi-pencil"
                      variant="text"
                      size="small"
                      color="primary"
                      @click="editItem(item, index)"
                    />
                    <VBtn
                      icon="mdi-delete"
                      variant="text"
                      size="small"
                      color="error"
                      @click="removeItem(index)"
                    />
                  </div>
                </template>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCardText>

      <!-- Actions -->
      <VCardActions class="pa-6 pt-0">
        <VSpacer />
        <VBtn
          variant="outlined"
          @click="closeDialog"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          @click="saveComposition"
          :loading="loading"
        >
          Simpan Komposisi
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { formatRupiah } from '@/@core/utils/formatters'

interface CompositionItem {
  id?: string
  id_product_item?: string
  item?: {
    id: string
    name: string
    inventory?: {
      current_stock: number
    }
  }
  quantity_needed: number
  unit: string
  is_critical: boolean
  formatted_total_cost?: string
}

interface Product {
  id_product: string
  name: string
  sku?: string
  price?: number
  image_url?: string
}

interface Props {
  modelValue: boolean
  product: Product | null
  items: CompositionItem[]
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'save', data: { product: Product, items: CompositionItem[] }): void
  (e: 'refresh'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Dialog state
const dialogModel = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Form state
const loading = ref(false)
const compositionItems = ref<CompositionItem[]>([])

// New item form
const newItem = ref({
  itemId: null as string | null,
  quantity: 1,
  unit: 'pcs',
  isCritical: false
})

// Mock available items - in real app, this would come from API
const availableItems = ref([
  { id: '1', name: 'Kopi Arabica', current_stock: 100, unit: 'kg' },
  { id: '2', name: 'Susu Segar', current_stock: 50, unit: 'liter' },
  { id: '3', name: 'Gula Pasir', current_stock: 25, unit: 'kg' },
  { id: '4', name: 'Whipped Cream', current_stock: 30, unit: 'pcs' },
])

// Computed
const canAddItem = computed(() => {
  return newItem.value.itemId && 
         newItem.value.quantity > 0 && 
         newItem.value.unit.trim().length > 0
})

// Watch for props changes
watch(() => props.items, (newItems) => {
  compositionItems.value = [...newItems]
}, { immediate: true })

// Methods
const getStockStatusColor = () => {
  const hasLowStock = compositionItems.value.some(item => 
    item.item?.inventory && item.item.inventory.current_stock < item.quantity_needed
  )
  return hasLowStock ? 'error' : 'success'
}

const getStockStatusText = () => {
  const hasLowStock = compositionItems.value.some(item => 
    item.item?.inventory && item.item.inventory.current_stock < item.quantity_needed
  )
  return hasLowStock ? 'Stok Kritis' : 'Stok Aman'
}

const addItem = () => {
  if (!canAddItem.value) return

  const selectedItem = availableItems.value.find(item => item.id === newItem.value.itemId)
  if (!selectedItem) return

  const newCompositionItem: CompositionItem = {
    id_product_item: `temp_${Date.now()}`,
    item: {
      id: selectedItem.id,
      name: selectedItem.name,
      inventory: {
        current_stock: selectedItem.current_stock
      }
    },
    quantity_needed: newItem.value.quantity,
    unit: newItem.value.unit,
    is_critical: newItem.value.isCritical
  }

  compositionItems.value.push(newCompositionItem)
  
  // Reset form
  newItem.value = {
    itemId: null,
    quantity: 1,
    unit: 'pcs',
    isCritical: false
  }
}

const editItem = (item: CompositionItem, index: number) => {
  // In real app, open edit dialog or inline editing
  console.log('Edit item:', item, 'at index:', index)
}

const removeItem = (index: number) => {
  compositionItems.value.splice(index, 1)
}

const saveComposition = async () => {
  if (!props.product) return

  loading.value = true
  try {
    // In real app, call API to save composition
    await new Promise(resolve => setTimeout(resolve, 1000)) // Simulate API call
    
    emit('save', {
      product: props.product,
      items: compositionItems.value
    })
    
    emit('refresh')
    closeDialog()
  } catch (error) {
    console.error('Error saving composition:', error)
  } finally {
    loading.value = false
  }
}

const closeDialog = () => {
  dialogModel.value = false
  // Reset form when closing
  setTimeout(() => {
    compositionItems.value = [...props.items]
    newItem.value = {
      itemId: null,
      quantity: 1,
      unit: 'pcs',
      isCritical: false
    }
  }, 300)
}
</script>

<style scoped>
.border-b {
  border-bottom: 1px solid rgb(var(--v-theme-surface-variant));
}
</style>
