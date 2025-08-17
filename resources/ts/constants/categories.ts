// Status filter options
export const STATUS_OPTIONS = [
  { title: 'Semua Status', value: 'all' },
  { title: 'Aktif', value: 'active' },
  { title: 'Tidak Aktif', value: 'inactive' }
]

// Items per page options
export const PER_PAGE_OPTIONS = [
  { title: '5 per halaman', value: 5 },
  { title: '10 per halaman', value: 10 },
  { title: '25 per halaman', value: 25 },
  { title: '50 per halaman', value: 50 }
]

// Image upload constraints
export const IMAGE_CONSTRAINTS = {
  MAX_SIZE: 2 * 1024 * 1024, // 2MB
  ACCEPTED_TYPES: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
  ACCEPTED_EXTENSIONS: '.jpg,.jpeg,.png,.gif,.webp'
}

// Default form data
export const DEFAULT_FORM_DATA = {
  name: '',
  description: '',
  image: null,
  active: true
}

// Default pagination
export const DEFAULT_PAGINATION = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
}

// View modes
export const VIEW_MODES = {
  GRID: 'grid',
  LIST: 'list'
} as const

// Coffee Shop Color Palette
export const COFFEE_COLORS = {
  PRIMARY: '#B07124',          // Main brown/coffee color
  PRIMARY_DARK: '#9A5F1C',     // Darker brown
  SECONDARY: '#8D7053',        // Warm gray-brown
  ACCENT: '#D4AC71',           // Light coffee/cream
  BACKGROUND_LIGHT: '#FBF9F6', // Warm white
  BACKGROUND_DARK: '#1C1510',  // Dark coffee
  TEXT_LIGHT: '#3E2723',       // Dark brown text
  TEXT_DARK: '#F4E7D1',        // Cream text
  SUCCESS: '#8BC34A',          // Green (coffee plant)
  WARNING: '#FF9800',          // Orange (coffee bean)
  ERROR: '#F44336'             // Red
}

// Messages
export const MESSAGES = {
  SUCCESS: {
    CREATED: 'Kategori berhasil ditambahkan',
    UPDATED: 'Kategori berhasil diperbarui',
    DELETED: 'Kategori berhasil dihapus',
    ACTIVATED: 'Status kategori berhasil diaktifkan',
    DEACTIVATED: 'Status kategori berhasil dinonaktifkan'
  },
  ERROR: {
    FETCH: 'Terjadi kesalahan saat mengambil data kategori',
    SAVE: 'Terjadi kesalahan saat menyimpan kategori',
    DELETE: 'Terjadi kesalahan saat menghapus kategori',
    TOGGLE: 'Terjadi kesalahan saat mengubah status kategori',
    IMAGE_TYPE: 'File harus berupa gambar',
    IMAGE_SIZE: 'Ukuran file maksimal 2MB',
    REQUIRED_NAME: 'Nama kategori wajib diisi'
  }
}
