<template>
  <VDialog
    v-model="localValue"
    max-width="600px"
    scrollable
  >
    <VCard v-if="discount">
      <VCardTitle class="d-flex align-center justify-space-between bg-primary text-white">
        <div class="d-flex align-center">
          <VIcon icon="mdi-eye" size="24" class="me-2" />
          <span>Detail Diskon</span>
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
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Nama Diskon</div>
                <div class="text-body-1 font-weight-medium">{{ discount.name }}</div>
              </div>
            </VCol>
            
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Kode Diskon</div>
                <VChip color="primary" variant="tonal" size="small">
                  {{ discount.code }}
                </VChip>
              </div>
            </VCol>
            
            <VCol cols="12" v-if="discount.description">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Deskripsi</div>
                <div class="text-body-1">{{ discount.description }}</div>
              </div>
            </VCol>
          </VRow>
        </div>

        <!-- Discount Configuration -->
        <div class="mb-6">
          <h6 class="text-h6 mb-4">Konfigurasi Diskon</h6>
          
          <VRow>
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Tipe Diskon</div>
                <VChip 
                  :color="getTypeColor(discount.type)" 
                  variant="tonal" 
                  size="small"
                >
                  {{ getTypeLabel(discount.type) }}
                </VChip>
              </div>
            </VCol>
            
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Nilai Diskon</div>
                <div class="text-body-1 font-weight-medium">{{ formatDiscountValue(discount) }}</div>
              </div>
            </VCol>
            
            <VCol v-if="discount.type === 'buy_x_get_y'" cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Beli Quantity</div>
                <div class="text-body-1 font-weight-medium">{{ discount.buy_quantity }}</div>
              </div>
            </VCol>
            
            <VCol v-if="discount.type === 'buy_x_get_y'" cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Dapat Quantity</div>
                <div class="text-body-1 font-weight-medium">{{ discount.get_quantity }}</div>
              </div>
            </VCol>
            
            <VCol v-if="discount.minimum_amount" cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Minimum Pembelian</div>
                <div class="text-body-1 font-weight-medium">{{ formatCurrency(discount.minimum_amount) }}</div>
              </div>
            </VCol>
            
            <VCol v-if="discount.maximum_discount_amount" cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Maksimum Diskon</div>
                <div class="text-body-1 font-weight-medium">{{ formatCurrency(discount.maximum_discount_amount) }}</div>
              </div>
            </VCol>
          </VRow>
        </div>

        <!-- Usage Information -->
        <div class="mb-6">
          <h6 class="text-h6 mb-4">Informasi Penggunaan</h6>
          
          <VRow>
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Total Digunakan</div>
                <div class="text-body-1 font-weight-medium">
                  {{ discount.used_count || 0 }}
                  <span v-if="discount.usage_limit" class="text-medium-emphasis">
                    / {{ discount.usage_limit }}
                  </span>
                  <span v-else class="text-medium-emphasis">
                    / Unlimited
                  </span>
                </div>
              </div>
            </VCol>
            
            <VCol v-if="discount.usage_limit_per_user" cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Batas Per Pengguna</div>
                <div class="text-body-1 font-weight-medium">{{ discount.usage_limit_per_user }}</div>
              </div>
            </VCol>
            
            <VCol v-if="discount.usage_limit" cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Progress Penggunaan</div>
                <VProgressLinear
                  :model-value="usagePercentage"
                  :color="usagePercentage >= 90 ? 'error' : usagePercentage >= 70 ? 'warning' : 'success'"
                  height="8"
                  rounded
                  class="mt-2"
                />
                <div class="text-caption text-medium-emphasis mt-1">
                  {{ usagePercentage.toFixed(1) }}% terpakai
                </div>
              </div>
            </VCol>
          </VRow>
        </div>

        <!-- Valid Period -->
        <div class="mb-6">
          <h6 class="text-h6 mb-4">Periode Berlaku</h6>
          
          <VRow>
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Berlaku Dari</div>
                <div class="text-body-1 font-weight-medium">{{ formatDateTime(discount.valid_from) }}</div>
              </div>
            </VCol>
            
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Berlaku Sampai</div>
                <div class="text-body-1 font-weight-medium">{{ formatDateTime(discount.valid_until) }}</div>
              </div>
            </VCol>
            
            <VCol cols="12">
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
                  :color="discount.active ? 'success' : 'error'" 
                  variant="tonal" 
                  size="small"
                >
                  <VIcon 
                    :icon="discount.active ? 'mdi-check' : 'mdi-close'" 
                    size="16" 
                    class="me-1" 
                  />
                  {{ discount.active ? 'Aktif' : 'Tidak Aktif' }}
                </VChip>
              </div>
            </VCol>
            
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Status Berlaku</div>
                <VChip 
                  :color="getStatusColor(discount.status)" 
                  variant="tonal" 
                  size="small"
                >
                  {{ getStatusLabel(discount.status) }}
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
                <div class="text-body-2">{{ formatDateTime(discount.created_at) }}</div>
              </div>
            </VCol>
            
            <VCol cols="12" md="6">
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis">Terakhir Diupdate</div>
                <div class="text-body-2">{{ formatDateTime(discount.updated_at) }}</div>
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
import DiscountsApi, { type Discount } from '@/utils/api/DiscountsApi'
import { computed } from 'vue'

interface Props {
  modelValue: boolean
  discount: Discount | null
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

const usagePercentage = computed(() => {
  if (!props.discount?.usage_limit) return 0
  return (props.discount.used_count / props.discount.usage_limit) * 100
})

// Methods
const close = () => {
  localValue.value = false
}

const formatDiscountValue = (discount: Discount): string => {
  return DiscountsApi.formatDiscountValue(discount)
}

const getTypeLabel = (type: string): string => {
  return DiscountsApi.getDiscountTypeLabel(type)
}

const getTypeColor = (type: string): string => {
  const colors = {
    percentage: 'primary',
    fixed_amount: 'success',
    buy_x_get_y: 'warning'
  }
  return colors[type as keyof typeof colors] || 'secondary'
}

const getStatusColor = (status: string): string => {
  return DiscountsApi.getStatusColor(status)
}

const getStatusLabel = (status: string): string => {
  return DiscountsApi.getStatusLabel(status)
}

const formatCurrency = (amount: number): string => {
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
  if (!props.discount) return ''
  
  const now = new Date()
  const validUntil = new Date(props.discount.valid_until)
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
  if (!props.discount) return 'secondary'
  
  const now = new Date()
  const validUntil = new Date(props.discount.valid_until)
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
