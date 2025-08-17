<script setup lang="ts">
import type { ItemStats } from '@/composables/useItems';
import { formatCurrency } from '@/utils/helpers';

interface Props {
  stats: ItemStats
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})
</script>

<template>
  <VRow class="mb-6">
    <!-- Total Items -->
    <VCol cols="12" sm="6" lg="3">
      <VCard class="stats-card">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="primary"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="tabler-package" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Total Item</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.total_items || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Active Items -->
    <VCol cols="12" sm="6" lg="3">
      <VCard class="stats-card">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="success"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="tabler-check" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Item Aktif</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.active_items || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Low Stock -->
    <VCol cols="12" sm="6" lg="3">
      <VCard class="stats-card">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="warning"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="tabler-alert-triangle" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Stok Rendah</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.low_stock_items || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Out of Stock -->
    <VCol cols="12" sm="6" lg="3">
      <VCard class="stats-card">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="error"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="tabler-x" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Stok Habis</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.out_of_stock_items || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Total Value -->
    <VCol cols="12" sm="6" lg="3">
      <VCard class="stats-card">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="info"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="tabler-currency-dollar" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Nilai Stok</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ formatCurrency(stats?.total_value || 0) }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Inactive Items -->
    <VCol cols="12" sm="6" lg="3">
      <VCard class="stats-card">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="secondary"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="tabler-pause" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Item Nonaktif</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.inactive_items || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">
.stats-card {
  border: 0;
  box-shadow: rgba(var(--v-theme-on-surface), 0.05) 0px 2px 4px !important;
  
  .v-card-text {
    padding: 1.5rem !important;
  }
}
</style>
