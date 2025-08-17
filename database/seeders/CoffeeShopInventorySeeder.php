<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class CoffeeShopInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all existing products
        $products = Product::all();

        $inventoryData = [
            // Coffee products
            'MIN-001-250803' => ['stock' => 85, 'cost' => 15000],   // Kopi Americano
            'MIN-002-250803' => ['stock' => 120, 'cost' => 18000],  // Cappuccino  
            'MIN-005-250803' => ['stock' => 45, 'cost' => 35000],   // Kopi Luwak Premium
            
            // Non-coffee drinks
            'MIN-003-250803' => ['stock' => 95, 'cost' => 8000],    // Ice Tea Lemon
            'MIN-004-250803' => ['stock' => 78, 'cost' => 22000],   // Chocolate Frappe
            
            // Food
            'MKN-001-250803' => ['stock' => 35, 'cost' => 25000],   // Nasi Gudeg Jogja
            'MKN-002-250803' => ['stock' => 42, 'cost' => 18000],   // Sate Ayam Madura
            'MKN-003-250803' => ['stock' => 28, 'cost' => 15000],   // Gado-Gado Jakarta
            
            // Snacks
            'SNK-001-250803' => ['stock' => 150, 'cost' => 5000],   // Keripik Singkong Balado
            'SNK-002-250803' => ['stock' => 89, 'cost' => 6000],    // Pisang Goreng Kipas
            'SNK-003-250803' => ['stock' => 200, 'cost' => 3000],   // Donat Mini Glaze
        ];

        foreach ($products as $product) {
            $sku = $product->sku;
            
            // Check if we have predefined data for this SKU
            if (isset($inventoryData[$sku])) {
                $data = $inventoryData[$sku];
                $stock = $data['stock'];
                $cost = $data['cost'];
            } else {
                // For other products, assign random reasonable values
                $stock = rand(10, 100);
                $cost = $product->price * 0.6; // Cost is 60% of selling price
            }

            // Determine reorder level based on stock
            $reorderLevel = $stock < 20 ? 5 : ($stock < 50 ? 10 : 15);
            
            // Create or update inventory
            $inventory = Inventory::where('id_product', $product->id_product)->first();
            
            if ($inventory) {
                // Update existing inventory
                $inventory->update([
                    'current_stock' => $stock,
                    'reserved_stock' => rand(0, 5),
                    'reorder_level' => $reorderLevel,
                    'max_stock_level' => $stock * 2,
                    'average_cost' => $cost,
                    'last_restocked' => now()->subDays(rand(1, 30)),
                    'updated_by' => 1,
                ]);
            } else {
                // Create new inventory
                Inventory::create([
                    'id_product' => $product->id_product,
                    'id_variant' => null,
                    'current_stock' => $stock,
                    'reserved_stock' => rand(0, 5),
                    'reorder_level' => $reorderLevel,
                    'max_stock_level' => $stock * 2,
                    'average_cost' => $cost,
                    'last_restocked' => now()->subDays(rand(1, 30)),
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
            }
        }

        $this->command->info('Coffee Shop Inventory data created successfully!');
        $this->command->info('Created inventory records for ' . $products->count() . ' products.');
        
        // Show some stats
        $stats = [
            'total_items' => Inventory::count(),
            'low_stock' => Inventory::whereRaw('current_stock <= reorder_level')->count(),
            'out_of_stock' => Inventory::where('current_stock', 0)->count(),
            'total_value' => Inventory::sum(DB::raw('current_stock * average_cost')),
        ];
        
        $this->command->info('Stats:');
        $this->command->info('- Total items: ' . $stats['total_items']);
        $this->command->info('- Low stock items: ' . $stats['low_stock']);
        $this->command->info('- Out of stock: ' . $stats['out_of_stock']);
        $this->command->info('- Total inventory value: Rp ' . number_format($stats['total_value']));
    }
}
