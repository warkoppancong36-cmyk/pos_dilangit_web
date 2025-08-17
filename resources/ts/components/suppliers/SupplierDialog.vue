<script setup lang="ts">
import type { Supplier, SupplierCreateData, SupplierUpdateData } from '@/utils/api/SuppliersApi'

interface Props {
  modelValue: boolean
  supplier?: Supplier | null
  loading?: boolean
  errorMessage?: string | null
  useDialogError?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'save', data: SupplierCreateData | SupplierUpdateData): void
  (e: 'clear-error'): void
  (e: 'close'): void
}

const props = defineProps<Props>()

const emit = defineEmits<Emits>()

// Dialog state
const localValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Form data - use individual refs for better binding
const code = ref('')
const name = ref('')
const email = ref('')
const phone = ref('')
const address = ref('')
const city = ref('')
const province = ref('')
const postal_code = ref('')
const contact_person = ref('')
const tax_number = ref('')
const bank_name = ref('')
const bank_account = ref('')
const bank_account_name = ref('')
const notes = ref('')
const status = ref<'active' | 'inactive'>('active')

// Computed form data
const formData = computed(() => ({
  code: code.value,
  name: name.value,
  email: email.value,
  phone: phone.value,
  address: address.value,
  city: city.value,
  province: province.value,
  postal_code: postal_code.value,
  contact_person: contact_person.value,
  tax_number: tax_number.value,
  bank_name: bank_name.value,
  bank_account: bank_account.value,
  bank_account_name: bank_account_name.value,
  notes: notes.value,
  status: status.value
}))

// Debug: watch form data changes
watch(formData, (newData) => {
  console.log('Form data changed:', newData)
}, { deep: true })

// Form validation
const isFormValid = ref(false)
const formRef = ref()

// Validation rules
const rules = {
  code: [
    (v: string) => !!v || 'Kode supplier wajib diisi',
    (v: string) => (v && v.length >= 2) || 'Kode supplier minimal 2 karakter',
    (v: string) => (v && v.length <= 20) || 'Kode supplier maksimal 20 karakter'
  ],
  name: [
    (v: string) => !!v || 'Nama supplier wajib diisi',
    (v: string) => (v && v.length >= 2) || 'Nama supplier minimal 2 karakter',
    (v: string) => (v && v.length <= 100) || 'Nama supplier maksimal 100 karakter'
  ],
  email: [
    (v: string) => {
      if (!v) return true // Email is optional
      const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return pattern.test(v) || 'Format email tidak valid'
    }
  ],
  phone: [
    (v: string) => {
      if (!v) return true // Phone is optional
      const pattern = /^[\d\s\-\+\(\)]+$/
      return pattern.test(v) || 'Format nomor telepon tidak valid'
    }
  ],
  postal_code: [
    (v: string) => {
      if (!v) return true // Postal code is optional
      const pattern = /^\d{5}$/
      return pattern.test(v) || 'Kode pos harus 5 digit'
    }
  ]
}

// Status options
const statusOptions = [
  { title: 'Aktif', value: 'active' },
  { title: 'Nonaktif', value: 'inactive' }
]

// Computed properties
const isEditMode = computed(() => !!props.supplier)
const dialogTitle = computed(() => isEditMode.value ? 'Edit Supplier' : 'Tambah Supplier')
const saveButtonText = computed(() => isEditMode.value ? 'Update' : 'Simpan')
const displayErrorMessage = computed(() => props.errorMessage || null)

// Reset form
const resetForm = () => {
  code.value = ''
  name.value = ''
  email.value = ''
  phone.value = ''
  address.value = ''
  city.value = ''
  province.value = ''
  postal_code.value = ''
  contact_person.value = ''
  tax_number.value = ''
  bank_name.value = ''
  bank_account.value = ''
  bank_account_name.value = ''
  notes.value = ''
  status.value = 'active'
  
  // Reset validation
  nextTick(() => {
    formRef.value?.resetValidation()
  })
}

// Watch for supplier changes
watch(
  () => props.supplier,
  (newSupplier) => {
    if (newSupplier) {
      // Edit mode - populate form with supplier data
      code.value = newSupplier.code
      name.value = newSupplier.name
      email.value = newSupplier.email || ''
      phone.value = newSupplier.phone || ''
      address.value = newSupplier.address || ''
      city.value = newSupplier.city || ''
      province.value = newSupplier.province || ''
      postal_code.value = newSupplier.postal_code || ''
      contact_person.value = newSupplier.contact_person || ''
      tax_number.value = newSupplier.tax_number || ''
      bank_name.value = newSupplier.bank_name || ''
      bank_account.value = newSupplier.bank_account || ''
      bank_account_name.value = newSupplier.bank_account_name || ''
      notes.value = newSupplier.notes || ''
      status.value = newSupplier.status
    } else {
      // Create mode - reset form
      resetForm()
    }
  },
  { immediate: true }
)

// Watch for dialog close
watch(localValue, (newValue) => {
  if (!newValue) {
    resetForm()
  }
})

// Handle save
const handleSave = async () => {
  // Debug: log form data sebelum validation
  console.log('Form data before save:', formData.value)
  console.log('Form data stringified:', JSON.stringify(formData.value))
  
  const { valid } = await formRef.value?.validate()
  
  console.log('Form validation result:', valid)
  
  if (valid) {
    const payload = { ...formData.value }
    console.log('Payload to emit:', payload)
    emit('save', payload)
  }
}

// Handle dialog close
const handleClose = () => {
  localValue.value = false
  emit('close')
}
</script>

<template>
  <VDialog
    v-model="localValue"
    max-width="900px"
    persistent
    scrollable
  >
    <VCard class="supplier-dialog coffee-dialog">
      <VCardTitle class="pa-6 pb-4 coffee-header">
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon
              :icon="isEditMode ? 'tabler-edit' : 'tabler-plus'"
              class="text-white"
            />
            <span class="text-h6 text-white">
              {{ dialogTitle }}
            </span>
          </div>
          <VBtn
            icon="tabler-x"
            variant="text"
            color="white"
            size="small"
            @click="handleClose"
          />
        </div>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-6">
        <VForm
          ref="formRef"
          v-model="isFormValid"
          @submit.prevent="handleSave"
        >
          <VRow>
            <!-- Basic Information -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-info-circle"
                  size="20"
                  class="coffee-icon"
                />
                Informasi Dasar
              </h6>
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="code"
                label="Kode Supplier *"
                :rules="rules.code"
                :disabled="isEditMode"
                placeholder="Masukkan kode supplier"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="8">
              <VTextField
                v-model="name"
                label="Nama Supplier *"
                :rules="rules.name"
                placeholder="Masukkan nama supplier"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="email"
                label="Email"
                :rules="rules.email"
                placeholder="supplier@email.com"
                type="email"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="phone"
                label="Nomor Telepon"
                :rules="rules.phone"
                placeholder="08123456789"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="8">
              <VTextField
                v-model="contact_person"
                label="Nama Kontak"
                placeholder="Nama person in charge"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VSelect
                v-model="status"
                label="Status *"
                :items="statusOptions"
                item-title="title"
                item-value="value"
                variant="outlined"
              />
            </VCol>

            <!-- Address Information -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 mt-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-map-pin"
                  size="20"
                  class="coffee-icon"
                />
                Informasi Alamat
              </h6>
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="address"
                label="Alamat"
                placeholder="Masukkan alamat lengkap"
                rows="3"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="city"
                label="Kota"
                placeholder="Nama kota"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="province"
                label="Provinsi"
                placeholder="Nama provinsi"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="postal_code"
                label="Kode Pos"
                :rules="rules.postal_code"
                placeholder="12345"
                variant="outlined"
              />
            </VCol>

            <!-- Financial Information -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 mt-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-credit-card"
                  size="20"
                  class="coffee-icon"
                />
                Informasi Keuangan
              </h6>
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="tax_number"
                label="NPWP"
                placeholder="Nomor NPWP"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="bank_name"
                label="Nama Bank"
                placeholder="Nama bank"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="bank_account"
                label="Nomor Rekening"
                placeholder="Nomor rekening bank"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="bank_account_name"
                label="Nama Pemilik Rekening"
                placeholder="Nama pemilik rekening"
                variant="outlined"
              />
            </VCol>

            <!-- Additional Information -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 mt-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-notes"
                  size="20"
                  class="coffee-icon"
                />
                Informasi Tambahan
              </h6>
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="notes"
                label="Catatan"
                placeholder="Catatan tambahan tentang supplier"
                rows="3"
                variant="outlined"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-6">
        <VSpacer />
        <VBtn
          color="secondary"
          variant="outlined"
          class="coffee-secondary"
          @click="handleClose"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          class="coffee-primary"
          :loading="loading"
          :disabled="!isFormValid"
          @click="handleSave"
        >
          {{ saveButtonText }}
        </VBtn>
      </VCardActions>
    </VCard>

    <!-- Error Popup - Snackbar Style (Default) -->
    <VSnackbar
      v-if="!useDialogError"
      :model-value="!!displayErrorMessage"
      @update:model-value="!$event && $emit('clear-error')"
      color="error"
      location="top right"
      :timeout="6000"
      vertical
      multi-line
      min-width="320"
      max-width="500"
      class="coffee-error"
    >
      <div class="d-flex align-center">
        <VIcon 
          icon="tabler-alert-circle" 
          class="me-3"
          size="28"
        />
        <div>
          <div class="text-h6 font-weight-bold">Error!</div>
          <div class="text-subtitle-1">{{ displayErrorMessage }}</div>
        </div>
      </div>
      
      <template #actions>
        <VBtn
          icon="tabler-x"
          size="small"
          variant="text"
          @click="$emit('clear-error')"
        />
      </template>
    </VSnackbar>
  </VDialog>
</template>

<style lang="scss" scoped>
// Global styles are imported from @core/dialog-styles.scss
// Component-specific styles can be added here if needed
</style>
