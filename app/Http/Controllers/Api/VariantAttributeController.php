<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VariantAttribute;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VariantAttributeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = VariantAttribute::with(['product']);

            // Filter by product
            if ($request->filled('product_id')) {
                $query->byProduct($request->product_id);
            }

            // Filter by attribute type
            if ($request->filled('attribute_type')) {
                $query->byType($request->attribute_type);
            }

            // Filter by active status
            if ($request->filled('active')) {
                if ($request->active === 'true') {
                    $query->active();
                } else {
                    $query->where('active', false);
                }
            }

            // Filter by required status
            if ($request->filled('required_only') && $request->required_only === 'true') {
                $query->required();
            }

            // Search in attribute name
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('attribute_name', 'like', "%{$search}%");
            }

            $variantAttributes = $query->ordered()->get();

            return response()->json([
                'success' => true,
                'data' => $variantAttributes,
                'message' => 'Variant attributes retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving variant attributes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $variantAttribute = VariantAttribute::with(['product'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $variantAttribute,
                'message' => 'Variant attribute retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Variant attribute not found'
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_product' => 'required|exists:products,id_product',
                'attribute_name' => 'required|string|max:100',
                'attribute_type' => 'required|in:select,color,text,number,boolean',
                'attribute_values' => 'required|array|min:1',
                'attribute_values.*' => 'required|string|max:100',
                'is_required' => 'boolean',
                'sort_order' => 'integer|min:0',
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if attribute name already exists for this product
            $exists = VariantAttribute::where('id_product', $request->id_product)
                                     ->where('attribute_name', $request->attribute_name)
                                     ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attribute name already exists for this product'
                ], 422);
            }

            $attributeData = $request->all();
            $attributeData['created_by'] = Auth::id();

            // Set sort_order to max + 1 if not provided
            if (!isset($attributeData['sort_order'])) {
                $maxOrder = VariantAttribute::where('id_product', $request->id_product)->max('sort_order') ?? 0;
                $attributeData['sort_order'] = $maxOrder + 1;
            }

            $variantAttribute = VariantAttribute::create($attributeData);
            $variantAttribute->load(['product']);

            return response()->json([
                'success' => true,
                'data' => $variantAttribute,
                'message' => 'Variant attribute created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating variant attribute: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $variantAttribute = VariantAttribute::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'id_product' => 'required|exists:products,id_product',
                'attribute_name' => 'required|string|max:100',
                'attribute_type' => 'required|in:select,color,text,number,boolean',
                'attribute_values' => 'required|array|min:1',
                'attribute_values.*' => 'required|string|max:100',
                'is_required' => 'boolean',
                'sort_order' => 'integer|min:0',
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if attribute name already exists for this product (exclude current record)
            $exists = VariantAttribute::where('id_product', $request->id_product)
                                     ->where('attribute_name', $request->attribute_name)
                                     ->where('id_variant_attribute', '!=', $id)
                                     ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attribute name already exists for this product'
                ], 422);
            }

            $attributeData = $request->all();
            $attributeData['updated_by'] = Auth::id();

            $variantAttribute->update($attributeData);
            $variantAttribute->load(['product']);

            return response()->json([
                'success' => true,
                'data' => $variantAttribute,
                'message' => 'Variant attribute updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating variant attribute: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $variantAttribute = VariantAttribute::findOrFail($id);
            $variantAttribute->delete();

            return response()->json([
                'success' => true,
                'message' => 'Variant attribute deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting variant attribute: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateSortOrder(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'attributes' => 'required|array',
                'attributes.*.id_variant_attribute' => 'required|exists:variant_attributes,id_variant_attribute',
                'attributes.*.sort_order' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            foreach ($request->attributes as $attributeData) {
                VariantAttribute::where('id_variant_attribute', $attributeData['id_variant_attribute'])
                               ->update([
                                   'sort_order' => $attributeData['sort_order'],
                                   'updated_by' => Auth::id()
                               ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sort order updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating sort order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProductAttributes($productId): JsonResponse
    {
        try {
            $attributes = VariantAttribute::byProduct($productId)
                                        ->active()
                                        ->ordered()
                                        ->get();

            return response()->json([
                'success' => true,
                'data' => $attributes,
                'message' => 'Product attributes retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product attributes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateVariantCombinations($productId): JsonResponse
    {
        try {
            $attributes = VariantAttribute::byProduct($productId)
                                        ->active()
                                        ->required()
                                        ->ordered()
                                        ->get();

            if ($attributes->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No required attributes found for this product'
                ], 422);
            }

            $combinations = $this->generateCombinations($attributes->toArray());

            return response()->json([
                'success' => true,
                'data' => [
                    'attributes' => $attributes,
                    'combinations' => $combinations,
                    'total_combinations' => count($combinations)
                ],
                'message' => 'Variant combinations generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating variant combinations: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateCombinations($attributes, $i = 0, $current = []): array
    {
        if ($i == count($attributes)) {
            return [$current];
        }

        $combinations = [];
        $attribute = $attributes[$i];
        
        foreach ($attribute['attribute_values'] as $value) {
            $newCurrent = $current;
            $newCurrent[$attribute['attribute_name']] = $value;
            $combinations = array_merge($combinations, $this->generateCombinations($attributes, $i + 1, $newCurrent));
        }

        return $combinations;
    }
}
