import type { Category, CategoryFormData, Pagination } from '@/composables/useCategories'
import axios from 'axios'

// API Response interfaces
interface CategoriesResponse {
  success: boolean
  data: Category[]
  pagination: Pagination
  message?: string
}

interface CategoryResponse {
  success: boolean
  data: Category
  message?: string
}

interface ApiError {
  response?: {
    status: number
    data: {
      message?: string
      errors?: Record<string, string[]>
    }
  }
}

export class CategoriesApi {
  private static baseUrl = '/api/categories'

  /**
   * Fetch categories with pagination and filters
   */
  static async getCategories(params: {
    page: number
    per_page: number
    search?: string
    status?: string
  }): Promise<CategoriesResponse> {
    const searchParams = new URLSearchParams({
      page: params.page.toString(),
      per_page: params.per_page.toString()
    })

    if (params.search?.trim()) {
      searchParams.append('search', params.search.trim())
    }

    if (params.status && params.status !== 'all') {
      searchParams.append('status', params.status)
    }

    const response = await axios.get(`${this.baseUrl}?${searchParams.toString()}`)
    return response.data
  }

  /**
   * Create new category
   */
  static async createCategory(data: CategoryFormData): Promise<CategoryResponse> {
    const formData = this.prepareFormData(data)
    const response = await axios.post(this.baseUrl, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }

  /**
   * Update existing category
   */
  static async updateCategory(id: number, data: CategoryFormData): Promise<CategoryResponse> {
    const formData = this.prepareFormData(data)
    formData.append('_method', 'PUT')
    
    const response = await axios.post(`${this.baseUrl}/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }

  /**
   * Delete category
   */
  static async deleteCategory(id: number): Promise<CategoryResponse> {
    const response = await axios.delete(`${this.baseUrl}/${id}`)
    return response.data
  }

  /**
   * Toggle category active status
   */
  static async toggleActiveStatus(id: number): Promise<CategoryResponse> {
    const response = await axios.post(`${this.baseUrl}/${id}/toggle-active`)
    return response.data
  }

  /**
   * Get single category by ID
   */
  static async getCategory(id: number): Promise<CategoryResponse> {
    const response = await axios.get(`${this.baseUrl}/${id}`)
    return response.data
  }

  /**
   * Prepare form data for API submission
   */
  private static prepareFormData(data: CategoryFormData): FormData {
    const formData = new FormData()
    formData.append('name', data.name)
    formData.append('description', data.description)
    formData.append('active', data.active ? '1' : '0')
    
    if (data.image) {
      formData.append('image', data.image)
    }

    return formData
  }

  /**
   * Handle API errors and extract meaningful error messages
   */
  static handleError(error: ApiError): string {
    if (error.response?.status === 422 && error.response?.data?.errors) {
      const errors = error.response.data.errors
      const firstError = Object.values(errors)[0] as string[]
      return firstError[0]
    }

    return error.response?.data?.message || 'Terjadi kesalahan yang tidak diketahui'
  }

  /**
   * Validate image file
   */
  static validateImage(file: File): { valid: boolean; error?: string } {
    // Check file type
    if (!file.type.startsWith('image/')) {
      return { valid: false, error: 'File harus berupa gambar' }
    }

    // Check file size (2MB max)
    const maxSize = 2 * 1024 * 1024 // 2MB
    if (file.size > maxSize) {
      return { valid: false, error: 'Ukuran file maksimal 2MB' }
    }

    return { valid: true }
  }
}
