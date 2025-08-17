import type { PaginatedPpnResponse, Ppn, PpnFormData } from '@/composables/usePpn'
import axios from 'axios'

export interface PpnListParams {
  page?: number
  per_page?: number
  search?: string
  active?: boolean
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

export class PpnApi {
  private static handleError(error: any): never {
    const message = error.response?.data?.message || error.message || 'Terjadi kesalahan'
    throw new Error(message)
  }

  static async getPpnList(params: PpnListParams = {}): Promise<PaginatedPpnResponse> {
    try {
      const searchParams = new URLSearchParams()
      
      if (params.page) searchParams.append('page', params.page.toString())
      if (params.per_page) searchParams.append('per_page', params.per_page.toString())
      if (params.search) searchParams.append('search', params.search)
      if (params.active !== undefined) searchParams.append('active', params.active.toString())
      if (params.sort_by) searchParams.append('sort_by', params.sort_by)
      if (params.sort_order) searchParams.append('sort_order', params.sort_order)

      const response = await axios.get(`/api/ppn?${searchParams}`)
      
      if (!response.data.success) {
        throw new Error(response.data.message || 'Gagal memuat data PPN')
      }

      return response.data.data
    } catch (error) {
      this.handleError(error)
    }
  }

  static async createPpn(data: PpnFormData): Promise<Ppn> {
    try {
      const response = await axios.post('/api/ppn', data)
      
      if (!response.data.success) {
        throw new Error(response.data.message || 'Gagal membuat PPN')
      }

      return response.data.data
    } catch (error) {
      this.handleError(error)
    }
  }

  static async updatePpn(id: number, data: PpnFormData): Promise<Ppn> {
    try {
      const response = await axios.put(`/api/ppn/${id}`, data)
      
      if (!response.data.success) {
        throw new Error(response.data.message || 'Gagal memperbarui PPN')
      }

      return response.data.data
    } catch (error) {
      this.handleError(error)
    }
  }

  static async deletePpn(id: number): Promise<void> {
    try {
      const response = await axios.delete(`/api/ppn/${id}`)
      
      if (!response.data.success) {
        throw new Error(response.data.message || 'Gagal menghapus PPN')
      }
    } catch (error) {
      this.handleError(error)
    }
  }

  static async togglePpnStatus(id: number): Promise<Ppn> {
    try {
      const response = await axios.post(`/api/ppn/${id}/toggle-active`)
      
      if (!response.data.success) {
        throw new Error(response.data.message || 'Gagal mengubah status PPN')
      }

      return response.data.data
    } catch (error) {
      this.handleError(error)
    }
  }

  static async getPpnById(id: number): Promise<Ppn> {
    try {
      const response = await axios.get(`/api/ppn/${id}`)
      
      if (!response.data.success) {
        throw new Error(response.data.message || 'Gagal memuat data PPN')
      }

      return response.data.data
    } catch (error) {
      this.handleError(error)
    }
  }
}
