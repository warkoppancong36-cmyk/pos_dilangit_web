<template>
  <div class="admin-dashboard">
    <v-container>
      <v-row>
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h4 d-flex align-center">
              <v-icon class="me-3" color="error">mdi-shield-crown</v-icon>
              Admin Dashboard
            </v-card-title>
            
            <v-card-text>
              <v-alert type="info" variant="outlined" class="mb-4">
                Selamat datang di Admin Dashboard! Hanya Super Admin yang dapat mengakses halaman ini.
              </v-alert>
              
              <v-row>
                <!-- User Management -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="error" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-account-multiple</v-icon>
                      User Management
                    </v-card-title>
                    <v-card-text>
                      <p>Kelola pengguna sistem POS</p>
                      <v-btn block color="white" variant="outlined">
                        Manage Users
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Role Management -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="deep-purple" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-shield-account</v-icon>
                      Role Management
                    </v-card-title>
                    <v-card-text>
                      <p>Kelola role dan permissions</p>
                      <v-btn block color="white" variant="outlined">
                        Manage Roles
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- System Settings -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="indigo" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-cog</v-icon>
                      System Settings
                    </v-card-title>
                    <v-card-text>
                      <p>Konfigurasi sistem POS</p>
                      <v-btn block color="white" variant="outlined">
                        Settings
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Reports -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="green" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-chart-line</v-icon>
                      Advanced Reports
                    </v-card-title>
                    <v-card-text>
                      <p>Laporan lengkap sistem</p>
                      <v-btn block color="white" variant="outlined">
                        View Reports
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Audit Logs -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="orange" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-history</v-icon>
                      Audit Logs
                    </v-card-title>
                    <v-card-text>
                      <p>Log aktivitas sistem</p>
                      <v-btn block color="white" variant="outlined">
                        View Logs
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Backup & Restore -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="blue-grey" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-backup-restore</v-icon>
                      Backup & Restore
                    </v-card-title>
                    <v-card-text>
                      <p>Backup dan restore data</p>
                      <v-btn block color="white" variant="outlined">
                        Manage Backup
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
              
              <!-- Quick Stats -->
              <v-row class="mt-4">
                <v-col cols="12">
                  <v-card variant="outlined">
                    <v-card-title>System Overview</v-card-title>
                    <v-card-text>
                      <v-row>
                        <v-col cols="6" md="3">
                          <v-sheet class="pa-4 text-center" color="error" rounded>
                            <h2 class="text-white">5</h2>
                            <p class="text-white ma-0">Total Users</p>
                          </v-sheet>
                        </v-col>
                        <v-col cols="6" md="3">
                          <v-sheet class="pa-4 text-center" color="warning" rounded>
                            <h2 class="text-white">4</h2>
                            <p class="text-white ma-0">Active Roles</p>
                          </v-sheet>
                        </v-col>
                        <v-col cols="6" md="3">
                          <v-sheet class="pa-4 text-center" color="success" rounded>
                            <h2 class="text-white">245</h2>
                            <p class="text-white ma-0">Login Logs</p>
                          </v-sheet>
                        </v-col>
                        <v-col cols="6" md="3">
                          <v-sheet class="pa-4 text-center" color="info" rounded>
                            <h2 class="text-white">12</h2>
                            <p class="text-white ma-0">Permissions</p>
                          </v-sheet>
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
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore()

// This page should only be accessible by Super Admin
definePage({
  meta: {
    permissions: {
      roles: ['Super Admin']
    }
  }
})
</script>

<style scoped>
.admin-dashboard {
  min-height: calc(100vh - 64px);
  background-color: #f5f5f5;
}
</style>
