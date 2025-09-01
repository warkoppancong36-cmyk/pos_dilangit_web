<template>
  <div class="global-notifications">
    <VAlert
      v-for="notification in notificationsWithShow"
      :key="notification.id"
      v-model="notification.show"
      :type="getNotificationType(notification.type)"
      :variant="notification.type === 'error' ? 'tonal' : 'outlined'"
      class="mb-4"
      closable
      @click:close="removeNotification(notification.id)"
    >
      <template #prepend>
        <VIcon :icon="getNotificationIcon(notification.type)" />
      </template>
      
      <div>
        <div class="text-subtitle-2 font-weight-medium">
          {{ notification.title }}
        </div>
        <div
          v-if="notification.message"
          class="text-body-2 mt-1"
        >
          {{ notification.message }}
        </div>
      </div>
    </VAlert>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useNotification } from '@/composables/useNotification'
import type { Notification } from '@/composables/useNotification'

const { notifications, removeNotification } = useNotification()

// Add show property to notifications for v-model
const notificationsWithShow = ref<(Notification & { show: boolean })[]>([])

// Watch for new notifications and add show property
watch(notifications, (newNotifications) => {
  // Add new notifications with show: true
  newNotifications.forEach(notification => {
    const exists = notificationsWithShow.value.find(n => n.id === notification.id)
    if (!exists) {
      const newNotificationWithShow = {
        ...notification,
        show: true
      }
      notificationsWithShow.value.push(newNotificationWithShow)
      
      // Auto remove after timeout
      if (notification.timeout) {
        setTimeout(() => {
          removeNotification(notification.id)
        }, notification.timeout)
      }
    }
  })

  // Remove notifications that are no longer in the store
  notificationsWithShow.value = notificationsWithShow.value.filter(notification =>
    newNotifications.find(n => n.id === notification.id)
  )
}, { deep: true, immediate: true })

const handleSnackbarUpdate = (id: string, show: boolean) => {
  const notification = notificationsWithShow.value.find(n => n.id === id)
  if (notification) {
    notification.show = show
    if (!show) {
      // Remove notification when snackbar is closed
      setTimeout(() => {
        removeNotification(id)
      }, 100)
    }
  }
}

const getNotificationType = (type: Notification['type']) => {
  switch (type) {
    case 'success':
      return 'success'
    case 'error':
      return 'error'
    case 'warning':
      return 'warning'
    case 'info':
      return 'info'
    default:
      return 'info'
  }
}

const getNotificationIcon = (type: Notification['type']) => {
  switch (type) {
    case 'success':
      return 'mdi-check-circle'
    case 'error':
      return 'mdi-alert-circle'
    case 'warning':
      return 'mdi-alert-triangle'
    case 'info':
      return 'mdi-information'
    default:
      return 'mdi-bell'
  }
}
</script>

<style scoped>
.global-notifications {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 9999;
  max-width: 400px;
  width: 100%;
}
</style>
