<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between">
      <div class="d-flex align-center gap-3">
        <VIcon icon="mdi-calculator-variant" size="24" />
        <span>HPP Management Dashboard</span>
      </div>
      
      <VBtn
        color="primary"
        variant="elevated"
        @click="refreshDashboard"
        :loading="loading"
        prepend-icon="mdi-refresh"
      >
        Refresh Data
      </VBtn>
    </VCardTitle>

    <VCardText>
      <!-- Dashboard Statistics -->
      <VRow v-if="hasDashboardData" class="mb-6">
        <VCol cols="12" md="3">
          <VCard color="primary" variant="tonal" class="h-100">
            <VCardText class="text-center">
              <VIcon icon="mdi-package-variant" size="48" class="mb-2" />
              <div class="text-h4 font-weight-bold">{{ hppDashboard.total_products }}</div>
              <div class="text-subtitle-1">Total Products</div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="3">
          <VCard color="success" variant="tonal" class="h-100">
            <VCardText class="text-center">
              <VIcon icon="mdi-check-circle" size="48" class="mb-2" />
              <div class="text-h4 font-weight-bold">{{ hppDashboard.products_with_items }}</div>
              <div class="text-subtitle-1">With HPP Data</div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="3">
          <VCard color="warning" variant="tonal" class="h-100">
            <VCardText class="text-center">
              <VIcon icon="mdi-alert-circle" size="48" class="mb-2" />
              <div class="text-h4 font-weight-bold">{{ hppDashboard.products_without_items }}</div>
              <div class="text-subtitle-1">Missing HPP Data</div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="3">
          <VCard color="info" variant="tonal" class="h-100">
            <VCardText class="text-center">
              <VIcon icon="mdi-trending-up" size="48" class="mb-2" />
              <div class="text-h4 font-weight-bold">
                {{ formatCurrency(hppDashboard.hpp_statistics?.average_margin || 0) }}
              </div>
              <div class="text-subtitle-1">Average Margin</div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- HPP Statistics -->
      <VRow v-if="hasDashboardData && hppDashboard.hpp_statistics" class="mb-6">
        <VCol cols="12">
          <VCard>
            <VCardTitle>
              <VIcon icon="mdi-chart-line" class="me-2" />
              HPP Statistics
            </VCardTitle>
            <VCardText>
              <VRow>
                <VCol cols="12" md="4">
                  <div class="text-subtitle-2 text-grey-darken-1">Average HPP</div>
                  <div class="text-h6 font-weight-bold text-primary">
                    {{ formatCurrency(hppDashboard.hpp_statistics.average_hpp) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-subtitle-2 text-grey-darken-1">Min HPP</div>
                  <div class="text-h6 font-weight-bold text-success">
                    {{ formatCurrency(hppDashboard.hpp_statistics.min_hpp) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-subtitle-2 text-grey-darken-1">Max HPP</div>
                  <div class="text-h6 font-weight-bold text-error">
                    {{ formatCurrency(hppDashboard.hpp_statistics.max_hpp) }}
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Bulk Operations -->
      <VRow class="mb-6">
        <VCol cols="12">
          <VCard>
            <VCardTitle>
              <VIcon icon="mdi-update" class="me-2" />
              Bulk HPP Operations
            </VCardTitle>
            <VCardText>
              <VRow align="center">
                <VCol cols="12" md="4">
                  <VSelect
                    v-model="selectedBulkMethod"
                    :items="hppMethods"
                    label="HPP Calculation Method"
                    variant="outlined"
                    hide-details
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VBtn
                    color="primary"
                    variant="elevated"
                    :loading="loading"
                    @click="performBulkUpdate"
                    prepend-icon="mdi-update"
                    block
                  >
                    Bulk Update All Products
                  </VBtn>
                </VCol>
                <VCol cols="12" md="4">
                  <VChip
                    v-if="bulkUpdateResults.length > 0"
                    color="success"
                    variant="elevated"
                    prepend-icon="mdi-check"
                  >
                    {{ bulkUpdateResults.length }} products updated
                  </VChip>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Bulk Update Results -->
      <VRow v-if="bulkUpdateResults.length > 0">
        <VCol cols="12">
          <VCard>
            <VCardTitle>
              <VIcon icon="mdi-file-document-multiple" class="me-2" />
              Bulk Update Results ({{ bulkUpdateResults.length }} products)
            </VCardTitle>
            <VCardText>
              <VDataTable
                :headers="bulkUpdateHeaders"
                :items="bulkUpdateResults"
                :items-per-page="10"
                class="elevation-1"
              >
                <template #item.old_cost="{ item }">
                  <span class="text-grey-darken-1">{{ formatCurrency(item.old_cost) }}</span>
                </template>
                
                <template #item.new_cost="{ item }">
                  <span class="font-weight-bold text-primary">{{ formatCurrency(item.new_cost) }}</span>
                </template>
                
                <template #item.difference="{ item }">
                  <VChip 
                    :color="item.difference > 0 ? 'error' : item.difference < 0 ? 'success' : 'grey'"
                    variant="tonal"
                    size="small"
                  >
                    {{ item.difference > 0 ? '+' : '' }}{{ formatCurrency(item.difference) }}
                  </VChip>
                </template>
                
                <template #item.updated_at="{ item }">
                  <span class="text-caption">
                    {{ new Date(item.updated_at).toLocaleString() }}
                  </span>
                </template>
              </VDataTable>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Loading State -->
      <VRow v-if="loading && !hasDashboardData">
        <VCol cols="12" class="text-center py-12">
          <VProgressCircular indeterminate size="64" />
          <div class="mt-4 text-h6">Loading HPP Dashboard...</div>
        </VCol>
      </VRow>

      <!-- Empty State -->
      <VRow v-if="!loading && !hasDashboardData">
        <VCol cols="12" class="text-center py-12">
          <VIcon icon="mdi-chart-line" size="64" class="text-grey-lighten-1 mb-4" />
          <div class="text-h6 text-grey-darken-1 mb-2">No HPP Data Available</div>
          <div class="text-body-2 text-grey-darken-1 mb-4">
            Click "Refresh Data" to load HPP dashboard information
          </div>
          <VBtn
            color="primary"
            variant="elevated"
            @click="refreshDashboard"
            prepend-icon="mdi-refresh"
          >
            Load Dashboard
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
import { useHPP } from '@/composables/useHPP'
import { onMounted, ref } from 'vue'

// Composables
const { 
  loading, 
  hppDashboard, 
  bulkUpdateResults,
  hasDashboardData,
  getHPPDashboard,
  bulkUpdateHPP,
  formatCurrency,
} = useHPP()

// State
const selectedBulkMethod = ref<'current' | 'latest' | 'average'>('latest')

// Data
const hppMethods = [
  { title: 'Current Cost', value: 'current' },
  { title: 'Latest Purchase', value: 'latest' },
  { title: 'Average Purchase', value: 'average' },
]

const bulkUpdateHeaders = [
  { title: 'Product', value: 'product_name', sortable: true },
  { title: 'Old Cost', value: 'old_cost', align: 'end' },
  { title: 'New Cost', value: 'new_cost', align: 'end' },
  { title: 'Difference', value: 'difference', align: 'center' },
  { title: 'Updated', value: 'updated_at', sortable: true },
]

// Methods
const refreshDashboard = async () => {
  await getHPPDashboard()
}

const performBulkUpdate = async () => {
  await bulkUpdateHPP(selectedBulkMethod.value)
  // Refresh dashboard after bulk update
  await refreshDashboard()
}

// Lifecycle
onMounted(() => {
  refreshDashboard()
})
</script>

<style scoped>
.v-card--tonal {
  transition: transform 0.2s ease-in-out;
}

.v-card--tonal:hover {
  transform: translateY(-2px);
}
</style>
