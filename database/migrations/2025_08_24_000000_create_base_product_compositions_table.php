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
        Schema::create('base_product_compositions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('base_product_id');
            $table->unsignedBigInteger('ingredient_base_product_id');
            $table->decimal('quantity', 10, 3)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('base_product_id')->references('id_base_product')->on('base_products')->onDelete('cascade');
            $table->foreign('ingredient_base_product_id')->references('id_base_product')->on('base_products')->onDelete('cascade');

            // Indexes with shorter names
            $table->index(['base_product_id', 'is_active'], 'bpc_base_product_active_idx');
            $table->index(['ingredient_base_product_id', 'is_active'], 'bpc_ingredient_active_idx');
            
            // Unique constraint to prevent duplicate combinations
            $table->unique(['base_product_id', 'ingredient_base_product_id'], 'bpc_unique_combination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_product_compositions');
    }
};
