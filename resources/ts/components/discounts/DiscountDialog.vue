<template>
  <VDialog
    v-model="localValue"
    max-width="800px"
    persistent
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between bg-primary text-white">
        <div class="d-flex align-center">
          <VIcon icon="mdi-tag-outline" size="24" class="me-2" />
          <span>{{ title }}</span>
        </div>
        <VBtn
          icon="mdi-close"
          variant="text"
          color="white"
          @click="cancel"
        />
      </VCardTitle>

      <VForm ref="formRef" v-model="isValid" @submit.prevent="save">
        <VCardText class="pa-6">
          <!-- Error Alert -->
          <VAlert
            v-if="showErrorAlert"
            type="error"
            variant="tonal"
            class="mb-4"
            closable
            @click:close="showErrorAlert = false"
          >
            <div class="text-subtitle-2 mb-2">{{ errorMessage }}</div>
            <div v-if="Object.keys(serverErrors).length > 0" class="text-body-2">
              <div v-for="(errors, field) in serverErrors" :key="field" class="mb-1">
                <strong>{{ field === 'valid_until' ? 'Tanggal Berakhir' : field === 'valid_from' ? 'Tanggal Mulai' : field }}:</strong>
                <ul class="ml-4 mt-1">
                  <li v-for="error in errors" :key="error" class="text-caption">{{ error }}</li>
                </ul>
              </div>
            </div>
          </VAlert>

          <VRow>
            <!-- Basic Information -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4">Informasi Dasar</h6>
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.name"
                label="Nama Diskon"
                :rules="nameRules"
                variant="outlined"
                required
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.code"
                label="Kode Diskon"
                :rules="codeRules"
                variant="outlined"
                required
                @blur="validateCode"
              >
                <template #append-inner>
                  <VBtn
                    icon="mdi-dice-multiple"
                    size="small"
                    variant="text"
                    @click="generateCode"
                  />
                </template>
              </VTextField>
              <div v-if="codeValidation.loading" class="text-caption text-info mt-1">
                <VIcon icon="mdi-loading" size="14" class="spin me-1" />
                Mengecek ketersediaan kode...
              </div>
              <div v-else-if="codeValidation.available === false" class="text-caption text-error mt-1">
                <VIcon icon="mdi-alert" size="14" class="me-1" />
                Kode sudah digunakan
              </div>
              <div v-else-if="codeValidation.available === true" class="text-caption text-success mt-1">
                <VIcon icon="mdi-check" size="14" class="me-1" />
                Kode tersedia
              </div>
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="formData.description"
                label="Deskripsi"
                variant="outlined"
                rows="3"
                auto-grow
              />
            </VCol>

            <!-- Discount Configuration -->
            <VCol cols="12">
              <VDivider class="my-4" />
              <h6 class="text-h6 mb-4">Konfigurasi Diskon</h6>
            </VCol>

            <VCol cols="12" md="4">
              <VSelect
                v-model="formData.type"
                :items="discountTypes"
                label="Tipe Diskon"
                :rules="[v => !!v || 'Tipe diskon wajib dipilih']"
                variant="outlined"
                required
                @update:model-value="onTypeChange"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model.number="formData.value"
                :label="getValueLabel()"
                :rules="valueRules"
                variant="outlined"
                type="number"
                :prefix="formData.type === 'fixed_amount' ? 'Rp' : undefined"
                :suffix="formData.type === 'percentage' ? '%' : undefined"
                min="0"
                required
              />
            </VCol>

            <!-- Minimum Amount -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="minimumAmountDisplay"
                label="Minimum Pembelian"
                variant="outlined"
                prefix="Rp"
                hint="Kosongkan jika tidak ada minimum"
                @input="onMinimumAmountInput($event.target.value)"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="maximumDiscountDisplay"
                label="Maksimum Diskon"
                variant="outlined"
                prefix="Rp"
                hint="Hanya untuk diskon persentase"
                :disabled="formData.type !== 'percentage'"
                @input="onMaximumDiscountInput($event.target.value)"
              />
            </VCol>

            <!-- Usage Limits -->
            <VCol cols="12">
              <VDivider class="my-4" />
              <h6 class="text-h6 mb-4">Batasan Penggunaan</h6>
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model.number="formData.usage_limit"
                label="Batas Total Penggunaan"
                variant="outlined"
                type="number"
                min="1"
                hint="Kosongkan untuk unlimited"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model.number="formData.usage_limit_per_customer"
                label="Batas Per Pengguna"
                variant="outlined"
                type="number"
                min="1"
                hint="Kosongkan untuk unlimited"
              />
            </VCol>

            <!-- Valid Period -->
            <VCol cols="12">
              <VDivider class="my-4" />
              <h6 class="text-h6 mb-4">Periode Berlaku</h6>
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.valid_from"
                label="Berlaku Dari"
                :rules="validFromRules"
                variant="outlined"
                type="datetime-local"
                required
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.valid_until"
                label="Berlaku Sampai"
                :rules="validUntilRules"
                variant="outlined"
                type="datetime-local"
                required
              />
            </VCol>

            <!-- Status -->
            <VCol cols="12">
              <VDivider class="my-4" />
              <h6 class="text-h6 mb-4">Status</h6>
            </VCol>

            <VCol cols="12">
              <VSwitch
                v-model="formData.active"
                label="Aktifkan diskon ini"
                color="primary"
                inset
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="pa-6 pt-0">
          <VSpacer />
          <VBtn
            variant="outlined"
            @click="cancel"
          >
            Batal
          </VBtn>
          <VBtn
            type="submit"
            color="primary"
            :loading="saving"
            :disabled="!isValid || codeValidation.available === false"
          >
            {{ props.mode === 'create' ? 'Simpan' : 'Update' }}
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import DiscountsApi, { type CreateDiscountRequest, type Discount } from '@/utils/api/DiscountsApi'
import { useDebounceFn } from '@vueuse/core'
import { computed, onMounted, ref, watch } from 'vue'

// Types
interface Props {
  modelValue: boolean
  mode: 'create' | 'edit'
  discount: Discount | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'saved'): void
}

const props = withDefaults(defineProps<Props>(), {
  discount: null
})

const emit = defineEmits<Emits>()

// Form refs
const formRef = ref()
const isValid = ref(false)
const saving = ref(false)

// Error handling
const serverErrors = ref<Record<string, string[]>>({})
const showErrorAlert = ref(false)
const errorMessage = ref('')

// Code validation
const codeValidation = ref({
  loading: false,
  available: null as boolean | null
})

// Form data
const formData = ref<CreateDiscountRequest>({
  name: '',
  code: '',
  description: '',
  type: 'percentage',
  value: 0,
  minimum_amount: undefined,
  maximum_discount: undefined,
  usage_limit: undefined,
  usage_limit_per_customer: undefined,
  valid_from: '',
  valid_until: '',
  active: true
})

// Currency formatting methods
const formatRupiah = (value: number | undefined): string => {
  if (!value || value === 0) return ''
  return new Intl.NumberFormat('id-ID').format(value)
}

const parseRupiah = (value: string): number | undefined => {
  if (!value || value.trim() === '') return undefined
  // Remove all non-digit characters
  const cleanValue = value.replace(/[^\d]/g, '')
  if (cleanValue === '') return undefined
  const parsed = parseInt(cleanValue)
  return isNaN(parsed) ? undefined : parsed
}

// Reactive currency display values
const minimumAmountDisplay = ref('')
const maximumDiscountDisplay = ref('')

// Update display when formData changes
watch(() => formData.value.minimum_amount, (newValue) => {
  minimumAmountDisplay.value = formatRupiah(newValue)
})

watch(() => formData.value.maximum_discount, (newValue) => {
  maximumDiscountDisplay.value = formatRupiah(newValue)
})

// Handle input changes
const onMinimumAmountInput = (value: string) => {
  const parsed = parseRupiah(value)
  formData.value.minimum_amount = parsed
  minimumAmountDisplay.value = formatRupiah(parsed)
}

const onMaximumDiscountInput = (value: string) => {
  const parsed = parseRupiah(value)
  formData.value.maximum_discount = parsed
  maximumDiscountDisplay.value = formatRupiah(parsed)
}

// Constants
const discountTypes = [
  { title: 'Persentase (%)', value: 'percentage' },
  { title: 'Jumlah Tetap (Rp)', value: 'fixed_amount' },
  { title: 'Beli X Dapat Y', value: 'buy_x_get_y' }
]

// Computed
const localValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const title = computed(() => {
  return props.mode === 'create' ? 'Tambah Diskon Baru' : 'Edit Diskon'
})

// Validation rules
const nameRules = [
  (v: string) => !!v || 'Nama diskon wajib diisi',
  (v: string) => v.length >= 3 || 'Nama minimal 3 karakter'
]

const codeRules = [
  (v: string) => !!v || 'Kode diskon wajib diisi',
  (v: string) => v.length >= 3 || 'Kode minimal 3 karakter',
  (v: string) => /^[A-Z0-9_-]+$/.test(v) || 'Kode hanya boleh huruf kapital, angka, - dan _'
]

const valueRules = computed(() => [
  (v: number) => v > 0 || 'Nilai harus lebih dari 0',
  ...(formData.value.type === 'percentage' ? [
    (v: number) => v <= 100 || 'Persentase maksimal 100%'
  ] : [])
])

// Date validation rules
const validFromRules = [
  (v: string) => !!v || 'Tanggal mulai wajib diisi'
]

const validUntilRules = computed(() => [
  (v: string) => !!v || 'Tanggal berakhir wajib diisi',
  (v: string) => {
    if (!v || !formData.value.valid_from) return true
    const validFrom = new Date(formData.value.valid_from)
    const validUntil = new Date(v)
    return validUntil > validFrom || 'Tanggal berakhir harus setelah tanggal mulai'
  }
])

// Debounced code validation (mock implementation)
const debouncedValidateCode = useDebounceFn(async (code: string) => {
  if (!code || code.length < 3) {
    codeValidation.value = { loading: false, available: null }
    return
  }

  try {
    codeValidation.value.loading = true
    // Mock validation - always return available for now
    // TODO: Replace with actual API call when backend is ready
    await new Promise(resolve => setTimeout(resolve, 500))
    codeValidation.value = {
      loading: false,
      available: true
    }
  } catch (error) {
    codeValidation.value = { loading: false, available: null }
    console.error('Code validation error:', error)
  }
}, 500)

const validateCode = () => {
  debouncedValidateCode(formData.value.code)
}

const generateCode = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let result = ''
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  formData.value.code = result
  validateCode()
}

const getValueLabel = () => {
  switch (formData.value.type) {
    case 'percentage':
      return 'Persentase Diskon'
    case 'fixed_amount':
      return 'Jumlah Diskon'
    case 'buy_x_get_y':
      return 'Persentase/Jumlah Diskon'
    default:
      return 'Nilai'
  }
}

const onTypeChange = () => {
  // Reset related fields when type changes
  if (formData.value.type !== 'percentage') {
    formData.value.maximum_discount = undefined
  }
}

const resetForm = () => {
  formData.value = {
    name: '',
    code: '',
    description: '',
    type: 'percentage',
    value: 0,
    minimum_amount: undefined,
    maximum_discount: undefined,
    usage_limit: undefined,
    usage_limit_per_customer: undefined,
    valid_from: '',
    valid_until: '',
    active: true
  }
  
  // Reset display values
  minimumAmountDisplay.value = ''
  maximumDiscountDisplay.value = ''
  
  codeValidation.value = { loading: false, available: null }
  serverErrors.value = {}
  showErrorAlert.value = false
  errorMessage.value = ''
}

const loadDiscountData = () => {
  if (props.discount) {
    const discount = props.discount
    
    formData.value = {
      name: discount.name,
      code: discount.code,
      description: discount.description || '',
      type: discount.type,
      value: discount.value,
      minimum_amount: discount.minimum_amount,
      maximum_discount: discount.maximum_discount,
      usage_limit: discount.usage_limit,
      usage_limit_per_customer: discount.usage_limit_per_customer,
      valid_from: discount.valid_from ? new Date(discount.valid_from).toISOString().slice(0, 16) : '',
      valid_until: discount.valid_until ? new Date(discount.valid_until).toISOString().slice(0, 16) : '',
      active: discount.active
    }
    
    // Update display values
    minimumAmountDisplay.value = formatRupiah(discount.minimum_amount)
    maximumDiscountDisplay.value = formatRupiah(discount.maximum_discount)
  }
}

const save = async () => {
  // Reset previous errors
  serverErrors.value = {}
  showErrorAlert.value = false
  errorMessage.value = ''

  // Validate form first
  const { valid } = await formRef.value?.validate()
  if (!valid || codeValidation.value.available === false) {
    return
  }

  // Additional date validation before sending to server
  if (formData.value.valid_from && formData.value.valid_until) {
    const validFrom = new Date(formData.value.valid_from)
    const validUntil = new Date(formData.value.valid_until)
    
    if (validUntil <= validFrom) {
      errorMessage.value = 'Tanggal berakhir harus setelah tanggal mulai'
      showErrorAlert.value = true
      return
    }
  }

  try {
    saving.value = true

    if (props.mode === 'create') {
      await DiscountsApi.createDiscount(formData.value)
    } else if (props.discount) {
      await DiscountsApi.updateDiscount(props.discount.id_discount, formData.value)
    }

    emit('saved')
  } catch (error: any) {
    console.error('Save error:', error)
    
    // Handle validation errors from server
    if (error.response?.data?.errors) {
      serverErrors.value = error.response.data.errors
      errorMessage.value = error.response.data.message || 'Terjadi kesalahan validasi'
    } else {
      errorMessage.value = error.message || 'Terjadi kesalahan saat menyimpan data'
    }
    showErrorAlert.value = true
  } finally {
    saving.value = false
  }
}

const cancel = () => {
  localValue.value = false
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    if (props.mode === 'edit' && props.discount) {
      loadDiscountData()
    } else {
      resetForm()
    }
  }
})

watch(() => formData.value.code, (newCode) => {
  if (newCode) {
    validateCode()
  }
})

// Watch date changes to trigger revalidation
watch(() => formData.value.valid_from, () => {
  // Reset server errors when user changes date
  if (serverErrors.value.valid_from || serverErrors.value.valid_until) {
    serverErrors.value = {}
    showErrorAlert.value = false
  }
})

watch(() => formData.value.valid_until, () => {
  // Reset server errors when user changes date  
  if (serverErrors.value.valid_from || serverErrors.value.valid_until) {
    serverErrors.value = {}
    showErrorAlert.value = false
  }
})

onMounted(() => {
  if (props.modelValue && props.mode === 'edit' && props.discount) {
    loadDiscountData()
  }
})
</script>

<style scoped>
.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
