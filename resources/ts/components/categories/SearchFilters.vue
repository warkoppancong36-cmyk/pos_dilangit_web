<template>
  <VCard class="mb-6" elevation="2">
    <VCardTitle class="pa-6 pb-4">Filter & Pencarian</VCardTitle>
    <VCardText class="pt-0">
      <VRow align="center">
        <VCol cols="12" md="4">
          <VTextField
            :model-value="search"
            label="Cari kategori..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="comfortable"
            hide-details
            clearable
            @update:model-value="$emit('update:search', $event)"
            @keyup.enter="$emit('search')"
            @click:clear="$emit('search')"
          />
        </VCol>
        <VCol cols="12" md="3">
          <VSelect
            :model-value="statusFilter"
            :items="statusOptions"
            label="Status"
            variant="outlined"
            density="comfortable"
            hide-details
            @update:model-value="$emit('update:statusFilter', $event)"
          />
        </VCol>
        <VCol cols="12" md="3">
          <VSelect
            :model-value="perPage"
            :items="perPageOptions"
            label="Per Halaman"
            variant="outlined"
            density="comfortable"
            hide-details
            @update:model-value="$emit('update:perPage', $event)"
          />
        </VCol>
        <VCol cols="12" md="2">
          <VBtn
            color="primary"
            variant="elevated"
            block
            prepend-icon="mdi-magnify"
            @click="$emit('search')"
          >
            Cari
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
interface Props {
  search: string
  statusFilter: string
  perPage: number
}

interface Emits {
  (e: 'update:search', value: string): void
  (e: 'update:statusFilter', value: string): void
  (e: 'update:perPage', value: number): void
  (e: 'search'): void
}

defineProps<Props>()
defineEmits<Emits>()

// Options
const statusOptions = [
  { title: 'Semua Status', value: 'all' },
  { title: 'Aktif', value: 'active' },
  { title: 'Tidak Aktif', value: 'inactive' }
]

const perPageOptions = [
  { title: '5 per halaman', value: 5 },
  { title: '10 per halaman', value: 10 },
  { title: '25 per halaman', value: 25 },
  { title: '50 per halaman', value: 50 }
]
</script>
