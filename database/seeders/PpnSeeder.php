<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ppn;
use App\Models\User;

class PpnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user as creator (role_id = 1 for admin)
        $adminUser = User::where('role_id', 1)->first();
        
        if (!$adminUser) {
            $this->command->error('No admin user found. Please create admin user first.');
            return;
        }

        $ppnData = [
            [
                'name' => 'PPN Standard 11%',
                'nominal' => 11,
                'description' => 'Pajak Pertambahan Nilai standar Indonesia 11%',
                'active' => true,
                'status' => 'standard',
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'PPN Lama 10%',
                'nominal' => 10,
                'description' => 'Pajak Pertambahan Nilai lama Indonesia 10% (untuk referensi historis)',
                'active' => false,
                'status' => 'legacy',
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'Non-PPN (0%)',
                'nominal' => 0,
                'description' => 'Untuk barang/jasa yang tidak dikenakan PPN',
                'active' => true,
                'status' => 'exempt',
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'PPN Ekspor (0%)',
                'nominal' => 0,
                'description' => 'PPN untuk barang ekspor (tarif 0%)',
                'active' => true,
                'status' => 'export',
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'PPN Khusus 5%',
                'nominal' => 5,
                'description' => 'PPN khusus untuk kategori tertentu (contoh: barang kebutuhan pokok)',
                'active' => false,
                'status' => 'special',
                'created_by' => $adminUser->id,
            ],
        ];

        foreach ($ppnData as $ppn) {
            Ppn::firstOrCreate(
                ['name' => $ppn['name']], // Check by name
                $ppn // Create with full data if not exists
            );
        }

        $this->command->info('PPN data seeded successfully!');
    }
}
