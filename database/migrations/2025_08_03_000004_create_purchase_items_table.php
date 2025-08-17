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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id('id_purchase_item');
            $table->unsignedBigInteger('id_purchase');
            $table->unsignedBigInteger('id_product');
            $table->unsignedBigInteger('id_variant')->nullable();
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->date('expiry_date')->nullable(); // Untuk produk yang bisa expired
            $table->string('batch_number')->nullable(); // Untuk tracking batch
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_purchase')->references('id_purchase')->on('purchases')->onDelete('cascade');
            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
            $table->foreign('id_variant')->references('id_variant')->on('variants')->onDelete('cascade');

            // Indexes
            $table->index(['id_purchase']);
            $table->index(['id_product']);
            $table->index(['id_variant']);
            $table->index(['expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
