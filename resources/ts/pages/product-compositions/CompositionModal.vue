<template>
  <VDialog
    :model-value="show"
    max-width="800px"
    persistent
    @update:model-value="!$event && $emit('close')"
  >
    <VCard>
              <form @submit.prevent="handleSubmit">
                <div>
                  <div class="flex items-center">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100">
                      <VIcon icon="tabler-assembly" class="h-6 w-6 text-indigo-600" />
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                      <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                        {{ isEdit ? 'Edit Product Composition' : 'Create Product Composition' }}
                      </DialogTitle>
                      <p class="text-sm text-gray-500">
                        {{ isEdit ? 'Update the composition details.' : 'Define which base products are needed for this product.' }}
                      </p>
                    </div>
                  </div>

                  <div class="mt-6 space-y-6">
                    <!-- Product Selection -->
                    <div>
                      <label for="product_id" class="block text-sm font-medium text-gray-700">
                        Product *
                      </label>
                      <select
                        id="product_id"
                        v-model="form.product_id"
                        required
                        :disabled="isEdit"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100"
                        :class="{ 'border-red-300': errors.product_id }"
                      >
                        <option value="">Select Product</option>
                        <option v-for="product in products" :key="product.id_product" :value="product.id_product">
                          {{ product.name }}
                        </option>
                      </select>
                      <p v-if="errors.product_id" class="mt-1 text-sm text-red-600">{{ errors.product_id[0] }}</p>
                      <p v-if="isEdit" class="mt-1 text-sm text-gray-500">Product cannot be changed when editing</p>
                    </div>

                    <!-- Base Product Selection -->
                    <div>
                      <label for="base_product_id" class="block text-sm font-medium text-gray-700">
                        Base Product *
                      </label>
                      <select
                        id="base_product_id"
                        v-model="form.base_product_id"
                        required
                        :disabled="isEdit"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100"
                        :class="{ 'border-red-300': errors.base_product_id }"
                        @change="updateSelectedBaseProduct"
                      >
                        <option value="">Select Base Product</option>
                        <option v-for="baseProduct in baseProducts" :key="baseProduct.id_base_product" :value="baseProduct.id_base_product">
                          {{ baseProduct.name }} ({{ baseProduct.current_stock }} {{ baseProduct.unit }} available)
                        </option>
                      </select>
                      <p v-if="errors.base_product_id" class="mt-1 text-sm text-red-600">{{ errors.base_product_id[0] }}</p>
                      <p v-if="isEdit" class="mt-1 text-sm text-gray-500">Base product cannot be changed when editing</p>
                    </div>

                    <!-- Base Product Info -->
                    <div v-if="selectedBaseProduct" class="bg-gray-50 p-4 rounded-md">
                      <div class="flex items-center space-x-3">
                        <img 
                          v-if="selectedBaseProduct.image_url" 
                          :src="selectedBaseProduct.image_url" 
                          :alt="selectedBaseProduct.name"
                          class="h-12 w-12 rounded-lg object-cover"
                        >
                        <div v-else class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center">
                          <VIcon icon="tabler-package" class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="flex-1">
                          <h4 class="font-medium text-gray-900">{{ selectedBaseProduct.name }}</h4>
                          <p class="text-sm text-gray-500">SKU: {{ selectedBaseProduct.sku }}</p>
                          <p class="text-sm text-gray-500">Cost: {{ selectedBaseProduct.formatted_cost }} per {{ selectedBaseProduct.unit }}</p>
                          <p class="text-sm text-gray-500">Available: {{ formatNumber(selectedBaseProduct.current_stock) }} {{ selectedBaseProduct.unit }}</p>
                        </div>
                      </div>
                    </div>

                    <!-- Quantity -->
                    <div>
                      <label for="quantity" class="block text-sm font-medium text-gray-700">
                        Quantity Required *
                      </label>
                      <div class="mt-1 relative rounded-md shadow-sm">
                        <input
                          id="quantity"
                          v-model="form.quantity"
                          type="number"
                          step="0.001"
                          min="0"
                          required
                          class="block w-full pr-16 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                          :class="{ 'border-red-300': errors.quantity }"
                          @input="calculateCost"
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                          <span class="text-gray-500 sm:text-sm">{{ selectedBaseProduct?.unit || 'unit' }}</span>
                        </div>
                      </div>
                      <p v-if="errors.quantity" class="mt-1 text-sm text-red-600">{{ errors.quantity[0] }}</p>
                      <p class="mt-1 text-sm text-gray-500">
                        Quantity of base product needed to produce 1 unit of the selected product
                      </p>
                    </div>

                    <!-- Cost Information -->
                    <div v-if="form.quantity && selectedBaseProduct" class="bg-blue-50 p-4 rounded-md">
                      <h4 class="text-sm font-medium text-blue-900 mb-2">Cost Calculation</h4>
                      <div class="text-sm text-blue-800 space-y-1">
                        <p>Cost per unit: {{ selectedBaseProduct.formatted_cost }}</p>
                        <p>Quantity needed: {{ formatNumber(form.quantity) }} {{ selectedBaseProduct.unit }}</p>
                        <p class="font-medium border-t border-blue-200 pt-1">
                          Total cost per product: {{ formatCurrency(calculatedCost) }}
                        </p>
                      </div>
                    </div>

                    <!-- Production Calculation -->
                    <div v-if="form.quantity && selectedBaseProduct" class="bg-green-50 p-4 rounded-md">
                      <h4 class="text-sm font-medium text-green-900 mb-2">Production Capacity</h4>
                      <div class="text-sm text-green-800 space-y-1">
                        <p>Available stock: {{ formatNumber(selectedBaseProduct.current_stock) }} {{ selectedBaseProduct.unit }}</p>
                        <p>Required per product: {{ formatNumber(form.quantity) }} {{ selectedBaseProduct.unit }}</p>
                        <p class="font-medium border-t border-green-200 pt-1">
                          Can produce: {{ formatNumber(maxProducibleQuantity) }} products
                        </p>
                      </div>
                    </div>

                    <!-- Notes -->
                    <div>
                      <label for="notes" class="block text-sm font-medium text-gray-700">
                        Notes
                      </label>
                      <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Additional notes about this composition..."
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        :class="{ 'border-red-300': errors.notes }"
                      ></textarea>
                      <p v-if="errors.notes" class="mt-1 text-sm text-red-600">{{ errors.notes[0] }}</p>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center">
                      <input
                        id="is_active"
                        v-model="form.is_active"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      >
                      <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Active
                      </label>
                    </div>
                  </div>
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3">
                  <button
                    type="button"
                    @click="$emit('close')"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="loading || !canSubmit"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ loading ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}
                  </button>
                </div>
              </form>
    </VCard>
  </VDialog>
</template>

<script>
import { ref, reactive, computed, watch } from 'vue'
import { useBaseProductStore } from '@/stores/baseProduct'

export default {
  name: 'CompositionModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    composition: {
      type: Object,
      default: null
    },
    products: {
      type: Array,
      default: () => []
    },
    baseProducts: {
      type: Array,
      default: () => []
    }
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const baseProductStore = useBaseProductStore()
    const loading = ref(false)
    const errors = ref({})
    const selectedBaseProduct = ref(null)
    
    const form = reactive({
      product_id: '',
      base_product_id: '',
      quantity: '',
      notes: '',
      is_active: true
    })

    const isEdit = computed(() => !!props.composition)

    const canSubmit = computed(() => {
      return form.product_id && form.base_product_id && form.quantity > 0
    })

    const calculatedCost = computed(() => {
      if (!selectedBaseProduct.value || !form.quantity) return 0
      return parseFloat(selectedBaseProduct.value.cost_per_unit) * parseFloat(form.quantity)
    })

    const maxProducibleQuantity = computed(() => {
      if (!selectedBaseProduct.value || !form.quantity) return 0
      const availableStock = parseFloat(selectedBaseProduct.value.current_stock) || 0
      const requiredQuantity = parseFloat(form.quantity) || 0
      return requiredQuantity > 0 ? Math.floor(availableStock / requiredQuantity) : 0
    })

    // Watch for prop changes
    watch(() => props.composition, (newValue) => {
      if (newValue) {
        // Populate form with existing data
        Object.keys(form).forEach(key => {
          form[key] = newValue[key] || (key === 'is_active' ? true : '')
        })
        updateSelectedBaseProduct()
      } else {
        // Reset form for create mode
        resetForm()
      }
      errors.value = {}
    }, { immediate: true })

    const resetForm = () => {
      Object.keys(form).forEach(key => {
        if (key === 'is_active') {
          form[key] = true
        } else {
          form[key] = ''
        }
      })
      selectedBaseProduct.value = null
      errors.value = {}
    }

    const updateSelectedBaseProduct = () => {
      const baseProductId = form.base_product_id
      if (baseProductId) {
        selectedBaseProduct.value = props.baseProducts.find(bp => bp.id_base_product == baseProductId)
      } else {
        selectedBaseProduct.value = null
      }
    }

    const calculateCost = () => {
      // This method is called when quantity changes to trigger reactivity
      // The actual calculation is done in the computed property
    }

    const formatNumber = (number) => {
      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3
      }).format(number || 0)
    }

    const formatCurrency = (number) => {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(number || 0)
    }

    const handleSubmit = async () => {
      loading.value = true
      errors.value = {}

      try {
        const payload = {
          product_id: form.product_id,
          base_product_id: form.base_product_id,
          quantity: parseFloat(form.quantity),
          notes: form.notes || null,
          is_active: form.is_active
        }

        let response
        if (isEdit.value) {
          response = await baseProductStore.updateComposition(props.composition.id_composition, payload)
        } else {
          response = await baseProductStore.createComposition(payload)
        }

        emit('saved', response.data)
      } catch (error) {
        console.error('Error saving composition:', error)
        if (error.response?.data?.errors) {
          errors.value = error.response.data.errors
        } else {
          errors.value = { general: [error.response?.data?.message || 'An error occurred'] }
        }
      } finally {
        loading.value = false
      }
    }

    return {
      loading,
      errors,
      selectedBaseProduct,
      form,
      isEdit,
      canSubmit,
      calculatedCost,
      maxProducibleQuantity,
      updateSelectedBaseProduct,
      calculateCost,
      formatNumber,
      formatCurrency,
      handleSubmit,
      resetForm
    }
  }
}
</script>
