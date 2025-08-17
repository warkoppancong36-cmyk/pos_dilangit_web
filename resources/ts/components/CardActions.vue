<script setup lang="ts">
interface Props {
  editColor?: string
  editIcon?: string
  editText?: string
  toggleColor?: string
  toggleIcon?: string
  toggleText?: string
  deleteColor?: string
  deleteIcon?: string
  showEdit?: boolean
  showToggle?: boolean
  showDelete?: boolean
  editLoading?: boolean
  toggleLoading?: boolean
  deleteLoading?: boolean
  variant?: 'text' | 'outlined' | 'flat' | 'elevated' | 'tonal' | 'plain'
  size?: 'x-small' | 'small' | 'default' | 'large' | 'x-large'
}

interface Emits {
  (e: 'edit'): void
  (e: 'toggle'): void
  (e: 'delete'): void
}

const props = withDefaults(defineProps<Props>(), {
  editColor: 'primary',
  editIcon: 'mdi-pencil',
  editText: 'Edit',
  toggleColor: 'warning',
  toggleIcon: 'mdi-eye-off',
  toggleText: 'Toggle',
  deleteColor: 'error',
  deleteIcon: 'mdi-delete',
  showEdit: true,
  showToggle: true,
  showDelete: true,
  editLoading: false,
  toggleLoading: false,
  deleteLoading: false,
  variant: 'outlined',
  size: 'small'
})

const emit = defineEmits<Emits>()

const handleEdit = () => {
  emit('edit')
}

const handleToggle = () => {
  emit('toggle')
}

const handleDelete = () => {
  emit('delete')
}
</script>

<template>
  <div class="d-flex gap-1 align-center">
    <VBtn
      v-if="showEdit"
      :color="editColor"
      :variant="variant"
      :size="size"
      :prepend-icon="editIcon"
      :loading="editLoading"
      @click="handleEdit"
    >
      {{ editText }}
    </VBtn>
    
    <VBtn
      v-if="showToggle"
      :color="toggleColor"
      :variant="variant"
      :size="size"
      :prepend-icon="toggleIcon"
      :loading="toggleLoading"
      @click="handleToggle"
    >
      {{ toggleText }}
    </VBtn>

    <VSpacer v-if="showEdit || showToggle" />
    
    <VBtn
      v-if="showDelete"
      :color="deleteColor"
      :variant="variant"
      :size="size"
      :icon="deleteIcon"
      :loading="deleteLoading"
      @click="handleDelete"
    />
  </div>
</template>
