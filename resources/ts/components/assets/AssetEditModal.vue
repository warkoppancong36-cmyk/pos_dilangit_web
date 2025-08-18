<template>
  <div
    v-if="show && asset"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    @click="$emit('close')"
  >
    <div
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white"
      @click.stop
    >
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Edit Asset</h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Asset Preview -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
          <div class="flex items-start space-x-4">
            <div class="flex-shrink-0">
              <div class="w-20 h-20 bg-white rounded border flex items-center justify-center">
                <img
                  v-if="asset.file_type === 'image' && asset.file_url"
                  :src="asset.file_url"
                  :alt="asset.alt_text || asset.original_filename"
                  class="w-full h-full object-cover rounded"
                />
                <div v-else class="text-center">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                </div>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="text-sm font-medium text-gray-900 truncate">{{ asset.original_filename }}</h4>
              <p class="text-sm text-gray-500">{{ asset.formatted_size }} • {{ asset.mime_type }}</p>
              <p class="text-sm text-gray-500">Uploaded {{ formatDate(asset.created_at) }}</p>
              <div class="mt-2 flex items-center space-x-4">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                      :class="asset.is_public ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                  {{ asset.is_public ? 'Public' : 'Private' }}
                </span>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ asset.disk }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Form -->
        <form @submit.prevent="saveChanges" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              File Name
            </label>
            <input
              v-model="formData.filename"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter filename..."
            />
            <p class="text-xs text-gray-500 mt-1">This will be the stored filename (without extension)</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Description
            </label>
            <textarea
              v-model="formData.description"
              rows="3"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter description..."
            ></textarea>
          </div>

          <div v-if="asset.file_type === 'image'">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Alt Text
            </label>
            <input
              v-model="formData.alt_text"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter alt text for accessibility..."
            />
            <p class="text-xs text-gray-500 mt-1">Used for accessibility and SEO</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Access Level
              </label>
              <select
                v-model="formData.is_public"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option :value="true">Public</option>
                <option :value="false">Private</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Storage Disk
              </label>
              <select
                v-model="formData.disk"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="public">Public</option>
                <option value="private">Private</option>
              </select>
            </div>
          </div>

          <!-- Asset Stats -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Asset Statistics</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-500">Access Count:</span>
                <span class="font-medium text-gray-900 ml-2">{{ asset.access_count }}</span>
              </div>
              <div>
                <span class="text-gray-500">File Type:</span>
                <span class="font-medium text-gray-900 ml-2">{{ asset.file_type }}</span>
              </div>
              <div>
                <span class="text-gray-500">Created:</span>
                <span class="font-medium text-gray-900 ml-2">{{ formatDate(asset.created_at) }}</span>
              </div>
              <div>
                <span class="text-gray-500">Modified:</span>
                <span class="font-medium text-gray-900 ml-2">{{ formatDate(asset.updated_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Metadata (if available) -->
          <div v-if="asset.metadata && Object.keys(asset.metadata).length" class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Metadata</h4>
            <div class="space-y-2 text-sm">
              <div v-for="(value, key) in asset.metadata" :key="key" class="flex justify-between">
                <span class="text-gray-500 capitalize">{{ key.replace(/_/g, ' ') }}:</span>
                <span class="font-medium text-gray-900">{{ value }}</span>
              </div>
            </div>
          </div>

          <!-- Error Messages -->
          <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm text-red-600">{{ error }}</p>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-4">
            <div class="flex space-x-3">
              <button
                type="button"
                @click="downloadAsset"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download
              </button>
              
              <button
                type="button"
                @click="toggleAccess"
                :class="[
                  'inline-flex items-center px-3 py-2 border shadow-sm text-sm leading-4 font-medium rounded-md',
                  asset.is_public 
                    ? 'border-red-300 text-red-700 bg-red-50 hover:bg-red-100' 
                    : 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100'
                ]"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="asset.is_public" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.11 8.11m1.768 1.768L14.121 7.636M14.121 7.636L16.95 4.807m-2.829 2.829L12 9.757"></path>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                {{ asset.is_public ? 'Make Private' : 'Make Public' }}
              </button>
            </div>

            <div class="flex items-center space-x-3">
              <button
                type="button"
                @click="$emit('close')"
                :disabled="loading"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading || !hasChanges"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="loading">Saving...</span>
                <span v-else>Save Changes</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useAssets } from '@/composables/useAssets'
import type { Asset } from '@/composables/useAssets'

// Props & Emits
interface Props {
  show: boolean
  asset: Asset | null
}

interface Emits {
  (e: 'close'): void
  (e: 'updated', asset: Asset): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Composables
const { updateAsset, downloadAsset: download, toggleAccess: toggle, loading } = useAssets()

// State
const error = ref<string | null>(null)

const formData = reactive({
  filename: '',
  description: '',
  alt_text: '',
  is_public: true,
  disk: 'public' as 'public' | 'private'
})

// Computed
const hasChanges = computed(() => {
  if (!props.asset) return false
  
  return (
    formData.filename !== props.asset.filename ||
    formData.description !== (props.asset.description || '') ||
    formData.alt_text !== (props.asset.alt_text || '') ||
    formData.is_public !== props.asset.is_public ||
    formData.disk !== props.asset.disk
  )
})

// Methods
const resetForm = () => {
  if (props.asset) {
    formData.filename = props.asset.filename
    formData.description = props.asset.description || ''
    formData.alt_text = props.asset.alt_text || ''
    formData.is_public = props.asset.is_public
    formData.disk = props.asset.disk
  }
}

const saveChanges = async () => {
  if (!props.asset || !hasChanges.value) return

  error.value = null

  try {
    const updatedAsset = await updateAsset(props.asset.id, {
      filename: formData.filename,
      description: formData.description || undefined,
      alt_text: formData.alt_text || undefined,
      is_public: formData.is_public,
      disk: formData.disk
    })

    emit('updated', updatedAsset)
  } catch (err: any) {
    error.value = err.message || 'Failed to update asset'
  }
}

const downloadAsset = async () => {
  if (props.asset) {
    try {
      await download(props.asset.id)
    } catch (err: any) {
      error.value = err.message || 'Failed to download asset'
    }
  }
}

const toggleAccess = async () => {
  if (props.asset) {
    try {
      const updatedAsset = await toggle(props.asset.id)
      emit('updated', updatedAsset)
    } catch (err: any) {
      error.value = err.message || 'Failed to toggle access'
    }
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Watch for asset changes to reset form
watch(() => props.asset, (newAsset) => {
  if (newAsset) {
    resetForm()
    error.value = null
  }
}, { immediate: true })

// Watch for show prop changes to reset error
watch(() => props.show, (newShow) => {
  if (!newShow) {
    error.value = null
  }
})
</script>
