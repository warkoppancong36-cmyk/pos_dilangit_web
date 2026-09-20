<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_TYPES = ['percentage', 'fixed_amount', 'buy_x_get_y'];
    private const NEW_TYPES = ['percentage', 'fixed_amount', 'buy_x_get_y', 'fixed_price'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE discounts MODIFY COLUMN type ENUM('" . implode("','", self::NEW_TYPES) . "')");
            return;
        }

        // sqlite (and others): Laravel emulates enum() as a CHECK constraint, which
        // ->change() rebuilds correctly without needing doctrine/dbal on Laravel 12.
        Schema::table('discounts', function (Blueprint $table) {
            $table->enum('type', self::NEW_TYPES)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE discounts MODIFY COLUMN type ENUM('" . implode("','", self::OLD_TYPES) . "')");
            return;
        }

        Schema::table('discounts', function (Blueprint $table) {
            $table->enum('type', self::OLD_TYPES)->change();
        });
    }
};
