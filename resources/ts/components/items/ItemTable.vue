<script setup lang="ts">
import type { Item } from '@/composables/useItems'
import { formatCurrency } from '@/utils/helpers'

interface Props {
  items: Item[]
  loading?: boolean
  selectedItems?: number[]
  currentPage?: number
  itemsPerPage?: number
  totalItems?: number
}

interface Emits {
  (e: 'update:selected-items', selected: number[]): void
  (e: 'add-item'): void
  (e: 'edit-item', item: Item): void
  (e: 'delete-item', item: Item): void
  (e: 'update:page', page: number): void
  (e: 'update:items-per-page', itemsPerPage: number): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  selectedItems: () => [],
  currentPage: 1,
  itemsPerPage: 15,
  totalItems: 0
})

const emit = defineEmits<Emits>()

const headers = [
  { title: 'ITEM', key: 'item_info', align: 'start' as const },
  { title: 'SATUAN', key: 'unit', align: 'center' as const },
  { title: 'HARGA/UNIT', key: 'cost_per_unit', align: 'end' as const },
  { title: 'STOK', key: 'current_stock', align: 'center' as const },
  { title: 'MIN. STOK', key: 'minimum_stock', align: 'center' as const },
  { title: 'STATUS STOK', key: 'stock_status', align: 'center' as const },
  { title: 'LOKASI', key: 'storage_location', align: 'center' as const },
  { title: 'STATUS', key: 'active', align: 'center' as const },
  { title: 'STATION', key: 'station_availability', align: 'center' as const },
  { title: 'JENIS LAYANAN', key: 'service_type', align: 'center' as const },
  { title: 'AKSI', key: 'actions', align: 'center' as const, sortable: false }
]

const getStockStatusColor = (status: string) => {
  switch (status) {
    case 'in_stock':
      return 'success'
    case 'low_stock':
      return 'warning'
    case 'out_of_stock':
      return 'error'
    default:
      return 'success'
  }
}

const getStockStatusText = (status: string) => {
  switch (status) {
    case 'in_stock':
      return 'Tersedia'
    case 'low_stock':
      return 'Stok Rendah'
    case 'out_of_stock':
      return 'Stok Habis'
    default:
      return 'Tersedia'
  }
}

const getStockProgressColor = (percentage: number) => {
  if (percentage <= 20) return 'error'
  if (percentage <= 40) return 'warning'
  return 'success'
}
</script>

<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between">
      <div class="d-flex align-center gap-2">
        <VIcon
          icon="tabler-list"
          class="coffee-icon"
        />
        Data Item
        <VChip
          v-if="totalItems"
          size="small"
          color="primary"
          variant="tonal"
        >
          {{ totalItems }} item
        </VChip>
      </div>
      
      <div class="d-flex align-center gap-2">
        <VBtn
          prepend-icon="tabler-plus"
          color="primary"
          @click="$emit('add-item')"
        >
          Tambah Item
        </VBtn>
      </div>
    </VCardTitle>

    <VDivider />

    <VDataTableServer
      :model-value="selectedItems"
      @update:model-value="$emit('update:selected-items', $event)"
      :headers="headers"
      :items="items"
      :loading="loading"
      :items-length="totalItems"
      :items-per-page="itemsPerPage"
      :page="currentPage"
      item-value="id_item"
      show-select
      @update:page="$emit('update:page', $event)"
      @update:items-per-page="$emit('update:items-per-page', $event)"
      class="text-no-wrap"
    >
      <template #item.item_info="{ item }">
        <div class="d-flex flex-column">
          <div class="font-weight-medium text-high-emphasis">{{ item.name }}</div>
          <VChip
            color="primary"
            variant="outlined"
            size="small"
            class="font-mono mt-1 align-self-start"
          >
            {{ item.item_code }}
          </VChip>
          <div v-if="item.description" class="text-caption text-medium-emphasis mt-1">
            {{ item.description }}
          </div>
        </div>
      </template>

      <template #item.unit="{ item }">
        <VChip color="info" variant="tonal" size="small">
          {{ item.unit }}
        </VChip>
      </template>

      <template #item.cost_per_unit="{ item }">
        <div class="text-end">
          <div class="font-weight-medium">{{ formatCurrency(item.cost_per_unit) }}</div>
        </div>
      </template>

      <template #item.current_stock="{ item }">
        <div class="text-center">
          <div class="d-flex align-center justify-center gap-1">
            <span class="font-weight-bold" :class="(item.inventory?.current_stock || 0) <= (item.inventory?.reorder_level || 0) ? 'text-error' : 'text-success'">
              {{ item.inventory?.current_stock || 0 }}
            </span>
            <small class="text-medium-emphasis">{{ item.unit }}</small>
          </div>
        </div>
      </template>

      <template #item.minimum_stock="{ item }">
        <div class="text-center">
          <span class="font-weight-medium">{{ item.inventory?.reorder_level || 0 }}</span>
          <small class="text-medium-emphasis"> {{ item.unit }}</small>
        </div>
      </template>

      <template #item.stock_status="{ item }">
        <VChip
          :color="getStockStatusColor(item.stock_status || 'in_stock')"
          variant="tonal"
          size="small"
        >
          {{ getStockStatusText(item.stock_status || 'in_stock') }}
        </VChip>
      </template>

      <template #item.storage_location="{ item }">
        <div class="d-flex align-center justify-center gap-1">
          <VIcon icon="tabler-map-pin" size="16" class="text-medium-emphasis" />
          <span class="text-sm">{{ item.storage_location || 'Gudang A-Rak 1' }}</span>
        </div>
      </template>

      <template #item.active="{ item }">
        <VChip
          :color="item.active ? 'success' : 'error'"
          variant="tonal"
          size="small"
        >
          {{ item.active ? 'Aktif' : 'Nonaktif' }}
        </VChip>
      </template>

      <template #item.station_availability="{ item }">
        <div class="d-flex flex-column gap-1">
          <VChip
            v-if="item.available_in_kitchen"
            color="warning"
            variant="tonal"
            size="x-small"
            prepend-icon="tabler-chef-hat"
          >
            Kitchen
          </VChip>
          <VChip
            v-if="item.available_in_bar"
            color="info"
            variant="tonal"
            size="x-small"
            prepend-icon="tabler-glass-cocktail"
          >
            Bar
          </VChip>
          <span v-if="!item.available_in_kitchen && !item.available_in_bar" class="text-caption text-medium-emphasis">
            Tidak tersedia
          </span>
        </div>
      </template>

      <template #item.service_type="{ item }">
        <div class="d-flex flex-column gap-1">
          <VChip
            v-if="item.is_delivery"
            color="primary"
            variant="tonal"
            size="x-small"
            prepend-icon="tabler-truck-delivery"
          >
            Delivery
          </VChip>
          <VChip
            v-if="item.is_takeaway"
            color="secondary"
            variant="tonal"
            size="x-small"
            prepend-icon="tabler-package"
          >
            Take Away
          </VChip>
          <span v-if="!item.is_delivery && !item.is_takeaway" class="text-caption text-medium-emphasis">
            Tidak tersedia
          </span>
        </div>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex gap-1 justify-center">
          <VBtn
            icon="tabler-edit"
            size="small"
            variant="text"
            color="primary"
            @click="$emit('edit-item', item)"
          />
          <VBtn
            icon="tabler-trash"
            size="small"
            variant="text"
            color="error"
            @click="$emit('delete-item', item)"
          />
        </div>
      </template>
    </VDataTableServer>
  </VCard>
</template>

<style scoped lang="scss">
.coffee-icon {
  color: rgb(var(--v-theme-primary));
}

.font-mono {
  font-family: Monaco, Menlo, "Ubuntu Mono", monospace;
}
</style>
