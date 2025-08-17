<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada kategori terlebih dahulu
        $beverageCategory = Category::where('name', 'Minuman')->first();
        $foodCategory = Category::where('name', 'Makanan')->first();
        $snackCategory = Category::where('name', 'Snack')->first();

        // Jika kategori belum ada, buat default
        if (!$beverageCategory) {
            $beverageCategory = Category::create([
                'name' => 'Minuman',
                'description' => 'Kategori untuk minuman',
                'active' => true
            ]);
        }

        if (!$foodCategory) {
            $foodCategory = Category::create([
                'name' => 'Makanan',
                'description' => 'Kategori untuk makanan',
                'active' => true
            ]);
        }

        if (!$snackCategory) {
            $snackCategory = Category::create([
                'name' => 'Snack',
                'description' => 'Kategori untuk snack',
                'active' => true
            ]);
        }

        // Sample products data
        $products = [
            // Minuman
            [
                'name' => 'Kopi Americano',
                'slug' => 'kopi-americano',
                'description' => 'Kopi hitam dengan rasa yang kuat dan aroma yang menggugah selera. Dibuat dari biji kopi pilihan yang diseduh dengan sempurna.',
                'sku' => 'MIN-001-' . date('ymd'),
                'barcode' => '1234567890123',
                'price' => 25000,
                'cost' => 12000,
                'stock' => 100,
                'min_stock' => 10,
                'unit' => 'cup',
                'weight' => 350,
                'dimensions' => json_encode(['length' => 8, 'width' => 8, 'height' => 12]),
                'category_id' => $beverageCategory->id_category,
                'brand' => 'Coffee House',
                'tags' => json_encode(['kopi', 'americano', 'hot', 'caffeine']),
                'meta_title' => 'Kopi Americano - Coffee House',
                'meta_description' => 'Nikmati kopi americano terbaik dengan cita rasa yang autentik',
                'status' => 'published',
                'active' => true,
                'featured' => true,
            ],
            [
                'name' => 'Cappuccino',
                'slug' => 'cappuccino',
                'description' => 'Perpaduan sempurna antara espresso, steamed milk, dan milk foam yang creamy.',
                'sku' => 'MIN-002-' . date('ymd'),
                'barcode' => '1234567890124',
                'price' => 30000,
                'cost' => 15000,
                'stock' => 80,
                'min_stock' => 15,
                'unit' => 'cup',
                'weight' => 400,
                'dimensions' => json_encode(['length' => 8, 'width' => 8, 'height' => 12]),
                'category_id' => $beverageCategory->id_category,
                'brand' => 'Coffee House',
                'tags' => json_encode(['kopi', 'cappuccino', 'milk', 'foam']),
                'meta_title' => 'Cappuccino - Coffee House',
                'meta_description' => 'Cappuccino dengan foam art yang indah dan rasa yang sempurna',
                'status' => 'published',
                'active' => true,
                'featured' => true,
            ],
            [
                'name' => 'Ice Tea Lemon',
                'slug' => 'ice-tea-lemon',
                'description' => 'Teh dingin segar dengan perasan lemon alami yang menyegarkan.',
                'sku' => 'MIN-003-' . date('ymd'),
                'barcode' => '1234567890125',
                'price' => 18000,
                'cost' => 8000,
                'stock' => 120,
                'min_stock' => 20,
                'unit' => 'glass',
                'weight' => 500,
                'category_id' => $beverageCategory->id_category,
                'brand' => 'Fresh Tea',
                'tags' => json_encode(['tea', 'lemon', 'cold', 'refreshing']),
                'status' => 'published',
                'active' => true,
                'featured' => false,
            ],
            [
                'name' => 'Chocolate Frappe',
                'slug' => 'chocolate-frappe',
                'description' => 'Minuman cokelat dingin yang creamy dengan whipped cream di atasnya.',
                'sku' => 'MIN-004-' . date('ymd'),
                'price' => 35000,
                'cost' => 18000,
                'stock' => 5,
                'min_stock' => 10,
                'unit' => 'cup',
                'weight' => 450,
                'category_id' => $beverageCategory->id_category,
                'brand' => 'Sweet Treats',
                'tags' => json_encode(['chocolate', 'frappe', 'cold', 'sweet']),
                'status' => 'published',
                'active' => true,
                'featured' => false,
            ],

            // Makanan
            [
                'name' => 'Nasi Gudeg Jogja',
                'slug' => 'nasi-gudeg-jogja',
                'description' => 'Gudeg khas Jogja yang manis dengan ayam, telur, dan sambal krecek.',
                'sku' => 'MKN-001-' . date('ymd'),
                'barcode' => '2234567890123',
                'price' => 45000,
                'cost' => 22000,
                'stock' => 30,
                'min_stock' => 5,
                'unit' => 'porsi',
                'weight' => 800,
                'dimensions' => json_encode(['length' => 25, 'width' => 20, 'height' => 5]),
                'category_id' => $foodCategory->id_category,
                'brand' => 'Warung Jogja',
                'tags' => json_encode(['nasi', 'gudeg', 'jogja', 'traditional']),
                'meta_title' => 'Nasi Gudeg Jogja Asli',
                'meta_description' => 'Rasakan kelezatan gudeg khas Jogja dengan cita rasa autentik',
                'status' => 'published',
                'active' => true,
                'featured' => true,
            ],
            [
                'name' => 'Sate Ayam Madura',
                'slug' => 'sate-ayam-madura',
                'description' => 'Sate ayam khas Madura dengan bumbu kacang yang gurih dan pedas.',
                'sku' => 'MKN-002-' . date('ymd'),
                'price' => 38000,
                'cost' => 18000,
                'stock' => 25,
                'min_stock' => 8,
                'unit' => 'porsi',
                'weight' => 600,
                'category_id' => $foodCategory->id_category,
                'brand' => 'Sate Pak Mul',
                'tags' => json_encode(['sate', 'ayam', 'madura', 'bumbu kacang']),
                'status' => 'published',
                'active' => true,
                'featured' => true,
            ],
            [
                'name' => 'Gado-Gado Jakarta',
                'slug' => 'gado-gado-jakarta',
                'description' => 'Salad Indonesia dengan sayuran segar dan bumbu kacang yang lezat.',
                'sku' => 'MKN-003-' . date('ymd'),
                'price' => 28000,
                'cost' => 12000,
                'stock' => 0,
                'min_stock' => 10,
                'unit' => 'porsi',
                'weight' => 700,
                'category_id' => $foodCategory->id_category,
                'brand' => 'Warung Sehat',
                'tags' => json_encode(['gado-gado', 'sayuran', 'sehat', 'kacang']),
                'status' => 'published',
                'active' => true,
                'featured' => false,
            ],

            // Snack
            [
                'name' => 'Keripik Singkong Balado',
                'slug' => 'keripik-singkong-balado',
                'description' => 'Keripik singkong renyah dengan bumbu balado yang pedas dan gurih.',
                'sku' => 'SNK-001-' . date('ymd'),
                'barcode' => '3234567890123',
                'price' => 15000,
                'cost' => 7000,
                'stock' => 50,
                'min_stock' => 15,
                'unit' => 'pack',
                'weight' => 200,
                'dimensions' => json_encode(['length' => 15, 'width' => 10, 'height' => 20]),
                'category_id' => $snackCategory->id_category,
                'brand' => 'Keripik Nusantara',
                'tags' => json_encode(['keripik', 'singkong', 'balado', 'pedas']),
                'status' => 'published',
                'active' => true,
                'featured' => false,
            ],
            [
                'name' => 'Pisang Goreng Kipas',
                'slug' => 'pisang-goreng-kipas',
                'description' => 'Pisang goreng dengan tepung crispy yang renyah di luar dan lembut di dalam.',
                'sku' => 'SNK-002-' . date('ymd'),
                'price' => 12000,
                'cost' => 5000,
                'stock' => 3,
                'min_stock' => 10,
                'unit' => 'pcs',
                'weight' => 150,
                'category_id' => $snackCategory->id_category,
                'brand' => 'Gorengan Enak',
                'tags' => json_encode(['pisang', 'goreng', 'crispy', 'tradisional']),
                'status' => 'published',
                'active' => true,
                'featured' => false,
            ],
            [
                'name' => 'Donat Mini Glaze',
                'slug' => 'donat-mini-glaze',
                'description' => 'Donat mini dengan glaze manis dalam berbagai varian rasa.',
                'sku' => 'SNK-003-' . date('ymd'),
                'price' => 20000,
                'cost' => 10000,
                'stock' => 60,
                'min_stock' => 12,
                'unit' => 'pack',
                'weight' => 300,
                'category_id' => $snackCategory->id_category,
                'brand' => 'Sweet Bakery',
                'tags' => json_encode(['donat', 'mini', 'glaze', 'manis']),
                'status' => 'published',
                'active' => true,
                'featured' => true,
            ],

            // Draft products
            [
                'name' => 'Kopi Luwak Premium',
                'slug' => 'kopi-luwak-premium',
                'description' => 'Kopi luwak premium dengan kualitas terbaik dan rasa yang eksotis.',
                'sku' => 'MIN-005-' . date('ymd'),
                'price' => 150000,
                'cost' => 80000,
                'stock' => 10,
                'min_stock' => 2,
                'unit' => 'cup',
                'weight' => 350,
                'category_id' => $beverageCategory->id_category,
                'brand' => 'Premium Coffee',
                'tags' => json_encode(['kopi', 'luwak', 'premium', 'exotic']),
                'status' => 'draft',
                'active' => false,
                'featured' => false,
            ],
        ];

        // Insert products
        foreach ($products as $productData) {
            Product::create($productData);
        }

        $this->command->info('Products seeded successfully!');
    }
}
