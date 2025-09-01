<template>
  <VRow class="mb-6">
    <VCol cols="12" md="3">
      <VCard>
        <VCardText class="text-center pa-6">
          <VAvatar size="56" color="primary" class="mb-4">
            <VIcon icon="tabler-users" size="28" />
          </VAvatar>
          <div class="text-h4 font-weight-bold">{{ totalUsers }}</div>
          <div class="text-subtitle-2 text-medium-emphasis">Total Pengguna</div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" md="3">
      <VCard>
        <VCardText class="text-center pa-6">
          <VAvatar size="56" color="success" class="mb-4">
            <VIcon icon="tabler-user-check" size="28" />
          </VAvatar>
          <div class="text-h4 font-weight-bold">{{ activeUsers }}</div>
          <div class="text-subtitle-2 text-medium-emphasis">Pengguna Aktif</div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" md="3">
      <VCard>
        <VCardText class="text-center pa-6">
          <VAvatar size="56" color="warning" class="mb-4">
            <VIcon icon="tabler-user-x" size="28" />
          </VAvatar>
          <div class="text-h4 font-weight-bold">{{ inactiveUsers }}</div>
          <div class="text-subtitle-2 text-medium-emphasis">Pengguna Nonaktif</div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" md="3">
      <VCard>
        <VCardText class="text-center pa-6">
          <VAvatar size="56" color="info" class="mb-4">
            <VIcon icon="tabler-shield-lock" size="28" />
          </VAvatar>
          <div class="text-h4 font-weight-bold">{{ totalRoles }}</div>
          <div class="text-subtitle-2 text-medium-emphasis">Total Role</div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<script setup lang="ts">
interface Props {
  totalUsers: number
  activeUsers: number
  inactiveUsers: number
  totalRoles: number
}

defineProps<Props>()
</script>
