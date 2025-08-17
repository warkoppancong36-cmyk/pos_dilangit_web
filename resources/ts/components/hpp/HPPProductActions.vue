<template>
  <div class="hpp-actions">
    <!-- HPP Quick Info -->
    <div class="d-flex align-center gap-2 mb-2">
      <VChip
        :color="hppStatus.color"
        :prepend-icon="hppStatus.icon"
        variant="tonal"
        size="small"
      >
        HPP: {{ formatCurrency(product.cost || 0) }}
      </VChip>
      
      <VChip
        v-if="product.price && product.cost"
        :color="profitMargin >= 0 ? 'success' : 'error'"
        variant="tonal"
        size="small"
      >
        {{ profitMarginPercent.toFixed(1) }}% margin
      </VChip>
    </div>

    <!-- HPP Actions -->
    <VBtnGroup variant="tonal" density="compact" class="hpp-btn-group">
      <VBtn
        size="small"
        prepend-icon="mdi-calculator-variant"
        @click="openBreakdownDialog"
        :loading="loading"
      >
        HPP Detail
      </VBtn>
      
      <VBtn
        size="small"
        prepend-icon="mdi-compare"
        @click="openComparisonDialog"
        :loading="loading"
      >
        Compare
      </VBtn>
      
      <VMenu>
        <template #activator="{ props: menuProps }">
          <VBtn
            size="small"
            prepend-icon="mdi-update"
            v-bind="menuProps"
            :loading="loading"
          >
            Update HPP
          </VBtn>
        </template>
        
        <VCard min-width="250">
          <VList>
            <VListSubheader>Update HPP Method</VListSubheader>
            <VListItem
              v-for="method in hppMethods"
              :key="method.value"
              @click="updateHPP(method.value)"
              :prepend-icon="method.icon"
            >
              <VListItemTitle>{{ method.title }}</VListItemTitle>
              <VListItemSubtitle>{{ method.description }}</VListItemSubtitle>
            </VListItem>
          </VList>
        </VCard>
      </VMenu>
      
      <VMenu>
        <template #activator="{ props: menuProps }">
          <VBtn
            size="small"
            prepend-icon="mdi-currency-usd"
            v-bind="menuProps"
            :loading="loading"
          >
            Price Tools
          </VBtn>
        </template>
        
        <VCard min-width="280">
          <VCardTitle class="text-subtitle-1">Price Calculator</VCardTitle>
          <VCardText>
            <VTextField
              v-model.number="markupPercentage"
              type="number"
              label="Markup %"
              variant="outlined"
              density="compact"
              suffix="%"
              min="0"
              max="1000"
              class="mb-3"
            />
            
            <div class="d-flex flex-column gap-2">
              <VBtn
                color="info"
                variant="tonal"
                size="small"
                block
                @click="calculatePrice"
                prepend-icon="mdi-calculator"
              >
                Calculate Price
              </VBtn>
              
              <VBtn
                v-if="suggestedPrice > 0"
                color="success"
                variant="tonal"
                size="small"
                block
                @click="applyCalculatedPrice"
                prepend-icon="mdi-check"
              >
                Apply {{ formatCurrency(suggestedPrice) }}
              </VBtn>
            </div>
            
            <div v-if="suggestedPrice > 0" class="mt-2 text-center">
              <VChip color="info" variant="tonal" size="small">
                Profit: {{ formatCurrency(suggestedPrice - (product.cost || 0)) }}
              </VChip>
            </div>
          </VCardText>
        </VCard>
      </VMenu>
    </VBtnGroup>

    <!-- HPP Breakdown Dialog -->
    <HPPBreakdownDialog
      v-model="showBreakdownDialog"
      :product-id="product.id_product"
      :product-name="product.name"
      @hpp-updated="handleHPPUpdated"
      @price-updated="handlePriceUpdated"
    />

    <!-- HPP Comparison Dialog -->
    <HPPComparisonDialog
      v-model="showComparisonDialog"
      :product-id="product.id_product"
      :product-name="product.name"
      @hpp-updated="handleHPPUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { useHPP } from '@/composables/useHPP'
import { computed, ref } from 'vue'
import HPPBreakdownDialog from './HPPBreakdownDialog.vue'
import HPPComparisonDialog from './HPPComparisonDialog.vue'

// Props
interface Props {
  product: {
    id_product: number
    name: string
    price?: number
    cost?: number
    [key: string]: any
  }
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  'hpp-updated': []
  'price-updated': []
  'product-updated': []
}>()

// Composables
const {
  loading,
  updateProductHPP,
  updatePriceFromHPP,
  formatCurrency,
} = useHPP()

// State
const showBreakdownDialog = ref(false)
const showComparisonDialog = ref(false)
const markupPercentage = ref(30)
const suggestedPrice = ref(0)

// Data
const hppMethods = [
  {
    title: 'Current Cost',
    value: 'current',
    icon: 'mdi-database',
    description: 'Use stored cost per unit',
  },
  {
    title: 'Latest Purchase',
    value: 'latest',
    icon: 'mdi-clock-outline',
    description: 'Use latest purchase prices',
  },
  {
    title: 'Average Purchase',
    value: 'average',
    icon: 'mdi-chart-line',
    description: 'Use average purchase prices',
  },
]

// Computed
const profitMargin = computed(() => {
  return (props.product.price || 0) - (props.product.cost || 0)
})

const profitMarginPercent = computed(() => {
  if (!props.product.cost || props.product.cost === 0) return 0
  return (profitMargin.value / props.product.cost) * 100
})

const hppStatus = computed(() => {
  const cost = props.product.cost || 0
  
  if (cost === 0) {
    return {
      color: 'error',
      icon: 'mdi-alert-circle',
      text: 'No HPP',
    }
  } else if (cost > 0 && profitMarginPercent.value < 10) {
    return {
      color: 'warning',
      icon: 'mdi-alert',
      text: 'Low Margin',
    }
  } else {
    return {
      color: 'success',
      icon: 'mdi-check-circle',
      text: 'Good Margin',
    }
  }
})

// Methods
const openBreakdownDialog = () => {
  showBreakdownDialog.value = true
}

const openComparisonDialog = () => {
  showComparisonDialog.value = true
}

const updateHPP = async (method: 'current' | 'latest' | 'average') => {
  await updateProductHPP(props.product.id_product, method)
  handleHPPUpdated()
}

const calculatePrice = () => {
  const hpp = props.product.cost || 0
  suggestedPrice.value = Math.round(hpp * (1 + markupPercentage.value / 100))
}

const applyCalculatedPrice = async () => {
  await updatePriceFromHPP(
    props.product.id_product,
    'current',
    markupPercentage.value,
    false // Don't update cost, just price
  )
  handlePriceUpdated()
  suggestedPrice.value = 0
}

const handleHPPUpdated = () => {
  emit('hpp-updated')
  emit('product-updated')
}

const handlePriceUpdated = () => {
  emit('price-updated')
  emit('product-updated')
}
</script>

<style scoped>
.hpp-actions {
  min-width: 200px;
}

.hpp-btn-group {
  width: 100%;
}

.hpp-btn-group .v-btn {
  flex: 1;
}

@media (max-width: 960px) {
  .hpp-btn-group {
    flex-direction: column;
  }
  
  .hpp-btn-group .v-btn {
    width: 100%;
    margin-bottom: 2px;
  }
}
</style>
