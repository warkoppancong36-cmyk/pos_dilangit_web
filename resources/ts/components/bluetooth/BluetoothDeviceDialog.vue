<template>
  <VDialog
    :model-value="modelValue"
    max-width="600px"
    persistent
    @update:model-value="$emit('update:model-value', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span class="text-h5">{{ editMode ? 'Edit Perangkat Bluetooth' : 'Tambah Perangkat Bluetooth' }}</span>
        <VBtn
          icon
          variant="text"
          @click="$emit('close')"
        >
          <VIcon icon="mdi-close" />
        </VBtn>
      </VCardTitle>

      <VCardText>
        <VForm ref="form" @submit.prevent="$emit('save')">
          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
            closable
            @click:close="$emit('clear-error')"
          >
            {{ errorMessage }}
          </VAlert>

          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="formData.device_name"
                label="Nama Perangkat *"
                placeholder="contoh: Kitchen Printer"
                :rules="[v => !!v || 'Nama perangkat wajib diisi']"
                required
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="formData.device_address"
                label="Alamat MAC *"
                placeholder="contoh: 00:11:22:33:44:55"
                :rules="[
                  v => !!v || 'Alamat MAC wajib diisi',
                  v => /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/.test(v) || 'Format alamat MAC tidak valid'
                ]"
                required
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                v-model="formData.device_type"
                :items="deviceTypes"
                label="Tipe Perangkat *"
                placeholder="Pilih tipe perangkat"
                :rules="[v => !!v || 'Tipe perangkat wajib dipilih']"
                required
              >
                <template #item="{ props, item }">
                  <VListItem v-bind="props">
                    <template #prepend>
                      <VIcon :icon="item.raw.icon" class="me-2" />
                    </template>
                  </VListItem>
                </template>
                <template #selection="{ item }">
                  <div class="d-flex align-center">
                    <VIcon :icon="item.raw.icon" class="me-2" size="20" />
                    {{ item.title }}
                  </div>
                </template>
              </VSelect>
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.manufacturer"
                label="Manufacturer"
                placeholder="contoh: Epson, Canon"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.model"
                label="Model"
                placeholder="contoh: TM-T82"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="formData.notes"
                label="Catatan"
                placeholder="Catatan tambahan tentang perangkat ini"
                rows="3"
              />
            </VCol>

            <VCol cols="12">
              <div class="d-flex gap-4">
                <VCheckbox
                  v-model="formData.is_active"
                  label="Aktif"
                  color="primary"
                />
                <VCheckbox
                  v-model="formData.is_default"
                  label="Jadikan Default"
                  color="primary"
                />
              </div>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VCardActions class="d-flex justify-end gap-3 pa-4">
        <VBtn
          variant="outlined"
          @click="$emit('close')"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          :loading="saveLoading"
          @click="$emit('save')"
        >
          {{ editMode ? 'Perbarui' : 'Simpan' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import type { BluetoothDeviceFormData } from '@/composables/useBluetoothDevices'

interface Props {
  modelValue: boolean
  editMode: boolean
  formData: BluetoothDeviceFormData
  saveLoading: boolean
  errorMessage: string
}

interface Emits {
  (e: 'update:model-value', value: boolean): void
  (e: 'save'): void
  (e: 'close'): void
  (e: 'clear-error'): void
}

defineProps<Props>()
defineEmits<Emits>()

const deviceTypes = [
  { title: 'Printer', value: 'printer', icon: 'mdi-printer' },
  { title: 'Scanner', value: 'scanner', icon: 'mdi-qrcode-scan' },
  { title: 'Cash Drawer', value: 'cash_drawer', icon: 'mdi-cash-register' },
  { title: 'Scale', value: 'scale', icon: 'mdi-scale-bathroom' },
  { title: 'Other', value: 'other', icon: 'mdi-devices' }
]
</script>
