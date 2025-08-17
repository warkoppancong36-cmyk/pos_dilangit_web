<template>
  <VDialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="1400"
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-3">
          <VIcon icon="mdi-compare" size="24" />
          <span>HPP Methods Comparison - {{ productName || 'Product' }}</span>
        </div>
        
        <VBtn
          icon="mdi-close"
          size="small"
          variant="text"
          @click="$emit('update:modelValue', false)"
        />
      </VCardTitle>

      <VCardText>
        <!-- Comparison Summary -->
        <VCard v-if="hasComparisonData" variant="outlined" class="mb-4">
          <VCardTitle>
            <VIcon icon="mdi-chart-bar" class="me-2" />
            HPP Comparison Summary
          </VCardTitle>
          <VCardText>
            <VRow>
              <VCol cols="12" md="3">
                <div class="text-center">
                  <div class="text-subtitle-2 text-grey-darken-1">Current Price</div>
                  <div class="text-h5 font-weight-bold text-primary">
                    {{ formatCurrency(currentHPPComparison.current_price) }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-center">
                  <div class="text-subtitle-2 text-grey-darken-1">Current Cost</div>
                  <div class="text-h5 font-weight-bold text-info">
                    {{ formatCurrency(currentHPPComparison.current_cost) }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-center">
                  <div class="text-subtitle-2 text-grey-darken-1">Current Margin</div>
                  <div class="text-h5 font-weight-bold" 
                       :class="currentMargin >= 0 ? 'text-success' : 'text-error'">
                    {{ formatCurrency(currentMargin) }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-center">
                  <div class="text-subtitle-2 text-grey-darken-1">Margin %</div>
                  <div class="text-h5 font-weight-bold"
                       :class="currentMarginPercent >= 0 ? 'text-success' : 'text-error'">
                    {{ currentMarginPercent.toFixed(1) }}%
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- HPP Methods Comparison Cards -->
        <VRow v-if="hasComparisonData">
          <VCol 
            v-for="(method, key) in comparisonMethods" 
            :key="key"
            cols="12" 
            md="4"
          >
            <VCard 
              :color="method.color" 
              variant="tonal" 
              class="h-100"
              :class="{ 'v-card--selected': selectedMethod === key }"
            >
              <VCardTitle class="d-flex justify-space-between align-center">
                <div>
                  <VIcon :icon="method.icon" class="me-2" />
                  {{ method.title }}
                </div>
                <VChip 
                  :color="method.color" 
                  variant="elevated" 
                  size="small"
                >
                  {{ key.toUpperCase() }}
                </VChip>
              </VCardTitle>
              
              <VCardText>
                <div class="mb-4">
                  <div class="text-subtitle-2 text-grey-darken-1 mb-1">HPP Cost</div>
                  <div class="text-h4 font-weight-bold">
                    {{ formatCurrency(currentHPPComparison.hpp_methods[key as keyof typeof currentHPPComparison.hpp_methods]) }}
                  </div>
                </div>

                <div class="mb-4">
                  <div class="text-subtitle-2 text-grey-darken-1 mb-1">vs Current Cost</div>
                  <VChip 
                    :color="getComparisonColor(currentHPPComparison.hpp_methods[key as keyof typeof currentHPPComparison.hpp_methods] - currentHPPComparison.current_cost)"
                    variant="tonal"
                    size="small"
                  >
                    {{ formatDifference(currentHPPComparison.hpp_methods[key as keyof typeof currentHPPComparison.hpp_methods] - currentHPPComparison.current_cost) }}
                  </VChip>
                </div>

                <div class="mb-4">
                  <div class="text-subtitle-2 text-grey-darken-1 mb-1">Items Count</div>
                  <div class="text-h6">
                    {{ currentHPPComparison.breakdown[key as keyof typeof currentHPPComparison.breakdown]?.items.length || 0 }} items
                  </div>
                </div>

                <VBtn
                  :color="method.color"
                  variant="elevated"
                  block
                  @click="selectMethod(key as 'current' | 'latest_purchase' | 'average_purchase')"
                  :prepend-icon="selectedMethod === key ? 'mdi-check' : 'mdi-eye'"
                >
                  {{ selectedMethod === key ? 'Selected' : 'View Details' }}
                </VBtn>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <!-- Detailed Breakdown -->
        <VCard v-if="hasComparisonData && selectedBreakdown" class="mt-4">
          <VCardTitle>
            <VIcon icon="mdi-format-list-bulleted" class="me-2" />
            {{ comparisonMethods[selectedMethod]?.title }} - Detailed Breakdown
          </VCardTitle>
          
          <VDataTable
            :headers="detailHeaders"
            :items="selectedBreakdown.items"
            :items-per-page="15"
            class="elevation-0"
          >
            <template #item.item_name="{ item }">
              <div class="d-flex flex-column">
                <span class="font-weight-medium">{{ item.item_name }}</span>
                <span class="text-caption text-grey-darken-1">{{ item.item_code }}</span>
              </div>
            </template>

            <template #item.quantity_needed="{ item }">
              <span class="font-weight-medium">
                {{ Number(item.quantity_needed).toFixed(3) }} {{ item.unit }}
              </span>
            </template>

            <template #item.cost_per_unit="{ item }">
              <span>{{ formatCurrency(item.cost_per_unit) }}</span>
            </template>

            <template #item.total_cost="{ item }">
              <VChip
                :color="item.is_critical ? 'error' : 'primary'"
                variant="tonal"
                size="small"
              >
                {{ formatCurrency(item.total_cost) }}
              </VChip>
            </template>

            <template #item.percentage="{ item }">
              <div class="text-end">
                <VProgressLinear
                  :model-value="(item.total_cost / selectedBreakdown.total_hpp) * 100"
                  :color="item.is_critical ? 'error' : 'primary'"
                  height="4"
                  rounded
                />
                <span class="text-caption mt-1">
                  {{ ((item.total_cost / selectedBreakdown.total_hpp) * 100).toFixed(1) }}%
                </span>
              </div>
            </template>

            <template #bottom>
              <div class="v-data-table__footer">
                <VDivider />
                <div class="pa-4 d-flex justify-space-between align-center">
                  <div class="text-subtitle-1 font-weight-bold">
                    Total HPP ({{ comparisonMethods[selectedMethod]?.title }}): 
                    {{ formatCurrency(selectedBreakdown.total_hpp) }}
                  </div>
                  <div class="d-flex gap-2">
                    <VChip color="info" variant="elevated" size="small">
                      {{ selectedBreakdown.items.length }} items
                    </VChip>
                    <VBtn
                      :color="comparisonMethods[selectedMethod]?.color"
                      variant="elevated"
                      size="small"
                      @click="applySelectedMethod"
                      :loading="loading"
                      prepend-icon="mdi-check"
                    >
                      Apply This HPP
                    </VBtn>
                  </div>
                </div>
              </div>
            </template>
          </VDataTable>
        </VCard>

        <!-- Loading State -->
        <VCard v-if="loading && !hasComparisonData">
          <VCardText class="text-center py-12">
            <VProgressCircular indeterminate size="64" />
            <div class="mt-4 text-h6">Loading HPP comparison...</div>
          </VCardText>
        </VCard>

        <!-- Empty State -->
        <VCard v-if="!loading && !hasComparisonData">
          <VCardText class="text-center py-12">
            <VIcon icon="mdi-compare" size="64" class="text-grey-lighten-1 mb-4" />
            <div class="text-h6 text-grey-darken-1 mb-2">No Comparison Data Available</div>
            <div class="text-body-2 text-grey-darken-1 mb-4">
              Unable to load HPP methods comparison for this product
            </div>
            <VBtn
              color="primary"
              variant="elevated"
              @click="loadComparison"
              prepend-icon="mdi-refresh"
            >
              Try Again
            </VBtn>
          </VCardText>
        </VCard>
      </VCardText>

      <VCardActions class="px-6 pb-6">
        <VSpacer />
        <VBtn
          color="grey-darken-1"
          variant="text"
          @click="$emit('update:modelValue', false)"
        >
          Close
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { useHPP } from '@/composables/useHPP'
import { computed, ref, watch } from 'vue'

// Props
interface Props {
  modelValue: boolean
  productId: number | null
  productName?: string
}

const props = withDefaults(defineProps<Props>(), {
  productName: '',
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'hpp-updated': []
}>()

// Composables
const {
  loading,
  currentHPPComparison,
  hasComparisonData,
  compareHPPMethods,
  updateProductHPP,
  formatCurrency,
} = useHPP()

// State
const selectedMethod = ref<'current' | 'latest_purchase' | 'average_purchase'>('latest_purchase')

// Data
const comparisonMethods = {
  current: {
    title: 'Current Cost',
    icon: 'mdi-database',
    color: 'primary',
  },
  latest_purchase: {
    title: 'Latest Purchase',
    icon: 'mdi-clock-outline',
    color: 'success',
  },
  average_purchase: {
    title: 'Average Purchase',
    icon: 'mdi-chart-line',
    color: 'warning',
  },
}

const detailHeaders = [
  { title: 'Item', value: 'item_name', sortable: true },
  { title: 'Quantity', value: 'quantity_needed', align: 'end' },
  { title: 'Cost/Unit', value: 'cost_per_unit', align: 'end' },
  { title: 'Total Cost', value: 'total_cost', align: 'end' },
  { title: 'Contribution', value: 'percentage', align: 'end' },
]

// Computed
const currentMargin = computed(() => {
  if (!hasComparisonData.value) return 0
  return currentHPPComparison.value!.current_price - currentHPPComparison.value!.current_cost
})

const currentMarginPercent = computed(() => {
  if (!hasComparisonData.value || currentHPPComparison.value!.current_cost === 0) return 0
  return (currentMargin.value / currentHPPComparison.value!.current_cost) * 100
})

const selectedBreakdown = computed(() => {
  if (!hasComparisonData.value) return null
  return currentHPPComparison.value!.breakdown[selectedMethod.value]
})

// Methods
const loadComparison = async () => {
  if (props.productId) {
    await compareHPPMethods(props.productId)
  }
}

const selectMethod = (method: 'current' | 'latest_purchase' | 'average_purchase') => {
  selectedMethod.value = method
}

const applySelectedMethod = async () => {
  if (!props.productId) return
  
  // Convert method name for API
  const apiMethod = selectedMethod.value === 'latest_purchase' ? 'latest' : 
                   selectedMethod.value === 'average_purchase' ? 'average' : 'current'
  
  await updateProductHPP(props.productId, apiMethod)
  emit('hpp-updated')
  
  // Reload comparison after update
  await loadComparison()
}

const getComparisonColor = (difference: number): string => {
  if (difference > 0) return 'error'
  if (difference < 0) return 'success'
  return 'grey'
}

const formatDifference = (difference: number): string => {
  const sign = difference > 0 ? '+' : ''
  return `${sign}${formatCurrency(difference)}`
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue && props.productId) {
    loadComparison()
  }
})

watch(() => props.productId, (newValue) => {
  if (newValue && props.modelValue) {
    loadComparison()
  }
})
</script>

<style scoped>
.v-card--selected {
  border: 2px solid rgb(var(--v-theme-primary));
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.v-data-table__footer {
  border-top: 1px solid rgb(var(--v-theme-surface-variant));
}
</style>
