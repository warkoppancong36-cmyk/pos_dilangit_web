<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderHistorySearchTest extends TestCase
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

    private function makeOrderWithProduct(string $orderNumber, string $productName, ?string $walkInName = null): Order
    {
        $category = Category::firstOrCreate(['name' => 'Menu'], ['active' => true]);

        $product = Product::create([
            'name' => $productName,
            'slug' => Str::slug($productName) . '-' . Str::random(4),
            'description' => $productName,
            'price' => 10000,
            'category_id' => $category->id_category,
        ]);

        $order = Order::create([
            'order_number' => $orderNumber,
            'id_user' => $this->user->id,
            'order_type' => 'takeaway',
            'status' => 'completed',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'order_date' => now(),
            'customer_info' => $walkInName ? ['name' => $walkInName] : null,
        ]);

        OrderItem::create([
            'id_order' => $order->id_order,
            'id_product' => $product->id_product,
            'item_type' => 'product',
            'item_name' => $product->name,
            'item_sku' => 'SKU-' . Str::random(4),
            'quantity' => 1,
            'unit_price' => 10000,
            'total_price' => 10000,
        ]);

        return $order;
    }

    public function test_history_can_be_searched_by_product_name(): void
    {
        $this->makeOrderWithProduct('ORD-SEARCH-001', 'Americano Special');
        $this->makeOrderWithProduct('ORD-SEARCH-002', 'Nasi Goreng Kampung');

        $response = $this->getJson('/api/pos/orders/history?search=Americano');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('ORD-SEARCH-001', $content, 'Order containing the searched product must be found');
        $this->assertStringNotContainsString('ORD-SEARCH-002', $content, 'Orders without the searched product must be excluded');
    }

    public function test_history_can_be_searched_by_walk_in_customer_name(): void
    {
        $this->makeOrderWithProduct('ORD-SEARCH-003', 'Ice Tea', 'Budi Santoso');
        $this->makeOrderWithProduct('ORD-SEARCH-004', 'Ice Tea', 'Siti Aminah');

        $response = $this->getJson('/api/pos/orders/history?search=Budi');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('ORD-SEARCH-003', $content);
        $this->assertStringNotContainsString('ORD-SEARCH-004', $content);
    }
}
