<template>
  <VCard class="mb-6">
    <VCardText>
      <VRow>
        <VCol cols="12" md="4">
          <VTextField
            :model-value="search"
            placeholder="Cari nama atau alamat perangkat..."
            prepend-inner-icon="mdi-magnify"
            clearable
            @update:model-value="$emit('update:search', $event)"
            @click:clear="$emit('update:search', '')"
          />
        </VCol>
        
        <VCol cols="12" md="3">
          <VSelect
            :model-value="typeFilter"
            :items="typeOptions"
            label="Filter Tipe"
            placeholder="Semua Tipe"
            clearable
            @update:model-value="$emit('update:type-filter', $event)"
          />
        </VCol>
        
        <VCol cols="12" md="2">
          <VSwitch
            :model-value="activeOnly"
            label="Hanya Aktif"
            color="primary"
            @update:model-value="$emit('update:active-only', $event)"
          />
        </VCol>
        
        <VCol cols="12" md="2">
          <VSelect
            :model-value="perPage"
            :items="perPageOptions"
            label="Per Halaman"
            @update:model-value="$emit('update:per-page', $event)"
          />
        </VCol>
        
        <VCol cols="12" md="1" class="d-flex align-center">
          <VBtn
            icon
            color="primary"
            @click="$emit('search')"
          >
            <VIcon icon="mdi-refresh" />
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
interface Props {
  search: string
  typeFilter: string
  activeOnly: boolean
  perPage: number
}

interface Emits {
  (e: 'update:search', value: string): void
  (e: 'update:type-filter', value: string): void
  (e: 'update:active-only', value: boolean): void
  (e: 'update:per-page', value: number): void
  (e: 'search'): void
}

defineProps<Props>()
defineEmits<Emits>()

const typeOptions = [
  { title: 'Printer', value: 'printer' },
  { title: 'Scanner', value: 'scanner' },
  { title: 'Cash Drawer', value: 'cash_drawer' },
  { title: 'Scale', value: 'scale' },
  { title: 'Other', value: 'other' }
]

const perPageOptions = [
  { title: '10', value: 10 },
  { title: '25', value: 25 },
  { title: '50', value: 50 },
  { title: '100', value: 100 }
]
</script>
