<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  modelValue: boolean
  title?: string
  message?: string
  itemName?: string
  loading?: boolean
  confirmText?: string
  cancelText?: string
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Konfirmasi Hapus',
  message: 'Apakah Anda yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.',
  itemName: '',
  loading: false,
  confirmText: 'Hapus',
  cancelText: 'Batal',
})

const emit = defineEmits<Emits>()

const dialog = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
})

const handleConfirm = () => {
  emit('confirm')
}

const handleCancel = () => {
  emit('cancel')
  dialog.value = false
}

const finalMessage = computed(() => {
  if (props.itemName)
    return `Apakah Anda yakin ingin menghapus "${props.itemName}"? Tindakan ini tidak dapat dibatalkan.`

  return props.message
})
</script>

<template>
  <VDialog
    v-model="dialog"
    max-width="500px"
    persistent
  >
    <VCard class="delete-confirmation-dialog">
      <VCardTitle class="pa-6 pb-4">
        <VIcon
          icon="mdi-alert-circle"
          color="error"
          class="me-2"
        />
        <span class="text-h5">{{ title }}</span>
      </VCardTitle>

      <VCardText class="pa-6 pt-4">
        <p class="text-body-1">
          {{ finalMessage }}
        </p>
        <slot name="additional-message" />
      </VCardText>

      <VCardActions class="pa-6 pt-0">
        <VSpacer />
        <VBtn
          variant="outlined"
          size="large"
          prepend-icon="mdi-close-circle"
          :disabled="loading"
          @click="handleCancel"
        >
          {{ cancelText }}
        </VBtn>
        <VBtn
          color="error"
          size="large"
          prepend-icon="mdi-delete"
          :loading="loading"
          :disabled="loading"
          @click="handleConfirm"
        >
          {{ confirmText }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.delete-confirmation-dialog .v-card-title {
  border-block-end: 1px solid rgba(var(--v-theme-on-surface), 0.12);
}
</style>
