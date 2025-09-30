<template>
  <div class="pos-layout">
    <!-- Header Kompak -->
    <div class="pos-header">
      <div class="header-content">
        <div class="header-left">
          <div class="logo-section">
            <VAvatar size="40" color="primary">
              <VIcon size="20">tabler-shopping-cart</VIcon>
            </VAvatar>
            <div class="header-info">
              <h3 class="pos-title">Point of Sale</h3>
              <div class="header-meta">
                <span>{{ currentUser?.name || 'Administrator' }}</span>
                <span>•</span>
                <span>{{ currentTime }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="header-actions">
          <VBtn
            variant="text"
            color="primary"
            prepend-icon="tabler-history"
            @click="viewTransactionHistory"
          >
            Riwayat
          </VBtn>
          <VBtn
            variant="outlined"
            color="success"
            prepend-icon="tabler-receipt"
            @click="openCashDrawer"
          >
            Cash Drawer
          </VBtn>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="pos-main">
      <!-- Product Panel -->
      <div class="product-panel">
        <!-- Search & Filter Bar -->
        <div class="filter-bar">
          <div class="search-section">
            <VTextField
              v-model="productSearch"
              placeholder="Cari produk, SKU, atau barcode..."
              density="comfortable"
              variant="solo"
              prepend-inner-icon="tabler-search"
              clearable
              hide-details
              class="search-input"
              @click:clear="productSearch = ''"
            />
          </div>
          
          <div class="filter-section">
            <VSelect
              v-model="selectedCategory"
              :items="categories"
              item-title="name"
              item-value="id_category"
              placeholder="Kategori"
              density="comfortable"
              variant="solo"
              prepend-inner-icon="tabler-category"
              clearable
              hide-details
              style="min-inline-size: 180px;"
              class="category-select"
            />
            
            <VSelect
              v-model="selectedStation"
              :items="stationOptions"
              item-title="title"
              item-value="value"
              placeholder="Station"
              density="comfortable"
              variant="solo"
              prepend-inner-icon="tabler-tools-kitchen-2"
              clearable
              hide-details
              style="min-inline-size: 150px;"
              class="station-select"
            />
            
            <VChip
              v-if="filteredProducts.length > 0"
              color="primary"
              variant="tonal"
              size="large"
            >
              {{ filteredProducts.length }} produk
            </VChip>
          </div>
        </div>

        <!-- Products Grid -->
        <div class="products-container">
          <div v-if="!loading && filteredProducts.length > 0" class="products-grid">
            <div
              v-for="product in filteredProducts"
              :key="product.id_product"
              class="product-item"
              :class="{ 
                'out-of-stock': product.stock === 0,
                'no-price': !product.selling_price || product.selling_price <= 0,
                'disabled': product.stock === 0 || !product.selling_price || product.selling_price <= 0
              }"
              @click="canAddToCart(product) ? addToCart(product) : null"
            >
              <div class="product-image">
                <div class="product-image-wrapper">
                  <VImg
                    v-if="product.image && !imageErrors[product.id_product]"
                    :src="product.image"
                    class="product-image-content"
                    cover
                    @error="handleImageError(product.id_product)"
                  />
                  <div v-else class="product-image-placeholder">
                    <VIcon size="32" color="grey-400">tabler-package</VIcon>
                    <span class="placeholder-text">{{ getProductInitials(product.name) }}</span>
                  </div>
                </div>
                
                <!-- Stock Badge -->
                <div class="stock-badge" :class="getStockBadgeClass(product.stock)">
                  <VIcon 
                    :icon="product.stock > 0 ? 'tabler-package' : 'tabler-package-off'" 
                    size="12" 
                    class="mr-1"
                  />
                  Stock: {{ product.stock }}
                </div>
                
                <!-- Station Availability Badges -->
                <div class="station-badges">
                  <div 
                    v-if="product.available_in_kitchen"
                    class="station-badge kitchen-badge"
                    title="Tersedia di Kitchen"
                  >
                    <VIcon icon="tabler-chef-hat" size="10" />
                    Kitchen
                  </div>
                  <div 
                    v-if="product.available_in_bar"
                    class="station-badge bar-badge"
                    title="Tersedia di Bar"
                  >
                    <VIcon icon="tabler-glass-cocktail" size="10" />
                    Bar
                  </div>
                </div>
                
                <!-- Status Indicators -->
                <div v-if="product.stock === 0" class="status-indicator out-of-stock-indicator">
                  <VIcon icon="tabler-alert-circle" size="14" />
                  Stok Habis
                </div>
                <div v-else-if="!product.selling_price || product.selling_price <= 0" class="status-indicator no-price-indicator">
                  <VIcon icon="tabler-alert-triangle" size="14" />
                  Harga Kosong
                </div>
                
                <!-- Cart Indicator -->
                <div v-if="getCartQuantity(product.id_product) > 0" class="cart-indicator">
                  {{ getCartQuantity(product.id_product) }} di keranjang
                </div>
              </div>
              
              <div class="product-info">
                <h6 class="product-name">{{ product.name }}</h6>
                <p class="product-sku">{{ product.sku }}</p>
                
                <div class="product-footer">
                  <span class="product-price" :class="{ 'price-missing': !product.selling_price || product.selling_price <= 0 }">
                    {{ product.selling_price && product.selling_price > 0 ? formatCurrency(product.selling_price) : 'Harga tidak tersedia' }}
                  </span>
                  <VBtn
                    size="small"
                    color="primary"
                    variant="flat"
                    icon="tabler-plus"
                    :disabled="!canAddToCart(product)"
                    @click.stop="canAddToCart(product) ? addToCart(product) : null"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-else-if="loading" class="empty-state">
            <VProgressCircular size="48" color="primary" indeterminate />
            <p class="empty-text">Memuat produk...</p>
          </div>

          <!-- Empty State -->
          <div v-else class="empty-state">
            <VIcon size="64" color="grey-400">
              {{ productSearch ? 'tabler-search-off' : 'tabler-package' }}
            </VIcon>
            <h4 class="empty-title">
              {{ productSearch ? 'Produk Tidak Ditemukan' : 'Tidak Ada Produk' }}
            </h4>
            <p class="empty-text">
              {{ productSearch 
                ? `Coba kata kunci lain untuk pencarian "${productSearch}"`
                : 'Belum ada produk yang tersedia'
              }}
            </p>
            <VBtn
              v-if="productSearch"
              variant="outlined"
              @click="productSearch = ''"
            >
              Reset Pencarian
            </VBtn>
          </div>
        </div>
      </div>

      <!-- Cart Panel -->
      <div class="cart-panel">
        <div class="cart-header">
          <div class="cart-title">
            <VIcon size="20" color="success">tabler-shopping-cart</VIcon>
            <span>Keranjang</span>
            <VChip
              v-if="cartItems.length > 0"
              color="success"
              variant="tonal"
              size="small"
            >
              {{ cartCount }}
            </VChip>
          </div>
          
          <VBtn
            v-if="cartItems.length > 0"
            variant="text"
            color="error"
            size="small"
            prepend-icon="tabler-trash"
            @click="clearCartConfirm"
          >
            Hapus Semua
          </VBtn>
        </div>

        <div class="cart-content">
          <!-- Cart Items -->
          <div v-if="cartItems.length > 0" class="cart-items">
            <div
              v-for="item in cartItems"
              :key="item.id_product"
              class="cart-item"
            >
              <div class="item-info">
                <h6 class="item-name">{{ item.name }}</h6>
                <p class="item-price">{{ formatCurrency(item.selling_price) }} / item</p>
              </div>
              
              <div class="item-controls">
                <div class="quantity-controls">
                  <VBtn
                    size="x-small"
                    variant="text"
                    color="error"
                    icon="tabler-minus"
                    :disabled="item.quantity <= 1"
                    @click="updateQuantity(item.id_product, item.quantity - 1)"
                  />
                  <span class="quantity">{{ item.quantity }}</span>
                  <VBtn
                    size="x-small"
                    variant="text"
                    color="success"
                    icon="tabler-plus"
                    :disabled="item.quantity >= item.stock"
                    @click="updateQuantity(item.id_product, item.quantity + 1)"
                  />
                </div>
                
                <div class="item-total">
                  {{ formatCurrency(item.selling_price * item.quantity) }}
                </div>
                
                <VBtn
                  size="x-small"
                  variant="text"
                  color="error"
                  icon="tabler-x"
                  @click="removeFromCart(item.id_product)"
                />
              </div>
            </div>
          </div>

          <!-- Empty Cart -->
          <div v-else class="empty-cart">
            <VIcon size="48" color="grey-400">tabler-shopping-cart</VIcon>
            <h4>Keranjang Kosong</h4>
            <p>Pilih produk untuk mulai berbelanja</p>
          </div>
        </div>

        <!-- Cart Summary -->
        <div v-if="cartItems.length > 0" class="cart-summary">
          <!-- Summary Details -->
          <div class="summary-row">
            <span class="summary-label">Subtotal</span>
            <span class="summary-value">{{ formatCurrency(subtotal) }}</span>
          </div>
          
          <div class="summary-row">
            <span class="summary-label">Items</span>
            <span class="summary-value">{{ cartCount }}</span>
          </div>
          
          <div class="summary-row">
            <span class="summary-label summary-total">Total</span>
            <span class="summary-value summary-total">{{ formatCurrency(subtotal) }}</span>
          </div>

          <!-- Checkout Button -->
          <VBtn
            color="success"
            size="large"
            block
            class="checkout-btn"
            @click="openPaymentDialog"
          >
            <VIcon start>tabler-credit-card</VIcon>
            Bayar Sekarang
          </VBtn>
        </div>
      </div>
    </div>

    <!-- Dialogs -->
    <PaymentDialog
      v-model="paymentDialog"
      :cart-items="mutableCartItems"
      :customers="customers"
      @payment-success="handlePaymentComplete"
      @clear-cart="clearCart"
    />

    <TransactionHistoryDialog
      v-model="transactionHistoryDialog"
    />

    <CashDrawerDialog
      v-model="cashDrawerDialog"
    />
    
    <!-- Clear Cart Confirmation -->
    <VDialog v-model="clearCartDialog" max-width="400" persistent>
      <VCard>
        <VCardTitle>Hapus Semua Item?</VCardTitle>
        <VCardText>
          Apakah Anda yakin ingin menghapus semua item dari keranjang?
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="clearCartDialog = false">Batal</VBtn>
          <VBtn color="error" variant="text" @click="confirmClearCart">Hapus</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Success Snackbar -->
    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="4000"
      location="top right"
    >
      {{ snackbar.message }}
    </VSnackbar>
  </div>
</template>

<script setup lang="ts">
import usePOS, { type Customer, type Product } from '@/composables/usePOS'
import { useAuthStore } from '@/stores/auth'
import { PosApi } from '@/utils/api/PosApi'
import { computed, onMounted, ref } from 'vue'
import CashDrawerDialog from './components/CashDrawerDialog.vue'
import PaymentDialog from './components/PaymentDialog.vue'
import TransactionHistoryDialog from './components/TransactionHistoryDialog.vue'

// Store
const authStore = useAuthStore()

// Composable
const {
  cartItems,
  subtotal,
  cartCount,
  addToCart: addToCartComposable,
  removeFromCart,
  updateQuantity,
  clearCart: clearCartComposable
} = usePOS()

// Reactive data
const loading = ref(false)
const products = ref<Product[]>([])
const categories = ref<any[]>([])
const customers = ref<Customer[]>([])
const productSearch = ref('')
const selectedCategory = ref<number | null>(null)
const selectedStation = ref<'kitchen' | 'bar' | null>(null)
const paymentDialog = ref(false)
const transactionHistoryDialog = ref(false)
const cashDrawerDialog = ref(false)
const clearCartDialog = ref(false)
const currentTime = ref('')
const imageErrors = ref<Record<number, boolean>>({})

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Update time every second
setInterval(() => {
  currentTime.value = new Date().toLocaleTimeString('id-ID', { 
    hour: '2-digit', 
    minute: '2-digit'
  })
}, 1000)

// Computed
const currentUser = computed(() => authStore.user)
const currentDate = computed(() => {
  return new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

// Create mutable cart items for payment dialog
const mutableCartItems = computed(() => {
  return cartItems.value.map(item => ({
    ...item
  }))
})

// Station options
const stationOptions = [
  { title: 'Kitchen', value: 'kitchen' },
  { title: 'Bar', value: 'bar' }
]

const filteredProducts = computed(() => {
  let filtered = products.value

  // Filter by search
  if (productSearch.value) {
    const search = productSearch.value.toLowerCase()
    filtered = filtered.filter(product =>
      product.name.toLowerCase().includes(search) ||
      product.sku?.toLowerCase().includes(search) ||
      product.barcode?.toLowerCase().includes(search)
    )
  }

  // Filter by category
  if (selectedCategory.value) {
    filtered = filtered.filter(product => product.id_category === selectedCategory.value)
  }

  // Filter by station availability
  if (selectedStation.value) {
    filtered = filtered.filter(product => {
      if (selectedStation.value === 'kitchen') {
        return product.available_in_kitchen !== false
      } else if (selectedStation.value === 'bar') {
        return product.available_in_bar !== false
      }
      return true
    })
  }

  return filtered
})

// Methods
const loadProducts = async () => {
  try {
    loading.value = true
    const response = await PosApi.getProducts()
    // Transform ProductForPos to Product interface
    products.value = response.data.map(p => ({
      id_product: p.id_product,
      name: p.name,
      sku: p.sku,
      selling_price: parseFloat(p.price),
      // Get stock from stock_info, fallback to 0 if not available
      stock: p.stock_info?.available_stock || p.stock_info?.current_stock || p.stock || 0,
      image: p.image_url || p.image,
      id_category: p.category?.id_category,
      available_in_kitchen: p.available_in_kitchen ?? true,
      available_in_bar: p.available_in_bar ?? true
    }))
  } catch (error) {
    console.error('Error loading products:', error)
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await PosApi.getCategories()
    
    if (response.success && response.data) {
      categories.value = response.data
    }
  } catch (error) {
    console.error('Error loading categories:', error)
    // Fallback to hardcoded categories if API fails
    categories.value = [
      { id_category: 1, name: 'Minuman' },
      { id_category: 2, name: 'Makanan' },
      { id_category: 3, name: 'Snack' }
    ]
  }
}

const loadCustomers = async () => {
  try {
    // This would need to be implemented in the API
    // For now, we'll use placeholder data
    customers.value = [
      { id_customer: 1, name: 'John Doe', phone: '0812345678', email: 'john@example.com' },
      { id_customer: 2, name: 'Jane Smith', phone: '0812345679', email: 'jane@example.com' },
      { id_customer: 3, name: 'Bob Johnson', phone: '0812345680', email: 'bob@example.com' }
    ]
  } catch (error) {
    console.error('Error loading customers:', error)
  }
}

// Enhanced methods for UI
const addToCart = (product: Product) => {
  if (!canAddToCart(product)) {
    // Show toast notification
    if (product.stock <= 0) {
      console.warn('Stok tidak tersedia untuk produk:', product.name)
    } else if (!product.selling_price || product.selling_price <= 0) {
      console.warn('Harga tidak tersedia untuk produk:', product.name)
    }
    return
  }
  
  const success = addToCartComposable(product)
  if (success) {
    // Show success feedback if needed
  }
}

const canAddToCart = (product: Product): boolean => {
  return product.stock > 0 && product.selling_price > 0
}

const getStockColor = (stock: number) => {
  if (stock === 0) return 'error'
  if (stock < 10) return 'warning'
  return 'success'
}

const getStockBadgeClass = (stock: number) => {
  if (stock === 0) return 'stock-low'
  if (stock < 10) return 'stock-medium'
  return 'stock-high'
}

const getCartQuantity = (productId: number) => {
  const item = cartItems.value.find(item => item.id_product === productId)
  return item ? item.quantity : 0
}

const clearCartConfirm = () => {
  clearCartDialog.value = true
}

const confirmClearCart = () => {
  clearCartComposable()
  clearCartDialog.value = false
}

// Image helper functions
const handleImageError = (productId: number) => {
  console.error('🖼️ Image error for product ID:', productId)
  imageErrors.value[productId] = true
}

const getProductInitials = (productName: string): string => {
  return productName
    .split(' ')
    .slice(0, 2)
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
}

const openPaymentDialog = () => {
  paymentDialog.value = true
}

const handlePaymentComplete = (orderData: any) => {
  
  // Show success snackbar
  snackbar.value = {
    show: true,
    message: 'Pembayaran berhasil diproses! 🎉',
    color: 'success'
  }
  
  // Close payment dialog
  paymentDialog.value = false
  
  // Refresh products to update stock
  loadProducts()
}

const clearCart = () => {
  clearCartComposable()
}

const openCashDrawer = () => {
  // Open cash drawer dialog
  cashDrawerDialog.value = true
}

const viewTransactionHistory = () => {
  transactionHistoryDialog.value = true
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

// Lifecycle
onMounted(async () => {
  // Initialize current time
  currentTime.value = new Date().toLocaleTimeString('id-ID', { 
    hour: '2-digit', 
    minute: '2-digit'
  })
  
  // Load initial data
  await Promise.all([
    loadProducts(),
    loadCategories(),
    loadCustomers()
  ])
})
</script>

<style scoped>
/* Main Layout */
.pos-layout {
  display: flex;
  flex-direction: column;
  background: #f5f7fa;
  block-size: 100vh;
}

/* Header Styles */
.pos-header {
  flex-shrink: 0;
  background: white;
  border-block-end: 1px solid #e5e7eb;
  padding-block: 1rem;
  padding-inline: 2rem;
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block: 0;
  margin-inline: auto;
  max-inline-size: 1400px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.pos-title {
  margin: 0;
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1.2;
}

.header-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.header-meta {
  display: flex;
  align-items: center;
  color: #6b7280;
  font-size: 0.875rem;
  gap: 0.5rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

/* Main Content */
.pos-main {
  display: flex;
  overflow: hidden;
  flex: 1;
  gap: 1.5rem;
  inline-size: 100%;
  margin-block: 0;
  margin-inline: auto;
  max-inline-size: 1400px;
  padding-block: 1.5rem;
  padding-inline: 2rem;
}

/* Product Panel */
.product-panel {
  display: flex;
  flex: 1;
  flex-direction: column;
  border-radius: 12px;
  background: white;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 10%);
}

.filter-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-block-end: 1px solid #e5e7eb;
  gap: 1rem;
}

.search-section {
  flex: 1;
  max-inline-size: 400px;
}

.search-input :deep(.v-field) {
  border-radius: 8px;
  background-color: #f8fafc !important;
}

.search-input :deep(.v-field__input) {
  color: #334155 !important;
  font-weight: 500 !important;
}

.search-input :deep(.v-field__input input) {
  color: #334155 !important;
  font-weight: 500 !important;
}

.search-input :deep(.v-field__input input::placeholder) {
  color: #000 !important;
  opacity: 1 !important;
}

.search-input :deep(.v-field--focused) {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 10%) !important;
}

.search-input :deep(.v-icon) {
  color: #64748b !important;
}

.filter-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

/* Category Select Styling */
.category-select :deep(.v-field) {
  border-radius: 8px;
  background-color: #f8fafc !important;
}

.category-select :deep(.v-field__input) {
  color: #334155 !important;
  font-weight: 500 !important;
}

.category-select :deep(.v-field__input input) {
  color: #334155 !important;
  font-weight: 500 !important;
}

.category-select :deep(.v-field__input input::placeholder) {
  color: #94a3b8 !important;
  opacity: 1 !important;
}

.category-select :deep(.v-select__selection-text) {
  color: #334155 !important;
  font-weight: 500 !important;
}

.category-select :deep(.v-field--focused) {
  box-shadow: 0 0 0 2px rgba(176, 113, 36, 10%) !important;
}

.category-select :deep(.v-icon) {
  color: #64748b !important;
}

.category-select :deep(.v-list-item-title) {
  color: #334155 !important;
  font-weight: 500 !important;
}

.category-select :deep(.v-list-item) {
  background-color: #fff !important;
}

.category-select :deep(.v-list-item--active) {
  background-color: #f1f5f9 !important;
}

.products-container {
  display: flex;
  overflow: hidden;
  flex: 1;
  flex-direction: column;
}

.products-grid {
  display: grid;
  flex: 1;
  padding: 1.5rem;
  gap: 1rem;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  overflow-y: auto;
}

.product-item {
  position: relative;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.product-item:hover {
  border-color: #b07124;
  box-shadow: 0 4px 12px rgba(176, 113, 36, 15%);
  transform: translateY(-2px);
}

.product-item.out-of-stock {
  cursor: not-allowed;
  opacity: 0.6;
}

.product-image {
  position: relative;
  overflow: hidden;
  background: #f9fafb;
  block-size: 120px;
}

.product-image-content {
  overflow: hidden;
  border-radius: 8px;
  block-size: 120px !important;
  inline-size: 100% !important;
}

.product-image-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  block-size: 100%;
  inline-size: 100%;
}

.product-image img {
  block-size: 100%;
  inline-size: 100%;
  object-fit: cover;
}

/* Image Handling */
.product-image-content {
  position: relative;
  overflow: hidden;
  border-radius: 8px;
  background-color: #f5f5f5;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  block-size: 120px;
  inline-size: 100%;
}

.product-image-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(45deg, #f5f5f5 0%, #e0e0e0 100%);
  block-size: 100%;
  color: #757575;
  inline-size: 100%;
}

.product-image-initials {
  color: #4caf50;
  font-size: 24px;
  font-weight: bold;
  margin-block-end: 4px;
}

.stock-badge {
  position: absolute;
  border-radius: 4px;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  inset-block-start: 8px;
  inset-inline-end: 8px;
  padding-block: 0.25rem;
  padding-inline: 0.5rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 20%);
}

.stock-badge.stock-good {
  background: #10b981;
}

.stock-badge.stock-low {
  background: #f59e0b;
}

.stock-badge.stock-out {
  background: #ef4444;
}

.cart-indicator {
  position: absolute;
  padding: 0.25rem;
  border-radius: 4px;
  background: rgba(16, 185, 129, 90%);
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  inset-block-end: 8px;
  inset-inline: 8px;
  text-align: center;
}

/* Status Indicators */
.status-indicator {
  position: absolute;
  display: flex;
  align-items: center;
  border-radius: 4px;
  color: white;
  font-size: 0.625rem;
  font-weight: 600;
  gap: 0.25rem;
  inset-block-start: 8px;
  inset-inline-start: 8px;
  padding-block: 0.25rem;
  padding-inline: 0.5rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 20%);
}

.out-of-stock-indicator {
  background: rgba(239, 68, 68, 90%);
}

.no-price-indicator {
  background: rgba(245, 158, 11, 90%);
}

/* Disabled Product State */
.product-item.disabled {
  cursor: not-allowed;
  filter: grayscale(20%);
  opacity: 0.5;
}

.product-item.disabled:hover {
  border-color: #e5e7eb;
  box-shadow: none;
  transform: none;
}

.product-item.no-price .product-price {
  color: #ef4444 !important;
}

.price-missing {
  color: #ef4444 !important;
  font-style: italic;
  font-weight: 500 !important;
}

.product-info {
  padding: 1rem;
}

.product-name {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  color: #1f2937;
  font-size: 0.875rem;
  font-weight: 600;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  line-height: 1.3;
  margin-block: 0 0.25rem;
  margin-inline: 0;
}

.product-sku {
  color: #6b7280;
  font-size: 0.75rem;
  margin-block: 0 0.75rem;
  margin-inline: 0;
}

.product-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.product-price {
  color: #b07124 !important;
  font-size: 0.9375rem;
  font-weight: 700;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 10%);
}

/* Cart Panel */
.cart-panel {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  border-radius: 12px;
  background: white;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 10%);
  inline-size: 380px;
}

.cart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-block-end: 1px solid #e5e7eb;
}

.cart-title {
  display: flex;
  align-items: center;
  color: #1f2937;
  font-size: 1rem;
  font-weight: 600;
  gap: 0.5rem;
}

.cart-content {
  display: flex;
  overflow: hidden;
  flex: 1;
  flex-direction: column;
}

.cart-items {
  flex: 1;
  padding: 0.5rem;
  overflow-y: auto;
}

.cart-item {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
  margin-block-end: 0.5rem;
  transition: all 0.2s ease;
}

.cart-item:hover {
  background: #f3f4f6;
}

.item-info {
  margin-block-end: 0.75rem;
}

.item-name {
  color: #000 !important;
  font-size: 0.9375rem;
  font-weight: 700;
  margin-block: 0 0.25rem;
  margin-inline: 0;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 10%);
}

.item-price {
  margin: 0;
  color: #000 !important;
  font-size: 0.8125rem;
  font-weight: 500;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 10%);
}

.item-controls {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.quantity-controls {
  display: flex;
  align-items: center;
  padding: 0.25rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: white;
  gap: 0.5rem;
}

.quantity {
  color: #000 !important;
  font-weight: 600;
  min-inline-size: 2rem;
  text-align: center;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 10%);
}

.item-total {
  color: #b07124 !important;
  font-size: 0.9375rem;
  font-weight: 700;
  min-inline-size: 80px;
  text-align: end;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 10%);
}

.empty-cart {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding-block: 3rem;
  padding-inline: 1.5rem;
  text-align: center;
}

.empty-cart-text {
  color: #6b7280;
  font-size: 1rem;
  font-weight: 600;
  margin-block: 1rem 0.5rem;
  margin-inline: 0;
}

.cart-summary {
  padding: 1.5rem;
  border-radius: 0 0 12px 12px;
  background: #f9fafb;
  border-block-start: 1px solid #e5e7eb;
}

.summary-details {
  margin-block: 1rem;
  margin-inline: 0;
}

.summary-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-radius: 6px;
  background: #f8f9fa;
  color: #000;
  font-size: 0.9375rem;
  font-weight: 600;
  margin-block-end: 0.75rem;
  padding-block: 0.5rem;
  padding-inline: 0.75rem;
}

.summary-row.discount {
  border: 1px solid #a7f3d0;
  background: #f0f9f5;
  color: #059669 !important;
  font-weight: 700;
}

.summary-row.total {
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
  color: #000 !important;
  font-size: 1.125rem;
  font-weight: 800;
  margin-block: 1rem;
}

.customer-select {
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  margin-block-end: 1rem;
}

.checkout-btn {
  padding: 1rem;
  border-radius: 8px;
  background: linear-gradient(135deg, #b07124 0%, #8d7053 100%) !important;
  box-shadow: 0 4px 12px rgba(176, 113, 36, 30%);
  font-size: 1rem;
  font-weight: 700;
  transition: all 0.2s ease;
}

.checkout-btn:hover {
  background: linear-gradient(135deg, #a0651f 0%, #7d6349 100%) !important;
  box-shadow: 0 8px 25px rgba(176, 113, 36, 40%);
  transform: translateY(-1px);
}

/* Empty State */
.empty-state {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding-block: 4rem;
  padding-inline: 2rem;
  text-align: center;
}

.empty-title {
  color: #6b7280;
  font-size: 1.25rem;
  font-weight: 600;
  margin-block: 1rem 0.5rem;
  margin-inline: 0;
}

.empty-text {
  color: #9ca3af;
  line-height: 1.5;
  margin-block-end: 1.5rem;
  max-inline-size: 300px;
}

/* Responsive */
@media (max-width: 1200px) {
  .pos-main {
    flex-direction: column;
    padding: 1rem;
    gap: 1rem;
  }

  .cart-panel {
    inline-size: 100%;
    max-block-size: 400px;
  }

  .products-grid {
    padding: 1rem;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  }
}

@media (max-width: 768px) {
  .pos-header {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .header-actions {
    justify-content: center;
  }

  .filter-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .filter-section {
    flex-wrap: wrap;
  }

  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  }
}

.product-card:hover {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 10%) !important;
  transform: translateY(-2px);
}

.product-card--out-of-stock {
  cursor: not-allowed;
  opacity: 0.6;
}

.product-image-container {
  position: relative;
  overflow: hidden;
}

.product-image {
  transition: transform 0.3s ease;
}

.product-card:hover .product-image {
  transform: scale(1.05);
}

.stock-badge {
  position: absolute;
  z-index: 2;
  inset-block-start: 8px;
  inset-inline-end: 8px;
}

.station-badges {
  position: absolute;
  z-index: 2;
  inset-block-start: 8px;
  inset-inline-start: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.station-badge {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 2px 6px;
  font-size: 10px;
  font-weight: 500;
  border-radius: 12px;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.kitchen-badge {
  background: rgba(76, 175, 80, 0.9);
}

.bar-badge {
  background: rgba(255, 152, 0, 0.9);
}

.out-of-stock-overlay {
  position: absolute;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 70%);
  inset: 0;
}

.add-btn {
  opacity: 0;
  transform: scale(0.8);
  transition: all 0.3s ease;
}

.product-card:hover .add-btn {
  opacity: 1;
  transform: scale(1);
}

/* Cart Styles */
.cart-container {
  border-radius: 12px !important;
}

.cart-content {
  min-block-size: 400px;
}

.cart-items {
  max-block-size: 400px;
  overflow-y: auto;
}

.cart-item {
  border-radius: 8px !important;
  transition: all 0.2s ease;
}

.cart-item:hover {
  transform: translateX(2px);
}

.quantity-input :deep(.v-field__input) {
  text-align: center;
}

.cart-summary {
  border-radius: 0 0 12px 12px;
}

.payment-btn {
  border-radius: 8px !important;
  box-shadow: 0 4px 12px rgba(76, 175, 80, 30%) !important;
  transition: all 0.3s ease;
}

.payment-btn:hover {
  box-shadow: 0 8px 25px rgba(76, 175, 80, 40%) !important;
  transform: translateY(-1px);
}

/* Search Field */
.search-field :deep(.v-field) {
  border-radius: 8px;
}

/* Responsive */
@media (max-width: 1280px) {
  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  }
}

@media (max-width: 960px) {
  .pos-container {
    padding: 0.5rem;
  }

  .product-grid {
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  }
}

/* Animation */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.product-card {
  animation: fadeInUp 0.5s ease forwards;
}

.cart-item {
  animation: fadeInUp 0.3s ease forwards;
}

/* Fix text visibility in form fields */
.customer-select :deep(.v-field__input) {
  color: #000 !important;
  font-weight: 600 !important;
}

.customer-select :deep(.v-label) {
  color: #374151 !important;
  font-weight: 600 !important;
}

.customer-select :deep(.v-field__outline) {
  border-color: #d1d5db !important;
}

.customer-select :deep(.v-field--active .v-field__outline) {
  border-color: #3b82f6 !important;
}

.customer-select :deep(.v-icon) {
  color: #6b7280 !important;
}

/* Global dropdown fix */
.v-overlay .v-list-item-title {
  color: #1e293b !important;
  font-weight: 600 !important;
}

.v-overlay .v-list-item {
  background-color: #fff !important;
}

.customer-select :deep(.v-list-item-title) {
  color: #000 !important;
  font-weight: 600 !important;
}
</style>
