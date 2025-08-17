import { PosApi } from '@/utils/api/PosApi'
import { computed, readonly, ref, watch } from 'vue'

export interface Product {
  id_product: number
  name: string
  sku: string
  barcode?: string
  selling_price: number
  stock: number
  image?: string
  id_category?: number
  id_variant?: number
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
      return total + (item.selling_price * item.quantity)
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
      const existingItem = cartItems.value.find(item => item.id_product === product.id_product)
      
      if (existingItem) {
        const newQuantity = existingItem.quantity + quantity
        if (newQuantity <= product.stock) {
          existingItem.quantity = newQuantity
        } else {
          throw new Error(`Stok tidak mencukupi. Tersedia: ${product.stock}`)
        }
      } else {
        if (quantity <= product.stock) {
          cartItems.value.push({
            ...product,
            quantity
          })
        } else {
          throw new Error(`Stok tidak mencukupi. Tersedia: ${product.stock}`)
        }
      }
      
      error.value = null
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Gagal menambahkan ke keranjang'
      return false
    }
  }

  const removeFromCart = (productId: number) => {
    const index = cartItems.value.findIndex(item => item.id_product === productId)
    if (index > -1) {
      cartItems.value.splice(index, 1)
    }
  }

  const updateQuantity = (productId: number, newQuantity: number) => {
    const item = cartItems.value.find(item => item.id_product === productId)
    if (item) {
      if (newQuantity <= 0) {
        removeFromCart(productId)
      } else if (newQuantity <= item.stock) {
        item.quantity = newQuantity
      } else {
        error.value = `Stok tidak mencukupi. Tersedia: ${item.stock}`
        return false
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
        await PosApi.addItem(order.id_order, {
          id_product: item.id_product,
          id_variant: item.id_variant || undefined,
          quantity: item.quantity,
          price: item.selling_price,
          notes: ''
        })
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
    canAddToCart
  }
}

export default usePOS
