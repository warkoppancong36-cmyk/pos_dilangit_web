<script setup lang="ts">
import type { SupplierStats } from '@/utils/api/SuppliersApi';

interface Props {
  stats: SupplierStats
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  loading: false
})
</script>

<template>
  <VRow>
    <!-- Total Suppliers -->
    <VCol cols="12" sm="6" md="3">
      <VCard>
        <VCardText>
          <div class="d-flex align-center">
            <VAvatar
              color="primary"
              variant="tonal"
              size="42"
              class="me-3"
            >
              <VIcon icon="tabler-building-store" />
            </VAvatar>
            <div>
              <p class="text-caption text-medium-emphasis mb-1">Total Supplier</p>
              <h6 class="text-h6 font-weight-semibold">
                <VSkeletonLoader
                  v-if="loading"
                  type="text"
                  width="60"
                />
                <span v-else>{{ stats.total_suppliers.toLocaleString() }}</span>
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Active Suppliers -->
    <VCol cols="12" sm="6" md="3">
      <VCard>
        <VCardText>
          <div class="d-flex align-center">
            <VAvatar
              color="success"
              variant="tonal"
              size="42"
              class="me-3"
            >
              <VIcon icon="tabler-check" />
            </VAvatar>
            <div>
              <p class="text-caption text-medium-emphasis mb-1">Supplier Aktif</p>
              <h6 class="text-h6 font-weight-semibold">
                <VSkeletonLoader
                  v-if="loading"
                  type="text"
                  width="60"
                />
                <span v-else>{{ stats.active_suppliers.toLocaleString() }}</span>
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Suppliers with Purchases -->
    <VCol cols="12" sm="6" md="3">
      <VCard>
        <VCardText>
          <div class="d-flex align-center">
            <VAvatar
              color="info"
              variant="tonal"
              size="42"
              class="me-3"
            >
              <VIcon icon="tabler-shopping-cart" />
            </VAvatar>
            <div>
              <p class="text-caption text-medium-emphasis mb-1">Dengan Pembelian</p>
              <h6 class="text-h6 font-weight-semibold">
                <VSkeletonLoader
                  v-if="loading"
                  type="text"
                  width="60"
                />
                <span v-else>{{ stats.suppliers_with_purchases.toLocaleString() }}</span>
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Total Cities -->
    <VCol cols="12" sm="6" md="3">
      <VCard>
        <VCardText>
          <div class="d-flex align-center">
            <VAvatar
              color="warning"
              variant="tonal"
              size="42"
              class="me-3"
            >
              <VIcon icon="tabler-map-pin" />
            </VAvatar>
            <div>
              <p class="text-caption text-medium-emphasis mb-1">Total Kota</p>
              <h6 class="text-h6 font-weight-semibold">
                <VSkeletonLoader
                  v-if="loading"
                  type="text"
                  width="60"
                />
                <span v-else>{{ stats.total_cities.toLocaleString() }}</span>
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Inactive Suppliers -->
    <VCol cols="12" sm="6" md="3">
      <VCard>
        <VCardText>
          <div class="d-flex align-center">
            <VAvatar
              color="error"
              variant="tonal"
              size="42"
              class="me-3"
            >
              <VIcon icon="tabler-x" />
            </VAvatar>
            <div>
              <p class="text-caption text-medium-emphasis mb-1">Supplier Nonaktif</p>
              <h6 class="text-h6 font-weight-semibold">
                <VSkeletonLoader
                  v-if="loading"
                  type="text"
                  width="60"
                />
                <span v-else>{{ stats.inactive_suppliers.toLocaleString() }}</span>
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Total Provinces -->
    <VCol cols="12" sm="6" md="3">
      <VCard>
        <VCardText>
          <div class="d-flex align-center">
            <VAvatar
              color="secondary"
              variant="tonal"
              size="42"
              class="me-3"
            >
              <VIcon icon="tabler-map" />
            </VAvatar>
            <div>
              <p class="text-caption text-medium-emphasis mb-1">Total Provinsi</p>
              <h6 class="text-h6 font-weight-semibold">
                <VSkeletonLoader
                  v-if="loading"
                  type="text"
                  width="60"
                />
                <span v-else>{{ stats.total_provinces.toLocaleString() }}</span>
              </h6>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
