<template>
  <div class="manager-dashboard">
    <v-container>
      <v-row>
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h4 d-flex align-center">
              <v-icon class="me-3" color="warning">mdi-briefcase</v-icon>
              Manager Dashboard
            </v-card-title>
            
            <v-card-text>
              <v-alert type="warning" variant="outlined" class="mb-4">
                Dashboard khusus untuk Manager. Kelola operasional toko dan pantau performa.
              </v-alert>
              
              <v-row>
                <!-- Sales Reports -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="warning" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-chart-line</v-icon>
                      Sales Reports
                    </v-card-title>
                    <v-card-text>
                      <p>Laporan penjualan harian/bulanan</p>
                      <v-btn block color="white" variant="outlined">
                        View Reports
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Product Management -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="blue" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-package</v-icon>
                      Product Management
                    </v-card-title>
                    <v-card-text>
                      <p>Kelola produk dan kategori</p>
                      <v-btn block color="white" variant="outlined">
                        Manage Products
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Inventory -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="teal" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-warehouse</v-icon>
                      Inventory Management
                    </v-card-title>
                    <v-card-text>
                      <p>Kelola stok dan inventory</p>
                      <v-btn block color="white" variant="outlined">
                        Manage Inventory
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Staff Management -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="purple" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-account-group</v-icon>
                      Staff Management
                    </v-card-title>
                    <v-card-text>
                      <p>Kelola kasir dan supervisor</p>
                      <v-btn block color="white" variant="outlined">
                        Manage Staff
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Promotions -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="pink" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-sale</v-icon>
                      Promotions
                    </v-card-title>
                    <v-card-text>
                      <p>Kelola promo dan diskon</p>
                      <v-btn block color="white" variant="outlined">
                        Manage Promotions
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Analytics -->
                <v-col cols="12" md="6" lg="4">
                  <v-card color="indigo" dark>
                    <v-card-title>
                      <v-icon class="me-2">mdi-analytics</v-icon>
                      Analytics
                    </v-card-title>
                    <v-card-text>
                      <p>Analisis performa toko</p>
                      <v-btn block color="white" variant="outlined">
                        View Analytics
                      </v-btn>
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

// This page should only be accessible by Manager and above
definePage({
  meta: {
    permissions: {
      roles: ['Super Admin', 'Manager']
    }
  }
})
</script>

<style scoped>
.manager-dashboard {
  min-height: calc(100vh - 64px);
  background-color: #f5f5f5;
}
</style>
