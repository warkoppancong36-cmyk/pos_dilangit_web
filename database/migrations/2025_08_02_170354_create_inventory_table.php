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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id('id_inventory');
            $table->unsignedBigInteger('id_product')->nullable();
            $table->unsignedBigInteger('id_variant')->nullable();
            $table->integer('current_stock')->default(0);
            $table->integer('reserved_stock')->default(0); // Stock reserved for pending orders
            $table->integer('available_stock')->storedAs('current_stock - reserved_stock');
            $table->integer('reorder_level')->default(0);
            $table->integer('max_stock_level')->nullable();
            $table->decimal('average_cost', 15, 2)->default(0);
            $table->timestamp('last_restocked')->nullable();
            $table->timestamp('last_counted')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
            $table->foreign('id_variant')->references('id_variant')->on('variants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->unique(['id_product', 'id_variant']);
            $table->index(['current_stock']);
            $table->index(['reorder_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
