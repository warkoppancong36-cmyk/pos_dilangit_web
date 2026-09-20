<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers the three "open bill" flows that must correctly apply a fixed_price
 * discount: adding a new item to an already-open order, and editing the
 * discount on an item already in an open order.
 */
class OpenBillFixedPriceDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        $this->user = User::factory()->create(['username' => 'openbill-test']);
        Sanctum::actingAs($this->user);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Minuman',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->product = Product::create([
            'name' => 'Es Kopi Susu',
            'slug' => 'es-kopi-susu-' . Str::random(6),
            'description' => 'Test',
            'price' => 50000,
            'unit_price' => 50000,
            'category_id' => $categoryId,
            'status' => 'published',
            'active' => true,
            'available_in_kitchen' => false,
        ]);

        // addItem checks recipe stock availability.
        $itemId = DB::table('items')->insertGetId([
            'item_code' => 'ITM-' . Str::random(6),
            'name' => 'Kopi',
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

        $this->order = Order::create([
            'order_number' => 'ORD-OPEN-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'subtotal' => 0,
            'total_amount' => 0,
            'order_date' => now(),
        ]);
    }

    /** A) Adding a NEW item to an already-open bill with a fixed_price discount. */
    public function test_adding_item_to_open_bill_with_fixed_price_discount_lands_on_exact_price(): void
    {
        $response = $this->postJson("/api/pos/orders/{$this->order->id_order}/items", [
            'id_product' => $this->product->id_product,
            'item_type' => 'product',
            'quantity' => 1,
            'unit_price' => 50000, // Flutter always sends this explicitly
            'cashier_id' => $this->user->id,
            'discount_type' => 'fixed_price',
            'discount_amount' => 20000,
        ]);

        $response->assertSuccessful();

        $orderItem = OrderItem::where('id_order', $this->order->id_order)->first();
        $this->assertNotNull($orderItem);
        $this->assertSame('fixed_price', $orderItem->discount_type);
        $this->assertEquals(20000, $orderItem->total_price);
    }

    /** Same as above, but the fixed price is HIGHER than the product's own price. */
    public function test_adding_item_to_open_bill_with_fixed_price_still_applies_when_higher_than_original(): void
    {
        $response = $this->postJson("/api/pos/orders/{$this->order->id_order}/items", [
            'id_product' => $this->product->id_product,
            'item_type' => 'product',
            'quantity' => 1,
            'unit_price' => 50000, // Rp 50.000 — lower than the discount below
            'cashier_id' => $this->user->id,
            'discount_type' => 'fixed_price',
            'discount_amount' => 60000,
        ]);

        $response->assertSuccessful();

        $orderItem = OrderItem::where('id_order', $this->order->id_order)->first();
        $this->assertEquals(60000, $orderItem->total_price);
    }

    /** B) Editing the discount on an item that's already in an open bill. */
    public function test_updating_existing_open_bill_item_to_fixed_price_discount(): void
    {
        $orderItem = OrderItem::create([
            'id_order' => $this->order->id_order,
            'id_product' => $this->product->id_product,
            'item_type' => 'product',
            'item_name' => $this->product->name,
            'item_sku' => 'SKU-EKS',
            'quantity' => 1,
            'unit_price' => 50000,
            'total_price' => 50000,
        ]);

        $response = $this->putJson(
            "/api/pos/orders/{$this->order->id_order}/items/{$orderItem->id_order_item}/discount",
            [
                'discount_type' => 'fixed_price',
                'discount_amount' => 20000,
            ]
        );

        $response->assertSuccessful();

        $orderItem->refresh();
        $this->assertSame('fixed_price', $orderItem->discount_type);
        $this->assertEquals(20000, $orderItem->total_price);
    }

    /**
     * Regression: changing an item's QUANTITY (without resending discount
     * fields — e.g. the cashier only touches the quantity stepper) must not
     * silently break an already-applied fixed_price guarantee.
     */
    public function test_changing_quantity_keeps_fixed_price_discount_intact(): void
    {
        $orderItem = OrderItem::create([
            'id_order' => $this->order->id_order,
            'id_product' => $this->product->id_product,
            'item_type' => 'product',
            'item_name' => $this->product->name,
            'item_sku' => 'SKU-EKS',
            'quantity' => 1,
            'unit_price' => 50000,
            'total_price' => 50000,
        ]);
        $orderItem->applyDiscount(20000, 'fixed_price');
        $this->assertEquals(20000, $orderItem->fresh()->total_price);

        // Cashier bumps quantity to 2 via the quantity endpoint only — no
        // discount fields resent, matching how the mobile app's quantity
        // stepper calls this endpoint.
        $response = $this->putJson(
            "/api/pos/orders/{$this->order->id_order}/items/{$orderItem->id_order_item}",
            ['quantity' => 2]
        );

        $response->assertSuccessful();

        $orderItem->refresh();
        $this->assertSame('fixed_price', $orderItem->discount_type);
        $this->assertEquals(
            20000,
            $orderItem->total_price,
            'A fixed_price discount must still pin the line at exactly its value after a quantity change, not silently fall back to (new subtotal - stale discount_amount).'
        );
    }

    /**
     * A plain 'fixed' (subtract) discount is a FLAT amount by definition — it
     * must stay 10000 regardless of quantity, so the total scales with the
     * new subtotal minus that same flat amount.
     */
    public function test_changing_quantity_keeps_fixed_amount_discount_flat(): void
    {
        $orderItem = OrderItem::create([
            'id_order' => $this->order->id_order,
            'id_product' => $this->product->id_product,
            'item_type' => 'product',
            'item_name' => $this->product->name,
            'item_sku' => 'SKU-EKS',
            'quantity' => 1,
            'unit_price' => 50000,
            'total_price' => 50000,
        ]);
        $orderItem->applyDiscount(10000, 'fixed');
        $this->assertEquals(40000, $orderItem->fresh()->total_price);

        $response = $this->putJson(
            "/api/pos/orders/{$this->order->id_order}/items/{$orderItem->id_order_item}",
            ['quantity' => 3]
        );

        $response->assertSuccessful();

        $orderItem->refresh();
        $this->assertEquals(10000, $orderItem->discount_amount);
        $this->assertEquals(
            140000, // (3 * 50000) - 10000 — the 10000 stays flat
            $orderItem->total_price
        );
    }

    /**
     * A 'percentage' discount must be recomputed against the NEW subtotal
     * after a quantity change, not left as a stale amount from the old one.
     */
    public function test_changing_quantity_recomputes_percentage_discount(): void
    {
        $orderItem = OrderItem::create([
            'id_order' => $this->order->id_order,
            'id_product' => $this->product->id_product,
            'item_type' => 'product',
            'item_name' => $this->product->name,
            'item_sku' => 'SKU-EKS',
            'quantity' => 1,
            'unit_price' => 50000,
            'total_price' => 50000,
        ]);
        $orderItem->applyDiscount(10, 'percentage'); // 10% off
        $this->assertEquals(45000, $orderItem->fresh()->total_price);

        $response = $this->putJson(
            "/api/pos/orders/{$this->order->id_order}/items/{$orderItem->id_order_item}",
            ['quantity' => 2]
        );

        $response->assertSuccessful();

        $orderItem->refresh();
        $this->assertEquals(
            90000, // (2 * 50000) * (1 - 10%)
            $orderItem->total_price
        );
    }
}
