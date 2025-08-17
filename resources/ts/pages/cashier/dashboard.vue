<template>
  <div class="cashier-dashboard">
    <v-container>
      <v-row>
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h4 d-flex align-center">
              <v-icon class="me-3" color="success">mdi-cash-register</v-icon>
              Kasir Dashboard
            </v-card-title>
            
            <v-card-text>
              <v-alert type="success" variant="outlined" class="mb-4">
                Selamat datang Kasir! Kelola transaksi penjualan dengan mudah.
              </v-alert>
              
              <v-row>
                <!-- New Transaction -->
                <v-col cols="12" md="6">
                  <v-card color="success" dark class="h-100">
                    <v-card-title>
                      <v-icon class="me-2">mdi-cash-plus</v-icon>
                      New Transaction
                    </v-card-title>
                    <v-card-text>
                      <p>Mulai transaksi penjualan baru</p>
                      <v-btn block color="white" variant="outlined" size="large">
                        Start Transaction
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Transaction History -->
                <v-col cols="12" md="6">
                  <v-card color="blue" dark class="h-100">
                    <v-card-title>
                      <v-icon class="me-2">mdi-history</v-icon>
                      Transaction History
                    </v-card-title>
                    <v-card-text>
                      <p>Lihat riwayat transaksi hari ini</p>
                      <v-btn block color="white" variant="outlined" size="large">
                        View History
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Quick Actions -->
                <v-col cols="12" md="4">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title>Quick Actions</v-card-title>
                    <v-card-text>
                      <v-btn block color="orange" class="mb-2">
                        <v-icon class="me-2">mdi-barcode-scan</v-icon>
                        Scan Product
                      </v-btn>
                      <v-btn block color="purple" class="mb-2">
                        <v-icon class="me-2">mdi-receipt</v-icon>
                        Print Receipt
                      </v-btn>
                      <v-btn block color="teal">
                        <v-icon class="me-2">mdi-cash-multiple</v-icon>
                        Count Cash
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Today's Summary -->
                <v-col cols="12" md="8">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title>Today's Summary</v-card-title>
                    <v-card-text>
                      <v-row>
                        <v-col cols="6" md="3">
                          <v-sheet class="pa-3 text-center" color="success" rounded>
                            <h3 class="text-white">15</h3>
                            <p class="text-white ma-0">Transactions</p>
                          </v-sheet>
                        </v-col>
                        <v-col cols="6" md="3">
                          <v-sheet class="pa-3 text-center" color="info" rounded>
                            <h3 class="text-white">Rp 2.5M</h3>
                            <p class="text-white ma-0">Total Sales</p>
                          </v-sheet>
                        </v-col>
                        <v-col cols="6" md="3">
                          <v-sheet class="pa-3 text-center" color="warning" rounded>
                            <h3 class="text-white">45</h3>
                            <p class="text-white ma-0">Items Sold</p>
                          </v-sheet>
                        </v-col>
                        <v-col cols="6" md="3">
                          <v-sheet class="pa-3 text-center" color="error" rounded>
                            <h3 class="text-white">Rp 166K</h3>
                            <p class="text-white ma-0">Avg. Sale</p>
                          </v-sheet>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <!-- Recent Transactions -->
              <v-row class="mt-4">
                <v-col cols="12">
                  <v-card variant="outlined">
                    <v-card-title>Recent Transactions</v-card-title>
                    <v-card-text>
                      <v-table>
                        <thead>
                          <tr>
                            <th>Time</th>
                            <th>Receipt No.</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td>14:30</td>
                            <td>TRX-001</td>
                            <td>3 items</td>
                            <td>Rp 125,000</td>
                            <td>Cash</td>
                          </tr>
                          <tr>
                            <td>14:15</td>
                            <td>TRX-002</td>
                            <td>1 item</td>
                            <td>Rp 50,000</td>
                            <td>Card</td>
                          </tr>
                          <tr>
                            <td>13:45</td>
                            <td>TRX-003</td>
                            <td>5 items</td>
                            <td>Rp 275,000</td>
                            <td>Cash</td>
                          </tr>
                        </tbody>
                      </v-table>
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

// This page should be accessible by Kasir and above
definePage({
  meta: {
    permissions: {
      roles: ['Super Admin', 'Manager', 'Supervisor', 'Kasir']
    }
  }
})
</script>

<style scoped>
.cashier-dashboard {
  min-height: calc(100vh - 64px);
  background-color: #f5f5f5;
}
</style>
