<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

onMounted(async () => {
  if (authStore.token && !authStore.user) {
    const success = await authStore.fetchProfile()
    if (!success) {
      router.push('/login')
      return
    }
  } else if (!authStore.isLoggedIn) {
    router.push('/login')
    return
  }
  
  // Auto redirect admin/manager to dashboard
  if (authStore.isAdmin || authStore.isManager) {
    router.push('/dashboard')
  }
})

const getRoleColor = () => {
  switch (authStore.userRole) {
    case 'Super Admin': return 'error'
    case 'Manager': return 'warning'
    case 'Kasir': return 'success'
    default: return 'primary'
  }
}

const goToDashboard = () => {
  if (authStore.isAdmin || authStore.isManager) {
    router.push('/dashboard')
  } else if (authStore.isCashier) {
    router.push('/pos')
  } else {
    router.push('/dashboard')
  }
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div>
    <VCard
      class="mb-6"
      title="Welcome to POS Dashboard! 🎉"
    >
      <VCardText>
        <div v-if="authStore.user">
          <p>Hello <strong>{{ authStore.user.name }}</strong>! You are successfully logged in.</p>
          <p>Username: <strong>{{ authStore.user.username }}</strong></p>
          <p>Email: <strong>{{ authStore.user.email }}</strong></p>
          <p>Role: <v-chip :color="getRoleColor()" size="small">{{ authStore.userRole }}</v-chip></p>
          <p v-if="authStore.user.last_login_at">
            Last Login: {{ new Date(authStore.user.last_login_at).toLocaleString('id-ID') }}
          </p>
        </div>
        <div v-else>
          <p>Loading user information...</p>
        </div>
      </VCardText>
      <VCardActions>
        <VBtn
          color="primary"
          variant="outlined"
          @click="goToDashboard"
        >
          Go to Dashboard
        </VBtn>
        <VBtn
          color="error"
          variant="outlined"
          @click="handleLogout"
        >
          Logout
        </VBtn>
      </VCardActions>
    </VCard>

    <VCard
      class="mb-6"
      title="Quick Actions"
      v-if="authStore.user"
    >
      <VCardText>
        <div class="d-flex flex-wrap gap-3">
          <VBtn
            v-if="authStore.isAdmin"
            color="error"
            variant="outlined"
            prepend-icon="mdi-account-multiple"
          >
            Manage Users
          </VBtn>
          <VBtn
            v-if="authStore.isManager || authStore.isAdmin"
            color="warning"
            variant="outlined"
            prepend-icon="mdi-chart-line"
          >
            Sales Report
          </VBtn>
          <VBtn
            v-if="authStore.isManager || authStore.isAdmin"
            color="info"
            variant="outlined"
            prepend-icon="mdi-package"
          >
            Inventory
          </VBtn>
          <VBtn
            v-if="authStore.isCashier || authStore.isManager || authStore.isAdmin"
            color="success"
            variant="outlined"
            prepend-icon="mdi-cash-register"
          >
            New Transaction
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <VCard
      v-if="authStore.user?.role?.permissions && Object.keys(authStore.user.role.permissions).length > 0"
      class="mb-6"
      title="Your Permissions"
    >
      <VCardText>
        <div class="d-flex flex-wrap gap-2">
          <VChip
            v-for="(value, permission) in authStore.user.role.permissions"
            :key="permission"
            :color="value ? 'success' : 'error'"
            size="small"
          >
            {{ permission }}: {{ value ? 'Yes' : 'No' }}
          </VChip>
        </div>
      </VCardText>
    </VCard>

    <VCard
      class="mb-6"
      title="Kick start your project 🚀"
    >
      <VCardText>All the best for your new project.</VCardText>
      <VCardText>
        Please make sure to read our <a
          href="https://demos.pixinvent.com/vuexy-vuejs-admin-template/documentation/"
          target="_blank"
          rel="noopener noreferrer"
          class="text-decoration-none"
        >
          Template Documentation
        </a> to understand where to go from here and how to use our template.
      </VCardText>
    </VCard>

    <VCard title="Want to integrate JWT? 🔒">
      <VCardText>We carefully crafted JWT flow so you can implement JWT with ease and with minimum efforts.</VCardText>
      <VCardText>Please read our  JWT Documentation to get more out of JWT authentication.</VCardText>
    </VCard>
  </div>
</template>
