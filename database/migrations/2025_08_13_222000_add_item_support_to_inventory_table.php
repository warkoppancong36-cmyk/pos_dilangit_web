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
        Schema::table('inventory', function (Blueprint $table) {
            // Add column for Items
            $table->unsignedBigInteger('id_item')->nullable()->after('id_variant');
            
            // Add foreign key for items
            $table->foreign('id_item')->references('id_item')->on('items')->onDelete('cascade');
            
            // Modify unique constraint to include id_item
            $table->dropUnique(['id_product', 'id_variant']);
            
            // Create index for id_item
            $table->index(['id_item']);
        });
        
        // Update the unique constraint to handle all three types
        Schema::table('inventory', function (Blueprint $table) {
            // We need a custom constraint that ensures only one of the three IDs is set per row
            // For now, we'll add separate unique constraints
            $table->unique(['id_product'], 'inventory_id_product_unique');
            $table->unique(['id_variant'], 'inventory_id_variant_unique'); 
            $table->unique(['id_item'], 'inventory_id_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            // Drop foreign key and index
            $table->dropForeign(['id_item']);
            $table->dropIndex(['id_item']);
            
            // Drop unique constraints
            $table->dropUnique('inventory_id_product_unique');
            $table->dropUnique('inventory_id_variant_unique');
            $table->dropUnique('inventory_id_item_unique');
            
            // Drop the column
            $table->dropColumn('id_item');
            
            // Restore original unique constraint
            $table->unique(['id_product', 'id_variant']);
        });
    }
};
