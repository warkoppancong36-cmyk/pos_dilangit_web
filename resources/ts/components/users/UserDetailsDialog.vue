<template>
  <VDialog v-model="dialog" max-width="800px" scrollable>
    <VCard v-if="user">
      <VCardTitle class="text-h5 pa-6 pb-4">
        <VIcon icon="tabler-user" class="me-3 text-primary" />
        Detail Pengguna
        <VSpacer />
        <VBtn icon variant="text" @click="closeDialog">
          <VIcon>tabler-x</VIcon>
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-0">
        <VContainer class="pa-6">
          <!-- User Header -->
          <VRow class="mb-6">
            <VCol cols="12">
              <div class="d-flex align-center">
                <VAvatar
                  size="80"
                  :color="user.is_active ? 'primary' : 'secondary'"
                  class="me-4"
                >
                  <VIcon
                    :icon="user.is_active ? 'tabler-user' : 'tabler-user-off'"
                    size="40"
                  />
                </VAvatar>
                <div class="flex-grow-1">
                  <h2 class="text-h4 font-weight-bold">{{ user.name }}</h2>
                  <p class="text-body-1 text-medium-emphasis mb-2">@{{ user.username }}</p>
                  <VChip
                    :color="user.is_active ? 'success' : 'error'"
                    :prepend-icon="user.is_active ? 'tabler-check' : 'tabler-x'"
                    variant="tonal"
                    size="small"
                  >
                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                  </VChip>
                  <VChip
                    :color="getRoleColor(user.role?.name)"
                    variant="tonal"
                    size="small"
                    class="ms-2"
                  >
                    {{ user.role?.name || 'No Role' }}
                  </VChip>
                </div>
              </div>
            </VCol>
          </VRow>

          <!-- User Information -->
          <VRow>
            <VCol cols="12" md="6">
              <VCard variant="outlined" class="h-100">
                <VCardTitle class="text-h6 pa-4 pb-2">
                  <VIcon icon="tabler-info-circle" class="me-2" />
                  Informasi Kontak
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  <VList density="compact" class="pa-0">
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-mail" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Email</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ user.email }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem v-if="user.phone">
                      <template #prepend>
                        <VIcon icon="tabler-phone" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Telepon</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ user.phone }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-shield" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Role</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ user.role?.name || 'No Role' }}</VListItemSubtitle>
                    </VListItem>
                  </VList>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="6">
              <VCard variant="outlined" class="h-100">
                <VCardTitle class="text-h6 pa-4 pb-2">
                  <VIcon icon="tabler-clock" class="me-2" />
                  Aktivitas
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  <VList density="compact" class="pa-0">
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-calendar-plus" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Tanggal Bergabung</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ formatDate(user.created_at) }}</VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-login" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Terakhir Login</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">
                        {{ user.last_login_at ? formatDateTime(user.last_login_at) : 'Belum pernah login' }}
                      </VListItemSubtitle>
                    </VListItem>
                    
                    <VListItem>
                      <template #prepend>
                        <VIcon icon="tabler-edit" class="me-3" />
                      </template>
                      <VListItemTitle class="text-body-2 text-medium-emphasis">Terakhir Diperbarui</VListItemTitle>
                      <VListItemSubtitle class="font-weight-bold">{{ formatDateTime(user.updated_at) }}</VListItemSubtitle>
                    </VListItem>
                  </VList>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VContainer>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-6">
        <VSpacer />
        <VBtn
          variant="outlined"
          prepend-icon="tabler-edit"
          color="primary"
          @click="editUser"
        >
          Edit Pengguna
        </VBtn>
        <VBtn
          variant="outlined"
          prepend-icon="tabler-trash"
          color="error"
          @click="deleteUser"
        >
          Hapus
        </VBtn>
        <VBtn
          variant="text"
          @click="closeDialog"
        >
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { User } from '@/composables/useUserManagement'

interface Props {
  show: boolean
  user?: User
}

interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'edit', user: User): void
  (e: 'delete', user: User): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const dialog = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

const closeDialog = () => {
  emit('update:show', false)
}

const editUser = () => {
  if (props.user) {
    emit('edit', props.user)
    closeDialog()
  }
}

const deleteUser = () => {
  if (props.user) {
    emit('delete', props.user)
    closeDialog()
  }
}

const getRoleColor = (roleName?: string) => {
  switch (roleName?.toLowerCase()) {
    case 'admin': return 'error'
    case 'manager': return 'warning'
    case 'cashier': return 'primary'
    case 'staff': return 'info'
    default: return 'secondary'
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
