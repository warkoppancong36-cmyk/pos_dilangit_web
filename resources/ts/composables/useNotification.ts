import { ref, readonly } from 'vue'

export interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  timeout?: number
}

const notifications = ref<Notification[]>([])

export const useNotification = () => {
  const addNotification = (notification: Omit<Notification, 'id'>) => {
    const id = Date.now().toString()
    const newNotification: Notification = {
      id,
      timeout: 5000,
      ...notification
    }
    
    notifications.value.push(newNotification)
    
    if (newNotification.timeout) {
      setTimeout(() => {
        removeNotification(id)
      }, newNotification.timeout)
    }
    
    return id
  }

  const removeNotification = (id: string) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  const clearNotifications = () => {
    notifications.value = []
  }

  const showSuccess = (title: string, message?: string, timeout?: number) => {
    return addNotification({
      type: 'success',
      title,
      message,
      timeout
    })
  }

  const showError = (title: string, message?: string, timeout?: number) => {
    return addNotification({
      type: 'error',
      title,
      message,
      timeout: timeout || 8000 // Errors stay longer by default
    })
  }

  const showWarning = (title: string, message?: string, timeout?: number) => {
    return addNotification({
      type: 'warning',
      title,
      message,
      timeout
    })
  }

  const showInfo = (title: string, message?: string, timeout?: number) => {
    return addNotification({
      type: 'info',
      title,
      message,
      timeout
    })
  }

  return {
    notifications: readonly(notifications),
    addNotification,
    removeNotification,
    clearNotifications,
    showSuccess,
    showError,
    showWarning,
    showInfo
  }
}
