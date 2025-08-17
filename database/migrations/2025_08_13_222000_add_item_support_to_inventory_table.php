<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            // Add column for Items only if it doesn't exist
            if (!Schema::hasColumn('inventory', 'id_item')) {
                $table->unsignedBigInteger('id_item')->nullable()->after('id_variant');
            }
        });
        
        // Check if foreign key exists before adding
        $foreignKeyExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'inventory' 
            AND CONSTRAINT_NAME = 'inventory_id_item_foreign'
        ");
        
        if (empty($foreignKeyExists)) {
            Schema::table('inventory', function (Blueprint $table) {
                $table->foreign('id_item')->references('id_item')->on('items')->onDelete('cascade');
            });
        }
        
        Schema::table('inventory', function (Blueprint $table) {
            // Check if the unique constraint exists before dropping
            try {
                $table->dropUnique('inventory_id_product_id_variant_unique');
            } catch (Exception $e) {
                // Unique constraint might already be dropped
            }
            
            // Create index for id_item only if it doesn't exist
            try {
                $table->index(['id_item']);
            } catch (Exception $e) {
                // Index might already exist
            }
        });
        
        // Update the unique constraint to handle all three types
        Schema::table('inventory', function (Blueprint $table) {
            // Add separate unique constraints only if they don't exist
            try {
                $table->unique(['id_product'], 'inventory_id_product_unique');
            } catch (Exception $e) {
                // Unique constraint might already exist
            }
            
            try {
                $table->unique(['id_variant'], 'inventory_id_variant_unique');
            } catch (Exception $e) {
                // Unique constraint might already exist
            }
            
            try {
                $table->unique(['id_item'], 'inventory_id_item_unique');
            } catch (Exception $e) {
                // Unique constraint might already exist
            }
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
            $table->unique(['id_product', 'id_variant'], 'inventory_id_product_id_variant_unique');
        });
    }
};
