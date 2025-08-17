// PPN Management Constants
export const PPN_CONSTANTS = {
  // Default values
  DEFAULT_ITEMS_PER_PAGE: 15,
  DEFAULT_SORT_BY: 'created_at',
  DEFAULT_SORT_ORDER: 'desc' as const,
  
  // Validation limits
  MAX_NAME_LENGTH: 255,
  MIN_NOMINAL: 0,
  MAX_NOMINAL: 100,
  
  // Messages
  MESSAGES: {
    CREATE_SUCCESS: 'PPN berhasil ditambahkan',
    UPDATE_SUCCESS: 'PPN berhasil diperbarui',
    DELETE_SUCCESS: 'PPN berhasil dihapus',
    ACTIVATE_SUCCESS: 'PPN berhasil diaktifkan',
    DEACTIVATE_SUCCESS: 'PPN berhasil dinonaktifkan',
    FETCH_ERROR: 'Gagal memuat data PPN',
    CREATE_ERROR: 'Gagal menambahkan PPN',
    UPDATE_ERROR: 'Gagal memperbarui PPN',
    DELETE_ERROR: 'Gagal menghapus PPN',
    TOGGLE_ERROR: 'Gagal mengubah status PPN'
  },
  
  // Form validation messages
  VALIDATION: {
    NAME_REQUIRED: 'Nama wajib diisi',
    NAME_MAX_LENGTH: `Nama maksimal ${255} karakter`,
    NOMINAL_REQUIRED: 'Nominal wajib diisi',
    NOMINAL_MIN: 'Nominal harus 0 atau lebih besar',
    NOMINAL_MAX: 'Nominal maksimal 100'
  },
  
  // Status options
  STATUS_OPTIONS: [
    { value: 'all', title: 'Semua Status' },
    { value: 'active', title: 'Hanya Aktif' },
    { value: 'inactive', title: 'Hanya Tidak Aktif' }
  ],
  
  // Sort options
  SORT_OPTIONS: [
    { value: 'created_at', title: 'Tanggal Dibuat' },
    { value: 'name', title: 'Nama' },
    { value: 'nominal', title: 'Nominal' },
    { value: 'updated_at', title: 'Tanggal Diperbarui' }
  ],
  
  // Sort order options
  SORT_ORDER_OPTIONS: [
    { value: 'desc', title: 'Menurun' },
    { value: 'asc', title: 'Menaik' }
  ]
} as const

// Coffee theme colors for PPN
export const PPN_COLORS = {
  PRIMARY: '#B07124',
  SECONDARY: '#8D7053',
  ACCENT: '#D4AC71',
  SUCCESS: '#8BC34A',
  WARNING: '#FF9800',
  ERROR: '#F44336',
  INFO: '#2196F3',
  
  // Background colors
  LIGHT_BG: '#FBF9F6',
  DARK_BG: '#1C1510',
  
  // Text colors
  TEXT_PRIMARY_LIGHT: '#8D4B00',
  TEXT_SECONDARY_LIGHT: '#A0672D',
  TEXT_PRIMARY_DARK: '#D4AC71',
  TEXT_SECONDARY_DARK: '#B8946A',
  
  // Border colors
  BORDER_LIGHT: 'rgba(176, 113, 36, 0.1)',
  BORDER_DARK: 'rgba(212, 172, 113, 0.1)',
  
  // Shadow colors
  SHADOW_LIGHT: 'rgba(176, 113, 36, 0.15)',
  SHADOW_DARK: 'rgba(212, 172, 113, 0.2)'
} as const
