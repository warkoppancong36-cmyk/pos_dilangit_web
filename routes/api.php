<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionLogController;
use App\Http\Controllers\Api\PpnController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InventoryController;
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


// Test route to create sample inventory movement
Route::post('/test-create-movement', function(Request $request) {
    try {
        $inventory = \App\Models\Inventory::find($request->inventory_id ?? 24);
        if (!$inventory) {
            return response()->json(['error' => 'Inventory not found'], 404);
        }
        
        $movement = \App\Models\InventoryMovement::create([
            'id_inventory' => $inventory->id_inventory,
            'movement_type' => 'in',
            'quantity' => 10,
            'stock_before' => $inventory->current_stock,
            'stock_after' => $inventory->current_stock + 10,
            'unit_cost' => 1000,
            'total_cost' => 10000,
            'reference_type' => 'test',
            'reference_id' => null,
            'notes' => 'Test movement for debugging',
            'created_by' => 1,
            'movement_date' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'movement' => $movement,
            'message' => 'Test movement created successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Debug route to test stock update logic
Route::post('/debug-stock-update/{id}', function(Request $request, $id) {
    try {
        \DB::beginTransaction();
        
        $inventory = \App\Models\Inventory::find($id);
        if (!$inventory) {
            return response()->json(['error' => 'Inventory not found'], 404);
        }
        
        $stockBefore = $inventory->current_stock;
        $quantity = (int) $request->input('quantity', 5);
        $newStock = $stockBefore + $quantity;
        
        // Step 1: Create movement
        $movement = \App\Models\InventoryMovement::create([
            'id_inventory' => $inventory->id_inventory,
            'movement_type' => 'in',
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $newStock,
            'unit_cost' => 2000,
            'total_cost' => 2000 * $quantity,
            'reference_type' => 'debug',
            'reference_id' => null,
            'notes' => 'Debug test update',
            'created_by' => 1,
            'movement_date' => now()
        ]);
        
        // Step 2: Update inventory
        $updateResult = $inventory->update([
            'current_stock' => $newStock,
            'last_restocked' => now()
        ]);
        
        \DB::commit();
        
        return response()->json([
            'success' => true,
            'stock_before' => $stockBefore,
            'quantity_added' => $quantity,
            'expected_new_stock' => $newStock,
            'actual_current_stock' => $inventory->fresh()->current_stock,
            'update_result' => $updateResult,
            'movement_id' => $movement->id_movement
        ]);
    } catch (\Exception $e) {
        \DB::rollback();
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

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

    // VARIANT ROUTES DISABLED - Variant system removed
    // Route::prefix('variants')->group(function () {
    //     Route::get('/', [App\Http\Controllers\Api\VariantController::class, 'index']);
    //     Route::post('/', [App\Http\Controllers\Api\VariantController::class, 'store']);
    //     Route::post('/bulk-create', [App\Http\Controllers\Api\VariantController::class, 'bulkCreate']);
    //     Route::get('/stats', [App\Http\Controllers\Api\VariantController::class, 'stats']);
    //     Route::get('/attributes', [App\Http\Controllers\Api\VariantController::class, 'getVariantAttributes']);
    //     Route::get('/{variant}', [App\Http\Controllers\Api\VariantController::class, 'show']);
    //     Route::put('/{variant}', [App\Http\Controllers\Api\VariantController::class, 'update']);
    //     Route::delete('/{variant}', [App\Http\Controllers\Api\VariantController::class, 'destroy']);
    // });

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
});

Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
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
