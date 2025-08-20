<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use App\Models\Item;
use App\Models\VariantItem;
use App\Models\ItemPurchase;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VariantHPPController extends Controller
{
    /**
     * Get HPP breakdown for a variant
     */
    public function getHPPBreakdown(Request $request, $variantId): JsonResponse
    {
        try {
            $method = $request->get('method', 'latest');
            
            // Validate method
            if (!in_array($method, ['current', 'latest', 'average'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid method. Use: current, latest, or average'
                ], 400);
            }

            // Get variant with its items
            $variant = Variant::find($variantId);
            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found'
                ], 404);
            }

            // Get variant composition items
            $variantItems = VariantItem::where('id_variant', $variantId)
                ->with(['item.inventory', 'item.purchaseItems'])
                ->get();

            if ($variantItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No composition items found for this variant'
                ], 404);
            }

            $breakdown = [];
            $totalHPP = 0;

            foreach ($variantItems as $variantItem) {
                $item = $variantItem->item;
                $costPerUnit = $this->calculateCostPerUnit($item, $method);
                $totalCost = $costPerUnit * $variantItem->quantity_needed;
                
                $breakdown[] = [
                    'item_name' => $item->name,
                    'item_code' => $item->item_code ?? '',
                    'quantity_needed' => (float) $variantItem->quantity_needed,
                    'unit' => $variantItem->unit,
                    'cost_per_unit' => (float) $costPerUnit,
                    'total_cost' => (float) $totalCost,
                    'is_critical' => (bool) $variantItem->is_critical,
                    'notes' => $variantItem->notes ?? '',
                ];
                
                $totalHPP += $totalCost;
            }

            $result = [
                'items' => $breakdown,
                'total_hpp' => (float) $totalHPP,
                'method' => $method,
                'calculated_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating HPP breakdown: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update variant HPP
     */
    public function updateHPP(Request $request, $variantId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'method' => 'required|in:current,latest,average',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $method = $request->get('method');

            // Get variant
            $variant = Variant::find($variantId);
            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found'
                ], 404);
            }

            // Calculate new HPP
            $hppBreakdown = $this->calculateVariantHPP($variantId, $method);
            
            if (!$hppBreakdown) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not calculate HPP for this variant'
                ], 400);
            }

            // Update variant cost
            $oldCost = $variant->cost_per_unit ?? 0;
            $newCost = $hppBreakdown['total_hpp'];
            
            $variant->update([
                'cost_per_unit' => $newCost,
                'hpp_method' => $method,
                'hpp_calculated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'variant_id' => $variantId,
                    'variant_name' => $variant->name,
                    'old_cost' => (float) $oldCost,
                    'new_cost' => (float) $newCost,
                    'difference' => (float) ($newCost - $oldCost),
                    'method' => $method,
                    'updated_at' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating HPP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate price suggestion based on HPP
     */
    public function calculatePriceSuggestion(Request $request, $variantId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'markup_percentage' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $markupPercentage = $request->get('markup_percentage');

            // Get variant
            $variant = Variant::find($variantId);
            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found'
                ], 404);
            }

            // Calculate HPP for all methods
            $suggestions = [];
            $methods = ['current', 'latest', 'average'];

            foreach ($methods as $method) {
                $hppBreakdown = $this->calculateVariantHPP($variantId, $method);
                
                if ($hppBreakdown) {
                    $hpp = $hppBreakdown['total_hpp'];
                    $suggestedPrice = $hpp * (1 + ($markupPercentage / 100));
                    
                    $suggestions[$method] = [
                        'hpp' => (float) $hpp,
                        'markup_percentage' => (float) $markupPercentage,
                        'suggested_price' => (float) $suggestedPrice,
                        'profit_margin' => $hpp > 0 ? (float) (($suggestedPrice - $hpp) / $suggestedPrice * 100) : 0,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'variant_id' => $variantId,
                    'variant_name' => $variant->name,
                    'current_price' => (float) ($variant->price ?? 0),
                    'markup_percentage' => (float) $markupPercentage,
                    'suggestions' => $suggestions,
                    'calculated_at' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating price suggestion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update variant price from HPP
     */
    public function updatePriceFromHPP(Request $request, $variantId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'method' => 'required|in:current,latest,average',
                'target_price' => 'required|numeric|min:0',
                'update_stock' => 'boolean',
                'use_target_price' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $method = $request->get('method');
            $targetPrice = $request->get('target_price');
            $updateStock = $request->get('update_stock', false);
            $useTargetPrice = $request->get('use_target_price', true);

            // Get variant
            $variant = Variant::find($variantId);
            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found'
                ], 404);
            }

            // Calculate HPP
            $hppBreakdown = $this->calculateVariantHPP($variantId, $method);
            
            if (!$hppBreakdown) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not calculate HPP for this variant'
                ], 400);
            }

            $hpp = $hppBreakdown['total_hpp'];
            $newPrice = $useTargetPrice ? $targetPrice : $hpp * 1.2; // Default 20% markup
            $markupPercentage = $hpp > 0 ? (($newPrice - $hpp) / $hpp) * 100 : 0;

            // Update variant
            $oldPrice = $variant->price ?? 0;
            
            $variant->update([
                'price' => $newPrice,
                'cost_per_unit' => $hpp,
                'hpp_method' => $method,
                'hpp_calculated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'variant_id' => $variantId,
                    'variant_name' => $variant->name,
                    'old_price' => (float) $oldPrice,
                    'new_price' => (float) $newPrice,
                    'hpp' => (float) $hpp,
                    'markup_percentage' => (float) $markupPercentage,
                    'profit_margin' => $newPrice > 0 ? (float) (($newPrice - $hpp) / $newPrice * 100) : 0,
                    'method' => $method,
                    'updated_at' => now()->toISOString(),
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
     * Calculate variant HPP using specified method
     */
    private function calculateVariantHPP($variantId, $method)
    {
        try {
            // Get variant composition items
            $variantItems = VariantItem::where('id_variant', $variantId)
                ->with(['item.inventory', 'item.purchaseItems'])
                ->get();

            if ($variantItems->isEmpty()) {
                return null;
            }

            $breakdown = [];
            $totalHPP = 0;

            foreach ($variantItems as $variantItem) {
                $item = $variantItem->item;
                $costPerUnit = $this->calculateCostPerUnit($item, $method);
                $totalCost = $costPerUnit * $variantItem->quantity_needed;
                
                $breakdown[] = [
                    'item_name' => $item->name,
                    'item_code' => $item->item_code ?? '',
                    'quantity_needed' => (float) $variantItem->quantity_needed,
                    'unit' => $variantItem->unit,
                    'cost_per_unit' => (float) $costPerUnit,
                    'total_cost' => (float) $totalCost,
                    'is_critical' => (bool) $variantItem->is_critical,
                ];
                
                $totalHPP += $totalCost;
            }

            return [
                'items' => $breakdown,
                'total_hpp' => $totalHPP,
                'method' => $method,
                'calculated_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate cost per unit for an item using specified method
     */
    private function calculateCostPerUnit($item, $method)
    {
        switch ($method) {
            case 'current':
                // Use current cost from item or inventory
                return $item->cost_per_unit ?? $item->inventory?->cost_per_unit ?? 0;

            case 'latest':
                // Use latest purchase price
                $latestPurchase = \App\Models\PurchaseItem::where('item_id', $item->id_item)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                return $latestPurchase ? $latestPurchase->unit_cost : ($item->cost_per_unit ?? 0);

            case 'average':
                // Use average purchase price from last 3 months
                $averageCost = \App\Models\PurchaseItem::where('item_id', $item->id_item)
                    ->where('created_at', '>=', now()->subMonths(3))
                    ->avg('unit_cost');
                
                return $averageCost ? $averageCost : ($item->cost_per_unit ?? 0);

            default:
                return $item->cost_per_unit ?? 0;
        }
    }
}
