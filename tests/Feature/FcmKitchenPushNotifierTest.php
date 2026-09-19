<?php

namespace Tests\Feature;

use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\Push\FcmClient;
use App\Services\Push\FcmKitchenPushNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class FcmKitchenPushNotifierTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array> pesan FCM yang "terkirim" */
    private array $sent = [];

    private function notifier(): FcmKitchenPushNotifier
    {
        $client = Mockery::mock(FcmClient::class);
        $client->shouldReceive('send')->andReturnUsing(function (array $message) {
            $this->sent[] = $message;
        });

        return new FcmKitchenPushNotifier($client, 'kitchen-orders-test');
    }

    private function makeKitchenOrder(int $items = 2): KitchenOrder
    {
        DB::table('roles')->insert(['id' => 1, 'name' => 'kasir', 'display_name' => 'Kasir']);
        $user = User::factory()->create(['username' => 'kasir-fcm-test']);

        $order = Order::create([
            'order_number' => 'ORD-FCM-' . Str::random(6),
            'id_user' => $user->id,
            'order_type' => 'dine_in',
            'table_number' => '5',
            'status' => 'pending',
            'subtotal' => 0,
            'total_amount' => 0,
            'order_date' => now(),
        ]);

        return KitchenOrder::createFromOrderItems(
            $order,
            array_map(fn ($i) => ['product_name' => "Menu {$i}", 'quantity' => 1], range(1, $items)),
            'bar'
        );
    }

    public function test_order_created_sends_audible_notification_to_topic(): void
    {
        $kitchenOrder = $this->makeKitchenOrder(2);

        $this->notifier()->orderCreated($kitchenOrder);

        $this->assertCount(1, $this->sent);
        $message = $this->sent[0];
        $this->assertSame('kitchen-orders-test', $message['topic']);
        $this->assertSame("Order Baru #{$kitchenOrder->order_number}", $message['notification']['title']);
        $this->assertSame('Meja 5 · 2 item · dari Bar', $message['notification']['body']);
        $this->assertSame('kitchen_order_created', $message['data']['type']);
        $this->assertSame((string) $kitchenOrder->id_kitchen_order, $message['data']['kitchen_order_id']);
        $this->assertSame($kitchenOrder->order_number, $message['data']['order_number']);
        $this->assertSame('high', $message['android']['priority']);
        $this->assertSame('kitchen_orders_v1', $message['android']['notification']['channel_id']);
        $this->assertSame('kitchen_alert', $message['android']['notification']['sound']);
        $this->assertSame("kitchen-order-{$kitchenOrder->id_kitchen_order}", $message['android']['notification']['tag']);
    }

    public function test_all_data_values_are_strings_as_fcm_requires(): void
    {
        $kitchenOrder = $this->makeKitchenOrder(1);

        $this->notifier()->orderCreated($kitchenOrder);

        foreach ($this->sent[0]['data'] as $key => $value) {
            $this->assertIsString($value, "data.{$key} harus string — FCM menolak angka/boolean");
        }
    }

    public function test_second_alert_within_window_is_downgraded_to_silent_data_message(): void
    {
        $kitchenOrder = $this->makeKitchenOrder(1);
        $notifier = $this->notifier();

        $notifier->itemsAdded($kitchenOrder, 1);
        $notifier->itemsAdded($kitchenOrder, 1);

        $this->assertCount(2, $this->sent);
        $this->assertSame("Tambahan Order #{$kitchenOrder->order_number}", $this->sent[0]['notification']['title']);
        $this->assertSame('+1 item · Meja 5', $this->sent[0]['notification']['body']);
        $this->assertArrayNotHasKey('notification', $this->sent[1], 'Push susulan harus senyap');
        $this->assertSame('kitchen_items_added', $this->sent[1]['data']['type']);
        $this->assertSame('high', $this->sent[1]['android']['priority'], 'Sinyal sync tetap harus cepat sampai');
    }

    public function test_alert_for_a_different_order_is_not_muted(): void
    {
        DB::table('roles')->insert(['id' => 1, 'name' => 'kasir', 'display_name' => 'Kasir']);
        $user = User::factory()->create(['username' => 'kasir-fcm-test-2']);
        $makeOrder = fn () => KitchenOrder::createFromOrderItems(Order::create([
            'order_number' => 'ORD-FCM-' . Str::random(6),
            'id_user' => $user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'subtotal' => 0,
            'total_amount' => 0,
            'order_date' => now(),
        ]), [['product_name' => 'Menu', 'quantity' => 1]]);
        $notifier = $this->notifier();

        $notifier->orderCreated($makeOrder());
        $notifier->orderCreated($makeOrder());

        $this->assertArrayHasKey('notification', $this->sent[0]);
        $this->assertArrayHasKey('notification', $this->sent[1], 'Peredam hanya berlaku per kitchen order');
    }

    public function test_status_changed_is_silent(): void
    {
        $kitchenOrder = $this->makeKitchenOrder(1);

        $this->notifier()->statusChanged($kitchenOrder);

        $this->assertCount(1, $this->sent);
        $this->assertArrayNotHasKey('notification', $this->sent[0]);
        $this->assertSame('kitchen_status_changed', $this->sent[0]['data']['type']);
        $this->assertSame('pending', $this->sent[0]['data']['status']);
        $this->assertSame('normal', $this->sent[0]['android']['priority']);
    }

    public function test_order_without_table_omits_table_label(): void
    {
        $kitchenOrder = $this->makeKitchenOrder(1);
        $kitchenOrder->update(['table_number' => null]);

        $this->notifier()->orderCreated($kitchenOrder->fresh());

        $this->assertSame('1 item · dari Bar', $this->sent[0]['notification']['body']);
    }
}
