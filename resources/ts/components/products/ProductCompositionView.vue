<script setup lang="ts">
import type { ProductRecipe } from '@/composables/useProductRecipes'
import { formatCurrency } from '@/utils/helpers'
import { ref } from 'vue'

interface Props {
  recipes: ProductRecipe[]
  loading?: boolean
}

interface Emits {
  (e: 'edit-composition', composition: ProductRecipe): void
  (e: 'delete-composition', composition: ProductRecipe): void
  (e: 'duplicate-composition', composition: ProductRecipe): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<Emits>()

const searchQuery = ref('')
const filterBy = ref('all')

// Group recipes by product
const groupedCompositions = computed(() => {
  const grouped: Record<string, { product: any, compositions: ProductRecipe[] }> = {}
  
  props.recipes.forEach(recipe => {
    const productId = recipe.product_id.toString()
    if (!grouped[productId]) {
      grouped[productId] = {
        product: {
          id: recipe.product_id,
          name: `Product ${recipe.product_id}`, // This should come from actual product data
          code: `PRD-${recipe.product_id}`,
          image_url: null
        },
        compositions: []
      }
    }
    grouped[productId].compositions.push(recipe)
  })
  
  return Object.values(grouped)
})

const getCompositionStatus = (composition: ProductRecipe) => {
  if (!composition.active) {
    return { color: 'error', text: 'Nonaktif', icon: 'tabler-x' }
  }
  
  // Check if all items are available
  const hasUnavailableItems = composition.items.some(item => 
    !item.item || item.item.current_stock < item.quantity
  )
  
  if (hasUnavailableItems) {
    return { color: 'warning', text: 'Stok Terbatas', icon: 'tabler-alert-triangle' }
  }
  
  return { color: 'success', text: 'Stok Aman', icon: 'tabler-check' }
}

const getTotalItemsCount = (composition: ProductRecipe) => {
  return composition.items.length
}
</script>

<template>
  <div class="composition-view">
    <!-- Header with filters -->
    <VCard class="mb-6">
      <VCardTitle class="d-flex align-center justify-between">
        <div>
          <h2 class="text-h5 font-weight-bold">Komposisi Produk</h2>
          <p class="text-body-2 text-medium-emphasis">
            Lihat dan kelola komposisi semua produk
          </p>
        </div>
        <VChip color="primary" variant="tonal">
          {{ recipes.length }} Total Komposisi
        </VChip>
      </VCardTitle>
      
      <VDivider />
      
      <VCardText>
        <VRow align="center">
          <VCol cols="12" md="4">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari produk..."
              prepend-inner-icon="tabler-search"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="3">
            <VSelect
              v-model="filterBy"
              :items="[
                { title: 'Semua', value: 'all' },
                { title: 'Stok Aman', value: 'available' },
                { title: 'Stok Terbatas', value: 'limited' },
                { title: 'Nonaktif', value: 'inactive' }
              ]"
              label="Filter Status"
              variant="outlined"
              density="compact"
              hide-details
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Loading State -->
    <div v-if="loading" class="text-center pa-8">
      <VProgressCircular
        color="primary"
        indeterminate
        size="64"
      />
      <div class="mt-4 text-h6">Memuat komposisi...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="recipes.length === 0" class="text-center pa-12">
      <VIcon
        icon="tabler-chef-hat-off"
        size="96"
        class="text-disabled mb-6"
      />
      <div class="text-h5 font-weight-bold mb-3">Belum Ada Komposisi</div>
      <div class="text-body-1 text-medium-emphasis mb-6">
        Belum ada komposisi produk yang dibuat
      </div>
    </div>

    <!-- Composition Cards -->
    <div v-else class="composition-grid">
      <VRow>
        <VCol
          v-for="group in groupedCompositions"
          :key="group.product.id"
          cols="12"
          md="6"
          lg="4"
        >
          <VCard class="composition-card h-100">
            <!-- Product Header -->
            <VCardTitle class="pa-4 pb-2">
              <div class="d-flex align-center">
                <VAvatar
                  :image="group.product.image_url"
                  size="40"
                  class="me-3"
                >
                  <VIcon v-if="!group.product.image_url" icon="mdi-coffee" />
                </VAvatar>
                <div>
                  <div class="text-subtitle-1 font-weight-bold">
                    {{ group.product.name }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    {{ group.product.code }}
                  </div>
                </div>
              </div>
            </VCardTitle>

            <VDivider />

            <!-- Composition Items -->
            <VCardText class="pa-0">
              <div class="pa-3">
                <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
                  <VIcon icon="tabler-list-details" size="18" class="me-2" />
                  Item Komposisi ({{ getTotalItemsCount(group.compositions[0]) }})
                </div>

                <!-- Show items from first composition (assuming one composition per product for display) -->
                <div v-if="group.compositions[0]">
                  <div
                    v-for="(item, index) in group.compositions[0].items.slice(0, 3)"
                    :key="index"
                    class="composition-item mb-2"
                  >
                    <div class="d-flex align-center justify-between">
                      <div class="d-flex align-center">
                        <VAvatar
                          :color="item.item && item.item.current_stock >= item.quantity ? 'success' : 'error'"
                          size="24"
                          variant="tonal"
                          class="me-2"
                        >
                          <VIcon 
                            :icon="item.item && item.item.current_stock >= item.quantity ? 'tabler-check' : 'tabler-alert-triangle'"
                            size="12"
                          />
                        </VAvatar>
                        <div>
                          <div class="text-body-2 font-weight-medium">
                            {{ item.item?.name || 'Item tidak ditemukan' }}
                          </div>
                          <div class="text-caption text-medium-emphasis">
                            {{ item.quantity }} {{ item.unit }}
                            <span v-if="item.item" class="ms-2">
                              • Stok: {{ item.item.current_stock }} {{ item.unit }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Show more indicator -->
                  <div v-if="group.compositions[0].items.length > 3" class="text-center mt-2">
                    <VChip size="small" variant="tonal">
                      +{{ group.compositions[0].items.length - 3 }} item lainnya
                    </VChip>
                  </div>
                </div>
              </div>
            </VCardText>

            <VDivider />

            <!-- Card Footer -->
            <VCardActions class="pa-4">
              <div class="d-flex align-center justify-between w-100">
                <div>
                  <VChip
                    :color="getCompositionStatus(group.compositions[0]).color"
                    size="small"
                    variant="tonal"
                  >
                    <VIcon
                      :icon="getCompositionStatus(group.compositions[0]).icon"
                      size="14"
                      class="me-1"
                    />
                    {{ getCompositionStatus(group.compositions[0]).text }}
                  </VChip>
                </div>
                <div class="d-flex gap-1">
                  <VBtn
                    icon="tabler-copy"
                    size="small"
                    variant="text"
                    color="info"
                    @click="emit('duplicate-composition', group.compositions[0])"
                  />
                  <VBtn
                    icon="tabler-edit"
                    size="small"
                    variant="text"
                    color="primary"
                    @click="emit('edit-composition', group.compositions[0])"
                  />
                  <VBtn
                    icon="tabler-trash"
                    size="small"
                    variant="text"
                    color="error"
                    @click="emit('delete-composition', group.compositions[0])"
                  />
                </div>
              </div>
            </VCardActions>

            <!-- Setting HPP Button -->
            <VCardActions class="pa-4 pt-0">
              <VBtn
                block
                variant="outlined"
                color="primary"
                prepend-icon="tabler-calculator"
              >
                Setting HPP
                <template #append>
                  <div class="text-subtitle-2 font-weight-bold">
                    Harga: {{ group.compositions[0] ? formatCurrency(group.compositions[0].total_cost || 0) : '-' }}
                  </div>
                </template>
              </VBtn>
            </VCardActions>
          </VCard>
        </VCol>
      </VRow>
    </div>
  </div>
</template>

<style scoped>
.composition-view {
  .composition-grid {
    .composition-card {
      transition: all 0.3s ease;
      border: 1px solid rgb(var(--v-theme-outline));
    }

    .composition-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }
  }

  .composition-item {
    padding: 8px;
    border-radius: 8px;
    background: rgb(var(--v-theme-surface-variant));
  }
}
</style>
