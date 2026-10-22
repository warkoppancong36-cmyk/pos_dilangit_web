<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Laporan penjualan online: order yang dibayar lewat GoFood, GrabFood, atau
 * ShopeeFood (metode pembayaran gofood / grabfood / shopeefood).
 */
class OnlineSalesReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $this->user = User::factory()->create(['username' => 'online-report-test']);
        Sanctum::actingAs($this->user);
    }

    /**
     * @param array<int, array{0: string, 1: int, 2: int}> $items [nama, qty, harga]
     * @param array<int, array{0: string, 1: string}> $payments [metode, status]
     */
    private function makeOrder(string $date, string $status, array $items, array $payments): Order
    {
        $total = array_sum(array_map(fn ($i) => $i[1] * $i[2], $items));

        $order = Order::create([
            'order_number' => 'ORD-ONL-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'takeaway',
            'status' => $status,
            'subtotal' => $total,
            'total_amount' => $total,
            'order_date' => $date,
        ]);

        foreach ($items as [$name, $qty, $price]) {
            OrderItem::create([
                'id_order' => $order->id_order,
                'item_type' => 'product',
                'item_name' => $name,
                'item_sku' => 'SKU-' . Str::random(5),
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $qty * $price,
            ]);
        }

        foreach ($payments as [$method, $paymentStatus]) {
            Payment::create([
                'id_order' => $order->id_order,
                'payment_number' => 'PAY-' . Str::random(8),
                'payment_method' => $method,
                'amount' => $total,
                'status' => $paymentStatus,
                'payment_date' => $date,
                'processed_by' => $this->user->id,
            ]);
        }

        return $order;
    }

    private function seedOctober(): void
    {
        // Masuk laporan online
        $this->makeOrder('2026-10-05 10:00:00', 'completed', [['Nasi Goreng', 2, 25000]], [['gofood', 'paid']]);
        $this->makeOrder('2026-10-05 12:00:00', 'completed', [['Es Teh', 3, 10000]], [['grabfood', 'paid']]);
        $this->makeOrder('2026-10-06 09:00:00', 'completed', [['Nasi Goreng', 1, 25000]], [['gofood', 'paid']]);
        // Dua baris pembayaran untuk SATU order: tetap dihitung sekali
        $this->makeOrder('2026-10-07 15:00:00', 'completed', [['Kopi Susu', 1, 20000]], [['shopeefood', 'paid'], ['shopeefood', 'paid']]);

        // Bukan online, tapi masuk total semua penjualan
        $this->makeOrder('2026-10-06 11:00:00', 'completed', [['Paket Hemat', 1, 100000]], [['cash', 'paid']]);

        // Tidak boleh masuk sama sekali
        $this->makeOrder('2026-10-06 13:00:00', 'cancelled', [['Nasi Goreng', 1, 40000]], [['gofood', 'paid']]);
        $this->makeOrder('2026-10-06 14:00:00', 'completed', [['Nasi Goreng', 1, 45000]], [['gofood', 'pending']]);
        $this->makeOrder('2026-09-30 20:00:00', 'completed', [['Nasi Goreng', 1, 60000]], [['gofood', 'paid']]);
    }

    private function report()
    {
        return $this->getJson('/api/reports/online-sales?start_date=2026-10-01&end_date=2026-10-31')
            ->assertOk();
    }

    public function test_summary_counts_only_paid_online_orders_in_period(): void
    {
        $this->seedOctober();

        $summary = $this->report()->json('data.summary');

        $this->assertSame(4, $summary['total_orders']);
        $this->assertEquals(125000, $summary['total_revenue']);
        $this->assertEquals(31250, $summary['average_order_value']);
        $this->assertEquals(225000, $summary['all_sales_revenue']);
        $this->assertEquals(55.56, $summary['online_share_percentage']);
    }

    public function test_platforms_always_list_all_three_with_their_share(): void
    {
        $this->seedOctober();

        $platforms = collect($this->report()->json('data.platforms'))->keyBy('platform');

        $this->assertSame(['gofood', 'grabfood', 'shopeefood'], $platforms->keys()->all());
        $this->assertSame('GoFood', $platforms['gofood']['label']);
        $this->assertSame(2, $platforms['gofood']['order_count']);
        $this->assertEquals(75000, $platforms['gofood']['total_revenue']);
        $this->assertEquals(37500, $platforms['gofood']['average_order_value']);
        $this->assertEquals(60, $platforms['gofood']['revenue_percentage']);
        $this->assertEquals(30000, $platforms['grabfood']['total_revenue']);
        $this->assertEquals(24, $platforms['grabfood']['revenue_percentage']);
        $this->assertSame(1, $platforms['shopeefood']['order_count']);
        $this->assertEquals(20000, $platforms['shopeefood']['total_revenue']);
    }

    public function test_daily_trend_covers_every_day_of_the_period(): void
    {
        $this->seedOctober();

        $daily = collect($this->report()->json('data.daily'))->keyBy('date');

        $this->assertCount(31, $daily);
        $this->assertEquals(50000, $daily['2026-10-05']['gofood']);
        $this->assertEquals(30000, $daily['2026-10-05']['grabfood']);
        $this->assertEquals(80000, $daily['2026-10-05']['total']);
        $this->assertSame(2, $daily['2026-10-05']['order_count']);
        $this->assertEquals(20000, $daily['2026-10-07']['shopeefood']);
        $this->assertEquals(0, $daily['2026-10-10']['total']);
    }

    public function test_top_products_are_per_platform(): void
    {
        $this->seedOctober();

        $top = $this->report()->json('data.top_products');

        $this->assertSame('Nasi Goreng', $top['gofood'][0]['name']);
        $this->assertSame(3, $top['gofood'][0]['quantity']);
        $this->assertEquals(75000, $top['gofood'][0]['revenue']);
        $this->assertSame('Es Teh', $top['grabfood'][0]['name']);
        $this->assertSame(1, $top['shopeefood'][0]['quantity'], 'Order dengan dua baris pembayaran tetap dihitung sekali');
        $this->assertSame([], $top['shopeefood'][1] ?? []);
    }

    public function test_empty_period_returns_zeroes(): void
    {
        $data = $this->report()->json('data');

        $this->assertSame(0, $data['summary']['total_orders']);
        $this->assertEquals(0, $data['summary']['online_share_percentage']);
        $this->assertCount(3, $data['platforms']);
        $this->assertSame([], $data['top_products']['gofood']);
    }
}
