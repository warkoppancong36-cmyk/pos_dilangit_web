<script setup lang="ts">
import type { ProductRecipe } from '@/composables/useProductRecipes'
import { formatCurrency } from '@/utils/helpers'
import { ref } from 'vue'

interface Props {
  recipes: ProductRecipe[]
  loading?: boolean
  canEdit?: boolean
}

interface Emits {
  (e: 'edit-recipe', recipe: ProductRecipe): void
  (e: 'delete-recipe', recipe: ProductRecipe): void
  (e: 'duplicate-recipe', recipe: ProductRecipe): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  canEdit: true
})

const emit = defineEmits<Emits>()

const getDifficultyColor = (difficulty?: string) => {
  switch (difficulty) {
    case 'easy': return 'success'
    case 'medium': return 'warning'
    case 'hard': return 'error'
    default: return 'info'
  }
}

const getDifficultyText = (difficulty?: string) => {
  switch (difficulty) {
    case 'easy': return 'Mudah'
    case 'medium': return 'Sedang'
    case 'hard': return 'Sulit'
    default: return 'Tidak diketahui'
  }
}

const getDifficultyIcon = (difficulty?: string) => {
  switch (difficulty) {
    case 'easy': return 'tabler-thumb-up'
    case 'medium': return 'tabler-alert-triangle'
    case 'hard': return 'tabler-flame'
    default: return 'tabler-help'
  }
}

const getRecipeStatus = (recipe: ProductRecipe) => {
  if (!recipe.active) {
    return { color: 'error', text: 'Nonaktif', icon: 'tabler-x' }
  }
  
  // Check if all items are available
  const hasUnavailableItems = recipe.items.some(item => 
    !item.item || item.item.current_stock < item.quantity
  )
  
  if (hasUnavailableItems) {
    return { color: 'warning', text: 'Stok Terbatas', icon: 'tabler-alert-triangle' }
  }
  
  return { color: 'success', text: 'Tersedia', icon: 'tabler-check' }
}

const formatPreparationTime = (minutes?: number) => {
  if (!minutes) return '-'
  if (minutes < 60) return `${minutes} menit`
  const hours = Math.floor(minutes / 60)
  const remainingMinutes = minutes % 60
  return remainingMinutes > 0 ? `${hours}j ${remainingMinutes}m` : `${hours} jam`
}

const selectedPanels = ref<number[]>([])
</script>

<template>
  <VCard class="recipe-list-card">
    <VCardTitle class="d-flex align-center gap-2 coffee-header">
      <VIcon icon="tabler-chef-hat" class="coffee-icon" />
      <span>Resep Produk</span>
      <VSpacer />
      <VChip
        :color="recipes.length > 0 ? 'success' : 'default'"
        size="small"
        variant="tonal"
      >
        {{ recipes.length }} Resep
      </VChip>
    </VCardTitle>

    <VDivider />

    <VCardText class="pa-0">
      <!-- Loading State -->
      <div v-if="loading" class="text-center pa-8">
        <VProgressCircular
          color="primary"
          indeterminate
          size="64"
        />
        <div class="mt-4 text-h6">Memuat resep...</div>
      </div>

      <!-- Empty State -->
      <div v-else-if="recipes.length === 0" class="text-center pa-8">
        <VIcon
          icon="tabler-chef-hat-off"
          size="64"
          class="text-disabled mb-4"
        />
        <div class="text-h6 text-disabled mb-2">Belum Ada Resep</div>
        <div class="text-body-2 text-disabled">
          Tambahkan resep untuk produk ini agar dapat diproduksi
        </div>
      </div>

      <!-- Recipe List -->
      <div v-else>
        <VExpansionPanels
          v-model="selectedPanels"
          multiple
          variant="accordion"
        >
          <VExpansionPanel
            v-for="(recipe, index) in recipes"
            :key="recipe.id || index"
            :value="index"
            class="recipe-panel"
          >
            <VExpansionPanelTitle class="pa-4">
              <VRow align="center" no-gutters>
                <VCol cols="auto" class="me-3">
                  <VAvatar
                    :color="getDifficultyColor(recipe.difficulty_level)"
                    size="40"
                    variant="tonal"
                  >
                    <VIcon :icon="getDifficultyIcon(recipe.difficulty_level)" />
                  </VAvatar>
                </VCol>
                
                <VCol>
                  <div class="text-subtitle-1 font-weight-bold">
                    {{ recipe.name }}
                  </div>
                  <div class="text-caption text-medium-emphasis d-flex align-center gap-2">
                    <VIcon icon="tabler-clock" size="16" />
                    {{ formatPreparationTime(recipe.preparation_time) }}
                    <VIcon icon="tabler-users" size="16" class="ms-2" />
                    {{ recipe.portion_size }} {{ recipe.portion_unit }}
                  </div>
                </VCol>

                <VCol cols="auto">
                  <div class="text-end">
                    <VChip
                      :color="getRecipeStatus(recipe).color"
                      size="small"
                      variant="tonal"
                      class="mb-1"
                    >
                      <VIcon
                        :icon="getRecipeStatus(recipe).icon"
                        size="16"
                        class="me-1"
                      />
                      {{ getRecipeStatus(recipe).text }}
                    </VChip>
                    <div class="text-h6 font-weight-bold">
                      {{ formatCurrency(recipe.total_cost || 0) }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ formatCurrency((recipe.total_cost || 0) / (recipe.portion_size || 1)) }}/porsi
                    </div>
                  </div>
                </VCol>
              </VRow>
            </VExpansionPanelTitle>

            <VExpansionPanelText class="pa-4 pt-0">
              <VDivider class="mb-4" />
              
              <!-- Recipe Description -->
              <div v-if="recipe.description" class="mb-4">
                <div class="text-subtitle-2 font-weight-bold mb-2">Deskripsi:</div>
                <div class="text-body-2">{{ recipe.description }}</div>
              </div>

              <!-- Recipe Items -->
              <div class="mb-4">
                <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center gap-2">
                  <VIcon icon="tabler-list-details" size="18" />
                  Bahan-bahan ({{ recipe.items.length }})
                </div>
                
                <VRow>
                  <VCol
                    v-for="(item, itemIndex) in recipe.items"
                    :key="itemIndex"
                    cols="12"
                    md="6"
                  >
                    <VCard variant="outlined" class="pa-3">
                      <div class="d-flex align-center gap-3">
                        <VAvatar
                          :color="item.item && item.item.current_stock >= item.quantity ? 'success' : 'error'"
                          size="32"
                          variant="tonal"
                        >
                          <VIcon 
                            :icon="item.item && item.item.current_stock >= item.quantity ? 'tabler-check' : 'tabler-alert-triangle'"
                            size="18"
                          />
                        </VAvatar>
                        
                        <div class="flex-grow-1">
                          <div class="text-subtitle-2 font-weight-bold">
                            {{ item.item?.name || 'Item tidak ditemukan' }}
                          </div>
                          <div class="text-caption text-medium-emphasis">
                            {{ item.quantity }} {{ item.unit }}
                            <span v-if="item.item" class="ms-2">
                              • Stock: {{ item.item.current_stock }} {{ item.unit }}
                            </span>
                          </div>
                          <div v-if="item.notes" class="text-caption text-info mt-1">
                            <VIcon icon="tabler-note" size="12" class="me-1" />
                            {{ item.notes }}
                          </div>
                        </div>
                        
                        <div class="text-end">
                          <div class="text-subtitle-2 font-weight-bold">
                            {{ item.item ? formatCurrency(item.item.cost_per_unit * item.quantity) : '-' }}
                          </div>
                        </div>
                      </div>
                    </VCard>
                  </VCol>
                </VRow>
              </div>

              <!-- Instructions -->
              <div v-if="recipe.instructions && recipe.instructions.length > 0" class="mb-4">
                <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center gap-2">
                  <VIcon icon="tabler-clipboard-list" size="18" />
                  Instruksi Pembuatan
                </div>
                
                <VTimeline density="compact" align="start">
                  <VTimelineItem
                    v-for="(instruction, instructionIndex) in recipe.instructions"
                    :key="instructionIndex"
                    dot-color="primary"
                    size="small"
                  >
                    <VCard variant="outlined" class="pa-3">
                      <div class="d-flex align-start gap-2">
                        <VChip
                          color="primary"
                          size="x-small"
                          class="mt-1"
                        >
                          {{ instructionIndex + 1 }}
                        </VChip>
                        <div class="text-body-2">{{ instruction }}</div>
                      </div>
                    </VCard>
                  </VTimelineItem>
                </VTimeline>
              </div>

              <!-- Actions -->
              <div class="d-flex gap-2 justify-end" v-if="canEdit">
                <VBtn
                  variant="outlined"
                  color="info"
                  size="small"
                  prepend-icon="tabler-copy"
                  @click="emit('duplicate-recipe', recipe)"
                >
                  Duplikat
                </VBtn>
                <VBtn
                  variant="outlined"
                  color="primary"
                  size="small"
                  prepend-icon="tabler-edit"
                  @click="emit('edit-recipe', recipe)"
                >
                  Edit
                </VBtn>
                <VBtn
                  variant="outlined"
                  color="error"
                  size="small"
                  prepend-icon="tabler-trash"
                  @click="emit('delete-recipe', recipe)"
                >
                  Hapus
                </VBtn>
              </div>
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>
      </div>
    </VCardText>
  </VCard>
</template>

<style scoped>
.recipe-list-card {
  border-radius: 12px;
}

.recipe-panel {
  border: none !important;
}

.recipe-panel :deep(.v-expansion-panel-title) {
  border-radius: 8px;
  margin-bottom: 8px;
}

.recipe-panel :deep(.v-expansion-panel-text__wrapper) {
  padding: 0;
}

.coffee-header {
  background: linear-gradient(135deg, #B07124 0%, #8D7053 100%);
  color: white;
}

.coffee-icon {
  color: #B07124;
}
</style>
