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
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('unit_price');
            $table->string('discount_type')->nullable()->after('discount_amount'); // 'percentage', 'fixed'
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('discount_type');
            $table->decimal('subtotal_before_discount', 15, 2)->nullable()->after('discount_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'discount_amount',
                'discount_type', 
                'discount_percentage',
                'subtotal_before_discount'
            ]);
        });
    }
};
