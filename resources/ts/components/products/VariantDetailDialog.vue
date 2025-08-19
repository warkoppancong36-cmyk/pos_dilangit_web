<template>
  <v-dialog v-model="dialog" max-width="600px">
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2">
          <v-icon icon="mdi-eye" />
          <span>Detail Variant</span>
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
          <v-row>
            <v-col cols="12">
              <v-card variant="outlined">
                <v-card-text>
                  <div class="d-flex align-center gap-3 mb-4">
                    <v-avatar size="48" color="primary" variant="tonal">
                      <v-icon icon="mdi-package-variant" />
                    </v-avatar>
                    <div>
                      <h3 class="text-h6">{{ variant.name }}</h3>
                      <p class="text-body-2 text-medium-emphasis">{{ variant.sku }}</p>
                    </div>
                  </div>

                  <v-divider class="mb-4" />

                  <v-row>
                    <v-col cols="6">
                      <div class="text-caption text-medium-emphasis">Harga</div>
                      <div class="text-h6">{{ formatRupiah(variant.price || 0) }}</div>
                    </v-col>
                    <v-col cols="6">
                      <div class="text-caption text-medium-emphasis">Stok Saat Ini</div>
                      <div class="text-h6">{{ variant.current_stock || 0 }} pcs</div>
                    </v-col>
                    <v-col cols="6">
                      <div class="text-caption text-medium-emphasis">Stok Minimum</div>
                      <div class="text-body-1">{{ variant.min_stock || 0 }} pcs</div>
                    </v-col>
                    <v-col cols="6">
                      <div class="text-caption text-medium-emphasis">Status</div>
                      <v-chip
                        :color="variant.is_active ? 'success' : 'error'"
                        size="small"
                        variant="tonal"
                      >
                        {{ variant.is_active ? 'Aktif' : 'Nonaktif' }}
                      </v-chip>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
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
  price?: number
  current_stock?: number
  min_stock?: number
  is_active?: boolean
}

interface Props {
  modelValue: boolean
  variant?: Variant | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Dialog state
const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Methods
const formatRupiah = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}

const closeDialog = () => {
  dialog.value = false
}
</script>
