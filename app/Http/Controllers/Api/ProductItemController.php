<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductItem;
use App\Models\Product;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProductItem::with(['product', 'item.inventory']);

            // Search filter (search in product name, SKU, or item name)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                })->orWhereHas('item', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            // Filter by product
            if ($request->has('product_id') && !empty($request->product_id)) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by item
            if ($request->has('item_id') && !empty($request->item_id)) {
                $query->where('item_id', $request->item_id);
            }

            // Filter by active status
            if ($request->has('active') && $request->active !== 'all') {
                $query->where('active', $request->active === 'true');
            }

            // Filter by critical items only
            if ($request->has('critical_only') && $request->critical_only === 'true') {
                $query->where('is_critical', true);
            }

            // Filter by stock status
            if ($request->filled('stock_status')) {
                switch ($request->stock_status) {
                    case 'safe':
                        $query->whereHas('item.inventory', function ($q) {
                            $q->whereRaw('current_stock > min_stock');
                        });
                        break;
                    case 'low':
                        $query->whereHas('item.inventory', function ($q) {
                            $q->whereRaw('current_stock <= min_stock AND current_stock > 0');
                        });
                        break;
                    case 'out':
                        $query->whereHas('item.inventory', function ($q) {
                            $q->where('current_stock', '<=', 0);
                        });
                        break;
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_desc');
            switch ($sortBy) {
                case 'name_asc':
                    $query->join('products', 'product_items.product_id', '=', 'products.id_product')
                          ->orderBy('products.name', 'asc')
                          ->select('product_items.*');
                    break;
                case 'name_desc':
                    $query->join('products', 'product_items.product_id', '=', 'products.id_product')
                          ->orderBy('products.name', 'desc')
                          ->select('product_items.*');
                    break;
                case 'price_asc':
                    $query->join('products', 'product_items.product_id', '=', 'products.id_product')
                          ->orderBy('products.price', 'asc')
                          ->select('product_items.*');
                    break;
                case 'price_desc':
                    $query->join('products', 'product_items.product_id', '=', 'products.id_product')
                          ->orderBy('products.price', 'desc')
                          ->select('product_items.*');
                    break;
                case 'created_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'created_desc':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $productItems = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $productItems,
                'message' => 'Product items retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id_product',
                'item_id' => 'required|exists:items,id_item',
                'quantity_needed' => 'required|numeric|min:0.01',
                'unit' => 'required|string|max:50',
                'cost_per_unit' => 'nullable|numeric|min:0',
                'is_critical' => 'boolean',
                'notes' => 'nullable|string',
                'active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Check for existing relationship
            $existingProductItem = ProductItem::where('product_id', $validated['product_id'])
                ->where('item_id', $validated['item_id'])
                ->first();

            if ($existingProductItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'This item is already assigned to this product',
                    'error_code' => 'duplicate_relationship'
                ], 409);
            }

            $productItem = ProductItem::create([
                ...$validated,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            $productItem->load(['product', 'item', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $productItem,
                'message' => 'Product item created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating product item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $productItem = ProductItem::with(['product', 'item', 'creator', 'updater'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $productItem,
                'message' => 'Product item retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product item not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $productItem = ProductItem::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'quantity_needed' => 'required|numeric|min:0.01',
                'cost_per_unit' => 'nullable|numeric|min:0',
                'is_critical' => 'boolean',
                'notes' => 'nullable|string',
                'active' => 'boolean',
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

            DB::beginTransaction();
            
            $productItem->update($validated);
            
            DB::commit();

            $productItem->load(['product', 'item', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $productItem,
                'message' => 'Product item updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error updating product item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $productItem = ProductItem::findOrFail($id);

            DB::beginTransaction();
            
            $productItem->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product item deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProductionCapacity($productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $productItems = ProductItem::with('item')
                ->where('product_id', $productId)
                ->get();

            if ($productItems->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'can_produce' => 0,
                        'limiting_items' => [],
                        'details' => []
                    ],
                    'message' => 'No items assigned to this product'
                ]);
            }

            $capacities = [];
            $details = [];

            foreach ($productItems as $productItem) {
                $availableQuantity = $productItem->item->current_stock;
                $neededQuantity = $productItem->quantity_needed;
                
                $canProduce = $neededQuantity > 0 ? floor($availableQuantity / $neededQuantity) : 0;
                
                $capacities[] = $canProduce;
                
                $details[] = [
                    'item_id' => $productItem->item_id,
                    'item_name' => $productItem->item->name,
                    'available_stock' => $availableQuantity,
                    'needed_per_product' => $neededQuantity,
                    'can_produce' => $canProduce,
                    'is_limiting' => false
                ];
            }

            $maxProduction = min($capacities);
            
            // Find limiting items
            $limitingItems = [];
            foreach ($details as $index => $detail) {
                if ($capacities[$index] === $maxProduction && $maxProduction < 999999) {
                    $limitingItems[] = $detail['item_name'];
                    $details[$index]['is_limiting'] = true;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'can_produce' => $maxProduction,
                    'limiting_items' => array_unique($limitingItems),
                    'details' => $details
                ],
                'message' => 'Production capacity calculated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating production capacity: ' . $e->getMessage()
            ], 500);
        }
    }
}
