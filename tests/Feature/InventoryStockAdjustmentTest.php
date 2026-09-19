<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryStockAdjustmentTest extends TestCase
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

        $this->user = User::factory()->create(['username' => 'inv-test']);
        Sanctum::actingAs($this->user);
    }

    /** Fix #1: creating an item must also create its inventory (stock) record. */
    public function test_creating_item_also_creates_inventory_record(): void
    {
        $response = $this->postJson('/api/items', [
            'name' => 'Gula Aren',
            'unit' => 'kg',
            'current_stock' => 15,
            'minimum_stock' => 3,
        ]);

        $response->assertCreated();
        $idItem = $response->json('data.id_item');

        $this->assertDatabaseHas('inventory', [
            'id_item' => $idItem,
            'current_stock' => 15,
            'reorder_level' => 3,
        ]);
    }

    /** Fix #2: adjusting stock for a legacy item without inventory must not error. */
    public function test_stock_adjustment_creates_inventory_when_missing(): void
    {
        // Simulate a legacy item created before inventory rows were auto-created.
        $item = Item::create([
            'name' => 'Item Lama Tanpa Inventory',
            'unit' => 'pcs',
            'active' => true,
        ]);

        $this->assertDatabaseMissing('inventory', ['id_item' => $item->id_item]);

        $response = $this->postJson('/api/inventory/movement', [
            'item_id' => $item->id_item,
            'movement_type' => 'adjustment',
            'quantity' => 25,
            'notes' => 'penyesuaian awal',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventory', [
            'id_item' => $item->id_item,
            'current_stock' => 25,
        ]);
    }
}
