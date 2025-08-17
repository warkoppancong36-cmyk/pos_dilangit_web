<script setup lang="ts">
import { getAuthToken } from '@/utils/auth'
import axios from 'axios'
import { computed, ref, watch } from 'vue'

// Props
interface Props {
  modelValue: boolean
  purchase: any
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits(['update:modelValue', 'success'])

// Reactive data
const loading = ref(false)
const submitting = ref(false)
const deliveryDate = ref(new Date().toISOString().split('T')[0])
const purchaseItems = ref<any[]>([])

// Computed
const isOpen = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})

const canSubmit = computed(() => {
  return deliveryDate.value && getItemsToReceiveCount() > 0 && !submitting.value
})

// Table headers
const itemHeaders = [
  { title: 'Item', key: 'item_name', sortable: false, width: '250px' },
  { title: 'Ordered', key: 'quantity_ordered', sortable: false, width: '110px' },
  { title: 'Received', key: 'quantity_received', sortable: false, width: '110px' },
  { title: 'Remaining', key: 'remaining', sortable: false, width: '110px' },
  { title: 'Receive Qty', key: 'receive_quantity', sortable: false, width: '140px' },
  { title: 'Quality', key: 'quality_check', sortable: false, width: '140px' },
  { title: 'Notes', key: 'notes', sortable: false, width: '200px' },
  { title: 'Status', key: 'status', sortable: false, width: '120px' },
]

// Quality options
const qualityOptions = [
  { title: '✅ Good', value: 'good' },
  { title: '❌ Damaged', value: 'damaged' },
  { title: '⏰ Expired', value: 'expired' },
  { title: '⚠️ Issues', value: 'partial' },
]

// Methods
const getRemainingQuantity = (item: any) => {
  return item.quantity_ordered - (item.quantity_received || 0)
}

const getRemainingClass = (item: any) => {
  const remaining = getRemainingQuantity(item)
  if (remaining <= 0)
    return 'text-success'
  if (remaining < item.quantity_ordered * 0.5)
    return 'text-warning'

  return ''
}

const getItemsToReceiveCount = () => {
  return purchaseItems.value.filter(item =>
    item.receive_quantity && item.receive_quantity > 0,
  ).length
}

const validateReceiveQuantity = (item: any) => {
  const remaining = getRemainingQuantity(item)
  if (item.receive_quantity > remaining)
    item.receive_quantity = remaining

  if (item.receive_quantity < 0)
    item.receive_quantity = 0
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'warning',
    ordered: 'info',
    partial: 'primary',
    received: 'success',
    completed: 'success',
    cancelled: 'error',
  }

  return colors[status] || 'default'
}

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: 'Pending',
    ordered: 'Ordered',
    partial: 'Partial',
    received: 'Received',
    completed: 'Completed',
    cancelled: 'Cancelled',
  }

  return texts[status] || status
}

const getItemStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'warning',
    partial: 'primary',
    received: 'success',
  }

  return colors[status] || 'default'
}

const getItemStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: 'Pending',
    partial: 'Partial',
    received: 'Complete',
  }

  return texts[status] || status
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID')
}

const loadPurchaseItems = async () => {
  if (!props.purchase?.id_purchase)
    return

  loading.value = true
  try {
    // Debug: Check if props already has items
    console.log('Purchase props:', props.purchase)

    // If props already has items, use them directly
    if (props.purchase.items && props.purchase.items.length > 0) {
      console.log('Using items from props:', props.purchase.items)
      purchaseItems.value = props.purchase.items.map((item: any) => ({
        ...item,
        receive_quantity: 0,
        quality_condition: 'good',
        receive_notes: '',
      }))
      loading.value = false

      return
    }

    // Otherwise, fetch from API
    console.log('Fetching items from API...')

    const response = await axios.get(`/api/purchases/${props.purchase.id_purchase}`, {
      headers: {
        Authorization: `Bearer ${getAuthToken()}`,
      },
    })

    console.log('API Response:', response.data)

    if (response.data.success && response.data.data?.items) {
      console.log('Using items from API data.data:', response.data.data.items)
      purchaseItems.value = response.data.data.items.map((item: any) => ({
        ...item,
        receive_quantity: 0,
        quality_condition: 'good',
        receive_notes: '',
      }))
    }
  }
  catch (error) {
    console.error('Error loading purchase items:', error)
  }
  finally {
    loading.value = false
  }
}

const submitReceive = async () => {
  if (!canSubmit.value)
    return

  // Prepare items to receive
  const itemsToReceive = purchaseItems.value
    .filter(item => item.receive_quantity && item.receive_quantity > 0)
    .map(item => ({
      id_purchase_item: item.id_purchase_item,
      quantity_received: item.receive_quantity,
      quality_check: {
        condition: item.quality_condition,
        checked_at: new Date().toISOString(),
        checked_by: 'current_user',
      },
      notes: item.receive_notes || null,
    }))

  if (itemsToReceive.length === 0)
    return

  submitting.value = true
  try {
    const response = await axios.post(`/api/purchases/${props.purchase.id_purchase}/receive`, {
      items: itemsToReceive,
      delivery_date: deliveryDate.value,
    }, {
      headers: {
        Authorization: `Bearer ${getAuthToken()}`,
      },
    })

    if (response.data.success) {
      emit('success', response.data)
      closeDialog()
    }
  }
  catch (error: any) {
    console.error('Error receiving items:', error)

    // Handle error - you might want to show a snackbar
  }
  finally {
    submitting.value = false
  }
}

const closeDialog = () => {
  isOpen.value = false

  // Reset form
  purchaseItems.value = []
  deliveryDate.value = new Date().toISOString().split('T')[0]
}

// Watch for dialog open
watch(isOpen, newValue => {
  if (newValue && props.purchase)
    loadPurchaseItems()
})
</script>

<template>
  <VDialog
    v-model="isOpen"
    max-width="1400px"
    persistent
  >
    <VCard>
      <VCardTitle class="pa-4">
        <div class="d-flex align-center gap-3">
          <VIcon icon="mdi-package-variant" />
          <span>Receive Items - Purchase #{{ purchase?.purchase_number }}</span>
        </div>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-4">
        <!-- Purchase Info -->
        <VCard
          variant="outlined"
          class="mb-4"
        >
          <VCardText class="pa-3">
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-body-2 text-medium-emphasis">
                  Supplier
                </div>
                <div class="text-h6">
                  {{ purchase?.supplier?.name }}
                </div>
              </div>
              <div>
                <div class="text-body-2 text-medium-emphasis">
                  Order Date
                </div>
                <div class="text-body-1">
                  {{ formatDate(purchase?.purchase_date) }}
                </div>
              </div>
              <div>
                <div class="text-body-2 text-medium-emphasis">
                  Status
                </div>
                <VChip
                  :color="getStatusColor(purchase?.status)"
                  variant="tonal"
                  size="small"
                >
                  {{ getStatusText(purchase?.status) }}
                </VChip>
              </div>
            </div>
          </VCardText>
        </VCard>

        <!-- Delivery Date -->
        <VRow class="mb-4">
          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="deliveryDate"
              label="Delivery Date"
              type="date"
              variant="outlined"
              :rules="[v => !!v || 'Delivery date is required']"
              required
            />
          </VCol>
        </VRow>

        <!-- Items Table -->
        <div class="text-h6 mb-3">
          Items to Receive
        </div>

        <VDataTable
          :headers="itemHeaders"
          :items="purchaseItems"
          :loading="loading"
          item-key="id_purchase_item"
          class="border rounded"
          density="compact"
        >
          <!-- Item Name -->
          <template #item.item_name="{ item }">
            <div>
              <div class="font-weight-medium">
                {{ item.item?.name }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ item.item?.item_code }}
              </div>
            </div>
          </template>

          <!-- Quantity Ordered -->
          <template #item.quantity_ordered="{ item }">
            <span class="font-weight-medium">
              {{ parseInt(item.quantity_ordered) }} {{ item.item?.unit }}
            </span>
          </template>

          <!-- Already Received -->
          <template #item.quantity_received="{ item }">
            <span :class="item.quantity_received > 0 ? 'text-success' : ''">
              {{ item.quantity_received || 0 }} {{ item.item?.unit }}
            </span>
          </template>

          <!-- Remaining -->
          <template #item.remaining="{ item }">
            <span
              class="font-weight-medium"
              :class="getRemainingClass(item)"
            >
              {{ getRemainingQuantity(item) }} {{ item.item?.unit }}
            </span>
          </template>

          <!-- Receive Quantity Input -->
          <template #item.receive_quantity="{ item }">
            <VTextField
              v-model.number="item.receive_quantity"
              type="number"
              :min="0"
              :max="getRemainingQuantity(item)"
              step="0.001"
              density="compact"
              variant="outlined"
              style="inline-size: 140px;"
              :disabled="getRemainingQuantity(item) <= 0"
              @input="validateReceiveQuantity(item)"
            />
          </template>

          <!-- Quality Check -->
          <template #item.quality_check="{ item }">
            <VSelect
              v-model="item.quality_condition"
              :items="qualityOptions"
              density="compact"
              variant="outlined"
              style="inline-size: 140px;"
              :disabled="!item.receive_quantity || item.receive_quantity <= 0"
            />
          </template>

          <!-- Notes -->
          <template #item.notes="{ item }">
            <VTextField
              v-model="item.receive_notes"
              placeholder="Notes..."
              density="compact"
              variant="outlined"
              style="inline-size: 200px;"
              :disabled="!item.receive_quantity || item.receive_quantity <= 0"
            />
          </template>

          <!-- Status -->
          <template #item.status="{ item }">
            <VChip
              :color="getItemStatusColor(item.status)"
              variant="tonal"
              size="small"
            >
              {{ getItemStatusText(item.status) }}
            </VChip>
          </template>
        </VDataTable>

        <!-- Summary -->
        <VCard
          variant="outlined"
          class="mt-4"
        >
          <VCardText class="pa-3">
            <div class="text-body-2 text-medium-emphasis mb-2">
              Receive Summary
            </div>
            <div class="d-flex justify-space-between">
              <span>Items to receive:</span>
              <span class="font-weight-medium">{{ getItemsToReceiveCount() }}</span>
            </div>
          </VCardText>
        </VCard>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="outlined"
          :disabled="submitting"
          @click="closeDialog"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          :loading="submitting"
          :disabled="!canSubmit"
          @click="submitReceive"
        >
          <VIcon
            start
            icon="mdi-check"
          />
          Confirm Receipt
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
