<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaseProductComposition;
use App\Models\BaseProduct;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BaseProductCompositionController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of base product compositions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = BaseProductComposition::with(['baseProduct', 'ingredientBaseProduct', 'ingredientItem'])
                ->orderBy('created_at', 'desc');

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('baseProduct', function($bpq) use ($search) {
                        $bpq->where('name', 'like', "%{$search}%")
                           ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('ingredientBaseProduct', function($ibpq) use ($search) {
                        $ibpq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('ingredientItem', function($iiq) use ($search) {
                        $iiq->where('nama_item', 'like', "%{$search}%")
                            ->orWhere('kode_item', 'like', "%{$search}%");
                    });
                });
            }

            // Base product filter
            if ($request->filled('base_product_id')) {
                $query->where('base_product_id', $request->base_product_id);
            }

            // Status filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active === 'true' || $request->is_active === '1');
            }

            $compositions = $query->paginate($request->get('per_page', 15));

            return $this->paginatedResponse($compositions, 'Base product compositions retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving base product compositions: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve compositions: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created composition
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'base_product_id' => 'required|exists:base_products,id_base_product',
            'ingredient_base_product_id' => 'nullable|exists:base_products,id_base_product|different:base_product_id',
            'ingredient_item_id' => 'nullable|exists:items,id_item',
            'quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ], [
            'ingredient_base_product_id.different' => 'Ingredient cannot be the same as the base product',
            'ingredient_item_id.exists' => 'Selected ingredient item does not exist'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Custom validation: ensure either ingredient_base_product_id or ingredient_item_id is provided
        if (empty($request->ingredient_base_product_id) && empty($request->ingredient_item_id)) {
            return $this->errorResponse('Either ingredient base product or ingredient item must be selected', 422);
        }

        try {
            DB::beginTransaction();

            // Check if composition already exists (using appropriate field)
            $existingComposition = null;
            if ($request->ingredient_base_product_id) {
                $existingComposition = BaseProductComposition::where([
                    'base_product_id' => $request->base_product_id,
                    'ingredient_base_product_id' => $request->ingredient_base_product_id
                ])->first();
            } else if ($request->ingredient_item_id) {
                $existingComposition = BaseProductComposition::where([
                    'base_product_id' => $request->base_product_id,
                    'ingredient_item_id' => $request->ingredient_item_id
                ])->first();
            }

            if ($existingComposition) {
                return $this->errorResponse('Composition already exists for this base product and ingredient combination', 422);
            }

            $composition = BaseProductComposition::create([
                'base_product_id' => $request->base_product_id,
                'ingredient_base_product_id' => $request->ingredient_base_product_id,
                'ingredient_item_id' => $request->ingredient_item_id,
                'quantity' => $request->quantity,
                'notes' => $request->notes,
                'is_active' => $request->get('is_active', true)
            ]);

            $composition->load(['baseProduct', 'ingredientBaseProduct', 'ingredientItem']);

            DB::commit();

            return $this->createdResponse($composition, 'Base product composition created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating base product composition: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to create composition: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified composition
     */
    public function show($id): JsonResponse
    {
        try {
            $composition = BaseProductComposition::with(['baseProduct', 'ingredientBaseProduct'])->find($id);

            if (!$composition) {
                return $this->notFoundResponse('Base product composition not found');
            }

            return $this->successResponse($composition, 'Base product composition retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving base product composition: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve composition: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified composition
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'base_product_id' => 'required|exists:base_products,id_base_product',
            'ingredient_base_product_id' => 'required|exists:base_products,id_base_product|different:base_product_id',
            'quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $composition = BaseProductComposition::find($id);

            if (!$composition) {
                return $this->notFoundResponse('Base product composition not found');
            }

            // Check if composition already exists (excluding current one)
            $existingComposition = BaseProductComposition::where([
                'base_product_id' => $request->base_product_id,
                'ingredient_base_product_id' => $request->ingredient_base_product_id
            ])->where('id', '!=', $id)->first();

            if ($existingComposition) {
                return $this->errorResponse('Composition already exists for this base product and ingredient combination', 422);
            }

            $composition->update([
                'base_product_id' => $request->base_product_id,
                'ingredient_base_product_id' => $request->ingredient_base_product_id,
                'quantity' => $request->quantity,
                'notes' => $request->notes,
                'is_active' => $request->get('is_active', $composition->is_active)
            ]);

            $composition->load(['baseProduct', 'ingredientBaseProduct']);

            DB::commit();

            return $this->successResponse($composition, 'Base product composition updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating base product composition: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to update composition: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified composition
     */
    public function destroy($id): JsonResponse
    {
        try {
            $composition = BaseProductComposition::find($id);

            if (!$composition) {
                return $this->notFoundResponse('Base product composition not found');
            }

            $composition->delete();

            return $this->deletedResponse('Base product composition deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting base product composition: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to delete composition: ' . $e->getMessage());
        }
    }

    /**
     * Get compositions for a specific base product
     */
    public function getCompositionsForBaseProduct($baseProductId): JsonResponse
    {
        try {
            $compositions = BaseProductComposition::with(['ingredientBaseProduct'])
                ->where('base_product_id', $baseProductId)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($compositions, 'Compositions retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving compositions for base product: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve compositions: ' . $e->getMessage());
        }
    }

    /**
     * Calculate total cost for a base product composition
     */
    public function calculateCost(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ingredient_base_product_id' => 'required|exists:base_products,id_base_product',
            'quantity' => 'required|numeric|min:0.001'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $ingredient = BaseProduct::find($request->ingredient_base_product_id);
            
            if (!$ingredient) {
                return $this->notFoundResponse('Ingredient base product not found');
            }

            $totalCost = $ingredient->cost_per_unit * $request->quantity;
            $availablePortions = $ingredient->current_stock > 0 ? floor($ingredient->current_stock / $request->quantity) : 0;

            return $this->successResponse([
                'ingredient' => $ingredient,
                'quantity' => $request->quantity,
                'cost_per_unit' => $ingredient->cost_per_unit,
                'total_cost' => $totalCost,
                'available_stock' => $ingredient->current_stock,
                'available_portions' => $availablePortions,
                'formatted_total_cost' => 'Rp ' . number_format($totalCost, 0, ',', '.')
            ], 'Cost calculated successfully');
        } catch (\Exception $e) {
            Log::error('Error calculating composition cost: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to calculate cost: ' . $e->getMessage());
        }
    }
}
