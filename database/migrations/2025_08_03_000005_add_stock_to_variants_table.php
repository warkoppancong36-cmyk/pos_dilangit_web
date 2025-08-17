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
            // Add stock management columns
            $table->integer('stock')->default(0)->after('cost_price');
            $table->integer('min_stock')->default(0)->after('stock');
            
            // Add index untuk stock queries
            $table->index(['stock', 'min_stock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropIndex(['stock', 'min_stock']);
            $table->dropColumn(['stock', 'min_stock']);
        });
    }
};
