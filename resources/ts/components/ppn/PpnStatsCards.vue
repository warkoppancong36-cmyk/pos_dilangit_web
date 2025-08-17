<template>
  <VRow class="mb-6">
    <VCol cols="12" sm="6" md="3">
      <VCard class="text-center pa-4 stats-card" color="primary" variant="tonal">
        <VCardText>
          <VIcon icon="mdi-percent" size="48" class="mb-2 coffee-icon" />
          <div class="text-h4 font-weight-bold coffee-number">{{ totalPpn }}</div>
          <div class="text-body-1 coffee-label">Total PPN</div>
        </VCardText>
      </VCard>
    </VCol>
    <VCol cols="12" sm="6" md="3">
      <VCard class="text-center pa-4 stats-card" color="success" variant="tonal">
        <VCardText>
          <VIcon icon="mdi-check-circle" size="48" class="mb-2 coffee-icon" />
          <div class="text-h4 font-weight-bold coffee-number">{{ activePpn }}</div>
          <div class="text-body-1 coffee-label">PPN Aktif</div>
        </VCardText>
      </VCard>
    </VCol>
    <VCol cols="12" sm="6" md="3">
      <VCard class="text-center pa-4 stats-card" color="warning" variant="tonal">
        <VCardText>
          <VIcon icon="mdi-pause-circle" size="48" class="mb-2 coffee-icon" />
          <div class="text-h4 font-weight-bold coffee-number">{{ inactivePpn }}</div>
          <div class="text-body-1 coffee-label">Tidak Aktif</div>
        </VCardText>
      </VCard>
    </VCol>
    <VCol cols="12" sm="6" md="3">
      <VCard class="text-center pa-4 stats-card" color="info" variant="tonal">
        <VCardText>
          <VIcon icon="mdi-calculator" size="48" class="mb-2 coffee-icon" />
          <div class="text-h4 font-weight-bold coffee-number">{{ averageRate }}%</div>
          <div class="text-body-1 coffee-label">Rata-rata Tarif</div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<script setup lang="ts">
interface Props {
  totalPpn: number
  activePpn: number
  inactivePpn: number
  averageRate: number
}

defineProps<Props>()
</script>

<style scoped>
/* Coffee Shop Stats Cards */
.stats-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(176, 113, 36, 0.08);
}

.stats-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(176, 113, 36, 0.15);
}

.coffee-icon {
  color: #B07124;
  filter: drop-shadow(0 2px 4px rgba(176, 113, 36, 0.2));
}

.coffee-number {
  color: #8D4B00;
  text-shadow: 0 1px 2px rgba(176, 113, 36, 0.1);
}

.coffee-label {
  color: #A0672D;
  font-weight: 500;
}

/* Dark theme adjustments */
.v-theme--dark .coffee-icon {
  color: #D4AC71;
}

.v-theme--dark .coffee-number {
  color: #D4AC71;
}

.v-theme--dark .coffee-label {
  color: #B8946A;
}

.v-theme--dark .stats-card {
  border: 1px solid rgba(212, 172, 113, 0.08);
}

.v-theme--dark .stats-card:hover {
  box-shadow: 0 8px 25px rgba(212, 172, 113, 0.2);
}
</style>
