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
            if (!Schema::hasColumn('variants', 'stock')) {
                $table->integer('stock')->default(0)->after('cost_price');
            }
            if (!Schema::hasColumn('variants', 'min_stock')) {
                $table->integer('min_stock')->default(0)->after('stock');
            }
            if (!Schema::hasColumn('variants', 'unit')) {
                $table->string('unit')->default('pcs')->after('min_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            //
        });
    }
};
