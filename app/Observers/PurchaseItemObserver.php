<?php

namespace App\Observers;

use App\Models\PurchaseItem;
use App\Services\HPPCalculationService;
use Illuminate\Support\Facades\Log;

class PurchaseItemObserver
{
    /**
     * Handle the PurchaseItem "created" event.
     */
    public function created(PurchaseItem $purchaseItem): void
    {
        $this->updateRelatedProductsHPP($purchaseItem, 'created');
    }

    /**
     * Handle the PurchaseItem "updated" event.
     */
    public function updated(PurchaseItem $purchaseItem): void
    {
        // Only update if unit_cost changed
        if ($purchaseItem->isDirty('unit_cost')) {
            $this->updateRelatedProductsHPP($purchaseItem, 'updated');
        }
    }

    /**
     * Handle the PurchaseItem "deleted" event.
     */
    public function deleted(PurchaseItem $purchaseItem): void
    {
        $this->updateRelatedProductsHPP($purchaseItem, 'deleted');
    }

    /**
     * Update HPP for products that use this item
     */
    private function updateRelatedProductsHPP(PurchaseItem $purchaseItem, string $event): void
    {
        try {
            if ($purchaseItem->item_id) {
                Log::info("Purchase item {$event}, updating related products HPP", [
                    'purchase_item_id' => $purchaseItem->id,
                    'item_id' => $purchaseItem->item_id,
                    'unit_cost' => $purchaseItem->unit_cost,
                    'event' => $event,
                ]);

                // Update HPP for all products using this item with latest purchase method
                $affectedProducts = HPPCalculationService::updateProductsHPPForItem(
                    $purchaseItem->item_id, 
                    'latest'
                );

                Log::info("HPP updated for products due to purchase item {$event}", [
                    'purchase_item_id' => $purchaseItem->id,
                    'item_id' => $purchaseItem->item_id,
                    'affected_products_count' => count($affectedProducts),
                    'affected_products' => $affectedProducts,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error updating HPP after purchase item {$event}", [
                'purchase_item_id' => $purchaseItem->id,
                'item_id' => $purchaseItem->item_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
