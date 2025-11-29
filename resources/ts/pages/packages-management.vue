<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

// ==================== Types ====================
interface Product {
  id_product: number
  name: string
  price: number
  stock: number
  active: boolean
  unit: string
}

interface PackageItem {
  id_product: number
  product?: Product
  quantity: number
  unit: string
  is_optional: boolean
  sort_order: number
  notes?: string
}

interface Package {
  id_package: number
  name: string
  slug: string
  description?: string
  sku?: string
  barcode?: string
  image?: string
  package_type: 'fixed' | 'customizable'
  regular_price: number
  package_price: number
  savings_amount: number
  savings_percentage: number
  category_id?: number
  category?: any
  is_active: boolean
  is_featured: boolean
  status: 'draft' | 'published' | 'archived'
  stock: number
  track_stock: boolean
  tags?: string[]
  sort_order: number
  items: PackageItem[]
  items_count: number
  formatted_package_price: string
  formatted_regular_price: string
  formatted_savings: string
  availability_status: string
}

interface FormData {
  name: string
  description: string
  sku: string
  barcode: string
  image: string
  package_type: 'fixed' | 'customizable'
  package_price: number | null
  category_id: number | null
  is_active: boolean
  is_featured: boolean
  status: 'draft' | 'published' | 'archived'
  stock: number
  track_stock: boolean
  tags: string[]
  sort_order: number
  items: PackageItem[]
}

// ==================== State ====================
const loading = ref(false)
const saveLoading = ref(false)
const deleteLoading = ref(false)
const packagesList = ref<Package[]>([])
const productsList = ref<Product[]>([])
const categories = ref<any[]>([])
const selectedPackages = ref<Package[]>([])

// Pagination
const currentPage = ref(1)
const totalItems = ref(0)
const itemsPerPage = ref(15)

// Filters
const search = ref('')
const statusFilter = ref<string | null>(null)
const categoryFilter = ref<number | null>(null)
const packageTypeFilter = ref<string | null>(null)

// Dialog states
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedPackage = ref<Package | null>(null)

// Messages
const errorMessage = ref('')
const successMessage = ref('')
const modalErrorMessage = ref('')

// Form
const formData = ref<FormData>({
  name: '',
  description: '',
  sku: '',
  barcode: '',
  image: '',
  package_type: 'fixed',
  package_price: null,
  category_id: null,
  is_active: true,
  is_featured: false,
  status: 'published',
  stock: 0,
  track_stock: false,
  tags: [],
  sort_order: 0,
  items: [],
})

// Product search for adding items
const productSearch = ref('')
const productDialog = ref(false)

// Package price display (formatted)
const packagePriceDisplay = ref('')

// ==================== Computed ====================
const headers = [
  { title: 'Nama Paket', key: 'name', sortable: true },
  { title: 'SKU', key: 'sku', sortable: false },
  { title: 'Isi', key: 'items_count', sortable: true },
  { title: 'Harga Normal', key: 'regular_price', sortable: true },
  { title: 'Harga Paket', key: 'package_price', sortable: true },
  { title: 'Hemat', key: 'savings_amount', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Aksi', key: 'actions', sortable: false },
]

const statusOptions = [
  { title: 'Semua', value: null },
  { title: 'Draft', value: 'draft' },
  { title: 'Published', value: 'published' },
  { title: 'Archived', value: 'archived' },
]

const packageTypeOptions = [
  { title: 'Semua', value: null },
  { title: 'Fixed', value: 'fixed' },
  { title: 'Customizable', value: 'customizable' },
]

const canCreateEdit = computed(() => {
  return formData.value.name && 
         formData.value.package_price !== null && 
         formData.value.category_id !== null &&
         formData.value.items.length > 0
})

const calculatedRegularPrice = computed(() => {
  return formData.value.items.reduce((sum, item) => {
    const product = productsList.value.find(p => p.id_product === item.id_product)
    if (product) {
      return sum + (product.price * item.quantity)
    }
    return sum
  }, 0)
})

const calculatedSavings = computed(() => {
  if (formData.value.package_price === null || formData.value.package_price === 0) return 0
  return Math.abs(calculatedRegularPrice.value - formData.value.package_price)
})

const calculatedSavingsPercentage = computed(() => {
  if (calculatedRegularPrice.value === 0 || calculatedSavings.value === 0) return 0
  return (calculatedSavings.value / calculatedRegularPrice.value) * 100
})

const filteredProducts = computed(() => {
  if (!productSearch.value) return productsList.value
  const s = productSearch.value.toLowerCase()
  return productsList.value.filter(p => 
    p.name.toLowerCase().includes(s) || 
    (p.id_product.toString().includes(s))
  )
})

// ==================== Methods ====================
const fetchPackages = async () => {
  loading.value = true
  errorMessage.value = ''
  
  try {
    const params: any = {
      page: currentPage.value,
      per_page: itemsPerPage.value,
    }
    
    if (search.value) params.search = search.value
    if (statusFilter.value) params.status = statusFilter.value
    if (categoryFilter.value) params.category_id = categoryFilter.value
    if (packageTypeFilter.value) params.package_type = packageTypeFilter.value
    
    const response = await axios.get('/api/packages', { params })
    
    packagesList.value = response.data.data.data
    totalItems.value = response.data.data.total
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Gagal memuat data paket'
  } finally {
    loading.value = false
  }
}

const fetchProducts = async () => {
  try {
    const response = await axios.get('/api/products', { 
      params: { 
        paginate: false,
        is_active: true 
      } 
    })
    productsList.value = response.data.data
  } catch (error: any) {
    console.error('Gagal memuat produk:', error)
  }
}

const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/categories', { 
      params: { paginate: false } 
    })
    categories.value = response.data.data
  } catch (error: any) {
    console.error('Gagal memuat kategori:', error)
  }
}

const openCreateDialog = () => {
  editMode.value = false
  resetForm()
  dialog.value = true
}

const openEditDialog = (pkg: Package) => {
  editMode.value = true
  selectedPackage.value = pkg
  
  formData.value = {
    name: pkg.name,
    description: pkg.description || '',
    sku: pkg.sku || '',
    barcode: pkg.barcode || '',
    image: pkg.image || '',
    package_type: pkg.package_type,
    package_price: pkg.package_price,
    category_id: pkg.category_id || null,
    is_active: pkg.is_active,
    is_featured: pkg.is_featured,
    status: pkg.status,
    stock: pkg.stock,
    track_stock: pkg.track_stock,
    tags: pkg.tags || [],
    sort_order: pkg.sort_order,
    items: pkg.items.map(item => ({
      id_product: item.id_product,
      product: item.product,
      quantity: item.quantity,
      unit: item.unit,
      is_optional: item.is_optional,
      sort_order: item.sort_order,
      notes: item.notes,
    })),
  }
  
  packagePriceDisplay.value = formatRupiahInput(pkg.package_price)
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  modalErrorMessage.value = ''
  setTimeout(resetForm, 300)
}

const resetForm = () => {
  formData.value = {
    name: '',
    description: '',
    sku: '',
    barcode: '',
    image: '',
    package_type: 'fixed',
    package_price: null,
    category_id: null,
    is_active: true,
    is_featured: false,
    status: 'published',
    stock: 0,
    track_stock: false,
    tags: [],
    sort_order: 0,
    items: [],
  }
  packagePriceDisplay.value = ''
  selectedPackage.value = null
}

const savePackage = async () => {
  if (!canCreateEdit.value) return
  
  saveLoading.value = true
  modalErrorMessage.value = ''
  
  try {
    const payload = {
      ...formData.value,
      items: formData.value.items.map((item, index) => ({
        id_product: item.id_product,
        quantity: item.quantity,
        unit: item.unit,
        is_optional: item.is_optional,
        sort_order: index,
        notes: item.notes,
      }))
    }
    
    if (editMode.value && selectedPackage.value) {
      await axios.put(`/api/packages/${selectedPackage.value.id_package}`, payload)
      successMessage.value = 'Paket berhasil diperbarui'
    } else {
      await axios.post('/api/packages', payload)
      successMessage.value = 'Paket berhasil dibuat'
    }
    
    closeDialog()
    fetchPackages()
    
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    modalErrorMessage.value = error.response?.data?.message || 'Gagal menyimpan paket'
  } finally {
    saveLoading.value = false
  }
}

const openDeleteDialog = (pkg: Package) => {
  selectedPackage.value = pkg
  deleteDialog.value = true
}

const deletePackage = async () => {
  if (!selectedPackage.value) return
  
  deleteLoading.value = true
  
  try {
    await axios.delete(`/api/packages/${selectedPackage.value.id_package}`)
    successMessage.value = 'Paket berhasil dihapus'
    deleteDialog.value = false
    selectedPackage.value = null
    fetchPackages()
    
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Gagal menghapus paket'
  } finally {
    deleteLoading.value = false
  }
}

const bulkDeletePackages = async () => {
  if (selectedPackages.value.length === 0) return
  
  deleteLoading.value = true
  
  try {
    const ids = selectedPackages.value.map(pkg => pkg.id_package)
    await axios.post('/api/packages/bulk-delete', { ids })
    successMessage.value = `${ids.length} paket berhasil dihapus`
    selectedPackages.value = []
    fetchPackages()
    
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Gagal menghapus paket'
  } finally {
    deleteLoading.value = false
  }
}

const addProduct = (product: Product) => {
  // Check if already added
  const exists = formData.value.items.find(item => item.id_product === product.id_product)
  if (exists) {
    modalErrorMessage.value = 'Produk sudah ditambahkan'
    return
  }
  
  formData.value.items.push({
    id_product: product.id_product,
    product: product,
    quantity: 1,
    unit: product.unit || 'pcs',
    is_optional: false,
    sort_order: formData.value.items.length,
    notes: '',
  })
  
  productDialog.value = false
  productSearch.value = ''
}

const removeProduct = (index: number) => {
  formData.value.items.splice(index, 1)
}

const formatCurrency = (value: number) => {
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(value)
}

const parseRupiah = (value: string): number => {
  if (!value) return 0
  // Remove Rp, spaces, and dots (thousand separator)
  const cleaned = value.replace(/Rp\s?/g, '').replace(/\./g, '').replace(/,/g, '')
  const parsed = parseFloat(cleaned)
  return isNaN(parsed) ? 0 : parsed
}

const formatRupiahInput = (value: number | null): string => {
  if (value === null || value === 0) return ''
  return new Intl.NumberFormat('id-ID').format(value)
}

const handlePackagePriceInput = (event: Event) => {
  const input = event.target as HTMLInputElement
  const rawValue = input.value
  const numericValue = parseRupiah(rawValue)
  
  formData.value.package_price = numericValue
  packagePriceDisplay.value = formatRupiahInput(numericValue)
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'published': return 'success'
    case 'draft': return 'warning'
    case 'archived': return 'error'
    default: return 'default'
  }
}

const getAvailabilityColor = (status: string) => {
  switch (status) {
    case 'available': return 'success'
    case 'unavailable': return 'warning'
    case 'out_of_stock': return 'error'
    case 'inactive': return 'default'
    default: return 'default'
  }
}

// ==================== Watchers ====================
watch([search, statusFilter, categoryFilter, packageTypeFilter], () => {
  currentPage.value = 1
  fetchPackages()
})

watch(currentPage, () => {
  fetchPackages()
})

// ==================== Lifecycle ====================
onMounted(() => {
  fetchPackages()
  fetchProducts()
  fetchCategories()
})
</script>

<template>
  <div>
    <!-- Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
            <h2 class="text-h4 font-weight-bold mb-1">
              Manajemen Paket
            </h2>
            <p class="text-body-1 text-medium-emphasis">
              Kelola paket bundling produk
            </p>
          </div>
          <VBtn
            color="primary"
            prepend-icon="tabler-plus"
            @click="openCreateDialog"
          >
            Buat Paket Baru
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- Success Message -->
    <VRow v-if="successMessage">
      <VCol cols="12">
        <VAlert
          type="success"
          closable
          @click:close="successMessage = ''"
        >
          {{ successMessage }}
        </VAlert>
      </VCol>
    </VRow>

    <!-- Error Message -->
    <VRow v-if="errorMessage">
      <VCol cols="12">
        <VAlert
          type="error"
          closable
          @click:close="errorMessage = ''"
        >
          {{ errorMessage }}
        </VAlert>
      </VCol>
    </VRow>

    <!-- Filters -->
    <VRow>
      <VCol
        cols="12"
        md="4"
      >
        <VTextField
          v-model="search"
          placeholder="Cari nama, SKU, barcode..."
          prepend-inner-icon="tabler-search"
          clearable
          density="comfortable"
        />
      </VCol>
      <VCol
        cols="12"
        md="3"
      >
        <VSelect
          v-model="statusFilter"
          :items="statusOptions"
          item-title="title"
          item-value="value"
          label="Status"
          density="comfortable"
          clearable
        />
      </VCol>
      <VCol
        cols="12"
        md="3"
      >
        <VSelect
          v-model="categoryFilter"
          :items="categories"
          item-title="name"
          item-value="id_category"
          label="Kategori"
          density="comfortable"
          clearable
        />
      </VCol>
      <VCol
        cols="12"
        md="2"
      >
        <VSelect
          v-model="packageTypeFilter"
          :items="packageTypeOptions"
          item-title="title"
          item-value="value"
          label="Tipe"
          density="comfortable"
          clearable
        />
      </VCol>
    </VRow>

    <!-- Bulk Actions -->
    <VRow v-if="selectedPackages.length > 0">
      <VCol cols="12">
        <VAlert
          type="info"
          class="d-flex align-center"
        >
          <div class="d-flex justify-space-between align-center w-100">
            <span>{{ selectedPackages.length }} paket dipilih</span>
            <VBtn
              color="error"
              size="small"
              @click="bulkDeletePackages"
            >
              Hapus Terpilih
            </VBtn>
          </div>
        </VAlert>
      </VCol>
    </VRow>

    <!-- Table -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VDataTable
            v-model="selectedPackages"
            :headers="headers"
            :items="packagesList"
            :loading="loading"
            :items-per-page="itemsPerPage"
            hide-default-footer
            show-select
            item-value="id_package"
          >
            <!-- Name -->
            <template #[`item.name`]="{ item }">
              <div class="d-flex align-center gap-3">
                <VAvatar
                  :image="item.image"
                  size="40"
                  rounded
                >
                  <VIcon
                    v-if="!item.image"
                    icon="tabler-package"
                  />
                </VAvatar>
                <div>
                  <div class="font-weight-medium">
                    {{ item.name }}
                  </div>
                  <div
                    v-if="item.description"
                    class="text-caption text-medium-emphasis"
                  >
                    {{ item.description.substring(0, 50) }}{{ item.description.length > 50 ? '...' : '' }}
                  </div>
                </div>
              </div>
            </template>

            <!-- Items Count -->
            <template #[`item.items_count`]="{ item }">
              <VChip
                size="small"
                color="primary"
                variant="tonal"
              >
                {{ item.items_count }} item
              </VChip>
            </template>

            <!-- Regular Price -->
            <template #[`item.regular_price`]="{ item }">
              <span class="text-decoration-line-through text-medium-emphasis">
                {{ formatCurrency(item.regular_price) }}
              </span>
            </template>

            <!-- Package Price -->
            <template #[`item.package_price`]="{ item }">
              <span class="font-weight-bold text-success">
                {{ formatCurrency(item.package_price) }}
              </span>
            </template>

            <!-- Savings -->
            <template #[`item.savings_amount`]="{ item }">
              <VChip
                size="small"
                color="success"
                variant="tonal"
              >
                {{ formatCurrency(item.savings_amount || 0) }} ({{ parseFloat(item.savings_percentage || 0).toFixed(0) }}%)
              </VChip>
            </template>

            <!-- Status -->
            <template #[`item.is_active`]="{ item }">
              <VChip
                size="small"
                :color="item.is_active ? 'success' : 'error'"
              >
                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
              </VChip>
            </template>

            <!-- Actions -->
            <template #[`item.actions`]="{ item }">
              <div class="d-flex gap-2">
                <VBtn
                  icon="tabler-edit"
                  size="small"
                  variant="text"
                  @click="openEditDialog(item)"
                />
                <VBtn
                  icon="tabler-trash"
                  size="small"
                  variant="text"
                  color="error"
                  @click="openDeleteDialog(item)"
                />
              </div>
            </template>
          </VDataTable>

          <!-- Pagination -->
          <VDivider />
          <div class="d-flex justify-end pa-4">
            <VPagination
              v-model="currentPage"
              :length="Math.ceil(totalItems / itemsPerPage)"
              :total-visible="7"
            />
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Create/Edit Dialog -->
    <VDialog
      v-model="dialog"
      max-width="900"
      persistent
      scrollable
    >
      <VCard class="package-dialog coffee-dialog">
        <!-- Header -->
        <VCardTitle class="d-flex align-center justify-space-between coffee-header">
          <div class="d-flex align-center gap-2">
            <VIcon
              :icon="editMode ? 'tabler-edit' : 'tabler-plus'"
              class="text-white"
            />
            <span class="text-white">
              {{ editMode ? 'Edit Paket' : 'Tambah Paket Baru' }}
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

        <VCardText class="pa-6">
          <!-- Error Message -->
          <VAlert
            v-if="modalErrorMessage"
            type="error"
            closable
            class="mb-4"
            @click:close="modalErrorMessage = ''"
          >
            {{ modalErrorMessage }}
          </VAlert>

          <VForm>
            <VRow>
              <!-- Basic Info -->
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

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.name"
                  label="Nama Paket *"
                  placeholder="Contoh: Paket Hemat A"
                  variant="outlined"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="formData.package_type"
                  :items="[
                    { title: 'Fixed', value: 'fixed' },
                    { title: 'Customizable', value: 'customizable' }
                  ]"
                  item-title="title"
                  item-value="value"
                  label="Tipe Paket"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="formData.description"
                  label="Deskripsi"
                  rows="2"
                  variant="outlined"
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  v-model="formData.sku"
                  label="SKU"
                  variant="outlined"
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  v-model="formData.barcode"
                  label="Barcode"
                  variant="outlined"
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <VSelect
                  v-model="formData.category_id"
                  :items="categories"
                  item-title="name"
                  item-value="id_category"
                  label="Kategori *"
                  variant="outlined"
                />
              </VCol>

              <!-- Items -->
              <VCol cols="12">
                <VDivider class="my-4" />
                <div class="d-flex justify-space-between align-center mb-4">
                  <h6 class="text-h6 mb-0 d-flex align-center gap-2">
                    <VIcon
                      icon="tabler-package"
                      size="20"
                      class="coffee-icon"
                    />
                    Isi Paket *
                  </h6>
                  <VBtn
                    size="small"
                    prepend-icon="tabler-plus"
                    color="primary"
                    @click="productDialog = true"
                  >
                    Tambah Produk
                  </VBtn>
                </div>

                <VAlert
                  v-if="formData.items.length === 0"
                  type="info"
                  variant="tonal"
                >
                  Belum ada produk. Klik "Tambah Produk" untuk menambahkan.
                </VAlert>

                <VList v-else>
                  <VListItem
                    v-for="(item, index) in formData.items"
                    :key="index"
                    class="mb-2 border rounded"
                  >
                    <template #prepend>
                      <VIcon
                        icon="tabler-drag-drop"
                        class="cursor-move"
                      />
                    </template>

                    <VListItemTitle>
                      {{ item.product?.name }}
                    </VListItemTitle>

                    <VListItemSubtitle>
                      <div class="d-flex gap-3 align-center mt-2 flex-wrap">
                        <VTextField
                          v-model.number="item.quantity"
                          type="number"
                          label="Qty"
                          density="compact"
                          style="max-width: 100px"
                          min="0.01"
                          step="0.01"
                        />
                        <VTextField
                          v-model="item.unit"
                          label="Unit"
                          density="compact"
                          style="max-width: 100px"
                        />
                        <VTextField
                          :model-value="formatCurrency(item.product?.price || 0)"
                          label="Harga Satuan"
                          density="compact"
                          style="max-width: 150px"
                          readonly
                          hint="Dari produk"
                          persistent-hint
                        />
                        <VTextField
                          :model-value="formatCurrency((item.product?.price || 0) * item.quantity)"
                          label="Subtotal"
                          density="compact"
                          style="max-width: 150px"
                          readonly
                          class="font-weight-bold"
                        />
                        <VCheckbox
                          v-model="item.is_optional"
                          label="Optional"
                          density="compact"
                          hide-details
                        />
                      </div>
                    </VListItemSubtitle>

                    <template #append>
                      <VBtn
                        icon="tabler-trash"
                        size="small"
                        variant="text"
                        color="error"
                        @click="removeProduct(index)"
                      />
                    </template>
                  </VListItem>
                </VList>
              </VCol>

              <!-- Pricing -->
              <VCol cols="12">
                <VDivider class="my-4" />
                <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-currency-dollar"
                    size="20"
                    class="coffee-icon"
                  />
                  Harga
                </h6>
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  :model-value="formatCurrency(calculatedRegularPrice)"
                  label="Harga Normal"
                  readonly
                  variant="outlined"
                  hint="Dihitung otomatis dari item"
                  persistent-hint
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  v-model="packagePriceDisplay"
                  label="Harga Paket *"
                  variant="outlined"
                  prefix="Rp"
                  placeholder="0"
                  @input="handlePackagePriceInput"
                  hint="Masukkan harga paket"
                  persistent-hint
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  :model-value="`${formatCurrency(calculatedSavings)} (${calculatedSavingsPercentage.toFixed(1)}%)`"
                  label="Hemat"
                  readonly
                  variant="outlined"
                  color="success"
                />
              </VCol>

              <!-- Stock & Status -->
              <VCol cols="12">
                <VDivider class="my-4" />
                <h6 class="text-h6 mb-4 d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-settings"
                    size="20"
                    class="coffee-icon"
                  />
                  Stok & Status
                </h6>
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <VCheckbox
                  v-model="formData.track_stock"
                  label="Track Stok Paket"
                  density="comfortable"
                />
              </VCol>

              <VCol
                v-if="formData.track_stock"
                cols="12"
                md="3"
              >
                <VTextField
                  v-model.number="formData.stock"
                  label="Stok"
                  type="number"
                  variant="outlined"
                  min="0"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <VSelect
                  v-model="formData.status"
                  :items="[
                    { title: 'Draft', value: 'draft' },
                    { title: 'Published', value: 'published' },
                    { title: 'Archived', value: 'archived' }
                  ]"
                  item-title="title"
                  item-value="value"
                  label="Status"
                  variant="outlined"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <VCheckbox
                  v-model="formData.is_active"
                  label="Aktif"
                  density="comfortable"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn
            variant="outlined"
            class="coffee-secondary"
            @click="closeDialog"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            class="coffee-primary"
            :loading="saveLoading"
            :disabled="!canCreateEdit"
            @click="savePackage"
          >
            {{ editMode ? 'Perbarui' : 'Simpan' }} Paket
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Product Selection Dialog -->
    <VDialog
      v-model="productDialog"
      max-width="600"
    >
      <VCard>
        <VCardTitle>Pilih Produk</VCardTitle>
        <VDivider />
        <VCardText>
          <VTextField
            v-model="productSearch"
            placeholder="Cari produk..."
            prepend-inner-icon="tabler-search"
            density="comfortable"
            class="mb-4"
            clearable
          />

          <VList
            max-height="400"
            class="overflow-y-auto"
          >
            <VListItem
              v-for="product in filteredProducts"
              :key="product.id_product"
              @click="addProduct(product)"
            >
              <VListItemTitle>{{ product.name }}</VListItemTitle>
              <VListItemSubtitle>
                {{ formatCurrency(product.price) }} | Stok: {{ product.stock }}
              </VListItemSubtitle>
            </VListItem>
          </VList>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Delete Dialog -->
    <VDialog
      v-model="deleteDialog"
      max-width="400"
    >
      <VCard>
        <VCardTitle>Konfirmasi Hapus</VCardTitle>
        <VDivider />
        <VCardText>
          Apakah Anda yakin ingin menghapus paket <strong>{{ selectedPackage?.name }}</strong>?
        </VCardText>
        <VDivider />
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="outlined"
            @click="deleteDialog = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            :loading="deleteLoading"
            @click="deletePackage"
          >
            Hapus
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.cursor-move {
  cursor: move;
}
</style>
