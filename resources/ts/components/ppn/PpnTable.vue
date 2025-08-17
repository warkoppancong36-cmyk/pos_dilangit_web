<template>
  <VCard>
    <VDataTable
      :headers="headers"
      :items="ppnList"
      :loading="loading"
      :items-per-page="itemsPerPage"
      :page="currentPage"
      :server-items-length="totalItems"
      class="elevation-1"
      @update:page="$emit('pageChange', $event)"
    >
      <!-- NO column -->
      <template #item.no="{ index }">
        {{ (currentPage - 1) * itemsPerPage + index + 1 }}
      </template>

      <!-- Nominal column -->
      <template #item.nominal="{ item }">
        <VChip
          :color="item.nominal > 0 ? 'success' : 'default'"
          size="small"
          class="ppn-nominal-chip"
        >
          {{ item.nominal }}%
        </VChip>
      </template>

      <!-- Status column -->
      <template #item.active="{ item }">
        <VChip
          :color="item.active ? 'success' : 'error'"
          size="small"
          class="ppn-status-chip"
        >
          {{ item.active ? 'Aktif' : 'Tidak Aktif' }}
        </VChip>
      </template>

      <!-- Created At column -->
      <template #item.created_at="{ item }">
        <span class="ppn-date">{{ formatDate(item.created_at) }}</span>
      </template>

      <!-- Actions column -->
      <template #item.actions="{ item }">
        <div class="d-flex gap-2">
          <VBtn
            v-if="canCreateEdit"
            color="primary"
            variant="text"
            size="small"
            icon="mdi-pencil"
            @click="$emit('edit', item)"
          />
          <VBtn
            v-if="canCreateEdit"
            :color="item.active ? 'warning' : 'success'"
            variant="text"
            size="small"
            :icon="item.active ? 'mdi-pause' : 'mdi-play'"
            :loading="toggleLoading[item.id_ppn]"
            :disabled="toggleLoading[item.id_ppn]"
            @click="$emit('toggle', item)"
          />
          <VBtn
            v-if="canDelete"
            color="error"
            variant="text"
            size="small"
            icon="mdi-delete"
            @click="$emit('delete', item)"
          />
        </div>
      </template>
    </VDataTable>
  </VCard>
</template>

<script setup lang="ts">
import type { Ppn } from '@/composables/usePpn'

interface Props {
  ppnList: Ppn[]
  loading: boolean
  currentPage: number
  totalItems: number
  itemsPerPage: number
  canCreateEdit: boolean
  canDelete: boolean
  toggleLoading: { [key: number]: boolean }
}

interface Emits {
  (e: 'pageChange', page: number): void
  (e: 'edit', ppn: Ppn): void
  (e: 'delete', ppn: Ppn): void
  (e: 'toggle', ppn: Ppn): void
}

defineProps<Props>()
defineEmits<Emits>()

const headers = [
  { title: 'NO', key: 'no', sortable: false },
  { title: 'Nama', key: 'name', sortable: true },
  { title: 'Nominal (%)', key: 'nominal', sortable: true },
  { title: 'Deskripsi', key: 'description', sortable: false },
  { title: 'Status', key: 'active', sortable: true },
  { title: 'Dibuat Oleh', key: 'creator.username', sortable: false },
  { title: 'Tanggal Dibuat', key: 'created_at', sortable: true },
  { title: 'Aksi', key: 'actions', sortable: false }
]

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}
</script>

<style scoped>
.ppn-nominal-chip {
  font-weight: 600;
  letter-spacing: 0.5px;
}

.ppn-status-chip {
  font-weight: 500;
}

.ppn-date {
  color: #8D7053;
  font-weight: 500;
}

/* Dark theme adjustments */
.v-theme--dark .ppn-date {
  color: #B8946A;
}
</style>
