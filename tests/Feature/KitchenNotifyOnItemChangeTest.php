<?php

namespace Tests\Feature;

use App\Contracts\KitchenPushNotifier;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeKitchenPushNotifier;
use Tests\TestCase;

/**
 * Dapur harus tahu setiap porsi yang ditambahkan ke bill terbuka:
 * - tambah item dari layar HP (dulu mengirim available_kitchen=false),
 * - tambah JUMLAH item yang sudah ada (dulu tidak memberi tahu dapur sama
 *   sekali, Kitchen Display tetap menampilkan jumlah lama).
 */
class KitchenNotifyOnItemChangeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FakeKitchenPushNotifier $push;
    private Product $kitchenProduct;
    private Product $barProduct;
    private Order $order;
    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDefer();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        $this->user = User::factory()->create(['username' => 'kitchen-notify-test']);
        Sanctum::actingAs($this->user);

        $this->push = new FakeKitchenPushNotifier();
        $this->app->instance(KitchenPushNotifier::class, $this->push);

        $this->categoryId = DB::table('categories')->insertGetId([
            'name' => 'Menu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->kitchenProduct = $this->makeProduct('Nasi Goreng', 25000, kitchen: true);
        $this->barProduct = $this->makeProduct('Blue Lagoon', 28000, kitchen: false);

        $this->order = Order::create([
            'order_number' => 'ORD-KNOTIF-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'table_number' => '3',
            'subtotal' => 0,
            'total_amount' => 0,
            'order_date' => now(),
        ]);
    }

    private function makeProduct(string $name, int $price, bool $kitchen): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'description' => 'Test',
            'price' => $price,
            'unit_price' => $price,
            'category_id' => $this->categoryId,
            'status' => 'published',
            'active' => true,
            'available_in_kitchen' => $kitchen,
            'available_in_bar' => ! $kitchen,
        ]);

        // addItem memeriksa stok bahan resep.
        $itemId = DB::table('items')->insertGetId([
            'item_code' => 'ITM-' . Str::random(6),
            'name' => "Bahan {$name}",
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
            'product_id' => $product->id_product,
            'item_id' => $itemId,
            'quantity_needed' => 1,
            'unit' => 'porsi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $product;
    }

    private function addItem(Product $product, int $quantity, array $extra = []): OrderItem
    {
        $this->postJson("/api/pos/orders/{$this->order->id_order}/items", array_merge([
            'id_product' => $product->id_product,
            'item_type' => 'product',
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'cashier_id' => $this->user->id,
        ], $extra))->assertSuccessful();

        return OrderItem::where('id_order', $this->order->id_order)
            ->where('id_product', $product->id_product)
            ->latest('id_order_item')
            ->firstOrFail();
    }

    private function changeQuantity(OrderItem $orderItem, int $quantity): void
    {
        // Persis seperti aplikasi: hanya quantity (+ update_order_total).
        $this->putJson(
            "/api/pos/orders/{$this->order->id_order}/items/{$orderItem->id_order_item}",
            ['quantity' => $quantity, 'update_order_total' => true]
        )->assertSuccessful();
    }

    private function kitchenQuantityFor(OrderItem $orderItem): int
    {
        return (int) KitchenOrderItem::where('id_order_item', $orderItem->id_order_item)->sum('quantity');
    }

    public function test_add_item_without_kitchen_flag_uses_product_setting(): void
    {
        // Layar HP tidak tahu pengaturan dapur produk — field tidak dikirim,
        // jadi server harus memakai pengaturan produk.
        $orderItem = $this->addItem($this->kitchenProduct, 1);

        $this->assertSame(1, $this->kitchenQuantityFor($orderItem));
        $this->assertCount(1, $this->push->calls);
        $this->assertSame('orderCreated', $this->push->calls[0][0]);
    }

    public function test_increasing_quantity_sends_extra_portions_to_pending_kitchen_order(): void
    {
        $orderItem = $this->addItem($this->kitchenProduct, 1);
        $kitchenOrder = KitchenOrder::where('id_order', $this->order->id_order)->firstOrFail();
        $this->push->calls = [];

        $this->changeQuantity($orderItem, 3);

        $this->assertSame(3, $this->kitchenQuantityFor($orderItem), 'Dapur harus tahu total 3 porsi');
        $this->assertSame(
            [['itemsAdded', $kitchenOrder->id_kitchen_order, 1]],
            $this->push->calls,
            'Porsi tambahan masuk ke order dapur yang masih menunggu, dengan push "Tambahan Order"'
        );
        $this->assertSame(
            2,
            (int) KitchenOrderItem::where('id_order_item', $orderItem->id_order_item)
                ->latest('id_kitchen_order_item')
                ->value('quantity'),
            'Baris tambahan berisi selisih porsinya saja'
        );
    }

    public function test_increasing_quantity_after_kitchen_started_creates_new_kitchen_order(): void
    {
        $orderItem = $this->addItem($this->kitchenProduct, 1);
        $first = KitchenOrder::where('id_order', $this->order->id_order)->firstOrFail();
        $first->update(['status' => KitchenOrder::STATUS_IN_PROGRESS]);
        $this->push->calls = [];

        $this->changeQuantity($orderItem, 2);

        $second = KitchenOrder::where('id_order', $this->order->id_order)
            ->where('id_kitchen_order', '!=', $first->id_kitchen_order)
            ->firstOrFail();
        $this->assertSame([['orderCreated', $second->id_kitchen_order, null]], $this->push->calls);
        $this->assertSame(1, (int) $second->items()->sum('quantity'));
    }

    public function test_decreasing_quantity_does_not_notify_kitchen(): void
    {
        $orderItem = $this->addItem($this->kitchenProduct, 3);
        $this->push->calls = [];

        $this->changeQuantity($orderItem, 1);

        $this->assertSame([], $this->push->calls);
        $this->assertSame(3, $this->kitchenQuantityFor($orderItem));
    }

    public function test_quantity_change_on_non_kitchen_item_does_not_notify(): void
    {
        $orderItem = $this->addItem($this->barProduct, 1);
        $this->push->calls = [];

        $this->changeQuantity($orderItem, 2);

        $this->assertSame([], $this->push->calls);
        $this->assertDatabaseCount('kitchen_orders', 0);
    }

    public function test_quantity_change_keeps_item_notes_and_sends_them_to_kitchen(): void
    {
        $orderItem = $this->addItem($this->kitchenProduct, 1, ['notes' => 'tidak pedas']);

        $this->changeQuantity($orderItem, 2);

        $this->assertSame('tidak pedas', $orderItem->fresh()->notes, 'Catatan item tidak boleh hilang saat jumlah diubah');
        $this->assertSame(
            'tidak pedas',
            KitchenOrderItem::where('id_order_item', $orderItem->id_order_item)
                ->latest('id_kitchen_order_item')
                ->value('notes'),
            'Tiket dapur untuk porsi tambahan ikut membawa catatan'
        );
    }

    public function test_items_for_package_order_item_lists_only_kitchen_products(): void
    {
        $package = Package::create([
            'name' => 'Paket Hemat',
            'slug' => 'paket-hemat-' . Str::random(6),
            'package_price' => 45000,
            'regular_price' => 53000,
            'is_active' => true,
        ]);
        PackageItem::create(['id_package' => $package->id_package, 'id_product' => $this->kitchenProduct->id_product, 'quantity' => 1]);
        PackageItem::create(['id_package' => $package->id_package, 'id_product' => $this->barProduct->id_product, 'quantity' => 2]);

        $orderItem = OrderItem::create([
            'id_order' => $this->order->id_order,
            'id_package' => $package->id_package,
            'item_type' => 'package',
            'item_name' => $package->name,
            'item_sku' => 'PKG-1',
            'quantity' => 1,
            'unit_price' => 45000,
            'total_price' => 45000,
        ]);

        $items = KitchenOrder::itemsForOrderItem($orderItem, 2);

        $this->assertCount(1, $items);
        $this->assertSame('Nasi Goreng (dari Paket Hemat)', $items[0]['product_name']);
        $this->assertSame(2, $items[0]['quantity']);
        $this->assertSame($orderItem->id_order_item, $items[0]['id_order_item']);
    }
}
