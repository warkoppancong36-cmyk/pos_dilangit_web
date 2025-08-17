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
        if (!Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id('id_purchase_item');
                $table->unsignedBigInteger('id_purchase');
                $table->unsignedBigInteger('id_product');
                $table->unsignedBigInteger('id_variant')->nullable();
                $table->integer('quantity_ordered');
                $table->integer('quantity_received')->default(0);
                $table->decimal('unit_cost', 10, 2);
                $table->decimal('total_cost', 15, 2);
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->index(['id_purchase', 'id_product']);
                $table->index('id_product');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
