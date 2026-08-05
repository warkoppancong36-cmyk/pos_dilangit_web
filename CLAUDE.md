# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Development (all services concurrently)
composer run dev
# Individually: php artisan serve | npm run dev | php artisan queue:listen | php artisan pail

# Testing
composer test                        # run all PHPUnit tests
php artisan test --filter TestName   # run a single test class or method

# Frontend
npm run typecheck    # TypeScript type check
npm run lint         # ESLint auto-fix

# First-time setup
php artisan key:generate && php artisan migrate && php artisan db:seed
```

## Architecture Overview

### Authentication
Uses **Laravel Sanctum** (not tymon/jwt-auth despite it being installed). The `check.token.expiration` middleware (`app/Http/Middleware/CheckTokenExpiration.php`) checks `expires_at` on Sanctum tokens; tokens expiring within 10 minutes return `X-Token-Expires-Soon` response headers. All `/api` routes except `auth/register` and `auth/login` require `auth:sanctum` + `check.token.expiration`.

The frontend (`resources/ts/stores/auth.ts`) stores the token in both `localStorage` and a cookie (`accessToken`), with cookie taking priority. Axios headers are set on login and cleared on logout.

### Primary Keys Convention
Models use non-standard primary keys: `id_order`, `id_product`, `id_kitchen_order`, `id_inventory`, `id_customer`, etc. Always use these field names when writing queries or relationships.

### Inventory System
Stock is managed **exclusively** in the `inventory` table — the `products` and `variants` tables do not have stock columns (they were removed in migrations). `InventorySyncService` exists for compatibility but is a no-op. All stock reads/writes go through the `Inventory` model.

### HPP (Harga Pokok Produksi)
HPP = cost of production. Products have a `cost` field updated by `HPPCalculationService`. Cost is derived from product recipes (`product_recipe_items` → `items` → `item_purchases` for latest price). `HPPController` and `VariantHPPController` expose this. When an `ItemPurchase` changes price, `PurchaseItemObserver` triggers `HPPCalculationService::updateProductsHPPForItem()`.

### Report Caching
`OrderObserver` maintains two denormalized cache tables (`report_transaction_cache`, `report_sales_daily`) on every Order create/update/delete. `ReportController` reads from these caches rather than aggregating orders at query time.

### Kitchen Order System
`KitchenController` has a dual-source strategy: if the `kitchen_orders` table exists and has data, it uses `KitchenOrder`; otherwise it falls back to querying the `orders` table directly (legacy mode). Pass `?source=new` or `?source=legacy` to force a source.

`KitchenOrder::findOrCreateForOrder()` merges new items into an existing **pending** kitchen order for the same `id_order`. If the existing kitchen order is already `in_progress`, a new kitchen order is created so kitchen staff sees a fresh notification. The kitchen order lifecycle is: `pending` → `in_progress` (acknowledged) → `completed`.

### POS Domain Concepts
- **PPN** = Pajak Pertambahan Nilai (Indonesian VAT), managed via `PpnController`
- **Base Products** = raw ingredients (`base_products` table) used in recipes
- **Product Compositions** = recipes linking products to base products
- **Variants** = product size/type variants; the `VariantItem` model holds composition of a variant
- **Packages** = bundled product offerings (`packages` + `package_items` tables)
- **HPP** = Harga Pokok Produksi (cost of production)

### Frontend Routing
Pages under `resources/ts/pages/` are auto-routed by `unplugin-vue-router` — the file path is the route. Route guard in `resources/ts/plugins/1.router/index.ts` handles auth redirect and calls `checkRoutePermissions` for role/permission gating. Role-based dashboards exist at `/admin/dashboard`, `/cashier/dashboard`, `/manager/dashboard`.

### API Client Pattern
Frontend API calls use typed functions in `resources/ts/utils/api/` (one file per domain, e.g., `ProductsApi.ts`). These are consumed by composables in `resources/ts/composables/` (e.g., `useProducts.ts`). Composables wrap the API calls with reactive state and error handling and are what Vue pages import.
