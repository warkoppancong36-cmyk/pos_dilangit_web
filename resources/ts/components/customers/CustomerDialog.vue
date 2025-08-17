<template>
  <VDialog
    :model-value="dialog"
    max-width="600px"
    persistent
    @update:model-value="$emit('update:dialog', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ editMode ? 'Edit Customer' : 'Tambah Customer Baru' }}</span>
        <VBtn
          icon="tabler-x"
          variant="text"
          size="small"
          @click="closeDialog"
        />
      </VCardTitle>

      <VDivider />

      <VForm
        ref="formRef"
        @submit.prevent="saveCustomer"
      >
        <VCardText>
          <!-- Error Alert -->
          <VAlert
            v-if="modalErrorMessage"
            type="error"
            variant="tonal"
            closable
            class="mb-4"
            @click:close="clearModalError"
          >
            {{ modalErrorMessage }}
          </VAlert>

          <VRow>
            <!-- Customer Code (Auto-generated, shown only in edit mode) -->
            <VCol v-if="editMode" cols="12" md="6">
              <VTextField
                :model-value="selectedCustomer?.customer_code"
                label="Kode Customer"
                readonly
                variant="outlined"
                prepend-inner-icon="tabler-id"
              />
            </VCol>

            <!-- Name -->
            <VCol :cols="editMode ? 6 : 12">
              <VTextField
                v-model="formData.name"
                label="Nama Customer *"
                placeholder="Masukkan nama lengkap"
                :rules="nameRules"
                variant="outlined"
                prepend-inner-icon="tabler-user"
                autofocus
              />
            </VCol>
          </VRow>

          <VRow>
            <!-- Email -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.email"
                label="Email"
                placeholder="customer@email.com"
                type="email"
                :rules="emailRules"
                variant="outlined"
                prepend-inner-icon="tabler-mail"
              />
            </VCol>

            <!-- Phone -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.phone"
                label="Nomor Telepon"
                placeholder="08xxxxxxxxxx"
                :rules="phoneRules"
                variant="outlined"
                prepend-inner-icon="tabler-phone"
              />
            </VCol>
          </VRow>

          <VRow>
            <!-- Birth Date -->
            <VCol cols="12" md="4">
              <VTextField
                v-model="formData.birth_date"
                label="Tanggal Lahir"
                type="date"
                variant="outlined"
                prepend-inner-icon="tabler-calendar"
              />
            </VCol>

            <!-- Gender -->
            <VCol cols="12" md="4">
              <VSelect
                v-model="formData.gender"
                label="Jenis Kelamin"
                :items="genderOptions"
                clearable
                variant="outlined"
                prepend-inner-icon="tabler-gender-male"
              />
            </VCol>

            <!-- Status -->
            <VCol cols="12" md="4">
              <VSwitch
                v-model="formData.active"
                label="Customer Aktif"
                color="success"
                inset
                hide-details
              />
            </VCol>
          </VRow>

          <!-- Address Section -->
          <VRow>
            <VCol cols="12">
              <VTextarea
                v-model="formData.address"
                label="Alamat"
                placeholder="Masukkan alamat lengkap"
                rows="2"
                auto-grow
                variant="outlined"
                prepend-inner-icon="tabler-map-pin"
              />
            </VCol>
          </VRow>

          <VRow>
            <!-- City -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.city"
                label="Kota"
                placeholder="Nama kota"
                variant="outlined"
                prepend-inner-icon="tabler-building"
              />
            </VCol>

            <!-- Postal Code -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.postal_code"
                label="Kode Pos"
                placeholder="12345"
                variant="outlined"
                prepend-inner-icon="tabler-mailbox"
              />
            </VCol>
          </VRow>

          <!-- Notes -->
          <VRow>
            <VCol cols="12">
              <VTextarea
                v-model="formData.notes"
                label="Catatan"
                placeholder="Catatan tambahan tentang customer"
                rows="2"
                auto-grow
                variant="outlined"
                prepend-inner-icon="tabler-notes"
              />
            </VCol>
          </VRow>

          <!-- Customer Preferences (Advanced) -->
          <VExpansionPanels v-if="editMode" class="mt-4">
            <VExpansionPanel
              title="Preferensi Customer"
              text="Atur preferensi dan pengaturan khusus customer"
            >
              <template #text>
                <VRow>
                  <VCol cols="12">
                    <VTextarea
                      v-model="preferencesJson"
                      label="Preferensi (JSON)"
                      placeholder='{"newsletter": true, "sms_notifications": false}'
                      rows="3"
                      variant="outlined"
                      :error="preferencesError"
                      :error-messages="preferencesError ? 'Format JSON tidak valid' : ''"
                    />
                  </VCol>
                </VRow>
              </template>
            </VExpansionPanel>
          </VExpansionPanels>

          <!-- Customer Statistics (Edit Mode Only) -->
          <VCard v-if="editMode && selectedCustomer" variant="tonal" class="mt-4">
            <VCardText>
              <div class="text-h6 mb-3">Statistik Customer</div>
              <VRow>
                <VCol cols="6" md="3">
                  <div class="text-center">
                    <div class="text-h6 text-primary">{{ selectedCustomer.total_visits }}</div>
                    <div class="text-caption text-medium-emphasis">Total Kunjungan</div>
                  </div>
                </VCol>
                <VCol cols="6" md="3">
                  <div class="text-center">
                    <div class="text-h6 text-success">{{ formatCurrency(selectedCustomer.total_spent) }}</div>
                    <div class="text-caption text-medium-emphasis">Total Belanja</div>
                  </div>
                </VCol>
                <VCol cols="6" md="3">
                  <div class="text-center">
                    <VChip
                      :color="getLoyaltyColor(selectedCustomer.loyalty_level || '')"
                      size="small"
                      variant="elevated"
                    >
                      {{ getLoyaltyLabel(selectedCustomer.loyalty_level || '') }}
                    </VChip>
                    <div class="text-caption text-medium-emphasis mt-1">Level Loyalitas</div>
                  </div>
                </VCol>
                <VCol cols="6" md="3">
                  <div class="text-center">
                    <VChip
                      :color="getStatusColor(selectedCustomer.customer_status || '')"
                      size="small"
                      variant="tonal"
                    >
                      {{ getStatusLabel(selectedCustomer.customer_status || '') }}
                    </VChip>
                    <div class="text-caption text-medium-emphasis mt-1">Status Customer</div>
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn
            variant="outlined"
            @click="closeDialog"
          >
            Batal
          </VBtn>
          <VBtn
            type="submit"
            color="primary"
            :loading="saveLoading"
          >
            {{ editMode ? 'Perbarui' : 'Simpan' }}
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import type { Customer, CustomerFormData } from '@/composables/useCustomers'
import { computed, ref, watch } from 'vue'

interface Props {
  dialog: boolean
  editMode: boolean
  selectedCustomer: Customer | null
  formData: CustomerFormData
  saveLoading: boolean
  modalErrorMessage: string
  nameRules: Array<(v: string) => boolean | string>
  emailRules: Array<(v: string) => boolean | string>
  phoneRules: Array<(v: string) => boolean | string>
  genderOptions: Array<{ title: string; value: string }>
}

interface Emits {
  (e: 'update:dialog', value: boolean): void
  (e: 'update:form-data', data: CustomerFormData): void
  (e: 'save'): void
  (e: 'close'): void
  (e: 'clear-modal-error'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Form ref
const formRef = ref()

// Local computed for form data
const formData = computed({
  get: () => props.formData,
  set: (value) => emit('update:form-data', value)
})

// Preferences JSON handling
const preferencesJson = ref('')
const preferencesError = ref(false)

// Watch for customer selection to update preferences
watch(
  () => props.selectedCustomer,
  (customer) => {
    if (customer?.preferences) {
      try {
        preferencesJson.value = JSON.stringify(customer.preferences, null, 2)
        preferencesError.value = false
      } catch {
        preferencesJson.value = ''
        preferencesError.value = false
      }
    } else {
      preferencesJson.value = ''
      preferencesError.value = false
    }
  },
  { immediate: true }
)

// Watch preferences JSON changes
watch(preferencesJson, (newValue) => {
  if (!newValue.trim()) {
    formData.value.preferences = {}
    preferencesError.value = false
    return
  }

  try {
    const parsed = JSON.parse(newValue)
    formData.value.preferences = parsed
    preferencesError.value = false
  } catch {
    preferencesError.value = true
  }
})

// Methods
const saveCustomer = async () => {
  const { valid } = await formRef.value.validate()
  if (valid && !preferencesError.value) {
    emit('save')
  }
}

const closeDialog = () => {
  emit('close')
}

const clearModalError = () => {
  emit('clear-modal-error')
}

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const getLoyaltyColor = (level: string): string => {
  switch (level) {
    case 'platinum': return 'purple'
    case 'gold': return 'amber'
    case 'silver': return 'blue-grey'
    case 'bronze': return 'orange'
    case 'basic': return 'grey'
    default: return 'grey'
  }
}

const getLoyaltyLabel = (level: string): string => {
  switch (level) {
    case 'platinum': return 'Platinum'
    case 'gold': return 'Gold'
    case 'silver': return 'Silver'
    case 'bronze': return 'Bronze'
    case 'basic': return 'Basic'
    default: return '-'
  }
}

const getStatusColor = (status: string): string => {
  switch (status) {
    case 'active': return 'success'
    case 'regular': return 'info'
    case 'inactive': return 'warning'
    case 'dormant': return 'error'
    default: return 'grey'
  }
}

const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'active': return 'Aktif'
    case 'regular': return 'Reguler'
    case 'inactive': return 'Tidak Aktif'
    case 'dormant': return 'Dorman'
    default: return '-'
  }
}
</script>
