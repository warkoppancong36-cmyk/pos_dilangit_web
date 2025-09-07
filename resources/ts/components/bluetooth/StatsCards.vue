<template>
  <VRow class="mb-6">
    <VCol cols="12" sm="6" md="3">
      <VCard class="h-100">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="primary"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="mdi-bluetooth" />
          </VAvatar>
          <div>
            <h5 class="text-h5 font-weight-semibold">
              {{ totalDevices }}
            </h5>
            <span class="text-sm text-medium-emphasis">Total Perangkat</span>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" sm="6" md="3">
      <VCard class="h-100">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="success"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="mdi-check-circle" />
          </VAvatar>
          <div>
            <h5 class="text-h5 font-weight-semibold">
              {{ activeDevices }}
            </h5>
            <span class="text-sm text-medium-emphasis">Aktif</span>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" sm="6" md="3">
      <VCard class="h-100">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="error"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="mdi-cancel" />
          </VAvatar>
          <div>
            <h5 class="text-h5 font-weight-semibold">
              {{ inactiveDevices }}
            </h5>
            <span class="text-sm text-medium-emphasis">Tidak Aktif</span>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" sm="6" md="3">
      <VCard class="h-100">
        <VCardText class="d-flex align-center">
          <VAvatar
            size="44"
            rounded
            color="info"
            variant="tonal"
            class="me-3"
          >
            <VIcon icon="mdi-printer" />
          </VAvatar>
          <div>
            <h5 class="text-h5 font-weight-semibold">
              {{ printerDevices }}
            </h5>
            <span class="text-sm text-medium-emphasis">Printer</span>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<script setup lang="ts">
interface Props {
  totalDevices: number
  activeDevices: number
  inactiveDevices: number
  printerDevices: number
}

defineProps<Props>()
</script>
