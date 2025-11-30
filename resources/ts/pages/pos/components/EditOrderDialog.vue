<template>
  <VDialog
    v-model="localDialog"
    max-width="900"
    scrollable
    persistent
  >
    <VCard class="coffee-dialog">
      <VCardTitle class="d-flex align-center justify-space-between coffee-header">
        <div class="d-flex align-items-center gap-2">
          <VIcon
            icon="tabler-edit"
            class="text-white"
          />
          <div>
            <div class="coffee-title">Edit Transaksi</div>
            <div class="coffee-subtitle">{{ order?.order_number }}</div>
          </div>
        </div>
        <VBtn
          icon="tabler-x"
          variant="text"
          color="white"
          @click="closeDialog"
        />
      </VCardTitle>

      <VCardText class="pa-6">
        <VForm
          ref="formRef"
          v-model="formValid"
          @submit.prevent="saveOrder"
        >
          <VRow>
            <!-- Order Information -->
            <VCol cols="12">
              <h4 class="text-h6 mb-4">Informasi Pesanan</h4>
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="formData.order_type"
                label="Tipe Pesanan"
                :items="orderTypeOptions"
                variant="outlined"
                :rules="[rules.required]"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="formData.table_number"
                label="Nomor Meja"
                variant="outlined"
                clearable
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model.number="formData.guest_count"
                label="Jumlah Tamu"
                type="number"
                variant="outlined"
                min="1"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="formData.id_customer"
                label="Pelanggan"
                :items="customers"
                item-title="name"
                item-value="id_customer"
                variant="outlined"
                clearable
                prepend-inner-icon="tabler-user"
              />
            </VCol>

            <!-- Order Items -->
            <VCol cols="12">
              <h4 class="text-h6 mb-4 mt-4">Item Pesanan</h4>
            </VCol>

            <VCol cols="12">
              <VCard variant="outlined">
                <VCardText>
                  <!-- Add Item Button -->
                  <VBtn
                    color="primary"
                    variant="outlined"
                    prepend-icon="tabler-plus"
                    class="mb-4"
                    @click="addNewItem"
                  >
                    Tambah Item
                  </VBtn>

                  <!-- Items List -->
                  <div
                    v-if="formData.items.length > 0"
                    class="space-y-4"
                  >
                    <VCard
                      v-for="(item, index) in formData.items"
                      :key="index"
                      variant="outlined"
                      class="mb-3"
                    >
                      <VCardText>
                        <VRow align="center">
                          <VCol
                            cols="12"
                            md="4"
                          >
                            <VSelect
                              v-model="item.id_product"
                              label="Produk"
                              :items="products"
                              item-title="name"
                              item-value="id_product"
                              variant="outlined"
                              density="compact"
                              :rules="[rules.required]"
                              @update:model-value="updateItemPrice(index)"
                            />
                          </VCol>
                          <VCol
                            cols="12"
                            md="2"
                          >
                            <VTextField
                              v-model.number="item.quantity"
                              label="Qty"
                              type="number"
                              variant="outlined"
                              density="compact"
                              min="1"
                              :rules="[rules.required, rules.minOne]"
                              @update:model-value="calculateItemTotal(index)"
                            />
                          </VCol>
                          <VCol
                            cols="12"
                            md="2"
                          >
                            <VTextField
                              v-model.number="item.unit_price"
                              label="Harga"
                              type="number"
                              variant="outlined"
                              density="compact"
                              min="0"
                              :rules="[rules.required, rules.minZero]"
                              @update:model-value="calculateItemTotal(index)"
                            />
                          </VCol>
                          <VCol
                            cols="12"
                            md="2"
                          >
                            <VTextField
                              :model-value="formatCurrency(item.quantity * item.unit_price)"
                              label="Total"
                              variant="outlined"
                              density="compact"
                              readonly
                            />
                          </VCol>
                          <VCol
                            cols="12"
                            md="2"
                          >
                            <VBtn
                              icon="tabler-trash"
                              variant="text"
                              color="error"
                              size="small"
                              @click="removeItem(index)"
                            />
                          </VCol>
                        </VRow>
                        <VRow>
                          <VCol cols="12">
                            <VTextField
                              v-model="item.notes"
                              label="Catatan Item"
                              variant="outlined"
                              density="compact"
                            />
                          </VCol>
                        </VRow>
                      </VCardText>
                    </VCard>
                  </div>

                  <VAlert
                    v-else
                    type="info"
                    variant="tonal"
                    class="mb-0"
                  >
                    Belum ada item dalam pesanan
                  </VAlert>
                </VCardText>
              </VCard>
            </VCol>

            <!-- Discount Section -->
            <VCol cols="12">
              <h4 class="text-h6 mb-4 mt-4">Diskon</h4>
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="formData.discount_type"
                label="Tipe Diskon"
                :items="discountTypeOptions"
                variant="outlined"
                clearable
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model.number="formData.discount_value"
                label="Nilai Diskon"
                type="number"
                variant="outlined"
                min="0"
                :suffix="formData.discount_type === 'percentage' ? '%' : 'Rp'"
              />
            </VCol>

            <!-- Notes -->
            <VCol cols="12">
              <VTextarea
                v-model="formData.notes"
                label="Catatan Pesanan"
                variant="outlined"
                rows="3"
              />
            </VCol>

            <!-- Order Summary -->
            <VCol cols="12">
              <VCard
                variant="tonal"
                color="primary"
              >
                <VCardText>
                  <h4 class="text-h6 mb-3">Ringkasan Pesanan</h4>
                  <div class="d-flex justify-space-between mb-2">
                    <span>Subtotal:</span>
                    <span class="font-weight-medium">{{ formatCurrency(orderSummary.subtotal) }}</span>
                  </div>
                  <div
                    v-if="orderSummary.discount > 0"
                    class="d-flex justify-space-between mb-2"
                  >
                    <span>Diskon:</span>
                    <span class="font-weight-medium text-error">-{{ formatCurrency(orderSummary.discount) }}</span>
                  </div>
                  <VDivider class="my-2" />
                  <div class="d-flex justify-space-between">
                    <span class="font-weight-bold">Total:</span>
                    <span class="font-weight-bold text-h6">{{ formatCurrency(orderSummary.total) }}</span>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <!-- Error Alert di Bottom - lebih visible -->
      <div 
        v-if="errorMessage" 
        class="px-6 pb-2"
      >
        <VAlert
          type="error"
          variant="tonal"
          closable
          @click:close="errorMessage = ''"
        >
          {{ errorMessage }}
        </VAlert>
      </div>

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
          :loading="saving"
          :disabled="!formValid || formData.items.length === 0"
          @click="saveOrder"
        >
          Simpan Perubahan
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { CustomerApi } from '@/utils/api/CustomerApi';
import { PosApi } from '@/utils/api/PosApi';
import { computed, onMounted, ref, watch } from 'vue';

// Props
interface Props {
  modelValue: boolean
  order: any
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'order-updated': [order: any]
}>()

// Reactive data
const formRef = ref()
const formValid = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const customers = ref<any[]>([])
const products = ref<any[]>([])

// Form data
const formData = ref({
  order_type: '',
  table_number: '',
  guest_count: 1,
  id_customer: null,
  notes: '',
  discount_type: '',
  discount_value: 0,
  items: [] as any[]
})

// Options
const orderTypeOptions = [
  { title: 'Makan di Tempat', value: 'dine_in' },
  { title: 'Bawa Pulang', value: 'takeaway' },
  { title: 'Antar', value: 'delivery' }
]

const discountTypeOptions = [
  { title: 'Persentase', value: 'percentage' },
  { title: 'Nominal', value: 'fixed' }
]

// Validation rules
const rules = {
  required: (value: any) => !!value || 'Field ini wajib diisi',
  minOne: (value: number) => value >= 1 || 'Minimal 1',
  minZero: (value: number) => value >= 0 || 'Minimal 0'
}

// Computed
const localDialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const orderSummary = computed(() => {
  const subtotal = formData.value.items.reduce((sum, item) => {
    return sum + (item.quantity * item.unit_price)
  }, 0)

  let discount = 0
  if (formData.value.discount_type && formData.value.discount_value > 0) {
    if (formData.value.discount_type === 'percentage') {
      discount = (subtotal * formData.value.discount_value) / 100
    } else {
      discount = formData.value.discount_value
    }
  }

  return {
    subtotal,
    discount,
    total: subtotal - discount
  }
})

// Methods
const loadCustomers = async () => {
  try {
    const response = await CustomerApi.getCustomers()
    if (response.success) {
      customers.value = response.data
    }
  } catch (error) {
    console.error('Error loading customers:', error)
  }
}

const loadProducts = async () => {
  try {
    const response = await PosApi.getProducts()
    if (response.success) {
      products.value = response.data
    }
  } catch (error) {
    console.error('Error loading products:', error)
  }
}

const initializeForm = () => {
  if (props.order) {
    // Support both possible naming conventions
    const orderItems = props.order.orderItems || props.order.order_items || []
    
    formData.value = {
      order_type: props.order.order_type || '',
      table_number: props.order.table_number || '',
      guest_count: props.order.guest_count || 1,
      id_customer: props.order.id_customer || null,
      notes: props.order.notes || '',
      discount_type: props.order.discount_type || '',
      discount_value: props.order.discount_amount || 0,
      items: orderItems.map((item: any) => ({
        id_product: item.id_product,
        item_type: 'product',
        quantity: item.quantity,
        unit_price: item.unit_price,
        notes: item.notes || ''
      }))
    }
  }
}

const addNewItem = () => {
  formData.value.items.push({
    id_product: null,
    item_type: 'product',
    quantity: 1,
    unit_price: 0,
    notes: ''
  })
}

const removeItem = (index: number) => {
  formData.value.items.splice(index, 1)
}

const updateItemPrice = (index: number) => {
  const item = formData.value.items[index]
  const product = products.value.find(p => p.id_product === item.id_product)
  if (product && item.unit_price === 0) {
    item.unit_price = product.price || 0
  }
}

const calculateItemTotal = (index: number) => {
  // This is handled by the computed total in the template
}

const saveOrder = async () => {
  if (!formRef.value?.validate()) return

  try {
    saving.value = true
    errorMessage.value = '' // Clear previous error
    
    const updateData = {
      order_type: formData.value.order_type,
      table_number: formData.value.table_number,
      guest_count: formData.value.guest_count,
      id_customer: formData.value.id_customer,
      notes: formData.value.notes,
      discount_type: formData.value.discount_type,
      discount_value: formData.value.discount_value,
      items: formData.value.items.filter(item => item.id_product)
    }

    const response = await PosApi.editOrder(props.order.id_order, updateData)
    
    if (response.success) {
      emit('order-updated', response.data)
      closeDialog()
      // Show success message
    }
  } catch (error: any) {
    console.error('Error updating order:', error)
    // Extract error message from API response
    if (error.message) {
      errorMessage.value = error.message
    } else if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message
    } else {
      errorMessage.value = 'Terjadi kesalahan saat memperbarui pesanan'
    }
  } finally {
    saving.value = false
  }
}

const closeDialog = () => {
  errorMessage.value = '' // Clear error when closing dialog
  localDialog.value = false
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount || 0)
}

// Lifecycle
onMounted(() => {
  loadCustomers()
  loadProducts()
})

// Watch for dialog changes
watch(localDialog, (newValue) => {
  if (newValue && props.order) {
    initializeForm()
  }
})

watch(() => props.order, (newOrder) => {
  if (newOrder && localDialog.value) {
    initializeForm()
  }
})
</script>

<style scoped>
.coffee-dialog {
  border-radius: 16px;
}

.coffee-header {
  background: linear-gradient(135deg, #6f4e37 0%, #8b4513 100%);
  color: white;
  padding-block: 20px;
  padding-inline: 24px;
}

.coffee-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
}

.coffee-subtitle {
  margin: 0;
  font-size: 1rem;
  font-weight: 500;
  line-height: 1.2;
  opacity: 0.9;
}

.space-y-4 > * + * {
  margin-block-start: 1rem;
}
</style>
