<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Manual mapping based on business logic
        // Since the automatic mapping failed, we'll do manual mapping
        
        echo "Manual mapping of purchase items...\n";
        
        // For demonstration, I'll create some example mappings
        // You need to adjust these based on your actual data
        
        // Example: Map products to corresponding raw material items
        $mappings = [
            // If Gado-Gado Jakarta (product) uses vegetables as raw materials
            // Map to vegetable items if they exist
            
            // For now, let's assume we need to create some basic items
            // or map to existing ones based on your business logic
        ];
        
        // Create some basic raw material items if they don't exist
        $defaultItems = [
            [
                'item_code' => 'VEG001',
                'name' => 'Vegetables Mix', 
                'description' => 'Mixed vegetables for cooking',
                'unit' => 'kg', 
                'current_stock' => 0, 
                'minimum_stock' => 5, 
                'cost_per_unit' => 15000,
                'active' => true
            ],
            [
                'item_code' => 'COF001',
                'name' => 'Coffee Beans', 
                'description' => 'Premium coffee beans',
                'unit' => 'kg', 
                'current_stock' => 0, 
                'minimum_stock' => 2, 
                'cost_per_unit' => 80000,
                'active' => true
            ],
            [
                'item_code' => 'MLK001',
                'name' => 'Milk', 
                'description' => 'Fresh milk',
                'unit' => 'liter', 
                'current_stock' => 0, 
                'minimum_stock' => 10, 
                'cost_per_unit' => 12000,
                'active' => true
            ],
        ];
        
        foreach ($defaultItems as $itemData) {
            $existingItem = DB::table('items')->where('name', $itemData['name'])->first();
            if (!$existingItem) {
                $itemId = DB::table('items')->insertGetId(array_merge($itemData, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
                echo "Created item: {$itemData['name']} (ID: {$itemId})\n";
            }
        }
        
        // Now map purchase items to appropriate items
        $purchaseItems = DB::table('purchase_items')
            ->whereNotNull('id_product')
            ->whereNull('item_id')
            ->get();
            
        foreach ($purchaseItems as $purchaseItem) {
            $product = DB::table('products')->where('id_product', $purchaseItem->id_product)->first();
            
            if ($product) {
                $itemId = null;
                
                // Business logic mapping
                if (stripos($product->name, 'gado') !== false) {
                    // Gado-gado uses vegetables
                    $item = DB::table('items')->where('name', 'Vegetables Mix')->first();
                    $itemId = $item ? $item->id_item : null;
                } elseif (stripos($product->name, 'cappuccino') !== false || stripos($product->name, 'coffee') !== false) {
                    // Coffee products use coffee beans
                    $item = DB::table('items')->where('name', 'Coffee Beans')->first();
                    $itemId = $item ? $item->id_item : null;
                }
                
                if ($itemId) {
                    DB::table('purchase_items')
                        ->where('id_purchase_item', $purchaseItem->id_purchase_item)
                        ->update(['item_id' => $itemId]);
                        
                    echo "Mapped purchase item {$purchaseItem->id_purchase_item} (Product: {$product->name}) to item ID: {$itemId}\n";
                } else {
                    echo "Could not map purchase item {$purchaseItem->id_purchase_item} (Product: {$product->name})\n";
                }
            }
        }
        
        echo "Manual mapping completed.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the mappings
        DB::table('purchase_items')
            ->whereNotNull('item_id')
            ->update(['item_id' => null]);
    }
};
