<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
// use App\Models\Variant; // DISABLED - Variant system removed
use Illuminate\Support\Facades\DB;

class InventorySyncService
{
    /**
     * Sinkronisasi stok dari inventory ke product/variant
     * NOTE: Since stock columns have been removed from products/variants tables,
     * this method now only handles inventory-specific operations
     */
    public static function syncStockToProductAndVariant(Inventory $inventory)
    {
        // Since stock is now managed entirely in inventory table,
        // no sync to products/variants table is needed.
        // This method is kept for compatibility but doesn't need to do anything.
        
        // Optional: Log inventory changes for audit trail
        \Log::info('Inventory updated', [
            'id_inventory' => $inventory->id_inventory,
            'id_product' => $inventory->id_product,
            'id_variant' => $inventory->id_variant,
            'current_stock' => $inventory->current_stock,
            'updated_at' => $inventory->updated_at
        ]);
    }
    
    /**
     * Sinkronisasi bulk untuk semua inventory
     * NOTE: This method is kept for compatibility but no longer syncs to products/variants
     */
    public static function syncAllInventory()
    {
        $inventories = Inventory::with(['product'])->get();
        
        // Since stock is now managed entirely in inventory table,
        // no sync to products/variants table is needed
        
        return [
            'total_items' => $inventories->count(),
            'message' => 'Inventory system is already up-to-date (stock managed in inventory table)'
        ];
    }
}
