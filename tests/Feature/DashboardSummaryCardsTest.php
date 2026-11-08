<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\DashboardController;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Kartu ringkasan dashboard (getSummaryCards):
 *  - data kosong tampil 0, BUKAN angka contoh (dulu 45 order / Rp 2,5 juta / 13,64% / 18,42%)
 *  - satu order yang dibayar lebih dari sekali dihitung SATU kali (jumlah order dan omzet)
 */
class DashboardSummaryCardsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert(['id' => 1, 'name' => 'kasir', 'display_name' => 'Kasir']);
        $this->user = User::factory()->create(['username' => 'kasir-cards-test']);
        Sanctum::actingAs($this->user);
    }

    /**
     * @param array<int, array{0: string, 1: float}> $payments [metode, jumlah] — semuanya berstatus paid
     */
    private function makeOrder(float $total, array $payments, ?Carbon $date = null, string $type = 'dine_in'): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-CARD-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => $type,
            'status' => 'completed',
            'subtotal' => $total,
            'total_amount' => $total,
            'order_date' => $date ?? now(),
        ]);

        foreach ($payments as [$method, $amount]) {
            Payment::create([
                'id_order' => $order->id_order,
                'payment_number' => 'PAY-' . Str::random(8),
                'payment_method' => $method,
                'amount' => $amount,
                'status' => 'paid',
                'payment_date' => $date ?? now(),
                'processed_by' => $this->user->id,
            ]);
        }

        return $order;
    }

    private function cards(): array
    {
        $controller = app(DashboardController::class);
        $method = new \ReflectionMethod($controller, 'getSummaryCards');
        $method->setAccessible(true);

        return $method->invoke($controller, Carbon::now()->subDays(7)->startOfDay(), Carbon::now()->endOfDay());
    }

    public function test_empty_data_shows_zero_instead_of_sample_numbers(): void
    {
        $cards = $this->cards();

        $this->assertEquals(0, $cards['today_sales']['value']);
        $this->assertEquals(0, $cards['today_sales']['yesterday']);
        $this->assertEquals(0, $cards['today_sales']['last_week_same_day']);
        $this->assertEquals(0, $cards['today_orders']['value']);
        $this->assertEquals(0, $cards['today_orders']['yesterday']);
        $this->assertEquals(0, $cards['today_orders']['last_week_same_day']);
        $this->assertEquals(0, $cards['period_summary']['total_orders']);
        $this->assertEquals(0, $cards['period_summary']['total_sales']);

        // Pertumbuhan juga tidak boleh palsu (dulu 13,64% dan 18,42%)
        $this->assertEquals(0, $cards['today_sales']['growth']);
        $this->assertEquals(0, $cards['today_orders']['growth']);
    }

    public function test_order_paid_twice_is_counted_once(): void
    {
        // Bayar sebagian tunai + sisanya QRIS = SATU order dengan DUA baris pembayaran lunas
        $this->makeOrder(40000, [['cash', 20000], ['qris', 20000]]);

        $cards = $this->cards();

        $this->assertSame(1, (int) $cards['today_orders']['value'], 'Order yang dibayar dua kali tetap SATU order');
        $this->assertEqualsWithDelta(40000.0, (float) $cards['today_sales']['value'], 0.01, 'Omzet tidak boleh berlipat');
        $this->assertSame(1, (int) $cards['period_summary']['total_orders']);
        $this->assertEqualsWithDelta(40000.0, (float) $cards['period_summary']['total_sales'], 0.01);
        $this->assertEqualsWithDelta(40000.0, (float) $cards['period_summary']['avg_order_value'], 0.01, 'Rata-rata per order = omzet / jumlah order yang sama-sama tidak berlipat');

        $dineIn = collect($cards['order_types_today']['data'])->firstWhere('order_type', 'dine_in');
        $this->assertSame(1, (int) $dineIn['order_count']);
        $this->assertEqualsWithDelta(40000.0, (float) $dineIn['total_amount'], 0.01);
    }

    public function test_single_payment_orders_are_unchanged(): void
    {
        $this->makeOrder(20000, [['cash', 20000]]);
        $this->makeOrder(30000, [['qris', 30000]]);

        $cards = $this->cards();

        $this->assertSame(2, (int) $cards['today_orders']['value']);
        $this->assertEqualsWithDelta(50000.0, (float) $cards['today_sales']['value'], 0.01);
        $this->assertEqualsWithDelta(25000.0, (float) $cards['period_summary']['avg_order_value'], 0.01);
    }

    public function test_growth_is_computed_from_real_yesterday_data(): void
    {
        $this->makeOrder(20000, [['cash', 20000]], now()->subDay());
        $this->makeOrder(30000, [['cash', 30000]]);

        $cards = $this->cards();

        $this->assertEqualsWithDelta(50.0, (float) $cards['today_sales']['growth'], 0.01);   // 20.000 -> 30.000
        $this->assertEqualsWithDelta(0.0, (float) $cards['today_orders']['growth'], 0.01);   // 1 -> 1 order
    }

    public function test_growth_is_100_when_yesterday_was_empty_but_today_has_sales(): void
    {
        $this->makeOrder(30000, [['cash', 30000]]);

        $cards = $this->cards();

        $this->assertEqualsWithDelta(100.0, (float) $cards['today_sales']['growth'], 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $cards['today_orders']['growth'], 0.01);
    }

    public function test_free_order_and_unpaid_or_pending_orders_are_still_excluded(): void
    {
        $this->makeOrder(25000, [['cash', 25000]]);                    // dihitung

        $free = $this->makeOrder(0, [['qris', 0]]);                    // gratis (diskon 100%, bayar Rp 0)
        $free->update(['subtotal' => 30000, 'discount_amount' => 30000]);

        $pending = $this->makeOrder(15000, [['cash', 15000]]);         // belum selesai
        $pending->update(['status' => 'pending']);

        $unpaid = $this->makeOrder(18000, []);                         // tanpa pembayaran sama sekali

        $cards = $this->cards();

        $this->assertSame(1, (int) $cards['today_orders']['value']);
        $this->assertEqualsWithDelta(25000.0, (float) $cards['today_sales']['value'], 0.01);
    }
}
