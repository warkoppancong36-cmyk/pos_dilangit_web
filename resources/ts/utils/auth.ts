// Helper function to get auth token from various sources
export function getAuthToken(): string | null {
  // Try different token storage keys
  const possibleKeys = [
    'authToken',
    'accessToken', 
    'access_token',
    'token',
    'bearerToken',
    'user_token',
    'api_token'
  ]
  
  // Check localStorage
  for (const key of possibleKeys) {
    const token = localStorage.getItem(key)
    if (token) {
      return token
    }
  }
  
  // Check sessionStorage
  for (const key of possibleKeys) {
    const token = sessionStorage.getItem(key)
    if (token) {
      return token
    }
  }
  
  // Check meta tag
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (csrfToken) {
    return csrfToken
  }
  
  // Check cookies
  const cookies = document.cookie.split(';')
  for (const cookie of cookies) {
    const [name, value] = cookie.trim().split('=')
    if (possibleKeys.includes(name)) {
      return value
    }
  }
  
  console.warn('No authentication token found')
  return null
}

// Setup axios defaults with auth token
export function setupAxiosAuth() {
  const token = getAuthToken()
  if (token) {
    // Set default axios headers
    window.axios = window.axios || require('axios')
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    window.axios.defaults.headers.common['Accept'] = 'application/json'
    window.axios.defaults.headers.common['Content-Type'] = 'application/json'
    return true
  }
  return false
}
