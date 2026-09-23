<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression: a failed/duplicate/retried payment attempt must not inflate
 * Order::total_paid (and therefore remaining_amount / is_paid), which
 * previously summed ALL payment rows regardless of status.
 */
class OrderTotalPaidTest extends TestCase
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
    }

    public function test_total_paid_ignores_non_paid_payment_rows(): void
    {
        $user = User::factory()->create(['username' => 'total-paid-test']);

        $order = Order::create([
            'order_number' => 'ORD-PAID-' . Str::random(8),
            'id_user' => $user->id,
            'order_type' => 'dine_in',
            'status' => 'completed',
            'subtotal' => 26000,
            'total_amount' => 26000,
            'order_date' => now(),
        ]);

        Payment::create([
            'id_order' => $order->id_order,
            'payment_number' => 'PAY-' . Str::random(8),
            'payment_method' => 'cash',
            'amount' => 26000,
            'status' => 'paid',
            'payment_date' => now(),
            'processed_by' => $user->id,
        ]);

        // A stray/duplicate second row — e.g. a retried request or a
        // pending attempt that never actually succeeded.
        Payment::create([
            'id_order' => $order->id_order,
            'payment_number' => 'PAY-' . Str::random(8),
            'payment_method' => 'cash',
            'amount' => 5000,
            'status' => 'pending',
            'payment_date' => now(),
            'processed_by' => $user->id,
        ]);

        $order->refresh();

        $this->assertEquals(
            26000,
            $order->total_paid,
            'total_paid must only count payments with status=paid, not every row for the order.'
        );
        $this->assertEquals(0, $order->remaining_amount);
        $this->assertTrue($order->is_paid);
    }
}
