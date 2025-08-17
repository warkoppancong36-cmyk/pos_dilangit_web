<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Map products to items based on business logic
        // This assumes that products should correspond to items with similar names or purposes
        
        echo "Mapping purchase items from products to items...\n";
        
        // Get all purchase items that have id_product but no item_id
        $purchaseItems = DB::table('purchase_items')
            ->whereNotNull('id_product')
            ->whereNull('item_id')
            ->get();
            
        echo "Found " . $purchaseItems->count() . " purchase items to map.\n";
        
        foreach ($purchaseItems as $purchaseItem) {
            // Get the product info
            $product = DB::table('products')->where('id_product', $purchaseItem->id_product)->first();
            
            if ($product) {
                echo "Processing purchase item {$purchaseItem->id_purchase_item} with product: {$product->name}\n";
                
                // Try to find matching item by name (you might need to adjust this logic)
                $item = DB::table('items')
                    ->where('name', 'LIKE', '%' . $product->name . '%')
                    ->orWhere('name', 'LIKE', '%' . explode(' ', $product->name)[0] . '%')
                    ->first();
                
                if ($item) {
                    // Update the purchase item with the mapped item_id
                    DB::table('purchase_items')
                        ->where('id_purchase_item', $purchaseItem->id_purchase_item)
                        ->update(['item_id' => $item->id_item]);
                        
                    echo "Mapped to item: {$item->name} (ID: {$item->id_item})\n";
                } else {
                    // If no matching item found, create a basic mapping or leave null
                    echo "No matching item found for product: {$product->name}. Leaving item_id as null.\n";
                    echo "Manual mapping required for purchase item {$purchaseItem->id_purchase_item}.\n";
                }
            }
        }
        
        echo "Mapping completed. Please review the results and manually fix any unmapped items.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the mapping by clearing item_id and restoring id_product references
        echo "Reversing item mapping...\n";
        
        DB::table('purchase_items')
            ->whereNotNull('item_id')
            ->update(['item_id' => null]);
            
        echo "Mapping reversed.\n";
    }
};
