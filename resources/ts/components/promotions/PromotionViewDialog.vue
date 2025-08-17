<template>
  <VDialog
    v-model="localValue"
    max-width="700px"
    scrollable
  >
    <VCard v-if="promotion">
      <VCardTitle class="d-flex align-center justify-space-between bg-primary text-white">
        <div class="d-flex align-center">
          <VIcon icon="mdi-eye" size="24" class="me-2" />
          <span>Detail Promosi</span>
        </div>
        <VBtn
          icon="mdi-close"
          variant="text"
          color="white"
          @click="close"
        />
      </VCardTitle>

      <VCardText class="pa-6">
        <!-- Basic Information -->
        <div class="mb-6">
          <h6 class="text-h6 mb-4">Informasi Dasar</h6>
          
          <VRow>
            <VCol cols="12">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Nama Promosi</div>
                <div class="text-h6 font-weight-medium">{{ promotion.name }}</div>
              </div>
            </VCol>
            
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Tipe Promosi</div>
                <VChip 
                  :color="getTypeColor(promotion.type)" 
                  variant="tonal" 
                  size="small"
                >
                  {{ getTypeLabel(promotion.type) }}
                </VChip>
              </div>
            </VCol>
            
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Prioritas</div>
                <VChip 
                  :color="getPriorityColor(promotion.priority)" 
                  variant="tonal" 
                  size="small"
                >
                  {{ promotion.priority }}
                </VChip>
              </div>
            </VCol>
            
            <VCol cols="12" v-if="promotion.description">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Deskripsi</div>
                <div class="text-body-1">{{ promotion.description }}</div>
              </div>
            </VCol>
          </VRow>
        </div>

        <!-- Promotion Configuration -->
        <div class="mb-6">
          <h6 class="text-h6 mb-4">Konfigurasi Promosi</h6>
          
          <VRow>
            <!-- Happy Hour -->
            <template v-if="promotion.type === 'happy_hour'">
              <VCol cols="12" md="6">
                <div class="mb-3">
                  <div class="text-caption text-medium-emphasis">Jam Berlaku</div>
                  <div class="text-body-1 font-weight-medium">
                    {{ promotion.start_time || '00:00' }} - {{ promotion.end_time || '23:59' }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="6" v-if="promotion.valid_days?.length">
                <div class="mb-3">
                  <div class="text-caption text-medium-emphasis">Hari Berlaku</div>
                  <div class="d-flex flex-wrap gap-1">
                    <VChip
                      v-for="day in promotion.valid_days"
                      :key="day"
                      size="x-small"
                      variant="tonal"
                      color="primary"
                    >
                      {{ getDayLabel(day) }}
                    </VChip>
                  </div>
                </div>
              </VCol>
            </template>

            <!-- Buy One Get One -->
            <template v-if="promotion.type === 'buy_one_get_one'">
              <VCol cols="12" md="6">
                <div class="mb-3">
                  <div class="text-caption text-medium-emphasis">Beli Quantity</div>
                  <div class="text-body-1 font-weight-medium">{{ promotion.buy_quantity || 1 }}</div>
                </div>
              </VCol>
              <VCol cols="12" md="6">
                <div class="mb-3">
                  <div class="text-caption text-medium-emphasis">Dapat Quantity</div>
                  <div class="text-body-1 font-weight-medium">{{ promotion.get_quantity || 1 }}</div>
                </div>
              </VCol>
            </template>

            <!-- Combo Deal -->
            <template v-if="promotion.type === 'combo_deal'">
              <VCol cols="12" md="6">
                <div class="mb-3">
                  <div class="text-caption text-medium-emphasis">Jumlah Item Combo</div>
                  <div class="text-body-1 font-weight-medium">{{ promotion.combo_quantity || 2 }}</div>
                </div>
              </VCol>
              <VCol cols="12" md="6">
                <div class="mb-3">
                  <div class="text-caption text-medium-emphasis">Harga Combo</div>
                  <div class="text-body-1 font-weight-medium">{{ formatCurrency(promotion.combo_price) }}</div>
                </div>
              </VCol>
            </template>

            <!-- Quantity Discount -->
            <template v-if="promotion.type === 'quantity_discount'">
              <VCol cols="12" md="6">
                <div class="mb-3">
                  <div class="text-caption text-medium-emphasis">Minimum Quantity</div>
                  <div class="text-body-1 font-weight-medium">{{ promotion.min_quantity || 1 }}</div>
                </div>
              </VCol>
            </template>

            <!-- Discount Configuration -->
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Tipe Diskon</div>
                <VChip 
                  :color="promotion.discount_type === 'percentage' ? 'primary' : 'success'" 
                  variant="tonal" 
                  size="small"
                >
                  {{ promotion.discount_type === 'percentage' ? 'Persentase' : 'Jumlah Tetap' }}
                </VChip>
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Nilai Diskon</div>
                <div class="text-body-1 font-weight-medium">{{ formatPromotionDiscount(promotion) }}</div>
              </div>
            </VCol>

            <VCol v-if="promotion.max_discount_amount" cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Maksimum Diskon</div>
                <div class="text-body-1 font-weight-medium">{{ formatCurrency(promotion.max_discount_amount) }}</div>
              </div>
            </VCol>

            <VCol v-if="promotion.minimum_amount" cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Minimum Pembelian</div>
                <div class="text-body-1 font-weight-medium">{{ formatCurrency(promotion.minimum_amount) }}</div>
              </div>
            </VCol>
          </VRow>
        </div>

        <!-- Valid Period -->
        <div class="mb-6" v-if="promotion.valid_from || promotion.valid_until">
          <h6 class="text-h6 mb-4">Periode Berlaku</h6>
          
          <VRow>
            <VCol cols="12" md="6" v-if="promotion.valid_from">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Berlaku Dari</div>
                <div class="text-body-1 font-weight-medium">{{ formatDateTime(promotion.valid_from) }}</div>
              </div>
            </VCol>
            
            <VCol cols="12" md="6" v-if="promotion.valid_until">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Berlaku Sampai</div>
                <div class="text-body-1 font-weight-medium">{{ formatDateTime(promotion.valid_until) }}</div>
              </div>
            </VCol>
            
            <VCol cols="12" v-if="promotion.valid_until">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Sisa Waktu</div>
                <VChip 
                  :color="getTimeRemainingColor()" 
                  variant="tonal" 
                  size="small"
                >
                  <VIcon icon="mdi-clock-outline" size="16" class="me-1" />
                  {{ getTimeRemaining() }}
                </VChip>
              </div>
            </VCol>
          </VRow>
        </div>

        <!-- Status -->
        <div class="mb-6">
          <h6 class="text-h6 mb-4">Status</h6>
          
          <VRow>
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Status Aktif</div>
                <VChip 
                  :color="promotion.active ? 'success' : 'error'" 
                  variant="tonal" 
                  size="small"
                >
                  <VIcon 
                    :icon="promotion.active ? 'mdi-check' : 'mdi-close'" 
                    size="16" 
                    class="me-1" 
                  />
                  {{ promotion.active ? 'Aktif' : 'Tidak Aktif' }}
                </VChip>
              </div>
            </VCol>
            
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Status Berlaku</div>
                <VChip 
                  :color="getStatusColor(promotion.status || 'unknown')" 
                  variant="tonal" 
                  size="small"
                >
                  {{ getStatusLabel(promotion.status || 'unknown') }}
                </VChip>
              </div>
            </VCol>
          </VRow>
        </div>

        <!-- Timestamps -->
        <div>
          <h6 class="text-h6 mb-4">Informasi Sistem</h6>
          
          <VRow>
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Dibuat Pada</div>
                <div class="text-body-2">{{ formatDateTime(promotion.created_at) }}</div>
              </div>
            </VCol>
            
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Terakhir Diupdate</div>
                <div class="text-body-2">{{ formatDateTime(promotion.updated_at) }}</div>
              </div>
            </VCol>
          </VRow>
        </div>
      </VCardText>

      <VCardActions class="pa-6 pt-0">
        <VSpacer />
        <VBtn
          variant="outlined"
          @click="close"
        >
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import PromotionsApi, { type Promotion } from '@/utils/api/PromotionsApi'
import { computed } from 'vue'

interface Props {
  modelValue: boolean
  promotion: Promotion | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Computed
const localValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Methods
const close = () => {
  localValue.value = false
}

const formatPromotionDiscount = (promotion: Promotion): string => {
  return PromotionsApi.formatPromotionDiscount(promotion)
}

const getTypeLabel = (type: string): string => {
  return PromotionsApi.getPromotionTypeLabel(type)
}

const getTypeColor = (type: string): string => {
  const colors = {
    happy_hour: 'orange',
    buy_one_get_one: 'purple',
    combo_deal: 'teal',
    category_discount: 'indigo',
    quantity_discount: 'pink'
  }
  return colors[type as keyof typeof colors] || 'secondary'
}

const getPriorityColor = (priority: number): string => {
  if (priority >= 8) return 'error'
  if (priority >= 5) return 'warning'
  return 'success'
}

const getStatusColor = (status: string): string => {
  return PromotionsApi.getStatusColor(status)
}

const getStatusLabel = (status: string): string => {
  return PromotionsApi.getStatusLabel(status)
}

const getDayLabel = (day: string): string => {
  const dayLabels = {
    'monday': 'Sen',
    'tuesday': 'Sel',
    'wednesday': 'Rab',
    'thursday': 'Kam',
    'friday': 'Jum',
    'saturday': 'Sab',
    'sunday': 'Min'
  }
  return dayLabels[day as keyof typeof dayLabels] || day
}

const formatCurrency = (amount: number | null): string => {
  if (!amount) return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const formatDateTime = (dateString: string): string => {
  return new Date(dateString).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getTimeRemaining = (): string => {
  if (!props.promotion?.valid_until) return 'Tidak terbatas'
  
  const now = new Date()
  const validUntil = new Date(props.promotion.valid_until)
  const diff = validUntil.getTime() - now.getTime()
  
  if (diff <= 0) {
    return 'Kadaluarsa'
  }
  
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  
  if (days > 0) {
    return `${days} hari ${hours} jam`
  } else if (hours > 0) {
    return `${hours} jam ${minutes} menit`
  } else if (minutes > 0) {
    return `${minutes} menit`
  } else {
    return 'Kurang dari 1 menit'
  }
}

const getTimeRemainingColor = (): string => {
  if (!props.promotion?.valid_until) return 'info'
  
  const now = new Date()
  const validUntil = new Date(props.promotion.valid_until)
  const diff = validUntil.getTime() - now.getTime()
  
  if (diff <= 0) {
    return 'error'
  }
  
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days <= 1) {
    return 'error'
  } else if (days <= 7) {
    return 'warning'
  } else {
    return 'success'
  }
}
</script>
