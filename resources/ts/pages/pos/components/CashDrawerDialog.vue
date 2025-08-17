<template>
  <VDialog
    v-model="localDialog"
    max-width="600"
    persistent
  >
    <VCard class="coffee-dialog">
      <VCardTitle class="d-flex align-center justify-space-between coffee-header">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="mdi-cash-register"
            class="text-white"
          />
          <span class="text-white">
            Cash Drawer
          </span>
        </div>
        <VBtn
          icon="tabler-x"
          variant="text"
          color="white"
          @click="closeDialog"
        />
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VRow>
          <!-- Current Cash Summary -->
          <VCol cols="12">
            <VCard class="mb-6" variant="tonal" color="primary">
              <VCardTitle class="text-h6 pb-2">
                <VIcon icon="mdi-cash-multiple" size="20" class="me-2" />
                Kas Saat Ini
              </VCardTitle>
              <VCardText class="pt-0">
                <div class="text-h4 font-weight-bold text-primary">
                  {{ formatCurrency(currentCash) }}
                </div>
                <div v-if="isNaN(currentCash)" class="text-error text-caption">
                  Debug: currentCash is {{ currentCash }} ({{ typeof currentCash }})
                </div>
                <div class="text-body-2 text-medium-emphasis mt-1">
                  Terakhir update: {{ lastUpdated }}
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <VRow class="mb-4">
          <VCol cols="6">
            <VBtn
              color="success"
              variant="flat"
              block
              size="large"
              prepend-icon="mdi-plus-circle"
              @click="openCashInDialog"
            >
              Kas Masuk
            </VBtn>
          </VCol>
          <VCol cols="6">
            <VBtn
              color="error"
              variant="flat"
              block
              size="large"
              prepend-icon="mdi-minus-circle"
              @click="openCashOutDialog"
            >
              Kas Keluar
            </VBtn>
          </VCol>
        </VRow>

        <!-- Recent Transactions -->
        <VCard variant="outlined">
          <VCardTitle class="text-h6">
            <VIcon icon="mdi-history" size="20" class="me-2" />
            Transaksi Hari Ini
          </VCardTitle>
          <VCardText>
            <div v-if="recentTransactions.length === 0" class="text-center text-medium-emphasis py-4">
              Belum ada transaksi hari ini
            </div>
            <div v-else>
              <div
                v-for="(transaction, index) in recentTransactions"
                :key="index"
                class="d-flex justify-space-between align-center py-2 border-b"
              >
                <div>
                  <div class="font-weight-medium">{{ transaction.type_text || transaction.description }}</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ formatDate(transaction.created_at) }}
                  </div>
                </div>
                <div class="text-right">
                  <div
                    :class="transaction.type === 'in' ? 'text-success' : 'text-error'"
                    class="font-weight-bold"
                  >
                    {{ transaction.type === 'in' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                  </div>
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCardText>

      <VCardActions>
        <VSpacer />
        <VBtn variant="text" @click="closeDialog">
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>

    <!-- Cash In Dialog -->
    <VDialog
      v-model="cashInDialog"
      max-width="400"
      persistent
    >
      <VCard>
        <VCardTitle>
          <VIcon icon="mdi-plus-circle" class="me-2" />
          Kas Masuk
        </VCardTitle>
        <VCardText>
          <VForm ref="cashInForm" v-model="cashInFormValid">
            <VTextField
              v-model="cashInAmountDisplay"
              label="Jumlah Kas Masuk"
              variant="outlined"
              prefix="Rp"
              placeholder="0"
              :rules="[rules.required, rules.positive]"
              class="mb-4"
              @input="formatCashInAmount"
              @blur="formatCashInAmount"
            />
            <VTextarea
              v-model="cashInNotes"
              label="Catatan (Opsional)"
              variant="outlined"
              rows="3"
              placeholder="Masukkan catatan untuk kas masuk..."
            />
          </VForm>
        </VCardText>
        <VCardActions>
          <VBtn
            variant="text"
            @click="closeCashInDialog"
          >
            Batal
          </VBtn>
          <VSpacer />
          <VBtn
            color="success"
            :loading="processingCashIn"
            :disabled="!cashInFormValid || !cashInAmount"
            @click="processCashIn"
          >
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Cash Out Dialog -->
    <VDialog
      v-model="cashOutDialog"
      max-width="400"
      persistent
    >
      <VCard>
        <VCardTitle>
          <VIcon icon="mdi-minus-circle" class="me-2" />
          Kas Keluar
        </VCardTitle>
        <VCardText>
          <VForm ref="cashOutForm" v-model="cashOutFormValid">
            <VTextField
              v-model="cashOutAmountDisplay"
              label="Jumlah Kas Keluar"
              variant="outlined"
              prefix="Rp"
              placeholder="0"
              :rules="[rules.required, rules.positive]"
              class="mb-4"
              @input="formatCashOutAmount"
              @blur="formatCashOutAmount"
            />
            <VTextarea
              v-model="cashOutNotes"
              label="Catatan (Opsional)"
              variant="outlined"
              rows="3"
              placeholder="Masukkan catatan untuk kas keluar..."
            />
          </VForm>
        </VCardText>
        <VCardActions>
          <VBtn
            variant="text"
            @click="closeCashOutDialog"
          >
            Batal
          </VBtn>
          <VSpacer />
          <VBtn
            color="error"
            :loading="processingCashOut"
            :disabled="!cashOutFormValid || !cashOutAmount"
            @click="processCashOut"
          >
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VDialog>
</template>

<script setup lang="ts">
import { getAuthToken } from '@/utils/auth';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { VForm } from 'vuetify/components';

// Props
interface Props {
  modelValue: boolean
  dailySales?: number
  dailyExpenses?: number
}

const props = withDefaults(defineProps<Props>(), {
  dailySales: 0,
  dailyExpenses: 0
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// Dialog state
const localDialog = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

// Data
const currentCash = ref(0)
const lastUpdated = ref('')
const recentTransactions = ref<any[]>([])

// Cash In Dialog
const cashInDialog = ref(false)
const cashInForm = ref<VForm>()
const cashInFormValid = ref(false)
const cashInAmount = ref<number | null>(null)
const cashInAmountDisplay = ref('')
const cashInNotes = ref('')
const processingCashIn = ref(false)

// Cash Out Dialog
const cashOutDialog = ref(false)
const cashOutForm = ref<VForm>()
const cashOutFormValid = ref(false)
const cashOutAmount = ref<number | null>(null)
const cashOutAmountDisplay = ref('')
const cashOutNotes = ref('')
const processingCashOut = ref(false)

// Form validation rules
const rules = {
  required: (v: any) => !!v || 'Field wajib diisi',
  positive: (v: any) => {
    const num = typeof v === 'string' ? parseFloat(v.replace(/[^\d]/g, '')) : v
    return num > 0 || 'Nilai harus lebih besar dari 0'
  }
}

// Format currency for display
const formatCurrency = (amount: number | string): string => {
  console.log('formatCurrency called with:', amount, 'type:', typeof amount) // Debug log
  const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  
  if (isNaN(numericAmount) || numericAmount === null || numericAmount === undefined) {
    console.log('formatCurrency received invalid number:', amount, 'type:', typeof amount)
    return 'Rp 0'
  }
  
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(numericAmount)
}

// Format date for display
const formatDate = (dateString: string | null | undefined): string => {
  if (!dateString) {
    return '-'
  }
  
  try {
    const date = new Date(dateString)
    
    // Check if date is valid
    if (isNaN(date.getTime())) {
      console.warn('Invalid date format:', dateString)
      return '-'
    }
    
    return date.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    console.error('Error formatting date:', error, 'dateString:', dateString)
    return '-'
  }
}

const loadCashData = async () => {
  try {
    // Get token using helper function
    const token = getAuthToken()
    
    const headers: any = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }
    
    const response = await axios.get('/api/cash-drawer/data', { headers })
    
    console.log('API Response:', response.data) // Debug log
    const data = response.data.data
    
    // Parse current_balance - could be string or number
    let balance = 0
    if (data?.current_balance !== undefined && data?.current_balance !== null) {
      if (typeof data.current_balance === 'string') {
        balance = parseFloat(data.current_balance)
      } else if (typeof data.current_balance === 'number') {
        balance = data.current_balance
      }
    }
    
    // Set currentCash with parsed balance
    currentCash.value = isNaN(balance) ? 0 : balance
    
    console.log('Parsed balance:', balance, 'type:', typeof balance) // Debug log
    console.log('currentCash.value after assignment:', currentCash.value, 'type:', typeof currentCash.value) // Debug log
    
    lastUpdated.value = new Date().toLocaleString('id-ID')
    recentTransactions.value = Array.isArray(data?.today_transactions) ? data.today_transactions : []
  } catch (error) {
    console.error('Error loading cash data:', error)
    console.log('Using fallback data')
    // Fallback to calculating from props if API fails
    const startingCash = 500000
    const dailySales = typeof props.dailySales === 'number' && !isNaN(props.dailySales) ? props.dailySales : 0
    const dailyExpenses = typeof props.dailyExpenses === 'number' && !isNaN(props.dailyExpenses) ? props.dailyExpenses : 0
    
    currentCash.value = startingCash + dailySales - dailyExpenses
    lastUpdated.value = new Date().toLocaleString('id-ID')
    recentTransactions.value = []
  }
}

// Close dialog
const closeDialog = () => {
  localDialog.value = false
}

const openCashInDialog = () => {
  cashInDialog.value = true
  cashInAmount.value = null
  cashInAmountDisplay.value = ''
  cashInNotes.value = ''
}

const closeCashInDialog = () => {
  cashInDialog.value = false
  cashInForm.value?.reset()
}

// Format rupiah input function
const formatCashInAmount = (event: any) => {
  let value = event.target?.value || event
  
  // Remove all non-digit characters
  const numericValue = value.toString().replace(/\D/g, '')
  
  if (numericValue === '') {
    cashInAmount.value = null
    cashInAmountDisplay.value = ''
    return
  }
  
  // Convert to number
  const numberValue = parseInt(numericValue, 10)
  cashInAmount.value = numberValue
  
  // Format with thousand separators
  cashInAmountDisplay.value = numberValue.toLocaleString('id-ID')
}

const processCashIn = async () => {
  if (!cashInAmount.value) return

  try {
    processingCashIn.value = true
    
    // Get token using helper function
    const token = getAuthToken()
    
    const headers: any = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }
    
    const response = await axios.post('/api/cash-drawer/cash-in', {
      amount: cashInAmount.value,
      notes: cashInNotes.value,
      description: 'Kas Masuk Manual'
    }, { headers })
    
    const result = response.data
    
    // Update current cash from API response
    currentCash.value = result.data.new_balance
    
    // Add to recent transactions
    recentTransactions.value.unshift({
      id: Date.now(),
      type: 'in',
      type_text: 'Kas Masuk',
      amount: cashInAmount.value,
      notes: cashInNotes.value || 'Kas masuk manual',
      created_at: new Date().toISOString()
    })
    
    closeCashInDialog()
    console.log('Kas masuk berhasil ditambahkan')
    
  } catch (error) {
    console.error('Error processing cash in:', error)
  } finally {
    processingCashIn.value = false
  }
}

const openCashOutDialog = () => {
  cashOutDialog.value = true
  cashOutAmount.value = null
  cashOutAmountDisplay.value = ''
  cashOutNotes.value = ''
}

const closeCashOutDialog = () => {
  cashOutDialog.value = false
  cashOutForm.value?.reset()
}

// Format rupiah input function for cash out
const formatCashOutAmount = (event: any) => {
  let value = event.target?.value || event
  
  // Remove all non-digit characters
  const numericValue = value.toString().replace(/\D/g, '')
  
  if (numericValue === '') {
    cashOutAmount.value = null
    cashOutAmountDisplay.value = ''
    return
  }
  
  // Convert to number
  const numberValue = parseInt(numericValue, 10)
  cashOutAmount.value = numberValue
  
  // Format with thousand separators
  cashOutAmountDisplay.value = numberValue.toLocaleString('id-ID')
}

const processCashOut = async () => {
  if (!cashOutAmount.value) return

  try {
    processingCashOut.value = true
    
    // Get token using helper function
    const token = getAuthToken()
    
    const headers: any = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }
    
    const response = await axios.post('/api/cash-drawer/cash-out', {
      amount: cashOutAmount.value,
      notes: cashOutNotes.value,
      description: 'Kas Keluar Manual'
    }, { headers })
    
    const result = response.data
    
    // Update current cash from API response
    currentCash.value = result.data.new_balance
    
    // Add to recent transactions
    recentTransactions.value.unshift({
      id: Date.now(),
      type: 'out',
      type_text: 'Kas Keluar',
      amount: cashOutAmount.value,
      notes: cashOutNotes.value || 'Kas keluar manual',
      created_at: new Date().toISOString()
    })
    
    closeCashOutDialog()
    console.log('Kas keluar berhasil ditambahkan')
    
  } catch (error) {
    console.error('Error processing cash out:', error)
  } finally {
    processingCashOut.value = false
  }
}

// Watch dialog visibility
watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      loadCashData()
    }
  }
)

// Watcher for debugging
watch(currentCash, (newValue, oldValue) => {
  console.log('currentCash changed from', oldValue, 'to', newValue)
}, { deep: true, immediate: true })

// Lifecycle
onMounted(() => {
  if (props.modelValue) {
    loadCashData()
  }
})
</script>

<style scoped>
.border-b {
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.coffee-dialog {
  border-radius: 16px;
}

.coffee-header {
  background: linear-gradient(135deg, #6f4e37 0%, #8b4513 100%);
  color: white;
  padding-block: 20px;
  padding-inline: 24px;
}
</style>
