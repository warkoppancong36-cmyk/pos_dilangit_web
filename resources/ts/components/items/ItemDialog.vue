<script setup lang="ts">
import type { ItemFormData } from '@/composables/useItems'

interface Props {
  modelValue: boolean
  editMode?: boolean
  loading?: boolean
  formData: ItemFormData
  errorMessage?: string
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'save'): void
  (e: 'close'): void
  (e: 'clear-error'): void
}

const props = withDefaults(defineProps<Props>(), {
  editMode: false,
  loading: false,
  errorMessage: ''
})

const emit = defineEmits<Emits>()

const formRef = ref()

const localValue = computed({
  get: () => props.modelValue,
  set: (value: boolean) => {
    if (!value) {
      emit('close')
    }
    emit('update:modelValue', value)
  }
})

const unitOptions = [
  'kg', 'gram', 'liter', 'ml', 'butir', 'pcs', 'pack', 'dus', 'meter', 'lembar'
]

const storageLocationOptions = [
  'Gudang A - Rak 1',
  'Gudang A - Rak 2', 
  'Gudang A - Rak 3',
  'Gudang B - Rak 1',
  'Gudang B - Rak 2',
  'Gudang B - Rak 3',
  'Kulkas - Rak 1',
  'Kulkas - Rak 2',
  'Freezer - Rak 1',
  'Freezer - Rak 2'
]

const handleSave = async () => {
  console.log('handleSave called')
  console.log('formData:', props.formData)
  
  // Manual validation
  if (!props.formData.name?.trim()) {
    console.log('Name is required')
    return
  }
  
  if (!props.formData.unit?.trim()) {
    console.log('Unit is required')
    return
  }
  
  // Validate form if ref exists
  if (formRef.value) {
    const { valid } = await formRef.value.validate()
    if (!valid) {
      console.log('Form validation failed')
      return
    }
  }
  
  emit('clear-error')
  emit('save')
}

const addProperty = () => {
  if (!props.formData.properties) {
    props.formData.properties = {}
  }
  // Add a new empty property
  const newKey = `property_${Date.now()}`
  props.formData.properties[newKey] = ''
}

const removeProperty = (key: string) => {
  if (props.formData.properties) {
    delete props.formData.properties[key]
  }
}

const updatePropertyKey = (oldKey: string, newKey: string) => {
  if (props.formData.properties && oldKey !== newKey) {
    const value = props.formData.properties[oldKey]
    delete props.formData.properties[oldKey]
    props.formData.properties[newKey] = value
  }
}
</script>

<template>
  <VDialog
    v-model="localValue"
    max-width="900px"
    persistent
    scrollable
  >
    <VCard class="item-dialog coffee-dialog">
      <!-- Header -->
      <VCardTitle class="d-flex align-center justify-space-between coffee-header">
        <div class="d-flex align-center gap-2">
          <VIcon
            :icon="editMode ? 'tabler-edit' : 'tabler-plus'"
            class="text-white"
          />
          <span class="text-white">
            {{ editMode ? 'Edit Item' : 'Tambah Item Baru' }}
          </span>
        </div>
        <VBtn
          icon="tabler-x"
          variant="text"
          color="white"
          @click="localValue = false"
        />
      </VCardTitle>

      <VDivider />

      <!-- Form Content -->
      <VCardText class="pa-6">
        <VForm ref="formRef" @submit.prevent="handleSave">
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

            <VCol cols="12" md="8">
              <VTextField
                v-model="formData.name"
                label="Nama Item"
                placeholder="Masukkan nama item"
                variant="outlined"
                required
                :rules="[v => !!v || 'Nama item harus diisi']"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VSelect
                v-model="formData.unit"
                :items="unitOptions"
                label="Satuan"
                placeholder="Pilih satuan"
                variant="outlined"
                required
                :rules="[v => !!v || 'Satuan harus dipilih']"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="formData.description"
                label="Deskripsi"
                placeholder="Masukkan deskripsi item"
                rows="3"
                variant="outlined"
              />
            </VCol>

            <!-- Location -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-building-warehouse"
                  size="20"
                  class="coffee-icon"
                />
                Lokasi & Tanggal
              </h6>
            </VCol>

            <VCol cols="12" md="6">
              <VSelect
                v-model="formData.storage_location"
                :items="storageLocationOptions"
                label="Lokasi Penyimpanan"
                placeholder="Pilih lokasi"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.expiry_date"
                label="Tanggal Kadaluwarsa"
                type="date"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="6">
              <div class="d-flex align-center">
                <VSwitch
                  v-model="formData.active"
                  color="success"
                  class="me-3"
                />
                <VLabel class="text-body-1">Item Aktif</VLabel>
              </div>
            </VCol>

            <!-- Station Availability -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-tools-kitchen-2"
                  size="20"
                  class="coffee-icon"
                />
                Ketersediaan Station
              </h6>
            </VCol>

            <VCol cols="12" md="6">
              <div class="d-flex align-center">
                <VSwitch
                  v-model="formData.available_in_kitchen"
                  color="warning"
                  class="me-3"
                />
                <VLabel class="text-body-1">
                  <VIcon icon="tabler-chef-hat" class="me-2" size="18" />
                  Tersedia di Kitchen
                </VLabel>
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <div class="d-flex align-center">
                <VSwitch
                  v-model="formData.available_in_bar"
                  color="info"
                  class="me-3"
                />
                <VLabel class="text-body-1">
                  <VIcon icon="tabler-glass-cocktail" class="me-2" size="18" />
                  Tersedia di Bar
                </VLabel>
              </div>
            </VCol>

            <!-- Additional Properties -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-settings"
                  size="20"
                  class="coffee-icon"
                />
                Properti Tambahan
              </h6>
            </VCol>

            <VCol cols="12">
              <div class="text-caption text-medium-emphasis mb-3">
                Tambahkan informasi tambahan seperti kandungan, spesifikasi, dll.
              </div>
              
              <VBtn
                variant="outlined" 
                prepend-icon="tabler-plus"
                class="coffee-secondary mb-3"
                @click="addProperty"
              >
                Tambah Properti
              </VBtn>

              <div v-if="formData.properties" v-for="(value, key) in formData.properties" :key="key" class="mb-3">
                <VRow no-gutters>
                  <VCol cols="4">
                    <VTextField
                      :model-value="key"
                      @update:model-value="updatePropertyKey(key, $event)"
                      label="Nama Properti"
                      variant="outlined"
                      density="compact"
                    />
                  </VCol>
                  <VCol cols="7" class="ps-2">
                    <VTextField
                      v-model="formData.properties[key]"
                      label="Nilai"
                      variant="outlined"
                      density="compact"
                    />
                  </VCol>
                  <VCol cols="1" class="ps-2 d-flex align-center">
                    <VBtn
                      icon="tabler-trash"
                      color="error"
                      variant="text"
                      size="small"
                      @click="removeProperty(key)"
                    />
                  </VCol>
                </VRow>
              </div>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <!-- Actions -->
      <VCardActions class="pa-6">
        <VSpacer />
        <VBtn
          variant="outlined"
          class="coffee-secondary"
          @click="localValue = false"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          class="coffee-primary"
          :loading="loading"
          @click="handleSave"
        >
          {{ editMode ? 'Perbarui' : 'Simpan' }} Item
        </VBtn>
      </VCardActions>
    </VCard>

    <!-- Error Snackbar -->
    <VSnackbar
      :model-value="!!errorMessage"
      @update:model-value="!$event && emit('clear-error')"
      color="error"
      location="top right"
      :timeout="6000"
      vertical
      multi-line
      min-width="320"
      max-width="500"
    >
      <div class="d-flex align-center">
        <VIcon 
          icon="tabler-alert-circle" 
          class="me-3"
          size="28"
        />
        <div>
          <div class="text-h6 font-weight-bold">Error!</div>
          <div class="text-subtitle-1">{{ errorMessage }}</div>
        </div>
      </div>
      
      <template #actions>
        <VBtn
          icon="tabler-x"
          size="small"
          variant="text"
          @click="emit('clear-error')"
        />
      </template>
    </VSnackbar>
  </VDialog>
</template>

<style scoped>
/* ItemDialog styles handled by @core/dialog-styles.scss */
</style>
