<template>
  <VCard class="discount-code-card mb-4" variant="tonal" color="success-lighten-5">
    <VCardTitle class="text-h6 pb-2">
      <VIcon icon="mdi-ticket-percent" size="20" class="me-2" />
      Kode Diskon
    </VCardTitle>
    
    <VCardText class="pt-0">
      <!-- Discount Code Input -->
      <VRow class="mb-3">
        <VCol cols="12" md="8">
          <VTextField
            v-model="discountCode"
            label="Masukkan Kode Diskon"
            variant="outlined"
            placeholder="Contoh: DISC10, SAVE20"
            prepend-inner-icon="mdi-ticket"
            :loading="isValidating"
            :error="hasError"
            :error-messages="errorMessage"
            :success="isValid && !hasError"
            :success-messages="successMessage"
            @input="onCodeInput"
            @keyup.enter="validateCode"
          >
            <template #append-inner>
              <VBtn
                v-if="discountCode && !isValidating"
                icon="mdi-close"
                variant="text"
                size="small"
                @click="clearCode"
              />
            </template>
          </VTextField>
        </VCol>
        <VCol cols="12" md="4">
          <VBtn
            :disabled="!discountCode || isValidating"
            :loading="isValidating"
            color="success"
            variant="flat"
            block
            @click="validateCode"
          >
            <VIcon icon="mdi-check" class="me-1" />
            Terapkan
          </VBtn>
        </VCol>
      </VRow>

      <!-- Applied Discount Info -->
      <div v-if="appliedDiscount" class="applied-discount-info">
        <VAlert
          type="success"
          variant="tonal"
          class="mb-3"
        >
          <template #prepend>
            <VIcon icon="mdi-check-circle" />
          </template>
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="font-weight-medium">{{ appliedDiscount.name }}</div>
              <div class="text-caption">{{ appliedDiscount.description }}</div>
            </div>
            <VBtn
              icon="mdi-close"
              variant="text"
              size="small"
              @click="removeDiscount"
            />
          </div>
        </VAlert>

        <!-- Discount Details -->
        <div class="discount-details">
          <div class="detail-row d-flex justify-space-between py-1">
            <span class="text-body-2">Kode Diskon:</span>
            <span class="text-body-2 font-weight-medium">{{ appliedDiscount.code }}</span>
          </div>
          <div class="detail-row d-flex justify-space-between py-1">
            <span class="text-body-2">Jenis:</span>
            <span class="text-body-2">
              {{ appliedDiscount.type === 'percentage' ? 'Persentase' : 'Nominal' }}
            </span>
          </div>
          <div class="detail-row d-flex justify-space-between py-1">
            <span class="text-body-2">Nilai:</span>
            <span class="text-body-2 font-weight-medium">
              {{ appliedDiscount.type === 'percentage' 
                ? `${appliedDiscount.value}%` 
                : formatCurrency(appliedDiscount.value) 
              }}
            </span>
          </div>
          <div v-if="appliedDiscount.minimum_amount > 0" class="detail-row d-flex justify-space-between py-1">
            <span class="text-body-2">Min. Pembelian:</span>
            <span class="text-body-2">{{ formatCurrency(appliedDiscount.minimum_amount) }}</span>
          </div>
          <div v-if="appliedDiscount.usage_limit" class="detail-row d-flex justify-space-between py-1">
            <span class="text-body-2">Sisa Penggunaan:</span>
            <span class="text-body-2">{{ appliedDiscount.usage_limit - appliedDiscount.used_count }}</span>
          </div>
          <div v-if="appliedDiscount.valid_until" class="detail-row d-flex justify-space-between py-1">
            <span class="text-body-2">Berlaku Hingga:</span>
            <span class="text-body-2">{{ formatDate(appliedDiscount.valid_until) }}</span>
          </div>
          
          <VDivider class="my-2" />
          
          <div class="discount-amount d-flex justify-space-between py-1">
            <span class="text-body-1 font-weight-medium">Potongan Harga:</span>
            <span class="text-body-1 font-weight-bold text-success">
              -{{ formatCurrency(calculatedDiscount) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Codes (if available) -->
      <div v-if="quickCodes.length > 0" class="quick-codes mt-3">
        <div class="text-caption mb-2 text-medium-emphasis">Kode diskon tersedia:</div>
        <div class="d-flex flex-wrap gap-2">
          <VChip
            v-for="code in quickCodes"
            :key="code"
            size="small"
            variant="outlined"
            color="success"
            clickable
            @click="applyQuickCode(code)"
          >
            {{ code }}
          </VChip>
        </div>
      </div>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

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

// Mock API for now - will be replaced with actual implementation
const DiscountsApi = {
  async validateCode(code: string, subtotal: number) {
    // Mock validation - replace with actual API call
    return {
      success: false,
      message: 'API belum tersedia - implementasi mock',
      data: null
    }
  }
}

interface Props {
  subtotal: number
  quickCodes?: string[]
}

interface Emits {
  (e: 'discount-applied', discount: Discount, amount: number): void
  (e: 'discount-removed'): void
}

const props = withDefaults(defineProps<Props>(), {
  quickCodes: () => []
})

const emit = defineEmits<Emits>()

// State
const discountCode = ref('')
const appliedDiscount = ref<Discount | null>(null)
const isValidating = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const isValid = ref(false)
const validationTimeout = ref<NodeJS.Timeout | null>(null)

// Computed
const calculatedDiscount = computed(() => {
  if (!appliedDiscount.value) return 0
  
  const discount = appliedDiscount.value
  if (discount.type === 'percentage') {
    const amount = (props.subtotal * discount.value) / 100
    return discount.maximum_discount > 0 
      ? Math.min(amount, discount.maximum_discount)
      : amount
  }
  
  return Math.min(discount.value, props.subtotal)
})

// Methods
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount).replace('IDR', 'Rp')
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const onCodeInput = () => {
  hasError.value = false
  isValid.value = false
  errorMessage.value = ''
  successMessage.value = ''

  // Clear existing timeout
  if (validationTimeout.value) {
    clearTimeout(validationTimeout.value)
  }

  // Auto-validate after user stops typing (debounce)
  if (discountCode.value.length >= 3) {
    validationTimeout.value = setTimeout(() => {
      validateCode()
    }, 1000)
  }
}

const validateCode = async () => {
  if (!discountCode.value.trim()) {
    hasError.value = true
    errorMessage.value = 'Silakan masukkan kode diskon'
    return
  }

  isValidating.value = true
  hasError.value = false
  errorMessage.value = ''

  try {
    const response = await DiscountsApi.validateCode(discountCode.value.trim(), props.subtotal)
    
    if (response.success && response.data) {
      const discount = response.data as Discount
      
      // Check minimum amount
      if (discount.minimum_amount > 0 && props.subtotal < discount.minimum_amount) {
        hasError.value = true
        errorMessage.value = `Minimum pembelian ${formatCurrency(discount.minimum_amount)}`
        isValid.value = false
        return
      }

      // Check usage limit
      if (discount.usage_limit && discount.used_count >= discount.usage_limit) {
        hasError.value = true
        errorMessage.value = 'Kode diskon sudah mencapai batas penggunaan'
        isValid.value = false
        return
      }

      // Check expiration
      if (discount.valid_until) {
        const now = new Date()
        const validUntil = new Date(discount.valid_until)
        if (now > validUntil) {
          hasError.value = true
          errorMessage.value = 'Kode diskon sudah kedaluwarsa'
          isValid.value = false
          return
        }
      }

      appliedDiscount.value = discount
      isValid.value = true
      successMessage.value = `Kode diskon "${discount.code}" berhasil diterapkan!`
      
      // Emit discount applied event
      emit('discount-applied', discount, calculatedDiscount.value)
    } else {
      hasError.value = true
      errorMessage.value = response.message || 'Kode diskon tidak valid'
      isValid.value = false
    }
  } catch (error) {
    console.error('Error validating discount code:', error)
    hasError.value = true
    errorMessage.value = 'Gagal memvalidasi kode diskon'
    isValid.value = false
  } finally {
    isValidating.value = false
  }
}

const clearCode = () => {
  discountCode.value = ''
  removeDiscount()
}

const removeDiscount = () => {
  appliedDiscount.value = null
  isValid.value = false
  hasError.value = false
  errorMessage.value = ''
  successMessage.value = ''
  
  emit('discount-removed')
}

const applyQuickCode = (code: string) => {
  discountCode.value = code
  validateCode()
}

// Watch for subtotal changes to recalculate discount
watch(() => props.subtotal, () => {
  if (appliedDiscount.value) {
    // Re-emit with new calculated amount
    emit('discount-applied', appliedDiscount.value, calculatedDiscount.value)
    
    // Revalidate minimum amount
    if (appliedDiscount.value.minimum_amount > 0 && props.subtotal < appliedDiscount.value.minimum_amount) {
      hasError.value = true
      errorMessage.value = `Minimum pembelian ${formatCurrency(appliedDiscount.value.minimum_amount)}`
      removeDiscount()
    }
  }
})

// Expose methods for parent component
defineExpose({
  validateCode,
  clearCode,
  removeDiscount,
  appliedDiscount: computed(() => appliedDiscount.value),
  calculatedDiscount
})
</script>

<style scoped lang="scss">
.discount-code-card {
  border: 2px dashed rgb(var(--v-theme-success));
  border-radius: 12px;
  
  .discount-details {
    background: rgba(var(--v-theme-success), 0.04);
    border-radius: 8px;
    padding: 12px;
    
    .detail-row {
      min-height: 24px;
    }
    
    .discount-amount {
      border-top: 1px solid rgba(var(--v-theme-success), 0.12);
      margin-top: 8px;
      padding-top: 8px;
    }
  }
  
  .applied-discount-info {
    .v-alert {
      border-left: 4px solid rgb(var(--v-theme-success));
    }
  }
  
  .quick-codes {
    .v-chip {
      transition: all 0.2s;
      
      &:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(var(--v-theme-success), 0.2);
      }
    }
  }
}
</style>
