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
            // Add package support columns
            $table->unsignedBigInteger('id_package')->nullable()->after('id_product');
            $table->string('package_name')->nullable()->after('id_package');
            $table->enum('item_type', ['product', 'package'])->default('product')->after('package_name');
            
            // Add foreign key constraint
            $table->foreign('id_package')
                  ->references('id_package')
                  ->on('packages')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['id_package']);
            
            // Then drop columns
            $table->dropColumn(['id_package', 'package_name', 'item_type']);
        });
    }
};
