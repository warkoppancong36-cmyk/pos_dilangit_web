<script setup lang="ts">
import type { SupplierFilters } from '@/utils/api/SuppliersApi'
import { computed, ref, watch } from 'vue'

// Debounce utility
let searchTimeout: any = null
const debounceSearch = (callback: Function, delay: number = 500) => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(callback, delay)
}

interface Props {
  filters: SupplierFilters
  loading?: boolean
}

interface Emits {
  (e: 'update:filters', filters: SupplierFilters): void
  (e: 'search'): void
  (e: 'reset'): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<Emits>()

// Local reactive filters
const localFilters = ref<SupplierFilters>({ ...props.filters })

// Watch for external filter changes
watch(
  () => props.filters,
  (newFilters) => {
    localFilters.value = { ...newFilters }
  },
  { deep: true }
)

// Update filters
const updateFilters = (key: keyof SupplierFilters, value: any) => {
  
  localFilters.value[key] = value
  
  
  // Emit immediately for non-search filters
  if (key !== 'search') {
    emit('update:filters', { ...localFilters.value })
  } else {
    // Debounce search
    debounceSearch(() => {
      console.log('Emitting debounced search filter update:', { ...localFilters.value })
      emit('update:filters', { ...localFilters.value })
    })
  }
}

// Search
const handleSearch = () => {
  emit('search')
}

// Reset filters
const handleReset = () => {
  const resetFilters: SupplierFilters = {
    search: '',
    status: undefined,
    city: '',
    province: ''
  }
  localFilters.value = { ...resetFilters }
  emit('update:filters', resetFilters)
  emit('reset')
}

// Status options
const statusOptions = [
  { title: 'Semua Status', value: undefined },
  { title: 'Aktif', value: 'active' },
  { title: 'Nonaktif', value: 'inactive' }
]

// Check if any filter is active
const hasActiveFilters = computed(() => {
  return !!(
    localFilters.value.search ||
    localFilters.value.status ||
    localFilters.value.city ||
    localFilters.value.province
  )
})
</script>

<template>
  <VCard>
    <VCardText>
      <VRow>
        <!-- Search -->
        <VCol cols="12" md="4">
          <VTextField
            v-model="localFilters.search"
            placeholder="Cari nama, kode, atau email supplier..."
            prepend-inner-icon="tabler-search"
            clearable
            variant="outlined"
            @update:model-value="(value) => updateFilters('search', value)"
            @keydown.enter="handleSearch"
          />
        </VCol>

        <!-- Status Filter -->
        <VCol cols="12" md="2">
          <VSelect
            v-model="localFilters.status"
            :items="statusOptions"
            item-title="title"
            item-value="value"
            placeholder="Status"
            variant="outlined"
            clearable
            @update:model-value="(value) => updateFilters('status', value)"
          />
        </VCol>

        <!-- City Filter -->
        <VCol cols="12" md="2">
          <VTextField
            v-model="localFilters.city"
            placeholder="Kota"
            variant="outlined"
            clearable
            @update:model-value="(value) => updateFilters('city', value)"
          />
        </VCol>

        <!-- Province Filter -->
        <VCol cols="12" md="2">
          <VTextField
            v-model="localFilters.province"
            placeholder="Provinsi"
            variant="outlined"
            clearable
            @update:model-value="(value) => updateFilters('province', value)"
          />
        </VCol>

        <!-- Action Buttons -->
        <VCol cols="12" md="2">
          <div class="d-flex gap-2">
            <VBtn
              color="primary"
              :loading="loading"
              @click="handleSearch"
            >
              <VIcon icon="tabler-search" />
              Cari
            </VBtn>
            
            <VBtn
              v-if="hasActiveFilters"
              variant="outlined"
              color="secondary"
              @click="handleReset"
            >
              <VIcon icon="tabler-x" />
              Reset
            </VBtn>
          </div>
        </VCol>
      </VRow>

      <!-- Active Filters Display -->
      <div v-if="hasActiveFilters" class="mt-4">
        <div class="d-flex align-center gap-2 flex-wrap">
          <span class="text-caption text-medium-emphasis">Filter aktif:</span>
          
          <VChip
            v-if="localFilters.search"
            size="small"
            closable
            @click:close="updateFilters('search', '')"
          >
            Pencarian: {{ localFilters.search }}
          </VChip>
          
          <VChip
            v-if="localFilters.status"
            size="small"
            closable
            @click:close="updateFilters('status', undefined)"
          >
            Status: {{ localFilters.status === 'active' ? 'Aktif' : 'Nonaktif' }}
          </VChip>
          
          <VChip
            v-if="localFilters.city"
            size="small"
            closable
            @click:close="updateFilters('city', '')"
          >
            Kota: {{ localFilters.city }}
          </VChip>
          
          <VChip
            v-if="localFilters.province"
            size="small"
            closable
            @click:close="updateFilters('province', '')"
          >
            Provinsi: {{ localFilters.province }}
          </VChip>
        </div>
      </div>
    </VCardText>
  </VCard>
</template>
