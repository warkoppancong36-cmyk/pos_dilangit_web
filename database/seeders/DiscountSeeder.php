<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Discount;
use Carbon\Carbon;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discounts = [
            [
                'code' => 'WELCOME20',
                'name' => 'Diskon Pembuka',
                'description' => 'Diskon 20% untuk pelanggan baru',
                'type' => 'percentage',
                'value' => 20,
                'minimum_amount' => 50000,
                'maximum_discount' => 25000,
                'usage_limit' => 100,
                'used_count' => 25,
                'valid_from' => Carbon::now()->subDays(10),
                'valid_until' => Carbon::now()->addMonths(3),
                'active' => true,
                'conditions' => [
                    'first_time_customer' => true,
                    'minimum_purchase' => 50000
                ],
                'created_by' => 1,
            ],
            [
                'code' => 'SAVE10K',
                'name' => 'Diskon Fixed',
                'description' => 'Potongan 10K untuk setiap pembelian',
                'type' => 'fixed_amount',
                'value' => 10000,
                'minimum_amount' => 100000,
                'maximum_discount' => 10000,
                'usage_limit' => null,
                'used_count' => 45,
                'valid_from' => Carbon::now()->subDays(5),
                'valid_until' => Carbon::now()->addMonths(2),
                'active' => true,
                'conditions' => [
                    'minimum_purchase' => 100000
                ],
                'created_by' => 1,
            ],
            [
                'code' => 'WEEKEND15',
                'name' => 'Diskon Weekend',
                'description' => 'Diskon khusus akhir pekan',
                'type' => 'percentage',
                'value' => 15,
                'minimum_amount' => 75000,
                'maximum_discount' => 20000,
                'usage_limit' => 200,
                'used_count' => 180,
                'valid_from' => Carbon::now()->subMonths(2),
                'valid_until' => Carbon::now()->subDays(10), // Expired
                'active' => false,
                'conditions' => [
                    'minimum_purchase' => 75000,
                    'valid_days' => ['saturday', 'sunday']
                ],
                'created_by' => 1,
            ],
            [
                'code' => 'STUDENT25',
                'name' => 'Diskon Mahasiswa',
                'description' => 'Diskon 25% khusus mahasiswa',
                'type' => 'percentage',
                'value' => 25,
                'minimum_amount' => 30000,
                'maximum_discount' => 15000,
                'usage_limit' => 50,
                'used_count' => 8,
                'valid_from' => Carbon::now()->subDays(2),
                'valid_until' => Carbon::now()->addMonths(1),
                'active' => true,
                'customer_groups' => ['student'],
                'conditions' => [
                    'minimum_purchase' => 30000,
                    'student_verification' => true
                ],
                'created_by' => 1,
            ],
            [
                'code' => 'FUTURE50',
                'name' => 'Diskon Masa Depan',
                'description' => 'Diskon yang akan aktif minggu depan',
                'type' => 'percentage',
                'value' => 50,
                'minimum_amount' => 200000,
                'maximum_discount' => 50000,
                'usage_limit' => 20,
                'used_count' => 0,
                'valid_from' => Carbon::now()->addDays(7), // Scheduled
                'valid_until' => Carbon::now()->addDays(14),
                'active' => true,
                'conditions' => [
                    'minimum_purchase' => 200000,
                    'special_event' => true
                ],
                'created_by' => 1,
            ]
        ];

        foreach ($discounts as $discount) {
            Discount::create($discount);
        }
    }
}
