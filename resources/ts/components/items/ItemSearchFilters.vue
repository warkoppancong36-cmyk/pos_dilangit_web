<script setup lang="ts">
import type { ItemFilters } from '@/composables/useItems';

interface Props {
  filters: ItemFilters
}

interface Emits {
  (e: 'update:filters', filters: ItemFilters): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const localFilters = ref<ItemFilters>({ ...props.filters })

// Watch for external filter changes
watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...newFilters }
}, { deep: true })

// Status options
const statusOptions = [
  { title: 'all', value: 'all' },
  { title: 'active', value: 'active' },
  { title: 'inactive', value: 'inactive' }
]

// Stock status options  
const stockOptions = [
  { title: 'Semua Status', value: '' },
  { title: 'Stok Tersedia', value: 'in_stock' },
  { title: 'Stok Menipis', value: 'low_stock' },
  { title: 'Stok Habis', value: 'out_of_stock' }
]

// Station options
const stationOptions = [
  { title: 'Semua station', value: 'all' },
  { title: 'Kitchen Only', value: 'kitchen' },
  { title: 'Bar Only', value: 'bar' },
  { title: 'Kitchen & Bar', value: 'both' }
]

const onFilterChange = () => {
  console.log('ItemSearchFilters - onFilterChange called with:', localFilters.value)
  emit('update:filters', { ...localFilters.value })
}

const resetFilters = () => {
  localFilters.value = {
    search: '',
    active: 'all',
    unit: '',
    stock_status: '',
    station: 'all',
  }
  onFilterChange()
}

const hasActiveFilters = computed(() => {
  return (
    localFilters.value.search ||
    localFilters.value.active !== 'all' ||
    localFilters.value.unit ||
    localFilters.value.stock_status ||
    localFilters.value.station !== 'all'
  )
})
</script>

<template>
  <VCard class="mb-6">
    <VCardTitle class="d-flex align-center gap-2">
      <VIcon
        icon="tabler-filter"
        class="coffee-icon"
      />
      Filter & Pencarian Item
    </VCardTitle>
    
    <VCardText>
      <VRow>
        <!-- Search -->
        <VCol cols="12" md="4">
          <VTextField
            v-model="localFilters.search"
            label="Cari item..."
            placeholder="Nama, kode, atau deskripsi"
            prepend-inner-icon="tabler-search"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>

        <!-- Status Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.active"
            :items="statusOptions"
            label="Status"
            prepend-inner-icon="tabler-toggle-left"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>

        <!-- Stock Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.stock_status"
            :items="stockOptions"
            label="Stok"
            prepend-inner-icon="tabler-package"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>

        <!-- Station Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.station"
            :items="stationOptions"
            label="Station"
            prepend-inner-icon="tabler-building-store"
            clearable
            variant="outlined"
            @update:model-value="onFilterChange"
          />
        </VCol>

        <!-- Actions -->
        <VCol cols="12" md="2" class="d-flex align-center gap-3">
          <VBtn
            color="primary"
            variant="tonal"
            prepend-icon="tabler-search"
            @click="onFilterChange"
          >
            Cari
          </VBtn>

          <VBtn
            v-if="hasActiveFilters"
            color="secondary"
            variant="outlined"
            prepend-icon="tabler-x"
            @click="resetFilters"
          >
            Reset
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<style scoped lang="scss">
.coffee-icon {
  color: rgb(var(--v-theme-primary));
}
</style>
