<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VariantItem;
use App\Models\Variant;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VariantItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = VariantItem::with(['variant.product', 'item.inventory']);

            // Filter by variant
            if ($request->filled('variant_id')) {
                $query->byVariant($request->variant_id);
            }

            // Filter by item
            if ($request->filled('item_id')) {
                $query->byItem($request->item_id);
            }

            // Filter by active status
            if ($request->filled('active')) {
                if ($request->active === 'true') {
                    $query->active();
                } else {
                    $query->where('active', false);
                }
            }

            // Filter by critical items only
            if ($request->filled('critical_only') && $request->critical_only === 'true') {
                $query->critical();
            }

            // Search in variant name or item name
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('variant', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                })->orWhereHas('item', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if ($sortBy === 'variant_name') {
                $query->join('variants', 'variant_items.id_variant', '=', 'variants.id_variant')
                      ->orderBy('variants.name', $sortOrder)
                      ->select('variant_items.*');
            } elseif ($sortBy === 'item_name') {
                $query->join('items', 'variant_items.id_item', '=', 'items.id_item')
                      ->orderBy('items.name', $sortOrder)
                      ->select('variant_items.*');
            } else {
                $allowedSorts = ['quantity_needed', 'cost_per_unit', 'created_at', 'updated_at'];
                if (in_array($sortBy, $allowedSorts)) {
                    $query->orderBy($sortBy, $sortOrder);
                }
            }

            $perPage = $request->get('per_page', 15);
            $variantItems = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $variantItems,
                'message' => 'Variant items retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving variant items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $variantItem = VariantItem::with(['variant.product', 'item.inventory'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $variantItem,
                'message' => 'Variant item retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Variant item not found'
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_variant' => 'required|exists:variants,id_variant',
                'id_item' => 'required|exists:items,id_item',
                'quantity_needed' => 'required|numeric|min:0.001',
                'unit' => 'required|string|max:50',
                'cost_per_unit' => 'nullable|numeric|min:0',
                'is_critical' => 'boolean',
                'notes' => 'nullable|string|max:1000',
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if combination already exists
            $exists = VariantItem::where('id_variant', $request->id_variant)
                                 ->where('id_item', $request->id_item)
                                 ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item already exists in this variant composition'
                ], 422);
            }

            $variantItemData = $request->all();
            $variantItemData['created_by'] = Auth::id();

            $variantItem = VariantItem::create($variantItemData);
            $variantItem->load(['variant.product', 'item.inventory']);

            return response()->json([
                'success' => true,
                'data' => $variantItem,
                'message' => 'Variant item created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating variant item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $variantItem = VariantItem::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'id_variant' => 'required|exists:variants,id_variant',
                'id_item' => 'required|exists:items,id_item',
                'quantity_needed' => 'required|numeric|min:0.001',
                'unit' => 'required|string|max:50',
                'cost_per_unit' => 'nullable|numeric|min:0',
                'is_critical' => 'boolean',
                'notes' => 'nullable|string|max:1000',
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if combination already exists (exclude current record)
            $exists = VariantItem::where('id_variant', $request->id_variant)
                                 ->where('id_item', $request->id_item)
                                 ->where('id_variant_item', '!=', $id)
                                 ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item already exists in this variant composition'
                ], 422);
            }

            $variantItemData = $request->all();
            $variantItemData['updated_by'] = Auth::id();

            $variantItem->update($variantItemData);
            $variantItem->load(['variant.product', 'item.inventory']);

            return response()->json([
                'success' => true,
                'data' => $variantItem,
                'message' => 'Variant item updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating variant item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $variantItem = VariantItem::findOrFail($id);
            $variantItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Variant item deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting variant item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_variant' => 'required|exists:variants,id_variant',
                'items' => 'required|array|min:1',
                'items.*.id_item' => 'required|exists:items,id_item',
                'items.*.quantity_needed' => 'required|numeric|min:0.001',
                'items.*.unit' => 'required|string|max:50',
                'items.*.cost_per_unit' => 'nullable|numeric|min:0',
                'items.*.is_critical' => 'boolean',
                'items.*.notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $variantItems = [];
            $errors = [];

            foreach ($request->items as $index => $itemData) {
                // Check if combination already exists
                $exists = VariantItem::where('id_variant', $request->id_variant)
                                     ->where('id_item', $itemData['id_item'])
                                     ->exists();

                if ($exists) {
                    $item = Item::find($itemData['id_item']);
                    $errors[] = "Item '{$item->name}' already exists in this variant composition";
                    continue;
                }

                $variantItemData = $itemData;
                $variantItemData['id_variant'] = $request->id_variant;
                $variantItemData['active'] = true;
                $variantItemData['created_by'] = Auth::id();

                $variantItem = VariantItem::create($variantItemData);
                $variantItem->load(['variant.product', 'item.inventory']);
                $variantItems[] = $variantItem;
            }

            DB::commit();

            $response = [
                'success' => true,
                'data' => $variantItems,
                'message' => count($variantItems) . ' variant items created successfully'
            ];

            if (!empty($errors)) {
                $response['warnings'] = $errors;
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating variant items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.id_variant_item' => 'required|exists:variant_items,id_variant_item',
                'items.*.quantity_needed' => 'required|numeric|min:0.001',
                'items.*.unit' => 'required|string|max:50',
                'items.*.cost_per_unit' => 'nullable|numeric|min:0',
                'items.*.is_critical' => 'boolean',
                'items.*.notes' => 'nullable|string|max:1000',
                'items.*.active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $updatedItems = [];

            foreach ($request->items as $itemData) {
                $variantItem = VariantItem::findOrFail($itemData['id_variant_item']);
                
                $updateData = $itemData;
                $updateData['updated_by'] = Auth::id();
                unset($updateData['id_variant_item']);

                $variantItem->update($updateData);
                $variantItem->load(['variant.product', 'item.inventory']);
                $updatedItems[] = $variantItem;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $updatedItems,
                'message' => count($updatedItems) . ' variant items updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating variant items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'variant_item_ids' => 'required|array',
                'variant_item_ids.*' => 'exists:variant_items,id_variant_item'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            VariantItem::whereIn('id_variant_item', $request->variant_item_ids)->delete();

            return response()->json([
                'success' => true,
                'message' => count($request->variant_item_ids) . ' variant items deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting variant items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getVariantComposition($variantId): JsonResponse
    {
        try {
            $variantItems = VariantItem::with(['item.inventory'])
                                     ->byVariant($variantId)
                                     ->active()
                                     ->orderBy('is_critical', 'desc')
                                     ->orderBy('created_at', 'asc')
                                     ->get();

            $variant = Variant::with(['product'])->find($variantId);

            return response()->json([
                'success' => true,
                'data' => [
                    'variant' => $variant,
                    'composition' => $variantItems,
                    'summary' => [
                        'total_items' => $variantItems->count(),
                        'critical_items' => $variantItems->where('is_critical', true)->count(),
                        'total_cost' => $variantItems->sum('total_cost')
                    ]
                ],
                'message' => 'Variant composition retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving variant composition: ' . $e->getMessage()
            ], 500);
        }
    }
}
