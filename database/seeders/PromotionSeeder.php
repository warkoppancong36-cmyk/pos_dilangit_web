<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Promotion;
use Carbon\Carbon;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promotions = [
            [
                'name' => 'Happy Hour 20%',
                'description' => 'Diskon 20% untuk pembelian pada jam 15:00-17:00',
                'type' => 'happy_hour',
                'discount_value' => 20,
                'discount_type' => 'percentage',
                'priority' => 1,
                'valid_from' => Carbon::now()->subDays(10),
                'valid_until' => Carbon::now()->addMonths(3),
                'valid_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'valid_time_from' => '15:00',
                'valid_time_until' => '17:00',
                'active' => true,
                'promotion_rules' => [
                    'applicable_categories' => ['beverages'],
                    'usage_limit_per_customer' => 1,
                    'total_usage_limit' => 100,
                    'max_discount_amount' => 50000
                ],
                'created_by' => 1,
            ],
            [
                'name' => 'Buy 2 Get 1 Free Coffee',
                'description' => 'Beli 2 kopi gratis 1 untuk semua jenis kopi',
                'type' => 'buy_one_get_one',
                'discount_value' => 100,
                'discount_type' => 'percentage',
                'priority' => 2,
                'valid_from' => Carbon::now()->subDays(5),
                'valid_until' => Carbon::now()->addMonths(2),
                'valid_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                'active' => true,
                'promotion_rules' => [
                    'applicable_categories' => ['coffee'],
                    'usage_limit_per_customer' => 2,
                    'total_usage_limit' => 200,
                    'buy_quantity' => 2,
                    'get_quantity' => 1
                ],
                'created_by' => 1,
            ],
            [
                'name' => 'Breakfast Combo Deal',
                'description' => 'Paket hemat sarapan: kopi + roti hanya 25K',
                'type' => 'combo_deal',
                'discount_value' => 15000,
                'discount_type' => 'fixed_amount',
                'priority' => 3,
                'valid_from' => Carbon::now()->subDays(2),
                'valid_until' => Carbon::now()->addMonth(),
                'valid_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'valid_time_from' => '06:00',
                'valid_time_until' => '10:00',
                'active' => true,
                'promotion_rules' => [
                    'applicable_categories' => ['food', 'beverages'],
                    'combo_items' => ['coffee', 'bread'],
                    'usage_limit_per_customer' => 1,
                    'minimum_amount' => 50000,
                    'max_discount_amount' => 15000
                ],
                'created_by' => 1,
            ],
            [
                'name' => 'Weekend Member Discount',
                'description' => 'Diskon 15% khusus member di akhir pekan',
                'type' => 'member_discount',
                'discount_value' => 15,
                'discount_type' => 'percentage',
                'priority' => 4,
                'valid_from' => Carbon::now()->subDays(7),
                'valid_until' => Carbon::now()->addMonths(2),
                'valid_days' => ['saturday', 'sunday'],
                'active' => false, // Inactive
                'promotion_rules' => [
                    'customer_type' => 'member',
                    'minimum_purchase' => 100000,
                    'max_discount_amount' => 30000
                ],
                'created_by' => 1,
            ],
            [
                'name' => 'Summer Special 25%',
                'description' => 'Diskon musim panas yang akan aktif minggu depan',
                'type' => 'seasonal',
                'discount_value' => 25,
                'discount_type' => 'percentage',
                'priority' => 5,
                'valid_from' => Carbon::now()->addDays(7), // Scheduled
                'valid_until' => Carbon::now()->addDays(30),
                'valid_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                'active' => true,
                'promotion_rules' => [
                    'special_event' => 'summer_promo',
                    'usage_limit_per_customer' => 3,
                    'minimum_amount' => 80000,
                    'max_discount_amount' => 40000
                ],
                'created_by' => 1,
            ]
        ];

        foreach ($promotions as $promotion) {
            Promotion::create($promotion);
        }
    }
}
