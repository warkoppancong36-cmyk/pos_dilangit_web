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
        Schema::table('inventory', function (Blueprint $table) {
            // Check if column exists before dropping
            if (Schema::hasColumn('inventory', 'item_id')) {
                // Just drop the column, Laravel will handle foreign key automatically
                $table->dropColumn('item_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->nullable();
            // Optionally add back foreign key
            // $table->foreign('item_id')->references('id_item')->on('items');
        });
    }
};
