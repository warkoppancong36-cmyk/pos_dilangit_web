<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user for created_by
        $adminUser = User::where('email', 'admin@example.com')->first();
        $userId = $adminUser ? $adminUser->id : 1;

        $categories = [
            [
                'name' => 'Coffee',
                'description' => 'Berbagai jenis kopi premium dari biji pilihan terbaik',
                'image' => null,
                'active' => true,
                'created_by' => $userId,
            ],
            [
                'name' => 'Tea',
                'description' => 'Koleksi teh berkualitas tinggi dari berbagai daerah',
                'image' => null,
                'active' => true,
                'created_by' => $userId,
            ],
            [
                'name' => 'Pastry',
                'description' => 'Kue dan pastry segar dibuat setiap hari',
                'image' => null,
                'active' => true,
                'created_by' => $userId,
            ],
            [
                'name' => 'Sandwich',
                'description' => 'Sandwich sehat dengan bahan-bahan segar',
                'image' => null,
                'active' => true,
                'created_by' => $userId,
            ],
            [
                'name' => 'Dessert',
                'description' => 'Dessert manis untuk melengkapi pengalaman ',
                'image' => null,
                'active' => true,
                'created_by' => $userId,
            ],
            [
                'name' => 'Non-Coffee',
                'description' => 'Minuman non-kopi seperti smoothie, jus, dan minuman dingin lainnya',
                'image' => null,
                'active' => true,
                'created_by' => $userId,
            ],
            [
                'name' => 'Hot Beverage',
                'description' => 'Minuman hangat selain kopi untuk cuaca dingin',
                'image' => null,
                'active' => true,
                'created_by' => $userId,
            ],
            [
                'name' => 'Cold Beverage',
                'description' => 'Minuman dingin menyegarkan untuk cuaca panas',
                'image' => null,
                'active' => true,
                'created_by' => $userId,
            ],
            [
                'name' => 'Snacks',
                'description' => 'Camilan ringan yang cocok dinikmati dengan kopi',
                'image' => null,
                'active' => true,
                'created_by' => $userId,
            ],
            [
                'name' => 'Breakfast',
                'description' => 'Menu sarapan sehat untuk memulai hari',
                'image' => null,
                'active' => false, // Sample inactive category
                'created_by' => $userId,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
