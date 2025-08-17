<template>
  <div class="dashboard">
    <v-app-bar color="primary" dark>
      <v-app-bar-title>
        POS System - {{ authStore.userRole }}
      </v-app-bar-title>
      
      <v-spacer />
      
      <v-menu>
        <template #activator="{ props }">
          <v-btn v-bind="props" icon>
            <v-avatar size="40">
              <v-icon>mdi-account-circle</v-icon>
            </v-avatar>
          </v-btn>
        </template>
        
        <v-list>
          <v-list-item>
            <v-list-item-title>{{ authStore.user?.name }}</v-list-item-title>
            <v-list-item-subtitle>{{ authStore.user?.username }}</v-list-item-subtitle>
          </v-list-item>
          
          <v-divider />
          
          <v-list-item @click="handleProfile">
            <v-list-item-title>
              <v-icon class="me-2">mdi-account</v-icon>
              Profile
            </v-list-item-title>
          </v-list-item>
          
          <v-list-item @click="handleChangePassword">
            <v-list-item-title>
              <v-icon class="me-2">mdi-lock</v-icon>
              Ubah Password
            </v-list-item-title>
          </v-list-item>
          
          <v-divider />
          
          <v-list-item @click="handleLogout">
            <v-list-item-title class="text-error">
              <v-icon class="me-2">mdi-logout</v-icon>
              Logout
            </v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <v-main>
      <v-container>
        <v-row>
          <v-col cols="12">
            <v-card>
              <v-card-title class="text-h4">
                Selamat Datang, {{ authStore.user?.name }}!
              </v-card-title>
              
              <v-card-text>
                <v-row>
                  <!-- User Info -->
                  <v-col cols="12" md="6">
                    <v-card variant="outlined">
                      <v-card-title>Informasi User</v-card-title>
                      <v-card-text>
                        <v-list>
                          <v-list-item>
                            <v-list-item-title>Nama:</v-list-item-title>
                            <v-list-item-subtitle>{{ authStore.user?.name }}</v-list-item-subtitle>
                          </v-list-item>
                          
                          <v-list-item>
                            <v-list-item-title>Username:</v-list-item-title>
                            <v-list-item-subtitle>{{ authStore.user?.username }}</v-list-item-subtitle>
                          </v-list-item>
                          
                          <v-list-item>
                            <v-list-item-title>Email:</v-list-item-title>
                            <v-list-item-subtitle>{{ authStore.user?.email }}</v-list-item-subtitle>
                          </v-list-item>
                          
                          <v-list-item>
                            <v-list-item-title>Role:</v-list-item-title>
                            <v-list-item-subtitle>
                              <v-chip 
                                :color="getRoleColor(authStore.userRole)" 
                                size="small"
                              >
                                {{ authStore.userRole }}
                              </v-chip>
                            </v-list-item-subtitle>
                          </v-list-item>
                          
                          <v-list-item v-if="authStore.user?.last_login_at">
                            <v-list-item-title>Last Login:</v-list-item-title>
                            <v-list-item-subtitle>
                              {{ formatDate(authStore.user.last_login_at) }}
                            </v-list-item-subtitle>
                          </v-list-item>
                        </v-list>
                      </v-card-text>
                    </v-card>
                  </v-col>

                  <!-- Permissions -->
                  <v-col cols="12" md="6">
                    <v-card variant="outlined">
                      <v-card-title>Permissions</v-card-title>
                      <v-card-text>
                        <div v-if="Object.keys(authStore.userPermissions).length > 0">
                          <v-chip 
                            v-for="(value, permission) in authStore.userPermissions"
                            :key="permission"
                            :color="value ? 'success' : 'error'"
                            size="small"
                            class="ma-1"
                          >
                            {{ permission }}: {{ value ? 'Ya' : 'Tidak' }}
                          </v-chip>
                        </div>
                        <p v-else class="text-medium-emphasis">
                          Tidak ada permission yang ditetapkan
                        </p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>

                <!-- Role-based Menu -->
                <v-row class="mt-4">
                  <v-col cols="12">
                    <v-card variant="outlined">
                      <v-card-title>Menu Berdasarkan Role</v-card-title>
                      <v-card-text>
                        <v-row>
                          <!-- Admin Menu -->
                          <v-col v-if="authStore.isAdmin" cols="12" sm="6" md="4">
                            <v-card color="error" dark>
                              <v-card-title>Admin Panel</v-card-title>
                              <v-card-text>
                                <v-btn block color="white" variant="outlined">
                                  Kelola User
                                </v-btn>
                                <v-btn block color="white" variant="outlined" class="mt-2">
                                  Kelola Role
                                </v-btn>
                                <v-btn block color="white" variant="outlined" class="mt-2">
                                  Setting Sistem
                                </v-btn>
                              </v-card-text>
                            </v-card>
                          </v-col>

                          <!-- Manager Menu -->
                          <v-col v-if="authStore.isManager || authStore.isAdmin" cols="12" sm="6" md="4">
                            <v-card color="warning" dark>
                              <v-card-title>Manager Panel</v-card-title>
                              <v-card-text>
                                <v-btn block color="white" variant="outlined">
                                  Laporan Penjualan
                                </v-btn>
                                <v-btn block color="white" variant="outlined" class="mt-2">
                                  Kelola Produk
                                </v-btn>
                                <v-btn block color="white" variant="outlined" class="mt-2">
                                  Kelola Inventory
                                </v-btn>
                              </v-card-text>
                            </v-card>
                          </v-col>

                          <!-- Cashier Menu -->
                          <v-col v-if="authStore.isCashier || authStore.isManager || authStore.isAdmin" cols="12" sm="6" md="4">
                            <v-card color="success" dark>
                              <v-card-title>Kasir Panel</v-card-title>
                              <v-card-text>
                                <v-btn block color="white" variant="outlined">
                                  Transaksi Penjualan
                                </v-btn>
                                <v-btn block color="white" variant="outlined" class="mt-2">
                                  Riwayat Transaksi
                                </v-btn>
                                <v-btn block color="white" variant="outlined" class="mt-2">
                                  Laporan Shift
                                </v-btn>
                              </v-card-text>
                            </v-card>
                          </v-col>
                        </v-row>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Dialogs -->
    <v-dialog v-model="profileDialog" max-width="600">
      <v-card>
        <v-card-title>Profile</v-card-title>
        <v-card-text>
          <p>Profile management akan ditambahkan di sini</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="profileDialog = false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="passwordDialog" max-width="500">
      <v-card>
        <v-card-title>Ubah Password</v-card-title>
        <v-card-text>
          <p>Form ubah password akan ditambahkan di sini</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="passwordDialog = false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const profileDialog = ref(false)
const passwordDialog = ref(false)

// Methods
const getRoleColor = (role?: string) => {
  switch (role) {
    case 'Super Admin': return 'error'
    case 'Manager': return 'warning'
    case 'Kasir': return 'success'
    default: return 'primary'
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('id-ID')
}

const handleProfile = () => {
  profileDialog.value = true
}

const handleChangePassword = () => {
  passwordDialog.value = true
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

// Check authentication on mount
if (!authStore.isLoggedIn) {
  router.push('/login')
}
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background-color: #f5f5f5;
}
</style>
