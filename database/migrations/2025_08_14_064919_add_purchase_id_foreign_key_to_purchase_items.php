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
        // Column already added by 2025_08_13_193855_update_purchase_items_table_structure
        if (Schema::hasColumn('purchase_items', 'purchase_id')) {
            return;
        }

        Schema::table('purchase_items', function (Blueprint $table) {
            // Add purchase_id foreign key (using standard Laravel naming)
            $table->foreignId('purchase_id')->constrained('purchases', 'id_purchase')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_id']);
            $table->dropColumn('purchase_id');
        });
    }
};
