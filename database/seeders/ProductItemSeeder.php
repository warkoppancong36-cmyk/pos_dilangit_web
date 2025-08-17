<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductItem;
use App\Models\Product;
use App\Models\Item;
use App\Models\User;

class ProductItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        
        // Get some products and items
        $products = Product::take(3)->get();
        $items = Item::take(8)->get(); // Get more items
        
        if ($products->isEmpty() || $items->isEmpty()) {
            $this->command->warn('No products or items found. Make sure to run ProductSeeder and ItemSeeder first.');
            return;
        }
        
        if ($items->count() < 8) {
            $this->command->warn('Not enough items found. Expected 8, found ' . $items->count());
            return;
        }

        // Product 1 - Roti Cokelat (using multiple items)
        if (isset($products[0])) {
            $productItems = [
                [
                    'product_id' => $products[0]->id_product,
                    'item_id' => $items[0]->id_item, // Tepung Terigu
                    'quantity_needed' => 0.250, // 250 gram per roti
                    'unit' => 'kg',
                    'cost_per_unit' => null, // Use item's cost
                    'is_critical' => true,
                    'notes' => 'Bahan utama untuk adonan roti',
                    'created_by' => $user->id,
                ],
                [
                    'product_id' => $products[0]->id_product,
                    'item_id' => $items[1]->id_item, // Gula Pasir
                    'quantity_needed' => 0.050, // 50 gram per roti
                    'unit' => 'kg',
                    'is_critical' => true,
                    'notes' => 'Pemanis untuk roti',
                    'created_by' => $user->id,
                ],
                [
                    'product_id' => $products[0]->id_product,
                    'item_id' => $items[2]->id_item, // Mentega
                    'quantity_needed' => 0.030, // 30 gram per roti
                    'unit' => 'kg',
                    'is_critical' => true,
                    'notes' => 'Lemak untuk tekstur roti',
                    'created_by' => $user->id,
                ],
                [
                    'product_id' => $products[0]->id_product,
                    'item_id' => $items[6]->id_item, // Cokelat Bubuk
                    'quantity_needed' => 0.025, // 25 gram per roti
                    'unit' => 'kg',
                    'is_critical' => true,
                    'notes' => 'Rasa cokelat untuk roti',
                    'created_by' => $user->id,
                ],
            ];
            
            foreach ($productItems as $productItem) {
                ProductItem::create($productItem);
            }
        }

        // Product 2 - Kue Vanilla (using different items)
        if (isset($products[1])) {
            $productItems = [
                [
                    'product_id' => $products[1]->id_product,
                    'item_id' => $items[0]->id_item, // Tepung Terigu
                    'quantity_needed' => 0.200, // 200 gram per kue
                    'unit' => 'kg',
                    'is_critical' => true,
                    'notes' => 'Bahan utama untuk kue',
                    'created_by' => $user->id,
                ],
                [
                    'product_id' => $products[1]->id_product,
                    'item_id' => $items[1]->id_item, // Gula Pasir
                    'quantity_needed' => 0.080, // 80 gram per kue
                    'unit' => 'kg',
                    'is_critical' => true,
                    'notes' => 'Pemanis untuk kue',
                    'created_by' => $user->id,
                ],
                [
                    'product_id' => $products[1]->id_product,
                    'item_id' => $items[3]->id_item, // Telur
                    'quantity_needed' => 2, // 2 butir per kue
                    'unit' => 'butir',
                    'is_critical' => true,
                    'notes' => 'Pengikat adonan kue',
                    'created_by' => $user->id,
                ],
                [
                    'product_id' => $products[1]->id_product,
                    'item_id' => $items[5]->id_item, // Vanilla Extract
                    'quantity_needed' => 5, // 5 ml per kue
                    'unit' => 'ml',
                    'is_critical' => false,
                    'notes' => 'Aroma vanilla untuk kue',
                    'created_by' => $user->id,
                ],
                [
                    'product_id' => $products[1]->id_product,
                    'item_id' => $items[7]->id_item, // Baking Powder
                    'quantity_needed' => 10, // 10 gram per kue
                    'unit' => 'gram',
                    'is_critical' => true,
                    'notes' => 'Pengembang kue',
                    'created_by' => $user->id,
                ],
            ];
            
            foreach ($productItems as $productItem) {
                ProductItem::create($productItem);
            }
        }

        // Product 3 - Simple recipe with fewer items
        if (isset($products[2])) {
            $productItems = [
                [
                    'product_id' => $products[2]->id_product,
                    'item_id' => $items[4]->id_item, // Susu UHT
                    'quantity_needed' => 0.250, // 250 ml per produk
                    'unit' => 'liter',
                    'is_critical' => true,
                    'notes' => 'Bahan utama minuman',
                    'created_by' => $user->id,
                ],
                [
                    'product_id' => $products[2]->id_product,
                    'item_id' => $items[1]->id_item, // Gula Pasir
                    'quantity_needed' => 0.020, // 20 gram per produk
                    'unit' => 'kg',
                    'is_critical' => false,
                    'notes' => 'Pemanis minuman',
                    'created_by' => $user->id,
                ],
            ];
            
            foreach ($productItems as $productItem) {
                ProductItem::create($productItem);
            }
        }
    }
}
