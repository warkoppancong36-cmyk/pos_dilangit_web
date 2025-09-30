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
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'credit_card', 'debit_card', 'kartu', 'qris', 'gopay', 'ovo', 'dana', 'shopeepay', 'bank_transfer', 'gofood', 'grabfood', 'shopeefood', 'other')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'credit_card', 'debit_card', 'kartu', 'qris', 'gopay', 'ovo', 'dana', 'shopeepay', 'bank_transfer','other')");
    }
};
