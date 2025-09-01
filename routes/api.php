<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionLogController;
use App\Http\Controllers\Api\PpnController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\InventoryUploadController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\CashDrawerController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ProductItemController;
use App\Http\Controllers\Api\ItemPurchaseController;
use App\Http\Controllers\Api\ProductRecipeController;
use App\Http\Controllers\Api\HPPController;
use App\Http\Controllers\VariantHPPController;
use App\Http\Controllers\Api\BaseProductCompositionController;


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });

    Route::prefix('logs')->group(function () {
        Route::get('transactions', [TransactionLogController::class, 'index']);
        Route::get('transactions/{id}', [TransactionLogController::class, 'show']);
        Route::get('statistics', [TransactionLogController::class, 'statistics']);
        Route::get('user/{userId}/activity', [TransactionLogController::class, 'userActivity']);
        Route::get('export', [TransactionLogController::class, 'export']);
    });

    Route::prefix('ppn')->group(function () {
        Route::get('/', [PpnController::class, 'index']);
        Route::post('/', [PpnController::class, 'store']);
        Route::get('/active', [PpnController::class, 'getActive']);
        Route::get('/{id}', [PpnController::class, 'show']);
        Route::put('/{id}', [PpnController::class, 'update']);
        Route::delete('/{id}', [PpnController::class, 'destroy']);
        Route::post('/{id}/restore', [PpnController::class, 'restore']);
        Route::post('/{id}/toggle-active', [PpnController::class, 'toggleActive']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/roles', [UserController::class, 'getRoles']);
        Route::post('/bulk-delete', [UserController::class, 'bulkDelete']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/{id}/toggle-active', [UserController::class, 'toggleActive']);
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/stats', [RoleController::class, 'stats']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
        Route::post('/{id}/toggle-active', [RoleController::class, 'toggleActive']);
        Route::get('/{id}/permissions', [RoleController::class, 'getPermissions']);
        Route::post('/{id}/permissions/sync', [RoleController::class, 'syncPermissions']);
        Route::get('/{id}/users', [RoleController::class, 'getUsers']);
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
        Route::post('/{category}/toggle-active', [CategoryController::class, 'toggleActive']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/test-log', [ProductController::class, 'testLog']);
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/stats', [ProductController::class, 'stats']);
        Route::post('/bulk-delete', [ProductController::class, 'bulkDelete']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::post('/{id}/toggle-active', [ProductController::class, 'toggleActive']);
        Route::post('/{id}/toggle-featured', [ProductController::class, 'toggleFeatured']);
    });

    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::get('/stats', [SupplierController::class, 'stats']);
        Route::get('/cities', [SupplierController::class, 'cities']);
        Route::get('/provinces', [SupplierController::class, 'provinces']);
        Route::get('/{supplier}', [SupplierController::class, 'show']);
        Route::put('/{supplier}', [SupplierController::class, 'update']);
        Route::delete('/{supplier}', [SupplierController::class, 'destroy']);
    });

    // VARIANT ROUTES - Product Variant Management
    Route::prefix('variants')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\VariantController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\VariantController::class, 'store']);
        Route::get('/product/{productId}', [App\Http\Controllers\Api\VariantController::class, 'getProductVariants']);
        Route::post('/bulk-delete', [App\Http\Controllers\Api\VariantController::class, 'bulkDelete']);
        Route::get('/{id}', [App\Http\Controllers\Api\VariantController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\VariantController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\VariantController::class, 'destroy']);
        Route::post('/{id}/toggle-active', [App\Http\Controllers\Api\VariantController::class, 'toggleActive']);
        Route::get('/{id}/production-capacity', [App\Http\Controllers\Api\VariantController::class, 'checkProductionCapacity']);
    });

    // VARIANT ITEMS ROUTES - Variant Composition Management
    Route::prefix('variant-items')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\VariantItemController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\VariantItemController::class, 'store']);
        Route::post('/bulk-store', [App\Http\Controllers\Api\VariantItemController::class, 'bulkStore']);
        Route::post('/bulk-update', [App\Http\Controllers\Api\VariantItemController::class, 'bulkUpdate']);
        Route::post('/bulk-delete', [App\Http\Controllers\Api\VariantItemController::class, 'bulkDelete']);
        Route::get('/product/{productId}', [App\Http\Controllers\Api\VariantItemController::class, 'getVariantsByProduct']);
        Route::get('/variant/{variantId}', [App\Http\Controllers\Api\VariantItemController::class, 'getVariantComposition']);
        Route::get('/{id}', [App\Http\Controllers\Api\VariantItemController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\VariantItemController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\VariantItemController::class, 'destroy']);
    });

    // VARIANT HPP ROUTES - Variant HPP Management
    Route::prefix('variants')->group(function () {
        Route::get('/{variantId}/hpp-breakdown', [App\Http\Controllers\VariantHPPController::class, 'getHPPBreakdown']);
        Route::post('/{variantId}/hpp-update', [App\Http\Controllers\VariantHPPController::class, 'updateHPP']);
        Route::post('/{variantId}/hpp-suggestion', [App\Http\Controllers\VariantHPPController::class, 'calculatePriceSuggestion']);
        Route::post('/{variantId}/hpp-price-update', [App\Http\Controllers\VariantHPPController::class, 'updatePriceFromHPP']);
    });

    // BASE PRODUCTS ROUTES - Base Product Management for Compositions
    Route::prefix('base-products')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\BaseProductController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\BaseProductController::class, 'store']);
        Route::get('/categories', [App\Http\Controllers\Api\BaseProductController::class, 'getCategories']);
        Route::get('/for-selection', [App\Http\Controllers\Api\BaseProductController::class, 'getForSelection']);
        Route::get('/{id}', [App\Http\Controllers\Api\BaseProductController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\BaseProductController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\BaseProductController::class, 'destroy']);
        Route::post('/{id}/update-stock', [App\Http\Controllers\Api\BaseProductController::class, 'updateStock']);
        Route::get('/{id}/movements', [App\Http\Controllers\Api\BaseProductController::class, 'getMovements']);
    });

    // BASE PRODUCT COMPOSITIONS ROUTES - Base Product Recipe Management
    Route::prefix('base-product-compositions')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\BaseProductCompositionController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\BaseProductCompositionController::class, 'store']);
        Route::get('/base-product/{baseProductId}', [App\Http\Controllers\Api\BaseProductCompositionController::class, 'getCompositionsForBaseProduct']);
        Route::post('/calculate-cost', [App\Http\Controllers\Api\BaseProductCompositionController::class, 'calculateCost']);
        Route::get('/{id}', [App\Http\Controllers\Api\BaseProductCompositionController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\BaseProductCompositionController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\BaseProductCompositionController::class, 'destroy']);
    });

    // PRODUCT COMPOSITIONS ROUTES - Product Recipe Management using Base Products
    Route::prefix('product-compositions')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ProductCompositionController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\ProductCompositionController::class, 'store']);
        Route::post('/bulk-store', [App\Http\Controllers\Api\ProductCompositionController::class, 'bulkStore']);
        Route::post('/bulk-update', [App\Http\Controllers\Api\ProductCompositionController::class, 'bulkUpdate']);
        Route::post('/bulk-delete', [App\Http\Controllers\Api\ProductCompositionController::class, 'bulkDelete']);
        Route::get('/product/{productId}', [App\Http\Controllers\Api\ProductCompositionController::class, 'getProductCompositions']);
        Route::post('/product/{productId}/check-availability', [App\Http\Controllers\Api\ProductCompositionController::class, 'checkProductionAvailability']);
        Route::get('/{id}', [App\Http\Controllers\Api\ProductCompositionController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\ProductCompositionController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\ProductCompositionController::class, 'destroy']);
    });

    // VARIANT ATTRIBUTES ROUTES - Product Attribute Management
    Route::prefix('variant-attributes')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\VariantAttributeController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\VariantAttributeController::class, 'store']);
        Route::post('/sort-order', [App\Http\Controllers\Api\VariantAttributeController::class, 'updateSortOrder']);
        Route::get('/product/{productId}', [App\Http\Controllers\Api\VariantAttributeController::class, 'getProductAttributes']);
        Route::get('/product/{productId}/combinations', [App\Http\Controllers\Api\VariantAttributeController::class, 'generateVariantCombinations']);
        Route::get('/{id}', [App\Http\Controllers\Api\VariantAttributeController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\VariantAttributeController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\VariantAttributeController::class, 'destroy']);
    });

    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/stats', [CustomerController::class, 'stats']);
        Route::get('/search-suggestions', [CustomerController::class, 'searchSuggestions']);
        Route::post('/bulk-delete', [CustomerController::class, 'bulkDelete']);
        Route::get('/{customer}', [CustomerController::class, 'show']);
        Route::put('/{customer}', [CustomerController::class, 'update']);
        Route::delete('/{customer}', [CustomerController::class, 'destroy']);
        Route::post('/{customer}/toggle-active', [CustomerController::class, 'toggleActive']);
    });

    Route::prefix('purchases')->group(function () {
        Route::get('/', [PurchaseController::class, 'index']);
        Route::post('/', [PurchaseController::class, 'store']);
        Route::post('/test-data', function(Request $request) {
            return response()->json([
                'success' => true,
                'received_data' => $request->all(),
                'supplier_id' => $request->get('supplier_id'),
                'items' => $request->get('items'),
                'content_type' => $request->header('Content-Type'),
                'auth_user' => Auth::check() ? Auth::user()->email : 'Not authenticated'
            ]);
        });
        Route::get('/statistics', [PurchaseController::class, 'getStatistics']);
        Route::get('/products', [PurchaseController::class, 'getProductsForPurchase']);
        Route::get('/{purchase}', [PurchaseController::class, 'show']);
        Route::put('/{purchase}', [PurchaseController::class, 'update']);
        Route::patch('/{purchase}/status', [PurchaseController::class, 'updateStatus']);
        Route::post('/{purchase}/receive', [PurchaseController::class, 'receiveItems']); // New route
        Route::post('/{purchase}/complete-auto-receive', [PurchaseController::class, 'completeWithAutoReceive']); // Auto-complete route
        Route::delete('/{purchase}', [PurchaseController::class, 'destroy']);
        Route::post('/{purchase}/restore', [PurchaseController::class, 'restore']);
        Route::delete('/{purchase}/force', [PurchaseController::class, 'forceDelete']);
    });

    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/stats', [InventoryController::class, 'stats']);
        Route::get('/low-stock-alerts', [InventoryController::class, 'lowStockAlerts']);
        Route::get('/movements', [InventoryController::class, 'movements']);
        Route::post('/movement', [InventoryController::class, 'recordMovement']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::get('/{id}/movements', [InventoryController::class, 'movements']);
        Route::post('/{id}/update-stock', [InventoryController::class, 'updateStock']);
        Route::post('/{id}/reorder-level', [InventoryController::class, 'setReorderLevel']);
        Route::post('/bulk-update-stock', [InventoryController::class, 'bulkUpdateStock']);
        
        // Inventory upload routes
        Route::post('/upload-data', [InventoryUploadController::class, 'uploadInventoryData']);
        Route::get('/upload-history', [InventoryUploadController::class, 'getUploadHistory']);
    });

    Route::prefix('discounts')->group(function () {
        Route::get('/', [DiscountController::class, 'index']);
        Route::post('/', [DiscountController::class, 'store']);
        Route::get('/stats', [DiscountController::class, 'getStats']);
        Route::post('/validate-code', [DiscountController::class, 'validateCode']);
        Route::get('/{discount}', [DiscountController::class, 'show']);
        Route::put('/{discount}', [DiscountController::class, 'update']);
        Route::delete('/{discount}', [DiscountController::class, 'destroy']);
        Route::post('/{discount}/toggle-status', [DiscountController::class, 'toggleStatus']);
        Route::post('/{discount}/duplicate', [DiscountController::class, 'duplicate']);
    });

    Route::prefix('promotions')->group(function () {
        Route::get('/', [PromotionController::class, 'index']);
        Route::post('/', [PromotionController::class, 'store']);
        Route::get('/active', [PromotionController::class, 'getActivePromotions']);
        Route::get('/stats', [PromotionController::class, 'getStats']);
        Route::post('/calculate', [PromotionController::class, 'calculatePromotions']);
        Route::get('/{promotion}', [PromotionController::class, 'show']);
        Route::put('/{promotion}', [PromotionController::class, 'update']);
        Route::delete('/{promotion}', [PromotionController::class, 'destroy']);
        Route::post('/{promotion}/toggle-status', [PromotionController::class, 'toggleStatus']);
        Route::post('/{promotion}/duplicate', [PromotionController::class, 'duplicate']);
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // POS Routes
    Route::prefix('pos')->group(function () {
        Route::get('/stats', [PosController::class, 'getStats']);
        Route::get('/products', [PosController::class, 'getProducts']);
        
        // Order routes - specific routes first
        Route::get('/orders', [PosController::class, 'getActiveOrders']);
        Route::get('/orders/history', [PosController::class, 'getOrders']);
        Route::post('/orders', [PosController::class, 'createOrder']);
        Route::post('/process-direct-payment', [PosController::class, 'processDirectPayment']);
        
        // Order routes with parameters - must be after specific routes
        Route::get('/orders/{orderId}/details', [PosController::class, 'getOrderDetails']);
        Route::put('/orders/{order}', [PosController::class, 'editOrder']);
        Route::put('/orders/{order}/status', [PosController::class, 'updateOrderStatus']);
        Route::post('/orders/{order}/cancel', [PosController::class, 'cancelOrder']);
        Route::post('/orders/{order}/items', [PosController::class, 'addItem']);
        Route::put('/orders/{order}/items/{orderItem}', [PosController::class, 'updateItem']);
        Route::put('/orders/{order}/items/{orderItem}/discount', [PosController::class, 'updateItemDiscount']);
        Route::delete('/orders/{order}/items/{orderItem}', [PosController::class, 'removeItem']);
        Route::post('/orders/{order}/discount', [PosController::class, 'applyDiscount']);
        Route::post('/orders/{order}/tax', [PosController::class, 'applyTax']);
        Route::post('/orders/{order}/payment', [PosController::class, 'processPayment']);
    });

    // Cash Drawer Routes
    Route::prefix('cash-drawer')->group(function () {
        Route::get('/data', [CashDrawerController::class, 'getCashData']);
        Route::post('/cash-in', [CashDrawerController::class, 'cashIn']);
        Route::post('/cash-out', [CashDrawerController::class, 'cashOut']);
        Route::get('/summary', [CashDrawerController::class, 'getDailySummary']);
    });

    // Item Management Routes
    Route::prefix('items')->group(function () {
        Route::get('/', [ItemController::class, 'index']);
        Route::post('/', [ItemController::class, 'store']);
        Route::get('/stats', [ItemController::class, 'getStats']);
        Route::get('/{id}', [ItemController::class, 'show']);
        Route::put('/{id}', [ItemController::class, 'update']);
        Route::delete('/{id}', [ItemController::class, 'destroy']);
    });

    // Product-Item Relationship Routes
    Route::prefix('product-items')->group(function () {
        Route::get('/', [ProductItemController::class, 'index']);
        Route::post('/', [ProductItemController::class, 'store']);
        Route::get('/{id}', [ProductItemController::class, 'show']);
        Route::put('/{id}', [ProductItemController::class, 'update']);
        Route::delete('/{id}', [ProductItemController::class, 'destroy']);
        Route::get('/production-capacity/{productId}', [ProductItemController::class, 'getProductionCapacity']);
    });

    // Item Purchase Routes
    Route::prefix('item-purchases')->group(function () {
        Route::get('/', [ItemPurchaseController::class, 'index']);
        Route::post('/', [ItemPurchaseController::class, 'store']);
        Route::post('/bulk', [ItemPurchaseController::class, 'bulkStore']);
        Route::get('/history/{itemId}', [ItemPurchaseController::class, 'getPurchaseHistory']);
        Route::get('/{id}', [ItemPurchaseController::class, 'show']);
        Route::put('/{id}', [ItemPurchaseController::class, 'update']);
        Route::delete('/{id}', [ItemPurchaseController::class, 'destroy']);
    });

    // Product Recipes Routes
    Route::prefix('product-recipes')->group(function () {
        Route::get('/available-items', [ProductRecipeController::class, 'availableItems']);
        Route::get('/product/{productId}', [ProductRecipeController::class, 'index']);
        Route::get('/product/{productId}/stats', [ProductRecipeController::class, 'stats']);
        Route::post('/', [ProductRecipeController::class, 'store']);
        Route::get('/{id}', [ProductRecipeController::class, 'show']);
        Route::put('/{id}', [ProductRecipeController::class, 'update']);
        Route::delete('/{id}', [ProductRecipeController::class, 'destroy']);
    });

    // HPP (Harga Pokok Produksi) Routes
    Route::prefix('hpp')->group(function () {
        Route::get('/dashboard', [HPPController::class, 'getHPPDashboard']);
        Route::post('/bulk-update', [HPPController::class, 'bulkUpdateHPP']);
        
        Route::prefix('products/{productId}')->group(function () {
            Route::get('/breakdown', [HPPController::class, 'getProductHPPBreakdown']);
            Route::post('/update', [HPPController::class, 'updateProductHPP']);
            Route::get('/compare-methods', [HPPController::class, 'compareHPPMethods']);
            Route::post('/suggested-price', [HPPController::class, 'calculateSuggestedPrice']);
            Route::post('/update-price', [HPPController::class, 'updatePriceFromHPP']);
        });
    });

    // Base Product Composition Routes
    Route::prefix('base-product-compositions')->group(function () {
        Route::get('/', [BaseProductCompositionController::class, 'index']);
        Route::post('/', [BaseProductCompositionController::class, 'store']);
        Route::get('/{composition}', [BaseProductCompositionController::class, 'show']);
        Route::put('/{composition}', [BaseProductCompositionController::class, 'update']);
        Route::delete('/{composition}', [BaseProductCompositionController::class, 'destroy']);
        Route::post('/{composition}/toggle-status', [BaseProductCompositionController::class, 'toggleStatus']);
    });

    // Asset Management Routes
    Route::prefix('assets')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\AssetController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\AssetController::class, 'store']);
        Route::post('/bulk-delete', [App\Http\Controllers\Api\AssetController::class, 'bulkDelete']);
        Route::get('/statistics', [App\Http\Controllers\Api\AssetController::class, 'statistics']);
        Route::get('/maintenance-schedule', [App\Http\Controllers\Api\AssetController::class, 'maintenanceSchedule']);
        Route::get('/category/{category}', [App\Http\Controllers\Api\AssetController::class, 'byCategory']);
        Route::get('/{asset}', [App\Http\Controllers\Api\AssetController::class, 'show']);
        Route::put('/{asset}', [App\Http\Controllers\Api\AssetController::class, 'update']);
        Route::delete('/{asset}', [App\Http\Controllers\Api\AssetController::class, 'destroy']);
        Route::post('/{asset}/change-status', [App\Http\Controllers\Api\AssetController::class, 'changeStatus']);
        Route::post('/{asset}/assign', [App\Http\Controllers\Api\AssetController::class, 'assign']);
    });

    // Asset helper routes (categories, locations)
    Route::get('assets-categories', [App\Http\Controllers\Api\AssetController::class, 'getCategories']);
    Route::get('assets-locations', [App\Http\Controllers\Api\AssetController::class, 'getLocations']);

    // Permission Management Routes
    Route::prefix('permissions')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\PermissionController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\PermissionController::class, 'store']);
        Route::get('/modules', [App\Http\Controllers\Api\PermissionController::class, 'getModules']);
        Route::get('/actions', [App\Http\Controllers\Api\PermissionController::class, 'getActions']);
        Route::get('/user/{userId}', [App\Http\Controllers\Api\PermissionController::class, 'getUserPermissions']);
        Route::get('/role/{roleId}', [App\Http\Controllers\Api\PermissionController::class, 'getRolePermissions']);
        Route::get('/{permission}', [App\Http\Controllers\Api\PermissionController::class, 'show']);
        Route::put('/{permission}', [App\Http\Controllers\Api\PermissionController::class, 'update']);
        Route::delete('/{permission}', [App\Http\Controllers\Api\PermissionController::class, 'destroy']);
        
        // Permission assignment routes
        Route::post('/assign-to-user', [App\Http\Controllers\Api\PermissionController::class, 'assignToUser']);
        Route::post('/assign-to-role', [App\Http\Controllers\Api\PermissionController::class, 'assignToRole']);
        Route::post('/revoke-from-user', [App\Http\Controllers\Api\PermissionController::class, 'revokeFromUser']);
        Route::post('/revoke-from-role', [App\Http\Controllers\Api\PermissionController::class, 'revokeFromRole']);
        Route::post('/bulk-assign', [App\Http\Controllers\Api\PermissionController::class, 'bulkAssign']);
        
        // Permission checking routes
        Route::post('/check-user-permission', [App\Http\Controllers\Api\PermissionController::class, 'checkUserPermission']);
        Route::get('/user/{userId}/effective', [App\Http\Controllers\Api\PermissionController::class, 'getEffectivePermissions']);
    });

    // Bluetooth Device Management Routes
    Route::prefix('bluetooth-devices')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\BluetoothDeviceController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\BluetoothDeviceController::class, 'store']);
        Route::get('/defaults', [App\Http\Controllers\Api\BluetoothDeviceController::class, 'getDefaults']);
        Route::get('/{id}', [App\Http\Controllers\Api\BluetoothDeviceController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\BluetoothDeviceController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\BluetoothDeviceController::class, 'destroy']);
        Route::post('/{id}/set-default', [App\Http\Controllers\Api\BluetoothDeviceController::class, 'setDefault']);
        Route::post('/{id}/update-connection', [App\Http\Controllers\Api\BluetoothDeviceController::class, 'updateConnection']);
        Route::post('/{id}/test-connection', [App\Http\Controllers\Api\BluetoothDeviceController::class, 'testConnection']);
    });


});


// Temporary test endpoint for development
Route::get('/test-token', function () {
    $user = App\Models\User::first();
    $token = $user->createToken('test-token')->plainTextToken;
    return response()->json([
        'message' => 'Test token generated',
        'token' => $token,
        'user' => $user->name
    ]);
});

Route::prefix('master-slave-test')->group(function () {
    Route::get('/test-connections', [App\Http\Controllers\Api\MasterSlaveTestController::class, 'testConnections']);
    Route::post('/create-log', [App\Http\Controllers\Api\MasterSlaveTestController::class, 'createTestLog']);
    Route::post('/bulk-create-logs', [App\Http\Controllers\Api\MasterSlaveTestController::class, 'bulkCreateTestLogs']);
    Route::get('/logs', [App\Http\Controllers\Api\MasterSlaveTestController::class, 'getLogs']);
    Route::get('/analytics', [App\Http\Controllers\Api\MasterSlaveTestController::class, 'getAnalytics']);
    Route::get('/error-logs', [App\Http\Controllers\Api\MasterSlaveTestController::class, 'getErrorLogs']);
    Route::get('/consistent-logs', [App\Http\Controllers\Api\MasterSlaveTestController::class, 'getConsistentLogs']);
    Route::get('/database-stats', [App\Http\Controllers\Api\MasterSlaveTestController::class, 'getDatabaseStats']);
});

    