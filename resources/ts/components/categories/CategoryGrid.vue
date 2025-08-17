<template>
  <VRow v-if="categories.length > 0">
    <VCol
      v-for="category in categories"
      :key="category.id_category"
      cols="12"
      sm="6"
      md="4"
      lg="3"
    >
      <VCard class="category-card mx-auto" max-width="320" elevation="4" hover>
        <!-- Category Image -->
        <div class="category-image-container" style="height: 200px; position: relative;">
          <VImg
            v-if="category.image"
            :src="`/storage/categories/${category.image}`"
            :alt="category.name"
            height="200"
            cover
            class="category-image"
          >
            <template #placeholder>
              <div class="d-flex align-center justify-center fill-height bg-grey-lighten-3">
                <VProgressCircular indeterminate color="primary" size="40" />
              </div>
            </template>
          </VImg>
          <div
            v-else
            class="d-flex align-center justify-center fill-height bg-gradient-primary category-default-image"
            style="height: 200px;"
          >
            <div class="text-center text-white">
              <VIcon icon="mdi-coffee" size="48" class="mb-2" />
              <div class="text-subtitle-1 font-weight-medium">{{ category.name }}</div>
            </div>
          </div>
          <VChip
            :color="category.active ? 'success' : 'error'"
            size="small"
            class="ma-2 position-absolute"
            style="top: 0; right: 0; z-index: 1;"
          >
            {{ category.active ? 'Aktif' : 'Tidak Aktif' }}
          </VChip>
        </div>
        <VCardText class="pa-4">
          <h3 class="text-h6 font-weight-bold mb-2 text-truncate">{{ category.name }}</h3>
          <p class="text-body-2 text-medium-emphasis mb-3 category-description">
            {{ category.description || 'Tidak ada deskripsi' }}
          </p>
          <div class="d-flex align-center text-caption text-medium-emphasis mb-3">
            <VIcon icon="mdi-account" size="16" class="me-1" />
            {{ category.created_by_user?.name || '-' }}
            <VSpacer />
            <VIcon icon="mdi-calendar" size="16" class="me-1" />
            {{ new Date(category.created_at).toLocaleDateString('id-ID') }}
          </div>
        </VCardText>
        <VCardActions class="pa-4 pt-0">
          <VBtn
            color="primary"
            variant="outlined"
            size="small"
            prepend-icon="mdi-pencil"
            @click="$emit('edit', category)"
          >
            Edit
          </VBtn>
          <VBtn
            :color="category.active ? 'warning' : 'success'"
            variant="outlined"
            size="small"
            :prepend-icon="category.active ? 'mdi-eye-off' : 'mdi-eye'"
            :loading="toggleLoading[category.id_category]"
            @click="$emit('toggle', category)"
          >
            {{ category.active ? 'Nonaktifkan' : 'Aktifkan' }}
          </VBtn>
          <VSpacer />
          <VBtn
            color="error"
            variant="outlined"
            size="small"
            icon="mdi-delete"
            :loading="deleteLoading"
            @click="$emit('delete', category)"
          />
        </VCardActions>
      </VCard>
    </VCol>
  </VRow>
  <VCard v-else class="text-center pa-12">
    <VIcon icon="mdi-shape-outline" size="120" color="grey-lighten-2" class="mb-4" />
    <h3 class="text-h5 mb-2">Belum Ada Kategori</h3>
    <p class="text-body-1 text-medium-emphasis mb-4">
      Mulai dengan menambahkan kategori produk pertama Anda
    </p>
    <VBtn
      color="primary"
      size="large"
      prepend-icon="mdi-plus"
      @click="$emit('create')"
    >
      Tambah Kategori Pertama
    </VBtn>
  </VCard>
</template>

<script setup lang="ts">
import type { Category } from '@/composables/useCategories'

interface Props {
  categories: Category[]
  toggleLoading: Record<number, boolean>
  deleteLoading: boolean
}

interface Emits {
  (e: 'edit', category: Category): void
  (e: 'delete', category: Category): void
  (e: 'toggle', category: Category): void
  (e: 'create'): void
}

defineProps<Props>()
defineEmits<Emits>()
</script>

<style scoped>
.category-description {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
  max-height: 2.8em;
}

.bg-gradient-primary {
  background: linear-gradient(135deg, #B07124 0%, #D4AC71 100%);
}

.category-card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  border: 1px solid rgba(176, 113, 36, 0.1);
}

.category-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(176, 113, 36, 0.15) !important;
}

.category-image {
  transition: transform 0.3s ease-in-out;
}

.category-card:hover .category-image {
  transform: scale(1.05);
}

.category-description {
  color: #8D7053 !important;
  line-height: 1.4;
}

.category-default-image {
  background: linear-gradient(135deg, #B07124 0%, #D4AC71 100%);
}

/* Dark theme adjustments */
.v-theme--dark .category-card {
  border: 1px solid rgba(212, 172, 113, 0.1);
}

.v-theme--dark .category-card:hover {
  box-shadow: 0 8px 25px rgba(212, 172, 113, 0.2) !important;
}

.v-theme--dark .category-description {
  color: #B8946A !important;
}
</style>
