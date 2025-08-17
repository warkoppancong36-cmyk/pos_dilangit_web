<script setup lang="ts">
import { getAuthToken } from '@/utils/auth'
import axios from 'axios'
import { computed, nextTick, onMounted, ref, watch } from 'vue'

// Interfaces
interface Item {
  id_item: number | null
  quantity: number
  unit_cost: number
  unit_cost_display: string
  total_cost: number
}

interface PurchaseData {
  id_supplier: number | null
  purchase_date: string
  items: Item[]
  notes: string
  discount_amount: number
  discount_amount_display: string
}

// Props
interface Props {
  modelValue: boolean
  purchase?: any
  mode: 'create' | 'edit'
}

const props = withDefaults(defineProps<Props>(), {
  purchase: null,
  mode: 'create',
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'saved': []
}>()

// Dialog state
const localDialog = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
})

// Data
const form = ref()
const formValid = ref(false)
const saving = ref(false)
const loadingSuppliers = ref(false)
const loadingItems = ref(false)

const suppliers = ref<any[]>([])
const items = ref<any[]>([])

// Form data
const formData = ref<PurchaseData>({
  id_supplier: null,
  purchase_date: new Date().toISOString().split('T')[0],
  notes: '',
  discount_amount: 0,
  discount_amount_display: '0',
  items: [],
})

// Computed
const title = computed(() => {
  return props.mode === 'edit' ? 'Edit Purchase Order' : 'Buat Purchase Order Baru'
})

const totals = computed(() => {
  const subtotal = formData.value.items.reduce((sum, item) => sum + (item.total_cost || 0), 0)
  const discount = Number.parseFloat(formData.value.discount_amount_display.replace(/\D/g, '')) || 0
  const tax = (subtotal - discount) * 0.11 // 11% PPN
  const total = subtotal - discount + tax

  return {
    subtotal,
    discount,
    tax,
    total,
  }
})

const canSave = computed(() => {
  return formValid.value
         && formData.value.items.length > 0
         && formData.value.items.every(item =>
           item.id_item && item.quantity > 0 && item.unit_cost > 0,
         )
})

// Validation rules
const rules = {
  required: (value: any) => !!value || 'Field ini wajib diisi',
}

// Methods
const loadSuppliers = async () => {
  try {
    loadingSuppliers.value = true

    const token = getAuthToken()

    const headers: any = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    }

    if (token)
      headers.Authorization = `Bearer ${token}`

    const response = await axios.get('/api/suppliers', { headers })

    suppliers.value = response.data.data || []
    console.log('📋 Suppliers loaded:', suppliers.value.length, 'suppliers')
  }
  catch (error: any) {
    console.error('Error loading suppliers:', error)
  }
  finally {
    loadingSuppliers.value = false
  }
}

const loadItems = async () => {
  try {
    loadingItems.value = true

    const token = getAuthToken()

    console.log('Loading items with token:', token ? 'Token found' : 'No token')

    const headers: any = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    }

    if (token)
      headers.Authorization = `Bearer ${token}`

    console.log('Making request to /api/items')

    const response = await axios.get('/api/items', { headers })

    console.log('Items response:', response.data)

    // Handle paginated response structure
    const itemsData = response.data.data
    if (itemsData && itemsData.data)
      items.value = itemsData.data || []
    else
      items.value = response.data.data || []

    console.log('Items loaded:', items.value.length, 'items')
  }
  catch (error: any) {
    console.error('Error loading items:', error)
    console.error('Error response:', error.response?.data)
  }
  finally {
    loadingItems.value = false
  }
}

const calculateItemTotal = (index: number) => {
  try {
    const item = formData.value.items[index]
    if (!item) {
      console.warn('Item not found at index:', index)

      return
    }

    item.total_cost = (item.quantity || 0) * (item.unit_cost || 0)
    console.log(`Item ${index} total calculated:`, item.total_cost)
  }
  catch (error) {
    console.error('Error in calculateItemTotal:', error)
  }
}

const addItem = () => {
  try {
    console.log('Adding new item...')
    console.log('Current items count:', formData.value.items.length)

    const newItem = {
      id_item: null,
      quantity: 1,
      unit_cost: 0,
      unit_cost_display: '0',
      total_cost: 0,
    }

    formData.value.items.push(newItem)
    console.log('Item added successfully. New count:', formData.value.items.length)
  }
  catch (error) {
    console.error('Error in addItem:', error)
  }
}

const removeItem = (index: number) => {
  formData.value.items.splice(index, 1)
}

// VARIANT REMOVED - No longer need to reset variant when item changes
const onItemChange = (index: number, _: any) => {
  // Additional logic can be added here if needed
  calculateItemTotal(index)
}

// VARIANT REMOVED - getProductVariants function no longer needed
// const getProductVariants = (productId) => {
//   if (!productId || !variants.value) return []
//   return variants.value.filter(variant => variant.product_id == productId)
//     .map(variant => ({
//       ...variant,
//       display_name: `${variant.variant_name} - ${variant.sku}`
//     }))
// }

const formatQuantity = (index: number, event: Event) => {
  const value = (event.target as HTMLInputElement)?.value
  const item = formData.value.items[index]
  
  if (value === '' || value === null || value === undefined) {
    item.quantity = 1
  } else {
    item.quantity = Number.parseInt(value) || 1
  }
  
  calculateItemTotal(index)
}

const formatUnitCost = (index: number, event: Event) => {
  const value = (event.target as HTMLInputElement)?.value
  const numericValue = value?.replace(/\D/g, '') || ''
  const item = formData.value.items[index]

  if (numericValue === '') {
    item.unit_cost = 0
    item.unit_cost_display = '0'
  }
  else {
    item.unit_cost = Number.parseInt(numericValue)
    item.unit_cost_display = new Intl.NumberFormat('id-ID').format(item.unit_cost)
  }
}

const formatDiscountAmount = (event: Event) => {
  const value = (event.target as HTMLInputElement)?.value
  const numericValue = value?.replace(/\D/g, '') || ''

  if (numericValue === '')
    formData.value.discount_amount_display = '0'
  else
    formData.value.discount_amount_display = new Intl.NumberFormat('id-ID').format(Number.parseInt(numericValue))
}

const calculateTotals = () => {
  // Totals are automatically calculated via computed property
}

const formatCurrency = (amount: number) => {
  if (Number.isNaN(amount) || amount === null || amount === undefined)
    return 'Rp 0'

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

const initializeForm = () => {
  console.log('🔍 initializeForm called')
  console.log('📝 Mode:', props.mode)
  console.log('💾 Purchase data:', props.purchase)
  
  if (props.mode === 'edit' && props.purchase) {
    console.log('✅ Initializing form for edit mode')
    console.log('� All purchase properties:', Object.keys(props.purchase))
    console.log('�👤 Supplier ID from purchase:', props.purchase.id_supplier)
    console.log('� Supplier object from purchase:', props.purchase.supplier)
    console.log('👤 Supplier_id from purchase:', props.purchase.supplier_id)
    console.log('�📦 Items from purchase:', props.purchase.items)
    
    // Try different possible supplier field names
    const supplierId = props.purchase.supplier_id || 
                      props.purchase.id_supplier || 
                      props.purchase.supplier?.id_supplier ||
                      props.purchase.supplier?.id
    
    console.log('📦 Items from purchase:', props.purchase.items)
    
    // Debug first item structure
    if (props.purchase.items && props.purchase.items.length > 0) {
      const firstItem = props.purchase.items[0]
      console.log('� First item debug:', {
        quantity: firstItem.quantity,
        quantity_ordered: firstItem.quantity_ordered,
        unit_cost: firstItem.unit_cost,
        total_cost: firstItem.total_cost,
        item_id: firstItem.item?.id_item || firstItem.id_item
      })
    }
    
    console.log('🎯 Resolved supplier ID:', supplierId)
    console.log('📅 Original purchase_date:', props.purchase.purchase_date)
    console.log('📅 Purchase_date type:', typeof props.purchase.purchase_date)
    
    const formattedDate = props.purchase.purchase_date ? new Date(props.purchase.purchase_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0]
    console.log('📅 Formatted purchase_date:', formattedDate)
    
    formData.value = {
      id_supplier: supplierId,
      purchase_date: formattedDate,
      notes: props.purchase.notes || '',
      discount_amount: props.purchase.discount_amount || 0,
      discount_amount_display: new Intl.NumberFormat('id-ID').format(props.purchase.discount_amount || 0),
      items: props.purchase.items?.map((item: any) => {
        console.log('🔄 Mapping item:', item)
        console.log('📊 Item quantity_ordered:', item.quantity_ordered)
        console.log('📊 Item quantity:', item.quantity)
        return {
          id_item: item.item?.id_item || item.id_item,
          quantity: Number.parseInt(item.quantity_ordered || item.quantity || 0) || 0,
          unit_cost: item.unit_cost,
          unit_cost_display: new Intl.NumberFormat('id-ID').format(item.unit_cost),
          total_cost: item.total_cost,
        }
      }) || [],
    }
    
    console.log('📋 Form data after initialization:', formData.value)
  }
  else {
    console.log('🆕 Initializing form for create mode')
    formData.value = {
      id_supplier: null,
      purchase_date: new Date().toISOString().split('T')[0],
      notes: '',
      discount_amount: 0,
      discount_amount_display: '0',
      items: [],
    }
  }
}

const savePurchase = async () => {
  if (!form.value?.validate())
    return

  try {
    saving.value = true

    const token = getAuthToken()

    const headers: any = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    }

    if (token)
      headers.Authorization = `Bearer ${token}`

    const payload = {
      supplier_id: formData.value.id_supplier,
      purchase_date: formData.value.purchase_date,
      notes: formData.value.notes,
      discount_amount: Number.parseFloat(formData.value.discount_amount_display.replace(/\D/g, '')) || 0,
      items: formData.value.items.map(item => ({
        id_item: item.id_item,
        quantity: item.quantity,
        unit_cost: item.unit_cost,
      })),
    }

    if (props.mode === 'edit')
      await axios.put(`/api/purchases/${props.purchase.id_purchase}`, payload, { headers })
    else
      await axios.post('/api/purchases', payload, { headers })

    emit('saved')
  }
  catch (error: any) {
    console.error('Error saving purchase:', error)
    console.error('Error saving purchase:', error.response?.data?.message || error.message)

    // You can show a toast notification instead of alert here
  }
  finally {
    saving.value = false
  }
}

const closeDialog = () => {
  localDialog.value = false
}

// Watchers
watch(() => props.modelValue, async (newValue) => {
  if (newValue) {
    // Make sure suppliers and items are loaded first
    await Promise.all([loadSuppliers(), loadItems()])
    await nextTick()
    initializeForm()
  }
})

// Watch for purchase prop changes to update form when editing
watch(() => props.purchase, async (newPurchase) => {
  if (newPurchase && props.mode === 'edit') {
    // Ensure data is loaded before initializing form
    if (suppliers.value.length === 0) {
      await loadSuppliers()
    }
    if (items.value.length === 0) {
      await loadItems()
    }
    await nextTick()
    initializeForm()
  }
}, { deep: true, immediate: true })

// Lifecycle
onMounted(() => {
  loadSuppliers()
  loadItems()
})
</script>

<template>
  <VDialog
    v-model="localDialog"
    max-width="1200"
    persistent
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2">
          <VIcon>mdi-cart-plus</VIcon>
          <span>{{ title }}</span>
        </div>
        <VBtn
          icon="mdi-close"
          size="small"
          variant="text"
          @click="closeDialog"
        />
      </VCardTitle>

      <VCardText>
        <VForm
          ref="form"
          v-model="formValid"
        >
          <VRow>
            <!-- Supplier Selection -->
            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="formData.id_supplier"
                label="Supplier *"
                :items="suppliers"
                item-title="name"
                item-value="id_supplier"
                variant="outlined"
                :rules="[rules.required]"
                :loading="loadingSuppliers"
              >
                <template #item="{ props: itemProps, item }">
                  <VListItem v-bind="itemProps">
                    <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                    <VListItemSubtitle>{{ item.raw.phone }} - {{ item.raw.email }}</VListItemSubtitle>
                  </VListItem>
                </template>
              </VSelect>
            </VCol>

            <!-- Purchase Date -->
            <VCol
              cols="12"
              md="3"
            >
              <VTextField
                v-model="formData.purchase_date"
                label="Tanggal Purchase *"
                type="date"
                variant="outlined"
                :rules="[rules.required]"
              />
            </VCol>

            <!-- Notes -->
            <VCol cols="12">
              <VTextarea
                v-model="formData.notes"
                label="Catatan"
                variant="outlined"
                rows="2"
                placeholder="Catatan tambahan untuk purchase order ini..."
              />
            </VCol>
          </VRow>
        </VForm>

        <!-- Purchase Items -->
        <div class="mt-6">
          <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">
              Items Purchase
            </h3>
            <VBtn
              color="primary"
              variant="tonal"
              @click="addItem"
            >
              <VIcon start>
                mdi-plus
              </VIcon>
              Tambah Item
            </VBtn>
          </div>

          <!-- Items Table -->
          <VCard variant="outlined">
            <VTable>
              <thead>
                <tr>
                  <th style="inline-size: 45%;">
                    Item
                  </th>
                  <!-- VARIANT COLUMN REMOVED -->
                  <th style="inline-size: 15%;">
                    Quantity
                  </th>
                  <th style="inline-size: 15%;">
                    Unit Cost
                  </th>
                  <th style="inline-size: 15%;">
                    Total
                  </th>
                  <th style="inline-size: 10%;">
                    Action
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="formData.items.length === 0">
                  <td
                    colspan="6"
                    class="text-center py-8 text-medium-emphasis"
                  >
                    Belum ada item. Klik "Tambah Item" untuk menambah item purchase.
                  </td>
                </tr>
                <tr
                  v-for="(item, index) in formData.items"
                  :key="index"
                >
                  <td>
                    <VAutocomplete
                      v-model="item.id_item"
                      label="Pilih Item"
                      :items="items"
                      item-title="name"
                      item-value="id_item"
                      variant="outlined"
                      density="compact"
                      :loading="loadingItems"
                      clearable
                      no-data-text="Tidak ada item ditemukan"
                      @update:model-value="(value) => onItemChange(index, value)"
                    >
                      <template #item="{ props: itemProps, item: autocompleteItem }">
                        <VListItem v-bind="itemProps">
                          <VListItemTitle>{{ autocompleteItem.raw.name }}</VListItemTitle>
                          <VListItemSubtitle>{{ autocompleteItem.raw.category?.name }} - SKU: {{ autocompleteItem.raw.sku }}</VListItemSubtitle>
                        </VListItem>
                      </template>
                    </VAutocomplete>
                  </td>
                  <!-- VARIANT COLUMN REMOVED -->
                  <td>
                    <VTextField
                      v-model="item.quantity"
                      label="Qty"
                      type="number"
                      min="1"
                      step="1"
                      variant="outlined"
                      density="compact"
                      @input="formatQuantity(index, $event)"
                      @blur="calculateItemTotal(index)"
                    />
                  </td>
                  <td>
                    <VTextField
                      v-model="item.unit_cost_display"
                      label="Harga Satuan"
                      variant="outlined"
                      density="compact"
                      prefix="Rp"
                      @input="formatUnitCost(index, $event)"
                      @blur="calculateItemTotal(index)"
                    />
                  </td>
                  <td>
                    <span class="font-weight-medium">{{ formatCurrency(item.total_cost) }}</span>
                  </td>
                  <td>
                    <VBtn
                      icon="mdi-delete"
                      size="small"
                      variant="text"
                      color="error"
                      @click="removeItem(index)"
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>

          <!-- Purchase Summary -->
          <VCard
            class="mt-4"
            variant="outlined"
          >
            <VCardText>
              <VRow>
                <VCol
                  cols="12"
                  md="8"
                >
                  <!-- Discount Amount -->
                  <VTextField
                    v-model="formData.discount_amount_display"
                    label="Diskon"
                    variant="outlined"
                    prefix="Rp"
                    placeholder="0"
                    @input="formatDiscountAmount"
                    @blur="calculateTotals"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="4"
                >
                  <div class="text-end">
                    <div class="d-flex justify-space-between mb-2">
                      <span>Subtotal:</span>
                      <span class="font-weight-medium">{{ formatCurrency(totals.subtotal) }}</span>
                    </div>
                    <div class="d-flex justify-space-between mb-2">
                      <span>Diskon:</span>
                      <span class="text-success">-{{ formatCurrency(totals.discount) }}</span>
                    </div>
                    <div class="d-flex justify-space-between mb-2">
                      <span>PPN (11%):</span>
                      <span>{{ formatCurrency(totals.tax) }}</span>
                    </div>
                    <VDivider class="my-2" />
                    <div class="d-flex justify-space-between">
                      <span class="text-h6 font-weight-bold">Total:</span>
                      <span class="text-h6 font-weight-bold text-primary">{{ formatCurrency(totals.total) }}</span>
                    </div>
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </div>
      </VCardText>

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="text"
          @click="closeDialog"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          :loading="saving"
          :disabled="!canSave"
          @click="savePurchase"
        >
          {{ mode === 'edit' ? 'Update' : 'Simpan' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.v-table {
  background-color: transparent;
}
</style>
