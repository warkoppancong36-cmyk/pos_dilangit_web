<?php

namespace Tests\Feature;

use App\Contracts\KitchenPushNotifier;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeKitchenPushNotifier;
use Tests\TestCase;

/**
 * Split bill adalah urusan tagihan: makanan sudah dikirim ke dapur lewat order asli,
 * jadi dapur tidak boleh menerima order / checker / push kedua kalinya.
 */
class SplitBillKitchenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FakeKitchenPushNotifier $push;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDefer();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        $this->user = User::factory()->create(['username' => 'kasir-split-test']);
        Sanctum::actingAs($this->user);

        $this->push = new FakeKitchenPushNotifier();
        $this->app->instance(KitchenPushNotifier::class, $this->push);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Makanan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->product = Product::create([
            'name' => 'Nasi Goreng',
            'slug' => 'nasi-goreng-' . Str::random(6),
            'description' => 'Test',
            'price' => 25000,
            'category_id' => $categoryId,
            'status' => 'published',
            'active' => true,
            'available_in_kitchen' => true,
        ]);

        // processDirectPayment menolak produk tanpa stok — beri resep 1 bahan dengan stok cukup
        $itemId = DB::table('items')->insertGetId([
            'item_code' => 'ITM-' . Str::random(6),
            'name' => 'Beras',
            'unit' => 'porsi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory')->insert([
            'id_item' => $itemId,
            'current_stock' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_items')->insert([
            'product_id' => $this->product->id_product,
            'item_id' => $itemId,
            'quantity_needed' => 1,
            'unit' => 'porsi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentPayload(array $overrides = []): array
    {
        return array_merge([
            'cart_items' => [[
                'product_id' => $this->product->id_product,
                'item_type' => 'product',
                'quantity' => 1,
                'unit_price' => 25000,
                'subtotal' => 25000,
            ]],
            'order_type' => 'dine_in',
            'payment_method' => 'tunai',
            'subtotal_amount' => 25000,
            'total_amount' => 25000,
            'paid_amount' => 25000,
            'change_amount' => 0,
            'cashier_id' => $this->user->id,
        ], $overrides);
    }

    /** Order asli yang belum dibayar, dengan satu item yang sudah tampil di Kitchen Display. */
    private function makeOpenOrderInKitchen(): array
    {
        $order = Order::create([
            'order_number' => 'ORD-SPLIT-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'subtotal' => 25000,
            'total_amount' => 25000,
            'order_date' => now(),
        ]);

        $orderItem = OrderItem::create([
            'id_order' => $order->id_order,
            'id_product' => $this->product->id_product,
            'item_type' => 'product',
            'item_name' => 'Nasi Goreng',
            'item_sku' => 'SKU-NG',
            'quantity' => 1,
            'unit_price' => 25000,
            'total_price' => 25000,
        ]);

        $kitchenOrder = KitchenOrder::createFromOrderItems($order, [[
            'id_order_item' => $orderItem->id_order_item,
            'product_name' => 'Nasi Goreng',
            'quantity' => 1,
        ]]);

        $this->push->calls = [];

        return [$order, $orderItem, $kitchenOrder];
    }

    public function test_normal_direct_payment_still_creates_kitchen_order(): void
    {
        $this->postJson('/api/pos/process-direct-payment', $this->paymentPayload())
            ->assertSuccessful();

        $this->assertDatabaseCount('kitchen_orders', 1);
        $this->assertCount(1, $this->push->calls);
    }

    public function test_split_bill_payment_does_not_create_kitchen_order_or_push(): void
    {
        [$order] = $this->makeOpenOrderInKitchen();

        $this->postJson('/api/pos/process-direct-payment', $this->paymentPayload([
            'original_order_id' => $order->id_order,
            'notes' => "Split Bill dari {$order->order_number}",
        ]))->assertSuccessful();

        $this->assertDatabaseCount('kitchen_orders', 1); // hanya milik order asli
        $this->assertSame([], $this->push->calls, 'Split bill tidak boleh membunyikan dapur');
    }

    public function test_removing_item_for_split_bill_keeps_it_on_kitchen_display(): void
    {
        [$order, $orderItem, $kitchenOrder] = $this->makeOpenOrderInKitchen();

        $this->deleteJson("/api/pos/orders/{$order->id_order}/items/{$orderItem->id_order_item}?split_bill=1")
            ->assertSuccessful();

        $this->assertDatabaseMissing('order_items', ['id_order_item' => $orderItem->id_order_item]);
        $this->assertSame(
            1,
            KitchenOrderItem::where('id_kitchen_order', $kitchenOrder->id_kitchen_order)->count(),
            'Item yang sedang dimasak tidak boleh hilang dari Kitchen Display karena split bill'
        );
    }

    public function test_removing_item_without_split_flag_still_clears_pending_kitchen_item(): void
    {
        [$order, $orderItem, $kitchenOrder] = $this->makeOpenOrderInKitchen();

        $this->deleteJson("/api/pos/orders/{$order->id_order}/items/{$orderItem->id_order_item}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('kitchen_orders', ['id_kitchen_order' => $kitchenOrder->id_kitchen_order]);
    }
}
