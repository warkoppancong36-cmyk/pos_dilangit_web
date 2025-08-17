<template>
  <VDialog
    :model-value="modelValue"
    max-width="600px"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard class="ppn-dialog coffee-dialog">
      <VCardTitle class="ppn-dialog-title coffee-header">
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-percentage"
              class="text-white"
            />
            <span class="text-h6 text-white">{{ editMode ? 'Edit PPN' : 'Buat PPN Baru' }}</span>
          </div>
        </div>
      </VCardTitle>
      
      <VCardText>
        <VForm ref="formRef" @submit.prevent="onSubmit">
          <VContainer>
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="localFormData.name"
                  label="Nama PPN *"
                  :rules="nameRules"
                  required
                  color="primary"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="localFormData.nominal"
                  label="Nominal (%) *"
                  type="number"
                  :rules="nominalRules"
                  required
                  min="0"
                  max="100"
                  step="0.01"
                  color="primary"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="localFormData.status"
                  label="Status"
                  color="primary"
                />
              </VCol>
              <VCol cols="12">
                <VTextarea
                  v-model="localFormData.description"
                  label="Deskripsi"
                  rows="3"
                  color="primary"
                />
              </VCol>
              <VCol cols="12">
                <VSwitch
                  v-model="localFormData.active"
                  label="Status Aktif"
                  color="success"
                />
              </VCol>
            </VRow>

            <!-- Error Message -->
            <VAlert
              v-if="errorMessage"
              type="error"
              variant="outlined"
              class="mt-4"
              closable
              @click:close="$emit('clearError')"
            >
              {{ errorMessage }}
            </VAlert>
          </VContainer>
        </VForm>
      </VCardText>
      
      <VDivider />

      <!-- Actions -->
      <VCardActions class="pa-6">
        <VSpacer />
        <VBtn
          variant="outlined"
          class="coffee-secondary"
          @click="$emit('cancel')"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          class="coffee-primary"
          :loading="loading"
          @click="onSubmit"
        >
          {{ editMode ? 'Perbarui' : 'Simpan' }} PPN
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import type { PpnFormData } from '@/composables/usePpn';
import { reactive, ref, watch } from 'vue';

// Props
const props = defineProps<{
  modelValue: boolean
  editMode: boolean
  formData: PpnFormData
  loading: boolean
  errorMessage?: string
}>()

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'save': [data: PpnFormData]
  'cancel': []
  'clearError': []
}>()

// Form reference
const formRef = ref()

// Local form data - reactive copy of props
const localFormData = reactive({ ...props.formData })

// Watch props changes to sync with local data
watch(() => props.formData, (newData) => {
  Object.assign(localFormData, newData)
}, { deep: true })

// Validation rules
const nameRules = [
  (v: string) => !!v || 'Nama PPN wajib diisi',
  (v: string) => v?.length >= 2 || 'Nama PPN minimal 2 karakter',
  (v: string) => v?.length <= 100 || 'Nama PPN maksimal 100 karakter'
]

const nominalRules = [
  (v: number | string) => {
    const num = Number(v)
    return !isNaN(num) && v !== null && v !== undefined && v !== '' || 'Nominal PPN wajib diisi'
  },
  (v: number | string) => {
    const num = Number(v)
    return num >= 0 || 'Nominal PPN tidak boleh negatif'
  },
  (v: number | string) => {
    const num = Number(v)
    return num <= 100 || 'Nominal PPN tidak boleh lebih dari 100%'
  },
  (v: number | string) => {
    const str = v.toString()
    return /^\d+(\.\d{1,2})?$/.test(str) || 'Nominal PPN maksimal 2 digit desimal'
  }
]

// Methods
const onSubmit = async () => {
  const { valid } = await formRef.value?.validate()
  if (valid) {
    emit('save', localFormData)
  }
}
</script>

<style scoped>
.ppn-dialog {
  border: 1px solid rgba(176, 113, 36, 0.1);
}

.ppn-dialog-title {
  background: linear-gradient(135deg, rgba(176, 113, 36, 0.05) 0%, rgba(212, 172, 113, 0.05) 100%);
  border-bottom: 1px solid rgba(176, 113, 36, 0.1);
  color: #8D4B00;
}

/* Dark theme adjustments */
.v-theme--dark .ppn-dialog {
  border: 1px solid rgba(212, 172, 113, 0.1);
}

.v-theme--dark .ppn-dialog-title {
  background: linear-gradient(135deg, rgba(212, 172, 113, 0.05) 0%, rgba(176, 113, 36, 0.05) 100%);
  border-bottom: 1px solid rgba(212, 172, 113, 0.1);
  color: #D4AC71;
}
</style>
