<template>
  <VCard>
    <VCardTitle class="d-flex align-center gap-2 coffee-header">
      <VIcon icon="tabler-components" class="text-white" />
      <span class="text-white">Inventory Item</span>
      <VSpacer />
      <VChip color="white" size="small" variant="tonal">
        {{ totalItems }} Item
      </VChip>
    </VCardTitle>

    <VDivider />

    <VDataTableServer
      :headers="headers"
      :items="itemsList"
      :items-length="totalItems"
      :loading="loading"
      :items-per-page="itemsPerPage"
      :page="currentPage"
      class="inventory-items-table"
      @update:page="onPageChange"
    >
      <!-- Loading -->
      <template #loading>
        <VSkeletonLoader type="table-row@10" />
      </template>

      <!-- No Data -->
      <template #no-data>
        <div class="text-center py-8">
          <VIcon
            icon="tabler-components"
            size="64"
            class="mb-4 text-disabled"
          />
          <h6 class="text-h6 mb-2">Belum Ada Data Item</h6>
          <p class="text-body-2 text-medium-emphasis">
            Buat item terlebih dahulu di menu Items Management untuk mulai tracking inventory.
          </p>
        </div>
      </template>

      <!-- Item Name -->
      <template #item.name="{ item }">
        <div class="d-flex align-center gap-3 py-2">
          <VAvatar
            color="primary"
            size="40"
            variant="tonal"
          >
            <VIcon icon="tabler-components" />
          </VAvatar>
          <div>
            <div class="font-weight-medium">{{ item.name }}</div>
            <div class="text-caption text-medium-emphasis">
              {{ item.item_code }} • {{ item.unit }}
            </div>
          </div>
        </div>
      </template>

      <!-- Category -->
      <template #item.category="{ item }">
        <VChip
          v-if="item.category"
          size="small"
          variant="tonal"
          color="info"
        >
          {{ item.category }}
        </VChip>
        <span v-else class="text-medium-emphasis">-</span>
      </template>

      <!-- Stock Status -->
      <template #item.stock_status="{ item }">
        <div class="text-center">
          <VChip
            :color="getStockStatus(item).color"
            size="small"
            variant="tonal"
            class="mb-2"
          >
            <VIcon
              :icon="getStockStatus(item).icon"
              size="16"
              class="me-1"
            />
            {{ getStockStatus(item).text }}
          </VChip>
          <div class="text-body-2 font-weight-bold">
            {{ item.current_stock }} {{ item.unit }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Min: {{ item.minimum_stock }}
            <span v-if="item.maximum_stock"> • Max: {{ item.maximum_stock }}</span>
          </div>
        </div>
      </template>

      <!-- Stock Progress -->
      <template #item.stock_progress="{ item }">
        <div class="stock-progress-container">
          <VProgressLinear
            :model-value="getStockPercentage(item)"
            :color="getStockStatus(item).color"
            height="8"
            rounded
          />
          <div class="text-caption text-center mt-1">
            {{ getStockPercentage(item) }}%
          </div>
        </div>
      </template>

      <!-- Cost per Unit -->
      <template #item.cost_per_unit="{ item }">
        <div class="text-end">
          <div class="font-weight-medium">
            {{ formatCurrency(item.cost_per_unit) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            per {{ item.unit }}
          </div>
        </div>
      </template>

      <!-- Total Value -->
      <template #item.total_value="{ item }">
        <div class="text-end font-weight-medium">
          {{ formatCurrency(item.current_stock * item.cost_per_unit) }}
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="d-flex align-center gap-1">
          <!-- Stock In -->
          <VTooltip text="Stok Masuk">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-plus"
                size="small"
                variant="text"
                color="success"
                @click="$emit('stock-in', item)"
              />
            </template>
          </VTooltip>

          <!-- Stock Out -->
          <VTooltip text="Stok Keluar">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-minus"
                size="small"
                variant="text"
                color="error"
                :disabled="item.current_stock <= 0"
                @click="$emit('stock-out', item)"
              />
            </template>
          </VTooltip>

          <!-- Adjustment -->
          <VTooltip text="Penyesuaian Stok">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-adjustments"
                size="small"
                variant="text"
                color="warning"
                @click="$emit('stock-adjustment', item)"
              />
            </template>
          </VTooltip>

          <!-- History -->
          <VTooltip text="Riwayat Stok">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-history"
                size="small"
                variant="text"
                color="info"
                @click="$emit('stock-history', item)"
              />
            </template>
          </VTooltip>

          <!-- Edit Item -->
          <VTooltip text="Edit Item">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon="tabler-edit"
                size="small"
                variant="text"
                color="primary"
                @click="$emit('edit-item', item)"
              />
            </template>
          </VTooltip>
        </div>
      </template>
    </VDataTableServer>
  </VCard>
</template>

<script setup lang="ts">
import type { Item } from '@/composables/useItems'
import { useStockMovements } from '@/composables/useStockMovements'
import { formatCurrency } from '@/utils/helpers'

interface Props {
  itemsList: Item[]
  totalItems: number
  loading?: boolean
  itemsPerPage?: number
  currentPage?: number
}

interface Emits {
  (e: 'update:page', page: number): void
  (e: 'stock-in', item: Item): void
  (e: 'stock-out', item: Item): void
  (e: 'stock-adjustment', item: Item): void
  (e: 'stock-history', item: Item): void
  (e: 'edit-item', item: Item): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  itemsPerPage: 10,
  currentPage: 1
})

const emit = defineEmits<Emits>()

// Use stock movements composable for stock status logic
const {
  getStockStatus,
  getStockPercentage
} = useStockMovements()

// Handle page change
const onPageChange = (page: number) => {
  emit('update:page', page)
}

// Table headers
const headers = [
  {
    title: 'Item',
    key: 'name',
    align: 'start',
    sortable: true,
    width: '300px'
  },
  {
    title: 'Kategori',
    key: 'category',
    align: 'center',
    sortable: false,
    width: '120px'
  },
  {
    title: 'Status Stok',
    key: 'stock_status',
    align: 'center',
    sortable: false,
    width: '180px'
  },
  {
    title: 'Progress',
    key: 'stock_progress',
    align: 'center',
    sortable: false,
    width: '120px'
  },
  {
    title: 'Harga/Unit',
    key: 'cost_per_unit',
    align: 'end',
    sortable: false,
    width: '130px'
  },
  {
    title: 'Total Nilai',
    key: 'total_value',
    align: 'end',
    sortable: false,
    width: '150px'
  },
  {
    title: 'Aksi',
    key: 'actions',
    align: 'center',
    sortable: false,
    width: '200px'
  }
] as const
</script>

<style scoped lang="scss">
.inventory-items-table {
  .stock-progress-container {
    min-width: 80px;
  }
}

.coffee-header {
  background: linear-gradient(135deg, #B07124 0%, #8D7053 100%);
}
</style>
