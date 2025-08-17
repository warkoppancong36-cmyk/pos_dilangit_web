<script setup lang="ts">
import type { Supplier } from '@/utils/api/SuppliersApi'

interface Props {
  suppliers: Supplier[]
  loading?: boolean
  totalItems?: number
  page?: number
  itemsPerPage?: number
}

interface Emits {
  (e: 'edit', supplier: Supplier): void
  (e: 'delete', supplier: Supplier): void
  (e: 'update:page', page: number): void
  (e: 'update:itemsPerPage', itemsPerPage: number): void
  (e: 'sort', sortBy: string): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  totalItems: 0,
  page: 1,
  itemsPerPage: 10
})

const emit = defineEmits<Emits>()

// Headers
const headers = [
  { title: 'Kode', key: 'code', sortable: true },
  { title: 'Nama', key: 'name', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Telepon', key: 'phone', sortable: false },
  { title: 'Kota', key: 'city', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center' as const }
]

// Format status
const getStatusColor = (status: string) => {
  return status === 'active' ? 'success' : 'error'
}

const getStatusText = (status: string) => {
  return status === 'active' ? 'Aktif' : 'Nonaktif'
}

// Handle actions
const handleEdit = (supplier: Supplier) => {
  emit('edit', supplier)
}

const handleDelete = (supplier: Supplier) => {
  emit('delete', supplier)
}

// Handle pagination
const handlePageChange = (page: number) => {
  emit('update:page', page)
}

const handleItemsPerPageChange = (itemsPerPage: number) => {
  emit('update:itemsPerPage', itemsPerPage)
}

// Handle sorting
const handleSort = (sortBy: any) => {
  if (sortBy && sortBy.length > 0) {
    const column = sortBy[0]
    const direction = column.order === 'desc' ? '-' : ''
    emit('sort', `${direction}${column.key}`)
  }
}
</script>

<template>
  <VCard>
    <VCardText class="pa-0">
      <VDataTableServer
        :page="page"
        :items-per-page="itemsPerPage"
        :headers="headers"
        :items="suppliers"
        :items-length="totalItems"
        :loading="loading"
        :items-per-page-options="[
          { value: 10, title: '10' },
          { value: 25, title: '25' },
          { value: 50, title: '50' },
          { value: 100, title: '100' }
        ]"
        loading-text="Memuat data supplier..."
        no-data-text="Tidak ada data supplier"
        items-per-page-text="Supplier per halaman:"
        page-text="{0}-{1} dari {2}"
        class="text-no-wrap"
        @update:page="handlePageChange"
        @update:items-per-page="handleItemsPerPageChange"
        @update:sort-by="handleSort"
      >
        <!-- Code -->
        <template #item.code="{ item }">
          <div class="font-weight-medium">
            {{ item.code }}
          </div>
        </template>

        <!-- Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center">
            <VAvatar
              size="32"
              color="primary"
              variant="tonal"
              class="me-3"
            >
              <span class="text-sm font-weight-medium">
                {{ item.name.charAt(0).toUpperCase() }}
              </span>
            </VAvatar>
            <div>
              <div class="font-weight-medium">{{ item.name }}</div>
              <div v-if="item.contact_person" class="text-caption text-medium-emphasis">
                {{ item.contact_person }}
              </div>
            </div>
          </div>
        </template>

        <!-- Email -->
        <template #item.email="{ item }">
          <a
            v-if="item.email"
            :href="`mailto:${item.email}`"
            class="text-decoration-none"
          >
            {{ item.email }}
          </a>
          <span v-else class="text-medium-emphasis">-</span>
        </template>

        <!-- Phone -->
        <template #item.phone="{ item }">
          <a
            v-if="item.phone"
            :href="`tel:${item.phone}`"
            class="text-decoration-none"
          >
            {{ item.phone }}
          </a>
          <span v-else class="text-medium-emphasis">-</span>
        </template>

        <!-- City -->
        <template #item.city="{ item }">
          <div v-if="item.city || item.province">
            <div v-if="item.city" class="font-weight-medium">{{ item.city }}</div>
            <div v-if="item.province" class="text-caption text-medium-emphasis">
              {{ item.province }}
            </div>
          </div>
          <span v-else class="text-medium-emphasis">-</span>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            :color="getStatusColor(item.status)"
            variant="tonal"
            size="small"
          >
            {{ getStatusText(item.status) }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon
              size="small"
              color="primary"
              variant="text"
              @click="handleEdit(item)"
            >
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent" location="top">
                Edit Supplier
              </VTooltip>
            </VBtn>

            <VBtn
              icon
              size="small"
              color="error"
              variant="text"
              @click="handleDelete(item)"
            >
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent" location="top">
                Hapus Supplier
              </VTooltip>
            </VBtn>
          </div>
        </template>

        <!-- Loading -->
        <template #loading>
          <VSkeletonLoader
            v-for="n in 5"
            :key="n"
            type="table-row"
            class="border-b"
          />
        </template>

        <!-- No data -->
        <template #no-data>
          <div class="text-center py-8">
            <VIcon
              icon="tabler-building-store"
              size="48"
              class="text-medium-emphasis mb-4"
            />
            <div class="text-h6 mb-2">Tidak ada data supplier</div>
            <p class="text-medium-emphasis mb-0">
              Silakan tambah supplier baru atau ubah filter pencarian
            </p>
          </div>
        </template>
      </VDataTableServer>
    </VCardText>
  </VCard>
</template>
