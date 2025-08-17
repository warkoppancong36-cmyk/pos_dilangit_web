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
                  v-model.number="formData.combo_price"
                  label="Harga Combo"
                  type="number"
                  min="0"
                  prefix="Rp"
                  variant="outlined"
                  required
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
                v-model.number="formData.max_discount_amount"
                label="Maksimum Diskon"
                variant="outlined"
                type="number"
                min="0"
                prefix="Rp"
                hint="Opsional"
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
                v-model.number="formData.minimum_amount"
                label="Minimum Pembelian"
                variant="outlined"
                type="number"
                prefix="Rp"
                min="0"
                hint="Kosongkan jika tidak ada minimum"
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
                hint="Opsional untuk promosi berkelanjutan"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.valid_until"
                label="Berlaku Sampai"
                variant="outlined"
                type="datetime-local"
                hint="Opsional untuk promosi berkelanjutan"
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
  valid_from: null,
  valid_until: null,
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
    valid_from: null,
    valid_until: null,
    active: true,
    promotion_rules: []
  }
  
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
  }
}

const save = async () => {
  if (!isValid.value) return

  try {
    saving.value = true

    // Generate promotion_rules based on type
    formData.value.promotion_rules = generatePromotionRules()

    if (props.mode === 'create') {
      await PromotionsApi.createPromotion(formData.value)
    } else if (props.promotion) {
      await PromotionsApi.updatePromotion(props.promotion.id_promotion, formData.value as UpdatePromotionRequest)
    }

    emit('saved')
  } catch (error) {
    console.error('Save error:', error)
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
