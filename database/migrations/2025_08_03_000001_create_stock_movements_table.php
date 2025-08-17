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
        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id('id_stock_movement');
                $table->unsignedBigInteger('id_product');
                $table->unsignedBigInteger('id_variant')->nullable();
                $table->enum('movement_type', ['in', 'out', 'adjustment']);
                $table->enum('reference_type', ['purchase', 'sale', 'adjustment', 'initial']);
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->integer('quantity_before');
                $table->integer('quantity_change');
                $table->integer('quantity_after');
                $table->decimal('unit_cost', 10, 2)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
                
                $table->index(['id_product', 'movement_type']);
                $table->index(['reference_type', 'reference_id']);
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
