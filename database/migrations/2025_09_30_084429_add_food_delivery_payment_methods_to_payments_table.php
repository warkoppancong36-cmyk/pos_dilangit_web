<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    private const NEW_METHODS = ['cash', 'credit_card', 'debit_card', 'kartu', 'qris', 'gopay', 'ovo', 'dana', 'shopeepay', 'bank_transfer', 'gofood', 'grabfood', 'shopeefood', 'other'];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'credit_card', 'debit_card', 'kartu', 'qris', 'gopay', 'ovo', 'dana', 'shopeepay', 'bank_transfer', 'gofood', 'grabfood', 'shopeefood', 'other')");
            return;
        }

        // sqlite (tests): Laravel menyimpan enum sebagai CHECK constraint, jadi
        // tanpa ini database test menolak pembayaran gofood/grabfood/shopeefood
        // (dulu cabang ini cuma return, dengan anggapan keliru enum = teks bebas).
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', self::NEW_METHODS)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'credit_card', 'debit_card', 'kartu', 'qris', 'gopay', 'ovo', 'dana', 'shopeepay', 'bank_transfer','other')");
    }
};
