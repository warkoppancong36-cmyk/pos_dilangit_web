<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VariantItem;
use App\Models\Variant;
use App\Models\Item;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VariantItemController extends Controller
{
    use ApiResponseTrait;
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

            return $this->paginatedResponse($variantItems, 'Variant items retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error retrieving variant items: ' . $e->getMessage());
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $variantItem = VariantItem::with(['variant.product', 'item.inventory'])->findOrFail($id);

            return $this->successResponse($variantItem, 'Variant item retrieved successfully');

        } catch (\Exception $e) {
            return $this->notFoundResponse('Variant item not found');
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
                return $this->validationErrorResponse($validator->errors());
            }

            // Check if combination already exists
            $exists = VariantItem::where('id_variant', $request->id_variant)
                                 ->where('id_item', $request->id_item)
                                 ->exists();

            if ($exists) {
                return $this->badRequestResponse('Item already exists in this variant composition');
            }

            $variantItemData = $request->all();
            $variantItemData['created_by'] = Auth::id();

            $variantItem = VariantItem::create($variantItemData);
            $variantItem->load(['variant.product', 'item.inventory']);

            return $this->createdResponse($variantItem, 'Variant item created successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error creating variant item: ' . $e->getMessage());
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
                return $this->validationErrorResponse($validator->errors());
            }

            // Check if combination already exists (exclude current record)
            $exists = VariantItem::where('id_variant', $request->id_variant)
                                 ->where('id_item', $request->id_item)
                                 ->where('id_variant_item', '!=', $id)
                                 ->exists();

            if ($exists) {
                return $this->badRequestResponse('Item already exists in this variant composition');
            }

            $variantItemData = $request->all();
            $variantItemData['updated_by'] = Auth::id();

            $variantItem->update($variantItemData);
            $variantItem->load(['variant.product', 'item.inventory']);

            return $this->successResponse($variantItem, 'Variant item updated successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error updating variant item: ' . $e->getMessage());
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $variantItem = VariantItem::findOrFail($id);
            $variantItem->delete();

            return $this->deletedResponse('Variant item deleted successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error deleting variant item: ' . $e->getMessage());
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
                return $this->validationErrorResponse($validator->errors());
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
                'data' => $variantItems,
                'message' => count($variantItems) . ' variant items created successfully'
            ];

            if (!empty($errors)) {
                $response['warnings'] = $errors;
            }

            return $this->createdResponse($response['data'], $response['message']);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Error creating variant items: ' . $e->getMessage());
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
                return $this->validationErrorResponse($validator->errors());
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

            return $this->successResponse($updatedItems, count($updatedItems) . ' variant items updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Error updating variant items: ' . $e->getMessage());
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
                return $this->validationErrorResponse($validator->errors());
            }

            VariantItem::whereIn('id_variant_item', $request->variant_item_ids)->delete();

            return $this->deletedResponse(count($request->variant_item_ids) . ' variant items deleted successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error deleting variant items: ' . $e->getMessage());
        }
    }

    public function getVariantsByProduct($productId): JsonResponse
    {
        try {
            $variants = Variant::with(['variantItems.item.inventory'])
                              ->where('id_product', $productId)
                              ->where('active', true)
                              ->orderBy('name', 'asc')
                              ->get();

            if ($variants->isEmpty()) {
                return $this->successResponse([], 'No variants found for this product');
            }

            // Format data dengan informasi lengkap
            $formattedVariants = $variants->map(function ($variant) {
                return [
                    'id_variant' => $variant->id_variant,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'description' => $variant->description,
                    'price' => $variant->price,
                    'cost' => $variant->cost,
                    'active' => $variant->active,
                    'product' => [
                        'id_product' => $variant->product->id_product ?? null,
                        'name' => $variant->product->name ?? null,
                        'sku' => $variant->product->sku ?? null
                    ],
                    'composition' => $variant->variantItems->map(function ($variantItem) {
                        return [
                            'id_variant_item' => $variantItem->id_variant_item,
                            'quantity_needed' => $variantItem->quantity_needed,
                            'unit' => $variantItem->unit,
                            'cost_per_unit' => $variantItem->cost_per_unit,
                            'total_cost' => $variantItem->total_cost,
                            'is_critical' => $variantItem->is_critical,
                            'notes' => $variantItem->notes,
                            'item' => [
                                'id_item' => $variantItem->item->id_item,
                                'name' => $variantItem->item->name,
                                'sku' => $variantItem->item->sku,
                                'unit' => $variantItem->item->unit,
                                'inventory' => [
                                    'stock_quantity' => $variantItem->item->inventory->stock_quantity ?? 0,
                                    'minimum_stock' => $variantItem->item->inventory->minimum_stock ?? 0,
                                    'maximum_stock' => $variantItem->item->inventory->maximum_stock ?? null
                                ]
                            ]
                        ];
                    }),
                    'summary' => [
                        'total_items' => $variant->variantItems->count(),
                        'critical_items' => $variant->variantItems->where('is_critical', true)->count(),
                        'total_composition_cost' => $variant->variantItems->sum('total_cost'),
                        'can_produce' => $this->checkCanProduce($variant)
                    ]
                ];
            });

            return $this->successResponse($formattedVariants, 'Variants retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error retrieving variants: ' . $e->getMessage());
        }
    }

    private function checkCanProduce($variant): bool
    {
        foreach ($variant->variantItems as $variantItem) {
            $stockQuantity = $variantItem->item->inventory->stock_quantity ?? 0;
            if ($stockQuantity < $variantItem->quantity_needed) {
                return false;
            }
        }
        return true;
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

            $data = [
                'variant' => $variant,
                'composition' => $variantItems,
                'summary' => [
                    'total_items' => $variantItems->count(),
                    'critical_items' => $variantItems->where('is_critical', true)->count(),
                    'total_cost' => $variantItems->sum('total_cost')
                ]
            ];

            return $this->successResponse($data, 'Variant composition retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error retrieving variant composition: ' . $e->getMessage());
        }
    }
}
