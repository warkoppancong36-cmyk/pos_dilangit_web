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
        // Drop every index referencing supplier_id first (including
        // idx_items_supplier_id from the HPP index migration) — MySQL drops
        // them implicitly with the column, sqlite leaves orphans that break
        // later table rebuilds
        $indexes = collect(Schema::getIndexes('items'))->pluck('name');
        foreach (['items_supplier_id_index', 'idx_items_supplier_id'] as $indexName) {
            if ($indexes->contains($indexName)) {
                Schema::table('items', function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        }

        Schema::table('items', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['supplier_id']);

            // Drop the column
            $table->dropColumn('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Re-add the column
            $table->unsignedBigInteger('supplier_id')->nullable();
            
            // Re-add foreign key
            $table->foreign('supplier_id')->references('id_supplier')->on('suppliers')->onDelete('set null');
            
            // Re-add index
            $table->index(['supplier_id']);
        });
    }
};
