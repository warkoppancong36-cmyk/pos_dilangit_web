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
        // Drop every index that references the doomed columns first — MySQL
        // drops them implicitly with the column, sqlite leaves orphans that
        // break later table rebuilds
        $doomedColumns = ['current_stock', 'minimum_stock', 'maximum_stock'];
        foreach (Schema::getIndexes('items') as $index) {
            if (($index['primary'] ?? false) || empty($index['name'])) {
                continue;
            }
            if (array_intersect($doomedColumns, $index['columns'] ?? [])) {
                Schema::table('items', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index['name']);
                });
            }
        }

        Schema::table('items', function (Blueprint $table) {
            // Remove stock-related columns - these should only be in inventory table
            if (Schema::hasColumn('items', 'current_stock')) {
                $table->dropColumn('current_stock');
            }
            if (Schema::hasColumn('items', 'minimum_stock')) {
                $table->dropColumn('minimum_stock');
            }
            if (Schema::hasColumn('items', 'maximum_stock')) {
                $table->dropColumn('maximum_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('current_stock', 10, 3)->default(0);
            $table->decimal('minimum_stock', 10, 3)->default(0);
            $table->decimal('maximum_stock', 10, 3)->nullable();
        });
    }
};
