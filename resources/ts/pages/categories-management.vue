<script setup lang="ts">
import CategoryDialog from '@/components/categories/CategoryDialog.vue'
import CategoryGrid from '@/components/categories/CategoryGrid.vue'
import CategoryList from '@/components/categories/CategoryList.vue'
import SearchFilters from '@/components/categories/CategorySearchFilters.vue'
import StatsCards from '@/components/categories/CategoryStatsCards.vue'
import DeleteConfirmationDialog from '@/components/DeleteConfirmationDialog.vue'
import { useCategories } from '@/composables/useCategories'
// import '@styles/index.scss'
import { useDebounceFn } from '@vueuse/core'
import { onMounted, watch } from 'vue'

const {
  categories,
  loading,
  saveLoading,
  deleteLoading,
  toggleLoading,
  dialog,
  deleteDialog,
  editMode,
  selectedCategory,
  formData,
  imagePreview,
  search,
  statusFilter,
  perPage,
  viewMode,
  currentPage,
  pagination,
  successMessage,
  errorMessage,
  modalErrorMessage,
  canCreateEdit,
  totalPages,
  totalCategories,
  activeCategories,
  inactiveCategories,
  totalData,
  fetchCategories,
  handleImageUpload,
  removeImage,
  saveCategory,
  deleteCategory,
  confirmDelete,
  toggleActiveStatus,
  openCreateDialog,
  openEditDialog,
  closeDialog,
  onPageChange,
  onSearch,
  onFilterChange
} = useCategories()

const handleSearchUpdate = (value: string) => {
  search.value = value
}

// Debounced search
const debouncedSearch = useDebounceFn(() => {
  onSearch()
}, 500)

// Watch for search changes
watch(search, () => {
  debouncedSearch()
})

const handleStatusFilterUpdate = (value: string) => {
  statusFilter.value = value
  onFilterChange()
}

const handlePerPageUpdate = (value: number) => {
  perPage.value = value
  currentPage.value = 1
  onFilterChange()
}

const clearModalError = () => {
  modalErrorMessage.value = ''
}

watch(dialog, (newValue) => {
  if (!newValue) {
    modalErrorMessage.value = ''
    imagePreview.value = null
  }
})

onMounted(() => {
  fetchCategories()
})
</script>

<template>
  <div class="category-management">
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold coffee-title">Kategori Produk</h1>
        <p class="text-body-1 text-medium-emphasis coffee-subtitle">Kelola kategori produk </p>
      </div>
      <VBtn
        v-if="canCreateEdit"
        color="primary"
        prepend-icon="mdi-plus"
        @click="openCreateDialog"
      >
        Tambah Kategori
      </VBtn>
    </div>

    <VAlert
      v-if="successMessage"
      type="success"
      variant="outlined"
      class="mb-6"
      closable
      @click:close="successMessage = ''"
    >
      <template #prepend>
        <VIcon icon="mdi-check-circle" />
      </template>
      {{ successMessage }}
    </VAlert>
    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-6"
      closable
      @click:close="errorMessage = ''"
    >
      <template #prepend>
        <VIcon icon="mdi-alert-circle" />
      </template>
      {{ errorMessage }}
    </VAlert>

    <StatsCards
      :total-categories="totalCategories"
      :active-categories="activeCategories"
      :inactive-categories="inactiveCategories"
      :total-data="totalData"
    />

    <SearchFilters
      :search="search"
      :status-filter="statusFilter"
      :per-page="perPage"
      @update:search="handleSearchUpdate"
      @update:status-filter="handleStatusFilterUpdate"
      @update:per-page="handlePerPageUpdate"
      @search="onSearch"
    />

    <div class="d-flex justify-space-between align-center mb-4">
      <h2 class="text-h5 font-weight-medium">Daftar Kategori</h2>
      <VBtnToggle v-model="viewMode" mandatory variant="outlined" divided>
        <VBtn value="grid" size="small">
          <VIcon icon="mdi-view-grid" />
          Grid
        </VBtn>
        <VBtn value="list" size="small">
          <VIcon icon="mdi-view-list" />
          List
        </VBtn>
      </VBtnToggle>
    </div>

    <VCard v-if="loading" class="text-center pa-12">
      <VProgressCircular indeterminate color="primary" size="64" />
      <div class="mt-4 text-h6">Memuat data kategori...</div>
    </VCard>

    <template v-else>
      <CategoryGrid
        v-if="viewMode === 'grid'"
        :categories="categories"
        :toggle-loading="toggleLoading"
        :delete-loading="deleteLoading"
        @edit="openEditDialog"
        @delete="deleteCategory"
        @toggle="toggleActiveStatus"
        @create="openCreateDialog"
      />
      <CategoryList
        v-else-if="viewMode === 'list'"
        :categories="categories"
        :pagination="pagination"
        :loading="loading"
        :toggle-loading="toggleLoading"
        :delete-loading="deleteLoading"
        :per-page="perPage"
        :current-page="currentPage"
        @edit="openEditDialog"
        @delete="deleteCategory"
        @toggle="toggleActiveStatus"
        @update:per-page="handlePerPageUpdate"
        @update:current-page="(page) => { currentPage = page; onPageChange(page) }"
      />
    </template>

    <div v-if="viewMode === 'grid' && categories.length > 0" class="d-flex justify-center mt-6">
      <VPagination
        v-model="currentPage"
        :length="totalPages"
        :total-visible="7"
        @update:model-value="onPageChange"
      />
    </div>

    <CategoryDialog
      :is-open="dialog"
      :edit-mode="editMode"
      :form-data="formData"
      :image-preview="imagePreview"
      :error-message="modalErrorMessage"
      :save-loading="saveLoading"
      @update:is-open="dialog = $event"
      @image-upload="handleImageUpload"
      @remove-image="removeImage"
      @save="saveCategory"
      @cancel="closeDialog"
      @clear-error="clearModalError"
    />

    <DeleteConfirmationDialog
      v-model="deleteDialog"
      title="Konfirmasi Hapus Kategori"
      :item-name="selectedCategory?.name"
      :loading="deleteLoading"
      confirm-text="Hapus Kategori"
      @confirm="confirmDelete"
      @cancel="deleteDialog = false"
    />
  </div>
</template>
<style scoped lang="scss">
.category-management {
  .coffee-title {
    color: rgb(var(--v-theme-primary));
    font-family: 'Inter', sans-serif;
  }

  .coffee-subtitle {
    opacity: 0.8;
    margin-top: 4px;
  }
}
</style>

