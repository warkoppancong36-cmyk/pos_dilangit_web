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
        // Drop the composite index first — sqlite cannot drop a column
        // that is still referenced by an index (MySQL drops it implicitly)
        $indexes = collect(Schema::getIndexes('variants'))->pluck('name');
        if ($indexes->contains('variants_stock_min_stock_index')) {
            Schema::table('variants', function (Blueprint $table) {
                $table->dropIndex('variants_stock_min_stock_index');
            });
        }

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
