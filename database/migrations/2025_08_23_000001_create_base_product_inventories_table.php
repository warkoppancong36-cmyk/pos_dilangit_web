<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('base_product_inventories', function (Blueprint $table) {
            $table->id('id_base_inventory');
            $table->unsignedBigInteger('id_base_product');
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('reserved_stock', 15, 3)->default(0);
            $table->decimal('min_stock', 15, 3)->default(0);
            $table->decimal('max_stock', 15, 3)->nullable();
            $table->decimal('average_cost', 15, 2)->default(0);
            $table->decimal('last_purchase_cost', 15, 2)->nullable();
            $table->date('last_purchase_date')->nullable();
            $table->date('last_stock_check')->nullable();
            $table->json('batch_info')->nullable(); // for batch tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_base_product')->references('id_base_product')->on('base_products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->unique('id_base_product');
            $table->index(['current_stock', 'min_stock']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('base_product_inventories');
    }
};
