<script setup lang="ts">
import { useHPP } from '@/composables/useHPP'
import axios from 'axios'
import { nextTick, ref, watch } from 'vue'

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
  'price-updated': []
}>()

// Composables
const {
  loading,
  currentHPPBreakdown,
  currentHPPSuggestion,
  hasHPPData,
  hasSuggestionData,
  getProductHPPBreakdown,
  updateProductHPP,
  calculateSuggestedPrice,
  updatePriceFromHPP,
  formatCurrency,
} = useHPP()

// State
const selectedMethod = ref<'current' | 'latest' | 'average'>('latest')
const markupPercentage = ref(0) // Will be loaded from database
const markupKey = ref(0) // Key untuk force re-render TextField

// Data
const hppMethods = [
  { title: 'Current Cost', value: 'current' },
  { title: 'Latest Purchase', value: 'latest' },
  { title: 'Average Purchase', value: 'average' },
]

const breakdownHeaders = [
  { title: 'Item', value: 'item_name', sortable: true },
  { title: 'Quantity', value: 'quantity_needed', align: 'end' },
  { title: 'Cost/Unit', value: 'cost_per_unit', align: 'end' },
  { title: 'Total Cost', value: 'total_cost', align: 'end' },
  { title: 'Priority', value: 'is_critical', align: 'center' },
  { title: 'Notes', value: 'notes' },
]

// Methods
const loadProductMarkupPercentage = async () => {
  if (props.productId) {
    try {
      const token = localStorage.getItem('token')

      const response = await axios.get(`/api/products/${props.productId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      })

      if (response.data.success && response.data.data) {
        const product = response.data.data

        if (product.markup_percentage !== null && product.markup_percentage !== undefined) {
          // Use stored markup percentage
          markupPercentage.value = Number(product.markup_percentage)
        }
        else if (product.price && product.cost && product.cost > 0) {
          // Calculate markup percentage from price and cost
          const calculatedMarkup = ((product.price - product.cost) / product.cost) * 100

          markupPercentage.value = Math.round(calculatedMarkup * 100) / 100
        }
        else {
          // Default fallback
          markupPercentage.value = 20
        }

        markupKey.value += 1 // Force re-render
      }
    }
    catch (error) {
      console.warn('Could not load markup percentage, using default:', error)
      markupPercentage.value = 20
    }
  }
}

const loadHPPBreakdown = async () => {
  if (props.productId) {
    await getProductHPPBreakdown(props.productId, selectedMethod.value)
    await loadProductMarkupPercentage()
  }
}

const updateHPP = async () => {
  if (props.productId) {
    await updateProductHPP(props.productId, selectedMethod.value)
    emit('hpp-updated')

    // Reload breakdown after update
    await loadHPPBreakdown()
  }
}

const calculateSuggestion = async () => {
  if (props.productId)
    await calculateSuggestedPrice(props.productId, markupPercentage.value)
}

const applyPriceSuggestion = async () => {
  if (props.productId) {
    try {
      const response = await updatePriceFromHPP(
        props.productId,
        selectedMethod.value,
        markupPercentage.value,
        true,
      )

      // Update markup percentage FIRST before any reload
      if (response?.data?.markup_percentage !== undefined) {
        markupPercentage.value = response.data.markup_percentage
        markupKey.value += 1
        await nextTick()
      }
      else if (response?.data?.new_price && currentHPPBreakdown.value?.total_hpp) {
        // Fallback: calculate manually if backend doesn't provide
        const newPrice = response.data.new_price
        const hpp = currentHPPBreakdown.value.total_hpp
        const actualMarkup = ((newPrice - hpp) / hpp) * 100

        markupPercentage.value = Math.round(actualMarkup * 100) / 100
        markupKey.value += 1
        await nextTick()
      }

      // Reload HPP breakdown after price update
      await loadHPPBreakdown()

      emit('price-updated')
    }
    catch (error) {
      console.error('Error applying price suggestion:', error)
    }
  }
}

// Watchers
watch(() => props.modelValue, newValue => {
  if (newValue && props.productId)
    loadHPPBreakdown()
})

watch(() => props.productId, newValue => {
  if (newValue && props.modelValue)
    loadHPPBreakdown()
})
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="1200"
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-3">
          <VIcon
            icon="mdi-calculator-variant"
            size="24"
          />
          <span>HPP Breakdown - {{ productName || 'Product' }}</span>
        </div>

        <VBtn
          icon="mdi-close"
          size="small"
          variant="text"
          @click="$emit('update:modelValue', false)"
        />
      </VCardTitle>

      <VCardText>
        <!-- Method Selection -->
        <VRow class="mb-4">
          <VCol
            cols="12"
            md="6"
          >
            <VSelect
              v-model="selectedMethod"
              :items="hppMethods"
              label="HPP Calculation Method"
              variant="outlined"
              @update:model-value="loadHPPBreakdown"
            />
          </VCol>
          <VCol
            cols="12"
            md="6"
          >
            <VBtn
              color="primary"
              variant="elevated"
              :loading="loading"
              prepend-icon="mdi-update"
              block
              @click="updateHPP"
            >
              Update HPP with {{ selectedMethod }}
            </VBtn>
          </VCol>
        </VRow>

        <!-- HPP Summary -->
        <VCard
          v-if="hasHPPData"
          variant="tonal"
          color="primary"
          class="mb-4"
        >
          <VCardText>
            <VRow align="center">
              <VCol
                cols="12"
                md="4"
              >
                <div class="text-subtitle-2">
                  Total HPP
                </div>
                <div class="text-h5 font-weight-bold">
                  {{ formatCurrency(currentHPPBreakdown.total_hpp) }}
                </div>
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <div class="text-subtitle-2">
                  Calculation Method
                </div>
                <div class="text-h6">
                  {{ hppMethods.find(m => m.value === currentHPPBreakdown.method)?.title }}
                </div>
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <div class="text-subtitle-2">
                  Last Calculated
                </div>
                <div class="text-body-1">
                  {{ new Date(currentHPPBreakdown.calculated_at).toLocaleString() }}
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- HPP Breakdown Table -->
        <VCard v-if="hasHPPData">
          <VCardTitle>
            <VIcon
              icon="mdi-format-list-bulleted"
              class="me-2"
            />
            Item Breakdown ({{ currentHPPBreakdown.items.length }} items)
          </VCardTitle>

          <VDataTable
            :headers="breakdownHeaders"
            :items="currentHPPBreakdown.items"
            :items-per-page="10"
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
                {{ item.quantity_needed }} {{ item.unit }}
              </span>
            </template>

            <template #item.cost_per_unit="{ item }">
              <span>{{ formatCurrency(item.cost_per_unit) }}</span>
            </template>

            <template #item.total_cost="{ item }">
              <VChip
                :color="item.is_critical ? 'error' : 'primary'"
                variant="elevated"
                size="small"
              >
                {{ formatCurrency(item.total_cost) }}
              </VChip>
            </template>

            <template #item.is_critical="{ item }">
              <VChip
                :color="item.is_critical ? 'error' : 'success'"
                :prepend-icon="item.is_critical ? 'mdi-alert' : 'mdi-check'"
                variant="tonal"
                size="small"
              >
                {{ item.is_critical ? 'Critical' : 'Normal' }}
              </VChip>
            </template>

            <template #item.notes="{ item }">
              <span
                v-if="item.notes"
                class="text-caption"
              >
                {{ item.notes }}
              </span>
              <span
                v-else
                class="text-grey-lighten-1 text-caption"
              >—</span>
            </template>

            <template #bottom>
              <div class="v-data-table__footer">
                <VDivider />
                <div class="pa-4 d-flex justify-space-between align-center">
                  <div class="text-subtitle-1 font-weight-bold">
                    Total HPP: {{ formatCurrency(currentHPPBreakdown.total_hpp) }}
                  </div>
                  <VChip
                    color="info"
                    variant="elevated"
                  >
                    {{ currentHPPBreakdown.items.length }} items
                  </VChip>
                </div>
              </div>
            </template>
          </VDataTable>
        </VCard>

        <!-- Price Suggestion -->
        <VRow
          v-if="hasHPPData"
          class="mt-4"
        >
          <VCol cols="12">
            <VCard>
              <VCardTitle>
                <VIcon
                  icon="mdi-currency-usd"
                  class="me-2"
                />
                Price Suggestion
              </VCardTitle>
              <VCardText>
                <VRow align="end">
                  <VCol
                    cols="12"
                    md="3"
                  >
                    <VTextField
                      :key="markupKey"
                      v-model.number="markupPercentage"
                      type="number"
                      label="Markup Percentage (%)"
                      variant="outlined"
                      suffix="%"
                      min="0"
                      max="1000"
                    />
                  </VCol>
                  <VCol
                    cols="12"
                    md="3"
                  >
                    <VBtn
                      color="info"
                      variant="elevated"
                      :loading="loading"
                      prepend-icon="mdi-calculator"
                      block
                      @click="calculateSuggestion"
                    >
                      Calculate
                    </VBtn>
                  </VCol>
                  <VCol
                    cols="12"
                    md="3"
                  >
                    <VBtn
                      v-if="hasSuggestionData"
                      color="success"
                      variant="elevated"
                      :loading="loading"
                      prepend-icon="mdi-check"
                      block
                      @click="applyPriceSuggestion"
                    >
                      Apply Price
                    </VBtn>
                  </VCol>
                  <VCol
                    cols="12"
                    md="3"
                  >
                    <div
                      v-if="hasSuggestionData"
                      class="text-center"
                    >
                      <div class="text-subtitle-2">
                        Suggested Price
                      </div>
                      <div class="text-h6 font-weight-bold text-success">
                        {{ formatCurrency(currentHPPSuggestion.suggestions[selectedMethod]?.suggested_price || 0) }}
                      </div>
                    </div>
                  </VCol>
                </VRow>

                <!-- Suggestion Details -->
                <VRow
                  v-if="hasSuggestionData"
                  class="mt-4"
                >
                  <VCol cols="12">
                    <VAlert
                      color="info"
                      variant="tonal"
                      class="mb-0"
                    >
                      <div class="d-flex justify-space-between align-center">
                        <div>
                          <strong>HPP:</strong> {{ formatCurrency(currentHPPSuggestion.suggestions[selectedMethod]?.hpp || 0) }} |
                          <strong>Markup:</strong> {{ markupPercentage }}% |
                          <strong>Profit:</strong> {{ formatCurrency(currentHPPSuggestion.suggestions[selectedMethod]?.profit_margin || 0) }}
                        </div>
                        <VChip
                          color="success"
                          variant="elevated"
                          size="small"
                        >
                          {{ Number(markupPercentage).toFixed(1) }}% margin
                        </VChip>
                      </div>
                    </VAlert>
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <!-- Loading State -->
        <VCard v-if="loading && !hasHPPData">
          <VCardText class="text-center py-12">
            <VProgressCircular
              indeterminate
              size="64"
            />
            <div class="mt-4 text-h6">
              Loading HPP breakdown...
            </div>
          </VCardText>
        </VCard>

        <!-- Empty State -->
        <VCard v-if="!loading && !hasHPPData">
          <VCardText class="text-center py-12">
            <VIcon
              icon="mdi-calculator-variant"
              size="64"
              class="text-grey-lighten-1 mb-4"
            />
            <div class="text-h6 text-grey-darken-1 mb-2">
              No HPP Data Available
            </div>
            <div class="text-body-2 text-grey-darken-1 mb-4">
              This product doesn't have item composition data or HPP calculation failed
            </div>
            <VBtn
              color="primary"
              variant="elevated"
              prepend-icon="mdi-refresh"
              @click="loadHPPBreakdown"
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

<style scoped>
.v-data-table__footer {
  border-block-start: 1px solid rgb(var(--v-theme-surface-variant));
}
</style>
