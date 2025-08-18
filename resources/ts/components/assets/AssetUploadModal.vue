<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    @click="$emit('close')"
  >
    <div
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white"
      @click.stop
    >
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Upload Assets</h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Upload Area -->
        <div
          class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-gray-400 transition-colors"
          :class="{ 'border-blue-400 bg-blue-50': isDragOver }"
          @drop="handleDrop"
          @dragover.prevent="isDragOver = true"
          @dragleave="isDragOver = false"
          @dragenter.prevent
        >
          <div v-if="!selectedFiles.length">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
              <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <p class="mt-2 text-sm text-gray-600">
              <span class="font-medium text-blue-600 hover:text-blue-500 cursor-pointer" @click="triggerFileSelect">
                Click to upload
              </span>
              or drag and drop
            </p>
            <p class="text-xs text-gray-500">PNG, JPG, PDF, DOC up to 10MB each</p>
          </div>

          <!-- Selected Files Preview -->
          <div v-else class="space-y-3">
            <h4 class="text-sm font-medium text-gray-900">Selected Files ({{ selectedFiles.length }})</h4>
            <div class="max-h-48 overflow-y-auto space-y-2">
              <div
                v-for="(file, index) in selectedFiles"
                :key="index"
                class="flex items-center justify-between p-3 bg-gray-50 rounded border"
              >
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0">
                    <img
                      v-if="file.type.startsWith('image/')"
                      :src="getFilePreview(file)"
                      class="h-10 w-10 rounded object-cover"
                      alt="Preview"
                    />
                    <div v-else class="h-10 w-10 bg-gray-200 rounded flex items-center justify-center">
                      <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                      </svg>
                    </div>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ file.name }}</p>
                    <p class="text-sm text-gray-500">{{ formatFileSize(file.size) }}</p>
                  </div>
                </div>
                <button
                  @click="removeFile(index)"
                  class="text-red-400 hover:text-red-600"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
            </div>
            <button
              @click="triggerFileSelect"
              class="text-sm text-blue-600 hover:text-blue-500"
            >
              Add more files
            </button>
          </div>

          <input
            ref="fileInput"
            type="file"
            multiple
            class="hidden"
            @change="handleFileSelect"
            accept="image/*,.pdf,.doc,.docx,.txt,.xlsx,.zip,.rar"
          />
        </div>

        <!-- Upload Options -->
        <div v-if="selectedFiles.length" class="mt-6 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Storage Type</label>
              <select
                v-model="uploadOptions.disk"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="public">Public (Accessible via URL)</option>
                <option value="private">Private (Secure access only)</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Access Level</label>
              <select
                v-model="uploadOptions.is_public"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option :value="true">Public</option>
                <option :value="false">Private</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
            <textarea
              v-model="uploadOptions.description"
              rows="3"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter a description for these assets..."
            ></textarea>
          </div>
        </div>

        <!-- Progress Bar -->
        <div v-if="uploading" class="mt-6">
          <div class="flex justify-between text-sm text-gray-600 mb-2">
            <span>Uploading...</span>
            <span>{{ uploadProgress }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>
        </div>

        <!-- Error Messages -->
        <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
          <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 mt-6">
          <button
            @click="$emit('close')"
            :disabled="uploading"
            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Cancel
          </button>
          <button
            @click="uploadFiles"
            :disabled="!selectedFiles.length || uploading"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="uploading">Uploading...</span>
            <span v-else>Upload {{ selectedFiles.length }} file{{ selectedFiles.length !== 1 ? 's' : '' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { useAssets } from '@/composables/useAssets'
import type { AssetUpload } from '@/composables/useAssets'

// Props & Emits
interface Props {
  show: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'uploaded', assets: any[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Composables
const { uploadAsset, bulkUpload, loading } = useAssets()

// State
const selectedFiles = ref<File[]>([])
const isDragOver = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const error = ref<string | null>(null)
const fileInput = ref<HTMLInputElement>()

const uploadOptions = reactive({
  disk: 'public' as 'public' | 'private',
  is_public: true,
  description: ''
})

// Methods
const triggerFileSelect = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    addFiles(Array.from(target.files))
  }
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
  
  if (event.dataTransfer?.files) {
    addFiles(Array.from(event.dataTransfer.files))
  }
}

const addFiles = (files: File[]) => {
  error.value = null
  
  // Filter valid files and check size limits
  const validFiles = files.filter(file => {
    const maxSize = 10 * 1024 * 1024 // 10MB
    if (file.size > maxSize) {
      error.value = `File "${file.name}" exceeds 10MB limit`
      return false
    }
    return true
  })

  selectedFiles.value.push(...validFiles)
}

const removeFile = (index: number) => {
  selectedFiles.value.splice(index, 1)
}

const getFilePreview = (file: File): string => {
  return URL.createObjectURL(file)
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const uploadFiles = async () => {
  if (!selectedFiles.value.length) return

  uploading.value = true
  uploadProgress.value = 0
  error.value = null

  try {
    const uploads: AssetUpload[] = selectedFiles.value.map(file => ({
      file,
      description: uploadOptions.description || undefined,
      is_public: uploadOptions.is_public,
      disk: uploadOptions.disk
    }))

    // Use bulk upload for multiple files
    if (uploads.length > 1) {
      const uploadedAssets = await bulkUpload(uploads)
      emit('uploaded', uploadedAssets)
    } else {
      const uploadedAsset = await uploadAsset(uploads[0])
      emit('uploaded', [uploadedAsset])
    }

    // Reset form
    selectedFiles.value = []
    uploadOptions.description = ''
    uploadProgress.value = 100
    
  } catch (err: any) {
    error.value = err.message || 'Upload failed'
  } finally {
    uploading.value = false
    uploadProgress.value = 0
  }
}

// Cleanup object URLs when component unmounts
const cleanup = () => {
  selectedFiles.value.forEach(file => {
    if (file.type.startsWith('image/')) {
      URL.revokeObjectURL(getFilePreview(file))
    }
  })
}

// Watch for show prop changes to reset state
watch(() => props.show, (newShow) => {
  if (!newShow) {
    cleanup()
    selectedFiles.value = []
    error.value = null
    uploadProgress.value = 0
    uploading.value = false
  }
})
</script>
