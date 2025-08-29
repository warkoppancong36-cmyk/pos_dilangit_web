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
  { title: 'all', value: 'all' },
  { title: 'in-stock', value: 'in-stock' },
  { title: 'low-stock', value: 'low-stock' },
  { title: 'out-of-stock', value: 'out-of-stock' }
]

const onFilterChange = () => {
  console.log('ItemSearchFilters - onFilterChange called with:', localFilters.value)
  emit('update:filters', { ...localFilters.value })
}

const resetFilters = () => {
  localFilters.value = {
    search: '',
    active: 'all',
    supplier_id: undefined,
    unit: '',
    stock_status: 'all',
    expiring_days: undefined,
    show_expired: false
  }
  onFilterChange()
}

const hasActiveFilters = computed(() => {
  return (
    localFilters.value.search ||
    localFilters.value.active !== 'all' ||
    localFilters.value.supplier_id ||
    localFilters.value.unit ||
    localFilters.value.stock_status !== 'all' ||
    localFilters.value.expiring_days ||
    localFilters.value.show_expired
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

        <!-- Actions -->
        <VCol cols="12" md="4" class="d-flex align-center gap-3">
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

      <!-- Advanced Filters (Collapsible) -->
      <VExpansionPanels class="mt-4">
        <VExpansionPanel>
          <VExpansionPanelTitle class="text-sm">
            <VIcon icon="tabler-adjustments-horizontal" class="me-2" />
            Filter Lanjutan
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <VRow>
              <!-- Unit Filter -->
              <VCol cols="12" md="3">
                <VTextField
                  v-model="localFilters.unit"
                  label="Satuan"
                  placeholder="kg, pcs, liter..."
                  prepend-inner-icon="tabler-ruler"
                  clearable
                  variant="outlined"
                  @update:model-value="onFilterChange"
                />
              </VCol>

              <!-- Expiring Days -->
              <VCol cols="12" md="3">
                <VTextField
                  v-model="localFilters.expiring_days"
                  type="number"
                  label="Akan kadaluarsa (hari)"
                  placeholder="30"
                  prepend-inner-icon="tabler-calendar-time"
                  clearable
                  variant="outlined"
                  @update:model-value="onFilterChange"
                />
              </VCol>

              <!-- Show Expired -->
              <VCol cols="12" md="3" class="d-flex align-center">
                <VCheckbox
                  v-model="localFilters.show_expired"
                  label="Tampilkan yang kadaluarsa"
                  color="warning"
                  @update:model-value="onFilterChange"
                />
              </VCol>

              <!-- Kitchen Availability -->
              <VCol cols="12" md="3" class="d-flex align-center">
                <VCheckbox
                  v-model="localFilters.available_in_kitchen"
                  label="Tersedia di Kitchen"
                  color="primary"
                  @update:model-value="onFilterChange"
                />
              </VCol>

              <!-- Bar Availability -->
              <VCol cols="12" md="3" class="d-flex align-center">
                <VCheckbox
                  v-model="localFilters.available_in_bar"
                  label="Tersedia di Bar"
                  color="secondary"
                  @update:model-value="onFilterChange"
                />
              </VCol>
            </VRow>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>
    </VCardText>
  </VCard>
</template>

<style scoped lang="scss">
.coffee-icon {
  color: rgb(var(--v-theme-primary));
}
</style>
