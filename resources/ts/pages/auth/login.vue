<template>
  <div class="auth-page">
    <v-container class="fill-height" fluid>
      <v-row justify="center" align="center">
        <v-col cols="12" sm="8" md="6" lg="4">
          <v-card class="elevation-12" rounded="lg">
            <v-card-title class="text-center py-6">
              <h2 class="text-h4 font-weight-bold">POS Login</h2>
              <p class="text-subtitle-1 text-medium-emphasis mt-2">
                Masuk ke sistem Point of Sale
              </p>
            </v-card-title>

            <v-card-text>
              <v-form @submit.prevent="handleLogin" ref="form">
                <!-- Login Field (Username or Email) -->
                <v-text-field
                  v-model="loginForm.login"
                  label="Username atau Email"
                  prepend-inner-icon="mdi-account"
                  variant="outlined"
                  :rules="[rules.required]"
                  :error-messages="getFieldError('login')"
                  class="mb-3"
                />

                <!-- Password Field -->
                <v-text-field
                  v-model="loginForm.password"
                  :type="showPassword ? 'text' : 'password'"
                  label="Password"
                  prepend-inner-icon="mdi-lock"
                  :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                  @click:append-inner="showPassword = !showPassword"
                  variant="outlined"
                  :rules="[rules.required]"
                  :error-messages="getFieldError('password')"
                  class="mb-3"
                />

                <!-- Remember Me -->
                <v-checkbox
                  v-model="rememberMe"
                  label="Ingat saya"
                  color="primary"
                  hide-details
                  class="mb-4"
                />

                <!-- Error Alert -->
                <v-alert
                  v-if="errorMessage"
                  type="error"
                  variant="outlined"
                  class="mb-4"
                  :text="errorMessage"
                  closable
                  @click:close="errorMessage = ''"
                />

                <!-- Success Alert -->
                <v-alert
                  v-if="successMessage"
                  type="success"
                  variant="outlined"
                  class="mb-4"
                  :text="successMessage"
                  closable
                  @click:close="successMessage = ''"
                />

                <!-- Login Button -->
                <v-btn
                  type="submit"
                  color="primary"
                  size="large"
                  block
                  :loading="authStore.isLoading"
                  :disabled="!isFormValid"
                >
                  Masuk
                </v-btn>
              </v-form>
            </v-card-text>

            <v-divider class="mx-6" />

            <v-card-actions class="justify-center py-4">
              <p class="text-body-2 text-medium-emphasis">
                Belum punya akun?
                <v-btn 
                  variant="text" 
                  color="primary" 
                  size="small"
                  @click="router.push('/register')"
                >
                  Daftar disini
                </v-btn>
              </p>
            </v-card-actions>
          </v-card>

          <!-- Demo Accounts Info -->
          <v-card class="mt-4" variant="outlined">
            <v-card-title class="text-h6">Demo Accounts</v-card-title>
            <v-card-text>
              <div class="mb-2">
                <strong>Super Admin:</strong>
                <br>Username: admin | Password: password123
              </div>
              <div class="mb-2">
                <strong>Manager:</strong>
                <br>Username: manager | Password: password123
              </div>
              <div>
                <strong>Kasir:</strong>
                <br>Username: kasir | Password: password123
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

// Form data
const loginForm = ref({
  login: '',
  password: ''
})

const showPassword = ref(false)
const rememberMe = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const form = ref()

// Validation rules
const rules = {
  required: (value: string) => !!value || 'Field ini wajib diisi',
}

// Computed
const isFormValid = computed(() => {
  return loginForm.value.login && loginForm.value.password
})

// Methods
const getFieldError = (field: string): string[] => {
  return authStore.errors[field] || []
}

const handleLogin = async () => {
  if (!form.value.validate()) return

  errorMessage.value = ''
  successMessage.value = ''
  authStore.clearErrors()

  try {
    const result = await authStore.login({
      login: loginForm.value.login,
      password: loginForm.value.password
    })

    if (result.success) {
      successMessage.value = result.message || 'Login berhasil!'
      
      // Redirect based on user role
      setTimeout(() => {
        if (authStore.isAdmin) {
          router.push('/admin/dashboard')
        } else if (authStore.isManager) {
          router.push('/manager/dashboard')
        } else if (authStore.isCashier) {
          router.push('/cashier/dashboard')
        } else {
          router.push('/dashboard')
        }
      }, 1000)
    } else {
      errorMessage.value = result.message || 'Login gagal'
    }
  } catch (error: any) {
    errorMessage.value = error.message || 'Terjadi kesalahan saat login'
  }
}

// Auto-fill demo data for testing
const fillDemoData = (role: 'admin' | 'manager' | 'kasir') => {
  switch (role) {
    case 'admin':
      loginForm.value.login = 'admin'
      loginForm.value.password = 'password123'
      break
    case 'manager':
      loginForm.value.login = 'manager'
      loginForm.value.password = 'password123'
      break
    case 'kasir':
      loginForm.value.login = 'kasir'
      loginForm.value.password = 'password123'
      break
  }
}
</script>

<style scoped>
.auth-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
}
</style>
