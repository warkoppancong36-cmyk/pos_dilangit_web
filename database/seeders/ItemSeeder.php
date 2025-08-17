<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\User;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // Get first user for created_by field
        
        $items = [
            [
                'name' => 'Tepung Terigu Premium',
                'description' => 'Tepung terigu berkualitas tinggi untuk roti dan kue',
                'item_code' => null, // Will be auto-generated
                'unit' => 'kg',
                'cost_per_unit' => 12000,
                'current_stock' => 100,
                'minimum_stock' => 20,
                'maximum_stock' => 200,
                'supplier_id' => null,
                'storage_location' => 'Gudang A - Rak 1',
                'expiry_date' => now()->addMonths(6),
                'active' => true,
                'properties' => [
                    'protein_content' => '12-14%',
                    'moisture' => 'max 14%',
                    'ash' => 'max 0.6%'
                ],
                'created_by' => $user->id,
            ],
            [
                'name' => 'Gula Pasir Kristal',
                'description' => 'Gula pasir putih kristal untuk baking',
                'unit' => 'kg',
                'cost_per_unit' => 15000,
                'current_stock' => 80,
                'minimum_stock' => 15,
                'maximum_stock' => 150,
                'storage_location' => 'Gudang A - Rak 2',
                'active' => true,
                'properties' => [
                    'sweetness' => '99.8%',
                    'color' => 'putih',
                    'crystal_size' => 'fine'
                ],
                'created_by' => $user->id,
            ],
            [
                'name' => 'Mentega Unsalted',
                'description' => 'Mentega tawar untuk baking dan cooking',
                'unit' => 'kg',
                'cost_per_unit' => 45000,
                'current_stock' => 25,
                'minimum_stock' => 5,
                'maximum_stock' => 50,
                'storage_location' => 'Kulkas - Rak 1',
                'expiry_date' => now()->addMonths(3),
                'active' => true,
                'properties' => [
                    'fat_content' => '82%',
                    'storage_temp' => '2-8°C',
                    'type' => 'unsalted'
                ],
                'created_by' => $user->id,
            ],
            [
                'name' => 'Telur Ayam Grade A',
                'description' => 'Telur ayam segar grade A ukuran besar',
                'unit' => 'butir',
                'cost_per_unit' => 2500,
                'current_stock' => 200,
                'minimum_stock' => 50,
                'maximum_stock' => 500,
                'storage_location' => 'Kulkas - Rak 2',
                'expiry_date' => now()->addWeeks(2),
                'active' => true,
                'properties' => [
                    'grade' => 'A',
                    'size' => 'large',
                    'storage_temp' => '2-8°C'
                ],
                'created_by' => $user->id,
            ],
            [
                'name' => 'Susu UHT Full Cream',
                'description' => 'Susu UHT full cream 1 liter untuk baking',
                'unit' => 'liter',
                'cost_per_unit' => 18000,
                'current_stock' => 50,
                'minimum_stock' => 10,
                'maximum_stock' => 100,
                'storage_location' => 'Gudang B - Rak 1',
                'expiry_date' => now()->addMonths(8),
                'active' => true,
                'properties' => [
                    'fat_content' => '3.5%',
                    'protein' => '3.2%',
                    'type' => 'UHT'
                ],
                'created_by' => $user->id,
            ],
            [
                'name' => 'Vanilla Extract',
                'description' => 'Ekstrak vanili murni untuk flavoring',
                'unit' => 'ml',
                'cost_per_unit' => 150,
                'current_stock' => 1000,
                'minimum_stock' => 200,
                'maximum_stock' => 2000,
                'storage_location' => 'Gudang B - Rak 2',
                'active' => true,
                'properties' => [
                    'alcohol_content' => '35%',
                    'type' => 'pure extract',
                    'origin' => 'Madagascar'
                ],
                'created_by' => $user->id,
            ],
            [
                'name' => 'Cokelat Bubuk',
                'description' => 'Cokelat bubuk premium untuk kue dan minuman',
                'unit' => 'kg',
                'cost_per_unit' => 85000,
                'current_stock' => 15,
                'minimum_stock' => 3,
                'maximum_stock' => 30,
                'storage_location' => 'Gudang A - Rak 3',
                'expiry_date' => now()->addMonths(12),
                'active' => true,
                'properties' => [
                    'cocoa_content' => '22-24%',
                    'fat_content' => '10-12%',
                    'alkalized' => true
                ],
                'created_by' => $user->id,
            ],
            [
                'name' => 'Baking Powder',
                'description' => 'Baking powder double acting untuk pengembang kue',
                'unit' => 'gram',
                'cost_per_unit' => 25,
                'current_stock' => 2000,
                'minimum_stock' => 500,
                'maximum_stock' => 5000,
                'storage_location' => 'Gudang B - Rak 3',
                'expiry_date' => now()->addMonths(18),
                'active' => true,
                'properties' => [
                    'type' => 'double acting',
                    'aluminum_free' => true,
                    'leavening_power' => 'high'
                ],
                'created_by' => $user->id,
            ]
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
