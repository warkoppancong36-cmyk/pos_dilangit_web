import axios from 'axios'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export interface User {
  id: number
  name: string
  username: string
  email: string
  phone?: string
  role_id: number
  is_active: boolean
  last_login_at?: string
  last_login_ip?: string
  last_login_device?: string
  created_at: string
  updated_at: string
  role?: {
    id: number
    name: string
    permissions: Record<string, any>
  }
}

export interface AuthResponse {
  success: boolean
  message: string
  data: {
    user: User
    token: string
    token_type: string
  }
}

export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data?: T
  errors?: Record<string, string[]>
}

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const isLoading = ref(false)
  const errors = ref<Record<string, string[]>>({})

  // Getters
  const isLoggedIn = computed(() => !!token.value && !!user.value)
  const userRole = computed(() => user.value?.role?.name)
  const userPermissions = computed(() => user.value?.role?.permissions || {})
  const isAdmin = computed(() => userRole.value === 'admin')
  const isManager = computed(() => userRole.value === 'manager')
  const isCashier = computed(() => userRole.value === 'kasir')

  // Actions
  const setToken = (tokenValue: string) => {
    token.value = tokenValue
    localStorage.setItem('token', tokenValue)
    axios.defaults.headers.common['Authorization'] = `Bearer ${tokenValue}`
  }

  const clearToken = () => {
    token.value = null
    localStorage.removeItem('token')
    delete axios.defaults.headers.common['Authorization']
  }

  const setUser = (userData: User) => {
    user.value = userData
  }

  const clearUser = () => {
    user.value = null
  }

  const clearErrors = () => {
    errors.value = {}
  }

  const login = async (credentials: { login: string; password: string }) => {
    try {
      isLoading.value = true
      clearErrors()

      const response = await axios.post<AuthResponse>('/api/auth/login', credentials)
      
      if (response.data.success) {
        const { user: userData, token: tokenValue } = response.data.data
        
        setUser(userData)
        setToken(tokenValue)
        
        return { success: true, message: response.data.message }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error: any) {
      const errorResponse = error.response?.data as ApiResponse
      
      if (errorResponse?.errors) {
        errors.value = errorResponse.errors
      }
      
      return { 
        success: false, 
        message: errorResponse?.message || 'Login failed',
        errors: errorResponse?.errors 
      }
    } finally {
      isLoading.value = false
    }
  }

  const register = async (userData: {
    name: string
    username: string
    email: string
    phone?: string
    password: string
    password_confirmation: string
    role_id?: number
  }) => {
    try {
      isLoading.value = true
      clearErrors()

      const response = await axios.post<AuthResponse>('/api/auth/register', userData)
      
      if (response.data.success) {
        const { user: newUser, token: tokenValue } = response.data.data
        
        setUser(newUser)
        setToken(tokenValue)
        
        return { success: true, message: response.data.message }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error: any) {
      const errorResponse = error.response?.data as ApiResponse
      
      if (errorResponse?.errors) {
        errors.value = errorResponse.errors
      }
      
      return { 
        success: false, 
        message: errorResponse?.message || 'Registration failed',
        errors: errorResponse?.errors 
      }
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    try {
      if (token.value) {
        await axios.post('/api/auth/logout')
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      clearToken()
      clearUser()
      clearErrors()
    }
  }

  const logoutAll = async () => {
    try {
      if (token.value) {
        await axios.post('/api/auth/logout-all')
      }
    } catch (error) {
      console.error('Logout all error:', error)
    } finally {
      clearToken()
      clearUser()
      clearErrors()
    }
  }

  const fetchProfile = async () => {
    try {
      const response = await axios.get<ApiResponse<{ user: User }>>('/api/auth/profile')
      
      if (response.data.success && response.data.data) {
        setUser(response.data.data.user)
        return true
      } else {
        clearToken()
        clearUser()
        return false
      }
    } catch (error) {
      console.error('Fetch profile error:', error)
      clearToken()
      clearUser()
      return false
    }
  }

  const changePassword = async (passwordData: {
    current_password: string
    new_password: string
    new_password_confirmation: string
  }) => {
    try {
      isLoading.value = true
      clearErrors()

      const response = await axios.post<ApiResponse>('/api/auth/change-password', passwordData)
      
      if (response.data.success) {
        return { success: true, message: response.data.message }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error: any) {
      const errorResponse = error.response?.data as ApiResponse
      
      if (errorResponse?.errors) {
        errors.value = errorResponse.errors
      }
      
      return { 
        success: false, 
        message: errorResponse?.message || 'Password change failed',
        errors: errorResponse?.errors 
      }
    } finally {
      isLoading.value = false
    }
  }

  const refreshToken = async () => {
    try {
      const response = await axios.post<ApiResponse<{ token: string; token_type: string }>>('/api/auth/refresh')
      
      if (response.data.success && response.data.data) {
        setToken(response.data.data.token)
        return true
      } else {
        clearToken()
        clearUser()
        return false
      }
    } catch (error) {
      console.error('Token refresh error:', error)
      clearToken()
      clearUser()
      return false
    }
  }

  const hasPermission = (permission: string): boolean => {
    if (!user.value?.role?.permissions) return false
    
    const permissions = user.value.role.permissions
    return permissions[permission] === true || permissions[permission] === 1
  }

  const hasAnyPermission = (permissionList: string[]): boolean => {
    return permissionList.some(permission => hasPermission(permission))
  }

  const hasAllPermissions = (permissionList: string[]): boolean => {
    return permissionList.every(permission => hasPermission(permission))
  }

  const canAccess = (requiredRole?: string | string[], requiredPermission?: string | string[]): boolean => {
    // Check role
    if (requiredRole) {
      const roles = Array.isArray(requiredRole) ? requiredRole : [requiredRole]
      const userRoleName = user.value?.role?.name
      
      if (!userRoleName || !roles.includes(userRoleName)) {
        return false
      }
    }
    
    // Check permission
    if (requiredPermission) {
      const permissions = Array.isArray(requiredPermission) ? requiredPermission : [requiredPermission]
      
      if (!hasAnyPermission(permissions)) {
        return false
      }
    }
    
    return true
  }

  // Initialize token in axios headers
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  return {
    // State
    user,
    token,
    isLoading,
    errors,
    
    // Getters
    isLoggedIn,
    userRole,
    userPermissions,
    isAdmin,
    isManager,
    isCashier,
    
    // Actions
    login,
    register,
    logout,
    logoutAll,
    fetchProfile,
    changePassword,
    refreshToken,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    canAccess,
    clearErrors,
    setToken,
    clearToken,
    setUser,
    clearUser,
  }
})
