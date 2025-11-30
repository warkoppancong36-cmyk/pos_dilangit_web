<template>
  <VDialog
    v-model="localDialog"
    max-width="1200"
    max-height="90vh"
    persistent
    scrollable
    class="payment-dialog"
    @keydown.esc="closeDialog"
  >
    <VCard class="coffee-dialog">
      <!-- Header -->
      <VCardTitle class="d-flex align-center justify-space-between coffee-header">
        <div class="d-flex align-items-center gap-2">
          <VIcon
            icon="mdi-cash-register"
            class="text-white"
          />
          <div>
            <div class="coffee-title">Pembayaran</div>
            <div class="coffee-subtitle">Proses pembayaran pesanan</div>
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
        <VRow>
          <!-- Left Column - Order Summary & Payment Methods -->
          <VCol cols="12" md="6">
            <!-- Order Summary Card -->
            <VCard class="order-summary-card mb-4" variant="tonal" color="grey-lighten-4">
          <VCardTitle class="text-h6 pb-2">
            <VIcon icon="mdi-receipt" size="20" class="me-2" />
            Ringkasan Pesanan
            <VChip
              size="small"
              color="primary"
              variant="tonal"
              class="ml-2"
            >
              {{ getOrderTypeText(selectedOrderType) }}
            </VChip>
          </VCardTitle>
          
          <VCardText class="pt-0">
            <div class="order-items mb-4">
              <div
                v-for="item in cartItems"
                :key="item.item_type === 'package' ? `pkg-${item.id_package}` : `prd-${item.id_product}`"
                class="order-item d-flex justify-space-between align-center py-2"
              >
                <div class="item-info">
                  <div class="item-name text-body-1 font-weight-medium">
                    {{ item.name }}
                    <VChip
                      v-if="item.item_type === 'package'"
                      size="x-small"
                      color="success"
                      variant="tonal"
                      class="ml-1"
                    >
                      Paket
                    </VChip>
                  </div>
                  <div class="item-quantity text-caption text-medium-emphasis">
                    {{ item.quantity }} × {{ formatCurrency(item.item_type === 'package' ? (item.package_price || 0) : (item.selling_price || 0)) }}
                  </div>
                </div>
                <div class="item-total text-body-1 font-weight-medium">
                  {{ formatCurrency((item.item_type === 'package' ? (item.package_price || 0) : (item.selling_price || 0)) * item.quantity) }}
                </div>
              </div>
            </div>
            
            <VDivider class="my-3" />
            
            <div class="order-totals">
              <div class="total-row d-flex justify-space-between align-center py-1">
                <span class="text-body-2">Subtotal:</span>
                <span class="text-body-2">{{ formatCurrency(subtotal) }}</span>
              </div>
              
              <!-- Promotion Discount -->
              <div v-if="promotionDiscountAmount > 0" class="total-row d-flex justify-space-between align-center py-1">
                <span class="text-body-2 text-purple">
                  <VIcon icon="mdi-speakerphone" size="16" class="me-1" />
                  Promosi ({{ appliedPromotions.length }}):
                </span>
                <span class="text-body-2 text-purple">-{{ formatCurrency(promotionDiscountAmount) }}</span>
              </div>
              
              <!-- Code Discount -->
                            <div v-if="codeDiscountAmount > 0" class="total-row d-flex justify-space-between align-center py-1">
                <span class="text-body-2 text-success">
                  <VIcon icon="mdi-tag" size="16" class="me-1" />
                  Diskon Kode:
                </span>
                <span class="text-body-2 text-success">-{{ formatCurrency(codeDiscountAmount) }}</span>
              </div>
              
              <!-- Total Discount (if multiple discounts) -->
              <div v-if="discountValue > 0 && (promotionDiscountAmount + codeDiscountAmount) > 0" class="total-row d-flex justify-space-between align-center py-1">
                <span class="text-body-2 font-weight-medium text-success">
                  <VIcon icon="mdi-calculator" size="16" class="me-1" />
                  Total Diskon:
                </span>
                <span class="text-body-2 font-weight-medium text-success">-{{ formatCurrency(discountValue) }}</span>
              </div>
              
              <!-- Tax -->
              <div v-if="taxAmount > 0" class="total-row d-flex justify-space-between align-center py-1">
                <span class="text-body-2 text-orange">
                  <VIcon icon="mdi-percent" size="16" class="me-1" />
                  Pajak ({{ taxPercentage }}%):
                </span>
                <span class="text-body-2 text-orange">{{ formatCurrency(taxAmount) }}</span>
              </div>
              
              <VDivider class="my-3" />
              
              <div class="final-total d-flex justify-space-between align-center py-2">
                <span class="text-h6 font-weight-bold">Total Bayar:</span>
                <span class="text-h5 font-weight-bold text-primary">
                  {{ formatCurrency(finalTotal) }}
                </span>
              </div>
            </div>
          </VCardText>
        </VCard>
        
        <!-- Customer & Order Type Selection -->
        <VCard class="customer-order-card mb-6" variant="tonal" color="info">
          <VCardTitle class="text-h6 pb-2">
            <VIcon icon="mdi-account-details" size="20" class="me-2" />
            Detail Pesanan
          </VCardTitle>
          
          <VCardText class="pt-0">
            <VRow class="mb-4">
              <VCol cols="12">
                <VSelect
                  v-model="selectedCustomer"
                  :items="props.customers"
                  item-title="name"
                  item-value="id_customer"
                  label="Pilih Pelanggan (Opsional)"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="mdi-account"
                  clearable
                  class="customer-select"
                />
              </VCol>
            </VRow>
            
            <!-- Order Type Selection -->
            <div class="order-type-section">
              <h3 class="payment-section-title mb-4">Tipe Pesanan</h3>
              <VBtnToggle
                v-model="selectedOrderType"
                color="primary"
                variant="outlined"
                mandatory
                class="order-type-toggle"
              >
              
                <VBtn
                  value="dine_in"
                  class="order-type-btn"
                  :class="{ 'active': selectedOrderType === 'dine_in' }"
                >
                  <VIcon start>mdi-silverware-fork-knife</VIcon>
                  Dine In
                </VBtn>
                  <VBtn
                  value="takeaway"
                  class="order-type-btn"
                  :class="{ 'active': selectedOrderType === 'takeaway' }"
                >
                  <VIcon start>mdi-shopping</VIcon>
                  Takeaway
                </VBtn>
                <VBtn
                  value="delivery"
                  class="order-type-btn"
                  :class="{ 'active': selectedOrderType === 'delivery' }"
                >
                  <VIcon start>mdi-truck-delivery</VIcon>
                  Delivery
                </VBtn>
              </VBtnToggle>
            </div>
          </VCardText>
        </VCard>
        
        <!-- Payment Method Selection -->
        <div class="payment-methods-section mb-6">
          <h3 class="payment-section-title mb-4">Pilih Metode Pembayaran</h3>
          
          <VRadioGroup v-model="selectedPaymentMethod" class="payment-methods-horizontal">
            <div class="payment-methods-row">
              <VCard
                v-for="method in paymentMethods"
                :key="method.value"
                class="payment-method-card"
                :class="{ 'selected': selectedPaymentMethod === method.value }"
                @click="selectedPaymentMethod = method.value"
              >
                <VCardText class="payment-method-content">
                  <div class="payment-icon-wrapper">
                    <div 
                      class="payment-icon"
                      :class="{ 'active': selectedPaymentMethod === method.value }"
                    >
                      <VIcon 
                        :icon="method.icon" 
                        size="24"
                        :color="getMethodColor(method.value, selectedPaymentMethod === method.value)"
                      />
                    </div>
                  </div>
                  <div class="payment-method-title">{{ method.title }}</div>
                  <VRadio
                    :value="method.value"
                    class="payment-radio"
                    hide-details
                  />
                </VCardText>
              </VCard>
            </div>
          </VRadioGroup>
        </div>
        </VCol>
        
        <!-- Right Column - Payment Details -->
        <VCol cols="12" md="6">
        
        <!-- Payment Amount -->
        <VCard class="payment-amount-card mb-6" variant="tonal" color="success">
          <VCardTitle class="text-h6 pb-2">
            <VIcon icon="mdi-calculator" size="20" class="me-2" />
            Jumlah Pembayaran
          </VCardTitle>
          
          <VCardText class="pt-0">
            <VRow class="mb-4">
              <VCol cols="12" md="6">
                <VTextField
                  v-model="paidAmountDisplay"
                  type="text"
                  label="Jumlah Bayar"
                  variant="outlined"
                  prefix="Rp"
                  class="payment-input"
                  placeholder="0"
                  @input="handlePaidAmountInput"
                  :error="selectedPaymentMethod === 'cash' && paidAmount < finalTotal"
                  :error-messages="selectedPaymentMethod === 'cash' && paidAmount < finalTotal ? 'Jumlah bayar kurang dari total' : ''"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  :model-value="changeAmount >= 0 ? formatCurrency(changeAmount) : `- ${formatCurrency(Math.abs(changeAmount))}`"
                  label="Kembalian"
                  variant="outlined"
                  readonly
                  class="change-input"
                  :color="changeAmount >= 0 ? 'success' : 'error'"
                />
              </VCol>
            </VRow>
            
            <!-- Quick Amount Buttons -->
            <div class="quick-amounts">
              <div class="quick-amounts-label text-caption mb-2 text-medium-emphasis">
                Pilih nominal cepat:
              </div>
              <div class="quick-buttons">
                <VBtn
                  v-for="amount in quickAmounts"
                  :key="amount"
                  size="small"
                  variant="outlined"
                  color="primary"
                  class="quick-btn"
                  @click="setQuickAmount(amount)"
                >
                  {{ formatCurrency(amount) }}
                </VBtn>
                <VBtn
                  size="small"
                  variant="tonal"
                  color="success"
                  class="quick-btn"
                  @click="setExactAmount"
                >
                  <VIcon icon="mdi-check-circle" size="14" class="me-1" />
                  Pas
                </VBtn>
              </div>
            </div>
          </VCardText>
        </VCard>
        
        <!-- Promotion Suggestions -->
        <PromotionSuggestions
          :cart-items="cartItems"
          :subtotal="subtotal"
          @promotions-applied="handlePromotionsApplied"
          @promotions-removed="handlePromotionsRemoved"
        />

        <!-- Discount Code Section -->
        <DiscountCodeInput
          :subtotal="subtotal"
          :quick-codes="['DISC10', 'SAVE20', 'WELCOME']"
          @discount-applied="handleDiscountApplied"
          @discount-removed="handleDiscountRemoved"
        />

        <!-- Tax Section -->
        <VCard class="tax-card mb-6" variant="tonal" color="orange-lighten-5">
          <VCardTitle class="text-h6 pb-2">
            <VIcon icon="mdi-percent" size="20" class="me-2" />
            Pajak
          </VCardTitle>
          <VCardText class="pt-0">
            <VRow>
              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="taxPercentage"
                  label="Persentase Pajak (%)"
                  type="number"
                  variant="outlined"
                  min="0"
                  max="100"
                  step="0.1"
                  suffix="%"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  :model-value="formatCurrency(taxAmount)"
                  label="Jumlah Pajak"
                  variant="outlined"
                  readonly
                  prepend-inner-icon="mdi-currency-usd"
                />
              </VCol>
            </VRow>
            <div class="text-caption text-medium-emphasis">
              <VIcon icon="mdi-information" size="16" class="me-1" />
              Pajak dihitung setelah diskon diterapkan
            </div>
          </VCardText>
        </VCard>

        <!-- Reference Number (for non-cash payments) -->
        <VCard 
          v-if="selectedPaymentMethod !== 'cash'" 
          class="reference-card mb-6" 
          variant="tonal" 
          color="info"
        >
          <VCardTitle class="text-h6 pb-2">
            <VIcon icon="mdi-card-account-details" size="20" class="me-2" />
            Informasi Transaksi
          </VCardTitle>
          <VCardText class="pt-0">
            <VTextField
              v-model="referenceNumber"
              label="Nomor Referensi/Transaksi"
              variant="outlined"
              placeholder="Contoh: TXN123456789"
              prepend-inner-icon="mdi-card-text"
            />
          </VCardText>
        </VCard>
        
        <!-- Notes -->
        <VCard class="notes-card mb-6" variant="tonal" color="grey-lighten-3">
          <VCardTitle class="text-h6 pb-2">
            <VIcon icon="mdi-note-text" size="20" class="me-2" />
            Catatan Transaksi
          </VCardTitle>
          <VCardText class="pt-0">
            <VTextarea
              v-model="notes"
              label="Catatan (Opsional)"
              variant="outlined"
              rows="3"
              placeholder="Catatan tambahan untuk transaksi ini..."
              prepend-inner-icon="mdi-pencil"
            />
          </VCardText>
        </VCard>
        </VCol>
        </VRow>
      </VCardText>
      
      <!-- Actions -->
      <VCardActions class="payment-actions pa-6">
        <VBtn
          variant="outlined"
          size="large"
          prepend-icon="mdi-close"
          @click="closeDialog"
        >
          Batal
        </VBtn>
        
        <VSpacer />
        
        <VBtn
          color="success"
          variant="flat"
          size="large"
          :disabled="!canProcess || processing"
          :loading="processing"
          prepend-icon="mdi-check-circle"
          class="process-payment-btn"
          @click="showConfirmation"
        >
          {{ processing ? 'Memproses...' : 'Proses Pembayaran' }}
        </VBtn>
      </VCardActions>
    </VCard>
    
    <!-- Print Receipt Dialog -->
    <VDialog v-model="printDialog" max-width="400">
      <VCard>
        <VCardTitle>Cetak Struk</VCardTitle>
        <VCardText>
          <p class="text-body-1 mb-4">Pembayaran berhasil! Apakah Anda ingin mencetak struk?</p>
          
          <div class="d-flex gap-2">
            <VBtn
              color="primary"
              variant="flat"
              @click="printReceipt"
            >
              Cetak Struk
            </VBtn>
            <VBtn
              variant="outlined"
              @click="skipPrint"
            >
              Lewati
            </VBtn>
          </div>
        </VCardText>
      </VCard>
    </VDialog>
    
    <!-- Confirmation Dialog -->
    <VDialog v-model="confirmationDialog" max-width="400" persistent>
      <VCard>
        <VCardTitle class="d-flex align-center">
          <VIcon icon="mdi-help-circle" color="warning" class="me-2" />
          Konfirmasi Pembayaran
        </VCardTitle>
        <VCardText>
          <p class="text-body-1 mb-4">Apakah Anda yakin ingin memproses pembayaran ini?</p>
          
          <div class="confirmation-details">
            <div class="d-flex justify-space-between mb-2">
              <span>Metode Pembayaran:</span>
              <span class="font-weight-medium">{{ getPaymentMethodText(selectedPaymentMethod) }}</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span>Total Bayar:</span>
              <span class="font-weight-medium text-primary">{{ formatCurrency(finalTotal) }}</span>
            </div>
            <div v-if="selectedPaymentMethod === 'cash'" class="d-flex justify-space-between mb-2">
              <span>Jumlah Bayar:</span>
              <span class="font-weight-medium">{{ formatCurrency(paidAmount) }}</span>
            </div>
            <div v-if="selectedPaymentMethod === 'cash' && changeAmount > 0" class="d-flex justify-space-between">
              <span>Kembalian:</span>
              <span class="font-weight-medium text-success">{{ formatCurrency(changeAmount) }}</span>
            </div>
          </div>
        </VCardText>
        <VCardActions>
          <VBtn
            variant="outlined"
            @click="confirmationDialog = false"
          >
            Batal
          </VBtn>
          <VSpacer />
          <VBtn
            color="success"
            variant="flat"
            :loading="processing"
            @click="confirmPayment"
          >
            Ya, Proses
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VDialog>

  <!-- Error Snackbar -->
  <VSnackbar
    v-model="snackbar.show"
    :color="snackbar.color"
    :timeout="4000"
    location="top right"
  >
    {{ snackbar.message }}
  </VSnackbar>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import { computed, ref, watch } from 'vue'
import DiscountCodeInput from './DiscountCodeInput.vue'
import PromotionSuggestions from './PromotionSuggestions.vue'

// Type definitions
interface Discount {
  id: number
  name: string
  code: string
  type: 'percentage' | 'amount'
  value: number
  description?: string
  minimum_amount: number
  maximum_discount: number
  usage_limit: number | null
  used_count: number
  valid_until: string | null
  is_active: boolean
}

interface Promotion {
  id: number
  name: string
  description: string
  type: 'happy_hour' | 'bogo' | 'combo_deal' | 'category_discount' | 'quantity_discount'
  discount_type: 'percentage' | 'amount'
  discount_value: number
  minimum_amount?: number
  maximum_discount?: number
  valid_from: string
  valid_until: string | null
  valid_days: string | null
  valid_hours: string | null
  priority: number
  can_be_combined: boolean
  is_active: boolean
  calculated_discount?: number
}

// Store
const authStore = useAuthStore()

// Props
interface Props {
  modelValue: boolean
  cartItems: any[]
  customers: any[]
  discountAmount?: number
  discountType?: string
}

const props = withDefaults(defineProps<Props>(), {
  discountAmount: 0,
  discountType: 'amount'
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'payment-success': [data: { 
    paymentData: any
    transactionData?: any
    receiptData?: any
  }]
  'clear-cart': []
}>()

// Reactive data
const processing = ref(false)
const printDialog = ref(false)
const confirmationDialog = ref(false)
const selectedPaymentMethod = ref('cash')
const paidAmount = ref(0)
const paidAmountDisplay = ref('')
const referenceNumber = ref('')
const notes = ref('')
const processedOrder = ref<any>(null)

// Customer and Order Type
const selectedCustomer = ref<number | null>(null)
const selectedOrderType = ref<'takeaway' | 'dine_in' | 'delivery'>('dine_in')

// Discount data
const appliedDiscountCode = ref<Discount | null>(null)
const codeDiscountAmount = ref(0)
const appliedPromotions = ref<Promotion[]>([])
const promotionDiscountAmount = ref(0)

// Tax data
const taxPercentage = ref(11) // Default PPN 11%
const taxAmount = ref(0)

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Payment methods
const paymentMethods = [
  {
    value: 'cash',
    title: 'Cash',
    icon: 'mdi-cash-multiple',
    description: 'Pembayaran dengan uang cash'
  },
  {
    value: 'qris',
    title: 'QRIS',
    icon: 'mdi-qrcode-scan',
    description: 'Pembayaran menggunakan QRIS'
  },
  {
    value: 'digital_wallet',
    title: 'E-Wallet',
    icon: 'mdi-wallet',
    description: 'GoPay, OVO, DANA, dll'
  },
  {
    value: 'gojek',
    title: 'Gojek',
    icon: 'mdi-wallet',
    description: 'Pembayaran melalui Gojek/GoFood'
  },
  {
    value: 'Grab',
    title: 'Grab',
    icon: 'mdi-wallet',
    description: 'Pembayaran melalui Grab/GrabFood'
  },
  {
    value: 'Shopee',
    title: 'Shopee',
    icon: 'mdi-wallet',
    description: 'Pembayaran melalui Shopee/ShopeeFood'
  },
  {
    value: 'card',
    title: 'Debit/Credit Card',
    icon: 'mdi-credit-card',
    description: 'Kartu Debit/Credit'
  },
  {
    value: 'bank_transfer',
    title: 'Transfer Bank',
    icon: 'mdi-bank-transfer',
    description: 'Transfer Bank'
  }
]

// Computed
const localDialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const subtotal = computed(() => {
  return props.cartItems.reduce((total, item) => {
    const price = item.item_type === 'package' 
      ? (item.package_price || 0) 
      : (item.selling_price || 0)
    return total + (price * item.quantity)
  }, 0)
})

// Discount calculations
const discountValue = computed(() => {
  // Combine all discount sources
  let totalDiscount = 0
  
  // Add promotion discount (highest priority)
  if (promotionDiscountAmount.value > 0) {
    totalDiscount += promotionDiscountAmount.value
  }
  
  // Add code discount (if no promotion or they can be combined)
  if (codeDiscountAmount.value > 0) {
    totalDiscount += codeDiscountAmount.value
  }
  
  return Math.min(totalDiscount, subtotal.value)
})

const finalTotal = computed(() => {
  const afterDiscount = subtotal.value - discountValue.value
  
  // Calculate tax on amount after discount
  taxAmount.value = (afterDiscount * taxPercentage.value) / 100
  
  return afterDiscount + taxAmount.value
})

const changeAmount = computed(() => {
  const paid = Number(paidAmount.value) || 0
  const total = Number(finalTotal.value) || 0
  return paid - total
})

const quickAmounts = computed(() => {
  const amounts = [
    50000, 100000, 200000, 500000
  ]
  
  // Add amount slightly higher than final total
  const roundedTotal = Math.ceil(finalTotal.value / 1000) * 1000
  if (!amounts.includes(roundedTotal)) {
    amounts.unshift(roundedTotal)
  }
  
  return amounts.filter(amount => amount >= finalTotal.value).sort((a, b) => a - b)
})

const quickDiscounts = computed(() => [
  { type: 'percentage', value: 5, label: '5%' },
  { type: 'percentage', value: 10, label: '10%' },
  { type: 'percentage', value: 15, label: '15%' },
  { type: 'amount', value: 5000, label: '5K' },
  { type: 'amount', value: 10000, label: '10K' },
  { type: 'amount', value: 20000, label: '20K' },
])

const canProcess = computed(() => {
  if (selectedPaymentMethod.value === 'cash') {
    return paidAmount.value >= finalTotal.value
  }
  
  // For non-cash payments, reference number is required
  return referenceNumber.value.trim().length > 0
})

// Methods
const getMethodColor = (methodValue: string, isSelected: boolean) => {
  if (isSelected) {
    switch (methodValue) {
      case 'cash': return '#22c55e' // Green for cash
      case 'card': return '#6366f1' // Purple for card  
      case 'qris': return '#ef4444' // Red for split (matching the image)
      case 'digital_wallet': return '#f59e0b' // Orange for e-wallet
      case 'bank_transfer': return '#8b5cf6' // Purple for transfer
      default: return '#64748b'
    }
  }
  return '#64748b' // Gray for unselected
}

// Currency formatting functions
const formatCurrencyInput = (value: string): string => {
  // Remove all non-numeric characters
  const numericValue = value.replace(/\D/g, '')
  
  // Convert to number and format with thousand separators
  if (numericValue) {
    const number = parseInt(numericValue)
    return new Intl.NumberFormat('id-ID').format(number)
  }
  return ''
}

const parseCurrencyInput = (formattedValue: string): number => {
  // Remove all non-numeric characters and convert to number
  const numericValue = formattedValue.replace(/\D/g, '')
  return numericValue ? parseInt(numericValue) : 0
}

// Watch for paidAmount changes to update display
watch(paidAmount, (newValue) => {
  if (newValue === 0) {
    paidAmountDisplay.value = ''
  } else {
    paidAmountDisplay.value = formatCurrencyInput(newValue.toString())
  }
}, { immediate: true })

// Watch for display changes to update actual value
const handlePaidAmountInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const formatted = formatCurrencyInput(target.value)
  paidAmountDisplay.value = formatted
  paidAmount.value = parseCurrencyInput(formatted)
}

const getPaymentMethodText = (method: string) => {
  const methodMap: Record<string, string> = {
    'cash': 'Cash',
    'card': 'Credit Card',
    'qris': 'Split Payment',
    'digital_wallet': 'E-Wallet',
    'bank_transfer': 'Transfer Bank'
  }
  return methodMap[method] || method
}

const getOrderTypeText = (orderType: string) => {
  const orderTypeMap: Record<string, string> = {
    'takeaway': 'Takeaway',
    'dine_in': 'Dine In',
    'delivery': 'Delivery'
  }
  return orderTypeMap[orderType] || orderType
}

const showConfirmation = () => {
  confirmationDialog.value = true
}

const confirmPayment = () => {
  confirmationDialog.value = false
  processPayment()
}

const closeDialog = () => {
  if (!processing.value) {
    resetForm()
    localDialog.value = false
  }
}

const resetForm = () => {
  selectedPaymentMethod.value = 'cash'
  paidAmount.value = 0
  paidAmountDisplay.value = ''
  referenceNumber.value = ''
  notes.value = ''
  selectedCustomer.value = null
  selectedOrderType.value = 'takeaway'
  appliedDiscountCode.value = null
  codeDiscountAmount.value = 0
  appliedPromotions.value = []
  promotionDiscountAmount.value = 0
}

const setQuickAmount = (amount: number) => {
  paidAmount.value = amount
  paidAmountDisplay.value = formatCurrencyInput(amount.toString())
}

const setExactAmount = () => {
  paidAmount.value = finalTotal.value
  paidAmountDisplay.value = formatCurrencyInput(finalTotal.value.toString())
}

// Discount code handlers
const handleDiscountApplied = (discount: Discount, amount: number) => {
  appliedDiscountCode.value = discount
  codeDiscountAmount.value = amount
}

const handleDiscountRemoved = () => {
  appliedDiscountCode.value = null
  codeDiscountAmount.value = 0
}

// Promotion handlers
const handlePromotionsApplied = (promotions: Promotion[], totalDiscount: number) => {
  appliedPromotions.value = promotions
  promotionDiscountAmount.value = totalDiscount
}

const handlePromotionsRemoved = () => {
  appliedPromotions.value = []
  promotionDiscountAmount.value = 0
}

const processPayment = async () => {
  try {
    processing.value = true
    
    // Validate payment data
    if (!selectedPaymentMethod.value) {
      throw new Error('Pilih metode pembayaran terlebih dahulu')
    }
    
    if (selectedPaymentMethod.value === 'cash' && Number(paidAmount.value) < Number(finalTotal.value)) {
      throw new Error(`Jumlah bayar tidak mencukupi. Total: ${finalTotal.value}, Dibayar: ${paidAmount.value}`)
    }
    
    if (selectedPaymentMethod.value !== 'cash' && !referenceNumber.value.trim()) {
      throw new Error('Nomor referensi diperlukan untuk pembayaran non-cash')
    }
    
    // Prepare payment data
    const paymentPayload = {
      cart_items: props.cartItems.map(item => {
        const price = item.item_type === 'package' ? (item.package_price || 0) : (item.selling_price || 0)
        return {
          product_id: item.item_type === 'package' ? undefined : item.id_product,
          package_id: item.item_type === 'package' ? item.id_package : undefined,
          item_type: item.item_type || 'product',
          quantity: item.quantity,
          unit_price: price,
          subtotal: price * item.quantity
        }
      }),
      order_type: selectedOrderType.value,
      customer_id: selectedCustomer.value,
      payment_method: selectedPaymentMethod.value,
      subtotal_amount: subtotal.value,
      discount_amount: discountValue.value,
      
      // Tax information
      tax_percentage: taxPercentage.value,
      tax_amount: taxAmount.value,
      
      // Discount details
      discount_type: appliedDiscountCode.value ? 'code' : 
                    (appliedPromotions.value.length > 0 ? 'promotion' : null),
      
      // Discount code info
      discount_code: appliedDiscountCode.value?.code || null,
      discount_code_id: appliedDiscountCode.value?.id || null,
      
      // Promotion info
      applied_promotions: appliedPromotions.value.map((p: Promotion) => ({
        id: p.id,
        name: p.name,
        type: p.type,
        calculated_discount: p.calculated_discount
      })),
      
      total_amount: finalTotal.value,
      paid_amount: selectedPaymentMethod.value === 'cash' ? paidAmount.value : finalTotal.value,
      change_amount: selectedPaymentMethod.value === 'cash' ? changeAmount.value : 0,
      reference_number: referenceNumber.value || null,
      notes: notes.value || null,
      cashier_id: authStore.user?.id || 1, // Get from auth store
      transaction_date: new Date().toISOString()
    }
    
    
    // Send request to Laravel backend using axios (same pattern as other modules)
    const response = await axios.post('/api/pos/process-direct-payment', paymentPayload)
    
    
    // Store processed order data
    processedOrder.value = response.data.data
    
    // Clear cart immediately after successful payment
    emit('clear-cart')
    
    // Emit success event with transaction data
    emit('payment-success', { 
      paymentData: paymentPayload,
      transactionData: response.data.data,
      receiptData: {
        transaction_id: response.data.data.id,
        transaction_number: response.data.data.transaction_number,
        total_amount: response.data.data.total_amount,
        paid_amount: response.data.data.paid_amount,
        change_amount: response.data.data.change_amount,
        payment_method: response.data.data.payment_method,
        transaction_date: response.data.data.created_at,
        items: response.data.data.items
      }
    })
    
    // Reset form and close dialog after successful payment
    setTimeout(() => {
      resetForm()
      localDialog.value = false
    }, 500)
    
  } catch (error: any) {
    console.error('Payment processing error:', error)
    
    // Show error message to user
    let errorMessage = 'Terjadi kesalahan saat memproses pembayaran'
    
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    } else if (error.message) {
      errorMessage = error.message
    }
    
    // Show error snackbar
    snackbar.value = {
      show: true,
      message: errorMessage,
      color: 'error'
    }
    
  } finally {
    processing.value = false
  }
}

const printReceipt = () => {
  // Implement receipt printing
  
  // For now, just open print dialog
  window.print()
  
  finishPayment()
}

const skipPrint = () => {
  finishPayment()
}

const finishPayment = () => {
  printDialog.value = false
  emit('payment-success', processedOrder.value)
  resetForm()
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}

// Watch for dialog changes
watch(localDialog, (newValue) => {
  if (newValue) {
    // Set default paid amount to final total (after discount)
    paidAmount.value = finalTotal.value
  }
})

// Watch payment method changes
watch(selectedPaymentMethod, (newMethod) => {
  if (newMethod === 'cash') {
    referenceNumber.value = ''
  } else {
    paidAmount.value = finalTotal.value
  }
})

// Watch discount changes to update paid amount for non-cash only
watch(finalTotal, (newTotal) => {
  // For non-cash payments, auto-set to exact amount
  if (selectedPaymentMethod.value !== 'cash') {
    paidAmount.value = newTotal
  }
  // For cash payments, only update if current paid amount is less than new total
  else if (selectedPaymentMethod.value === 'cash' && Number(paidAmount.value) < Number(newTotal)) {
    paidAmount.value = newTotal
  }
})
</script>

<style scoped>
/* Main Dialog */
.payment-dialog :deep(.v-overlay__content) {
  margin: 1rem;
}

.payment-card {
  overflow: hidden;
  border-radius: 16px !important;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 15%) !important;
}

/* Header */
.payment-header {
  background: linear-gradient(135deg, #905e20 0%, #905e20 100%);
  border-block-end: none;
  color: white;
}

.header-content {
  display: flex;
  align-items: center;
}

.close-btn {
  color: white !important;
}

.close-btn:hover {
  background-color: rgba(255, 255, 255, 10%) !important;
}

/* Order Summary */
.order-summary-card {
  border: 1px solid rgba(0, 0, 0, 8%);
  border-radius: 12px !important;
}

.order-item {
  border-block-end: 1px solid rgba(0, 0, 0, 5%);
}

.order-item:last-child {
  border-block-end: none;
}

.item-info {
  flex: 1;
}

.item-name {
  color: rgb(var(--v-theme-on-surface));
}

.item-quantity {
  margin-block-start: 2px;
}

.final-total {
  border-radius: 8px;
  background: rgba(var(--v-theme-primary), 0.05);
  margin-block: 0;
  margin-inline: -16px;
  padding-block: 12px !important;
  padding-inline: 16px !important;
}

/* Payment Methods */
.payment-methods-section {
  padding: 0;
}

.payment-section-title {
  color: #e2d5c1;
  font-size: 16px;
  font-weight: 500;
  margin-block: 0 16px;
  margin-inline: 0;
}

.payment-methods-horizontal {
  margin: 0;
}

.payment-methods-row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}

.payment-method-card {
  position: relative;
  overflow: hidden;
  flex: 1;
  border: 2px solid #e5e7eb !important;
  border-radius: 12px !important;
  background: #fff !important;
  cursor: pointer;
  min-inline-size: 120px;
  transition: all 0.2s ease;
}

.payment-method-card:hover {
  border-color: #d1d5db !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 10%);
  transform: translateY(-1px);
}

.payment-method-card.selected {
  border-color: #3b82f6 !important;
  background: #f8fafc !important;
  box-shadow: 0 4px 16px rgba(59, 130, 246, 20%);
}

.payment-method-content {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding-block: 20px !important;
  padding-inline: 16px !important;
  text-align: center;
}

.payment-icon-wrapper {
  display: flex;
  justify-content: center;
  margin-block-end: 4px;
}

.payment-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: #f1f5f9;
  block-size: 48px;
  inline-size: 48px;
  transition: all 0.2s ease;
}

.payment-icon.active {
  background: #f0f9ff;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 20%);
}

.payment-method-title {
  color: #374151;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.2;
  margin-block-start: 4px;
}

.payment-radio {
  position: absolute;
  margin: 0;
  inset-block-start: 8px;
  inset-inline-end: 8px;
}

.payment-radio :deep(.v-selection-control) {
  min-block-size: auto;
}

.payment-radio :deep(.v-selection-control__wrapper) {
  block-size: 20px;
  inline-size: 20px;
}

@media (max-width: 768px) {
  .payment-methods-row {
    gap: 12px;
  }

  .payment-method-card {
    min-inline-size: 100px;
  }

  .payment-method-content {
    padding-block: 16px !important;
    padding-inline: 12px !important;
  }

  .payment-icon {
    block-size: 40px;
    inline-size: 40px;
  }

  .payment-method-title {
    font-size: 13px;
  }
}

.payment-method-option.selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);
  box-shadow: 0 4px 16px rgba(var(--v-theme-primary), 0.2);
}

/* Payment Amount */
.payment-amount-card {
  border: 1px solid rgba(var(--v-theme-success), 0.2);
  border-radius: 12px !important;
}

.payment-input :deep(.v-field__outline) {
  border-color: rgba(var(--v-theme-success), 0.3);
}

.change-input :deep(.v-field__outline) {
  border-color: rgba(var(--v-theme-success), 0.3);
}

/* Quick Amounts */
.quick-amounts {
  padding: 12px;
  border-radius: 8px;
  background: rgba(var(--v-theme-success), 0.03);
}

.quick-amounts-label {
  font-size: 11px;
  font-weight: 500;
}

.quick-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.quick-btn {
  border-radius: 16px !important;
  block-size: 28px;
  font-size: 11px;
  font-weight: 500;
  min-inline-size: 60px;
  transition: all 0.2s ease;
}

.quick-btn:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 10%);
  transform: translateY(-1px);
}

/* Other Cards */
.reference-card,
.notes-card {
  border: 1px solid rgba(0, 0, 0, 8%);
  border-radius: 12px !important;
}

/* Actions */
.payment-actions {
  background: rgba(var(--v-theme-surface), 0.5);
  border-block-start: 1px solid rgba(var(--v-border-color), 0.2);
}

.process-payment-btn {
  border-radius: 25px !important;
  box-shadow: 0 4px 16px rgba(var(--v-theme-success), 0.3) !important;
  font-weight: 600;
  padding-block: 0 !important;
  padding-inline: 32px !important;
  text-transform: none;
}

.process-payment-btn:hover {
  box-shadow: 0 6px 24px rgba(var(--v-theme-success), 0.4) !important;
  transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
  .payment-methods-grid {
    gap: 8px;
    grid-template-columns: repeat(3, 1fr);
  }

  .payment-method-option {
    min-block-size: 75px;
    padding-block: 10px;
    padding-inline: 6px;
  }

  .quick-buttons {
    justify-content: center;
    gap: 4px;
  }

  .quick-btn {
    font-size: 10px;
    min-inline-size: 55px;
  }

  .payment-dialog :deep(.v-overlay__content) {
    margin: 0.5rem;
  }
}

@media (max-width: 480px) {
  .payment-methods-grid {
    grid-template-columns: 1fr;
  }

  .quick-buttons {
    flex-direction: column;
    align-items: stretch;
  }

  .quick-btn {
    inline-size: 100%;
  }
}

/* Animation */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.payment-card {
  animation: slideIn 0.3s ease-out;
}

/* Discount Section */
.discount-card {
  border: 1px solid rgba(var(--v-theme-warning), 0.2);
  border-radius: 12px !important;
}

.discount-result-input :deep(.v-field__outline) {
  border-color: rgba(var(--v-theme-warning), 0.3);
}

.quick-discounts {
  padding: 12px;
  border-radius: 8px;
  background: rgba(var(--v-theme-warning), 0.03);
}

.quick-discounts-label {
  font-size: 11px;
  font-weight: 500;
}

.quick-discount-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.quick-discount-btn {
  border-radius: 16px !important;
  block-size: 28px;
  font-size: 11px;
  font-weight: 500;
  min-inline-size: 40px;
  transition: all 0.2s ease;
}

.quick-discount-btn:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 10%);
  transform: translateY(-1px);
}

/* Card hover effects */
.order-summary-card,
.payment-methods-card,
.payment-amount-card,
.discount-card,
.reference-card,
.notes-card {
  transition: all 0.2s ease;
}

.order-summary-card:hover,
.payment-methods-card:hover,
.payment-amount-card:hover,
.discount-card:hover,
.reference-card:hover,
.notes-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 8%) !important;
}

/* Coffee Theme Styling */
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
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1.2;
}

.coffee-subtitle {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.2;
  opacity: 0.9;
}

/* Customer & Order Type Card */
.customer-order-card {
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 5%);
}

.customer-select :deep(.v-field) {
  border-radius: 8px;
}

.customer-select :deep(.v-field__outline) {
  border-color: #e2e8f0;
}

.customer-select :deep(.v-field--focused .v-field__outline) {
  border-color: #3b82f6;
}

/* Order Type Section in Dialog */
.order-type-section {
  margin-block-start: 12px;
}

.order-type-label {
  display: block;
  color: #374151;
  font-size: 14px;
  font-weight: 600;
  margin-block-end: 8px;
}

.order-type-toggle {
  display: flex;
  overflow: hidden;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  inline-size: 100%;
}

.order-type-btn {
  flex: 1;
  border: none !important;
  border-radius: 0 !important;
  background: transparent !important;
  color: #64748b !important;
  font-weight: 500;
  min-block-size: 48px;
  text-transform: none;
  transition: all 0.2s ease;
}

.order-type-btn:hover {
  background: rgba(59, 130, 246, 5%) !important;
  color: #3b82f6 !important;
}

.order-type-btn.active,
.order-type-btn.v-btn--selected {
  background: #3b82f6 !important;
  color: white !important;
  font-weight: 600;
}

.order-type-btn .v-icon {
  font-size: 18px;
  margin-inline-end: 6px;
}
</style>
