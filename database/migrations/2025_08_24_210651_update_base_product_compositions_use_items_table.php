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
            // Add new ingredient_item_id column
            $table->unsignedBigInteger('ingredient_item_id')->nullable()->after('base_product_id');
            
            // Add foreign key constraint
            $table->foreign('ingredient_item_id', 'bpc_ingredient_item_fk')
                  ->references('id_item')
                  ->on('items')
                  ->onDelete('restrict');
            
            // Add index for better performance
            $table->index('ingredient_item_id', 'bpc_ingredient_item_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_product_compositions', function (Blueprint $table) {
            // Drop foreign key and index
            $table->dropForeign('bpc_ingredient_item_fk');
            $table->dropIndex('bpc_ingredient_item_idx');
            
            // Drop the column
            $table->dropColumn('ingredient_item_id');
        });
    }
};
