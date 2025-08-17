<script setup lang="ts">
interface Props {
  loading?: boolean
  editMode?: boolean
  cancelText?: string
  saveText?: string
  editText?: string
  showCancel?: boolean
  showSave?: boolean
  cancelIcon?: string
  saveIcon?: string
  editIcon?: string
  disabled?: boolean
}

interface Emits {
  (e: 'cancel'): void
  (e: 'save'): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  editMode: false,
  cancelText: 'Batal',
  saveText: 'Simpan',
  editText: 'Perbarui',
  showCancel: true,
  showSave: true,
  cancelIcon: 'mdi-close-circle',
  saveIcon: 'mdi-plus',
  editIcon: 'mdi-content-save',
  disabled: false
})

const emit = defineEmits<Emits>()

const handleCancel = () => {
  emit('cancel')
}

const handleSave = () => {
  emit('save')
}
</script>

<template>
  <VCardActions class="pa-6 pt-0">
    <VSpacer />
    
    <VBtn
      v-if="showCancel"
      variant="outlined"
      size="large"
      :prepend-icon="cancelIcon"
      :disabled="loading || disabled"
      @click="handleCancel"
    >
      {{ cancelText }}
    </VBtn>
    
    <VBtn
      v-if="showSave"
      color="primary"
      size="large"
      :prepend-icon="editMode ? editIcon : saveIcon"
      :loading="loading"
      :disabled="disabled"
      @click="handleSave"
    >
      {{ editMode ? editText : saveText }}
    </VBtn>
  </VCardActions>
</template>
