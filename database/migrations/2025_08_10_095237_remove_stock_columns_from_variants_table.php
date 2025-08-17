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
        Schema::table('variants', function (Blueprint $table) {
            // Remove stock-related columns as stock is now managed entirely in inventory table
            if (Schema::hasColumn('variants', 'stock')) {
                $table->dropColumn('stock');
            }
            if (Schema::hasColumn('variants', 'min_stock')) {
                $table->dropColumn('min_stock');
            }
            if (Schema::hasColumn('variants', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            // Restore columns if rollback is needed
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->string('unit')->default('pcs');
        });
    }
};
