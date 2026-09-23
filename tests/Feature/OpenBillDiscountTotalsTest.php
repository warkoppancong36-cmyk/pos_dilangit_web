<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression: order totals must not count an item discount twice.
 *
 * Each order item's total_price is already net of its own discount, so the
 * order's subtotal must be the GROSS line total, and the order's
 * discount_amount/total_amount must subtract each discount exactly once —
 * both when an item discount is (re)applied and when an order-level discount
 * is added at payment time on top of existing item discounts.
 */
class OpenBillDiscountTotalsTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private OrderItem $affogato;
    private OrderItem $avocado;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        $user = User::factory()->create(['username' => 'totals-test']);
        Sanctum::actingAs($user);

        $this->order = Order::create([
            'order_number' => 'ORD-TOTAL-' . Str::random(8),
            'id_user' => $user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'subtotal' => 63000,
            'total_amount' => 63000,
            'order_date' => now(),
        ]);

        $this->affogato = $this->makeItem('Affogato', 26000);
        $this->avocado = $this->makeItem('Avocado Coffee', 37000);
    }

    private function makeItem(string $name, float $price): OrderItem
    {
        return OrderItem::create([
            'id_order' => $this->order->id_order,
            'item_type' => 'product',
            'item_name' => $name,
            'item_sku' => 'SKU-' . Str::random(4),
            'quantity' => 1,
            'unit_price' => $price,
            'total_price' => $price,
        ]);
    }

    /** Affogato 26.000 → fixed_price 10.000; Avocado stays 37.000. */
    public function test_applying_an_item_discount_keeps_subtotal_gross_and_subtracts_it_once(): void
    {
        $this->putJson(
            "/api/pos/orders/{$this->order->id_order}/items/{$this->affogato->id_order_item}/discount",
            ['discount_type' => 'fixed_price', 'discount_amount' => 10000]
        )->assertSuccessful();

        $order = $this->order->fresh();

        $this->assertEquals(63000, $order->subtotal, 'subtotal must stay the gross line total');
        $this->assertEquals(16000, $order->discount_amount);
        $this->assertEquals(47000, $order->total_amount, 'discount must be subtracted exactly once');
    }

    /** An order-level discount at payment time adds to the item discounts, not replaces/double-counts them. */
    public function test_order_level_discount_at_payment_adds_to_existing_item_discounts(): void
    {
        $this->putJson(
            "/api/pos/orders/{$this->order->id_order}/items/{$this->affogato->id_order_item}/discount",
            ['discount_type' => 'fixed_price', 'discount_amount' => 10000]
        )->assertSuccessful();

        $this->putJson("/api/pos/orders/{$this->order->id_order}/status", [
            'status' => 'completed',
            'payment_method' => 'cash',
            'discount_amount' => 5000,
            'paid_amount' => 100000,
        ])->assertSuccessful();

        $order = $this->order->fresh();

        $this->assertEquals(21000, $order->discount_amount, '16.000 item discount + 5.000 order-level');
        $this->assertEquals(42000, $order->total_amount, '63.000 gross - 21.000');
    }
}
