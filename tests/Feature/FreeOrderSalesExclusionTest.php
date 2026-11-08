<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\DashboardController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Order gratis / owner (diskon yang membuat total bersih Rp 0) tetap tercatat
 * di transaksi, tapi TIDAK boleh dihitung sebagai penjualan. Diskon sebagian
 * tetap penjualan karena customer benar-benar membayar.
 */
class FreeOrderSalesExclusionTest extends TestCase
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

        $this->user = User::factory()->create(['username' => 'kasir-free-test']);
        Sanctum::actingAs($this->user);
    }

    /**
     * @param string $paymentMethod metode bayar order ini
     * @param int    $quantity      jumlah porsi pada satu-satunya baris item
     */
    private function makeOrder(
        float $subtotal,
        float $discount,
        string $paymentMethod = 'cash',
        int $quantity = 1,
        string $status = 'completed',
        bool $withPayment = true,
        ?float $paymentAmount = null,
        ?int $customerId = null,
        ?int $productId = null,
    ): Order {
        $total = $subtotal - $discount;

        $order = Order::create([
            'order_number' => 'ORD-FREE-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => $status,
            'id_customer' => $customerId,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'order_date' => now(),
        ]);

        OrderItem::create([
            'id_order' => $order->id_order,
            'item_type' => 'product',
            'id_product' => $productId,
            'item_name' => 'Item ' . Str::random(4),
            'item_sku' => 'SKU-' . Str::random(5),
            'quantity' => $quantity,
            'unit_price' => $subtotal / $quantity,
            'discount_amount' => $discount,
            'total_price' => $total,
        ]);

        // Order gratis di produksi sering TANPA baris pembayaran sama sekali
        // ($withPayment = false) atau punya pembayaran Rp 0 ($paymentAmount = 0).
        if ($withPayment) {
            Payment::create([
                'id_order' => $order->id_order,
                'payment_number' => 'PAY-' . Str::random(8),
                'payment_method' => $paymentMethod,
                'amount' => $paymentAmount ?? $total,
                'status' => 'paid',
                'payment_date' => now(),
                'processed_by' => $this->user->id,
            ]);
        }

        return $order;
    }

    /**
     * 1 order normal (20.000), 1 diskon sebagian (30.000 - 7.500 = 22.500),
     * 1 order gratis/owner (30.000 - 30.000 = 0, 3 porsi).
     * Penjualan yang benar: 2 order, Rp 42.500, 2 item.
     */
    private function seedNormalPartialAndFree(): Order
    {
        $this->makeOrder(20000, 0);
        $this->makeOrder(30000, 7500);

        return $this->makeOrder(30000, 30000, 'cash', 3);
    }

    public function test_sales_report_excludes_free_orders_but_keeps_partial_discounts(): void
    {
        $this->seedNormalPartialAndFree();

        $summary = $this->getJson('/api/reports/sales')->assertOk()->json('data.summary');

        $this->assertSame(2, (int) $summary['total_orders']);
        $this->assertSame(2, (int) $summary['completed_orders']);
        $this->assertEqualsWithDelta(42500.0, (float) $summary['total_revenue'], 0.01);
        $this->assertEqualsWithDelta(21250.0, (float) $summary['average_order'], 0.01);
        $this->assertSame(2, (int) $summary['total_items'], 'Porsi di order gratis (3) tidak boleh dihitung terjual');
    }

    public function test_sales_report_daily_row_excludes_free_orders(): void
    {
        $this->seedNormalPartialAndFree();

        $daily = collect($this->getJson('/api/reports/sales')->json('data.daily_sales'))
            ->firstWhere('date', now()->format('Y-m-d'));

        $this->assertNotNull($daily);
        $this->assertSame(2, (int) $daily['total_orders']);
        $this->assertSame(2, (int) $daily['completed_orders']);
        $this->assertEqualsWithDelta(21250.0, (float) $daily['average_order'], 0.01);
    }

    public function test_free_order_does_not_appear_in_payment_method_breakdown(): void
    {
        $this->seedNormalPartialAndFree();

        $json = $this->getJson('/api/reports/payment-methods')->assertOk()->json('data');

        $this->assertSame(2, (int) $json['summary']['total_orders']);
        $this->assertEqualsWithDelta(42500.0, (float) $json['summary']['total_amount'], 0.01);
        $this->assertSame(2, (int) $json['data'][0]['order_count']);
    }

    public function test_free_order_does_not_appear_in_order_type_breakdown_or_today_sales(): void
    {
        $this->seedNormalPartialAndFree();

        $types = $this->getJson('/api/reports/order-types')->assertOk()->json('data');
        $this->assertSame(2, (int) $types['summary']['total_orders']);

        $today = $this->getJson('/api/reports/today-sales')->assertOk()->json('data');
        $this->assertSame(2, (int) $today['total_orders']);
        $this->assertEqualsWithDelta(42500.0, (float) $today['total_sales'], 0.01);
        $this->assertEqualsWithDelta(21250.0, (float) $today['avg_order_value'], 0.01);
    }

    /**
     * Endpoint /dashboard/analytics utuh tidak bisa dites di SQLite: bagian
     * analitik pembeliannya memakai kolom yang tidak ada di skema tes (gagal
     * juga tanpa perubahan ini). Jadi bagian penjualannya dipanggil langsung.
     */
    private function callDashboard(string $method): mixed
    {
        $controller = app(DashboardController::class);
        $reflection = new \ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, Carbon::now()->subDays(7)->startOfDay(), Carbon::now()->endOfDay());
    }

    public function test_dashboard_summary_cards_exclude_free_orders(): void
    {
        $this->seedNormalPartialAndFree();

        $summary = $this->callDashboard('getSummaryCards');

        $this->assertSame(2, (int) $summary['today_orders']['value']);
        $this->assertEqualsWithDelta(42500.0, (float) $summary['today_sales']['value'], 0.01);
        $this->assertSame(2, (int) $summary['period_summary']['total_orders']);
        $this->assertEqualsWithDelta(42500.0, (float) $summary['period_summary']['total_sales'], 0.01);
        $this->assertSame(2, (int) $summary['payment_methods_today']['data'][0]['transaction_count']);
        $this->assertSame(2, (int) $summary['order_types_today']['data'][0]['order_count']);
    }

    public function test_dashboard_sales_analytics_exclude_free_orders(): void
    {
        $this->seedNormalPartialAndFree();

        $sales = $this->callDashboard('getSalesAnalytics');

        $this->assertSame(2, (int) $sales['daily_trend']->first()->order_count);
        $this->assertEqualsWithDelta(42500.0, (float) $sales['daily_trend']->first()->total_revenue, 0.01);
        $this->assertSame(2, (int) $sales['by_type']->first()->count);
    }

    public function test_pos_stats_exclude_free_orders(): void
    {
        $this->seedNormalPartialAndFree();

        $stats = $this->getJson('/api/pos/stats')->assertOk()->json('data');

        $this->assertSame(2, (int) $stats['today_orders']);
        $this->assertEqualsWithDelta(42500.0, (float) $stats['today_sales'], 0.01);
        $this->assertSame(2, (int) $stats['completed_orders_today']);
        $this->assertSame(2, (int) collect($stats['hourly_sales_today'])->sum('orders'));
    }

    public function test_free_online_order_is_not_counted_in_online_sales(): void
    {
        $this->makeOrder(10000, 0, 'gofood');
        $this->makeOrder(25000, 25000, 'gofood'); // gratis lewat GoFood

        $today = now()->format('Y-m-d');
        $summary = $this->getJson("/api/reports/online-sales?start_date={$today}&end_date={$today}")
            ->assertOk()
            ->json('data.summary');

        $this->assertSame(1, (int) $summary['total_orders']);
        $this->assertEqualsWithDelta(10000.0, (float) $summary['total_revenue'], 0.01);
        $this->assertEqualsWithDelta(10000.0, (float) $summary['all_sales_revenue'], 0.01);
    }

    public function test_free_order_still_listed_in_transaction_history_cache(): void
    {
        $free = $this->seedNormalPartialAndFree();

        $cached = DB::table('report_transaction_cache')
            ->where('id_order', $free->id_order)
            ->first();

        $this->assertNotNull($cached, 'Order gratis harus tetap ada di riwayat transaksi');
        $this->assertSame('completed', $cached->status);
        $this->assertEqualsWithDelta(0.0, (float) $cached->total_amount, 0.01);
    }

    public function test_daily_summary_table_excludes_free_orders(): void
    {
        $this->seedNormalPartialAndFree();

        $row = DB::table('report_sales_daily')
            ->where('report_date', now()->format('Y-m-d'))
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(2, (int) $row->total_orders);
        $this->assertSame(2, (int) $row->completed_orders);
        $this->assertEqualsWithDelta(42500.0, (float) $row->total_revenue, 0.01);
        $this->assertEqualsWithDelta(7500.0, (float) $row->total_discount, 0.01, 'Hanya diskon dari penjualan nyata');
    }

    public function test_zero_total_order_without_discount_is_not_treated_as_free(): void
    {
        // Pending/draft Rp 0 tanpa diskon bukan "gratis": aturan lama tetap
        // berlaku (ikut total_orders, tidak ikut completed/AOV).
        $this->makeOrder(20000, 0);
        Order::create([
            'order_number' => 'ORD-FREE-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'subtotal' => 0,
            'total_amount' => 0,
            'order_date' => now(),
        ]);

        $summary = $this->getJson('/api/reports/sales')->assertOk()->json('data.summary');

        $this->assertSame(2, (int) $summary['total_orders']);
        $this->assertSame(1, (int) $summary['completed_orders']);
    }

    /**
     * Bentuk order PERSIS seperti data produksi (60 hari terakhir):
     *  - gratis dengan pembayaran QRIS Rp 0 (contoh ORD202611070069)
     *  - gratis TANPA baris pembayaran sama sekali (9 dari 12 contoh)
     *  - diskon sebagian 50% dibayar QRIS (Rp 150.000 -> Rp 75.000), tetap penjualan
     */
    public function test_production_shaped_free_orders_are_excluded_everywhere(): void
    {
        $this->makeOrder(40000, 0, 'cash');                                         // normal
        $this->makeOrder(150000, 75000, 'qris');                                    // diskon 50%: tetap penjualan
        $this->makeOrder(68000, 68000, 'qris', 2, paymentAmount: 0);                // gratis, QRIS Rp 0
        $this->makeOrder(164000, 164000, 'cash', 4, withPayment: false);            // gratis, tanpa pembayaran

        // Laporan penjualan (hitung langsung dari orders, tanpa join pembayaran)
        $summary = $this->getJson('/api/reports/sales')->assertOk()->json('data.summary');
        $this->assertSame(2, (int) $summary['total_orders']);
        $this->assertSame(2, (int) $summary['completed_orders']);
        $this->assertEqualsWithDelta(115000.0, (float) $summary['total_revenue'], 0.01);
        $this->assertEqualsWithDelta(57500.0, (float) $summary['average_order'], 0.01);
        $this->assertSame(2, (int) $summary['total_items'], 'Porsi order gratis (2 + 4) tidak boleh ikut terjual');

        // Metode bayar di laporan penjualan: baris QRIS Rp 0 tidak boleh ikut menambah jumlah transaksi
        $methods = collect($this->getJson('/api/reports/sales')->json('data.payment_methods'))->keyBy('method');
        $this->assertSame(1, (int) $methods['qris']['order_count']);
        $this->assertSame(1, (int) $methods['cash']['order_count']);

        // Endpoint berbasis join pembayaran
        $today = $this->getJson('/api/reports/today-sales')->assertOk()->json('data');
        $this->assertSame(2, (int) $today['total_orders']);
        $this->assertEqualsWithDelta(115000.0, (float) $today['total_sales'], 0.01);

        $payments = collect($this->getJson('/api/reports/payment-methods')->assertOk()->json('data.data'))->keyBy('payment_method');
        $this->assertSame(1, (int) $payments['qris']['order_count']);
        $this->assertEqualsWithDelta(75000.0, (float) $payments['qris']['total_amount'], 0.01);

        // Kartu dashboard
        $cards = $this->callDashboard('getSummaryCards');
        $this->assertSame(2, (int) $cards['today_orders']['value']);
        $this->assertEqualsWithDelta(115000.0, (float) $cards['today_sales']['value'], 0.01);
        $qris = collect($cards['payment_methods_today']['data'])->firstWhere('payment_method', 'qris');
        $this->assertSame(1, (int) $qris['transaction_count']);

        // Semuanya tetap ada di riwayat transaksi
        $this->assertSame(4, DB::table('report_transaction_cache')->count());
        $this->assertSame(4, Order::count());
    }

    public function test_free_orders_are_excluded_from_product_and_category_sales(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Minuman',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $kopi = Product::create([
            'name' => 'Kopi Susu',
            'slug' => 'kopi-susu-' . Str::random(6),
            'description' => 'Test',
            'price' => 25000,
            'unit_price' => 25000,
            'category_id' => $categoryId,
            'status' => 'published',
            'active' => true,
        ]);

        $this->makeOrder(50000, 0, quantity: 2, productId: $kopi->id_product);       // 2 porsi, bayar penuh
        $this->makeOrder(25000, 12500, quantity: 1, productId: $kopi->id_product);   // 1 porsi, diskon 50%
        $this->makeOrder(150000, 150000, quantity: 6, productId: $kopi->id_product); // 6 porsi GRATIS (owner)

        $report = $this->getJson('/api/reports/sales')->assertOk()->json('data');

        $top = collect($report['top_products'])->firstWhere('name', 'Kopi Susu');
        $this->assertSame(3, (int) $top['quantity'], '6 porsi gratis tidak boleh ikut terjual (2 + 1 saja)');

        $all = collect($report['all_products'])->firstWhere('name', 'Kopi Susu');
        $this->assertSame(3, (int) $all['quantity']);

        $category = collect($report['category_performance'])->firstWhere('category', 'Minuman');
        $this->assertSame(3, (int) $category['quantity_sold']);
        $this->assertSame(2, (int) $category['orders_count']);

        // Detail per produk harus cocok dengan angka di tabel produk
        $detail = $this->getJson('/api/reports/sales/product-detail?product_id=' . $kopi->id_product)
            ->assertOk()->json('data.summary');
        $this->assertSame(2, (int) $detail['transaction_count']);
        $this->assertEqualsWithDelta(3.0, (float) $detail['total_quantity'], 0.01);
    }

    public function test_free_orders_do_not_count_as_customer_visits_in_sales_report(): void
    {
        $owner = Customer::create(['customer_code' => 'CUST-OWNER', 'name' => 'Owner']);
        $guest = Customer::create(['customer_code' => 'CUST-GUEST', 'name' => 'Tamu']);

        // Owner: 3 order gratis -> bukan penjualan, bukan "pelanggan kembali"
        $this->makeOrder(30000, 30000, customerId: $owner->id_customer);
        $this->makeOrder(30000, 30000, customerId: $owner->id_customer);
        $this->makeOrder(30000, 30000, customerId: $owner->id_customer);
        // Tamu: 1 order bayar
        $this->makeOrder(20000, 0, customerId: $guest->id_customer);

        $report = $this->getJson('/api/reports/sales')->assertOk()->json('data');

        $names = collect($report['top_customers'])->pluck('name');
        $this->assertTrue($names->contains('Tamu'));
        $this->assertFalse($names->contains('Owner'), 'Owner yang hanya punya order gratis tidak boleh jadi top customer');
        $this->assertSame(0, (int) $report['customer_behavior']['returning_customers']);
        $this->assertSame(1, (int) $report['customer_behavior']['one_time_customers']);
    }
}
