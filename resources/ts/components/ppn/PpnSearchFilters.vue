<template>
  <VCard class="mb-6">
    <VCardText>
      <VRow>
        <VCol cols="12" md="4">
          <VTextField
            :model-value="searchValue"
            label="Cari PPN..."
            prepend-inner-icon="mdi-magnify"
            clearable
            @update:model-value="$emit('update:search', $event)"
            @keyup.enter="$emit('search')"
            @click:clear="$emit('search')"
          />
        </VCol>
        <VCol cols="12" md="3">
          <VSelect
            :model-value="activeFilter"
            label="Filter Status"
            :items="[
              { value: 'all', title: 'Semua Status' },
              { value: 'active', title: 'Hanya Aktif' },
              { value: 'inactive', title: 'Hanya Tidak Aktif' }
            ]"
            @update:model-value="$emit('update:activeFilter', $event)"
          />
        </VCol>
        <VCol cols="12" md="2">
          <VSelect
            :model-value="sortBy"
            label="Urutkan Berdasarkan"
            :items="[
              { value: 'created_at', title: 'Tanggal Dibuat' },
              { value: 'name', title: 'Nama' },
              { value: 'nominal', title: 'Nominal' },
              { value: 'updated_at', title: 'Tanggal Diperbarui' }
            ]"
            @update:model-value="$emit('update:sortBy', $event)"
          />
        </VCol>
        <VCol cols="12" md="2">
          <VSelect
            :model-value="sortOrder"
            label="Urutan"
            :items="[
              { value: 'desc', title: 'Menurun' },
              { value: 'asc', title: 'Menaik' }
            ]"
            @update:model-value="$emit('update:sortOrder', $event)"
          />
        </VCol>
        <VCol cols="12" md="1">
          <VBtn
            color="primary"
            variant="outlined"
            icon="mdi-refresh"
            @click="$emit('refresh')"
          />
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
interface Props {
  searchValue: string
  activeFilter: 'all' | 'active' | 'inactive'
  sortBy: string
  sortOrder: 'asc' | 'desc'
}

interface Emits {
  (e: 'update:search', value: string): void
  (e: 'update:activeFilter', value: 'all' | 'active' | 'inactive'): void
  (e: 'update:sortBy', value: string): void
  (e: 'update:sortOrder', value: 'asc' | 'desc'): void
  (e: 'search'): void
  (e: 'refresh'): void
}

defineProps<Props>()
defineEmits<Emits>()
</script>
