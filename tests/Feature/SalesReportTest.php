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

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        $this->user = User::factory()->create(['username' => 'kasir-test']);
        Sanctum::actingAs($this->user);
    }

    private function makeOrder(string $status, float $total): Order
    {
        return Order::create([
            'order_number' => 'ORD-RPT-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => $status,
            'subtotal' => $total,
            'total_amount' => $total,
            'order_date' => now(),
        ]);
    }

    public function test_average_order_only_counts_completed_orders(): void
    {
        $this->makeOrder('completed', 20000);
        $this->makeOrder('completed', 10000);
        // Two-step orders start as pending with Rp 0 — they must NOT drag AOV down
        $this->makeOrder('pending', 0);

        $response = $this->getJson('/api/reports/sales');

        $response->assertOk();
        $summary = $response->json('data.summary');

        $this->assertSame(2, (int) $summary['completed_orders']);
        $this->assertSame(
            15000.0,
            (float) $summary['average_order'],
            'AOV must be completed revenue / completed orders (30000/2), not dragged down by pending Rp 0 orders'
        );
    }

    public function test_daily_sales_includes_order_count_and_aov(): void
    {
        $this->makeOrder('completed', 20000);
        $this->makeOrder('completed', 10000);
        $this->makeOrder('pending', 5000);

        $response = $this->getJson('/api/reports/sales');

        $response->assertOk();
        $daily = collect($response->json('data.daily_sales'))
            ->firstWhere('date', now()->format('Y-m-d'));

        $this->assertNotNull($daily, 'Daily sales row for today must exist');
        $this->assertSame(3, (int) $daily['total_orders']);
        $this->assertSame(2, (int) $daily['completed_orders']);
        $this->assertSame(15000.0, (float) $daily['average_order']);
    }

    public function test_report_cache_contains_items_created_after_the_order(): void
    {
        // processDirectPayment creates the order FIRST (status already
        // completed), then inserts the items — the cache must still end up
        // with the full item list
        $order = $this->makeOrder('completed', 65000);

        OrderItem::create([
            'id_order' => $order->id_order,
            'item_type' => 'product',
            'item_name' => 'Americano Special',
            'item_sku' => 'SKU-AMR',
            'quantity' => 1,
            'unit_price' => 25000,
            'total_price' => 25000,
        ]);

        OrderItem::create([
            'id_order' => $order->id_order,
            'item_type' => 'package',
            'item_name' => 'Breakfast Trio',
            'package_name' => 'Breakfast Trio',
            'item_sku' => 'PKG-BT',
            'quantity' => 1,
            'unit_price' => 40000,
            'total_price' => 40000,
        ]);

        $cached = DB::table('report_transaction_cache')
            ->where('id_order', $order->id_order)
            ->first();

        $this->assertNotNull($cached, 'Report cache row must exist');
        $this->assertSame(2, (int) $cached->items_count, 'All items created after the order must be cached');

        $names = collect(json_decode($cached->items_detail, true))->pluck('name');
        $this->assertTrue($names->contains('Americano Special'));
        $this->assertTrue(
            $names->contains('Breakfast Trio'),
            'Package items must appear with their real name, not "Unknown"'
        );
    }

    public function test_update_cache_command_backfills_stale_rows(): void
    {
        $order = $this->makeOrder('completed', 25000);

        OrderItem::create([
            'id_order' => $order->id_order,
            'item_type' => 'product',
            'item_name' => 'Kwetiau Dilangit',
            'item_sku' => 'SKU-KWD',
            'quantity' => 1,
            'unit_price' => 25000,
            'total_price' => 25000,
        ]);

        // Simulate a stale row left behind by the old observer (empty items)
        DB::table('report_transaction_cache')
            ->where('id_order', $order->id_order)
            ->update(['items_count' => 0, 'items_detail' => '[]']);

        $this->artisan('report:update-cache', ['--all' => true, '--force' => true])
            ->assertExitCode(0);

        $cached = DB::table('report_transaction_cache')
            ->where('id_order', $order->id_order)
            ->first();

        $this->assertNotNull($cached);
        $this->assertSame(1, (int) $cached->items_count);
        $this->assertStringContainsString('Kwetiau Dilangit', $cached->items_detail);
    }
}
