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
        Schema::create('variant_items', function (Blueprint $table) {
            $table->id('id_variant_item');
            $table->unsignedBigInteger('id_variant');
            $table->unsignedBigInteger('id_item');
            $table->decimal('quantity_needed', 10, 3); // Jumlah item yang dibutuhkan untuk 1 unit variant
            $table->string('unit', 50); // Unit pengukuran (gram, ml, pcs, dll)
            $table->decimal('cost_per_unit', 15, 2)->nullable(); // Biaya per unit item
            $table->boolean('is_critical')->default(false); // Apakah item ini kritis untuk variant
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_variant')->references('id_variant')->on('variants')->onDelete('cascade');
            $table->foreign('id_item')->references('id_item')->on('items')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['id_variant', 'active']);
            $table->index(['id_item']);
            $table->unique(['id_variant', 'id_item'], 'variant_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_items');
    }
};
