<?php

namespace Tests\Feature;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers "kasir langsung": checking out a cart of several items where only
 * SOME of them are selected for a fixed_price discount at payment time
 * (the "Item Dipilih (Dapat Diskon)" picker), via processDirectPayment.
 */
class DirectPaymentPerItemDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $discountedProduct;
    private Product $normalProduct;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        $this->user = User::factory()->create(['username' => 'direct-payment-test']);
        Sanctum::actingAs($this->user);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Minuman',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->discountedProduct = $this->makeProductWithStock($categoryId, 'Affogato', 26000);
        $this->normalProduct = $this->makeProductWithStock($categoryId, 'Es Teh', 8000);
    }

    private function makeProductWithStock(int $categoryId, string $name, float $price): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'description' => 'Test',
            'price' => $price,
            'unit_price' => $price,
            'category_id' => $categoryId,
            'status' => 'published',
            'active' => true,
            'available_in_kitchen' => false,
        ]);

        $itemId = DB::table('items')->insertGetId([
            'item_code' => 'ITM-' . Str::random(6),
            'name' => $name . ' bahan',
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

    /**
     * 2 items in the cart, only ONE checked for the "Diskon Tersedia"
     * fixed_price discount at payment time — the other must stay full price.
     */
    public function test_only_the_selected_item_gets_the_fixed_price_discount(): void
    {
        $response = $this->postJson('/api/pos/process-direct-payment', [
            'cart_items' => [
                [
                    'product_id' => $this->discountedProduct->id_product,
                    'item_type' => 'product',
                    'quantity' => 1,
                    'unit_price' => 26000,
                    'subtotal' => 10000, // Pre-discounted total the client computed
                    'discount_type' => 'fixed_price',
                    'discount_amount' => 16000, // 26000 - 10000 target
                ],
                [
                    'product_id' => $this->normalProduct->id_product,
                    'item_type' => 'product',
                    'quantity' => 1,
                    'unit_price' => 8000,
                    'subtotal' => 8000,
                ],
            ],
            'order_type' => 'dine_in',
            'payment_method' => 'tunai',
            'subtotal_amount' => 34000,
            'total_amount' => 18000,
            'paid_amount' => 20000,
            'change_amount' => 2000,
            'cashier_id' => $this->user->id,
        ]);

        $response->assertSuccessful();

        $discountedItem = OrderItem::where('id_product', $this->discountedProduct->id_product)->first();
        $normalItem = OrderItem::where('id_product', $this->normalProduct->id_product)->first();

        $this->assertSame('fixed_price', $discountedItem->discount_type);
        $this->assertEquals(10000, $discountedItem->total_price);

        // The other item must be completely unaffected.
        $this->assertNull($normalItem->discount_type);
        $this->assertEquals(0, $normalItem->discount_amount);
        $this->assertEquals(8000, $normalItem->total_price);

        // Regression: the response's hand-built 'items' array must actually
        // include the per-item discount fields — persisting them to the DB
        // is not enough if the API response the app reads back never sends
        // them. This is what was silently missing before.
        $responseItems = $response->json('data.items');
        $discountedResponseItem = collect($responseItems)->firstWhere(
            'product_name',
            $this->discountedProduct->name
        );
        $normalResponseItem = collect($responseItems)->firstWhere(
            'product_name',
            $this->normalProduct->name
        );

        $this->assertSame('fixed_price', $discountedResponseItem['discount_type']);
        $this->assertEquals(16000, $discountedResponseItem['discount_amount']);
        $this->assertNull($normalResponseItem['discount_type']);
    }
}
