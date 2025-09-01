<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Asset;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assets = [
            // Kitchen Equipment
            [
                'asset_code' => 'KIT-001',
                'name' => 'Commercial Oven',
                'category' => 'Kitchen Equipment',
                'brand' => 'Rational',
                'model' => 'SelfCookingCenter',
                'serial_number' => 'RAT-2024-001',
                'purchase_date' => '2024-01-15',
                'purchase_price' => 15999.99,
                'location' => 'Main Kitchen',
                'condition' => 'excellent',
                'status' => 'active',
                'description' => 'Commercial combi oven untuk dapur restaurant',
                'supplier' => 'Restaurant Supply Co',
                'warranty_until' => '2027-01-15',
                'assigned_to' => 'Head Chef',
                'department' => 'Kitchen',
            ],
            [
                'asset_code' => 'KIT-002',
                'name' => 'Commercial Refrigerator',
                'category' => 'Kitchen Equipment',
                'brand' => 'True Manufacturing',
                'model' => 'T-49-HC',
                'serial_number' => 'TRUE-2024-001',
                'purchase_date' => '2024-01-20',
                'purchase_price' => 2899.99,
                'location' => 'Cold Storage',
                'condition' => 'excellent',
                'status' => 'active',
                'description' => 'Kulkas commercial 2 pintu untuk penyimpanan bahan makanan',
                'supplier' => 'Restaurant Supply Co',
                'warranty_until' => '2026-01-20',
                'assigned_to' => 'Kitchen Staff',
                'department' => 'Kitchen',
            ],
            [
                'asset_code' => 'POS-001',
                'name' => 'Point of Sale Terminal',
                'category' => 'POS Equipment',
                'brand' => 'Square',
                'model' => 'Terminal',
                'serial_number' => 'SQ-2024-001',
                'purchase_date' => '2024-02-01',
                'purchase_price' => 299.99,
                'location' => 'Front Counter',
                'condition' => 'excellent',
                'status' => 'active',
                'description' => 'POS terminal untuk kasir utama',
                'supplier' => 'Square Inc',
                'warranty_until' => '2026-02-01',
                'assigned_to' => 'Cashier Team',
                'department' => 'Front of House',
            ],
            [
                'asset_code' => 'POS-002',
                'name' => 'Thermal Receipt Printer',
                'category' => 'POS Equipment',
                'brand' => 'Epson',
                'model' => 'TM-T88VI',
                'serial_number' => 'EP-2024-001',
                'purchase_date' => '2024-02-01',
                'purchase_price' => 199.99,
                'location' => 'Front Counter',
                'condition' => 'excellent',
                'status' => 'active',
                'description' => 'Printer thermal untuk struk kasir',
                'supplier' => 'POS Equipment Indonesia',
                'warranty_until' => '2025-02-01',
                'assigned_to' => 'Cashier Team',
                'department' => 'Front of House',
            ],
            [
                'asset_code' => 'FUR-001',
                'name' => 'Dining Table Set',
                'category' => 'Furniture',
                'brand' => 'Restaurant Furniture Plus',
                'model' => 'Classic Wood',
                'serial_number' => 'RF-2024-001',
                'purchase_date' => '2024-01-10',
                'purchase_price' => 459.99,
                'location' => 'Dining Area',
                'condition' => 'good',
                'status' => 'active',
                'description' => 'Set meja makan 4 kursi untuk area dining',
                'supplier' => 'Restaurant Furniture Plus',
                'warranty_until' => '2025-01-10',
                'assigned_to' => 'Front of House',
                'department' => 'Dining',
            ],
        ];

        foreach ($assets as $asset) {
            Asset::create($asset);
        }

        $this->command->info('Asset data berhasil dibuat!');
        $this->command->info('Total assets: ' . count($assets));
    }
}
