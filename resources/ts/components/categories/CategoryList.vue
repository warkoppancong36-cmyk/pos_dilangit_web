<template>
  <VCard elevation="2">
    <VCardText>
      <VDataTable
        :headers="headers"
        :items="categories"
        :loading="loading"
        class="elevation-0"
        item-value="id_category"
        no-data-text="Tidak ada data kategori"
        loading-text="Memuat data kategori..."
        :items-per-page="perPage"
        hide-default-footer
      >
        <template #item.image="{ item }">
          <VAvatar
            size="60"
            class="my-2"
            rounded="lg"
            :color="item.image ? undefined : 'primary'"
          >
            <VImg
              v-if="item.image"
              :src="item.image_url || `/storage/categories/${item.image}`"
              :alt="item.name"
              cover
            />
            <VIcon
              v-else
              icon="mdi-shape"
              size="30"
              color="white"
            />
          </VAvatar>
        </template>
        <template #item.name="{ item }">
          <div class="font-weight-medium">{{ item.name }}</div>
        </template>
        <template #item.description="{ item }">
          <div class="text-body-2 description-cell">
            {{ item.description || '-' }}
          </div>
        </template>
        <template #item.active="{ item }">
          <VChip
            :color="item.active ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            <VIcon
              :icon="item.active ? 'mdi-check' : 'mdi-close'"
              size="16"
              class="me-1"
            />
            {{ item.active ? 'Aktif' : 'Tidak Aktif' }}
          </VChip>
        </template>
        <template #item.created_by_user.name="{ item }">
          <div class="d-flex align-center">
            <VIcon icon="mdi-account-circle" size="16" class="me-2" />
            <span class="text-body-2">
              {{ item.created_by_user?.name || '-' }}
            </span>
          </div>
        </template>
        <template #item.created_at="{ item }">
          <div class="d-flex align-center">
            <VIcon icon="mdi-calendar" size="16" class="me-2" />
            <span class="text-body-2">
              {{ new Date(item.created_at).toLocaleDateString('id-ID') }}
            </span>
          </div>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VTooltip text="Edit Kategori">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="$emit('edit', item)"
                />
              </template>
            </VTooltip>
            <VTooltip :text="item.active ? 'Nonaktifkan' : 'Aktifkan'">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  :icon="item.active ? 'mdi-eye-off' : 'mdi-eye'"
                  size="small"
                  variant="text"
                  :color="item.active ? 'warning' : 'success'"
                  :loading="toggleLoading[item.id_category]"
                  @click="$emit('toggle', item)"
                />
              </template>
            </VTooltip>
            <VTooltip text="Hapus Kategori">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  :loading="deleteLoading"
                  @click="$emit('delete', item)"
                />
              </template>
            </VTooltip>
          </div>
        </template>
      </VDataTable>
      
      <!-- Custom Pagination Footer -->
      <div class="d-flex justify-space-between align-center pa-4">
        <div class="text-body-2">
          {{ `${(currentPage - 1) * perPage + 1}-${Math.min(currentPage * perPage, pagination.total)} dari ${pagination.total}` }}
        </div>
        <div class="d-flex align-center gap-4">
          <VSelect
            :model-value="perPage"
            :items="[5, 10, 25, 50]"
            label="Per halaman"
            variant="outlined"
            density="compact"
            style="width: 120px;"
            @update:model-value="$emit('update:perPage', $event)"
          />
          <VPagination
            :model-value="currentPage"
            :length="Math.ceil(pagination.total / perPage)"
            :total-visible="5"
            size="small"
            @update:model-value="$emit('update:currentPage', $event)"
          />
        </div>
      </div>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
import type { Category, Pagination } from '@/composables/useCategories'

interface Props {
  categories: Category[]
  pagination: Pagination
  loading: boolean
  toggleLoading: Record<number, boolean>
  deleteLoading: boolean
  perPage: number
  currentPage: number
}

interface Emits {
  (e: 'edit', category: Category): void
  (e: 'delete', category: Category): void
  (e: 'toggle', category: Category): void
  (e: 'update:perPage', value: number): void
  (e: 'update:currentPage', value: number): void
}

defineProps<Props>()
defineEmits<Emits>()

// Table headers
const headers = [
  { title: 'Gambar', key: 'image', sortable: false, width: '100px' },
  { title: 'Nama', key: 'name', sortable: true },
  { title: 'Deskripsi', key: 'description', sortable: false },
  { title: 'Status', key: 'active', sortable: true, width: '120px' },
  { title: 'Dibuat Oleh', key: 'created_by_user.name', sortable: false, width: '150px' },
  { title: 'Tanggal Dibuat', key: 'created_at', sortable: true, width: '150px' },
  { title: 'Aksi', key: 'actions', sortable: false, width: '200px' }
]
</script>

<style scoped>
.description-cell {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
