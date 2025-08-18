<template>
  <VDialog v-model="dialog" max-width="800px" persistent>
    <VCard>
      <VCardTitle class="text-h5 pa-6 pb-4">
        <VIcon icon="tabler-plus" class="me-2" />
        {{ isEdit ? 'Edit Asset' : 'Tambah Asset Baru' }}
        <VSpacer />
        <VBtn icon variant="text" @click="closeDialog">
          <VIcon>tabler-x</VIcon>
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-6">
        <VForm ref="formRef" v-model="valid" @submit.prevent="saveAsset">
          <VRow>
            <!-- Asset Code -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.asset_code"
                label="Kode Asset"
                :rules="requiredRules"
                variant="outlined"
                prepend-inner-icon="tabler-barcode"
                required
              />
            </VCol>

            <!-- Asset Name -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.name"
                label="Nama Asset"
                :rules="requiredRules"
                variant="outlined"
                prepend-inner-icon="tabler-tag"
                required
              />
            </VCol>

            <!-- Category -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="assetForm.category"
                label="Kategori"
                :items="categoryOptions"
                :rules="requiredRules"
                variant="outlined"
                prepend-inner-icon="tabler-category"
                required
              />
            </VCol>

            <!-- Location -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="assetForm.location"
                label="Lokasi"
                :items="locationOptions"
                :rules="requiredRules"
                variant="outlined"
                prepend-inner-icon="tabler-map-pin"
                required
              />
            </VCol>

            <!-- Brand -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.brand"
                label="Merek"
                variant="outlined"
                prepend-inner-icon="tabler-award"
              />
            </VCol>

            <!-- Model -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.model"
                label="Model"
                variant="outlined"
                prepend-inner-icon="tabler-versions"
              />
            </VCol>

            <!-- Serial Number -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.serial_number"
                label="Serial Number"
                variant="outlined"
                prepend-inner-icon="tabler-hash"
              />
            </VCol>

            <!-- Purchase Price -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.purchase_price"
                label="Harga Pembelian"
                type="number"
                variant="outlined"
                prepend-inner-icon="tabler-currency-dollar"
                prefix="$"
              />
            </VCol>

            <!-- Purchase Date -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.purchase_date"
                label="Tanggal Pembelian"
                type="date"
                variant="outlined"
                prepend-inner-icon="tabler-calendar"
              />
            </VCol>

            <!-- Warranty Until -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.warranty_until"
                label="Garansi Sampai"
                type="date"
                variant="outlined"
                prepend-inner-icon="tabler-shield-check"
              />
            </VCol>

            <!-- Status -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="assetForm.status"
                label="Status"
                :items="statusOptions"
                :rules="requiredRules"
                variant="outlined"
                prepend-inner-icon="tabler-toggle-left"
                required
              />
            </VCol>

            <!-- Condition -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="assetForm.condition"
                label="Kondisi"
                :items="conditionOptions"
                :rules="requiredRules"
                variant="outlined"
                prepend-inner-icon="tabler-heart-rate-monitor"
                required
              />
            </VCol>

            <!-- Supplier -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.supplier"
                label="Supplier"
                variant="outlined"
                prepend-inner-icon="tabler-building"
              />
            </VCol>

            <!-- Assigned To -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="assetForm.assigned_to"
                label="Ditugaskan Kepada"
                variant="outlined"
                prepend-inner-icon="tabler-user"
              />
            </VCol>

            <!-- Department -->
            <VCol cols="12">
              <VSelect
                v-model="assetForm.department"
                label="Departemen"
                :items="departmentOptions"
                variant="outlined"
                prepend-inner-icon="tabler-building-bank"
              />
            </VCol>

            <!-- Description -->
            <VCol cols="12">
              <VTextarea
                v-model="assetForm.description"
                label="Deskripsi"
                variant="outlined"
                prepend-inner-icon="tabler-notes"
                rows="3"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-6">
        <VSpacer />
        <VBtn
          variant="text"
          @click="closeDialog"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          @click="saveAsset"
        >
          {{ isEdit ? 'Update' : 'Simpan' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface AssetForm {
  id?: number
  asset_code: string
  name: string
  category: string
  brand?: string
  model?: string
  serial_number?: string
  purchase_date?: string
  purchase_price?: number
  location: string
  condition: string
  status: string
  description?: string
  supplier?: string
  warranty_until?: string
  assigned_to?: string
  department?: string
}

// Props
interface Props {
  show: boolean
  asset?: any
}

// Emits
interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'saved'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Reactive refs
const valid = ref(false)
const loading = ref(false)
const formRef = ref()

// Form data
const assetForm = ref<AssetForm>({
  asset_code: '',
  name: '',
  category: '',
  location: '',
  condition: 'good',
  status: 'active'
})

// Computed
const dialog = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

const isEdit = computed(() => !!props.asset?.id)

// Form options
const categoryOptions = [
  'Kitchen Equipment',
  'Electronics',
  'Furniture',
  'Beverage Equipment',
  'Cleaning Equipment',
  'Storage',
  'Other'
]

const locationOptions = [
  'Main Kitchen',
  'Front Counter',
  'Dining Area',
  'Cold Storage',
  'Beverage Station',
  'Dish Pit',
  'Back Office',
  'Storage Room'
]

const statusOptions = [
  { title: 'Active', value: 'active' },
  { title: 'Inactive', value: 'inactive' },
  { title: 'Maintenance', value: 'maintenance' },
  { title: 'Disposed', value: 'disposed' }
]

const conditionOptions = [
  { title: 'Excellent', value: 'excellent' },
  { title: 'Good', value: 'good' },
  { title: 'Fair', value: 'fair' },
  { title: 'Poor', value: 'poor' },
  { title: 'Damaged', value: 'damaged' }
]

const departmentOptions = [
  'Kitchen',
  'Front of House',
  'Beverage',
  'Management',
  'Maintenance',
  'Storage'
]

// Validation rules
const requiredRules = [
  (v: string) => !!v || 'Field ini wajib diisi'
]

// Methods
const resetForm = () => {
  assetForm.value = {
    asset_code: '',
    name: '',
    category: '',
    location: '',
    condition: 'good',
    status: 'active'
  }
  if (formRef.value) {
    formRef.value.resetValidation()
  }
}

const closeDialog = () => {
  emit('update:show', false)
  resetForm()
}

const saveAsset = async () => {
  if (!formRef.value) return
  
  const { valid: isValid } = await formRef.value.validate()
  if (!isValid) return

  loading.value = true
  
  try {
    // TODO: Implement actual save logic
    console.log('Saving asset:', assetForm.value)
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    emit('saved')
    closeDialog()
  } catch (error) {
    console.error('Error saving asset:', error)
  } finally {
    loading.value = false
  }
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue && props.asset) {
    // Populate form with asset data for editing
    Object.assign(assetForm.value, props.asset)
  } else if (!newValue) {
    resetForm()
  }
})
</script>
