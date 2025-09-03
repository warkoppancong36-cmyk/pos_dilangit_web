<template>
  <VCard class="mb-6">
    <VCardText class="pa-6">
      <VRow>
        <!-- Search Input -->
        <VCol cols="12" md="4">
          <VTextField
            :model-value="searchValue"
            label="Cari role..."
            placeholder="Nama role, display name, atau deskripsi"
            variant="outlined"
            prepend-inner-icon="tabler-search"
            clearable
            @update:model-value="$emit('update:search', $event)"
            @keyup.enter="$emit('search')"
            @click:clear="$emit('update:search', ''); $emit('search')"
          />
        </VCol>

        <!-- Status Filter -->
        <VCol cols="12" md="2">
          <VSelect
            :model-value="statusFilter"
            label="Status"
            variant="outlined"
            :items="statusOptions"
            @update:model-value="$emit('update:status-filter', $event)"
          />
        </VCol>

        <!-- Permission Count Filter -->
        <VCol cols="12" md="2">
          <VSelect
            :model-value="permissionFilter"
            label="Permissions"
            variant="outlined"
            :items="permissionFilterOptions"
            @update:model-value="$emit('update:permission-filter', $event)"
          />
        </VCol>

        <!-- Sort By -->
        <VCol cols="12" md="2">
          <VSelect
            :model-value="sortBy"
            label="Urutkan"
            variant="outlined"
            :items="sortOptions"
            @update:model-value="$emit('update:sort-by', $event)"
          />
        </VCol>

        <!-- Sort Order -->
        <VCol cols="12" md="1">
          <VBtn
            :icon="sortOrder === 'asc' ? 'tabler-sort-ascending' : 'tabler-sort-descending'"
            variant="outlined"
            color="primary"
            @click="$emit('update:sort-order', sortOrder === 'asc' ? 'desc' : 'asc')"
          />
        </VCol>

        <!-- Actions -->
        <VCol cols="12" md="1">
          <div class="d-flex gap-2">
            <VBtn
              icon="tabler-refresh"
              variant="outlined"
              @click="$emit('refresh')"
            />
          </div>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
interface Props {
  searchValue: string
  statusFilter: 'all' | 'active' | 'inactive'
  permissionFilter: string
  sortBy: string
  sortOrder: 'asc' | 'desc'
}

interface Emits {
  (e: 'update:search', value: string): void
  (e: 'update:status-filter', value: 'all' | 'active' | 'inactive'): void
  (e: 'update:permission-filter', value: string): void
  (e: 'update:sort-by', value: string): void
  (e: 'update:sort-order', value: 'asc' | 'desc'): void
  (e: 'search'): void
  (e: 'refresh'): void
}

defineProps<Props>()
defineEmits<Emits>()

const statusOptions = [
  { title: 'Semua Status', value: 'all' },
  { title: 'Aktif', value: 'active' },
  { title: 'Tidak Aktif', value: 'inactive' },
]

const permissionFilterOptions = [
  { title: 'Semua Permission', value: 'all' },
  { title: 'Punya Permissions', value: 'with_permissions' },
  { title: 'Tanpa Permissions', value: 'without_permissions' },
]

const sortOptions = [
  { title: 'Nama', value: 'name' },
  { title: 'Display Name', value: 'display_name' },
  { title: 'Jumlah Permissions', value: 'permissions_count' },
  { title: 'Jumlah Users', value: 'users_count' },
  { title: 'Tanggal Dibuat', value: 'created_at' },
  { title: 'Terakhir Diperbarui', value: 'updated_at' },
]
</script>
