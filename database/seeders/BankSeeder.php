<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            [
                'code' => 'bca',
                'name' => 'Bank Central Asia',
                'description' => 'Bank swasta terbesar di Indonesia dengan jaringan ATM terluas.',
                'icon' => 'bca.png',
                'is_active' => true,
            ],
            [
                'code' => 'mandiri',
                'name' => 'Bank Mandiri',
                'description' => 'Bank BUMN terbesar di Indonesia hasil merger empat bank negara.',
                'icon' => 'mandiri.png',
                'is_active' => true,
            ],
            [
                'code' => 'bri',
                'name' => 'Bank Rakyat Indonesia',
                'description' => 'Bank BUMN dengan fokus pada pelayanan perbankan bagi masyarakat kecil.',
                'icon' => 'bri.png',
                'is_active' => true,
            ],
            [
                'code' => 'bni',
                'name' => 'Bank Negara Indonesia',
                'description' => 'Bank BUMN dengan fokus pada perdagangan internasional dan UMKM.',
                'icon' => 'bni.png',
                'is_active' => true,
            ],
            [
                'code' => 'cimb',
                'name' => 'CIMB Niaga',
                'description' => 'Bank swasta dengan layanan perbankan modern dan digital.',
                'icon' => 'cimb.png',
                'is_active' => true,
            ],
            [
                'code' => 'danamon',
                'name' => 'Bank Danamon',
                'description' => 'Bank swasta dengan fokus pada retail banking dan wealth management.',
                'icon' => 'danamon.png',
                'is_active' => true,
            ],
            [
                'code' => 'btn',
                'name' => 'Bank Tabungan Negara',
                'description' => 'Bank BUMN yang fokus pada pembiayaan perumahan.',
                'icon' => 'btn.png',
                'is_active' => true,
            ],
            [
                'code' => 'permata',
                'name' => 'Bank Permata',
                'description' => 'Bank swasta hasil merger antara Bank Bali dan Bank Universal.',
                'icon' => 'permata.png',
                'is_active' => true,
            ],
            [
                'code' => 'panin',
                'name' => 'Bank Panin',
                'description' => 'Bank swasta dengan layanan perbankan komprehensif.',
                'icon' => 'panin.png',
                'is_active' => true,
            ],
            [
                'code' => 'maybank',
                'name' => 'Maybank Indonesia',
                'description' => 'Bank swasta asal Malaysia dengan layanan perbankan internasional.',
                'icon' => 'maybank.png',
                'is_active' => true,
            ],
        ];

        foreach ($banks as $bank) {
            \App\Models\Bank::create($bank);
        }
    }
}
