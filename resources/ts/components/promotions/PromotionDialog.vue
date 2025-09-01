<template>
  <VDialog
    v-model="localValue"
    max-width="900px"
    persistent
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between bg-primary text-white">
        <div class="d-flex align-center">
          <VIcon icon="mdi-bullhorn-outline" size="24" class="me-2" />
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
            v-if="errorMessage"
            type="error"
            class="mb-4"
            closable
            @click:close="clearErrors"
          >
            <div class="font-weight-medium">{{ errorMessage }}</div>
            <div v-if="Object.keys(fieldErrors).length > 0" class="mt-2">
              <ul class="mb-0">
                <li v-for="(errors, field) in fieldErrors" :key="field">
                  <strong>{{ getFieldLabel(field) }}:</strong> {{ errors.join(', ') }}
                </li>
              </ul>
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
                label="Nama Promosi"
                :rules="nameRules"
                variant="outlined"
                required
              />
            </VCol>

            <VCol cols="12" md="6">
              <VSelect
                v-model="formData.type"
                :items="promotionTypes"
                label="Tipe Promosi"
                :rules="[v => !!v || 'Tipe promosi wajib dipilih']"
                variant="outlined"
                required
                @update:model-value="onTypeChange"
              />
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

            <!-- Promotion Configuration -->
            <VCol cols="12">
              <VDivider class="my-4" />
              <h6 class="text-h6 mb-4">Konfigurasi Promosi</h6>
            </VCol>

            <!-- Happy Hour Configuration -->
            <template v-if="formData.type === 'happy_hour'">
              <VCol cols="12" md="4">
                <VTextField
                  v-model="formData.start_time"
                  label="Jam Mulai"
                  type="time"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="formData.end_time"
                  label="Jam Berakhir"
                  type="time"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="formData.valid_days"
                  :items="dayOptions"
                  label="Hari Berlaku"
                  multiple
                  chips
                  variant="outlined"
                />
              </VCol>
            </template>

            <!-- Buy One Get One Configuration -->
            <template v-if="formData.type === 'buy_one_get_one'">
              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="formData.buy_quantity"
                  label="Beli Quantity"
                  type="number"
                  min="1"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="formData.get_quantity"
                  label="Dapat Quantity"
                  type="number"
                  min="1"
                  variant="outlined"
                  required
                />
              </VCol>
            </template>

            <!-- Combo Deal Configuration -->
            <template v-if="formData.type === 'combo_deal'">
              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="formData.combo_quantity"
                  label="Jumlah Item Combo"
                  type="number"
                  min="2"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="comboPriceDisplay"
                  label="Harga Combo"
                  prefix="Rp"
                  variant="outlined"
                  required
                  @input="onComboPriceInput($event.target.value)"
                />
              </VCol>
            </template>

            <!-- Quantity Discount Configuration -->
            <template v-if="formData.type === 'quantity_discount'">
              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="formData.min_quantity"
                  label="Minimum Quantity"
                  type="number"
                  min="1"
                  variant="outlined"
                  required
                />
              </VCol>
            </template>

            <!-- Discount Configuration -->
            <VCol cols="12" md="4">
              <VSelect
                v-model="formData.discount_type"
                :items="discountTypes"
                label="Tipe Diskon"
                variant="outlined"
                required
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model.number="formData.discount_value"
                :label="getDiscountLabel()"
                :rules="discountRules"
                variant="outlined"
                type="number"
                :suffix="formData.discount_type === 'percentage' ? '%' : 'Rp'"
                required
              />
            </VCol>

            <VCol v-if="formData.discount_type === 'percentage'" cols="12" md="4">
              <VTextField
                v-model="maxDiscountDisplay"
                label="Maksimum Diskon"
                variant="outlined"
                prefix="Rp"
                hint="Opsional"
                @input="onMaxDiscountInput($event.target.value)"
              />
            </VCol>

            <!-- Priority -->
            <VCol cols="12" md="6">
              <VSlider
                v-model="formData.priority"
                label="Prioritas"
                :min="1"
                :max="10"
                :step="1"
                thumb-label="always"
                variant="outlined"
                color="primary"
              >
                <template #thumb-label="{ modelValue }">
                  {{ modelValue }}
                </template>
              </VSlider>
              <div class="text-caption text-medium-emphasis mt-2">
                1 = Prioritas Rendah, 10 = Prioritas Tinggi
              </div>
            </VCol>

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

            <!-- Valid Period -->
            <VCol cols="12">
              <VDivider class="my-4" />
              <h6 class="text-h6 mb-4">Periode Berlaku</h6>
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.valid_from"
                label="Berlaku Dari"
                variant="outlined"
                type="datetime-local"
                :rules="validFromRules"
                required
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.valid_until"
                label="Berlaku Sampai"
                variant="outlined"
                type="datetime-local"
                :rules="validUntilRules"
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
                label="Aktifkan promosi ini"
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
            :disabled="!isValid"
          >
            {{ mode === 'create' ? 'Simpan' : 'Update' }}
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import PromotionsApi, { type CreatePromotionRequest, type Promotion, type UpdatePromotionRequest } from '@/utils/api/PromotionsApi'
import { computed, nextTick, ref, watch } from 'vue'

interface Props {
  modelValue: boolean
  promotion?: Promotion | null
  mode: 'create' | 'edit'
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'saved'): void
}

const props = withDefaults(defineProps<Props>(), {
  promotion: null
})

const emit = defineEmits<Emits>()

// Form refs
const formRef = ref()
const isValid = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

// Form data
const formData = ref<CreatePromotionRequest>({
  name: '',
  description: '',
  type: 'happy_hour',
  discount_type: 'percentage',
  discount_value: 0,
  max_discount_amount: null,
  minimum_amount: null,
  priority: 5,
  start_time: null,
  end_time: null,
  valid_days: [],
  buy_quantity: null,
  get_quantity: null,
  combo_quantity: null,
  combo_price: null,
  min_quantity: null,
  valid_from: new Date().toISOString().slice(0, 16),
  valid_until: (() => {
    const date = new Date()
    date.setMonth(date.getMonth() + 1)
    return date.toISOString().slice(0, 16)
  })(),
  active: true,
  promotion_rules: []
})

// Constants
const promotionTypes = [
  { title: 'Happy Hour', value: 'happy_hour' },
  { title: 'Beli 1 Dapat 1', value: 'buy_one_get_one' },
  { title: 'Paket Combo', value: 'combo_deal' },
  { title: 'Diskon Kategori', value: 'category_discount' },
  { title: 'Diskon Quantity', value: 'quantity_discount' }
]

const discountTypes = [
  { title: 'Persentase (%)', value: 'percentage' },
  { title: 'Jumlah Tetap (Rp)', value: 'fixed_amount' }
]

const dayOptions = [
  { title: 'Senin', value: 'monday' },
  { title: 'Selasa', value: 'tuesday' },
  { title: 'Rabu', value: 'wednesday' },
  { title: 'Kamis', value: 'thursday' },
  { title: 'Jumat', value: 'friday' },
  { title: 'Sabtu', value: 'saturday' },
  { title: 'Minggu', value: 'sunday' }
]

// Computed
const localValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const title = computed(() => {
  return props.mode === 'create' ? 'Tambah Promosi Baru' : 'Edit Promosi'
})

// Validation rules
const nameRules = [
  (v: string) => !!v || 'Nama promosi wajib diisi',
  (v: string) => v.length >= 3 || 'Nama minimal 3 karakter'
]

const discountRules = computed(() => [
  (v: number) => v > 0 || 'Nilai diskon harus lebih dari 0',
  ...(formData.value.discount_type === 'percentage' ? [
    (v: number) => v <= 100 || 'Persentase maksimal 100%'
  ] : [])
])

const validFromRules = [
  (v: string) => !!v || 'Tanggal mulai berlaku wajib diisi'
]

const validUntilRules = [
  (v: string) => !!v || 'Tanggal berakhir wajib diisi',
  (v: string) => {
    if (!v || !formData.value.valid_from) return true
    return new Date(v) > new Date(formData.value.valid_from) || 'Tanggal berakhir harus setelah tanggal mulai'
  }
]

// Error handling methods
const clearErrors = () => {
  errorMessage.value = ''
  fieldErrors.value = {}
}

// Currency formatting methods
const formatRupiah = (value: number | null): string => {
  if (!value || value === 0) return ''
  return new Intl.NumberFormat('id-ID').format(value)
}

const parseRupiah = (value: string): number | null => {
  if (!value || value.trim() === '') return null
  // Remove all non-digit characters
  const cleanValue = value.replace(/[^\d]/g, '')
  if (cleanValue === '') return null
  const parsed = parseInt(cleanValue)
  return isNaN(parsed) ? null : parsed
}

// Reactive currency display values
const maxDiscountDisplay = ref('')
const minimumAmountDisplay = ref('')
const comboPriceDisplay = ref('')

// Update display when formData changes
watch(() => formData.value.max_discount_amount, (newValue) => {
  maxDiscountDisplay.value = formatRupiah(newValue)
})

watch(() => formData.value.minimum_amount, (newValue) => {
  minimumAmountDisplay.value = formatRupiah(newValue)
})

watch(() => formData.value.combo_price, (newValue) => {
  comboPriceDisplay.value = formatRupiah(newValue)
})

// Handle input changes
const onMaxDiscountInput = (value: string) => {
  const parsed = parseRupiah(value)
  formData.value.max_discount_amount = parsed
  maxDiscountDisplay.value = formatRupiah(parsed)
}

const onMinimumAmountInput = (value: string) => {
  const parsed = parseRupiah(value)
  formData.value.minimum_amount = parsed
  minimumAmountDisplay.value = formatRupiah(parsed)
}

const onComboPriceInput = (value: string) => {
  const parsed = parseRupiah(value)
  formData.value.combo_price = parsed
  comboPriceDisplay.value = formatRupiah(parsed)
}

const getFieldLabel = (fieldName: string): string => {
  const fieldLabels: Record<string, string> = {
    name: 'Nama Promosi',
    description: 'Deskripsi',
    type: 'Tipe Promosi',
    discount_type: 'Tipe Diskon',
    discount_value: 'Nilai Diskon',
    valid_from: 'Berlaku Dari',
    valid_until: 'Berlaku Sampai',
    start_time: 'Jam Mulai',
    end_time: 'Jam Berakhir',
    valid_days: 'Hari Berlaku',
    buy_quantity: 'Jumlah Beli',
    get_quantity: 'Jumlah Dapat',
    combo_quantity: 'Jumlah Paket',
    combo_price: 'Harga Paket',
    min_quantity: 'Minimal Quantity',
    minimum_amount: 'Minimal Pembelian',
    max_discount_amount: 'Maksimal Diskon',
    priority: 'Prioritas'
  }
  return fieldLabels[fieldName] || fieldName
}

// Methods
const getDiscountLabel = () => {
  return formData.value.discount_type === 'percentage' 
    ? 'Persentase Diskon' 
    : 'Jumlah Diskon'
}

const onTypeChange = () => {
  // Reset type-specific fields when type changes
  formData.value.start_time = null
  formData.value.end_time = null
  formData.value.valid_days = []
  formData.value.buy_quantity = null
  formData.value.get_quantity = null
  formData.value.combo_quantity = null
  formData.value.combo_price = null
  formData.value.min_quantity = null
}

const resetForm = () => {
  // Clear errors
  clearErrors()
  
  // Set default dates: now and 1 month later
  const now = new Date()
  const oneMonthLater = new Date()
  oneMonthLater.setMonth(oneMonthLater.getMonth() + 1)
  
  formData.value = {
    name: '',
    description: '',
    type: 'happy_hour',
    discount_type: 'percentage',
    discount_value: 0,
    max_discount_amount: null,
    minimum_amount: null,
    priority: 5,
    start_time: null,
    end_time: null,
    valid_days: [],
    buy_quantity: null,
    get_quantity: null,
    combo_quantity: null,
    combo_price: null,
    min_quantity: null,
    valid_from: now.toISOString().slice(0, 16),
    valid_until: oneMonthLater.toISOString().slice(0, 16),
    active: true,
    promotion_rules: []
  }
  
  // Reset display values
  maxDiscountDisplay.value = ''
  minimumAmountDisplay.value = ''
  comboPriceDisplay.value = ''
  
  nextTick(() => {
    formRef.value?.resetValidation()
  })
}

const loadPromotionData = () => {
  if (props.promotion) {
    const promotion = props.promotion
    
    formData.value = {
      name: promotion.name,
      description: promotion.description || '',
      type: promotion.type,
      discount_type: promotion.discount_type,
      discount_value: promotion.discount_value,
      max_discount_amount: promotion.max_discount_amount,
      minimum_amount: promotion.minimum_amount,
      priority: promotion.priority,
      start_time: promotion.start_time,
      end_time: promotion.end_time,
      valid_days: promotion.valid_days || [],
      buy_quantity: promotion.buy_quantity,
      get_quantity: promotion.get_quantity,
      combo_quantity: promotion.combo_quantity,
      combo_price: promotion.combo_price,
      min_quantity: promotion.min_quantity,
      valid_from: promotion.valid_from ? new Date(promotion.valid_from).toISOString().slice(0, 16) : null,
      valid_until: promotion.valid_until ? new Date(promotion.valid_until).toISOString().slice(0, 16) : null,
      active: promotion.active,
      promotion_rules: promotion.promotion_rules || []
    }
    
    // Update display values
    maxDiscountDisplay.value = formatRupiah(promotion.max_discount_amount)
    minimumAmountDisplay.value = formatRupiah(promotion.minimum_amount)
    comboPriceDisplay.value = formatRupiah(promotion.combo_price)
  }
}

const save = async () => {
  if (!isValid.value) return

  try {
    saving.value = true
    clearErrors() // Clear previous errors

    // Generate promotion_rules based on type
    formData.value.promotion_rules = generatePromotionRules()

    if (props.mode === 'create') {
      await PromotionsApi.createPromotion(formData.value)
    } else if (props.promotion) {
      await PromotionsApi.updatePromotion(props.promotion.id_promotion, formData.value as UpdatePromotionRequest)
    }

    emit('saved')
  } catch (error: any) {
    console.error('Save error:', error)
    
    // Handle different types of errors
    if (error?.response?.data) {
      const errorData = error.response.data
      
      // Check if it's a validation error
      if (errorData.errors && typeof errorData.errors === 'object') {
        fieldErrors.value = errorData.errors
        errorMessage.value = errorData.message || 'Terjadi kesalahan validasi'
      } else {
        // General error message
        errorMessage.value = errorData.message || 'Terjadi kesalahan saat menyimpan promosi'
      }
    } else if (error?.message) {
      errorMessage.value = error.message
    } else {
      errorMessage.value = 'Terjadi kesalahan yang tidak diketahui'
    }
  } finally {
    saving.value = false
  }
}

const generatePromotionRules = () => {
  const rules: any[] = []
  
  switch (formData.value.type) {
    case 'happy_hour':
      rules.push({
        type: 'time_based',
        start_time: formData.value.start_time,
        end_time: formData.value.end_time,
        valid_days: formData.value.valid_days || []
      })
      break
      
    case 'buy_one_get_one':
      rules.push({
        type: 'quantity_based',
        buy_quantity: formData.value.buy_quantity || 1,
        get_quantity: formData.value.get_quantity || 1
      })
      break
      
    case 'combo_deal':
      rules.push({
        type: 'combo_based',
        combo_quantity: formData.value.combo_quantity || 2,
        combo_price: formData.value.combo_price || 0
      })
      break
      
    case 'category_discount':
      rules.push({
        type: 'category_based',
        applicable_categories: formData.value.applicable_categories || []
      })
      break
      
    case 'quantity_discount':
      rules.push({
        type: 'quantity_threshold',
        min_quantity: formData.value.min_quantity || 1
      })
      break
  }
  
  // Add discount rules
  rules.push({
    type: 'discount',
    discount_type: formData.value.discount_type,
    discount_value: formData.value.discount_value,
    max_discount_amount: formData.value.max_discount_amount,
    minimum_amount: formData.value.minimum_amount
  })
  
  return rules
}

const cancel = () => {
  clearErrors()
  localValue.value = false
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    if (props.mode === 'edit' && props.promotion) {
      loadPromotionData()
    } else {
      resetForm()
    }
  }
})
</script>
