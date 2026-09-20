<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FixedPriceDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        Sanctum::actingAs(User::factory()->create(['username' => 'discount-test']));
    }

    /** Admin must be able to create a "fixed_price" discount (price becomes exactly this value). */
    public function test_admin_can_create_fixed_price_discount(): void
    {
        $response = $this->postJson('/api/discounts', [
            'code' => 'HARGA20K',
            'name' => 'Harga Tetap 20rb',
            'type' => 'fixed_price',
            'value' => 20000,
            'valid_from' => now()->subDay()->toDateTimeString(),
            'valid_until' => now()->addMonth()->toDateTimeString(),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('discounts', [
            'code' => 'HARGA20K',
            'type' => 'fixed_price',
        ]);
    }

    /** A 50.000 product with a fixed_price(20.000) discount must end up costing exactly 20.000. */
    public function test_fixed_price_discount_makes_final_price_equal_to_its_value_on_expensive_product(): void
    {
        $discount = Discount::create([
            'code' => 'HARGA20K',
            'name' => 'Harga Tetap 20rb',
            'type' => 'fixed_price',
            'value' => 20000,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'active' => true,
        ]);

        $discountAmount = $discount->calculateDiscount(50000);
        $finalPrice = 50000 - $discountAmount;

        $this->assertSame(20000.0, $finalPrice);
    }

    /**
     * Per product decision: a fixed_price discount ALWAYS forces the final price to its
     * value, even when that is higher than the item's original price.
     */
    public function test_fixed_price_discount_still_applies_when_original_price_is_lower(): void
    {
        $discount = Discount::create([
            'code' => 'HARGA20K',
            'name' => 'Harga Tetap 20rb',
            'type' => 'fixed_price',
            'value' => 20000,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'active' => true,
        ]);

        $discountAmount = $discount->calculateDiscount(15000);
        $finalPrice = 15000 - $discountAmount;

        $this->assertSame(20000.0, $finalPrice);
    }

    /** Admin UI displays formatted_value; fixed_price must render like a rupiah amount. */
    public function test_fixed_price_discount_formats_value_as_rupiah(): void
    {
        $discount = Discount::create([
            'code' => 'HARGA20K',
            'name' => 'Harga Tetap 20rb',
            'type' => 'fixed_price',
            'value' => 20000,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'active' => true,
        ]);

        $this->assertSame('Rp 20.000', $discount->formatted_value);
    }

    /**
     * Regression: maximum_discount is a knob for percentage/fixed_amount
     * discounts ("don't discount more than Rp X"). It must NOT apply to
     * fixed_price, or the final price stops being exactly the discount's
     * value whenever an admin also sets a maximum_discount.
     */
    public function test_maximum_discount_cap_does_not_affect_fixed_price(): void
    {
        $discount = Discount::create([
            'code' => 'HARGA20K',
            'name' => 'Harga Tetap 20rb',
            'type' => 'fixed_price',
            'value' => 20000,
            'maximum_discount' => 10000, // Should be ignored for fixed_price
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'active' => true,
        ]);

        $discountAmount = $discount->calculateDiscount(50000);
        $finalPrice = 50000 - $discountAmount;

        $this->assertSame(20000.0, $finalPrice);
    }
}
