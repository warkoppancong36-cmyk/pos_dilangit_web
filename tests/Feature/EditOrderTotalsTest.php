<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EditOrderTotalsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        $this->user = User::factory()->create(['username' => 'kasir-test']);
        Sanctum::actingAs($this->user);

        $this->category = Category::create([
            'name' => 'Coffee',
            'active' => true,
        ]);
    }

    private function makeProduct(string $name, float $price): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $name,
            'price' => $price,
            'category_id' => $this->category->id_category,
            'available_in_kitchen' => false,
            'available_in_bar' => false,
        ]);

        // Direct inventory so calculateProductAvailability() passes for
        // products without a recipe (it looks up Inventory by id_item = id_product).
        Inventory::create([
            'id_item' => $product->id_product,
            'current_stock' => 1000,
        ]);

        return $product;
    }

    private function makeOrder(array $items, array $attributes = []): Order
    {
        $subtotal = collect($items)->sum('total_price');

        $order = Order::create(array_merge([
            'order_number' => 'ORD-TEST-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'subtotal' => $subtotal,
            'total_amount' => $subtotal,
            'order_date' => now(),
        ], $attributes));

        foreach ($items as $item) {
            OrderItem::create(array_merge(['id_order' => $order->id_order], $item));
        }

        return $order;
    }

    public function test_editing_items_recalculates_order_totals(): void
    {
        $americano = $this->makeProduct('Americano', 10000);
        $fries = $this->makeProduct('French Fries', 5000);

        $order = $this->makeOrder([
            [
                'id_product' => $americano->id_product,
                'item_type' => 'product',
                'item_name' => $americano->name,
                'item_sku' => 'SKU-A',
                'quantity' => 1,
                'unit_price' => 10000,
                'total_price' => 10000,
            ],
        ]);

        $response = $this->putJson("/api/pos/orders/{$order->id_order}", [
            'items' => [
                ['id_product' => $americano->id_product, 'quantity' => 2, 'unit_price' => 10000],
                ['id_product' => $fries->id_product, 'quantity' => 1, 'unit_price' => 5000],
            ],
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertSame(25000.0, (float) $order->subtotal, 'Subtotal must be recalculated from the new items');
        $this->assertSame(25000.0, (float) $order->total_amount, 'Total must be recalculated from the new items');
    }

    public function test_percentage_discount_is_applied_to_new_subtotal(): void
    {
        $americano = $this->makeProduct('Americano', 10000);

        $order = $this->makeOrder([
            [
                'id_product' => $americano->id_product,
                'item_type' => 'product',
                'item_name' => $americano->name,
                'item_sku' => 'SKU-A',
                'quantity' => 1,
                'unit_price' => 10000,
                'total_price' => 10000,
            ],
        ]);

        $response = $this->putJson("/api/pos/orders/{$order->id_order}", [
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'items' => [
                ['id_product' => $americano->id_product, 'quantity' => 2, 'unit_price' => 10000],
            ],
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertSame(20000.0, (float) $order->subtotal);
        $this->assertSame(2000.0, (float) $order->discount_amount, 'Percentage discount must be computed from the NEW subtotal');
        $this->assertSame(18000.0, (float) $order->total_amount);
    }

    public function test_package_items_are_preserved_when_editing_product_items(): void
    {
        $americano = $this->makeProduct('Americano', 10000);

        $package = Package::create([
            'name' => 'Breakfast Trio',
            'package_type' => 'fixed',
            'regular_price' => 49000,
            'package_price' => 40000,
            'category_id' => $this->category->id_category,
        ]);

        $order = $this->makeOrder([
            [
                'id_product' => $americano->id_product,
                'item_type' => 'product',
                'item_name' => $americano->name,
                'item_sku' => 'SKU-A',
                'quantity' => 1,
                'unit_price' => 10000,
                'total_price' => 10000,
            ],
            [
                'id_package' => $package->id_package,
                'item_type' => 'package',
                'package_name' => $package->name,
                'item_name' => $package->name,
                'item_sku' => 'PKG-BT',
                'quantity' => 1,
                'unit_price' => 40000,
                'total_price' => 40000,
            ],
        ], ['subtotal' => 50000, 'total_amount' => 50000]);

        $response = $this->putJson("/api/pos/orders/{$order->id_order}", [
            'items' => [
                ['id_product' => $americano->id_product, 'quantity' => 2, 'unit_price' => 10000],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('order_items', [
            'id_order' => $order->id_order,
            'item_type' => 'package',
            'id_package' => $package->id_package,
        ]);

        $order->refresh();
        $this->assertSame(60000.0, (float) $order->subtotal, 'Subtotal must include the preserved package item');
        $this->assertSame(60000.0, (float) $order->total_amount);
    }
}
