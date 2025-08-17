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
        Schema::table('items', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['supplier_id']);
            
            // Drop index
            $table->dropIndex(['supplier_id']);
            
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
