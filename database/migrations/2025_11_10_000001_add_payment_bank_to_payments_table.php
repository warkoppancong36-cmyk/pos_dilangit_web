<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_bank', 50)->nullable()->after('payment_details');
        });

        // Backfill existing rows from JSON payment_details if present
        // Use JSON_EXTRACT to get $.bank value
        try {
            // Use single-quoted PHP string to avoid variable interpolation issues
            DB::statement('UPDATE payments SET payment_bank = JSON_UNQUOTE(JSON_EXTRACT(payment_details, "$.bank")) WHERE payment_details IS NOT NULL AND JSON_EXTRACT(payment_details, "$.bank") IS NOT NULL');
        } catch (\Exception $e) {
            // Some DB engines or older MySQL versions may not support JSON functions gracefully in migrations.
            // We'll silently ignore backfill failure and it can be run later via a separate script.
            logger()->warning('Backfill for payment_bank failed: ' . $e->getMessage());
        }

        // Add index to speed up reporting by bank
        Schema::table('payments', function (Blueprint $table) {
            $table->index('payment_bank', 'idx_payments_payment_bank');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_payment_bank');
            $table->dropColumn('payment_bank');
        });
    }
};
