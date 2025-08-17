<template>
  <VDialog :model-value="isOpen" max-width="700px" persistent @update:model-value="$emit('update:isOpen', $event)">
    <VCard class="category-dialog coffee-dialog">
      <VCardTitle class="pa-6 pb-4 coffee-header">
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon 
              :icon="editMode ? 'tabler-edit' : 'tabler-plus'" 
              class="text-white"
            />
            <span class="text-h6 text-white">
              {{ editMode ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
            </span>
          </div>
        </div>
      </VCardTitle>
      <VCardText class="pa-6">
        <VForm ref="formRef" @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12" class="text-center">
              <div class="image-upload-section">
                <VAvatar
                  size="120"
                  rounded="lg"
                  class="mb-4"
                  :style="{ backgroundColor: imagePreview ? 'transparent' : '#FBF9F6' }"
                >
                  <VImg
                    v-if="imagePreview"
                    :src="imagePreview"
                    cover
                    :color='"grey-lighten-2"'
                  />
                  <VIcon
                    v-else
                    icon="mdi-camera-plus"
                    size="48"
                    color="grey-lighten-1"
                  />
                </VAvatar>
                <div class="mb-4">
                  <VFileInput
                    label="Upload Gambar Kategori"
                    variant="outlined"
                    accept="image/*"
                    show-size
                    clearable
                    @change="onImageUpload"
                    @click:clear="removeImage"
                  />
                  <div class="text-caption text-medium-emphasis mt-1">
                    Format: JPG, PNG, GIF. Maksimal 2MB
                  </div>
                </div>
              </div>
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="localFormData.name"
                label="Nama Kategori"
                variant="outlined"
                prepend-inner-icon="mdi-shape"
                :rules="nameRules"
                required
              />
            </VCol>
            <VCol cols="12">
              <VTextarea
                v-model="localFormData.description"
                label="Deskripsi Kategori"
                variant="outlined"
                prepend-inner-icon="mdi-text"
                rows="4"
                auto-grow
                counter="1000"
                :rules="descriptionRules"
                placeholder="Jelaskan tentang kategori ini..."
              />
            </VCol>
            <VCol cols="12">
              <VCard variant="outlined" class="pa-4">
                <div class="d-flex align-center justify-space-between">
                  <div>
                    <h4 class="text-subtitle-1 font-weight-medium mb-1">Status Kategori</h4>
                    <p class="text-body-2 text-medium-emphasis">
                      {{ localFormData.active ? 'Kategori akan ditampilkan dan dapat digunakan' : 'Kategori akan disembunyikan' }}
                    </p>
                  </div>
                  <VSwitch 
                    v-model="localFormData.active"
                    color="success" 
                    inset 
                  />
                </div>
              </VCard>
            </VCol>

            <!-- Error Message -->
            <VCol v-if="errorMessage" cols="12">
              <VAlert
                type="error"
                variant="outlined"
                closable
                @click:close="$emit('clear-error')"
              >
                {{ errorMessage }}
              </VAlert>
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
          @click="$emit('cancel')"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          class="coffee-primary"
          :loading="saveLoading"
          @click="onSubmit"
        >
          {{ editMode ? 'Perbarui' : 'Simpan' }} Kategori
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import type { CategoryFormData } from '@/composables/useCategories';
import { reactive, ref, watch } from 'vue';

// Props
const props = defineProps<{
  isOpen: boolean
  editMode: boolean
  formData: CategoryFormData
  imagePreview: string | null
  errorMessage?: string
  saveLoading: boolean
}>()

// Emits
const emit = defineEmits<{
  'update:isOpen': [value: boolean]
  'save': [data: CategoryFormData]
  'image-upload': [files: File[]]
  'remove-image': []
  'cancel': []
  'clear-error': []
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
  (v: string) => !!v || 'Nama kategori wajib diisi',
  (v: string) => v?.length >= 2 || 'Nama kategori minimal 2 karakter',
  (v: string) => v?.length <= 100 || 'Nama kategori maksimal 100 karakter'
]

const descriptionRules = [
  (v: string) => !v || v.length <= 1000 || 'Deskripsi maksimal 1000 karakter'
]

// Methods
const onSubmit = async () => {
  const { valid } = await formRef.value?.validate()
  if (valid) {
    emit('save', localFormData)
  }
}

const onImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (files && files.length > 0) {
    emit('image-upload', Array.from(files))
  }
}

const removeImage = () => {
  emit('remove-image')
}
</script>
