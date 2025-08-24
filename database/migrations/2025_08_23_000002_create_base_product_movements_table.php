<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('base_product_movements', function (Blueprint $table) {
            $table->id('id_base_movement');
            $table->unsignedBigInteger('id_base_inventory');
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'transfer']);
            $table->decimal('quantity', 15, 3);
            $table->decimal('stock_before', 15, 3);
            $table->decimal('stock_after', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('reference_type')->nullable(); // purchase, production, adjustment, etc
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->datetime('movement_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_base_inventory')->references('id_base_inventory')->on('base_product_inventories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['movement_type', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('base_product_movements');
    }
};
