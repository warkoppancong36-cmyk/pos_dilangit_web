<template>
  <v-dialog v-model="dialog" max-width="600px" persistent>
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2">
          <v-icon icon="mdi-puzzle" />
          <span>Kelola Komposisi Variant</span>
        </div>
        <v-btn
          icon="mdi-close"
          variant="text"
          @click="closeDialog"
        />
      </v-card-title>

      <v-divider />

      <v-card-text class="pt-4">
        <div v-if="variant">
          <v-alert type="info" variant="tonal" class="mb-4">
            <div class="d-flex align-center">
              <v-icon icon="mdi-package-variant" class="me-2" />
              <div>
                <strong>Variant:</strong> {{ variant.name }}
                <br>
                <small>SKU: {{ variant.sku }}</small>
              </div>
            </div>
          </v-alert>

          <div class="text-center py-8">
            <v-icon icon="mdi-wrench" size="64" color="grey" />
            <h3 class="text-h6 mt-4">Fitur Komposisi</h3>
            <p class="text-body-2 text-medium-emphasis">
              Kelola komposisi item untuk variant ini akan segera tersedia
            </p>
          </div>
        </div>
      </v-card-text>

      <v-card-actions class="px-6 pb-6">
        <v-spacer />
        <v-btn
          variant="outlined"
          @click="closeDialog"
        >
          Tutup
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Variant {
  id?: number
  id_variant?: number
  name: string
  sku: string
}

interface Props {
  modelValue: boolean
  variant?: Variant | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'save'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Dialog state
const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Methods
const closeDialog = () => {
  dialog.value = false
}
</script>
