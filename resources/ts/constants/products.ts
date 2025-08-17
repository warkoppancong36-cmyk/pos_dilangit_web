// Products Constants and Configuration

import type { ProductStatus, StockStatus } from '@/composables/useProducts'

// Coffee Shop Product Categories (extend from existing coffee theme)
export const PRODUCT_COFFEE_COLORS = {
  PRIMARY: '#B07124',
  SECONDARY: '#8D7053', 
  ACCENT: '#D4AC71',
  DARK: '#8D4B00',
  MEDIUM: '#A0672D',
  LIGHT: '#B8946A',
  SUCCESS: '#4CAF50',
  WARNING: '#FF9800',
  ERROR: '#F44336',
  INFO: '#2196F3'
} as const

// Product Status Configuration
export const PRODUCT_STATUS_CONFIG: Record<ProductStatus, { 
  title: string
  color: string
  icon: string
  description: string
}> = {
  draft: {
    title: 'Draft',
    color: PRODUCT_COFFEE_COLORS.WARNING,
    icon: 'mdi-file-edit-outline',
    description: 'Produk masih dalam tahap persiapan'
  },
  published: {
    title: 'Published',
    color: PRODUCT_COFFEE_COLORS.SUCCESS,
    icon: 'mdi-check-circle',
    description: 'Produk aktif dan dapat dijual'
  },
  archived: {
    title: 'Archived',
    color: PRODUCT_COFFEE_COLORS.ERROR,
    icon: 'mdi-archive',
    description: 'Produk tidak aktif'
  }
}

// Stock Status Configuration
export const STOCK_STATUS_CONFIG: Record<StockStatus, { 
  title: string
  color: string
  icon: string
  description: string
}> = {
  in_stock: {
    title: 'Stok Tersedia',
    color: PRODUCT_COFFEE_COLORS.SUCCESS,
    icon: 'mdi-check-circle',
    description: 'Stok mencukupi'
  },
  low_stock: {
    title: 'Stok Menipis',
    color: PRODUCT_COFFEE_COLORS.WARNING,
    icon: 'mdi-alert-circle',
    description: 'Stok di bawah batas minimum'
  },
  out_of_stock: {
    title: 'Stok Habis',
    color: PRODUCT_COFFEE_COLORS.ERROR,
    icon: 'mdi-close-circle',
    description: 'Stok sudah habis'
  }
}

// Product Units
export const PRODUCT_UNITS = [
  { value: 'pcs', title: 'Pcs (Pieces)', description: 'Satuan per buah' },
  { value: 'kg', title: 'Kg (Kilogram)', description: 'Satuan berat kilogram' },
  { value: 'gram', title: 'Gram', description: 'Satuan berat gram' },
  { value: 'liter', title: 'Liter', description: 'Satuan volume liter' },
  { value: 'ml', title: 'ML (Mililiter)', description: 'Satuan volume mililiter' },
  { value: 'meter', title: 'Meter', description: 'Satuan panjang meter' },
  { value: 'cm', title: 'CM (Centimeter)', description: 'Satuan panjang centimeter' },
  { value: 'box', title: 'Box', description: 'Satuan per kotak' },
  { value: 'pack', title: 'Pack', description: 'Satuan per kemasan' },
  { value: 'dozen', title: 'Dozen', description: 'Satuan per lusin (12 buah)' },
  { value: 'cup', title: 'Cup', description: 'Satuan per cangkir' },
  { value: 'bottle', title: 'Bottle', description: 'Satuan per botol' },
  { value: 'sachet', title: 'Sachet', description: 'Satuan per sachet' }
] as const

// Sort Options
export const PRODUCT_SORT_OPTIONS = [
  { value: 'name', title: 'Nama Produk', icon: 'mdi-sort-alphabetical-variant' },
  { value: 'price', title: 'Harga Jual', icon: 'mdi-currency-usd' },
  { value: 'cost', title: 'Harga Pokok', icon: 'mdi-calculator' },
  { value: 'stock', title: 'Stok', icon: 'mdi-package-variant' },
  { value: 'created_at', title: 'Tanggal Dibuat', icon: 'mdi-calendar-plus' },
  { value: 'updated_at', title: 'Tanggal Diubah', icon: 'mdi-calendar-edit' }
] as const

// Filter Options
export const ACTIVE_FILTER_OPTIONS = [
  { value: 'all', title: 'Semua Status', icon: 'mdi-format-list-bulleted' },
  { value: 'active', title: 'Aktif', icon: 'mdi-check-circle', color: PRODUCT_COFFEE_COLORS.SUCCESS },
  { value: 'inactive', title: 'Tidak Aktif', icon: 'mdi-close-circle', color: PRODUCT_COFFEE_COLORS.ERROR }
] as const

export const STOCK_FILTER_OPTIONS = [
  { value: 'all', title: 'Semua Stok', icon: 'mdi-format-list-bulleted' },
  { value: 'in_stock', title: 'Stok Tersedia', icon: 'mdi-check-circle', color: PRODUCT_COFFEE_COLORS.SUCCESS },
  { value: 'low_stock', title: 'Stok Menipis', icon: 'mdi-alert-circle', color: PRODUCT_COFFEE_COLORS.WARNING },
  { value: 'out_of_stock', title: 'Stok Habis', icon: 'mdi-close-circle', color: PRODUCT_COFFEE_COLORS.ERROR }
] as const

export const FEATURED_FILTER_OPTIONS = [
  { value: 'all', title: 'Semua Produk', icon: 'mdi-format-list-bulleted' },
  { value: 'featured', title: 'Produk Unggulan', icon: 'mdi-star', color: PRODUCT_COFFEE_COLORS.WARNING },
  { value: 'not_featured', title: 'Bukan Unggulan', icon: 'mdi-star-outline' }
] as const

// Image Configuration
export const IMAGE_CONFIG = {
  MAX_SIZE: 2 * 1024 * 1024, // 2MB
  ALLOWED_TYPES: ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'],
  ALLOWED_EXTENSIONS: ['.jpg', '.jpeg', '.png', '.gif'],
  UPLOAD_PATH: '/storage/products/',
  PLACEHOLDER: '/images/placeholder-product.png'
} as const

// Form Configuration
export const FORM_CONFIG = {
  NAME_MIN_LENGTH: 2,
  NAME_MAX_LENGTH: 255,
  DESCRIPTION_MAX_LENGTH: 1000,
  SKU_MIN_LENGTH: 3,
  SKU_MAX_LENGTH: 20,
  BARCODE_MAX_LENGTH: 50,
  BRAND_MAX_LENGTH: 100,
  META_TITLE_MAX_LENGTH: 255,
  META_DESCRIPTION_MAX_LENGTH: 500,
  MIN_PRICE: 0,
  MIN_STOCK: 0,
  MIN_WEIGHT: 0
} as const

// Table Configuration
export const TABLE_CONFIG = {
  DEFAULT_PER_PAGE: 15,
  PER_PAGE_OPTIONS: [10, 15, 25, 50, 100],
  MAX_BULK_ACTIONS: 100
} as const

// Coffee Shop Specific Product Categories
export const COFFEE_PRODUCT_CATEGORIES = [
  { value: 'coffee_beans', title: 'Biji Kopi', icon: 'mdi-coffee-outline' },
  { value: 'ground_coffee', title: 'Kopi Bubuk', icon: 'mdi-coffee' },
  { value: 'instant_coffee', title: 'Kopi Instan', icon: 'mdi-coffee-to-go' },
  { value: 'espresso', title: 'Espresso', icon: 'mdi-coffee' },
  { value: 'cold_brew', title: 'Cold Brew', icon: 'mdi-glass-mug-variant' },
  { value: 'tea', title: 'Teh', icon: 'mdi-tea' },
  { value: 'pastries', title: 'Pastry', icon: 'mdi-cupcake' },
  { value: 'sandwiches', title: 'Sandwich', icon: 'mdi-hamburger' },
  { value: 'snacks', title: 'Snack', icon: 'mdi-cookie' },
  { value: 'merchandise', title: 'Merchandise', icon: 'mdi-tshirt-crew' },
  { value: 'equipment', title: 'Peralatan Kopi', icon: 'mdi-coffee-maker' }
] as const

// Success Messages
export const SUCCESS_MESSAGES = {
  CREATE: 'Produk berhasil dibuat',
  UPDATE: 'Produk berhasil diperbarui',
  DELETE: 'Produk berhasil dihapus',
  BULK_DELETE: 'Produk terpilih berhasil dihapus',
  TOGGLE_ACTIVE: 'Status produk berhasil diubah',
  TOGGLE_FEATURED: 'Status produk unggulan berhasil diubah',
  IMPORT: 'Data produk berhasil diimport',
  EXPORT: 'Data produk berhasil diexport'
} as const

// Error Messages
export const ERROR_MESSAGES = {
  FETCH_FAILED: 'Gagal mengambil data produk',
  CREATE_FAILED: 'Gagal membuat produk',
  UPDATE_FAILED: 'Gagal memperbarui produk',
  DELETE_FAILED: 'Gagal menghapus produk',
  TOGGLE_FAILED: 'Gagal mengubah status produk',
  IMAGE_UPLOAD_FAILED: 'Gagal mengunggah gambar',
  IMAGE_TOO_LARGE: 'Ukuran gambar terlalu besar (maksimal 2MB)',
  IMAGE_INVALID_TYPE: 'Format gambar tidak didukung',
  NETWORK_ERROR: 'Tidak dapat terhubung ke server',
  VALIDATION_ERROR: 'Data yang dimasukkan tidak valid',
  SKU_DUPLICATE: 'SKU sudah digunakan produk lain',
  BARCODE_DUPLICATE: 'Barcode sudah digunakan produk lain'
} as const

// Validation Messages
export const VALIDATION_MESSAGES = {
  REQUIRED: 'Field ini wajib diisi',
  MIN_LENGTH: (min: number) => `Minimal ${min} karakter`,
  MAX_LENGTH: (max: number) => `Maksimal ${max} karakter`,
  MIN_VALUE: (min: number) => `Nilai minimal ${min}`,
  MAX_VALUE: (max: number) => `Nilai maksimal ${max}`,
  INVALID_EMAIL: 'Format email tidak valid',
  INVALID_URL: 'Format URL tidak valid',
  INVALID_NUMBER: 'Harus berupa angka',
  INVALID_INTEGER: 'Harus berupa bilangan bulat',
  INVALID_DECIMAL: 'Format desimal tidak valid'
} as const
