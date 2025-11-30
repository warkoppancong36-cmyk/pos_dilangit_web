# Fitur Paket - Package Management

## Overview
Fitur manajemen paket untuk membuat bundling produk dengan harga spesial.

## Database Tables

### 1. `packages`
- **id_package**: Primary key
- **name**: Nama paket
- **slug**: URL-friendly name
- **sku, barcode**: Identifikasi unik
- **package_type**: 'fixed' atau 'customizable'
- **regular_price**: Total harga normal semua item
- **package_price**: Harga jual paket (after discount)
- **savings_amount**: Selisih hemat
- **savings_percentage**: Persentase hemat
- **category_id**: Kategori (optional)
- **is_active**: Status aktif
- **stock**: Stok paket
- **track_stock**: Boolean tracking stok

### 2. `package_items`
- **id_package_item**: Primary key
- **id_package**: Foreign key ke packages
- **id_product**: Foreign key ke products
- **quantity**: Jumlah produk
- **unit**: Satuan
- **is_optional**: Untuk customizable packages
- **unit_price**: Harga satuan saat paket dibuat
- **subtotal**: unit_price * quantity

## Backend

### Models
- **Package.php**: Model utama dengan relationships, scopes, dan helper methods
  - `items()`: Relationship ke PackageItem
  - `category()`: Relationship ke Category
  - `calculatePricing()`: Auto calculate pricing
  - `isAvailable()`: Check ketersediaan stok
  - `reduceStock()`: Kurangi stok paket dan items
  
- **PackageItem.php**: Model untuk item dalam paket
  - `package()`: Relationship ke Package
  - `product()`: Relationship ke Product
  - `calculateSubtotal()`: Auto calculate subtotal

### API Endpoints
**Base URL**: `/api/packages`

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/` | List semua paket (with pagination, search, filters) |
| POST | `/` | Buat paket baru |
| GET | `/{id}` | Detail paket |
| PUT | `/{id}` | Update paket |
| DELETE | `/{id}` | Hapus paket (soft delete) |
| POST | `/bulk-delete` | Hapus multiple paket |
| GET | `/{id}/check-availability` | Cek ketersediaan stok |

### Query Parameters (GET /)
- `search`: Cari nama, SKU, barcode
- `status`: Filter by status (draft/published/archived)
- `category_id`: Filter by category
- `package_type`: Filter by type (fixed/customizable)
- `is_active`: Filter aktif/nonaktif
- `active_only`: Only active packages (for POS)
- `per_page`: Pagination (default: 15)
- `page`: Current page

### Request Body (POST/PUT)
```json
{
  "name": "Paket Hemat A",
  "description": "Paket hemat untuk sarapan",
  "sku": "PKT-001",
  "barcode": "1234567890",
  "package_type": "fixed",
  "package_price": 85000,
  "category_id": 1,
  "is_active": true,
  "is_featured": false,
  "status": "published",
  "track_stock": false,
  "stock": 0,
  "items": [
    {
      "id_product": 1,
      "quantity": 2,
      "unit": "pcs",
      "is_optional": false,
      "notes": ""
    },
    {
      "id_product": 3,
      "quantity": 1,
      "unit": "pcs"
    }
  ]
}
```

## Frontend

### Pages
**`packages-management.vue`**
- List paket dengan data table
- Search & filter (status, kategori, tipe)
- Create/Edit dialog dengan form lengkap
- Bulk delete
- Real-time calculation harga & hemat

### Navigation
Menu "Paket" ditambahkan di:
- `resources/ts/navigation/vertical/index.ts`
- Icon: `tabler-package`
- Role: admin, manager

### Features
✅ CRUD paket lengkap
✅ Add/remove products ke paket
✅ Auto calculate pricing & savings
✅ Real-time preview harga normal vs paket
✅ Filter & search
✅ Pagination
✅ Validation

## Usage Example

### 1. Membuat Paket
1. Klik "Buat Paket Baru"
2. Isi nama paket
3. Klik "Tambah Produk" untuk menambahkan item
4. Atur quantity tiap produk
5. Set harga paket (lebih murah dari harga normal)
6. Sistem auto calculate hemat
7. Simpan

### 2. Di POS (Future)
```javascript
// Get available packages
const packages = await axios.get('/api/packages', {
  params: { active_only: true }
})

// Add package to cart
const pkg = packages.data.data[0]

// Check availability first
const check = await axios.get(`/api/packages/${pkg.id_package}/check-availability`)

if (check.data.data.is_available) {
  // Add to cart
  // On checkout, call pkg.reduceStock()
}
```

## Next Steps
- [ ] Integrasi ke POS untuk jual paket
- [ ] Upload image paket
- [ ] Package template/preset
- [ ] Tracking penjualan paket di reports
- [ ] Customizable package (pilih sendiri isi)
- [ ] Package stock alerts
