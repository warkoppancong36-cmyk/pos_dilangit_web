<template>
  <VDialog
    :model-value="modelValue"
    max-width="900px"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard class="product-dialog coffee-dialog">
      <!-- Header -->
      <VCardTitle class="d-flex align-center justify-space-between coffee-header">
        <div class="d-flex align-center gap-2">
          <VIcon
            :icon="editMode ? 'tabler-edit' : 'tabler-plus'"
            class="text-white"
          />
          <span class="text-white">
            {{ editMode ? 'Edit Produk' : 'Tambah Produk Baru' }}
          </span>
        </div>
        <VBtn
          icon="tabler-x"
          variant="text"
          color="white"
          @click="$emit('close')"
        />
      </VCardTitle>

      <VDivider />

      <!-- Form Content -->
      <VCardText class="pa-6">
        <VForm ref="formRef" @submit.prevent="onSubmit">
          <VRow>
            <!-- Basic Information -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-info-circle"
                  size="20"
                  class="coffee-icon"
                />
                Informasi Dasar
              </h6>
            </VCol>

            <!-- Product Name -->
            <VCol cols="12" md="8">
              <VTextField
                v-model="localFormData.name"
                label="Nama Produk"
                placeholder="Masukkan nama produk"
                :rules="nameRules"
                required
                variant="outlined"
              />
            </VCol>

            <!-- SKU -->
            <VCol cols="12" md="4">
              <VTextField
                v-model="localFormData.sku"
                label="SKU"
                placeholder="Auto generate jika kosong"
                variant="outlined"
              />
            </VCol>

            <!-- Description -->
            <VCol cols="12">
              <VTextarea
                v-model="localFormData.description"
                label="Deskripsi Produk"
                placeholder="Masukkan deskripsi produk"
                rows="3"
                variant="outlined"
              />
            </VCol>

            <!-- Category & Brand -->
            <VCol cols="12" md="6">
              <VSelect
                :model-value="localFormData.category_id"
                @update:model-value="localFormData.category_id = $event"
                :items="categories"
                item-title="name"
                item-value="id_category"
                label="Kategori"
                placeholder="Pilih kategori"
                :rules="categoryRules"
                required
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="localFormData.brand"
                label="Brand/Merek"
                placeholder="Masukkan brand produk"
                variant="outlined"
              />
            </VCol>

            <!-- Pricing -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-currency-dollar"
                  size="20"
                  class="coffee-icon"
                />
                Harga & Biaya
              </h6>
            </VCol>

            <!-- Price & Cost with HPP Integration -->
            <VCol cols="12" md="6">
              <VTextField
                :model-value="formatRupiah(localFormData.price)"
                @update:model-value="(value) => updatePrice(value)"
                label="Harga Jual"
                placeholder="Rp 0"
                required
                variant="outlined"
                @focus="onPriceFocus"
                @blur="onPriceBlur"
              />
            </VCol>

            <VCol cols="12" md="6">
              <div class="d-flex flex-column gap-2">
                <VTextField
                  :model-value="formatRupiah(localFormData.cost)"
                  label="Harga Pokok Penjualan (HPP)"
                  placeholder="Auto-calculated dari bahan baku"
                  variant="outlined"
                  readonly
                  append-inner-icon="mdi-calculator"
                  hint="HPP dihitung otomatis berdasarkan harga bahan baku"
                  persistent-hint
                />
                
                <!-- HPP Status Indicator -->
                <div v-if="editMode" class="d-flex align-center gap-2">
                  <VChip
                    :color="hasHPPCalculation ? 'success' : 'warning'"
                    size="small"
                    variant="tonal"
                  >
                    <VIcon 
                      :icon="hasHPPCalculation ? 'tabler-calculator' : 'tabler-alert-circle'" 
                      size="14" 
                      class="me-1"
                    />
                    {{ hasHPPCalculation ? 'Auto HPP Active' : 'Setup Required' }}
                  </VChip>
                  
                  <VBtn
                    v-if="hasHPPCalculation"
                    size="small"
                    variant="outlined"
                    color="primary"
                    @click="refreshHPP"
                    :loading="loadingHPP"
                  >
                    <VIcon icon="tabler-refresh" size="14" class="me-1" />
                    Update HPP
                  </VBtn>
                  
                  <VTooltip v-else>
                    <template #activator="{ props: tooltipProps }">
                      <VChip
                        v-bind="tooltipProps"
                        color="warning"
                        size="small"
                        variant="tonal"
                      >
                        <VIcon icon="tabler-alert-circle" size="14" class="me-1" />
                        Belum Ada Resep
                      </VChip>
                    </template>
                    <span>Setup resep produk untuk menghitung HPP otomatis</span>
                  </VTooltip>
                </div>
              </div>
            </VCol>

            <!-- HPP Information Alert -->
            <VCol v-if="editMode" cols="12">
              <VAlert
                v-if="hasHPPCalculation"
                color="info"
                variant="tonal"
                class="mb-0"
              >
                <VAlertTitle>
                  <VIcon icon="tabler-info-circle" class="me-2" />
                  HPP Otomatis Aktif
                </VAlertTitle>
                <div class="mt-2">
                  HPP produk ini dihitung otomatis berdasarkan item/bahan yang digunakan. 
                  Untuk mengubah HPP, silakan update harga item di menu Item Management.
                </div>
              </VAlert>
              
              <VAlert
                v-else
                color="warning"
                variant="tonal"
                class="mb-0"
              >
                <VAlertTitle>
                  <VIcon icon="tabler-alert-triangle" class="me-2" />
                  HPP Manual
                </VAlertTitle>
                <div class="mt-2">
                  Produk ini menggunakan HPP manual. Untuk perhitungan otomatis, 
                  tambahkan item/bahan yang digunakan untuk produk ini.
                </div>
              </VAlert>
            </VCol>

            <!-- HPP Setup Information Alert -->
            <VCol cols="12">
              <!-- Alert for products WITH HPP calculation -->
              <VAlert
                v-if="hasHPPCalculation && editMode"
                color="success"
                variant="tonal"
                class="mb-4"
              >
                <div class="d-flex align-center gap-2 mb-2">
                  <VIcon icon="tabler-check-circle" />
                  <span class="font-weight-medium">HPP Otomatis Aktif</span>
                </div>
                <div class="text-body-2 mb-3">
                  Produk ini sudah memiliki resep dan HPP dihitung otomatis berdasarkan harga bahan baku terkini. 
                  HPP akan update secara otomatis saat harga bahan baku berubah.
                </div>
                <div class="d-flex gap-2">
                  <VBtn
                    size="small"
                    color="primary"
                    variant="outlined"
                    @click="openItemsSetup"
                  >
                    <VIcon icon="tabler-edit" size="14" class="me-1" />
                    Edit Resep
                  </VBtn>
                  <VBtn
                    size="small"
                    variant="outlined"
                    color="info"
                    @click="refreshHPP"
                    :loading="loadingHPP"
                  >
                    <VIcon icon="tabler-refresh" size="14" class="me-1" />
                    Refresh HPP
                  </VBtn>
                </div>
              </VAlert>
              
              <!-- Alert for products WITHOUT HPP calculation -->
              <VAlert
                v-else-if="!hasHPPCalculation && editMode"
                color="warning"
                variant="tonal"
                class="mb-0"
              >
                <div class="d-flex gap-2">
                  <VBtn
                    size="small"
                    variant="outlined"
                    color="info"
                    @click="router.push('/hpp-management')"
                  >
                    <VIcon icon="tabler-calculator" size="14" class="me-1" />
                    Lihat HPP Management
                  </VBtn>
                </div>
              </VAlert>
            </VCol>

            <!-- Profit Margin Display -->
            <VCol cols="12">
              <VAlert
                v-if="localFormData.price && localFormData.cost && localFormData.cost > 0"
                :color="getMarginAlertColor(profitMargin)"
                variant="tonal"
                class="mb-0"
              >
                <div class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center gap-2">
                    <VIcon :icon="getMarginIcon(profitMargin)" />
                    <span class="font-weight-medium">Profit Margin</span>
                  </div>
                  <div class="text-end">
                    <div class="text-h6 font-weight-bold">{{ Math.round(profitMargin) }}%</div>
                    <div class="text-caption">
                      Keuntungan: {{ formatCurrency(localFormData.price - localFormData.cost) }}
                    </div>
                  </div>
                </div>
              </VAlert>
            </VCol>
            <!-- Dimensions -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-ruler"
                  size="20"
                  class="coffee-icon"
                />
                Dimensi (cm)
              </h6>
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model.number="dimensionsForm.length"
                label="Panjang"
                placeholder="0"
                type="number"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model.number="dimensionsForm.width"
                label="Lebar"
                placeholder="0"
                type="number"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model.number="dimensionsForm.height"
                label="Tinggi"
                placeholder="0"
                type="number"
                variant="outlined"
              />
            </VCol>

            <!-- Image Upload -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-photo"
                  size="20"
                  class="coffee-icon"
                />
                Gambar Produk
              </h6>
            </VCol>

            <VCol cols="12">
              <div class="image-upload-container">
                <!-- Image Preview -->
                <div v-if="imagePreview" class="mb-4">
                  <VImg
                    :src="imagePreview"
                    max-width="200"
                    max-height="200"
                    class="mx-auto rounded"
                  />
                  <div class="text-center mt-2">
                    <VBtn
                      size="small"
                      color="error"
                      variant="outlined"
                      @click="removeImage"
                    >
                      Hapus Gambar
                    </VBtn>
                  </div>
                </div>

                <!-- File Input -->
                <VFileInput
                  :model-value="selectedImage"
                  @update:model-value="(files) => onImageChange(Array.isArray(files) ? files : [files])"
                  label="Upload Gambar"
                  prepend-icon="tabler-camera"
                  accept="image/*"
                  variant="outlined"
                />
                <div class="text-caption text-medium-emphasis mt-1">
                  Format: JPG, JPEG, PNG, GIF. Maksimal 2MB.
                </div>
              </div>
            </VCol>

            <!-- Settings -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-settings"
                  size="20"
                  class="coffee-icon"
                />
                Pengaturan
              </h6>
            </VCol>

            <!-- Status -->
            <VCol cols="12" md="4">
              <VSelect
                :model-value="localFormData.status"
                @update:model-value="localFormData.status = $event"
                :items="statusOptions"
                label="Status Publikasi"
                variant="outlined"
              />
            </VCol>

            <!-- Active & Featured -->
            <VCol cols="12" md="4">
              <VSwitch
                :model-value="localFormData.active"
                @update:model-value="localFormData.active = $event ?? false"
                label="Produk Aktif"
                color="coffee"
                inset
              />
            </VCol>

            <VCol cols="12" md="4">
              <VSwitch
                :model-value="localFormData.featured"
                @update:model-value="localFormData.featured = $event ?? false"
                label="Produk Unggulan"
                color="coffee"
                inset
              />
            </VCol>

            <!-- Meta Information -->
            <VCol cols="12">
              <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                <VIcon
                  icon="tabler-seo"
                  size="20"
                  class="coffee-icon"
                />
                SEO (Opsional)
              </h6>
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="localFormData.meta_title"
                label="Meta Title"
                placeholder="Judul untuk SEO"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="localFormData.meta_description"
                label="Meta Description"
                placeholder="Deskripsi untuk SEO"
                rows="2"
                variant="outlined"
              />
            </VCol>

            <!-- Tags -->
            <VCol cols="12">
              <VCombobox
                :model-value="localFormData.tags"
                @update:model-value="localFormData.tags = $event"
                label="Tags"
                placeholder="Tambah tag dan tekan Enter"
                multiple
                chips
                variant="outlined"
              />
            </VCol>
          </VRow>

        </VForm>
      </VCardText>

      <VDivider />

      <!-- Actions -->
      <VCardActions class="pa-6">
        <VSpacer />
        <VBtn
          variant="outlined"
          class="coffee-secondary"
          @click="$emit('close')"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          class="coffee-primary"
          :loading="loading"
          @click="onSubmit"
        >
          {{ editMode ? 'Perbarui' : 'Simpan' }} Produk
        </VBtn>
      </VCardActions>
    </VCard>

    <!-- Error Popup - Snackbar Style (Default) -->
    <VSnackbar
      v-if="!useDialogError"
      :model-value="!!displayErrorMessage"
      @update:model-value="!$event && $emit('clear-error')"
      color="error"
      location="top right"
      :timeout="6000"
      vertical
      multi-line
      min-width="320"
      max-width="500"
    >
      <div class="d-flex align-center">
        <VIcon 
          icon="tabler-alert-circle" 
          class="me-3"
          size="28"
        />
        <div>
          <div class="text-h6 font-weight-bold">Error!</div>
          <div class="text-subtitle-1">{{ displayErrorMessage }}</div>
        </div>
      </div>
      
      <template #actions>
        <VBtn
          icon="tabler-x"
          size="small"
          variant="text"
          @click="$emit('clear-error')"
        />
      </template>
    </VSnackbar>

    <!-- Error Popup - Dialog Style (Optional) -->
    <VDialog
      v-if="useDialogError"
      :model-value="!!displayErrorMessage"
      @update:model-value="!$event && $emit('clear-error')"
      max-width="400"
      persistent
      class="error-dialog"
    >
      <VCard>
        <VCardTitle class="d-flex align-center text-error">
          <VIcon 
            icon="tabler-alert-circle" 
            class="me-2"
          />
          Error Occurred
        </VCardTitle>
        
        <VDivider />
        
        <VCardText class="py-4">
          <div class="text-body-1">{{ displayErrorMessage }}</div>
        </VCardText>
        
        <VDivider />
        
        <VCardActions class="justify-end pa-4">
          <VBtn
            color="error"
            variant="outlined"
            @click="$emit('clear-error')"
          >
            Close
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Success Popup -->
    <VSnackbar
      :model-value="!!successMessage"
      @update:model-value="!$event && $emit('clear-success')"
      color="success"
      location="top right"
      :timeout="4000"
      vertical
      min-width="320"
      max-width="500"
    >
      <div class="d-flex align-center">
        <VIcon 
          icon="tabler-check-circle" 
          class="me-3"
          size="28"
        />
        <div>
          <div class="text-h6 font-weight-bold">Berhasil!</div>
          <div class="text-subtitle-1">{{ successMessage }}</div>
        </div>
      </div>
      
      <template #actions>
        <VBtn
          icon="tabler-x"
          size="small"
          variant="text"
          @click="$emit('clear-success')"
        />
      </template>
    </VSnackbar>
  </VDialog>
</template>

<script setup lang="ts">
import { useHPP } from '@/composables/useHPP';
import type { Category, ProductFormData } from '@/composables/useProducts';
import { useProducts } from '@/composables/useProducts';
import { computed, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

// Initialize router
const router = useRouter()

// Get validation rules from composable
const { nameRules, priceRules, categoryRules } = useProducts()

// HPP Integration
const { getProductHPPBreakdown, updateProductHPP } = useHPP()
const loadingHPP = ref(false)
const hasHPPCalculation = ref(false)

// Check if product has HPP calculation (has product-items)
const checkHPPStatus = async () => {
  if (!props.editMode || !localFormData.id) return
  
  try {
    // Check if product has items configured
    const response = await fetch(`/api/products/${localFormData.id}/items`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    })
    
    if (response.ok) {
      const data = await response.json()
      hasHPPCalculation.value = data.success && data.data && data.data.length > 0
    }
  } catch (error) {
    console.error('Error checking HPP status:', error)
  }
}

// Refresh HPP calculation
const refreshHPP = async () => {
  if (!localFormData.id) return
  
  try {
    loadingHPP.value = true
    const breakdown = await getProductHPPBreakdown(localFormData.id, 'latest')
    if (breakdown) {
      localFormData.cost = breakdown.total_hpp
      emit('update-form', 'cost', breakdown.total_hpp)
    }
  } catch (error) {
    console.error('Error refreshing HPP:', error)
  } finally {
    loadingHPP.value = false
  }
}

// Open items setup (redirect to product-items management)
const openItemsSetup = () => {
  // You can emit event to parent to handle navigation
  console.log('Open items setup for product:', localFormData.id)
  // Or use router to navigate to product-items with filter
}

// Props
const props = defineProps<{
  modelValue: boolean
  editMode: boolean
  formData: ProductFormData
  categories: Category[]
  loading: boolean
  errorMessage?: string
  successMessage?: string
  selectedImage?: File[]
  imagePreview?: string
  useDialogError?: boolean 
}>()

// Use errorMessage from props
const displayErrorMessage = computed(() => props.errorMessage)

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'close': []
  'submit': [data: ProductFormData]
  'image-change': [files: File[]]
  'remove-image': []
  'clear-error': []
  'clear-success': []
  'update-form': [field: string, value: any]
}>()

// Form reference
const formRef = ref()

// Local form data - reactive copy of props
const localFormData = reactive({ ...props.formData })

// Watch props changes to sync with local data
watch(() => props.formData, (newData) => {
  Object.assign(localFormData, newData)
}, { deep: true })

// Watch local form changes and emit updates
watch(localFormData, (newData) => {
  Object.keys(newData).forEach(key => {
    emit('update-form', key, newData[key as keyof ProductFormData])
  })
}, { deep: true })

// Watch dialog state to check HPP status when opened
watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.editMode) {
    checkHPPStatus()
  }
})

// Local dimensions form
const dimensionsForm = reactive({
  length: props.formData.dimensions?.length || null,
  width: props.formData.dimensions?.width || null,
  height: props.formData.dimensions?.height || null
})

// Watch dimensions changes
watch(dimensionsForm, (newDimensions) => {
  const hasAnyDimension = newDimensions.length !== null || newDimensions.width !== null || newDimensions.height !== null;
  localFormData.dimensions = hasAnyDimension
    ? {
        length: newDimensions.length !== null ? newDimensions.length : undefined,
        width: newDimensions.width !== null ? newDimensions.width : undefined,
        height: newDimensions.height !== null ? newDimensions.height : undefined,
      }
    : {};
}, { deep: true })

// Status options
const statusOptions = [
  { title: 'Draft', value: 'draft' },
  { title: 'Published', value: 'published' },
  { title: 'Archived', value: 'archived' }
]

// Computed properties for profit margin
const profitMargin = computed(() => {
  if (!localFormData.price || !localFormData.cost || localFormData.cost <= 0) {
    return 0
  }
  const margin = ((localFormData.price - localFormData.cost) / localFormData.cost) * 100
  return Math.ceil(margin)
})

// Utility functions for margin
const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const getMarginAlertColor = (margin: number): string => {
  if (margin < 10) return 'error'      // Red for low margin
  if (margin < 25) return 'warning'    // Orange for moderate margin
  if (margin < 50) return 'success'    // Green for good margin
  return 'info'                        // Blue for excellent margin
}

const getMarginIcon = (margin: number): string => {
  if (margin < 10) return 'tabler-trending-down'
  if (margin < 25) return 'tabler-trending-up'
  if (margin < 50) return 'tabler-trending-up-2'
  return 'tabler-trending-up-3'
}

// Currency formatting functions
const formatRupiah = (value: number | undefined | null): string => {
  if (!value || value === 0) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const parseRupiah = (value: string): number => {
  if (!value || value.trim() === '' || value === 'Rp 0') return 0
  // Remove all non-digit characters except comma and period
  const cleanValue = value.replace(/[^\d,.-]/g, '')
  // Handle Indonesian number format (dots as thousands separator, comma as decimal)
  const normalizedValue = cleanValue.replace(/\./g, '').replace(',', '.')
  return parseFloat(normalizedValue) || 0
}

// State for focus handling
const isPriceFocused = ref(false)

// Input handlers
const updatePrice = (value: string) => {
  const numericValue = parseRupiah(value)
  localFormData.price = numericValue
}

// Custom validation rules for formatted fields
const priceValidationRules = [
  (value: string) => {
    const numericValue = parseRupiah(value)
    if (!numericValue || numericValue <= 0) {
      return 'Harga harus lebih dari 0'
    }
    return true
  }
]

const onPriceFocus = () => {
  isPriceFocused.value = true
}

const onPriceBlur = () => {
  isPriceFocused.value = false
}

// Methods
const onSubmit = async () => {
  const { valid } = await formRef.value?.validate()
  if (valid) {
    emit('submit', localFormData)
  }
}

const onImageChange = (files: File[]) => {
  emit('image-change', files)
}

const removeImage = () => {
  emit('remove-image')
}
</script>

<style scoped>
/* ProductDialog styles moved to resources/styles/products-management.scss */
</style>
