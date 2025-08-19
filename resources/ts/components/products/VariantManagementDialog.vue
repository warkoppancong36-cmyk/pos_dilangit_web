<template>
  <v-dialog v-model="dialog" max-width="1200px" persistent>
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2">
          <v-icon icon="mdi-cog" />
          <span>Kelola Variant</span>
        </div>
        <v-btn
          icon="mdi-close"
          variant="text"
          @click="closeDialog"
        />
      </v-card-title>

      <v-divider />

      <v-card-text class="pt-4">
        <div v-if="product">
          <v-alert type="info" variant="tonal" class="mb-4">
            <div class="d-flex align-center">
              <v-icon icon="mdi-package" class="me-2" />
              <div>
                <strong>Produk:</strong> {{ product.name }}
                <br>
                <small>Kelola semua variant untuk produk ini</small>
              </div>
            </div>
          </v-alert>

          <div class="text-center py-8">
            <v-icon icon="mdi-wrench" size="64" color="grey" />
            <h3 class="text-h6 mt-4">Fitur Management</h3>
            <p class="text-body-2 text-medium-emphasis">
              Advanced variant management features akan segera tersedia
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
        <v-btn
          color="primary"
          @click="handleRefresh"
        >
          Refresh
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Product {
  id: number
  name: string
  code?: string
}

interface Props {
  modelValue: boolean
  product?: Product | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'refresh'): void
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

const handleRefresh = () => {
  emit('refresh')
  closeDialog()
}
</script>
