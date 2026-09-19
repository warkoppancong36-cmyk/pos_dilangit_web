<?php

namespace Tests\Feature;

use App\Contracts\KitchenPushNotifier;
use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeKitchenPushNotifier;
use Tests\TestCase;

class KitchenPushTriggerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FakeKitchenPushNotifier $push;

    protected function setUp(): void
    {
        parent::setUp();

        // defer() dijalankan langsung supaya bisa di-assert tanpa siklus HTTP penuh
        $this->withoutDefer();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        $this->user = User::factory()->create(['username' => 'kasir-push-test']);
        Sanctum::actingAs($this->user);

        $this->push = new FakeKitchenPushNotifier();
        $this->app->instance(KitchenPushNotifier::class, $this->push);
    }

    private function makeOrder(): Order
    {
        return Order::create([
            'order_number' => 'ORD-PUSH-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'subtotal' => 0,
            'total_amount' => 0,
            'order_date' => now(),
        ]);
    }

    private function items(int $count): array
    {
        return array_map(fn ($i) => ['product_name' => "Nasi Goreng {$i}", 'quantity' => 1], range(1, $count));
    }

    public function test_creating_kitchen_order_sends_order_created_once(): void
    {
        $kitchenOrder = KitchenOrder::createFromOrderItems($this->makeOrder(), $this->items(2));

        $this->assertSame([['orderCreated', $kitchenOrder->id_kitchen_order, null]], $this->push->calls);
    }

    public function test_merging_items_into_pending_order_sends_items_added(): void
    {
        $order = $this->makeOrder();
        $first = KitchenOrder::findOrCreateForOrder($order, $this->items(1));
        $this->push->calls = [];

        $second = KitchenOrder::findOrCreateForOrder($order, $this->items(2));

        $this->assertSame($first->id_kitchen_order, $second->id_kitchen_order, 'Harus merge ke kitchen order pending yang sama');
        $this->assertSame([['itemsAdded', $first->id_kitchen_order, 2]], $this->push->calls);
    }

    public function test_push_waits_for_commit(): void
    {
        $order = $this->makeOrder();

        DB::transaction(function () use ($order) {
            KitchenOrder::createFromOrderItems($order, $this->items(1));
            $this->assertSame([], $this->push->calls, 'Push tidak boleh terkirim sebelum commit');
        });

        $this->assertCount(1, $this->push->calls);
    }

    public function test_rolled_back_transaction_sends_nothing(): void
    {
        $order = $this->makeOrder();

        DB::beginTransaction();
        KitchenOrder::createFromOrderItems($order, $this->items(1));
        DB::rollBack();

        $this->assertSame([], $this->push->calls);
    }

    public function test_status_update_endpoint_sends_status_changed(): void
    {
        $kitchenOrder = KitchenOrder::createFromOrderItems($this->makeOrder(), $this->items(1));
        $this->push->calls = [];

        $this->putJson("/api/kitchen/orders/{$kitchenOrder->id_kitchen_order}/status", ['status' => 'completed'])
            ->assertOk();

        $this->assertSame([['statusChanged', $kitchenOrder->id_kitchen_order, 'completed']], $this->push->calls);
    }

    public function test_create_endpoint_sends_exactly_one_push(): void
    {
        $order = $this->makeOrder();

        $this->postJson('/api/kitchen/orders', [
            'order_id' => $order->id_order,
            'items' => $this->items(3),
            'station' => 'bar',
        ])->assertCreated();

        $this->assertCount(1, $this->push->calls);
        $this->assertSame('orderCreated', $this->push->calls[0][0]);
    }

    public function test_push_failure_never_breaks_the_order(): void
    {
        $this->app->bind(KitchenPushNotifier::class, fn () => new class implements KitchenPushNotifier {
            public function orderCreated(KitchenOrder $kitchenOrder): void { throw new \RuntimeException('FCM down'); }
            public function itemsAdded(KitchenOrder $kitchenOrder, int $newItemsCount): void { throw new \RuntimeException('FCM down'); }
            public function statusChanged(KitchenOrder $kitchenOrder): void { throw new \RuntimeException('FCM down'); }
        });
        $order = $this->makeOrder();

        $this->postJson('/api/kitchen/orders', [
            'order_id' => $order->id_order,
            'items' => $this->items(1),
        ])->assertCreated();

        $this->assertDatabaseCount('kitchen_orders', 1);
    }
}
