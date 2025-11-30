import { PosApi } from '@/utils/api/PosApi'
import { computed, readonly, ref, watch } from 'vue'

export interface Product {
  id_product?: number
  id_package?: number
  item_type?: 'product' | 'package'
  name: string
  sku: string
  barcode?: string
  selling_price?: number
  package_price?: number
  stock?: number
  image?: string
  id_category?: number
  id_variant?: number
  available_in_kitchen?: boolean
  available_in_bar?: boolean
  is_disabled?: boolean
  stock_status?: string
  stock_info?: {
    current_stock: number
    available_stock: number
    is_available: boolean
  }
  unit_price?: number
  total_price?: number
}

export interface Customer {
  id_customer: number
  name: string
  phone: string
  email?: string
}

export interface CartItem extends Product {
  quantity: number
}

export interface OrderSummary {
  subtotal: number
  discountAmount: number
  discountType: 'amount' | 'percentage'
  taxAmount: number
  totalAmount: number
}

const usePOS = () => {
  // Reactive state
  const cartItems = ref<CartItem[]>([])
  const selectedCustomer = ref<number | null>(null)
  const discountAmount = ref(0)
  const discountType = ref<'amount' | 'percentage'>('amount')
  const taxRate = ref(0) // Tax rate in percentage
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed properties
  const subtotal = computed(() => {
    return cartItems.value.reduce((total, item) => {
      const price = item.item_type === 'package'
        ? (item.package_price || item.unit_price || 0)
        : (item.selling_price || item.unit_price || 0)
      return total + (price * item.quantity)
    }, 0)
  })

  const discountValue = computed(() => {
    if (!discountAmount.value) return 0

    if (discountType.value === 'percentage') {
      return (subtotal.value * discountAmount.value) / 100
    }

    return discountAmount.value
  })

  const taxValue = computed(() => {
    const afterDiscount = subtotal.value - discountValue.value
    return (afterDiscount * taxRate.value) / 100
  })

  const totalAmount = computed(() => {
    return Math.max(0, subtotal.value - discountValue.value + taxValue.value)
  })

  const cartCount = computed(() => {
    return cartItems.value.reduce((total, item) => total + item.quantity, 0)
  })

  const isEmpty = computed(() => cartItems.value.length === 0)

  const orderSummary = computed((): OrderSummary => ({
    subtotal: subtotal.value,
    discountAmount: discountValue.value,
    discountType: discountType.value,
    taxAmount: taxValue.value,
    totalAmount: totalAmount.value
  }))

  // Cart operations
  const addToCart = (product: Product, quantity: number = 1) => {
    try {
      // For packages, use id_package; for products, use id_product
      const itemId = product.item_type === 'package' ? product.id_package : product.id_product
      const existingItem = cartItems.value.find(item => {
        if (product.item_type === 'package') {
          return item.id_package === itemId && item.item_type === 'package'
        }
        return item.id_product === itemId && item.item_type !== 'package'
      })

      // Get available stock - for packages, use stock directly
      const availableStock = product.item_type === 'package'
        ? (product.stock || 0)
        : (product.stock || 0)

      if (existingItem) {
        const newQuantity = existingItem.quantity + quantity
        if (newQuantity <= availableStock) {
          existingItem.quantity = newQuantity
        } else {
          throw new Error(`Stok tidak mencukupi. Tersedia: ${availableStock}`)
        }
      } else {
        if (quantity <= availableStock) {
          cartItems.value.push({
            ...product,
            quantity,
            // Ensure proper stock value for display
            stock: availableStock
          })
        } else {
          throw new Error(`Stok tidak mencukupi. Tersedia: ${availableStock}`)
        }
      }

      error.value = null
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Gagal menambahkan ke keranjang'
      return false
    }
  }

  const removeFromCart = (productId: number, isPackage: boolean = false) => {
    const index = cartItems.value.findIndex(item => {
      if (isPackage) {
        return item.id_package === productId && item.item_type === 'package'
      }
      return item.id_product === productId && item.item_type !== 'package'
    })
    if (index > -1) {
      cartItems.value.splice(index, 1)
    }
  }

  const updateQuantity = (productId: number, newQuantity: number, isPackage: boolean = false) => {
    const item = cartItems.value.find(item => {
      if (isPackage) {
        return item.id_package === productId && item.item_type === 'package'
      }
      return item.id_product === productId && item.item_type !== 'package'
    })

    if (item) {
      if (newQuantity <= 0) {
        removeFromCart(productId, isPackage)
      } else {
        const availableStock = item.item_type === 'package'
          ? (item.stock_info?.available_stock || item.stock || 0)
          : (item.stock || 0)

        if (newQuantity <= availableStock) {
          item.quantity = newQuantity
        } else {
          error.value = `Stok tidak mencukupi. Tersedia: ${availableStock}`
          return false
        }
      }
    }
    return true
  }

  const clearCart = () => {
    cartItems.value = []
    selectedCustomer.value = null
    discountAmount.value = 0
    discountType.value = 'amount'
    error.value = null
  }

  // Discount operations
  const applyDiscount = (amount: number, type: 'amount' | 'percentage' = 'amount') => {
    if (type === 'percentage' && amount > 100) {
      error.value = 'Diskon persentase tidak boleh lebih dari 100%'
      return false
    }

    if (type === 'amount' && amount > subtotal.value) {
      error.value = 'Diskon tidak boleh lebih dari subtotal'
      return false
    }

    discountAmount.value = amount
    discountType.value = type
    error.value = null
    return true
  }

  const removeDiscount = () => {
    discountAmount.value = 0
    discountType.value = 'amount'
  }

  // Customer operations
  const setCustomer = (customerId: number | null) => {
    selectedCustomer.value = customerId
  }

  // Order operations
  const createOrder = async (paymentData: {
    payment_method: 'cash' | 'card' | 'digital_wallet' | 'bank_transfer' | 'qris' | 'other'
    amount: number
    reference_number?: string
    notes?: string
  }, orderType: 'dine_in' | 'takeaway' | 'delivery' = 'takeaway') => {
    try {
      loading.value = true
      error.value = null

      if (isEmpty.value) {
        throw new Error('Keranjang kosong')
      }

      // First create the order
      const orderData = {
        order_type: orderType,
        id_customer: selectedCustomer.value || undefined,
        guest_count: 1,
        notes: paymentData.notes
      }

      const orderResponse = await PosApi.createOrder(orderData)
      const order = orderResponse.data

      // Add items to the order
      for (const item of cartItems.value) {
        const itemData: any = {
          item_type: item.item_type || 'product',
          quantity: item.quantity,
          notes: ''
        }

        if (item.item_type === 'package') {
          itemData.id_package = item.id_package
          itemData.unit_price = item.package_price || item.unit_price
          itemData.total_price = (item.package_price || item.unit_price || 0) * item.quantity
        } else {
          itemData.id_product = item.id_product
          itemData.id_variant = item.id_variant || undefined
          itemData.unit_price = item.selling_price || item.unit_price
          itemData.total_price = (item.selling_price || item.unit_price || 0) * item.quantity
        }

        await PosApi.addItem(order.id_order, itemData)
      }

      // Apply discount if any
      if (discountValue.value > 0) {
        await PosApi.applyDiscount(order.id_order, {
          discount_type: discountType.value === 'percentage' ? 'percentage' : 'fixed',
          discount_value: discountType.value === 'percentage' ? discountAmount.value : discountValue.value
        })
      }

      // Process payment
      await PosApi.processPayment(order.id_order, {
        payment_method: paymentData.payment_method,
        amount: paymentData.amount,
        reference_number: paymentData.reference_number,
        notes: paymentData.notes
      })

      // Clear cart after successful order
      clearCart()

      return order
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Gagal membuat pesanan'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Validation
  const validateCart = () => {
    const errors: string[] = []

    if (isEmpty.value) {
      errors.push('Keranjang kosong')
    }

    cartItems.value.forEach(item => {
      if (item.quantity > item.stock) {
        errors.push(`Stok ${item.name} tidak mencukupi`)
      }
    })

    if (discountValue.value > subtotal.value) {
      errors.push('Diskon tidak boleh lebih dari subtotal')
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }

  // Utility functions
  const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount)
  }

  const getCartItemById = (productId: number): CartItem | undefined => {
    return cartItems.value.find(item => item.id_product === productId)
  }

  const getItemQuantity = (productId: number): number => {
    const item = getCartItemById(productId)
    return item ? item.quantity : 0
  }

  const canAddToCart = (product: Product, quantity: number = 1): boolean => {
    const existingQuantity = getItemQuantity(product.id_product)
    return (existingQuantity + quantity) <= product.stock
  }

  // Watch for validation
  watch([cartItems, discountAmount, discountType], () => {
    const validation = validateCart()
    if (!validation.isValid && validation.errors.length > 0) {
      error.value = validation.errors[0]
    } else {
      error.value = null
    }
  }, { deep: true })

  // Watch for discount validation
  watch([discountAmount, discountType, subtotal], () => {
    if (discountType.value === 'percentage' && discountAmount.value > 100) {
      discountAmount.value = 100
    }
    if (discountValue.value > subtotal.value) {
      if (discountType.value === 'amount') {
        discountAmount.value = subtotal.value
      } else {
        discountAmount.value = 100
      }
    }
  })

  // Station availability helpers
  const isAvailableInStation = (product: Product, station: 'kitchen' | 'bar'): boolean => {
    if (station === 'kitchen') {
      return product.available_in_kitchen !== false
    } else if (station === 'bar') {
      return product.available_in_bar !== false
    }
    return false
  }

  const isAvailableInBothStations = (product: Product): boolean => {
    return (product.available_in_kitchen !== false) && (product.available_in_bar !== false)
  }

  const getAvailableStations = (product: Product): ('kitchen' | 'bar')[] => {
    const stations: ('kitchen' | 'bar')[] = []
    if (product.available_in_kitchen !== false) stations.push('kitchen')
    if (product.available_in_bar !== false) stations.push('bar')
    return stations
  }

  const canProcessOrderInStation = (cartItems: CartItem[], station: 'kitchen' | 'bar'): boolean => {
    return cartItems.every(item => isAvailableInStation(item, station))
  }

  const getStationRequirements = (cartItems: CartItem[]): {
    kitchen: CartItem[]
    bar: CartItem[]
    both: CartItem[]
  } => {
    const kitchen: CartItem[] = []
    const bar: CartItem[] = []
    const both: CartItem[] = []

    cartItems.forEach(item => {
      const stations = getAvailableStations(item)
      if (stations.length === 1) {
        if (stations[0] === 'kitchen') kitchen.push(item)
        else if (stations[0] === 'bar') bar.push(item)
      } else if (stations.length === 2) {
        both.push(item)
      }
    })

    return { kitchen, bar, both }
  }

  return {
    // State
    cartItems: readonly(cartItems),
    selectedCustomer,
    discountAmount,
    discountType,
    taxRate,
    loading: readonly(loading),
    error: readonly(error),

    // Computed
    subtotal,
    discountValue,
    taxValue,
    totalAmount,
    cartCount,
    isEmpty,
    orderSummary,

    // Methods
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    applyDiscount,
    removeDiscount,
    setCustomer,
    createOrder,
    validateCart,
    formatCurrency,
    getCartItemById,
    getItemQuantity,
    canAddToCart,

    // Station helpers
    isAvailableInStation,
    isAvailableInBothStations,
    getAvailableStations,
    canProcessOrderInStation,
    getStationRequirements
  }
}

export default usePOS
