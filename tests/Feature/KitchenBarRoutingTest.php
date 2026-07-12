<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KitchenBarRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $category;

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

        $this->category = Category::create([
            'name' => 'Beverages',
            'active' => true,
        ]);
    }

    private function makeProduct(string $name, float $price, bool $kitchen, bool $bar): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $name,
            'price' => $price,
            'category_id' => $this->category->id_category,
            'available_in_kitchen' => $kitchen,
            'available_in_bar' => $bar,
        ]);

        Inventory::create([
            'id_item' => $product->id_product,
            'current_stock' => 1000,
        ]);

        return $product;
    }

    private function makeOrderWith(array $products): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-BAR-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'subtotal' => 0,
            'total_amount' => 0,
            'order_date' => now(),
        ]);

        foreach ($products as $product) {
            OrderItem::create([
                'id_order' => $order->id_order,
                'id_product' => $product->id_product,
                'item_type' => 'product',
                'item_name' => $product->name,
                'item_sku' => $product->sku ?? 'NO-SKU',
                'quantity' => 1,
                'unit_price' => $product->price,
                'total_price' => $product->price,
            ]);
        }

        return $order;
    }

    public function test_kitchen_orders_endpoint_includes_bar_items_with_flags(): void
    {
        $barDrink = $this->makeProduct('Es Kopi Signature', 25000, false, true);
        $kitchenFood = $this->makeProduct('Nasi Goreng Kampung', 30000, true, false);

        $order = $this->makeOrderWith([$barDrink, $kitchenFood]);

        KitchenOrder::create([
            'id_order' => $order->id_order,
            'order_number' => $order->order_number,
            'order_type' => 'dine_in',
            'customer_name' => 'Walk-in Customer',
            'status' => 'pending',
            'created_by_station' => 'kasir',
        ]);

        $response = $this->getJson('/api/kitchen/orders');
        $response->assertOk();

        $items = collect($response->json('data.0.items'));
        $this->assertCount(2, $items, 'Both kitchen and bar items must be returned');

        $barItem = $items->firstWhere('product_name', 'Es Kopi Signature');
        $this->assertNotNull($barItem, 'Bar-only item must be included');
        $this->assertTrue($barItem['available_bar']);
        $this->assertFalse($barItem['available_kitchen']);

        // Station filters return only that station's items
        $barOnly = collect($this->getJson('/api/kitchen/orders?station=bar')->json('data.0.items'));
        $this->assertCount(1, $barOnly);
        $this->assertSame('Es Kopi Signature', $barOnly[0]['product_name']);

        $kitchenOnly = collect($this->getJson('/api/kitchen/orders?station=kitchen')->json('data.0.items'));
        $this->assertCount(1, $kitchenOnly);
        $this->assertSame('Nasi Goreng Kampung', $kitchenOnly[0]['product_name']);
    }

    public function test_edit_order_creates_kitchen_order_for_new_bar_item(): void
    {
        $plainProduct = $this->makeProduct('Merchandise Mug', 50000, false, false);
        $barDrink = $this->makeProduct('Mojito Mocktail', 35000, false, true);

        $order = $this->makeOrderWith([$plainProduct]);

        $response = $this->putJson("/api/pos/orders/{$order->id_order}", [
            'items' => [
                ['id_product' => $plainProduct->id_product, 'quantity' => 1, 'unit_price' => 50000],
                ['id_product' => $barDrink->id_product, 'quantity' => 2, 'unit_price' => 35000],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('kitchen_orders', [
            'id_order' => $order->id_order,
        ]);
        $this->assertDatabaseHas('kitchen_order_items', [
            'product_name' => 'Mojito Mocktail',
            'quantity' => 2,
        ]);
    }
}
