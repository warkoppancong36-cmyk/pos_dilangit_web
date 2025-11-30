# Package Integration with POS System - API Documentation

## Overview
Fitur paket (bundle) telah diintegrasikan dengan sistem POS (Point of Sale) untuk memungkinkan penjualan produk bundel melalui POS mobile maupun web. Dokumentasi ini menjelaskan struktur API, format data, dan cara integrasi dengan aplikasi mobile.

---

## Table of Contents
1. [Backend Architecture](#backend-architecture)
2. [API Endpoints](#api-endpoints)
3. [Data Structures](#data-structures)
4. [Mobile Integration Guide](#mobile-integration-guide)
5. [Payment Flow](#payment-flow)
6. [Testing Guide](#testing-guide)
7. [Troubleshooting](#troubleshooting)

---

## Backend Architecture

### 1. Database Schema

#### Order Items Table Enhancement
**Migration**: `2025_11_30_025643_add_package_support_to_order_items_table.php`

```sql
ALTER TABLE order_items ADD COLUMN:
- id_package (BIGINT UNSIGNED, nullable, foreign key → packages.id_package)
- package_name (VARCHAR(255), nullable)
- item_type (ENUM('product', 'package'), default 'product')
```

**Indexes**:
- `order_items_id_package_foreign` on `id_package`
- Composite index on `(id_order, item_type)`

#### Models Relationship

```
Order (1) ──┬──→ (N) OrderItem
            │         ├─→ (item_type = 'product') → Product → ProductItems → Inventory
            │         └─→ (item_type = 'package') → Package → PackageItems → Product
            │
            └──→ (1) Customer
```

### 2. Stock Calculation Logic

#### For Products:
```php
// Calculate based on recipe (ProductItems)
$minProducible = PHP_INT_MAX;
foreach ($productItems as $item) {
    $available = floor($inventory->available_stock / $item->quantity_needed);
    $minProducible = min($minProducible, $available);
}
return $minProducible;
```

#### For Packages:
```php
// Get highest stock from all products in package
$maxStock = 0;
foreach ($package->items as $packageItem) {
    $productStock = calculateProductStock($packageItem->product);
    $maxStock = max($maxStock, $productStock);
}
return $maxStock;
```

---

## API Endpoints

### 1. Get Products & Packages for POS

#### Endpoint
```
GET /api/pos/products
GET /api/pos/packages
```

#### Headers
```
Authorization: Bearer {access_token}
Accept: application/json
```

#### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `search` | string | No | Search by name or SKU |
| `category_id` | integer | No | Filter by category |
| `station` | enum | No | Filter by station: 'kitchen', 'bar', 'both' |

#### Response Structure

**Success (200)**:
```json
{
  "status": "success",
  "message": "Products retrieved successfully",
  "data": [
    {
      "id_product": 1,
      "item_type": "product",
      "name": "Kopi Latte",
      "sku": "PROD-001",
      "barcode": "1234567890",
      "selling_price": 25000,
      "price": "25000.00",
      "stock": 100,
      "is_disabled": false,
      "stock_status": "available",
      "image": "https://example.com/storage/products/latte.jpg",
      "category": {
        "id_category": 1,
        "name": "Beverages"
      },
      "available_in_kitchen": false,
      "available_in_bar": true,
      "stock_info": {
        "current_stock": 100,
        "available_stock": 100,
        "is_available": true
      }
    },
    {
      "id_package": 1,
      "item_type": "package",
      "name": "Paket Breakfast",
      "sku": "PKG-001",
      "barcode": "PKG1234567",
      "package_price": 75000,
      "unit_price": 75000,
      "total_price": 75000,
      "stock": 50,
      "is_disabled": false,
      "stock_status": "available",
      "image": "https://example.com/storage/packages/breakfast.jpg",
      "category": {
        "id_category": 2,
        "name": "Packages"
      },
      "available_in_kitchen": true,
      "available_in_bar": true,
      "items": [
        {
          "id_product": 1,
          "product_name": "Kopi Latte",
          "quantity": 1,
          "unit": "pcs"
        },
        {
          "id_product": 5,
          "product_name": "Croissant",
          "quantity": 2,
          "unit": "pcs"
        }
      ]
    }
  ]
}
```

**Stock Status Values**:
- `"available"` - Stock > 0
- `"stok habis"` - Stock <= 0

**Error Responses**:
```json
// 401 Unauthorized
{
  "status": "error",
  "message": "Unauthenticated."
}

// 500 Internal Server Error
{
  "status": "error",
  "message": "Failed to retrieve products: {error_details}"
}
```

### 2. Process Direct Payment (Checkout)

#### Endpoint
```
POST /api/pos/process-direct-payment
```

#### Headers
```
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/json
```

#### Request Body
```json
{
  "order_type": "dine_in",
  "table_number": "5",
  "customer_id": null,
  "cashier_id": 1,
  "payment_method": "cash",
  "cart_items": [
    {
      "item_type": "product",
      "product_id": 1,
      "quantity": 2,
      "unit_price": 25000,
      "total_price": 50000,
      "notes": "Extra hot"
    },
    {
      "item_type": "package",
      "package_id": 1,
      "quantity": 1,
      "unit_price": 75000,
      "total_price": 75000,
      "notes": null
    }
  ],
  "subtotal_amount": 125000,
  "discount_amount": 0,
  "discount_type": null,
  "tax_amount": 0,
  "total_amount": 125000,
  "paid_amount": 130000,
  "change_amount": 5000,
  "notes": "Customer request: serve together",
  "transaction_date": "2025-11-30T12:34:56",
  "bank": null,
  "bank_id": null,
  "reference_number": null
}
```

#### Field Descriptions

**Order Level**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `order_type` | string | Yes | 'dine_in', 'takeaway', 'delivery' |
| `table_number` | string | No | Table number for dine-in |
| `customer_id` | integer | No | Customer ID if registered |
| `cashier_id` | integer | Yes | User ID of cashier |
| `payment_method` | string | Yes | 'cash', 'qris', 'credit_card', 'debit_card', 'gopay', 'ovo', 'bank_transfer' |

**Cart Items**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `item_type` | string | Yes | 'product' OR 'package' |
| `product_id` | integer | Conditional | Required if `item_type='product'` |
| `package_id` | integer | Conditional | Required if `item_type='package'` |
| `quantity` | number | Yes | Order quantity (min: 1) |
| `unit_price` | number | Yes | Price per unit |
| `total_price` | number | Yes | `unit_price * quantity` |
| `notes` | string | No | Item-specific notes |

**Payment**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `subtotal_amount` | number | Yes | Sum of all item totals |
| `discount_amount` | number | No | Discount value (0 if none) |
| `discount_type` | string | No | 'amount' or 'percentage' |
| `tax_amount` | number | No | Tax/service charge |
| `total_amount` | number | Yes | Final amount to pay |
| `paid_amount` | number | Yes | Amount paid by customer |
| `change_amount` | number | Yes | Change to return |

**Additional**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `bank` | string | No | Bank/biller name for QRIS/transfer |
| `bank_id` | integer | No | Master bank reference ID |
| `reference_number` | string | No | Transaction reference number |
| `notes` | string | No | Order-level notes |

#### Response Structure

**Success (200)**:
```json
{
  "status": "success",
  "message": "Pembayaran berhasil diproses",
  "data": {
    "order": {
      "id_order": 123,
      "order_number": "ORD-20251130-001",
      "order_type": "dine_in",
      "table_number": "5",
      "status": "completed",
      "subtotal": 125000,
      "discount_amount": 0,
      "tax_amount": 0,
      "total_amount": 125000,
      "order_date": "2025-11-30 12:34:56",
      "items": [
        {
          "id": 456,
          "item_type": "product",
          "product_name": "Kopi Latte",
          "quantity": 2,
          "unit_price": 25000,
          "total_price": 50000
        },
        {
          "id": 457,
          "item_type": "package",
          "product_name": "Paket Breakfast",
          "quantity": 1,
          "unit_price": 75000,
          "total_price": 75000
        }
      ]
    },
    "payment": {
      "id_payment": 789,
      "payment_method": "cash",
      "amount_paid": 130000,
      "change_amount": 5000,
      "payment_date": "2025-11-30 12:34:56"
    }
  }
}
```

**Validation Errors (422)**:
```json
{
  "status": "error",
  "message": "Validation error",
  "errors": {
    "cart_items.0.product_id": [
      "Item #0: product_id diperlukan untuk item bertipe 'product'"
    ],
    "paid_amount": [
      "Jumlah bayar tidak mencukupi untuk pembayaran tunai"
    ]
  }
}
```

**Business Logic Errors (400)**:
```json
{
  "status": "error",
  "message": "Paket 'Paket Breakfast' tidak aktif"
}

{
  "status": "error",
  "message": "Stok tidak mencukupi untuk produk 'Kopi Latte' dalam paket"
}
```

### 3. Get Customers

#### Endpoint
```
GET /api/pos/customers
```

#### Response
```json
{
  "status": "success",
  "data": [
    {
      "id_customer": 1,
      "name": "John Doe",
      "phone": "08123456789",
      "email": "john@example.com"
    }
  ]
}
```

---

## Data Structures

### Product Object
```typescript
interface Product {
  id_product: number
  item_type: 'product'
  name: string
  sku: string
  barcode?: string
  selling_price: number
  price: string
  stock: number
  is_disabled: boolean
  stock_status: 'available' | 'stok habis'
  image?: string
  category?: {
    id_category: number
    name: string
  }
  available_in_kitchen: boolean
  available_in_bar: boolean
  stock_info?: {
    current_stock: number
    available_stock: number
    is_available: boolean
  }
}
```

### Package Object
```typescript
interface Package {
  id_package: number
  item_type: 'package'
  name: string
  sku: string
  barcode?: string
  package_price: number
  unit_price: number
  total_price: number
  stock: number
  is_disabled: boolean
  stock_status: 'available' | 'stok habis'
  image?: string
  category?: {
    id_category: number
    name: string
  }
  items: Array<{
    id_product: number
    product_name: string
    quantity: number
    unit: string
  }>
}
```

---

## Mobile Integration Guide

### Step 1: Update Data Models

#### Kotlin (Android)
```kotlin
data class PosItem(
    @SerializedName("id_product")
    val idProduct: Int? = null,
    
    @SerializedName("id_package")
    val idPackage: Int? = null,
    
    @SerializedName("item_type")
    val itemType: String, // "product" or "package"
    
    val name: String,
    val sku: String,
    
    @SerializedName("selling_price")
    val sellingPrice: Double? = null,
    
    @SerializedName("package_price")
    val packagePrice: Double? = null,
    
    val stock: Int,
    
    @SerializedName("is_disabled")
    val isDisabled: Boolean = false,
    
    @SerializedName("stock_status")
    val stockStatus: String,
    
    val image: String? = null,
    
    val category: Category? = null,
    
    val items: List<PackageItem>? = null
)

data class PackageItem(
    @SerializedName("id_product")
    val idProduct: Int,
    
    @SerializedName("product_name")
    val productName: String,
    
    val quantity: Int,
    val unit: String
)

data class CartItemRequest(
    @SerializedName("item_type")
    val itemType: String, // "product" or "package"
    
    @SerializedName("product_id")
    val productId: Int? = null,
    
    @SerializedName("package_id")
    val packageId: Int? = null,
    
    val quantity: Int,
    
    @SerializedName("unit_price")
    val unitPrice: Double,
    
    @SerializedName("total_price")
    val totalPrice: Double,
    
    val notes: String? = null
)
```

#### Swift (iOS)
```swift
struct PosItem: Codable {
    let idProduct: Int?
    let idPackage: Int?
    let itemType: String  // "product" or "package"
    let name: String
    let sku: String
    let sellingPrice: Double?
    let packagePrice: Double?
    let stock: Int
    let isDisabled: Bool
    let stockStatus: String
    let image: String?
    let category: Category?
    let items: [PackageItem]?
    
    enum CodingKeys: String, CodingKey {
        case idProduct = "id_product"
        case idPackage = "id_package"
        case itemType = "item_type"
        case name, sku
        case sellingPrice = "selling_price"
        case packagePrice = "package_price"
        case stock
        case isDisabled = "is_disabled"
        case stockStatus = "stock_status"
        case image, category, items
    }
}

struct CartItemRequest: Codable {
    let itemType: String
    let productId: Int?
    let packageId: Int?
    let quantity: Int
    let unitPrice: Double
    let totalPrice: Double
    let notes: String?
    
    enum CodingKeys: String, CodingKey {
        case itemType = "item_type"
        case productId = "product_id"
        case packageId = "package_id"
        case quantity
        case unitPrice = "unit_price"
        case totalPrice = "total_price"
        case notes
    }
}
```

### Step 2: Fetch Products & Packages

```kotlin
// Retrofit API Interface
@GET("api/pos/products")
suspend fun getProducts(
    @Header("Authorization") token: String,
    @Query("search") search: String? = null,
    @Query("category_id") categoryId: Int? = null
): Response<ApiResponse<List<PosItem>>>
```

### Step 3: Display in UI

**Differentiate Package from Product**:
```kotlin
// In RecyclerView Adapter
override fun onBindViewHolder(holder: ViewHolder, position: Int) {
    val item = items[position]
    
    holder.apply {
        tvName.text = item.name
        
        // Show price based on item type
        val price = if (item.itemType == "package") {
            item.packagePrice ?: 0.0
        } else {
            item.sellingPrice ?: 0.0
        }
        tvPrice.text = formatCurrency(price)
        
        // Show badge for packages
        if (item.itemType == "package") {
            badgePackage.visibility = View.VISIBLE
            badgePackage.setBackgroundResource(R.drawable.bg_package_badge)
        } else {
            badgePackage.visibility = View.GONE
        }
        
        // Disable if stock habis
        itemView.isEnabled = !item.isDisabled && item.stock > 0
        tvStockStatus.text = item.stockStatus
        
        // Load image
        Glide.with(itemView)
            .load(item.image)
            .placeholder(R.drawable.ic_package_placeholder)
            .into(ivProduct)
    }
}
```

### Step 4: Add to Cart

```kotlin
fun addToCart(item: PosItem, quantity: Int) {
    val cartItem = if (item.itemType == "package") {
        CartItemRequest(
            itemType = "package",
            productId = null,
            packageId = item.idPackage,
            quantity = quantity,
            unitPrice = item.packagePrice ?: 0.0,
            totalPrice = (item.packagePrice ?: 0.0) * quantity,
            notes = null
        )
    } else {
        CartItemRequest(
            itemType = "product",
            productId = item.idProduct,
            packageId = null,
            quantity = quantity,
            unitPrice = item.sellingPrice ?: 0.0,
            totalPrice = (item.sellingPrice ?: 0.0) * quantity,
            notes = null
        )
    }
    
    cart.add(cartItem)
    updateCartUI()
}
```

### Step 5: Process Payment

```kotlin
val paymentRequest = PaymentRequest(
    orderType = "dine_in",
    tableNumber = "5",
    customerId = null,
    cashierId = currentUser.id,
    paymentMethod = selectedPaymentMethod,
    cartItems = cart,
    subtotalAmount = calculateSubtotal(),
    discountAmount = discount,
    discountType = discountType,
    taxAmount = tax,
    totalAmount = calculateTotal(),
    paidAmount = amountPaid,
    changeAmount = amountPaid - calculateTotal(),
    notes = orderNotes,
    transactionDate = getCurrentDateTime()
)

val response = apiService.processPayment(
    token = "Bearer $accessToken",
    request = paymentRequest
)

if (response.isSuccessful) {
    val order = response.body()?.data?.order
    showSuccessDialog(order)
    printReceipt(order)
    clearCart()
} else {
    showErrorDialog(response.errorBody()?.string())
}
```

---

## Payment Flow

### Complete Flow Diagram
```
[Mobile App] → API Request → [Backend Validation]
                                      ↓
                           [Stock Validation]
                                      ↓
                           [Create Order & OrderItems]
                                      ↓
                           [Process Payment]
                                      ↓
                           [Reduce Stock]
                                      ↓
                           [Return Response] → [Mobile App]
```

### Stock Reduction for Packages

When package with quantity = 2 is ordered:

**Package Contents**:
- 1x Kopi Latte (has recipe: 30g Coffee Beans, 200ml Milk)
- 2x Croissant (has recipe: 50g Flour, 20g Butter)

**Stock Reduction**:
```
Order Quantity = 2

Kopi Latte:
  - Needed: 1 × 2 = 2 cups
  - Coffee Beans: 30g × 2 = 60g (reduced from inventory)
  - Milk: 200ml × 2 = 400ml (reduced from inventory)

Croissant:
  - Needed: 2 × 2 = 4 pieces
  - Flour: 50g × 4 = 200g (reduced from inventory)
  - Butter: 20g × 4 = 80g (reduced from inventory)
```

---

## Testing Guide

### 1. Setup Test Data

**Create Package via SQL**:
```sql
-- Insert package
INSERT INTO packages (
    name, sku, barcode, package_price, regular_price, 
    package_type, is_active, category_id, created_by
) VALUES (
    'Paket Breakfast', 
    'PKG-001', 
    'PKG1234567890',
    75000, 
    85000,
    'fixed',
    1, 
    2, 
    1
);

-- Insert package items
INSERT INTO package_items (id_package, id_product, quantity, unit)
VALUES 
    (1, 1, 1, 'pcs'),  -- 1x Kopi Latte
    (1, 5, 2, 'pcs');  -- 2x Croissant
```

### 2. Test API with Postman

**Get Products**:
```
GET {{baseUrl}}/api/pos/products
Authorization: Bearer {{token}}
```

**Expected**: Returns both products and packages

**Process Payment with Package**:
```
POST {{baseUrl}}/api/pos/process-direct-payment
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "order_type": "dine_in",
  "cashier_id": 1,
  "payment_method": "cash",
  "cart_items": [
    {
      "item_type": "package",
      "package_id": 1,
      "quantity": 1,
      "unit_price": 75000,
      "total_price": 75000
    }
  ],
  "subtotal_amount": 75000,
  "total_amount": 75000,
  "paid_amount": 100000,
  "change_amount": 25000
}
```

**Expected**: 
- Order created successfully
- Stock reduced for all items in package
- Payment recorded

### 3. Verify Stock Reduction

```sql
-- Check inventory after order
SELECT 
    i.name AS item_name,
    inv.quantity AS total_stock,
    inv.available_stock,
    inv.reserved_stock
FROM inventories inv
JOIN items i ON i.id_item = inv.id_item
WHERE i.id_item IN (
    SELECT DISTINCT pi.item_id
    FROM product_items pi
    JOIN package_items pkg ON pkg.id_product = pi.product_id
    WHERE pkg.id_package = 1
);
```

---

## Troubleshooting

### Issue: Package tidak muncul di GET /api/pos/products

**Possible Causes**:
1. `is_active = 0` di packages table
2. Package tidak memiliki items
3. Category tidak ada

**Solution**:
```sql
-- Check package status
SELECT id_package, name, is_active, category_id 
FROM packages WHERE id_package = 1;

-- Check package items
SELECT * FROM package_items WHERE id_package = 1;

-- Activate package
UPDATE packages SET is_active = 1 WHERE id_package = 1;
```

### Issue: Error "package_id diperlukan untuk item bertipe 'package'"

**Cause**: Request mengirim `item_type = 'package'` tapi `package_id` null

**Solution**: Pastikan payload memiliki `package_id` yang valid
```json
{
  "item_type": "package",
  "package_id": 1,  // Must be present and not null
  "product_id": null
}
```

### Issue: Stock tidak berkurang saat order package

**Possible Causes**:
1. Products dalam package tidak memiliki recipe (product_items)
2. Inventory items tidak ada
3. Error di `consumeRecipeItems()`

**Debug**:
```sql
-- Check if products have recipes
SELECT p.name, COUNT(pi.id) as recipe_items
FROM products p
LEFT JOIN product_items pi ON pi.product_id = p.id_product
WHERE p.id_product IN (
    SELECT id_product FROM package_items WHERE id_package = 1
)
GROUP BY p.id_product;

-- Check inventory
SELECT i.name, inv.available_stock
FROM inventories inv
JOIN items i ON i.id_item = inv.id_item;
```

**Check Logs**:
```bash
tail -f storage/logs/laravel.log | grep "consumeRecipeItems"
```

### Issue: Payment berhasil tapi order tidak tercatat

**Cause**: Transaction rollback karena error

**Debug**: Check error response dan log
```bash
tail -f storage/logs/laravel.log | grep "ERROR"
```

**Common Errors**:
- Foreign key constraint (customer_id, cashier_id tidak ada)
- Validation error
- Database connection timeout

**Solution**:
```php
// Ensure transaction is properly wrapped
DB::beginTransaction();
try {
    // ... order creation
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    Log::error('Order creation failed', ['error' => $e->getMessage()]);
    throw $e;
}
```

---

## Best Practices for Mobile Development

### 1. Caching Strategy
```kotlin
// Cache products locally
class ProductRepository(private val dao: ProductDao, private val api: ApiService) {
    
    suspend fun getProducts(forceRefresh: Boolean = false): List<PosItem> {
        if (!forceRefresh) {
            val cached = dao.getAllProducts()
            if (cached.isNotEmpty()) return cached
        }
        
        val response = api.getProducts()
        if (response.isSuccessful) {
            response.body()?.data?.let { products ->
                dao.insertAll(products)
                return products
            }
        }
        
        return dao.getAllProducts() // Fallback to cache
    }
}
```

### 2. Offline Mode Support
```kotlin
// Queue orders when offline
class OrderQueue(private val context: Context) {
    
    fun queueOrder(order: PaymentRequest) {
        val json = Gson().toJson(order)
        PreferenceManager.getDefaultSharedPreferences(context)
            .edit()
            .putString("pending_order_${System.currentTimeMillis()}", json)
            .apply()
    }
    
    suspend fun syncPendingOrders() {
        val prefs = PreferenceManager.getDefaultSharedPreferences(context)
        val pendingKeys = prefs.all.keys.filter { it.startsWith("pending_order_") }
        
        pendingKeys.forEach { key ->
            val json = prefs.getString(key, null) ?: return@forEach
            val order = Gson().fromJson(json, PaymentRequest::class.java)
            
            try {
                val response = apiService.processPayment(order)
                if (response.isSuccessful) {
                    prefs.edit().remove(key).apply()
                }
            } catch (e: Exception) {
                Log.e("OrderQueue", "Failed to sync order", e)
            }
        }
    }
}
```

### 3. Error Handling
```kotlin
sealed class Result<out T> {
    data class Success<T>(val data: T) : Result<T>()
    data class Error(val message: String, val code: Int? = null) : Result<Nothing>()
    object Loading : Result<Nothing>()
}

// Usage
viewModelScope.launch {
    _uiState.value = Result.Loading
    
    try {
        val response = repository.processPayment(paymentRequest)
        _uiState.value = if (response.isSuccessful) {
            Result.Success(response.body()!!.data)
        } else {
            val errorBody = response.errorBody()?.string()
            val errorMessage = parseErrorMessage(errorBody)
            Result.Error(errorMessage, response.code())
        }
    } catch (e: Exception) {
        _uiState.value = Result.Error(
            e.message ?: "Network error occurred"
        )
    }
}
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2025-11-30 | Initial package integration |
| 1.1.0 | 2025-11-30 | Added stock calculation for packages |
| 1.2.0 | 2025-11-30 | Added `is_disabled` and `stock_status` fields |

---

## Support

Untuk pertanyaan atau issue, silakan hubungi:
- Backend Team: backend@example.com
- Mobile Team: mobile@example.com
- Documentation: docs@example.com

## Backend Changes

### 1. Database Migration
**File**: `database/migrations/2025_11_30_025643_add_package_support_to_order_items_table.php`

Menambahkan kolom baru di tabel `order_items`:
- `id_package` (nullable, foreign key ke `packages.id_package`)
- `package_name` (string, nullable)
- `item_type` (enum: 'product' | 'package', default 'product')

### 2. OrderItem Model Update
**File**: `app/Models/OrderItem.php`

**Perubahan**:
- Tambah `id_package`, `package_name`, `item_type` ke `$fillable`
- Tambah relationship `package()` ke Package model
- Update `getItemCodeAttribute()` untuk handle SKU paket

### 3. PosController Enhancement
**File**: `app/Http/Controllers/Api/PosController.php`

#### Method `getProducts()` dan `getProductsNonOnline()`
**Perubahan**:
- Mengambil data packages dari database dengan `Package::where('is_active', true)`
- Menghitung stock availability untuk packages berdasarkan item-item di dalamnya
- Menambahkan `item_type`, `unit_price`, `total_price` untuk consistency
- Merge products dan packages dalam satu response

**Response Structure**:
```php
[
    // Products
    {
        "id_product": 1,
        "item_type": "product",
        "name": "Kopi Latte",
        "sku": "PROD-001",
        "selling_price": 25000,
        "stock": 100,
        "stock_info": {
            "current_stock": 100,
            "available_stock": 100,
            "is_available": true
        }
    },
    // Packages
    {
        "id_package": 1,
        "item_type": "package",
        "name": "Paket Breakfast",
        "sku": "PKG-001",
        "package_price": 75000,
        "unit_price": 75000,  // Mapped dari package_price
        "total_price": 75000,  // Same as package_price
        "stock_info": {
            "current_stock": 50,  // Calculated from items
            "available_stock": 50,
            "is_available": true
        },
        "items": [...]  // Package items dengan product relationship
    }
]
```

#### Method `addItem()`
**Perubahan**:
- Validasi sekarang menerima `item_type` (required), `id_product` (nullable), `id_package` (nullable)
- Tambah logic untuk handle package:
  - Check if package is active
  - Validate stock availability untuk semua item dalam paket
  - Create OrderItem dengan `item_type = 'package'`
  - Consume stock untuk setiap product dalam package menggunakan `consumeRecipeItems()`
  
**Request Payload untuk Package**:
```json
{
    "item_type": "package",
    "id_package": 1,
    "quantity": 2,
    "notes": "Optional notes"
}
```

**Stock Reduction Logic**:
Ketika paket ditambahkan ke order dengan quantity 2:
1. Ambil semua items dalam paket (misalnya: 1x Kopi, 2x Roti)
2. Kalikan dengan order quantity: (1x2=2) Kopi, (2x2=4) Roti
3. Call `consumeRecipeItems()` untuk setiap product
4. Stock berkurang sesuai recipe/composition product tersebut

## Frontend Changes

### 1. TypeScript Interface Update
**File**: `resources/ts/composables/usePOS.ts`

**Product Interface**:
```typescript
export interface Product {
  id_product?: number
  id_package?: number
  item_type?: 'product' | 'package'
  name: string
  sku: string
  selling_price?: number
  package_price?: number
  stock?: number
  stock_info?: {
    current_stock: number
    available_stock: number
    is_available: boolean
  }
  unit_price?: number
  total_price?: number
  // ... other fields
}
```

**Composable Updates**:
- `subtotal` computed: Handle both `package_price` and `selling_price`
- `addToCart()`: Support both id_product and id_package, check stock dari stock_info untuk packages
- `removeFromCart()`, `updateQuantity()`: Tambah parameter `isPackage` untuk distinguish

### 2. API Interface Update
**File**: `resources/ts/utils/api/PosApi.ts`

```typescript
export interface AddItemData {
  id_product?: number
  id_package?: number
  item_type: 'product' | 'package'
  quantity: number
  unit_price?: number
  total_price?: number
  notes?: string
}
```

### 3. POS UI Enhancement
**File**: `resources/ts/pages/pos/index.vue`

**Visual Changes**:
- Package ditampilkan dengan **purple gradient badge** bertuliskan "PAKET" dengan icon gift
- Package item memiliki **purple border** dan subtle purple background gradient
- Stock badge untuk package diposisikan lebih rendah (top: 32px) untuk tidak overlap dengan package badge

**CSS Classes Added**:
```css
.package-badge {
  background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
  /* ... purple gradient styling */
}

.product-item.package-item {
  border: 2px solid #8b5cf6;
  background: linear-gradient(to bottom, #faf5ff 0%, #ffffff 100%);
}
```

**Logic Changes**:
- `canAddToCart()`: Check `stock_info.is_available` untuk packages
- `addToCart()`: Kirim `item_type`, `id_package`, `package_price` untuk packages
- Template: Use nullish coalescing (`product.stock || 0`) untuk handle optional fields

## Testing Guide

### 1. Create Test Package
```sql
-- Via packages-management page atau langsung via database
INSERT INTO packages (name, sku, package_price, regular_price, is_active, category_id, created_by)
VALUES ('Paket Sarapan', 'PKG-001', 75000, 85000, 1, 1, 1);

INSERT INTO package_items (id_package, id_product, quantity, unit)
VALUES 
  (1, 1, 1, 'pcs'),  -- 1x Kopi
  (1, 2, 2, 'pcs');  -- 2x Roti
```

### 2. Test in POS
1. Buka POS (`/pos`)
2. Package akan muncul di product grid dengan:
   - Purple "PAKET" badge di pojok kanan atas
   - Purple border
   - Icon `tabler-packages` (jika no image)
   - Stock calculation otomatis dari items

3. Click package untuk add to cart
4. Verify di cart:
   - Item name menampilkan nama package
   - Price menggunakan `package_price`
   - Quantity adjustment works

5. Lakukan checkout dan verify:
   - Order created dengan correct total
   - OrderItem has `item_type = 'package'` dan `id_package`
   - Stock items dalam package berkurang sesuai quantity

### 3. Verify Stock Reduction
```sql
-- Check inventory sebelum dan sesudah order
SELECT i.name, inv.available_stock 
FROM inventories inv
JOIN items i ON i.id_item = inv.id_item
WHERE i.id_item IN (
  -- IDs of items used in package
);
```

## Mobile POS Compatibility

POS mobile akan otomatis mendapatkan packages dari endpoint yang sama:
- `/api/pos/products` - Include packages
- `/api/pos/product_mobile` - Include packages (excludes online category)

Mobile app perlu:
1. Update interface/model untuk handle `item_type`, `id_package`
2. Display package badge di UI
3. Kirim `item_type` dan `id_package` saat add item

## API Endpoints Summary

| Endpoint | Method | Changes |
|----------|--------|---------|
| `/api/pos/products` | GET | Returns merged products + packages |
| `/api/pos/product_mobile` | GET | Returns merged products + packages (non-online) |
| `/api/pos/orders/{id}/items` | POST | Accepts `item_type`, `id_package` |

## Troubleshooting

### Package tidak muncul di POS
- Check `is_active = 1` di packages table
- Check package memiliki minimal 1 item di package_items
- Verify category_id exists

### Stock tidak berkurang saat order package
- Check apakah products dalam package memiliki recipe (product_items)
- Verify inventory tersedia untuk items dalam recipe
- Check log untuk error pada `consumeRecipeItems()`

### TypeScript errors di frontend
- Ensure `item_type`, `id_package` ada di Product interface
- Use nullish coalescing (`||`) untuk optional fields
- Import updated types dari composables/usePOS

## Next Steps
- [ ] Add package thumbnail/image upload
- [ ] Package availability alerts
- [ ] Sales report by package
- [ ] Package upsell suggestions in POS
- [ ] Bulk package creation from templates
