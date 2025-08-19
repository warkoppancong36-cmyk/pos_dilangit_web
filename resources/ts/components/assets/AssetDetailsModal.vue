<template>
  <VDialog v-model="dialog" max-width="900px" scrollable>
    <VCard v-if="asset">
      <VCardTitle class="text-h5 pa-6 pb-4">
        <VIcon :icon="getCategoryIcon(asset.category)" class="me-3 text-primary" />
        {{ asset.name }}
        <VSpacer />
        <VBtn icon variant="text" @click="closeDialog">
          <VIcon>tabler-x</VIcon>
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-0">
        <VContainer class="pa-6">
          <!-- Asset Status Header -->
          <VRow class="mb-6">
            <VCol cols="12">
              <div class="d-flex justify-space-between align-center">
                <div>
                  <VChip
                    :color="getStatusColor(asset.status)"
                    size="large"
                    variant="tonal"
                    class="me-3"
                  >
                    <VIcon start :icon="getStatusIcon(asset.status)" />
                    {{ asset.status.toUpperCase() }}
                  </VChip>
                  <VChip
                    :color="getConditionColor(asset.condition)"
                    size="large"
                    variant="outlined"
                  >
                    {{ asset.condition.toUpperCase() }}
                  </VChip>
                </div>
                <div class="text-h4 font-weight-bold text-primary">
                  ${{ asset.purchase_price?.toLocaleString() || '0' }}
                </div>
              </div>
            </VCol>
          </VRow>

          <!-- Asset Information -->
          <VRow>
            <VCol cols="12" md="6">
              <VCard variant="outlined" class="h-100">
                <VCardTitle class="text-h6 pa-4 pb-2">
                  <VIcon icon="tabler-info-circle" class="me-2" />
                  Informasi Dasar
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  <VList density="compact" class="pa-0">
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-barcode" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Kode Asset</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ asset.asset_code }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-category" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Kategori</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ asset.category }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-map-pin" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Lokasi</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ asset.location }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem v-if="asset.brand">
                      <template #prepend>
                        <VIcon icon="tabler-award" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Merek</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ asset.brand }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem v-if="asset.model">
                      <template #prepend>
                        <VIcon icon="tabler-versions" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Model</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ asset.model }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem v-if="asset.serial_number">
                      <template #prepend>
                        <VIcon icon="tabler-hash" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Serial Number</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ asset.serial_number }}</VListItemSubtitle>
                    </VListItem>
                  </VList>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="6">
              <VCard variant="outlined" class="h-100">
                <VCardTitle class="text-h6 pa-4 pb-2">
                  <VIcon icon="tabler-calendar-dollar" class="me-2" />
                  Informasi Pembelian
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  <VList density="compact" class="pa-0">
                    <VListItem v-if="asset.purchase_date">
                      <template #prepend>
                        <VIcon icon="tabler-calendar" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Tanggal Pembelian</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ formatDate(asset.purchase_date) }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-currency-dollar" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Harga Pembelian</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">${{ asset.purchase_price?.toLocaleString() || '0' }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem v-if="asset.supplier">
                      <template #prepend>
                        <VIcon icon="tabler-building" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Supplier</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ asset.supplier }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem v-if="asset.warranty_until">
                      <template #prepend>
                        <VIcon icon="tabler-shield-check" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Garansi Sampai</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ formatDate(asset.warranty_until) }}</VListItemSubtitle>
                    </VListItem>

                    <VListItem v-if="asset.assigned_to">
                      <template #prepend>
                        <VIcon icon="tabler-user" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Ditugaskan Kepada</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ asset.assigned_to }}</VListItemSubtitle>
                    </VListItem>

                    <VListItem v-if="asset.department">
                      <template #prepend>
                        <VIcon icon="tabler-building-bank" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Departemen</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ asset.department }}</VListItemSubtitle>
                    </VListItem>
                  </VList>
                </VCardText>
              </VCard>
            </VCol>

            <!-- Description -->
            <VCol v-if="asset.description" cols="12">
              <VCard variant="outlined">
                <VCardTitle class="text-h6 pa-4 pb-2">
                  <VIcon icon="tabler-notes" class="me-2" />
                  Deskripsi
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  {{ asset.description }}
                </VCardText>
              </VCard>
            </VCol>

            <!-- Timestamps -->
            <VCol cols="12">
              <VCard variant="outlined">
                <VCardTitle class="text-h6 pa-4 pb-2">
                  <VIcon icon="tabler-clock" class="me-2" />
                  Riwayat
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  <VList density="compact" class="pa-0">
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-plus" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Dibuat</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ formatDateTime(asset.created_at) }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-edit" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Terakhir Diupdate</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ formatDateTime(asset.updated_at) }}</VListItemSubtitle>
                    </VListItem>
                  </VList>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VContainer>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-6">
        <VSpacer />
        <VBtn
          variant="outlined"
          prepend-icon="tabler-edit"
          color="primary"
          @click="editAsset"
        >
          Edit Asset
        </VBtn>
        <VBtn
          variant="outlined"
          prepend-icon="tabler-trash"
          color="error"
          @click="deleteAsset"
        >
          Hapus
        </VBtn>
        <VBtn
          variant="text"
          @click="closeDialog"
        >
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// Props
interface Props {
  show: boolean
  asset?: any
}

// Emits
interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'edit', asset: any): void
  (e: 'delete', asset: any): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Computed
const dialog = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

// Methods
const closeDialog = () => {
  emit('update:show', false)
}

const editAsset = () => {
  emit('edit', props.asset)
  closeDialog()
}

const deleteAsset = () => {
  emit('delete', props.asset)
  closeDialog()
}

// Utility functions
const getStatusColor = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'secondary'
    case 'maintenance': return 'warning'
    case 'disposed': return 'error'
    default: return 'primary'
  }
}

const getConditionColor = (condition: string) => {
  switch (condition) {
    case 'excellent': return 'success'
    case 'good': return 'info'
    case 'fair': return 'warning'
    case 'poor': return 'orange'
    case 'damaged': return 'error'
    default: return 'secondary'
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'active': return 'tabler-check-circle'
    case 'inactive': return 'tabler-circle'
    case 'maintenance': return 'tabler-tool'
    case 'disposed': return 'tabler-trash'
    default: return 'tabler-circle'
  }
}

const getCategoryIcon = (category: string) => {
  switch (category?.toLowerCase()) {
    case 'kitchen equipment': return 'tabler-chef-hat'
    case 'electronics': return 'tabler-device-desktop'
    case 'furniture': return 'tabler-armchair'
    case 'beverage equipment': return 'tabler-coffee'
    default: return 'tabler-box'
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
