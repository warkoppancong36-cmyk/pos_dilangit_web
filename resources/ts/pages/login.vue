<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import AuthProvider from '@/views/pages/authentication/AuthProvider.vue'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { useRouter } from 'vue-router'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const authStore = useAuthStore()
const router = useRouter()

const form = ref({
  login: '',
  password: '',
  remember: false,
})

const isPasswordVisible = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

const authThemeImg = useGenerateImageVariant(
  authV2LoginIllustrationLight,
  authV2LoginIllustrationDark,
  authV2LoginIllustrationBorderedLight,
  authV2LoginIllustrationBorderedDark,
  true)

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

// Handle form submission
const handleLogin = async () => {
  isLoading.value = true
  errorMessage.value = ''
  
  try {
    const result = await authStore.login({
      login: form.value.login,
      password: form.value.password,
    })
    
    if (result.success) {
      // Redirect to dashboard or home page
      router.push('/')
    } else {
      errorMessage.value = result.message || 'Login failed'
    }
  } catch (error) {
    errorMessage.value = 'An error occurred during login'
  } finally {
    isLoading.value = false
  }
}

// Fill demo data
const fillDemo = (role: string) => {
  switch (role) {
    case 'admin':
      form.value.login = 'admin'
      form.value.password = 'admin123'
      break
    case 'manager':
      form.value.login = 'manager'
      form.value.password = 'manager123'
      break
    case 'kasir':
      form.value.login = 'kasir001'
      form.value.password = 'kasir123'
      break
  }
}
</script>

<template>
  <a href="javascript:void(0)">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </a>

  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem;"
        >
          <VImg
            max-width="613"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask flip-in-rtl"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-6"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Welcome to <span class="text-capitalize">{{ themeConfig.app.title }}</span>! 👋🏻
          </h4>
          <p class="mb-0">
            Please sign-in to your account and start the adventure
          </p>
        </VCardText>
        <VCardText>
          <VForm @submit.prevent="handleLogin">
            <!-- Error Message -->
            <VAlert
              v-if="errorMessage"
              type="error"
              class="mb-4"
              closable
              @click:close="errorMessage = ''"
            >
              {{ errorMessage }}
            </VAlert>
            
            <VRow>
              <!-- login field -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.login"
                  autofocus
                  label="Email or Username"
                  placeholder="admin atau admin@example.com"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.password"
                  label="Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <div class="d-flex align-center flex-wrap justify-space-between my-6">
                  <VCheckbox
                    v-model="form.remember"
                    label="Remember me"
                  />
                  <a
                    class="text-primary"
                    href="javascript:void(0)"
                  >
                    Forgot Password?
                  </a>
                </div>

                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                  :disabled="!form.login || !form.password"
                >
                  Login
                </VBtn>
              </VCol>

              <!-- create account -->
              <VCol
                cols="12"
                class="text-body-1 text-center"
              >
                <span class="d-inline-block">
                  New on our platform?
                </span>
                <a
                  class="text-primary ms-1 d-inline-block text-body-1"
                  href="javascript:void(0)"
                >
                  Create an account
                </a>
              </VCol>

              <VCol
                cols="12"
                class="d-flex align-center"
              >
                <VDivider />
                <span class="mx-4">or</span>
                <VDivider />
              </VCol>

              <!-- auth providers -->
              <VCol
                cols="12"
                class="text-center"
              >
                <AuthProvider />
              </VCol>
              
              <!-- Demo Accounts -->
              <VCol cols="12">
                <VCard 
                  variant="outlined" 
                  color="info"
                  class="mt-4"
                >
                  <VCardTitle class="text-h6">Demo Accounts</VCardTitle>
                  <VCardText>
                    <div class="mb-2">
                      <strong>Super Admin:</strong> admin / admin123
                      <VBtn 
                        size="small" 
                        variant="text" 
                        color="primary"
                        @click="fillDemo('admin')"
                      >
                        Use
                      </VBtn>
                    </div>
                    <div class="mb-2">
                      <strong>Manager:</strong> manager / manager123
                      <VBtn 
                        size="small" 
                        variant="text" 
                        color="primary"
                        @click="fillDemo('manager')"
                      >
                        Use
                      </VBtn>
                    </div>
                    <div>
                      <strong>Kasir:</strong> kasir001 / kasir123
                      <VBtn 
                        size="small" 
                        variant="text" 
                        color="primary"
                        @click="fillDemo('kasir')"
                      >
                        Use
                      </VBtn>
                    </div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
