<template>
  <VDialog
    :model-value="show"
    max-width="800px"
    persistent
    @update:model-value="!$event && $emit('close')"
  >
    <VCard>
      <VCardTitle class="d-flex align-center gap-3 pa-6">
        <VAvatar color="primary" variant="tonal">
          <VIcon icon="mdi-package-variant" />
        </VAvatar>
        <div>
          <h3 class="text-h6 font-weight-bold">
            {{ isEdit ? 'Edit Base Product' : 'Create Base Product' }}
          </h3>
          <p class="text-body-2 text-medium-emphasis mb-0">
            {{ isEdit ? 'Update the base product information.' : 'Add a new base product for compositions.' }}
          </p>
        </div>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-6">
        <VForm @submit.prevent="handleSubmit">
          <VRow>
            <!-- Basic Information -->
            <VCol cols="12">
              <h4 class="text-subtitle-1 font-weight-medium mb-4">Basic Information</h4>
            </VCol>

            <!-- Name -->
            <VCol cols="12">
              <VTextField
                v-model="form.name"
                label="Name"
                placeholder="Enter product name"
                required
                :error-messages="errors.name"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <!-- SKU and Category -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="form.sku"
                label="SKU"
                placeholder="Auto-generated if empty"
                :error-messages="errors.sku"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VSelect
                v-model="form.category_id"
                :items="categoryItems"
                label="Category"
                placeholder="Select Category"
                :error-messages="errors.category_id"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <!-- Description -->
            <VCol cols="12">
              <VTextarea
                v-model="form.description"
                label="Description"
                placeholder="Enter product description"
                :error-messages="errors.description"
                variant="outlined"
                density="comfortable"
                rows="3"
              />
            </VCol>

            <!-- Unit, Cost, and Min Stock -->
            <VCol cols="12">
              <h4 class="text-subtitle-1 font-weight-medium mb-4">Pricing & Stock</h4>
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="form.unit"
                label="Unit"
                placeholder="e.g., kg, pcs, liter"
                :error-messages="errors.unit"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="form.cost_per_unit"
                label="Cost per Unit"
                placeholder="0"
                type="number"
                step="0.01"
                min="0"
                :error-messages="errors.cost_per_unit"
                variant="outlined"
                density="comfortable"
                prefix="Rp"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="form.min_stock"
                label="Min Stock"
                placeholder="0"
                type="number"
                step="0.001"
                min="0"
                :error-messages="errors.min_stock"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <!-- Initial Stock (only for new products) -->
            <VCol v-if="!isEdit" cols="12">
              <VTextField
                v-model="form.initial_stock"
                label="Initial Stock"
                placeholder="0"
                type="number"
                step="0.001"
                min="0"
                :error-messages="errors.initial_stock"
                variant="outlined"
                density="comfortable"
                hint="Enter initial stock quantity for this base product"
                persistent-hint
              />
            </VCol>

            <!-- Image Upload -->
            <VCol cols="12">
              <h4 class="text-subtitle-1 font-weight-medium mb-4">Product Image</h4>
              <VCard
                variant="outlined"
                class="d-flex flex-column align-center justify-center pa-6"
                style="min-height: 200px; border-style: dashed;"
              >
                <!-- Current Image Preview -->
                <div v-if="imagePreview" class="text-center">
                  <VAvatar size="120" rounded="lg" class="mb-4">
                    <VImg :src="imagePreview" alt="Preview" />
                  </VAvatar>
                  <VBtn
                    color="error"
                    variant="text"
                    size="small"
                    @click="removeImage"
                  >
                    Remove Image
                  </VBtn>
                </div>
                
                <!-- Upload Area -->
                <div v-else class="text-center">
                  <VIcon icon="mdi-camera-plus" size="48" class="text-medium-emphasis mb-4" />
                  <p class="text-body-1 mb-2">{{ imagePreview ? 'Change image' : 'Upload a file' }}</p>
                  <p class="text-body-2 text-medium-emphasis">PNG, JPG, GIF up to 2MB</p>
                </div>
                
                <VBtn
                  color="primary"
                  variant="tonal"
                  class="mt-4"
                  @click="$refs.imageInput?.click()"
                >
                  {{ imagePreview ? 'Change Image' : 'Choose File' }}
                </VBtn>
                
                <input
                  ref="imageInput"
                  type="file"
                  accept="image/*"
                  style="display: none"
                  @change="handleImageUpload"
                >
              </VCard>
              <div v-if="errors.image" class="text-error text-body-2 mt-2">{{ errors.image[0] }}</div>
            </VCol>

            <!-- Status -->
            <VCol cols="12">
              <VCheckbox
                v-model="form.is_active"
                label="Active"
                :error-messages="errors.is_active"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-6">
        <VSpacer />
        <VBtn
          variant="outlined"
          @click="$emit('close')"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          @click="handleSubmit"
        >
          {{ isEdit ? 'Update' : 'Create' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script>
import { ref, reactive, computed, watch } from 'vue'
import { useBaseProductStore } from '@/stores/baseProduct'

export default {
  name: 'BaseProductModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    baseProduct: {
      type: Object,
      default: null
    },
    categories: {
      type: Array,
      default: () => []
    }
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const baseProductStore = useBaseProductStore()
    const loading = ref(false)
    const imageInput = ref(null)
    const imagePreview = ref(null)
    const errors = ref({})
    
    const form = reactive({
      name: '',
      sku: '',
      description: '',
      category_id: '',
      unit: '',
      cost_per_unit: '',
      min_stock: '',
      initial_stock: 0,
      image: null,
      is_active: true
    })

    const isEdit = computed(() => !!props.baseProduct)

    const categoryItems = computed(() => {
      if (!props.categories || !Array.isArray(props.categories)) {
        return []
      }
      const items = props.categories.map(category => ({
        title: category.name,
        value: category.id_category
      }))
      return items
    })

    const resetForm = () => {
      Object.keys(form).forEach(key => {
        if (key === 'is_active') {
          form[key] = true
        } else if (key === 'initial_stock') {
          form[key] = 0
        } else {
          form[key] = ''
        }
      })
      imagePreview.value = null
      errors.value = {}
    }

    // Watch for prop changes
    watch(() => props.baseProduct, (newValue) => {
      if (newValue) {
        // Populate form with existing data
        Object.keys(form).forEach(key => {
          if (key === 'image') {
            form[key] = null
            imagePreview.value = newValue.image_url || null
          } else if (key === 'initial_stock') {
            // Don't populate initial_stock for edit mode
            form[key] = 0
          } else {
            form[key] = newValue[key] || ''
          }
        })
      } else {
        // Reset form for create mode
        resetForm()
      }
      errors.value = {}
    }, { immediate: true })

    const handleImageUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        if (file.size > 2 * 1024 * 1024) { // 2MB limit
          errors.value = { ...errors.value, image: ['Image size must be less than 2MB'] }
          return
        }
        
        form.image = file
        
        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
          imagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
        
        // Clear image errors
        if (errors.value.image) {
          delete errors.value.image
        }
      }
    }

    const removeImage = () => {
      form.image = null
      imagePreview.value = null
      if (imageInput.value) {
        imageInput.value.value = ''
      }
    }

    const handleSubmit = async () => {
      loading.value = true
      errors.value = {}

      try {
        const formData = new FormData()
        
        // Append form fields with proper type handling
        Object.keys(form).forEach(key => {
          if (key === 'image' && form[key]) {
            formData.append('image', form[key])
          } else if (key !== 'image') {
            // Handle boolean fields properly
            if (key === 'is_active' || key === 'is_perishable') {
              formData.append(key, form[key] ? '1' : '0')
            } else if (key === 'min_stock' || key === 'cost_per_unit' || key === 'initial_stock') {
              // Ensure numeric fields have valid values
              const value = form[key] === '' || form[key] === null || form[key] === undefined ? '0' : form[key]
              formData.append(key, value)
            } else if (key === 'unit') {
              // Ensure unit has a default value
              const value = form[key] === '' || form[key] === null || form[key] === undefined ? 'pcs' : form[key]
              formData.append(key, value)
            } else if (form[key] !== '' && form[key] !== null && form[key] !== undefined) {
              formData.append(key, form[key])
            }
          }
        })

        let response
        if (isEdit.value) {
          response = await baseProductStore.updateBaseProduct(props.baseProduct.id_base_product, formData)
        } else {
          response = await baseProductStore.createBaseProduct(formData)
        }

        emit('saved', response.data)
      } catch (error) {
        console.error('Error saving base product:', error)
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
      imageInput,
      imagePreview,
      errors,
      form,
      isEdit,
      categoryItems,
      handleImageUpload,
      removeImage,
      handleSubmit,
      resetForm
    }
  }
}
</script>
