<template>
  <VDialog
    :model-value="modelValue"
    max-width="600px"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>Update Stock</span>
        <VBtn
          icon="tabler-x"
          variant="text"
          @click="$emit('update:modelValue', false)"
        />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-6">
        <p>Stock update modal - Under development</p>
      </VCardText>

      <VDivider />

      <VCardActions>
        <VSpacer />
        <VBtn
          variant="text"
          @click="$emit('update:modelValue', false)"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          variant="elevated"
          @click="$emit('update:modelValue', false)"
        >
          Save
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
interface Props {
  modelValue: boolean
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
}

defineProps<Props>()
defineEmits<Emits>()
</script>
