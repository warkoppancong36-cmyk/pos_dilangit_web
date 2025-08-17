<script setup lang="ts">
import type { ProductItem, ProductItemFilters } from '@/composables/useProductItems'

defineProps<{
  productItems: ProductItem[]
  products: any[]
  items: any[]
  loading: boolean
  totalItems: number
  itemsPerPage: number
  currentPage: number
  filters: ProductItemFilters
}>()

defineEmits<{
  add: []
  edit: [item: ProductItem]
  delete: [item: ProductItem]
  'check-capacity': [productId: number]
  'update:filters': [filters: ProductItemFilters]
  'update:options': [options: any]
  'update:items-per-page': [value: number]
  'update:page': [value: number]
}>()

// Table headers
const headers = [
  { title: 'Produk', value: 'product', sortable: false },
  { title: 'Item', value: 'item', sortable: false },
  { title: 'Jumlah', value: 'quantity_needed', align: 'end' as const },
  { title: 'Harga per Unit', value: 'cost_per_unit', align: 'end' as const },
  { title: 'Kritis', value: 'is_critical', align: 'center' as const },
  { title: 'Aksi', value: 'actions', sortable: false, align: 'center' as const },
]

// Helper functions
const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount)
}

const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(num)
}
</script>

<template>
  <VDataTableServer
    :items-per-page="itemsPerPage"
    :page="currentPage"
    :headers="headers"
    :items="productItems"
    :items-length="totalItems"
    :loading="loading"
    item-value="id_product_item"
    @update:items-per-page="$emit('update:items-per-page', $event)"
    @update:page="$emit('update:page', $event)"
    @update:options="$emit('update:options', $event)"
  >
    <!-- Product column -->
    <template #item.product="{ item }">
      <div>
        <div class="font-weight-medium">
          {{ item.product?.name || 'N/A' }}
        </div>
        <div class="text-caption text-medium-emphasis">
          {{ item.product?.code || '' }}
        </div>
      </div>
    </template>

    <!-- Item column -->
    <template #item.item="{ item }">
      <div>
        <div class="font-weight-medium">
          {{ item.item?.name || 'N/A' }}
        </div>
        <div class="text-caption text-medium-emphasis">
          {{ item.item?.code || '' }}
        </div>
      </div>
    </template>

    <!-- Quantity column -->
    <template #item.quantity_needed="{ item }">
      <div class="text-end">
        <div class="font-weight-medium">
          {{ formatNumber(item.quantity_needed) }}
        </div>
        <div class="text-caption text-medium-emphasis">
          {{ item.unit }}
        </div>
      </div>
    </template>

    <!-- Cost column -->
    <template #item.cost_per_unit="{ item }">
      <div class="text-end">
        <div class="font-weight-medium">
          {{ formatCurrency(item.cost_per_unit || 0) }}
        </div>
        <div class="text-caption text-success">
          Total: {{ formatCurrency((item.cost_per_unit || 0) * item.quantity_needed) }}
        </div>
      </div>
    </template>

    <!-- Critical column -->
    <template #item.is_critical="{ item }">
      <VChip
        :color="item.is_critical ? 'error' : 'default'"
        size="small"
        variant="tonal"
      >
        {{ item.is_critical ? 'Critical' : 'Standard' }}
      </VChip>
    </template>

    <!-- Actions column -->
    <template #item.actions="{ item }">
      <div class="d-flex gap-1">
        <VBtn
          icon="tabler-edit"
          size="small"
          variant="text"
          color="primary"
          @click="$emit('edit', item)"
        >
          <VIcon icon="tabler-edit" />
          <VTooltip
            activator="parent"
            location="top"
          >
            Edit Product Item
          </VTooltip>
        </VBtn>

        <VBtn
          icon="tabler-calculator"
          size="small"
          variant="text"
          color="info"
          @click="$emit('check-capacity', item.product_id)"
        >
          <VIcon icon="tabler-calculator" />
          <VTooltip
            activator="parent"
            location="top"
          >
            Check Production Capacity
          </VTooltip>
        </VBtn>

        <VBtn
          icon="tabler-trash"
          size="small"
          variant="text"
          color="error"
          @click="$emit('delete', item)"
        >
          <VIcon icon="tabler-trash" />
          <VTooltip
            activator="parent"
            location="top"
          >
            Delete Product Item
          </VTooltip>
        </VBtn>
      </div>
    </template>
  </VDataTableServer>
</template>
