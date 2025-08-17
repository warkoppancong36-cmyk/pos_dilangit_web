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
        // First, let's check if we have any existing data
        $hasData = DB::table('purchase_items')->count() > 0;
        
        if ($hasData) {
            // If there's existing data, we need to handle it carefully
            echo "Warning: Existing purchase_items data found. Please backup your data before proceeding.\n";
            echo "This migration will convert Product-based purchases to Item-based purchases.\n";
            echo "Manual data mapping may be required.\n";
            
            // Create backup table
            DB::statement('CREATE TABLE purchase_items_backup AS SELECT * FROM purchase_items');
            echo "Backup table 'purchase_items_backup' created.\n";
        }
        
        Schema::table('purchase_items', function (Blueprint $table) {
            // Drop old foreign keys first (if they exist)
            try {
                $table->dropForeign(['id_product']);
            } catch (Exception $e) {
                // Foreign key might not exist
            }
            try {
                $table->dropForeign(['id_variant']);
            } catch (Exception $e) {
                // Foreign key might not exist
            }
            
            // Drop old indexes (if they exist)
            try {
                $table->dropIndex(['id_product']);
            } catch (Exception $e) {
                // Index might not exist
            }
            try {
                $table->dropIndex(['id_variant']);
            } catch (Exception $e) {
                // Index might not exist
            }
            
            // Add new columns
            $table->unsignedBigInteger('purchase_id')->after('id_purchase_item')->nullable();
            $table->unsignedBigInteger('item_id')->after('purchase_id')->nullable();
            $table->string('unit')->after('quantity_received')->default('pcs');
            $table->date('expected_delivery_date')->nullable()->after('total_cost');
            $table->date('actual_delivery_date')->nullable()->after('expected_delivery_date');
            $table->enum('status', ['pending', 'partial', 'received', 'cancelled'])->default('pending')->after('actual_delivery_date');
            $table->json('quality_check')->nullable()->after('notes');
            $table->unsignedBigInteger('received_by')->nullable()->after('quality_check');
        });
        
        // Copy data from old columns to new columns (only purchase_id for now)
        if ($hasData) {
            DB::statement('UPDATE purchase_items SET purchase_id = id_purchase WHERE id_purchase IS NOT NULL');
            echo "Copied purchase_id data from id_purchase.\n";
            echo "NOTE: item_id must be manually mapped from id_product to corresponding items.\n";
            echo "You can use the backup table 'purchase_items_backup' to restore if needed.\n";
        }
        
        // Change quantity columns to decimal
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('quantity_ordered', 15, 3)->change();
            $table->decimal('quantity_received', 15, 3)->default(0)->change();
        });
        
        // Add new foreign keys and indexes after data migration
        Schema::table('purchase_items', function (Blueprint $table) {
            // Add new foreign keys (only for purchase_id initially)
            $table->foreign('purchase_id')->references('id_purchase')->on('purchases')->onDelete('cascade');
            // item_id foreign key will be added after manual mapping
            // $table->foreign('item_id')->references('id_item')->on('items')->onDelete('cascade');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
            
            // Add new indexes
            $table->index(['purchase_id']);
            $table->index(['item_id']);
            $table->index(['status']);
            $table->index(['expected_delivery_date']);
        });
        
        // Note: Old columns (id_purchase, id_product, id_variant) are kept for now
        // They should be manually removed after data migration is complete
        echo "Migration completed. Old columns (id_purchase, id_product, id_variant) are preserved for data migration.\n";
        echo "Please manually map id_product to item_id, then run a cleanup migration to remove old columns.\n";
    }    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            // Add back old columns
            $table->unsignedBigInteger('id_purchase')->after('id_purchase_item');
            $table->unsignedBigInteger('id_product')->after('id_purchase');
            $table->unsignedBigInteger('id_variant')->nullable()->after('id_product');
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            
            // Drop new foreign keys
            $table->dropForeign(['purchase_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['received_by']);
            
            // Drop new indexes
            $table->dropIndex(['purchase_id']);
            $table->dropIndex(['item_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['expected_delivery_date']);
            
            // Copy data back
            DB::statement('UPDATE purchase_items SET id_purchase = purchase_id');
            
            // Add old foreign keys
            $table->foreign('id_purchase')->references('id_purchase')->on('purchases')->onDelete('cascade');
            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
            $table->foreign('id_variant')->references('id_variant')->on('variants')->onDelete('cascade');
            
            // Add old indexes
            $table->index(['id_purchase']);
            $table->index(['id_product']);
            $table->index(['id_variant']);
            
            // Remove new columns
            $table->dropColumn(['purchase_id', 'item_id', 'unit', 'expected_delivery_date', 'actual_delivery_date', 'status', 'quality_check', 'received_by']);
            
            // Change quantity columns back
            $table->integer('quantity_ordered')->change();
            $table->integer('quantity_received')->change();
        });
    }
};
