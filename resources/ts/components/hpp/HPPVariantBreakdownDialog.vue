<script setup lang="ts">
import { useVariantHPP } from '@/composables/useVariantHPP'
import axios from 'axios'
import { nextTick, ref, watch } from 'vue'

// Props
interface Props {
  modelValue: boolean
  variantId: number | null
  variantName?: string
}

const props = withDefaults(defineProps<Props>(), {
  variantName: '',
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
  getVariantHPPBreakdown,
  updateVariantHPP,
  calculateSuggestedPrice,
  updatePriceFromHPP,
  formatCurrency,
} = useVariantHPP()

// State
const selectedMethod = ref<'current' | 'latest' | 'average'>('latest')
const markupPercentage = ref(0) // Will be calculated from target price
const targetPrice = ref(0) // User input for desired selling price
const targetPriceFormatted = ref('') // Formatted display value
const markupKey = ref(0) // Key untuk force re-render TextField

// Snackbar state
const successSnackbar = ref(false)
const errorSnackbar = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

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

// Helper functions for currency formatting
const formatRupiahInput = (value: number): string => {
  if (!value || value === 0) return ''
  return new Intl.NumberFormat('id-ID').format(value)
}

const parseRupiahInput = (value: string): number => {
  if (!value) return 0
  // Remove all non-digit characters except decimal separator
  const cleanValue = value.replace(/[^\d]/g, '')
  return parseInt(cleanValue) || 0
}

const updateTargetPriceFromInput = (inputValue: string) => {
  const numericValue = parseRupiahInput(inputValue)
  targetPrice.value = numericValue
  targetPriceFormatted.value = formatRupiahInput(numericValue)
  
  // Auto-calculate markup percentage
  if (currentHPPBreakdown.value?.total_hpp && numericValue > 0) {
    const hpp = currentHPPBreakdown.value.total_hpp
    markupPercentage.value = hpp > 0 ? Math.round(((numericValue - hpp) / hpp) * 100) : 0
  } else {
    markupPercentage.value = 0
  }
}

// Methods
const loadVariantTargetPrice = async () => {
  if (props.variantId) {
    try {
      const token = localStorage.getItem('token')

      const response = await axios.get(`/api/variants/${props.variantId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      })

      if (response.data.success && response.data.data) {
        const variant = response.data.data

        // Load current price as target price
        if (variant.price && variant.price > 0) {
          targetPrice.value = Number(variant.price)
          targetPriceFormatted.value = formatRupiahInput(Number(variant.price))
        }
        else {
          // Default fallback - calculate from HPP if available
          if (currentHPPBreakdown.value?.total_hpp) {
            const defaultPrice = Math.round(currentHPPBreakdown.value.total_hpp * 1.2) // 20% markup as default
            targetPrice.value = defaultPrice
            targetPriceFormatted.value = formatRupiahInput(defaultPrice)
          }
        }

        // Auto-calculate markup percentage
        if (currentHPPBreakdown.value?.total_hpp && targetPrice.value > 0) {
          const hpp = currentHPPBreakdown.value.total_hpp
          markupPercentage.value = hpp > 0 ? Math.round(((targetPrice.value - hpp) / hpp) * 100) : 0
        }

        markupKey.value += 1 // Force re-render
      }
    }
    catch (error) {
      console.warn('Could not load target price, using default:', error)
      if (currentHPPBreakdown.value?.total_hpp) {
        targetPrice.value = Math.round(currentHPPBreakdown.value.total_hpp * 1.2)
      }
    }
  }
}

const loadHPPBreakdown = async () => {
  if (props.variantId) {
    await getVariantHPPBreakdown(props.variantId, selectedMethod.value)
    await loadVariantTargetPrice()
  }
}

const updateHPP = async () => {
  if (props.variantId) {
    try {
      await updateVariantHPP(props.variantId, selectedMethod.value)
      
      // Show success message
      successMessage.value = `HPP successfully updated using ${selectedMethod.value} method!`
      successSnackbar.value = true
      
      emit('hpp-updated')

      // Reload breakdown after update
      await loadHPPBreakdown()
    } catch (error: any) {
      console.error('Error updating HPP:', error)
      
      // Show error message
      errorMessage.value = error?.response?.data?.message || 'Failed to update HPP'
      errorSnackbar.value = true
    }
  }
}

const calculateSuggestion = async () => {
  if (props.variantId && currentHPPBreakdown.value?.total_hpp && targetPrice.value) {
    try {
      await calculateSuggestedPrice(props.variantId, markupPercentage.value)
      
      // Show success message for calculation
      successMessage.value = `Price suggestion calculated successfully!`
      successSnackbar.value = true
    } catch (error: any) {
      console.error('Error calculating suggestion:', error)
      
      // Show error message
      errorMessage.value = error?.response?.data?.message || 'Failed to calculate price suggestion'
      errorSnackbar.value = true
    }
  }
}

const applyPriceSuggestion = async () => {
  if (props.variantId && currentHPPBreakdown.value?.total_hpp && targetPrice.value) {
    try {
      const response = await updatePriceFromHPP(
        props.variantId,
        selectedMethod.value,
        targetPrice.value,
        true,
        true, // useTargetPrice = true
      )

      // Update target price and markup from response
      if (response?.data?.new_price) {
        targetPrice.value = response.data.new_price
        targetPriceFormatted.value = formatRupiahInput(response.data.new_price)
        
        if (response?.data?.markup_percentage !== undefined) {
          markupPercentage.value = response.data.markup_percentage
        } else {
          // Calculate markup from response data
          const hpp = currentHPPBreakdown.value.total_hpp
          markupPercentage.value = hpp > 0 ? Math.round(((response.data.new_price - hpp) / hpp) * 100) : 0
        }
        
        markupKey.value += 1
        await nextTick()
      }

      // Reload HPP breakdown after price update
      await loadHPPBreakdown()

      // Show success message
      successMessage.value = `Price successfully updated to ${formatCurrency(response?.data?.new_price || targetPrice.value)}!`
      successSnackbar.value = true

      emit('price-updated')
    }
    catch (error: any) {
      console.error('Error applying price suggestion:', error)
      
      // Show error message
      errorMessage.value = error?.response?.data?.message || 'Failed to update variant price'
      errorSnackbar.value = true
    }
  }
}

// Watchers
watch(() => props.modelValue, newValue => {
  if (newValue && props.variantId)
    loadHPPBreakdown()
})

watch(() => props.variantId, newValue => {
  if (newValue && props.modelValue)
    loadHPPBreakdown()
})

watch(selectedMethod, async () => {
  if (props.variantId) {
    await loadHPPBreakdown()
  }
})

// Auto-calculate markup when HPP or target price changes
watch([() => currentHPPBreakdown.value?.total_hpp, targetPrice], () => {
  if (currentHPPBreakdown.value?.total_hpp && targetPrice.value > 0) {
    const hpp = currentHPPBreakdown.value.total_hpp
    markupPercentage.value = hpp > 0 ? Math.round(((targetPrice.value - hpp) / hpp) * 100) : 0
  } else {
    markupPercentage.value = 0
  }
})

// Format target price when it changes
watch(targetPrice, (newValue) => {
  if (newValue !== parseRupiahInput(targetPriceFormatted.value)) {
    targetPriceFormatted.value = formatRupiahInput(newValue)
  }
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
          <span>HPP Breakdown - {{ variantName || 'Variant' }}</span>
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

        <!-- Price Suggestion Section -->
        <VCard
          v-if="hasHPPData"
          class="mt-4"
        >
          <VCardTitle>
            <VIcon
              icon="mdi-cash-multiple"
              class="me-2"
            />
            Price Suggestion
          </VCardTitle>

          <VCardText>
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  :key="markupKey"
                  v-model="targetPriceFormatted"
                  label="Target Selling Price"
                  variant="outlined"
                  prefix="Rp"
                  :hint="`Current HPP: ${formatCurrency(currentHPPBreakdown.total_hpp)}`"
                  persistent-hint
                  @update:model-value="updateTargetPriceFromInput"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  :key="markupKey"
                  v-model="markupPercentage"
                  label="Markup Percentage"
                  variant="outlined"
                  type="number"
                  min="0"
                  step="5"
                  suffix="%"
                  readonly
                  :hint="`Profit: ${formatCurrency(targetPrice - (currentHPPBreakdown?.total_hpp || 0))}`"
                  persistent-hint
                />
              </VCol>
            </VRow>

            <VRow class="mt-2">
              <!-- <VCol
                cols="12"
                md="6"
              >
                <VBtn
                  color="info"
                  variant="elevated"
                  prepend-icon="mdi-calculator"
                  :loading="loading"
                  block
                  @click="calculateSuggestion"
                >
                  Calculate Suggestion
                </VBtn>
              </VCol> -->
              <VCol
                cols="12"
                md="12"
              >
                <VBtn
                  color="success"
                  variant="elevated"
                  prepend-icon="mdi-check-circle"
                  :loading="loading"
                  :disabled="!targetPrice || targetPrice <= 0"
                  block
                  @click="applyPriceSuggestion"
                >
                  Apply Price
                </VBtn>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Suggestion Results -->
        <VCard
          v-if="hasSuggestionData"
          variant="tonal"
          color="success"
          class="mt-4"
        >
          <VCardTitle>
            <VIcon
              icon="mdi-lightbulb"
              class="me-2"
            />
            Price Suggestions
          </VCardTitle>

          <VCardText>
            <VRow>
              <VCol
                v-for="(suggestion, method) in currentHPPSuggestion.suggestions"
                :key="method"
                cols="12"
                md="4"
              >
                <VCard variant="elevated">
                  <VCardText class="text-center">
                    <div class="text-subtitle-2 text-uppercase">
                      {{ method }} Method
                    </div>
                    <div class="text-h6 font-weight-bold my-2">
                      {{ formatCurrency(suggestion.suggested_price) }}
                    </div>
                    <div class="text-caption">
                      HPP: {{ formatCurrency(suggestion.hpp) }}<br>
                      Markup: {{ suggestion.markup_percentage.toFixed(1) }}%<br>
                      Margin: {{ suggestion.profit_margin.toFixed(1) }}%
                    </div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- No Data State -->
        <VCard
          v-if="!hasHPPData && !loading"
          variant="outlined"
        >
          <VCardText class="text-center py-8">
            <VIcon
              icon="mdi-calculator-variant-outline"
              size="64"
              color="grey"
              class="mb-4"
            />
            <div class="text-h6 mb-2">
              No HPP Data Available
            </div>
            <div class="text-body-2 text-medium-emphasis mb-4">
              Add composition items first, then calculate HPP using the method above.
            </div>
            <VBtn
              color="primary"
              prepend-icon="mdi-plus"
              @click="$emit('update:modelValue', false)"
            >
              Add Composition Items
            </VBtn>
          </VCardText>
        </VCard>
      </VCardText>

      <!-- Dialog Actions -->
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="outlined"
          color="grey"
          prepend-icon="mdi-close"
          @click="$emit('update:modelValue', false)"
        >
          Batal
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Success Snackbar -->
  <VSnackbar
    v-model="successSnackbar"
    color="success"
    location="top"
  >
    {{ successMessage }}

    <template #actions>
      <VBtn
        color="white"
        variant="text"
        @click="successSnackbar = false"
      >
        Close
      </VBtn>
    </template>
  </VSnackbar>

  <!-- Error Snackbar -->
  <VSnackbar
    v-model="errorSnackbar"
    color="error"
    location="top"
  >
    {{ errorMessage }}

    <template #actions>
      <VBtn
        color="white"
        variant="text"
        @click="errorSnackbar = false"
      >
        Close
      </VBtn>
    </template>
  </VSnackbar>
</template>
