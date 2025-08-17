<template>
  <VRow class="mb-6">
    <!-- Total Customers -->
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
            <VIcon icon="tabler-users" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Total Customer</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.total_customers || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Active Customers -->
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
            <VIcon icon="tabler-user-check" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Customer Aktif</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.active_customers || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Total Spent -->
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
            <span class="text-sm text-medium-emphasis">Total Pembelian</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ formatCurrency(stats?.total_spent_all || 0) }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Recent Customers -->
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
            <VIcon icon="tabler-user-plus" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Customer Baru (30 hari)</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.recent_customers || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Average Spent -->
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
            <VIcon icon="tabler-chart-line" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Rata-rata Pembelian</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ formatCurrency(stats?.average_spent_per_customer || 0) }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Frequent Customers -->
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
            <VIcon icon="tabler-crown" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Customer Setia</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.frequent_customers || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Total Visits -->
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
            <VIcon icon="tabler-calendar-stats" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Total Kunjungan</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.total_visits_all || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Loyalty Distribution -->
    <VCol cols="12" sm="6" lg="3">
      <VCard class="stats-card">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="purple"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="tabler-medal" />
          </VAvatar>
          <div>
            <span class="text-sm text-medium-emphasis">Platinum Members</span>
            <div class="d-flex align-center gap-2">
              <h6 class="text-h6">
                {{ stats?.customers_by_loyalty?.platinum || 0 }}
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<script setup lang="ts">
import type { CustomerStats } from '@/composables/useCustomers';

// Props
defineProps<{
  stats?: CustomerStats
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
