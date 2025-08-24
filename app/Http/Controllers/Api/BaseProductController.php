<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaseProduct;
use App\Models\BaseProductInventory;
use App\Models\BaseProductMovement;
use App\Models\Category;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class BaseProductController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all base products with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = BaseProduct::with(['category', 'inventory'])
                ->orderBy('name');

            // Search filter
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Category filter
            if ($request->filled('category_id')) {
                $query->byCategory($request->category_id);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Stock status filter
            if ($request->filled('stock_status')) {
                switch ($request->stock_status) {
                    case 'low_stock':
                        $query->lowStock();
                        break;
                    case 'out_of_stock':
                        $query->whereHas('inventory', function($q) {
                            $q->where('current_stock', '<=', 0);
                        });
                        break;
                    case 'in_stock':
                        $query->whereHas('inventory', function($q) {
                            $q->where('current_stock', '>', 0)
                              ->whereRaw('current_stock > min_stock');
                        });
                        break;
                }
            }

            // Perishable filter
            if ($request->filled('is_perishable')) {
                $query->where('is_perishable', $request->boolean('is_perishable'));
            }

            // Sorting
            if ($request->filled('sort_by')) {
                $sortBy = $request->sort_by;
                $sortOrder = $request->get('sort_order', 'asc');
                
                switch ($sortBy) {
                    case 'stock':
                        $query->leftJoin('base_product_inventories', 'base_products.id_base_product', '=', 'base_product_inventories.id_base_product')
                              ->orderBy('base_product_inventories.current_stock', $sortOrder)
                              ->select('base_products.*');
                        break;
                    case 'cost':
                        $query->orderBy('cost_per_unit', $sortOrder);
                        break;
                    case 'price':
                        $query->orderBy('selling_price', $sortOrder);
                        break;
                    default:
                        $query->orderBy($sortBy, $sortOrder);
                        break;
                }
            }

            $baseProducts = $query->paginate($request->get('per_page', 15));

            return $this->paginatedResponse($baseProducts, 'Base products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve base products: ' . $e->getMessage());
        }
    }

    /**
     * Store a new base product
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:base_products,sku',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id_category',
            'unit' => 'nullable|string|max:20',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0|gte:min_stock',
            'is_active' => 'sometimes|in:0,1,true,false',
            'is_perishable' => 'sometimes|in:0,1,true,false',
            'shelf_life_days' => 'nullable|integer|min:1|required_if:is_perishable,true',
            'storage_type' => 'nullable|string|in:freezer,refrigerator,room_temp,dry_storage',
            'supplier_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'nutritional_info' => 'nullable|array',
            'allergen_info' => 'nullable|array',
            'initial_stock' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $data = $request->except(['image', 'initial_stock']);
            
            // Convert string boolean values to actual booleans
            if (isset($data['is_active'])) {
                $data['is_active'] = in_array($data['is_active'], ['1', 'true', true], true);
            }
            if (isset($data['is_perishable'])) {
                $data['is_perishable'] = in_array($data['is_perishable'], ['1', 'true', true], true);
            }
            
            // Set default values for required fields
            $data['unit'] = $data['unit'] ?? 'pcs';
            $data['cost_per_unit'] = $data['cost_per_unit'] ?? 0;
            $data['min_stock'] = $data['min_stock'] ?? 0;
            $data['is_active'] = $data['is_active'] ?? true;
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('base-products', 'public');
                $data['image_url'] = Storage::url($imagePath);
            }

            // Create base product
            $baseProduct = BaseProduct::create($data);

            // Set initial stock if provided
            if ($request->filled('initial_stock') && $request->initial_stock > 0) {
                $baseProduct->updateStock($request->initial_stock, Auth::id(), 'Initial stock');
            }

            DB::commit();

            $baseProduct->load(['category', 'inventory']);

            return $this->createdResponse($baseProduct, 'Base product created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to create base product: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified base product
     */
    public function show($id): JsonResponse
    {
        try {
            $baseProduct = BaseProduct::with([
                'category', 
                'inventory.movements' => function($query) {
                    $query->latest()->limit(10);
                },
                'productCompositions.product',
                'creator',
                'updater'
            ])->find($id);

            if (!$baseProduct) {
                return $this->notFoundResponse('Base product not found');
            }

            return $this->successResponse($baseProduct, 'Base product retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve base product: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified base product
     */
    public function update(Request $request, $id): JsonResponse
    {
        $baseProduct = BaseProduct::find($id);

        if (!$baseProduct) {
            return $this->notFoundResponse('Base product not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:base_products,sku,' . $id . ',id_base_product',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id_category',
            'unit' => 'nullable|string|max:20',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0|gte:min_stock',
            'is_active' => 'sometimes|in:0,1,true,false',
            'is_perishable' => 'sometimes|in:0,1,true,false',
            'shelf_life_days' => 'nullable|integer|min:1|required_if:is_perishable,true',
            'storage_type' => 'nullable|string|in:freezer,refrigerator,room_temp,dry_storage',
            'supplier_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'nutritional_info' => 'nullable|array',
            'allergen_info' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $data = $request->except(['image']);
            
            // Convert string boolean values to actual booleans
            if (isset($data['is_active'])) {
                $data['is_active'] = in_array($data['is_active'], ['1', 'true', true], true);
            }
            if (isset($data['is_perishable'])) {
                $data['is_perishable'] = in_array($data['is_perishable'], ['1', 'true', true], true);
            }
            
            // Set default values for required fields if not provided
            if (isset($data['unit']) && empty($data['unit'])) {
                $data['unit'] = 'pcs';
            }
            if (isset($data['cost_per_unit']) && ($data['cost_per_unit'] === '' || $data['cost_per_unit'] === null)) {
                $data['cost_per_unit'] = 0;
            }
            if (isset($data['min_stock']) && ($data['min_stock'] === '' || $data['min_stock'] === null)) {
                $data['min_stock'] = 0;
            }
            
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($baseProduct->image_url) {
                    $oldImagePath = str_replace('/storage/', '', $baseProduct->image_url);
                    Storage::disk('public')->delete($oldImagePath);
                }
                
                $imagePath = $request->file('image')->store('base-products', 'public');
                $data['image_url'] = Storage::url($imagePath);
            }

            $baseProduct->update($data);
            $baseProduct->load(['category', 'inventory']);

            return $this->successResponse($baseProduct, 'Base product updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update base product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified base product
     */
    public function destroy($id): JsonResponse
    {
        try {
            $baseProduct = BaseProduct::find($id);

            if (!$baseProduct) {
                return $this->notFoundResponse('Base product not found');
            }

            // Check if base product is used in any product compositions
            if ($baseProduct->productCompositions()->count() > 0) {
                return $this->errorResponse('Cannot delete base product that is used in product compositions', 422);
            }

            // Delete image if exists
            if ($baseProduct->image_url) {
                $imagePath = str_replace('/storage/', '', $baseProduct->image_url);
                Storage::disk('public')->delete($imagePath);
            }

            $baseProduct->delete();

            return $this->deletedResponse('Base product deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete base product: ' . $e->getMessage());
        }
    }

    /**
     * Update stock for base product
     */
    public function updateStock(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'stock_adjustment' => 'required|numeric',
            'movement_type' => 'required|in:in,out,adjustment',
            'notes' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $baseProduct = BaseProduct::with('inventory')->find($id);

            if (!$baseProduct) {
                return $this->notFoundResponse('Base product not found');
            }

            DB::beginTransaction();

            $inventory = $baseProduct->inventory;
            if (!$inventory) {
                return $this->errorResponse('Inventory not found for this base product', 422);
            }

            $stockBefore = $inventory->current_stock;
            $adjustment = $request->stock_adjustment;
            
            // Calculate new stock based on movement type
            switch ($request->movement_type) {
                case 'in':
                    $stockAfter = $stockBefore + abs($adjustment);
                    break;
                case 'out':
                    $stockAfter = max(0, $stockBefore - abs($adjustment));
                    break;
                case 'adjustment':
                    $stockAfter = $adjustment; // Direct set to new value
                    break;
            }

            // Update inventory
            $inventory->update(['current_stock' => $stockAfter]);

            // Create movement record
            BaseProductMovement::create([
                'id_base_inventory' => $inventory->id_base_inventory,
                'movement_type' => $request->movement_type,
                'quantity' => abs($stockAfter - $stockBefore),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'unit_cost' => $baseProduct->cost_per_unit,
                'total_cost' => $baseProduct->cost_per_unit * abs($stockAfter - $stockBefore),
                'reference_type' => 'manual_adjustment',
                'reference_number' => $request->reference_number,
                'notes' => $request->notes ?? 'Manual stock adjustment',
                'movement_date' => now(),
                'created_by' => Auth::id()
            ]);

            DB::commit();

            $baseProduct->load(['inventory.movements' => function($query) {
                $query->latest()->limit(5);
            }]);

            return $this->successResponse([
                'base_product' => $baseProduct,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'adjustment' => $stockAfter - $stockBefore
            ], 'Stock updated successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to update stock: ' . $e->getMessage());
        }
    }

    /**
     * Get stock movements for base product
     */
    public function getMovements($id, Request $request): JsonResponse
    {
        try {
            $baseProduct = BaseProduct::find($id);

            if (!$baseProduct) {
                return $this->notFoundResponse('Base product not found');
            }

            $query = BaseProductMovement::with(['creator'])
                ->whereHas('baseInventory', function($q) use ($id) {
                    $q->where('id_base_product', $id);
                })
                ->orderBy('movement_date', 'desc');

            // Date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('movement_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('movement_date', '<=', $request->date_to);
            }

            // Movement type filter
            if ($request->filled('movement_type')) {
                $query->where('movement_type', $request->movement_type);
            }

            $movements = $query->paginate($request->get('per_page', 20));

            return $this->paginatedResponse($movements, 'Stock movements retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve stock movements: ' . $e->getMessage());
        }
    }

    /**
     * Get categories for dropdown
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = Category::active()
                ->orderBy('name')
                ->get(['id_category', 'name', 'description']);

            return $this->successResponse($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve categories: ' . $e->getMessage());
        }
    }

    /**
     * Get base products for dropdown/selection
     */
    public function getForSelection(Request $request): JsonResponse
    {
        try {
            $query = BaseProduct::active()
                ->select(['id_base_product', 'name', 'sku', 'unit', 'cost_per_unit'])
                ->with('inventory:id_base_product,current_stock')
                ->orderBy('name');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('category_id')) {
                $query->byCategory($request->category_id);
            }

            $baseProducts = $query->get();

            return $this->successResponse($baseProducts, 'Base products for selection retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve base products: ' . $e->getMessage());
        }
    }
}
