<template>
  <VDialog
    :model-value="show"
    max-width="600px"
    persistent
    @update:model-value="!$event && $emit('close')"
  >
    <VCard>
      <VCardTitle class="d-flex align-center gap-2">
        <VIcon icon="tabler-package" class="text-primary" />
        <span>Update Stock - {{ baseProduct?.name }}</span>
      </VCardTitle>
      
      <VDivider />
      
      <VCardText>
        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          closable
          class="mb-4"
          @click:close="error = ''"
        >
          {{ error }}
        </VAlert>

        <!-- Current Stock Display -->
        <VCard variant="tonal" color="info" class="mb-4">
          <VCardText>
            <div class="text-subtitle-2 mb-2">Current Information</div>
            <VRow>
              <VCol cols="6">
                <div class="text-caption text-medium-emphasis">Current Stock:</div>
                <div class="text-h6 font-weight-bold">{{ baseProduct?.current_stock || 0 }} {{ baseProduct?.unit }}</div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-medium-emphasis">Min Stock:</div>
                <div class="text-h6 font-weight-bold">{{ baseProduct?.min_stock || 0 }} {{ baseProduct?.unit }}</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VForm @submit.prevent="handleSubmit">
          <VRow>
            <!-- Movement Type -->
            <VCol cols="12">
              <VRadioGroup
                v-model="form.movement_type"
                label="Movement Type"
                required
                :rules="[v => !!v || 'Movement type is required']"
              >
                <VRadio
                  label="Stock In (+)"
                  value="stock_in"
                />
                <VRadio
                  label="Stock Out (-)"
                  value="stock_out"
                />
                <VRadio
                  label="Stock Adjustment"
                  value="adjustment"
                />
              </VRadioGroup>
            </VCol>

            <!-- Quantity -->
            <VCol cols="12">
              <VTextField
                v-model="form.quantity"
                :label="form.movement_type === 'adjustment' ? 'New Stock Quantity' : 'Quantity'"
                type="number"
                step="0.001"
                min="0"
                variant="outlined"
                required
                :suffix="baseProduct?.unit"
                :rules="[
                  v => !!v || 'Quantity is required',
                  v => v >= 0 || 'Quantity must be 0 or greater'
                ]"
              />
              
              <!-- New stock preview -->
              <VCard
                v-if="form.quantity && form.movement_type"
                variant="tonal"
                color="success"
                class="mt-2"
              >
                <VCardText class="py-2">
                  <div class="text-caption">New stock will be:</div>
                  <div class="text-subtitle-1 font-weight-bold">
                    {{ calculateNewStock() }} {{ baseProduct?.unit }}
                    <VChip
                      v-if="calculateNewStock() < (baseProduct?.min_stock || 0)"
                      color="error"
                      size="small"
                      variant="tonal"
                      class="ml-2"
                    >
                      Below minimum
                    </VChip>
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <!-- Reason -->
            <VCol cols="12">
              <VTextField
                v-model="form.reason"
                label="Reason"
                variant="outlined"
                placeholder="Reason for stock movement"
              />
            </VCol>

            <!-- Notes -->
            <VCol cols="12">
              <VTextarea
                v-model="form.notes"
                label="Notes"
                variant="outlined"
                rows="3"
                placeholder="Optional notes for this stock movement"
              />
            </VCol>

            <!-- Reference Number -->
            <VCol cols="12">
              <VTextField
                v-model="form.reference_number"
                label="Reference Number"
                variant="outlined"
                placeholder="Optional reference number"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions>
        <VSpacer />
        <VBtn
          variant="outlined"
          @click="$emit('close')"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          @click="handleSubmit"
        >
          Update Stock
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
<script>
import { ref, reactive, computed, watch } from 'vue'
import { useBaseProductStore } from '@/stores/baseProduct'

export default {
  name: 'StockUpdateModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    baseProduct: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'updated'],
  setup(props, { emit }) {
    const baseProductStore = useBaseProductStore()
    
    const loading = ref(false)
    const error = ref('')
    
    const form = reactive({
      movement_type: '',
      quantity: '',
      reason: '',
      notes: '',
      reference_number: ''
    })

    const calculateNewStock = () => {
      if (!form.quantity || !props.baseProduct) return 0
      
      const currentStock = parseFloat(props.baseProduct.current_stock) || 0
      const quantity = parseFloat(form.quantity) || 0
      
      switch (form.movement_type) {
        case 'stock_in':
          return currentStock + quantity
        case 'stock_out':
          return Math.max(0, currentStock - quantity)
        case 'adjustment':
          return quantity
        default:
          return currentStock
      }
    }

    const resetForm = () => {
      form.movement_type = ''
      form.quantity = ''
      form.reason = ''
      form.notes = ''
      form.reference_number = ''
      error.value = ''
    }

    const handleSubmit = async () => {
      if (!form.movement_type || !form.quantity) {
        error.value = 'Please fill in all required fields'
        return
      }

      if (form.quantity < 0) {
        error.value = 'Quantity must be 0 or greater'
        return
      }

      loading.value = true
      error.value = ''

      try {
        const payload = {
          movement_type: form.movement_type,
          quantity: Number(form.quantity),
          reason: form.reason || null,
          notes: form.notes || null,
          reference_number: form.reference_number || null
        }

        await baseProductStore.updateStock(props.baseProduct.id, payload)
        
        emit('updated')
        emit('close')
        resetForm()
      } catch (err) {
        error.value = err.message || 'Failed to update stock'
      } finally {
        loading.value = false
      }
    }

    // Watch for modal show changes to reset form
    watch(() => props.show, (newVal) => {
      if (newVal) {
        resetForm()
      }
    })

    return {
      loading,
      error,
      form,
      calculateNewStock,
      handleSubmit,
      resetForm
    }
  }
}
</script>

<style scoped>
.v-card {
  border-radius: 12px;
}

.v-card-title {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 16px 24px;
}

.v-card-title .v-icon {
  color: white;
}
</style>
