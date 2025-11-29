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
        Schema::create('package_items', function (Blueprint $table) {
            $table->id('id_package_item');
            $table->unsignedBigInteger('id_package');
            $table->unsignedBigInteger('id_product');
            
            // Quantity
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('pcs');
            
            // Optional for customizable packages
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_default_selected')->default(true);
            
            // Pricing (untuk referensi)
            $table->decimal('unit_price', 15, 2)->nullable()->comment('Harga satuan saat paket dibuat');
            $table->decimal('subtotal', 15, 2)->nullable()->comment('unit_price * quantity');
            
            // Display
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_package')->references('id_package')->on('packages')->onDelete('cascade');
            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
            
            // Indexes
            $table->index(['id_package']);
            $table->index(['id_product']);
            $table->index(['sort_order']);
            
            // Unique constraint: satu produk tidak boleh duplikat dalam 1 paket
            $table->unique(['id_package', 'id_product']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_items');
    }
};
