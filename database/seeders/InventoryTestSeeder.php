<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\User;

class InventoryTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create admin user
        $admin = User::where('email', 'admin@admin.com')->first();
        if (!$admin) {
            $admin = User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'role' => 'admin',
                'password' => bcrypt('password')
            ]);
        }

        // Get or create category
        $category = Category::where('name', 'Electronics')->first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Electronics',
                'description' => 'Electronic products for testing',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        // Create sample products with inventory
        $products = [
            [
                'name' => 'Laptop Dell Inspiron',
                'sku' => 'DELL-INSP-001',
                'description' => 'Dell Inspiron 15 3000 Series',
                'price' => 7500000,
                'stock' => 25,
                'cost' => 6500000
            ],
            [
                'name' => 'Mouse Wireless Logitech',
                'sku' => 'LOGI-M705',
                'description' => 'Logitech M705 Wireless Mouse',
                'price' => 450000,
                'stock' => 50,
                'cost' => 350000
            ],
            [
                'name' => 'Keyboard Mechanical',
                'sku' => 'MECH-KB-001',
                'description' => 'Mechanical Gaming Keyboard',
                'price' => 850000,
                'stock' => 15,
                'cost' => 650000
            ],
            [
                'name' => 'Monitor LED 24 inch',
                'sku' => 'MON-LED-24',
                'description' => 'LED Monitor 24 inch Full HD',
                'price' => 2100000,
                'stock' => 8,
                'cost' => 1800000
            ],
            [
                'name' => 'Webcam HD',
                'sku' => 'CAM-HD-001',
                'description' => 'HD Webcam for video calls',
                'price' => 320000,
                'stock' => 3,
                'cost' => 250000
            ]
        ];

        foreach ($products as $productData) {
            // Create product if not exists
            $product = Product::where('sku', $productData['sku'])->first();
            if (!$product) {
                $product = Product::create([
                    'name' => $productData['name'],
                    'sku' => $productData['sku'],
                    'description' => $productData['description'],
                    'id_category' => $category->id_category,
                    'price' => $productData['price'],
                    'is_active' => true,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]);
            }

            // Create or update inventory
            $inventory = Inventory::where('id_product', $product->id_product)->first();
            if (!$inventory) {
                Inventory::create([
                    'id_product' => $product->id_product,
                    'current_stock' => $productData['stock'],
                    'reserved_stock' => 0,
                    'reorder_level' => $productData['stock'] < 10 ? 5 : 10,
                    'max_stock_level' => $productData['stock'] * 2,
                    'average_cost' => $productData['cost'],
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]);
            }
        }

        $this->command->info('Inventory test data created successfully!');
        $this->command->info('Created ' . count($products) . ' products with inventory records.');
    }
}
