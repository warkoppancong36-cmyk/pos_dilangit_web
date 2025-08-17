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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id('id_order_item');
            $table->unsignedBigInteger('id_order');
            $table->unsignedBigInteger('id_product')->nullable();
            $table->unsignedBigInteger('id_variant')->nullable();
            $table->string('item_name'); // Store product/variant name at time of order
            $table->string('item_sku'); // Store SKU at time of order
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->json('customizations')->nullable(); // {"sugar_level": "50%", "ice_level": "normal", "toppings": ["pearl", "jelly"]}
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'served'])->default('pending');
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();

            $table->foreign('id_order')->references('id_order')->on('orders')->onDelete('cascade');
            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('set null');
            $table->foreign('id_variant')->references('id_variant')->on('variants')->onDelete('set null');
            
            $table->index(['id_order']);
            $table->index(['status']);
            $table->index(['id_product']);
            $table->index(['id_variant']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
