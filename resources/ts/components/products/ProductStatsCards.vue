<template>
  <VRow class="mb-6">
    <!-- Total Products -->
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
            <span class="text-sm text-medium-emphasis">Total Produk</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.total_products || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Active Products -->
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
            <span class="text-sm text-medium-emphasis">Produk Aktif</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.active_products || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Featured Products -->
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
            <VIcon icon="tabler-star" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Produk Unggulan</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.featured_products || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Low Stock Products -->
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
            <VIcon icon="tabler-alert-triangle" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Stok Rendah</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.low_stock_products || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Total Stock Value -->
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
                {{ formatCurrency(stats?.total_stock_value || 0) }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Average Price -->
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
            <VIcon icon="tabler-chart-line" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Harga Rata-rata</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ formatCurrency(stats?.average_price || 0) }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Average Margin -->
    <VCol cols="12" sm="6" lg="3">
      <VCard class="stats-card">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="coffee"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="tabler-trending-up" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Margin Rata-rata</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ formatPercentage(stats?.average_margin || 0) }}
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
            color="surface"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="tabler-x-circle" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Stok Habis</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.out_of_stock_products || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<script setup lang="ts">
import type { ProductStats } from '@/composables/useProducts';

// Props
defineProps<{
  stats?: ProductStats
}>()

// Utils
const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const formatPercentage = (value: number): string => {
  return `${value.toFixed(1)}%`
}
</script>

<style scoped>
.stats-card {
  transition: all 0.3s ease;
  border: 1px solid rgba(var(--v-theme-coffee), 0.1);
}

.stats-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(var(--v-theme-coffee), 0.1);
}
</style>
