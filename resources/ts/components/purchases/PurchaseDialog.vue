<script setup lang="ts">
import { getAuthToken } from '@/utils/auth'
import axios from 'axios'
import { computed, nextTick, onMounted, ref, watch } from 'vue'

// Interfaces
interface Item {
  id_item: number | null
  quantity: number
  unit: string // Unit dari item yang dipilih
  unit_cost: number
  unit_cost_display: string
  total_cost: number
  total_cost_display: string // Input field untuk total cost
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
  const subtotal = formData.value.items.reduce((sum, item) => {
    return sum + (item.total_cost || 0)
  }, 0)
  const discount = Number.parseFloat(String(formData.value.discount_amount_display).replace(/\D/g, '')) || 0
  const tax = 0 //(subtotal - discount) * 0.11 // 11% PPN
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
         && (formData.value.items.length === 0 || 
             formData.value.items.every(item =>
               item.id_item && item.quantity > 0 && item.unit_cost > 0,
             ))
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


    const headers: any = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    }

    if (token)
      headers.Authorization = `Bearer ${token}`


    const response = await axios.get('/api/items?per_page=all', { headers })


    // Handle paginated response structure
    const itemsData = response.data.data
    if (itemsData && itemsData.data)
      items.value = itemsData.data || []
    else
      items.value = response.data.data || []

  }
  catch (error: any) {
    console.error('Error loading items:', error)
    console.error('Error response:', error.response?.data)
  }
  finally {
    loadingItems.value = false
  }
}

const calculateUnitCost = (index: number) => {
  try {
    const item = formData.value.items[index]
    if (!item) {
      console.warn('Item not found at index:', index)
      return
    }

    // Calculate unit cost from total cost ÷ quantity
    if (item.quantity > 0 && item.total_cost > 0) {
      item.unit_cost = item.total_cost / item.quantity
      item.unit_cost_display = formatCurrency(item.unit_cost).replace('Rp ', '').replace(/\./g, '')
    } else {
      item.unit_cost = 0
      item.unit_cost_display = '0'
    }
    
  }
  catch (error) {
    console.error('Error in calculateUnitCost:', error)
  }
}

const calculateTotalCost = (index: number) => {
  try {
    const item = formData.value.items[index]
    if (!item) {
      console.warn('Item not found at index:', index)
      return
    }

    // Calculate total cost from quantity × unit cost
    if (item.quantity > 0 && item.unit_cost > 0) {
      item.total_cost = item.quantity * item.unit_cost
      item.total_cost_display = new Intl.NumberFormat('id-ID').format(item.total_cost)
    } else {
      item.total_cost = 0
      item.total_cost_display = '0'
    }
    
  }
  catch (error) {
    console.error('Error in calculateTotalCost:', error)
  }
}

const onItemChange = (index: number, itemId: number | null) => {
  try {
    const item = formData.value.items[index]
    if (!item || !itemId) return

    // Find selected item to get unit
    const selectedItem = items.value.find(i => i.id_item === itemId)
    if (selectedItem) {
      item.unit = selectedItem.unit || ''
    }
  }
  catch (error) {
    console.error('Error in onItemChange:', error)
  }
}

const addItem = () => {
  try {

    const newItem = {
      id_item: null,
      quantity: 0,
      unit: '',
      unit_cost: 0,
      unit_cost_display: '0',
      total_cost: 0,
      total_cost_display: '0',
    }

    formData.value.items.push(newItem)
  }
  catch (error) {
    console.error('Error in addItem:', error)
  }
}

const removeItem = (index: number) => {
  formData.value.items.splice(index, 1)
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
    item.quantity = 0
  } else {
    item.quantity = Number.parseFloat(value) || 1
  }
  
  calculateTotalCost(index)
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
    item.unit_cost = Number.parseFloat(numericValue)
    item.unit_cost_display = new Intl.NumberFormat('id-ID').format(item.unit_cost)
  }
  
  calculateTotalCost(index)
}

const formatTotalCost = (index: number, event: Event) => {
  const value = (event.target as HTMLInputElement)?.value
  const numericValue = value?.replace(/\D/g, '') || ''
  const item = formData.value.items[index]

  if (numericValue === '') {
    item.total_cost = 0
    item.total_cost_display = '0'
  }
  else {
    item.total_cost = Number.parseFloat(numericValue)
    item.total_cost_display = new Intl.NumberFormat('id-ID').format(item.total_cost)
  }
  
  calculateUnitCost(index)
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
  
  if (props.mode === 'edit' && props.purchase) {
    
    // Try different possible supplier field names
    const supplierId = props.purchase.supplier_id || 
                      props.purchase.id_supplier || 
                      props.purchase.supplier?.id_supplier ||
                      props.purchase.supplier?.id
    
    
    // Convert to number to match with VSelect item-value
    const supplierIdNumber = supplierId ? Number.parseInt(String(supplierId)) : null
    
    
    const formattedDate = props.purchase.purchase_date ? new Date(props.purchase.purchase_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0]
    
    formData.value = {
      id_supplier: supplierIdNumber,
      purchase_date: formattedDate,
      notes: props.purchase.notes || '',
      discount_amount: props.purchase.discount_amount || 0,
      discount_amount_display: new Intl.NumberFormat('id-ID').format(props.purchase.discount_amount || 0),
      items: props.purchase.items?.map((item: any) => {
        
        const quantity = Number.parseFloat(item.quantity_ordered || item.quantity || 0) || 0
        const unitCost = Number.parseFloat(item.unit_cost || 0) || 0
        const totalCost = quantity * unitCost
        
        return {
          id_item: item.item?.id_item || item.id_item,
          quantity: quantity,
          unit: item.item?.unit || item.unit || '',
          unit_cost: unitCost,
          unit_cost_display: new Intl.NumberFormat('id-ID').format(unitCost),
          total_cost: totalCost,
          total_cost_display: new Intl.NumberFormat('id-ID').format(totalCost),
        }
      }) || [],
    }
    
  }
  else {
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

// Method to update units for existing items after items data is loaded
const updateItemUnits = () => {
  formData.value.items.forEach((formItem, index) => {
    if (formItem.id_item && !formItem.unit) {
      const masterItem = items.value.find(item => item.id_item === formItem.id_item)
      if (masterItem) {
        formItem.unit = masterItem.unit
      }
    }
  })
}

// Watchers
watch(() => props.modelValue, async (newValue) => {
  if (newValue) {
    // Make sure suppliers and items are loaded first
    await Promise.all([loadSuppliers(), loadItems()])
    await nextTick()
    initializeForm()
    // Update units after initialization
    updateItemUnits()
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
    // Update units after initialization
    updateItemUnits()
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
          <div class="d-flex flex-column">
            <span>{{ title }}</span>
            <span v-if="props.mode === 'edit' && props.purchase?.purchase_number" class="text-caption text-medium-emphasis">
              No. Order: {{ props.purchase.purchase_number }}
            </span>
          </div>
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
                  <th style="inline-size: 35%;">
                    Item
                  </th>
                  <th style="inline-size: 12%;">
                    Quantity
                  </th>
                  <th style="inline-size: 10%;">
                    Unit
                  </th>
                  <th style="inline-size: 15%;">
                    Total Harga
                  </th>
                  <th style="inline-size: 15%;">
                    Harga Satuan
                  </th>
                  <th style="inline-size: 13%;">
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
                      @blur="calculateUnitCost(index)"
                    />
                  </td>
                  <td>
                    <div class="text-center font-weight-medium">
                      {{ item.unit || '-' }}
                    </div>
                  </td>
                  <td>
                    <VTextField
                      v-model="item.total_cost_display"
                      label="Total Harga"
                      variant="outlined"
                      density="compact"
                      prefix="Rp"
                      @input="formatTotalCost(index, $event)"
                      @blur="calculateUnitCost(index)"
                    />
                  </td>
                  <td>
                    <div class="text-center font-weight-medium">
                      {{ formatCurrency(item.unit_cost) }}
                    </div>
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
                    <!-- <div class="d-flex justify-space-between mb-2">
                      <span>PPN (11%):</span>
                      <span>{{ formatCurrency(totals.tax) }}</span>
                    </div> -->
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
