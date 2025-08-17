<template>
  <VDialog
    v-model="localDialog"
    persistent
    max-width="800px"
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center">
        <span class="text-h5">Production Capacity Analysis</span>
        <VBtn
          icon="tabler-x"
          size="small"
          variant="text"
          @click="localDialog = false"
        />
      </VCardTitle>

      <VDivider />

      <VCardText>
        <div v-if="loading" class="text-center py-8">
          <VProgressCircular
            indeterminate
            color="primary"
            size="64"
          />
          <div class="mt-4">Calculating production capacity...</div>
        </div>

        <div v-else-if="productionCapacity">
          <!-- Production Summary -->
          <VAlert
            :color="productionCapacity.can_produce > 0 ? 'success' : 'error'"
            variant="tonal"
            class="mb-6"
          >
            <VAlertTitle>
              {{ selectedProduct?.name || 'Product' }} - Production Capacity
            </VAlertTitle>
            <div class="mt-2">
              <div class="text-h4 font-weight-bold">
                {{ formatNumber(productionCapacity.can_produce) }} units
              </div>
              <div class="text-body-2">
                Maximum production with current inventory
              </div>
            </div>
          </VAlert>

          <!-- Limiting Factors -->
          <div v-if="productionCapacity.limiting_items.length > 0" class="mb-6">
            <VCardTitle class="px-0">Limiting Factors</VCardTitle>
            <VAlert
              color="warning"
              variant="tonal"
              class="mb-4"
            >
              <VAlertTitle>Production Limited By:</VAlertTitle>
              <VChipGroup class="mt-2">
                <VChip
                  v-for="item in productionCapacity.limiting_items"
                  :key="item"
                  color="warning"
                  size="small"
                  variant="tonal"
                >
                  {{ item }}
                </VChip>
              </VChipGroup>
            </VAlert>
          </div>

          <!-- Detailed Analysis -->
          <VCardTitle class="px-0 mb-4">Item Requirements Analysis</VCardTitle>
          
          <VDataTable
            :headers="detailHeaders"
            :items="productionCapacity.details"
            :items-per-page="10"
            class="elevation-1"
          >
            <!-- Item name with status indicator -->
            <template #item.item_name="{ item }">
              <div class="d-flex align-center gap-2">
                <VIcon
                  :icon="getStatusIcon(item)"
                  :color="getStatusColor(item)"
                  size="20"
                />
                <div>
                  <div class="font-weight-medium">{{ item.item_name }}</div>
                  <div class="text-caption text-medium-emphasis">
                    ID: {{ item.item_id }}
                  </div>
                </div>
              </div>
            </template>

            <!-- Available stock -->
            <template #item.available_stock="{ item }">
              <div class="text-end">
                <div class="font-weight-medium">{{ formatNumber(item.available_stock) }}</div>
                <div class="text-caption text-medium-emphasis">Available</div>
              </div>
            </template>

            <!-- Needed per product -->
            <template #item.needed_per_product="{ item }">
              <div class="text-end">
                <div class="font-weight-medium">{{ formatNumber(item.needed_per_product) }}</div>
                <div class="text-caption text-medium-emphasis">Per unit</div>
              </div>
            </template>

            <!-- Can produce -->
            <template #item.can_produce="{ item }">
              <div class="text-end">
                <VChip
                  :color="item.can_produce >= productionCapacity.can_produce ? 'success' : 'error'"
                  size="small"
                  variant="tonal"
                >
                  {{ formatNumber(item.can_produce) }}
                </VChip>
              </div>
            </template>

            <!-- Status indicator -->
            <template #item.status="{ item }">
              <VChip
                :color="getStatusColor(item)"
                size="small"
                variant="tonal"
              >
                {{ getStatusText(item) }}
              </VChip>
            </template>
          </VDataTable>

          <!-- Production Recommendations -->
          <div class="mt-6">
            <VCardTitle class="px-0">Recommendations</VCardTitle>
            
            <div v-if="productionCapacity.can_produce === 0">
              <VAlert color="error" variant="tonal">
                <VAlertTitle>Cannot Produce</VAlertTitle>
                <div>One or more critical items are out of stock. Please restock the limiting items listed above.</div>
              </VAlert>
            </div>
            
            <div v-else-if="productionCapacity.can_produce < 10">
              <VAlert color="warning" variant="tonal">
                <VAlertTitle>Low Production Capacity</VAlertTitle>
                <div>Consider restocking items to increase production capacity.</div>
              </VAlert>
            </div>
            
            <div v-else>
              <VAlert color="success" variant="tonal">
                <VAlertTitle>Good Production Capacity</VAlertTitle>
                <div>Sufficient inventory for production. Consider planning production batches.</div>
              </VAlert>
            </div>
          </div>
        </div>

        <div v-else>
          <VAlert color="info" variant="tonal">
            <VAlertTitle>No Data Available</VAlertTitle>
            <div>Unable to calculate production capacity. Please ensure the product has items configured.</div>
          </VAlert>
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          color="primary"
          variant="outlined"
          @click="localDialog = false"
        >
          Close
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import type { ProductionCapacity } from '@/composables/useProductItems'
import { computed } from 'vue'

interface Props {
  dialog: boolean
  loading: boolean
  productionCapacity: ProductionCapacity | null
  selectedProduct: any
}

interface Emits {
  (e: 'update:dialog', value: boolean): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local dialog state
const localDialog = computed({
  get: () => props.dialog,
  set: (value) => emit('update:dialog', value)
})

// Table headers
const detailHeaders = [
  { title: 'Item', value: 'item_name', sortable: false },
  { title: 'Available', value: 'available_stock', align: 'end' as const },
  { title: 'Needed/Unit', value: 'needed_per_product', align: 'end' as const },
  { title: 'Can Produce', value: 'can_produce', align: 'end' as const },
  { title: 'Status', value: 'status', align: 'center' as const }
]

// Helper functions
const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(num)
}

const getStatusIcon = (item: any): string => {
  if (item.can_produce === 0) return 'tabler-alert-triangle'
  if (item.can_produce < 5) return 'tabler-alert-circle'
  return 'tabler-check-circle'
}

const getStatusColor = (item: any): string => {
  if (item.can_produce === 0) return 'error'
  if (item.can_produce < 5) return 'warning'
  return 'success'
}

const getStatusText = (item: any): string => {
  if (item.can_produce === 0) return 'Out of Stock'
  if (item.can_produce < 5) return 'Low Stock'
  return 'Sufficient'
}
</script>
