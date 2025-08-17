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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id('id_movement');
            $table->unsignedBigInteger('id_product');
            $table->unsignedBigInteger('id_variant')->nullable(); // Jika produk punya variant
            $table->enum('movement_type', ['in', 'out']); // Masuk atau keluar
            $table->enum('reason', [
                'purchase', // Pembelian dari supplier
                'sale', // Penjualan ke customer
                'adjustment', // Adjustment manual (stock opname)
                'return_customer', // Return dari customer
                'return_supplier', // Return ke supplier
                'expired', // Barang expired/rusak
                'transfer_in', // Transfer masuk dari cabang lain
                'transfer_out', // Transfer keluar ke cabang lain
                'production', // Hasil produksi (untuk coffee blend)
                'waste' // Waste/shrinkage
            ]);
            $table->integer('quantity'); // Jumlah yang bergerak
            $table->decimal('unit_cost', 15, 2)->nullable(); // Cost per unit saat movement
            $table->integer('stock_before'); // Stock sebelum movement
            $table->integer('stock_after'); // Stock setelah movement
            $table->string('reference_type')->nullable(); // orders, purchases, adjustments
            $table->unsignedBigInteger('reference_id')->nullable(); // ID dari table reference
            $table->text('notes')->nullable(); // Keterangan tambahan
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
            $table->foreign('id_variant')->references('id_variant')->on('variants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes untuk performance
            $table->index(['id_product', 'movement_type']);
            $table->index(['id_variant', 'movement_type']);
            $table->index(['reason']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
