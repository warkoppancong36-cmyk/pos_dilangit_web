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
        Schema::table('purchase_items', function (Blueprint $table) {
            // Check if columns exist before dropping them
            if (Schema::hasColumn('purchase_items', 'id_product')) {
                $table->dropColumn('id_product');
            }
            
            if (Schema::hasColumn('purchase_items', 'id_purchase')) {
                $table->dropColumn('id_purchase');
            }
            
            if (Schema::hasColumn('purchase_items', 'id_variant')) {
                $table->dropColumn('id_variant');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->unsignedBigInteger('id_product')->nullable();
            $table->unsignedBigInteger('id_purchase')->nullable();
            $table->unsignedBigInteger('id_variant')->nullable();
        });
    }
};
