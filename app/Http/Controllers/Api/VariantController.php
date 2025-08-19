<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Models\VariantItem;
use App\Models\VariantAttribute;
use App\Models\Product;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class VariantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Variant::with(['product', 'variantItems.item.inventory', 'creator', 'updater']);

            // Search filter
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Product filter
            if ($request->filled('product_id')) {
                $query->byProduct($request->product_id);
            }

            // Active filter
            if ($request->filled('active')) {
                if ($request->active === 'active') {
                    $query->active();
                } elseif ($request->active === 'inactive') {
                    $query->inactive();
                }
            }

            // Stock status filter
            if ($request->filled('stock_status')) {
                switch ($request->stock_status) {
                    case 'low':
                        $query->lowStock();
                        break;
                    case 'out':
                        $query->whereHas('inventory', function ($q) {
                            $q->where('current_stock', '<=', 0);
                        });
                        break;
                    case 'sufficient':
                        $query->whereHas('inventory', function ($q) {
                            $q->whereRaw('current_stock > reorder_level');
                        });
                        break;
                }
            }

            // Composition filter
            if ($request->filled('has_composition')) {
                if ($request->boolean('has_composition')) {
                    $query->withComposition();
                } else {
                    $query->withoutComposition();
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $allowedSorts = ['name', 'price', 'cost_price', 'created_at', 'updated_at'];
            
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $perPage = $request->get('per_page', 15);
            $variants = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $variants,
                'message' => 'Variants retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving variants: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $variant = Variant::with([
                'product',
                'variantItems.item.inventory',
                'creator',
                'updater',
                'inventory'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $variant,
                'message' => 'Variant retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found'
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_product' => 'required|exists:products,id_product',
                'name' => 'required|string|max:255',
                'variant_values' => 'required|array',
                'price' => 'required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'barcode' => 'nullable|string|unique:variants,barcode',
                'sku' => 'nullable|string|unique:variants,sku',
                'image' => 'nullable|image|max:2048',
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $variantData = $request->except(['image']);
            $variantData['created_by'] = Auth::id();
            $variantData['active'] = true;

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('variants', 'public');
                $variantData['image'] = $imagePath;
            }

            // Generate SKU if not provided
            if (!$variantData['sku']) {
                $variantData['sku'] = $this->generateUniqueSku($request->id_product, $request->variant_values);
            }

            $variant = Variant::create($variantData);

            // Create inventory entry for variant
            $variant->inventory()->create([
                'current_stock' => 0,
                'reserved_stock' => 0,
                'reorder_level' => 10,
                'location' => 'Warehouse',
                'created_by' => Auth::id()
            ]);

            DB::commit();

            $variant->load(['product', 'variantItems.item.inventory', 'inventory']);

            return response()->json([
                'success' => true,
                'data' => $variant,
                'message' => 'Variant created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating variant: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $variant = Variant::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'id_product' => 'required|exists:products,id_product',
                'name' => 'required|string|max:255',
                'variant_values' => 'required|array',
                'price' => 'required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'barcode' => 'nullable|string|unique:variants,barcode,' . $id . ',id_variant',
                'sku' => 'nullable|string|unique:variants,sku,' . $id . ',id_variant',
                'image' => 'nullable|image|max:2048',
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $variantData = $request->except(['image']);
            $variantData['updated_by'] = Auth::id();

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($variant->image) {
                    Storage::disk('public')->delete($variant->image);
                }
                $imagePath = $request->file('image')->store('variants', 'public');
                $variantData['image'] = $imagePath;
            }

            $variant->update($variantData);

            DB::commit();

            $variant->load(['product', 'variantItems.item.inventory', 'inventory']);

            return response()->json([
                'success' => true,
                'data' => $variant,
                'message' => 'Variant updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating variant: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $variant = Variant::findOrFail($id);

            DB::beginTransaction();

            $variant->update(['deleted_by' => Auth::id()]);
            $variant->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Variant deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting variant: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleActive($id): JsonResponse
    {
        try {
            $variant = Variant::findOrFail($id);
            
            $variant->update([
                'active' => !$variant->active,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $variant,
                'message' => 'Variant status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating variant status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'variant_ids' => 'required|array',
                'variant_ids.*' => 'exists:variants,id_variant'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            Variant::whereIn('id_variant', $request->variant_ids)
                   ->update(['deleted_by' => Auth::id()]);
            
            Variant::whereIn('id_variant', $request->variant_ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($request->variant_ids) . ' variants deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting variants: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProductVariants($productId): JsonResponse
    {
        try {
            $variants = Variant::with(['variantItems.item.inventory', 'inventory'])
                             ->byProduct($productId)
                             ->active()
                             ->get();

            return response()->json([
                'success' => true,
                'data' => $variants,
                'message' => 'Product variants retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product variants: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkProductionCapacity($id): JsonResponse
    {
        try {
            $variant = Variant::with(['variantItems.item.inventory'])->findOrFail($id);
            $quantity = request('quantity', 1);

            $productionCapacity = $variant->canProduce($quantity);

            return response()->json([
                'success' => true,
                'data' => $productionCapacity,
                'message' => 'Production capacity checked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking production capacity: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateUniqueSku($productId, $variantValues): string
    {
        $product = Product::find($productId);
        $baseCode = $product ? strtoupper(substr($product->name, 0, 3)) : 'VAR';
        
        $variantCode = '';
        foreach ($variantValues as $key => $value) {
            $variantCode .= strtoupper(substr($value, 0, 1));
        }
        
        $counter = 1;
        do {
            $sku = $baseCode . '-' . $variantCode . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $exists = Variant::where('sku', $sku)->exists();
            $counter++;
        } while ($exists);

        return $sku;
    }
}
