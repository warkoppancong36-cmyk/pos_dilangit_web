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
        Schema::table('base_product_compositions', function (Blueprint $table) {
            // Drop existing foreign key and unique constraint first
            $table->dropForeign(['ingredient_base_product_id']);
            $table->dropUnique('bpc_unique_combination');
            
            // Make ingredient_base_product_id nullable
            $table->unsignedBigInteger('ingredient_base_product_id')->nullable()->change();
            
            // Re-add foreign key constraint
            $table->foreign('ingredient_base_product_id')
                  ->references('id_base_product')
                  ->on('base_products')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_product_compositions', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['ingredient_base_product_id']);
            
            // Make ingredient_base_product_id non-nullable again
            $table->unsignedBigInteger('ingredient_base_product_id')->nullable(false)->change();
            
            // Re-add foreign key and unique constraint
            $table->foreign('ingredient_base_product_id')
                  ->references('id_base_product')
                  ->on('base_products')
                  ->onDelete('cascade');
                  
            $table->unique(['base_product_id', 'ingredient_base_product_id'], 'bpc_unique_combination');
        });
    }
};
