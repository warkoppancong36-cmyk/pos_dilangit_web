<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Item;
use App\Models\ProductItem;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\Log;

class HPPCalculationService
{
    /**
     * Update HPP for products when item purchase price changes
     */
    public static function updateProductsHPPForItem(int $itemId, string $method = 'latest'): array
    {
        $affectedProducts = [];
        
        try {
            // Find all products that use this item
            $productItems = ProductItem::with('product')
                ->where('item_id', $itemId)
                ->get();
            
            foreach ($productItems as $productItem) {
                $product = $productItem->product;
                
                if ($product) {
                    $oldCost = $product->cost;
                    $product->updateCostFromHPP($method);
                    $newCost = $product->fresh()->cost;
                    
                    $affectedProducts[] = [
                        'product_id' => $product->id_product,
                        'product_name' => $product->name,
                        'old_cost' => $oldCost,
                        'new_cost' => $newCost,
                        'difference' => $newCost - $oldCost,
                    ];
                }
            }
            
            Log::info("HPP updated for " . count($affectedProducts) . " products due to item {$itemId} price change", [
                'item_id' => $itemId,
                'method' => $method,
                'affected_products' => $affectedProducts,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error updating HPP for item {$itemId}: " . $e->getMessage(), [
                'item_id' => $itemId,
                'method' => $method,
                'exception' => $e,
            ]);
        }
        
        return $affectedProducts;
    }
    
    /**
     * Update HPP for a specific product
     */
    public static function updateProductHPP(int $productId, string $method = 'latest'): ?array
    {
        try {
            $product = Product::find($productId);
            
            if (!$product) {
                Log::warning("Product not found for HPP update: {$productId}");
                return null;
            }
            
            $oldCost = $product->cost;
            $product->updateCostFromHPP($method);
            $newCost = $product->fresh()->cost;
            
            $result = [
                'product_id' => $product->id_product,
                'product_name' => $product->name,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'difference' => $newCost - $oldCost,
                'method' => $method,
                'updated_at' => now()->toDateTimeString(),
            ];
            
            Log::info("HPP updated for product {$productId}", $result);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("Error updating HPP for product {$productId}: " . $e->getMessage(), [
                'product_id' => $productId,
                'method' => $method,
                'exception' => $e,
            ]);
            
            return null;
        }
    }
    
    /**
     * Bulk update HPP for all products
     */
    public static function updateAllProductsHPP(string $method = 'latest'): array
    {
        $results = [];
        
        try {
            $products = Product::with('productItems.item')->get();
            
            foreach ($products as $product) {
                $result = self::updateProductHPP($product->id_product, $method);
                if ($result) {
                    $results[] = $result;
                }
            }
            
            Log::info("Bulk HPP update completed for " . count($results) . " products", [
                'method' => $method,
                'total_products' => $products->count(),
                'updated_products' => count($results),
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in bulk HPP update: " . $e->getMessage(), [
                'method' => $method,
                'exception' => $e,
            ]);
        }
        
        return $results;
    }
    
    /**
     * Get HPP comparison between different calculation methods
     */
    public static function compareHPPMethods(int $productId): ?array
    {
        try {
            $product = Product::find($productId);
            
            if (!$product) {
                return null;
            }
            
            return [
                'product_id' => $product->id_product,
                'product_name' => $product->name,
                'current_cost' => $product->cost,
                'current_price' => $product->price,
                'hpp_methods' => [
                    'current' => $product->calculateHPP(),
                    'latest_purchase' => $product->calculateHPPFromLatestPurchases(),
                    'average_purchase' => $product->calculateHPPFromAveragePurchases(),
                ],
                'breakdown' => [
                    'current' => $product->getHPPBreakdown('current'),
                    'latest_purchase' => $product->getHPPBreakdown('latest'),
                    'average_purchase' => $product->getHPPBreakdown('average'),
                ],
                'calculated_at' => now()->toDateTimeString(),
            ];
            
        } catch (\Exception $e) {
            Log::error("Error comparing HPP methods for product {$productId}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate suggested selling price based on HPP and markup percentage
     */
    public static function calculateSuggestedPrice(int $productId, float $markupPercentage = 30.0): ?array
    {
        try {
            \Log::info("HPP Service - Looking for product with id_product: {$productId}");
            
            // Debug: Check all products first
            $allProducts = Product::select('id_product', 'name')->take(5)->get();
            \Log::info("HPP Service - First 5 products: " . $allProducts->toJson());
            
            // Use the correct primary key (id_product) for finding product
            $product = Product::where('id_product', $productId)->first();
            
            \Log::info("HPP Service - Product found: " . ($product ? "YES ({$product->name})" : "NO"));
            
            if (!$product) {
                \Log::warning("Product not found with id_product: {$productId}");
                return null;
            }
            
            $hppMethods = [
                'current' => $product->calculateHPP(),
                'latest' => $product->calculateHPPFromLatestPurchases(),
                'average' => $product->calculateHPPFromAveragePurchases(),
            ];
            
            $suggestedPrices = [];
            foreach ($hppMethods as $method => $hpp) {
                $suggestedPrice = $hpp * (1 + $markupPercentage / 100);
                $suggestedPrices[$method] = [
                    'hpp' => $hpp,
                    'markup_percentage' => $markupPercentage,
                    'suggested_price' => round($suggestedPrice, 0), // Round to nearest rupiah
                    'profit_margin' => $suggestedPrice - $hpp,
                ];
            }
            
            return [
                'product_id' => $product->id_product,
                'product_name' => $product->name,
                'current_price' => $product->price,
                'markup_percentage' => $markupPercentage,
                'suggestions' => $suggestedPrices,
                'calculated_at' => now()->toDateTimeString(),
            ];
            
        } catch (\Exception $e) {
            Log::error("Error calculating suggested price for product {$productId}: " . $e->getMessage());
            return null;
        }
    }
}
