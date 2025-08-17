// Product Recipe Composable - State Management for Product-Item Relations
import type { Item } from '@/composables/useItems'
import { computed, reactive, ref } from 'vue'

// Types for Product Recipe System
export interface ProductRecipeItem {
  id?: number
  product_id: number
  item_id: number
  quantity: number
  unit: string
  notes?: string
  item?: Item
}

export interface ProductRecipe {
  id?: number
  product_id: number
  name: string
  description?: string
  items: ProductRecipeItem[]
  total_cost?: number
  portion_size?: number
  portion_unit?: string
  preparation_time?: number
  difficulty_level?: 'easy' | 'medium' | 'hard'
  instructions?: string[]
  active: boolean
  created_at?: string
  updated_at?: string
}

export interface ProductRecipeFormData {
  id?: number
  product_id: number
  name: string
  description?: string
  items: ProductRecipeItem[]
  portion_size?: number
  portion_unit?: string
  preparation_time?: number
  difficulty_level?: 'easy' | 'medium' | 'hard'
  instructions?: string[]
  active?: boolean
}

export interface RecipeStats {
  total_recipes: number
  active_recipes: number
  total_items_used: number
  avg_cost_per_recipe: number
}

export function useProductRecipes() {
  // State
  const recipesList = ref<ProductRecipe[]>([])
  const availableItems = ref<Item[]>([])
  const loading = ref(false)
  const saveLoading = ref(false)
  const deleteLoading = ref(false)
  const stats = ref<RecipeStats>({
    total_recipes: 0,
    active_recipes: 0,
    total_items_used: 0,
    avg_cost_per_recipe: 0
  })

  // Dialog states
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const editMode = ref(false)
  const selectedRecipe = ref<ProductRecipe | null>(null)

  // Form data
  const formData = reactive<ProductRecipeFormData>({
    product_id: 0,
    name: '',
    description: '',
    items: [],
    portion_size: 1,
    portion_unit: 'porsi',
    preparation_time: 30,
    difficulty_level: 'medium',
    instructions: [],
    active: true
  })

  // Messages
  const errorMessage = ref('')
  const successMessage = ref('')
  const modalErrorMessage = ref('')

  // Computed
  const canCreateEdit = computed(() => !loading.value)

  // Mock data in localStorage for persistence
  const STORAGE_KEY = 'product_recipes'

  const getStoredRecipes = (): ProductRecipe[] => {
    try {
      const stored = localStorage.getItem(STORAGE_KEY)
      const recipes = stored ? JSON.parse(stored) : []
      console.log('📖 Loaded recipes from localStorage:', recipes.length, 'recipes')
      return recipes
    } catch (error) {
      console.error('❌ Failed to load recipes from localStorage:', error)
      return []
    }
  }

  const saveToStorage = (recipes: ProductRecipe[]) => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(recipes))
      console.log('✅ Recipes saved to localStorage:', recipes.length, 'recipes')
      console.log('Storage key:', STORAGE_KEY)
    } catch (error) {
      console.error('❌ Failed to save recipes to localStorage:', error)
    }
  }

  // Helper functions
  const calculateRecipeCost = (recipe: ProductRecipeFormData): number => {
    return recipe.items.reduce((total, item) => {
      const itemData = availableItems.value.find(i => i.id_item === item.item_id)
      return total + (item.quantity * (itemData?.cost_per_unit || 0))
    }, 0)
  }

  const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount)
  }

  const resetForm = () => {
    Object.assign(formData, {
      id: undefined,
      product_id: 0,
      name: '',
      description: '',
      items: [],
      portion_size: 1,
      portion_unit: 'porsi',
      preparation_time: 30,
      difficulty_level: 'medium',
      instructions: [],
      active: true
    })
  }

  // API Functions
  const fetchRecipes = async (productId?: number) => {
    if (!productId) return
    
    loading.value = true
    errorMessage.value = ''
    
    try {
      // Simulate API call delay
      await new Promise(resolve => setTimeout(resolve, 500))
      
      // Get from localStorage
      const allRecipes = getStoredRecipes()
      recipesList.value = allRecipes.filter(r => r.product_id === productId)
      
      successMessage.value = 'Resep berhasil dimuat'
    } catch (error: any) {
      console.error('Failed to fetch recipes:', error)
      errorMessage.value = 'Gagal memuat data resep'
      recipesList.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchAvailableItems = async () => {
    try {
      // Mock items data
      availableItems.value = [
        { id_item: 1, item_code: 'KOPI001', name: 'Kopi Arabica', unit: 'gram', cost_per_unit: 50, current_stock: 1000, minimum_stock: 100, active: true },
        { id_item: 2, item_code: 'SUSU001', name: 'Susu Segar', unit: 'ml', cost_per_unit: 15, current_stock: 2000, minimum_stock: 200, active: true },
        { id_item: 3, item_code: 'GULA001', name: 'Gula Pasir', unit: 'gram', cost_per_unit: 12, current_stock: 5000, minimum_stock: 500, active: true },
        { id_item: 4, item_code: 'CHOC001', name: 'Cokelat Bubuk', unit: 'gram', cost_per_unit: 80, current_stock: 500, minimum_stock: 50, active: true }
      ] as Item[]
    } catch (error: any) {
      console.error('Failed to fetch available items:', error)
      availableItems.value = []
    }
  }

  const fetchStats = async () => {
    try {
      const productId = formData.product_id
      if (!productId) return

      const allRecipes = getStoredRecipes()
      const productRecipes = allRecipes.filter(r => r.product_id === productId)
      const activeRecipes = productRecipes.filter(r => r.active)
      
      const uniqueItems = new Set(productRecipes.flatMap(r => r.items.map(i => i.item_id)))
      const avgCost = activeRecipes.reduce((sum, r) => sum + (r.total_cost || 0), 0) / (activeRecipes.length || 1)

      stats.value = {
        total_recipes: productRecipes.length,
        active_recipes: activeRecipes.length,
        total_items_used: uniqueItems.size,
        avg_cost_per_recipe: avgCost
      }
    } catch (error: any) {
      console.error('Failed to fetch stats:', error)
    }
  }

  const saveRecipe = async () => {
    saveLoading.value = true
    modalErrorMessage.value = ''
    
    try {
      // Simulate API call delay
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      const allRecipes = getStoredRecipes()
      
      if (editMode.value && selectedRecipe.value?.id) {
        // Update existing recipe
        const index = allRecipes.findIndex(r => r.id === selectedRecipe.value?.id)
        if (index !== -1) {
          allRecipes[index] = {
            ...formData,
            id: selectedRecipe.value.id,
            total_cost: calculateRecipeCost(formData),
            updated_at: new Date().toISOString()
          } as ProductRecipe
        }
        successMessage.value = 'Resep berhasil diperbarui'
      } else {
        // Create new recipe
        const newRecipe: ProductRecipe = {
          ...formData,
          id: Date.now(),
          total_cost: calculateRecipeCost(formData),
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        } as ProductRecipe
        allRecipes.unshift(newRecipe)
        successMessage.value = 'Resep berhasil ditambahkan'
      }
      
      console.log('💾 Saving recipe data:', formData)
      console.log('📦 Total recipes to save:', allRecipes.length)
      
      saveToStorage(allRecipes)
      await fetchRecipes(formData.product_id)
      await fetchStats()
      closeDialog()
    } catch (error: any) {
      console.error('Failed to save recipe:', error)
      modalErrorMessage.value = 'Gagal menyimpan resep'
    } finally {
      saveLoading.value = false
    }
  }

  const deleteRecipe = async () => {
    if (!selectedRecipe.value) return
    
    deleteLoading.value = true
    try {
      // Simulate API call delay
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      const allRecipes = getStoredRecipes()
      const filteredRecipes = allRecipes.filter(r => r.id !== selectedRecipe.value?.id)
      
      saveToStorage(filteredRecipes)
      successMessage.value = 'Resep berhasil dihapus'
      
      await fetchRecipes(formData.product_id)
      await fetchStats()
      
      deleteDialog.value = false
      selectedRecipe.value = null
    } catch (error: any) {
      console.error('Failed to delete recipe:', error)
      errorMessage.value = 'Gagal menghapus resep'
    } finally {
      deleteLoading.value = false
    }
  }

  // Dialog actions
  const openCreateDialog = (productId: number) => {
    editMode.value = false
    resetForm()
    formData.product_id = productId
    dialog.value = true
  }

  const openEditDialog = (recipe: ProductRecipe) => {
    editMode.value = true
    selectedRecipe.value = recipe
    Object.assign(formData, {
      ...recipe,
      items: [...recipe.items]
    })
    dialog.value = true
  }

  const openDeleteDialog = (recipe: ProductRecipe) => {
    selectedRecipe.value = recipe
    deleteDialog.value = true
  }

  const closeDialog = () => {
    dialog.value = false
    modalErrorMessage.value = ''
    setTimeout(() => {
      resetForm()
      selectedRecipe.value = null
      editMode.value = false
    }, 200)
  }

  const clearModalError = () => {
    modalErrorMessage.value = ''
  }

  return {
    // Recipe state
    recipesList,
    availableItems,
    loading,
    saveLoading,
    deleteLoading,
    stats,
    dialog,
    deleteDialog,
    editMode,
    selectedRecipe,
    formData,
    errorMessage,
    successMessage,
    modalErrorMessage,
    
    // Computed
    canCreateEdit,
    
    // Helper functions
    calculateRecipeCost,
    formatCurrency,
    
    // Methods
    fetchRecipes,
    fetchAvailableItems,
    fetchStats,
    saveRecipe,
    deleteRecipe,
    openCreateDialog,
    openEditDialog,
    openDeleteDialog,
    closeDialog,
    clearModalError
  }
}
