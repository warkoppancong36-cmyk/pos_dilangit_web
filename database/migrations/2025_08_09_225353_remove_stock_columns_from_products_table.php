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
        Schema::table('products', function (Blueprint $table) {
            // Remove stock-related columns as they should be at variant level
            $table->dropColumn(['stock', 'min_stock', 'unit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore columns if rollback is needed
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);  
            $table->string('unit')->default('pcs');
        });
    }
};
