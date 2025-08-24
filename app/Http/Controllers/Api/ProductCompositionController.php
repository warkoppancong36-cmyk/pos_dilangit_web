<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductComposition;
use App\Models\Product;
use App\Models\BaseProduct;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductCompositionController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all product compositions with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProductComposition::with(['product', 'baseProduct.inventory', 'creator'])
                ->orderBy('created_at', 'desc');

            // Filter by product
            if ($request->filled('product_id')) {
                $query->where('id_product', $request->product_id);
            }

            // Filter by base product
            if ($request->filled('base_product_id')) {
                $query->where('id_base_product', $request->base_product_id);
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('product', function($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('baseProduct', function($bpq) use ($search) {
                        $bpq->where('name', 'like', "%{$search}%");
                    });
                });
            }

            $compositions = $query->paginate($request->get('per_page', 15));

            return $this->paginatedResponse($compositions, 'Product compositions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve product compositions: ' . $e->getMessage());
        }
    }

    /**
     * Store a new product composition
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_product' => 'required|exists:products,id_product',
            'id_base_product' => 'required|exists:base_products,id_base_product',
            'quantity_needed' => 'required|numeric|min:0.001',
            'unit' => 'required|string|max:20',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'is_essential' => 'boolean',
            'notes' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Check if composition already exists
        $exists = ProductComposition::where('id_product', $request->id_product)
            ->where('id_base_product', $request->id_base_product)
            ->exists();

        if ($exists) {
            return $this->errorResponse('Composition for this product and base product already exists', 422);
        }

        try {
            $baseProduct = BaseProduct::find($request->id_base_product);
            
            $composition = ProductComposition::create([
                'id_product' => $request->id_product,
                'id_base_product' => $request->id_base_product,
                'quantity_needed' => $request->quantity_needed,
                'unit' => $request->unit,
                'cost_per_unit' => $request->cost_per_unit ?? $baseProduct->cost_per_unit,
                'is_essential' => $request->get('is_essential', true),
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            $composition->load(['product', 'baseProduct.inventory']);

            return $this->createdResponse($composition, 'Product composition created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create product composition: ' . $e->getMessage());
        }
    }

    /**
     * Store multiple compositions at once
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_product' => 'required|exists:products,id_product',
            'compositions' => 'required|array|min:1',
            'compositions.*.id_base_product' => 'required|exists:base_products,id_base_product',
            'compositions.*.quantity_needed' => 'required|numeric|min:0.001',
            'compositions.*.unit' => 'required|string|max:20',
            'compositions.*.cost_per_unit' => 'nullable|numeric|min:0',
            'compositions.*.is_essential' => 'boolean',
            'compositions.*.notes' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $createdCompositions = [];
            $productId = $request->id_product;

            foreach ($request->compositions as $compositionData) {
                // Check if composition already exists
                $exists = ProductComposition::where('id_product', $productId)
                    ->where('id_base_product', $compositionData['id_base_product'])
                    ->exists();

                if ($exists) {
                    continue; // Skip if already exists
                }

                $baseProduct = BaseProduct::find($compositionData['id_base_product']);
                
                $composition = ProductComposition::create([
                    'id_product' => $productId,
                    'id_base_product' => $compositionData['id_base_product'],
                    'quantity_needed' => $compositionData['quantity_needed'],
                    'unit' => $compositionData['unit'],
                    'cost_per_unit' => $compositionData['cost_per_unit'] ?? $baseProduct->cost_per_unit,
                    'is_essential' => $compositionData['is_essential'] ?? true,
                    'notes' => $compositionData['notes'] ?? null,
                    'created_by' => Auth::id()
                ]);

                $createdCompositions[] = $composition->load(['baseProduct.inventory']);
            }

            DB::commit();

            return $this->createdResponse($createdCompositions, count($createdCompositions) . ' product compositions created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to create product compositions: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified composition
     */
    public function show($id): JsonResponse
    {
        try {
            $composition = ProductComposition::with([
                'product',
                'baseProduct.inventory',
                'creator'
            ])->find($id);

            if (!$composition) {
                return $this->notFoundResponse('Product composition not found');
            }

            return $this->successResponse($composition, 'Product composition retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve product composition: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified composition
     */
    public function update(Request $request, $id): JsonResponse
    {
        $composition = ProductComposition::find($id);

        if (!$composition) {
            return $this->notFoundResponse('Product composition not found');
        }

        $validator = Validator::make($request->all(), [
            'quantity_needed' => 'required|numeric|min:0.001',
            'unit' => 'required|string|max:20',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'is_essential' => 'boolean',
            'notes' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $composition->update($request->only([
                'quantity_needed',
                'unit', 
                'cost_per_unit',
                'is_essential',
                'notes'
            ]));

            $composition->load(['product', 'baseProduct.inventory']);

            return $this->successResponse($composition, 'Product composition updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update product composition: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified composition
     */
    public function destroy($id): JsonResponse
    {
        try {
            $composition = ProductComposition::find($id);

            if (!$composition) {
                return $this->notFoundResponse('Product composition not found');
            }

            $composition->delete();

            return $this->deletedResponse('Product composition deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete product composition: ' . $e->getMessage());
        }
    }

    /**
     * Get compositions for specific product
     */
    public function getProductCompositions($productId): JsonResponse
    {
        try {
            $product = Product::find($productId);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            $compositions = ProductComposition::with(['baseProduct.inventory'])
                ->where('id_product', $productId)
                ->orderBy('is_essential', 'desc')
                ->orderBy('created_at', 'asc')
                ->get();

            // Calculate total cost
            $totalCost = $compositions->sum('total_cost');

            return $this->successResponse([
                'product' => $product,
                'compositions' => $compositions,
                'total_cost' => $totalCost,
                'formatted_total_cost' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
                'composition_count' => $compositions->count()
            ], 'Product compositions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve product compositions: ' . $e->getMessage());
        }
    }

    /**
     * Check if product can be produced with current stock
     */
    public function checkProductionAvailability($productId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $product = Product::find($productId);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            $compositions = ProductComposition::with(['baseProduct.inventory'])
                ->where('id_product', $productId)
                ->get();

            if ($compositions->isEmpty()) {
                return $this->successResponse([
                    'can_produce' => true,
                    'quantity_requested' => $request->quantity,
                    'message' => 'Product has no composition requirements'
                ], 'Production availability checked');
            }

            $canProduce = true;
            $availabilityDetails = [];
            $limitingFactor = null;
            $maxProducible = PHP_INT_MAX;

            foreach ($compositions as $composition) {
                $availability = $composition->checkAvailability($request->quantity);
                $availabilityDetails[] = [
                    'base_product' => $composition->baseProduct->name,
                    'required_quantity' => $availability['required_quantity'],
                    'current_stock' => $availability['current_stock'],
                    'available' => $availability['available'],
                    'shortage' => $availability['shortage'],
                    'unit' => $composition->unit,
                    'is_essential' => $composition->is_essential
                ];

                if (!$availability['available'] && $composition->is_essential) {
                    $canProduce = false;
                }

                // Calculate max producible quantity
                if ($composition->quantity_needed > 0) {
                    $canProduceFromThis = floor($availability['current_stock'] / $composition->quantity_needed);
                    if ($canProduceFromThis < $maxProducible) {
                        $maxProducible = $canProduceFromThis;
                        $limitingFactor = $composition->baseProduct->name;
                    }
                }
            }

            return $this->successResponse([
                'can_produce' => $canProduce,
                'quantity_requested' => $request->quantity,
                'max_producible' => $maxProducible,
                'limiting_factor' => $limitingFactor,
                'availability_details' => $availabilityDetails,
                'message' => $canProduce 
                    ? 'Product can be produced with current stock' 
                    : 'Insufficient stock for production'
            ], 'Production availability checked');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check production availability: ' . $e->getMessage());
        }
    }

    /**
     * Update multiple compositions at once
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'compositions' => 'required|array|min:1',
            'compositions.*.id' => 'required|exists:product_compositions,id_composition',
            'compositions.*.quantity_needed' => 'required|numeric|min:0.001',
            'compositions.*.unit' => 'required|string|max:20',
            'compositions.*.cost_per_unit' => 'nullable|numeric|min:0',
            'compositions.*.is_essential' => 'boolean',
            'compositions.*.notes' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $updatedCompositions = [];

            foreach ($request->compositions as $compositionData) {
                $composition = ProductComposition::find($compositionData['id']);
                
                if ($composition) {
                    $composition->update([
                        'quantity_needed' => $compositionData['quantity_needed'],
                        'unit' => $compositionData['unit'],
                        'cost_per_unit' => $compositionData['cost_per_unit'],
                        'is_essential' => $compositionData['is_essential'] ?? true,
                        'notes' => $compositionData['notes'] ?? null
                    ]);

                    $updatedCompositions[] = $composition->load(['baseProduct.inventory']);
                }
            }

            DB::commit();

            return $this->successResponse($updatedCompositions, count($updatedCompositions) . ' product compositions updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to update product compositions: ' . $e->getMessage());
        }
    }

    /**
     * Delete multiple compositions at once
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'composition_ids' => 'required|array|min:1',
            'composition_ids.*' => 'required|exists:product_compositions,id_composition'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $deletedCount = ProductComposition::whereIn('id_composition', $request->composition_ids)->delete();

            return $this->deletedResponse($deletedCount . ' product compositions deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete product compositions: ' . $e->getMessage());
        }
    }
}
