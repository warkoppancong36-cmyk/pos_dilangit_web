<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
// use App\Models\Variant; // DISABLED - Variant system removed
use App\Services\InventorySyncService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of inventory items
     */
    public function index(Request $request)
    {
        try {
            $query = Inventory::with(['product', 'product.category', 'item', 'creator', 'updater']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    // Search in product fields
                    $q->whereHas('product', function($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('sku', 'like', "%{$search}%");
                    })
                    // OR search in item fields
                    ->orWhereHas('item', function($itemQuery) use ($search) {
                        $itemQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('item_code', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                    });
                });
            }

            // Filter by stock status
            if ($request->filled('stock_status')) {
                switch ($request->stock_status) {
                    case 'low_stock':
                        $query->whereRaw('current_stock <= reorder_level AND reorder_level > 0');
                        break;
                    case 'out_of_stock':
                        $query->where('current_stock', 0);
                        break;
                    case 'in_stock':
                        $query->where('current_stock', '>', 0);
                        break;
                    case 'overstock':
                        $query->whereRaw('current_stock > max_stock_level AND max_stock_level > 0');
                        break;
                }
            }

            // Filter by product category
            if ($request->filled('category_id')) {
                $query->whereHas('product', function($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            // Filter by supplier
            if ($request->filled('supplier_id')) {
                $query->whereHas('product', function($q) use ($request) {
                    $q->where('id_supplier', $request->supplier_id);
                });
            }

            // Filter by station availability with exact matching
            $kitchenFilter = $request->get('available_in_kitchen');
            $barFilter = $request->get('available_in_bar');
            $stationMode = $request->get('station_mode', 'inclusive');
            
            if ($kitchenFilter === 'true' && $barFilter === 'true') {
                if ($stationMode === 'exclusive') {
                    // Show items available in BOTH stations only (exact match)
                    $query->whereHas('item', function($q) {
                        $q->where('available_in_kitchen', '=', true)
                          ->where('available_in_bar', '=', true);
                    });
                } else {
                    // Show items available in either kitchen OR bar
                    $query->whereHas('item', function($q) {
                        $q->where('available_in_kitchen', '=', true)
                          ->orWhere('available_in_bar', '=', true);
                    });
                }
            } elseif ($kitchenFilter === 'true' && ($barFilter !== 'true')) {
                if ($stationMode === 'exclusive') {
                    // Show items available ONLY in kitchen (not in bar) - exact match
                    $query->whereHas('item', function($q) {
                        $q->where('available_in_kitchen', '=', true)
                          ->where('available_in_bar', '=', false);
                    });
                } else {
                    // Show all items available in kitchen (exact match)
                    $query->whereHas('item', function($q) {
                        $q->where('available_in_kitchen', '=', true);
                    });
                }
            } elseif ($barFilter === 'true' && ($kitchenFilter !== 'true')) {
                if ($stationMode === 'exclusive') {
                    // Show items available ONLY in bar (not in kitchen) - exact match
                    $query->whereHas('item', function($q) {
                        $q->where('available_in_bar', '=', true)
                          ->where('available_in_kitchen', '=', false);
                    });
                } else {
                    // Show all items available in bar (exact match)
                    $query->whereHas('item', function($q) {
                        $q->where('available_in_bar', '=', true);
                    });
                }
            }

            // Debug logging untuk station filtering
            \Log::info('Station Filter Debug:', [
                'kitchen_filter' => $kitchenFilter,
                'bar_filter' => $barFilter,
                'station_mode' => $stationMode,
                'request_params' => $request->only(['available_in_kitchen', 'available_in_bar', 'station_mode'])
            ]);

            // Sorting
            $sortBy = $request->get('sort_by', 'current_stock');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if (in_array($sortBy, ['current_stock', 'available_stock', 'reorder_level', 'last_restocked'])) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('current_stock', 'asc'); // Default: show low stock first
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            
            // Debug logging
            \Log::info('Inventory API Request:', [
                'all_params' => $request->all(),
                'per_page' => $perPage,
                'page' => $request->get('page', 1)
            ]);
            
            $inventory = $query->paginate($perPage);
            
            // Debug the paginated result
            \Log::info('Inventory Pagination Result:', [
                'total' => $inventory->total(),
                'count' => $inventory->count(),
                'current_page' => $inventory->currentPage(),
                'last_page' => $inventory->lastPage(),
                'per_page' => $inventory->perPage(),
                'items_in_data' => count($inventory->items())
            ]);

            return $this->successResponse($inventory, 'Inventory data retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve inventory data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get inventory statistics
     */
    public function stats()
    {
        try {
            $stats = [
                'total_items' => Inventory::count(),
                'low_stock_items' => Inventory::whereRaw('current_stock <= reorder_level AND reorder_level > 0')->count(),
                'out_of_stock_items' => Inventory::where('current_stock', 0)->count(),
                'overstock_items' => Inventory::whereRaw('current_stock > max_stock_level AND max_stock_level > 0')->count(),
                'total_stock_value' => Inventory::sum(DB::raw('current_stock * average_cost')),
                'total_stock_quantity' => Inventory::sum('current_stock'),
                'total_reserved_stock' => Inventory::sum('reserved_stock'),
                'items_need_restock' => Inventory::whereRaw('current_stock <= reorder_level AND reorder_level > 0')->count(),
            ];

            // Get stock distribution by category (optional - skip if fails)
            try {
                $stockByCategory = Inventory::select(
                    DB::raw('COALESCE(categories.name, "Uncategorized") as category_name'),
                    DB::raw('COUNT(*) as item_count'),
                    DB::raw('SUM(current_stock) as total_stock'),
                    DB::raw('SUM(current_stock * average_cost) as total_value')
                )
                ->join('products', 'inventory.id_product', '=', 'products.id_product')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id_category')
                ->groupBy('categories.id_category', 'categories.name')
                ->get();

                $stats['stock_by_category'] = $stockByCategory;
            } catch (\Exception $e) {
                // If category stats fail, just skip them
                $stats['stock_by_category'] = [];
            }

            // Recent stock movements (last 7 days)
            $recentMovements = InventoryMovement::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('movement_type, COUNT(*) as count, SUM(ABS(quantity)) as total_quantity')
                ->groupBy('movement_type')
                ->get()
                ->keyBy('movement_type');

            $stats['recent_movements'] = $recentMovements;

            return $this->successResponse($stats, 'Inventory statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve inventory statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show specific inventory item
     */
    public function show($id)
    {
        try {
            $inventory = Inventory::with(['product', 'creator', 'updater', 'movements' => function($query) {
                $query->with(['user'])->orderBy('created_at', 'desc')->take(10);
            }])->findOrFail($id);

            return $this->successResponse($inventory, 'Inventory item retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Inventory item not found: ' . $e->getMessage(), 404);
        }
    }

    /**
     * Update inventory stock
     */
    public function updateStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'movement_type' => 'required|in:stock_in,stock_out,adjustment,transfer,return',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            $inventory = Inventory::findOrFail($id);
            $movementType = $request->movement_type;
            $quantity = $request->quantity;

            // Validate stock availability for outbound movements
            if (in_array($movementType, ['stock_out', 'transfer']) && $inventory->current_stock < $quantity) {
                return $this->errorResponse('Insufficient stock available', 400);
            }

            // Calculate new stock based on movement type
            $stockBefore = $inventory->current_stock;
            $newStock = $inventory->current_stock;
            $movementQuantity = $quantity;

            switch ($movementType) {
                case 'stock_in':
                case 'return':
                    $newStock += $quantity;
                    break;
                case 'stock_out':
                case 'transfer':
                    $newStock -= $quantity;
                    $movementQuantity = -$quantity;
                    break;
                case 'adjustment':
                    // For adjustments, quantity represents the final stock level
                    $movementQuantity = $quantity - $inventory->current_stock;
                    $newStock = $quantity;
                    break;
            }

            // Create inventory movement record BEFORE updating inventory
            InventoryMovement::create([
                'id_inventory' => $inventory->id_inventory,
                'movement_type' => $movementType === 'stock_in' ? 'in' : ($movementType === 'stock_out' ? 'out' : $movementType),
                'quantity' => abs($movementQuantity),
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
                'unit_cost' => $request->cost_per_unit ?? $inventory->average_cost,
                'total_cost' => ($request->cost_per_unit ?? $inventory->average_cost) * abs($movementQuantity),
                'reference_type' => $request->reference_number ? 'manual' : 'adjustment',
                'reference_id' => null,
                'notes' => $request->reason . ($request->notes ? ' - ' . $request->notes : ''),
                'created_by' => auth()->id(),
                'movement_date' => now()
            ]);

            // Update inventory AFTER creating movement record
            $inventory->update([
                'current_stock' => $newStock,
                'last_restocked' => in_array($movementType, ['stock_in', 'return']) ? now() : $inventory->last_restocked,
                'average_cost' => $request->filled('cost_per_unit') 
                    ? $this->calculateAverageCost($inventory, $movementQuantity, $request->cost_per_unit)
                    : $inventory->average_cost,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            // Sinkronisasi stok ke tabel product/variant
            InventorySyncService::syncStockToProductAndVariant($inventory);

            // Reload inventory with relationships
            $inventory->load(['product']);

            return $this->successResponse($inventory, 'Stock updated successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Failed to update stock: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk stock update
     */
    public function bulkUpdateStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id_inventory' => 'required|exists:inventory,id_inventory',
            'items.*.movement_type' => 'required|in:stock_in,stock_out,adjustment',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'required|string|max:255',
            'items.*.cost_per_unit' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            $results = [];
            $errors = [];

            foreach ($request->items as $index => $item) {
                try {
                    $inventory = Inventory::findOrFail($item['id_inventory']);
                    
                    // Same logic as updateStock but for each item
                    $movementType = $item['movement_type'];
                    $quantity = $item['quantity'];

                    if (in_array($movementType, ['stock_out']) && $inventory->current_stock < $quantity) {
                        $errors[] = "Item {$index}: Insufficient stock available";
                        continue;
                    }

                    $newStock = $inventory->current_stock;
                    $movementQuantity = $quantity;

                    switch ($movementType) {
                        case 'stock_in':
                            $newStock += $quantity;
                            break;
                        case 'stock_out':
                            $newStock -= $quantity;
                            $movementQuantity = -$quantity;
                            break;
                        case 'adjustment':
                            $movementQuantity = $quantity - $inventory->current_stock;
                            $newStock = $quantity;
                            break;
                    }

                    $inventory->update([
                        'current_stock' => $newStock,
                        'last_restocked' => $movementType === 'stock_in' ? now() : $inventory->last_restocked,
                        'updated_by' => auth()->id()
                    ]);

                    InventoryMovement::create([
                        'id_inventory' => $inventory->id_inventory,
                        'movement_type' => $movementType === 'stock_in' ? 'in' : 'out',
                        'quantity' => $movementQuantity,
                        'stock_before' => $inventory->current_stock,
                        'stock_after' => $newStock,
                        'unit_cost' => $item['cost_per_unit'] ?? $inventory->average_cost,
                        'total_cost' => ($item['cost_per_unit'] ?? $inventory->average_cost) * abs($movementQuantity),
                        'notes' => $item['reason'],
                        'movement_date' => now(),
                        'reference_type' => 'bulk_update',
                        'reference_id' => auth()->id(),
                        'created_by' => auth()->id()
                    ]);

                    $results[] = $inventory->id_inventory;

                } catch (\Exception $e) {
                    $errors[] = "Item {$index}: " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                DB::rollback();
                return $this->errorResponse('Bulk update failed', 400, $errors);
            }

            DB::commit();

            // Sinkronisasi stok untuk semua item yang diupdate
            foreach ($results as $inventoryId) {
                $inventory = Inventory::find($inventoryId);
                if ($inventory) {
                    InventorySyncService::syncStockToProductAndVariant($inventory);
                }
            }

            return $this->successResponse([
                'updated_items' => count($results),
                'item_ids' => $results
            ], 'Bulk stock update completed successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Failed to perform bulk update: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get low stock alerts
     */
    public function lowStockAlerts()
    {
        try {
            $lowStockItems = Inventory::with(['product'])
                ->whereRaw('current_stock <= reorder_level AND reorder_level > 0')
                ->orderBy('current_stock', 'asc')
                ->get();

            return $this->successResponse($lowStockItems, 'Low stock alerts retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve low stock alerts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get inventory movements history
     */
    public function movements(Request $request, $id = null)
    {
        try {
            $query = InventoryMovement::with(['inventory.product', 'user']);

            if ($id) {
                $query->where('id_inventory', $id);
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Filter by movement type
            if ($request->filled('movement_type')) {
                $query->where('movement_type', $request->movement_type);
            }

            $movements = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->successResponse($movements, 'Inventory movements retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve movements: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate average cost for inventory
     */
    private function calculateAverageCost($inventory, $quantity, $newCostPerUnit)
    {
        if ($quantity <= 0) {
            return $inventory->average_cost;
        }

        $currentValue = $inventory->current_stock * $inventory->average_cost;
        $newValue = $quantity * $newCostPerUnit;
        $totalQuantity = $inventory->current_stock + $quantity;

        return $totalQuantity > 0 ? ($currentValue + $newValue) / $totalQuantity : $newCostPerUnit;
    }

    /**
     * Set reorder levels
     */
    public function setReorderLevel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reorder_level' => 'required|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $inventory = Inventory::findOrFail($id);
            
            $inventory->update([
                'reorder_level' => $request->reorder_level,
                'max_stock_level' => $request->max_stock_level,
                'updated_by' => auth()->id()
            ]);

            return $this->successResponse($inventory, 'Reorder level updated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update reorder level: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Sync inventory stock to products and variants tables
     */
    public function syncStock()
    {
        try {
            $result = InventorySyncService::syncAllInventory();
            return $this->successResponse($result, 'Inventory synchronization completed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to sync inventory: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Record a single inventory movement (for adjustments)
     */
    public function recordMovement(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_id' => 'required|exists:items,id_item',
                'movement_type' => 'required|in:adjustment',
                'quantity' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:500',
                'cost_per_unit' => 'nullable|numeric|min:0'
            ]);

            // Find inventory by item_id
            $inventory = Inventory::where('id_item', $validated['item_id'])->first();
            
            if (!$inventory) {
                return $this->errorResponse('Inventory record not found for this item', 404);
            }

            $stockBefore = $inventory->current_stock;
            $newStock = $validated['quantity'];

            // Update inventory stock
            $inventory->update([
                'current_stock' => $newStock,
            ]);

            // Create inventory movement record
            InventoryMovement::create([
                'id_inventory' => $inventory->id_inventory,
                'movement_type' => 'adjustment',
                'quantity' => abs($newStock - $stockBefore), // Record the absolute difference
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
                'unit_cost' => $validated['cost_per_unit'] ?? $inventory->average_cost,
                'total_cost' => ($validated['cost_per_unit'] ?? $inventory->average_cost) * abs($newStock - $stockBefore),
                'reference_type' => 'manual_adjustment',
                'reference_id' => null,
                'notes' => $validated['notes'] ?? 'Manual stock adjustment',
                'movement_date' => now(),
                'created_by' => auth()->id(),
            ]);

            return $this->successResponse([
                'inventory' => $inventory->fresh(),
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
                'difference' => $newStock - $stockBefore
            ], 'Stock adjustment recorded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record movement: ' . $e->getMessage(), 500);
        }
    }
}
