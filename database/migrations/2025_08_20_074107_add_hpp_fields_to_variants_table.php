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
            $table->enum('hpp_method', ['current', 'latest', 'average'])->nullable()->after('cost_price')->comment('HPP calculation method');
            $table->timestamp('hpp_calculated_at')->nullable()->after('hpp_method')->comment('When HPP was last calculated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropColumn(['hpp_method', 'hpp_calculated_at']);
        });
    }
};
