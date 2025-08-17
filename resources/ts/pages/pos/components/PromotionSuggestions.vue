<template>
  <div v-if="availablePromotions.length > 0 || appliedPromotions.length > 0">
    <!-- Applied Promotions -->
    <div v-if="appliedPromotions.length > 0" class="applied-promotions mb-4">
      <VAlert
        v-for="promotion in appliedPromotions"
        :key="promotion.id"
        type="success"
        variant="tonal"
        class="mb-2"
      >
        <template #prepend>
          <VIcon icon="mdi-speakerphone" />
        </template>
        <div class="d-flex justify-space-between align-center">
          <div>
            <div class="font-weight-medium">{{ promotion.name }}</div>
            <div class="text-caption">{{ promotion.description }}</div>
            <div class="text-caption text-success mt-1">
              Hemat: {{ formatCurrency(promotion.calculated_discount || 0) }}
            </div>
          </div>
          <VBtn
            icon="mdi-close"
            variant="text"
            size="small"
            @click="removePromotion(promotion.id)"
          />
        </div>
      </VAlert>
    </div>

    <!-- Available Promotions -->
    <VCard 
      v-if="availablePromotions.length > 0"
      class="promotion-suggestions-card mb-4" 
      variant="tonal" 
      color="purple-lighten-5"
    >
      <VCardTitle class="text-h6 pb-2">
        <VIcon icon="mdi-speakerphone" size="20" class="me-2" />
        Promosi Tersedia
        <VChip 
          v-if="availablePromotions.length > 0"
          size="small" 
          color="purple" 
          class="ms-2"
        >
          {{ availablePromotions.length }}
        </VChip>
      </VCardTitle>
      
      <VCardText class="pt-0">
        <div class="text-caption mb-3 text-medium-emphasis">
          Promosi yang bisa diterapkan pada pesanan ini:
        </div>
        
        <div class="promotion-list">
          <div
            v-for="promotion in availablePromotions"
            :key="promotion.id"
            class="promotion-item"
          >
            <VCard
              variant="outlined"
              class="promotion-card"
              :class="{ 'promotion-selected': isPromotionApplied(promotion.id) }"
              @click="togglePromotion(promotion)"
            >
              <VCardText class="pa-3">
                <div class="d-flex justify-space-between align-start">
                  <div class="promotion-info">
                    <div class="promotion-name font-weight-medium text-body-1">
                      {{ promotion.name }}
                    </div>
                    <div class="promotion-description text-caption text-medium-emphasis mb-2">
                      {{ promotion.description }}
                    </div>
                    
                    <!-- Promotion Type Badge -->
                    <VChip
                      size="x-small"
                      :color="getPromotionTypeColor(promotion.type)"
                      variant="tonal"
                      class="me-2 mb-2"
                    >
                      {{ getPromotionTypeLabel(promotion.type) }}
                    </VChip>
                    
                    <!-- Discount Info -->
                    <div class="promotion-savings text-success font-weight-medium">
                      <VIcon icon="mdi-tag" size="14" class="me-1" />
                      Hemat: {{ formatCurrency(promotion.calculated_discount || 0) }}
                    </div>
                    
                    <!-- Valid Until -->
                    <div v-if="promotion.valid_until" class="promotion-validity text-caption mt-1">
                      <VIcon icon="mdi-clock-outline" size="12" class="me-1" />
                      Berlaku hingga: {{ formatDate(promotion.valid_until) }}
                    </div>
                  </div>
                  
                  <div class="promotion-action">
                    <VBtn
                      :color="isPromotionApplied(promotion.id) ? 'error' : 'purple'"
                      :variant="isPromotionApplied(promotion.id) ? 'outlined' : 'flat'"
                      size="small"
                      @click.stop="togglePromotion(promotion)"
                    >
                      {{ isPromotionApplied(promotion.id) ? 'Hapus' : 'Terapkan' }}
                    </VBtn>
                  </div>
                </div>
              </VCardText>
            </VCard>
          </div>
        </div>
        
        <!-- Apply All Button -->
        <div v-if="availablePromotions.length > 1" class="mt-3 text-center">
          <VBtn
            color="purple"
            variant="outlined"
            size="small"
            @click="applyAllPromotions"
          >
            <VIcon icon="mdi-plus-circle" class="me-1" />
            Terapkan Semua Promosi
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

// Type definitions
interface Promotion {
  id: number
  name: string
  description: string
  type: 'happy_hour' | 'bogo' | 'combo_deal' | 'category_discount' | 'quantity_discount'
  discount_type: 'percentage' | 'amount'
  discount_value: number
  minimum_amount?: number
  maximum_discount?: number
  valid_from: string
  valid_until: string | null
  valid_days: string | null
  valid_hours: string | null
  priority: number
  can_be_combined: boolean
  is_active: boolean
  calculated_discount?: number
}

// Mock API for now - will be replaced with actual implementation
const PromotionsApi = {
  async calculatePromotions(cartData: any) {
    // Mock calculation - replace with actual API call
    return {
      success: false,
      message: 'API belum tersedia - implementasi mock',
      data: []
    }
  }
}

interface CartItem {
  id_product: number
  name: string
  category_id?: number
  selling_price: number
  quantity: number
}

interface Props {
  cartItems: CartItem[]
  subtotal: number
}

interface Emits {
  (e: 'promotions-applied', promotions: Promotion[], totalDiscount: number): void
  (e: 'promotions-removed'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const availablePromotions = ref<Promotion[]>([])
const appliedPromotions = ref<Promotion[]>([])
const isLoading = ref(false)

// Computed
const totalPromotionDiscount = computed(() => {
  return appliedPromotions.value.reduce((total: number, promotion: Promotion) => {
    return total + (promotion.calculated_discount || 0)
  }, 0)
})

// Methods
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount).replace('IDR', 'Rp')
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const getPromotionTypeColor = (type: string) => {
  const colorMap: Record<string, string> = {
    'happy_hour': 'orange',
    'bogo': 'green',
    'combo_deal': 'blue',
    'category_discount': 'purple',
    'quantity_discount': 'teal'
  }
  return colorMap[type] || 'grey'
}

const getPromotionTypeLabel = (type: string) => {
  const labelMap: Record<string, string> = {
    'happy_hour': 'Happy Hour',
    'bogo': 'BOGO',
    'combo_deal': 'Combo Deal',
    'category_discount': 'Diskon Kategori',
    'quantity_discount': 'Diskon Jumlah'
  }
  return labelMap[type] || type
}

const isPromotionApplied = (promotionId: number) => {
  return appliedPromotions.value.some((p: Promotion) => p.id === promotionId)
}

const loadAvailablePromotions = async () => {
  if (props.cartItems.length === 0) {
    availablePromotions.value = []
    return
  }

  try {
    isLoading.value = true
    
    // Prepare cart data for promotion calculation
    const cartData = {
      items: props.cartItems.map(item => ({
        product_id: item.id_product,
        category_id: item.category_id || null,
        price: item.selling_price,
        quantity: item.quantity,
        subtotal: item.selling_price * item.quantity
      })),
      subtotal: props.subtotal,
      current_time: new Date().toISOString()
    }
    
    const response = await PromotionsApi.calculatePromotions(cartData)
    
    if (response.success && response.data) {
      availablePromotions.value = response.data.filter((promotion: Promotion) => 
        !isPromotionApplied(promotion.id)
      )
    }
  } catch (error) {
    console.error('Error loading promotions:', error)
  } finally {
    isLoading.value = false
  }
}

const togglePromotion = (promotion: Promotion) => {
  if (isPromotionApplied(promotion.id)) {
    removePromotion(promotion.id)
  } else {
    applyPromotion(promotion)
  }
}

const applyPromotion = (promotion: Promotion) => {
  // Check if promotion can be combined
  if (appliedPromotions.value.length > 0 && !promotion.can_be_combined) {
    // Remove existing promotions if new one can't be combined
    appliedPromotions.value = []
  }
  
  appliedPromotions.value.push(promotion)
  
  // Remove from available list
  availablePromotions.value = availablePromotions.value.filter((p: Promotion) => p.id !== promotion.id)
  
  emitPromotionChange()
}

const removePromotion = (promotionId: number) => {
  const promotion = appliedPromotions.value.find((p: Promotion) => p.id === promotionId)
  if (promotion) {
    appliedPromotions.value = appliedPromotions.value.filter((p: Promotion) => p.id !== promotionId)
    
    // Add back to available list
    availablePromotions.value.push(promotion)
    
    emitPromotionChange()
  }
}

const applyAllPromotions = () => {
  // Apply all available promotions that can be combined
  availablePromotions.value.forEach((promotion: Promotion) => {
    if (promotion.can_be_combined || appliedPromotions.value.length === 0) {
      applyPromotion(promotion)
    }
  })
}

const emitPromotionChange = () => {
  if (appliedPromotions.value.length > 0) {
    emit('promotions-applied', appliedPromotions.value, totalPromotionDiscount.value)
  } else {
    emit('promotions-removed')
  }
}

// Watch for cart changes
watch(() => [props.cartItems, props.subtotal], () => {
  loadAvailablePromotions()
}, { deep: true })

// Load promotions on mount
onMounted(() => {
  loadAvailablePromotions()
})

// Expose methods for parent component
defineExpose({
  loadAvailablePromotions,
  appliedPromotions: computed(() => appliedPromotions.value),
  totalPromotionDiscount
})
</script>

<style scoped lang="scss">
.promotion-suggestions-card {
  border: 2px dashed rgb(var(--v-theme-purple));
  border-radius: 12px;
  
  .promotion-list {
    .promotion-item {
      margin-bottom: 8px;
      
      .promotion-card {
        cursor: pointer;
        transition: all 0.2s ease;
        border-radius: 8px;
        
        &:hover {
          transform: translateY(-1px);
          box-shadow: 0 4px 12px rgba(var(--v-theme-purple), 0.15);
        }
        
        &.promotion-selected {
          border-color: rgb(var(--v-theme-purple));
          background-color: rgba(var(--v-theme-purple), 0.04);
        }
      }
    }
  }
}

.applied-promotions {
  .v-alert {
    border-left: 4px solid rgb(var(--v-theme-success));
  }
}
</style>
