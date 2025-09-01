<template>
  <VCard>
    <VCardTitle class="d-flex align-center pa-6 pb-4">
      <VIcon icon="tabler-users" class="me-3" />
      Daftar Pengguna
      <VSpacer />
      <VBtn
        v-if="selectedUsers.length > 0"
        color="error"
        variant="outlined"
        prepend-icon="tabler-trash"
        @click="$emit('bulk-delete', selectedUsers.map(u => u.id))"
      >
        Hapus Terpilih ({{ selectedUsers.length }})
      </VBtn>
    </VCardTitle>

    <VDivider />

    <VDataTable
      v-model="selectedUsers"
      :headers="headers"
      :items="userList"
      :loading="loading"
      :items-per-page="itemsPerPage"
      :page="currentPage"
      class="text-no-wrap"
      show-select
      @update:page="$emit('page-change', $event)"
    >
      <!-- User Info -->
      <template #item.user_info="{ item }">
        <div class="d-flex align-center py-3">
          <VAvatar
            size="40"
            :color="item.is_active ? 'primary' : 'secondary'"
            class="me-3"
          >
            <VIcon
              :icon="item.is_active ? 'tabler-user' : 'tabler-user-off'"
              size="20"
            />
          </VAvatar>
          <div>
            <div class="font-weight-bold">{{ item.name }}</div>
            <div class="text-caption text-medium-emphasis">@{{ item.username }}</div>
          </div>
        </div>
      </template>

      <!-- Contact Info -->
      <template #item.contact="{ item }">
        <div>
          <div class="text-body-2">{{ item.email }}</div>
          <div v-if="item.phone" class="text-caption text-medium-emphasis">
            {{ item.phone }}
          </div>
        </div>
      </template>

      <!-- Role -->
      <template #item.role="{ item }">
        <VChip
          :color="getRoleColor(item.role?.name)"
          variant="tonal"
          size="small"
        >
          {{ item.role?.name || 'No Role' }}
        </VChip>
      </template>

      <!-- Status -->
      <template #item.status="{ item }">
        <VChip
          :color="item.is_active ? 'success' : 'error'"
          :prepend-icon="item.is_active ? 'tabler-check' : 'tabler-x'"
          variant="tonal"
          size="small"
        >
          {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
        </VChip>
      </template>

      <!-- Last Login -->
      <template #item.last_login="{ item }">
        <div v-if="item.last_login_at" class="text-body-2">
          {{ formatDateTime(item.last_login_at) }}
        </div>
        <div v-else class="text-caption text-medium-emphasis">
          Belum pernah login
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="d-flex gap-1">
          <VBtn
            icon="tabler-eye"
            size="small"
            variant="text"
            @click="$emit('view', item)"
          />
          <VBtn
            v-if="canCreateEdit"
            icon="tabler-edit"
            size="small"
            variant="text"
            color="primary"
            @click="$emit('edit', item)"
          />
          <VBtn
            v-if="canDelete && item.id !== currentUserId"
            :icon="item.is_active ? 'tabler-user-off' : 'tabler-user-check'"
            size="small"
            variant="text"
            :color="item.is_active ? 'warning' : 'success'"
            :loading="toggleLoading[item.id]"
            @click="$emit('toggle', item)"
          />
          <VBtn
            v-if="canDelete && item.id !== currentUserId"
            icon="tabler-trash"
            size="small"
            variant="text"
            color="error"
            @click="$emit('delete', item)"
          />
        </div>
      </template>

      <!-- No data -->
      <template #no-data>
        <div class="text-center py-8">
          <VIcon icon="tabler-users-off" size="48" class="text-disabled mb-4" />
          <div class="text-h6 text-disabled">Tidak ada data pengguna</div>
          <div class="text-body-2 text-disabled">Tambahkan pengguna pertama untuk memulai</div>
        </div>
      </template>
    </VDataTable>

    <!-- Pagination -->
    <VDivider />
    <div class="pa-4 d-flex justify-space-between align-center">
      <div class="text-body-2 text-medium-emphasis">
        Menampilkan {{ Math.min((currentPage - 1) * itemsPerPage + 1, totalItems) }} 
        sampai {{ Math.min(currentPage * itemsPerPage, totalItems) }} 
        dari {{ totalItems }} hasil
      </div>
      
      <VPagination
        :model-value="currentPage"
        :length="Math.ceil(totalItems / itemsPerPage)"
        :total-visible="5"
        @update:model-value="$emit('page-change', $event)"
      />
    </div>
  </VCard>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { User } from '@/composables/useUserManagement'

interface Props {
  userList: User[]
  loading: boolean
  currentPage: number
  totalItems: number
  itemsPerPage: number
  canCreateEdit: boolean
  canDelete: boolean
  toggleLoading: { [key: number]: boolean }
}

interface Emits {
  (e: 'page-change', page: number): void
  (e: 'view', user: User): void
  (e: 'edit', user: User): void
  (e: 'delete', user: User): void
  (e: 'toggle', user: User): void
  (e: 'bulk-delete', userIds: number[]): void
}

const props = defineProps<Props>()
defineEmits<Emits>()

const selectedUsers = ref<User[]>([])

// Assume current user ID (should come from auth store)
const currentUserId = computed(() => 1) // Replace with actual current user ID

const headers = [
  { title: 'Pengguna', key: 'user_info', sortable: false },
  { title: 'Kontak', key: 'contact', sortable: false },
  { title: 'Role', key: 'role', sortable: false },
  { title: 'Status', key: 'status', sortable: false },
  { title: 'Terakhir Login', key: 'last_login', sortable: false },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center' as const }
]

const getRoleColor = (roleName?: string) => {
  switch (roleName?.toLowerCase()) {
    case 'admin': return 'error'
    case 'manager': return 'warning'
    case 'cashier': return 'primary'
    case 'staff': return 'info'
    default: return 'secondary'
  }
}

const formatDateTime = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
