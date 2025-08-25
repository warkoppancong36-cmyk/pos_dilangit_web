<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove unit columns from purchase_items and product_items tables
     * since unit information will be retrieved from items table via JOIN
     */
    public function up(): void
    {
        // Remove unit column from purchase_items table
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        // Remove unit column from product_items table (if it exists)
        if (Schema::hasColumn('product_items', 'unit')) {
            Schema::table('product_items', function (Blueprint $table) {
                $table->dropColumn('unit');
            });
        }
    }

    /**
     * Reverse the migrations.
     * Add back unit columns if needed for rollback
     */
    public function down(): void
    {
        // Add back unit column to purchase_items table
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('unit', 50)->default('pcs')->after('total_cost');
        });

        // Add back unit column to product_items table
        Schema::table('product_items', function (Blueprint $table) {
            $table->string('unit', 50)->default('pcs')->after('quantity_needed');
        });
    }
};
