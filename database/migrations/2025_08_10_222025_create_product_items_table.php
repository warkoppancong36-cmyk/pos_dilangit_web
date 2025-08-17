<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_items', function (Blueprint $table) {
            $table->id('id_product_item');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity_needed', 15, 3); // How much of this item is needed for 1 unit of product
            $table->string('unit'); // Unit for this specific relationship (could be different from item's base unit)
            $table->decimal('cost_per_unit', 15, 2)->nullable(); // Override cost if needed
            $table->boolean('is_critical')->default(false); // Is this item critical for production?
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id_product')->on('products')->onDelete('cascade');
            $table->foreign('item_id')->references('id_item')->on('items')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->unique(['product_id', 'item_id']);
            $table->index(['product_id']);
            $table->index(['item_id']);
            $table->index(['is_critical']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};
