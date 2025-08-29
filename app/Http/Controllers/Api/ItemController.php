<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            // Debug: Log all request parameters
            \Log::info('Items API Request Parameters:', $request->all());
            
            $query = Item::with(['creator', 'updater', 'inventory'])
                ->orderBy('created_at', 'desc');

            // Filter by active status
            if ($request->has('active') && $request->active !== 'all') {
                $query->where('active', $request->active === 'true');
            }

            // Filter by unit
            if ($request->has('unit') && !empty($request->unit)) {
                $query->where('unit', $request->unit);
            }

            // Filter by stock status
            if ($request->has('stock_status') && !empty($request->stock_status) && $request->stock_status !== '') {
                \Log::info('Applying stock_status filter:', ['stock_status' => $request->stock_status]);
                switch ($request->stock_status) {
                    case 'low_stock':
                        $query->whereHas('inventory', function ($q) {
                            $q->whereRaw('current_stock <= reorder_level');
                        });
                        break;
                    case 'out_of_stock':
                        $query->whereHas('inventory', function ($q) {
                            $q->where('current_stock', '<=', 0);
                        });
                        break;
                    case 'in_stock':
                        $query->whereHas('inventory', function ($q) {
                            $q->where('current_stock', '>', 0)
                              ->whereRaw('current_stock > reorder_level');
                        });
                        break;
                    default:
                        \Log::info('Unknown stock_status value:', ['stock_status' => $request->stock_status]);
                }
            }

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                \Log::info('Applying search filter:', ['search' => $request->search]);
                $query->search($request->search);
            }

            // Station filtering
            if ($request->has('station') && $request->station !== 'all') {
                switch ($request->station) {
                    case 'kitchen':
                        $query->where('available_in_kitchen', true)
                              ->where('available_in_bar', false);
                        break;
                    case 'bar':
                        $query->where('available_in_kitchen', false)
                              ->where('available_in_bar', true);
                        break;
                    case 'both':
                        $query->where('available_in_kitchen', true)
                              ->where('available_in_bar', true);
                        break;
                }
            }

            // Handle pagination - support 'all' parameter to get all items
            $perPage = $request->get('per_page', 15);
            
            if ($perPage === 'all') {
                // Get all items without pagination
                $items = $query->get();
                
                // Format response to match pagination structure for frontend compatibility
                $response = [
                    'current_page' => 1,
                    'data' => $items,
                    'first_page_url' => null,
                    'from' => 1,
                    'last_page' => 1,
                    'last_page_url' => null,
                    'links' => [],
                    'next_page_url' => null,
                    'path' => $request->url(),
                    'per_page' => $items->count(),
                    'prev_page_url' => null,
                    'to' => $items->count(),
                    'total' => $items->count()
                ];
            } else {
                // Use normal pagination
                $response = $query->paginate((int) $perPage);
            }

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Items retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'unit' => 'required|string|max:50',
                'cost_per_unit' => 'nullable|numeric|min:0',  // Make optional since handled by inventory
                'current_stock' => 'nullable|numeric|min:0',  // Make optional since handled by inventory
                'minimum_stock' => 'nullable|numeric|min:0',  // Make optional since handled by inventory
                'storage_location' => 'nullable|string|max:255',
                'expiry_date' => 'nullable|date|after_or_equal:today',
                'active' => 'boolean',
                'is_delivery' => 'boolean',  // Add new field validation
                'is_takeaway' => 'boolean',  // Add new field validation
                'available_in_kitchen' => 'boolean',  // Add kitchen availability validation
                'available_in_bar' => 'boolean',  // Add bar availability validation
                'properties' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $validated['created_by'] = Auth::id();
            
            // Set default values for fields not managed in UI
            $validated['cost_per_unit'] = $validated['cost_per_unit'] ?? 0;
            $validated['is_delivery'] = $validated['is_delivery'] ?? false;
            $validated['is_takeaway'] = $validated['is_takeaway'] ?? false;
            $validated['available_in_kitchen'] = $validated['available_in_kitchen'] ?? true;
            $validated['available_in_bar'] = $validated['available_in_bar'] ?? true;
            
            DB::beginTransaction();
            
            // Retry logic for handling duplicate item_code
            $maxRetries = 3;
            $retryCount = 0;
            $item = null;
            
            while ($retryCount < $maxRetries) {
                try {
                    $item = Item::create($validated);
                    break; // Success, exit retry loop
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062 && strpos($e->getMessage(), 'item_code') !== false) {
                        // Duplicate item_code error, retry
                        $retryCount++;
                        if ($retryCount >= $maxRetries) {
                            // If still failing after retries, generate a completely unique code
                            $validated['item_code'] = 'ITM' . now()->format('ymdHis') . rand(100, 999);
                            $item = Item::create($validated);
                            break;
                        }
                        // Wait a small amount before retry
                        usleep(10000); // 10ms
                        continue;
                    } else {
                        // Different error, rethrow
                        throw $e;
                    }
                }
            }
            
            DB::commit();

            $item->load(['creator']);

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Item created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $item = Item::with(['creator', 'updater', 'productItems.product', 'purchaseItems.purchase'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Item retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = Item::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'unit' => 'required|string|max:50',
                'cost_per_unit' => 'nullable|numeric|min:0',  // Make optional since handled by inventory
                'current_stock' => 'nullable|numeric|min:0',  // Make optional since handled by inventory
                'minimum_stock' => 'nullable|numeric|min:0',  // Make optional since handled by inventory
                'storage_location' => 'nullable|string|max:255',
                'expiry_date' => 'nullable|date|after_or_equal:today',
                'active' => 'boolean',
                'is_delivery' => 'boolean',  // Add new field validation
                'is_takeaway' => 'boolean',  // Add new field validation
                'available_in_kitchen' => 'boolean',  // Add kitchen availability validation
                'available_in_bar' => 'boolean',  // Add bar availability validation
                'properties' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $validated['updated_by'] = Auth::id();
            
            // Set default values for fields not managed in UI
            if (isset($validated['cost_per_unit']) && $validated['cost_per_unit'] === null) {
                $validated['cost_per_unit'] = 0;
            }
            if (isset($validated['is_delivery']) && $validated['is_delivery'] === null) {
                $validated['is_delivery'] = false;
            }
            if (isset($validated['is_takeaway']) && $validated['is_takeaway'] === null) {
                $validated['is_takeaway'] = false;
            }
            if (isset($validated['available_in_kitchen']) && $validated['available_in_kitchen'] === null) {
                $validated['available_in_kitchen'] = true;
            }
            if (isset($validated['available_in_bar']) && $validated['available_in_bar'] === null) {
                $validated['available_in_bar'] = true;
            }

            DB::beginTransaction();
            
            $item->update($validated);
            
            DB::commit();

            $item->load(['creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Item updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error updating item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = Item::findOrFail($id);

            // Check if item is used in any active products
            $productCount = $item->productItems()->count();
            if ($productCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete item. It is used in {$productCount} product(s)."
                ], 422);
            }

            DB::beginTransaction();
            
            $item->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_items' => Item::count(),
                'active_items' => Item::active()->count(),
                'inactive_items' => Item::inactive()->count(),
                'low_stock_items' => Item::lowStock()->count(),
                'out_of_stock_items' => Item::outOfStock()->count(),
                'total_value' => Item::active()->get()->sum(function($item) {
                    return $item->calculateTotalValue();
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving stats: ' . $e->getMessage()
            ], 500);
        }
    }
}
