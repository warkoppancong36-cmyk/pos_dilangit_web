<template>
  <VCard class="mb-6">
    <VCardText class="pa-6">
      <VRow>
        <!-- Search Input -->
        <VCol cols="12" md="4">
          <VTextField
            :model-value="searchValue"
            label="Cari pengguna..."
            placeholder="Nama, username, email, atau phone"
            variant="outlined"
            prepend-inner-icon="tabler-search"
            clearable
            @update:model-value="$emit('update:search', $event); $emit('search')"
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

        <!-- Role Filter -->
        <VCol cols="12" md="2">
          <VSelect
            :model-value="roleFilter"
            label="Role"
            variant="outlined"
            :items="roleOptions"
            item-title="name"
            item-value="id"
            clearable
            @update:model-value="$emit('update:role-filter', $event)"
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
  roleFilter?: number
  sortBy: string
  sortOrder: 'asc' | 'desc'
  roleOptions: Array<{ id: number; name: string }>
}

interface Emits {
  (e: 'update:search', value: string): void
  (e: 'update:status-filter', value: 'all' | 'active' | 'inactive'): void
  (e: 'update:role-filter', value?: number): void
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
  { title: 'Nonaktif', value: 'inactive' }
]

const sortOptions = [
  { title: 'Tanggal Dibuat', value: 'created_at' },
  { title: 'Nama', value: 'name' },
  { title: 'Username', value: 'username' },
  { title: 'Email', value: 'email' },
  { title: 'Terakhir Login', value: 'last_login_at' },
  { title: 'Status', value: 'is_active' }
]
</script>
