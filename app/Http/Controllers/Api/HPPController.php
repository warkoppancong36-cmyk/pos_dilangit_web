<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Item;
use App\Services\HPPCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class HPPController extends Controller
{
    /**
     * Get HPP breakdown for a specific product
     */
    public function getProductHPPBreakdown(Request $request, $productId): JsonResponse
    {
        try {
            // Use the correct primary key (id_product) for finding product
            $product = Product::where('id_product', $productId)->first();
            
            if (!$product) {
                \Log::warning("Product not found with id_product: {$productId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            $method = $request->get('method', 'current');
            
            if (!in_array($method, ['current', 'latest', 'average'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid calculation method. Use: current, latest, or average'
                ], 422);
            }
            
            $breakdown = $product->getHPPBreakdown($method);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $product->id_product,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'current_cost' => $product->cost,
                        'current_price' => $product->price,
                    ],
                    'hpp_breakdown' => $breakdown,
                ],
                'message' => 'HPP breakdown retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving HPP breakdown: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update HPP for a specific product
     */
    public function updateProductHPP(Request $request, $productId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'method' => 'required|in:current,latest,average',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $result = HPPCalculationService::updateProductHPP($productId, $request->method);
            
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found or update failed'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Product HPP updated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating HPP: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk update HPP for all products
     */
    public function bulkUpdateHPP(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'method' => 'required|in:current,latest,average',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $results = HPPCalculationService::updateAllProductsHPP($request->method);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'updated_products' => count($results),
                    'details' => $results,
                ],
                'message' => 'Bulk HPP update completed successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error in bulk HPP update: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Compare HPP calculation methods for a product
     */
    public function compareHPPMethods($productId): JsonResponse
    {
        try {
            $comparison = HPPCalculationService::compareHPPMethods($productId);
            
            if (!$comparison) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $comparison,
                'message' => 'HPP methods comparison retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error comparing HPP methods: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate suggested selling price based on HPP and markup
     */
    public function calculateSuggestedPrice(Request $request, $productId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'markup_percentage' => 'required|numeric|min:0|max:1000',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $suggestion = HPPCalculationService::calculateSuggestedPrice(
                $productId, 
                $request->markup_percentage
            );
            
            if (!$suggestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $suggestion,
                'message' => 'Suggested price calculated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating suggested price: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update product price based on HPP calculation
     */
    public function updatePriceFromHPP(Request $request, $productId): JsonResponse
    {
        try {
            \Log::info("updatePriceFromHPP called with productId: {$productId}");
            \Log::info("Request payload: " . json_encode($request->all()));
            
            $validator = Validator::make($request->all(), [
                'method' => 'required|in:current,latest,average',
                'markup_percentage' => 'required|numeric|min:0|max:1000',
                'update_cost' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Use the correct primary key (id_product) for finding product
            $product = Product::where('id_product', $productId)->first();
            
            if (!$product) {
                \Log::warning("Product not found with id_product: {$productId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            $oldPrice = $product->price;
            $oldCost = $product->cost;
            
            // Calculate HPP based on method
            $hpp = match($request->method) {
                'latest' => $product->calculateHPPFromLatestPurchases(),
                'average' => $product->calculateHPPFromAveragePurchases(),
                default => $product->calculateHPP(),
            };
            
            // Calculate new price with markup
            $newPrice = round($hpp * (1 + $request->markup_percentage / 100), 0);
            
            // Calculate actual markup percentage from saved data
            $actualMarkupPercentage = $hpp > 0 
                ? (($newPrice - $hpp) / $hpp) * 100 
                : 0;
            
            // Update product with price, cost, and markup percentage
            $updateData = [
                'price' => $newPrice,
                'markup_percentage' => round($actualMarkupPercentage, 2)
            ];
            
            if ($request->get('update_cost', true)) {
                $updateData['cost'] = $hpp;
            }
            
            $product->update($updateData);
            
            // Reload product to get fresh data
            $product->refresh();
            
            return response()->json([
                'success' => true,
                'message' => 'Product price updated successfully based on HPP',
                'data' => [
                    'product_id' => $product->id_product,
                    'product_name' => $product->name,
                    'method' => $request->method,
                    'markup_percentage' => $product->markup_percentage,
                    'old_price' => $oldPrice,
                    'new_price' => $product->price,
                    'old_cost' => $oldCost,
                    'new_cost' => $product->cost,
                    'hpp' => $hpp,
                    'price_difference' => $product->price - $oldPrice,
                    'updated_at' => $product->updated_at->toDateTimeString(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating price from HPP: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get HPP dashboard summary
     */
    public function getHPPDashboard(): JsonResponse
    {
        try {
            $products = Product::with(['productItems.item'])->get();
            
            $summary = [
                'total_products' => $products->count(),
                'products_with_items' => $products->filter(function($product) {
                    return $product->productItems->count() > 0;
                })->count(),
                'products_without_items' => $products->filter(function($product) {
                    return $product->productItems->count() === 0;
                })->count(),
                'hpp_statistics' => [],
            ];
            
            // Calculate HPP statistics
            $hppValues = [];
            $priceDifferences = [];
            
            foreach ($products as $product) {
                if ($product->productItems->count() > 0) {
                    $hpp = $product->calculateHPP();
                    $hppValues[] = $hpp;
                    $priceDifferences[] = $product->price - $hpp;
                }
            }
            
            if (count($hppValues) > 0) {
                $summary['hpp_statistics'] = [
                    'average_hpp' => round(array_sum($hppValues) / count($hppValues), 2),
                    'min_hpp' => min($hppValues),
                    'max_hpp' => max($hppValues),
                    'average_margin' => round(array_sum($priceDifferences) / count($priceDifferences), 2),
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'HPP dashboard summary retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving HPP dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}
