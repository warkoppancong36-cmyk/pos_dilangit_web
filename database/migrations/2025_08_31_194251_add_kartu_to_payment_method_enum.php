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
        // Add 'kartu' to payment_method enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'credit_card', 'debit_card', 'kartu', 'qris', 'gopay', 'ovo', 'dana', 'shopeepay', 'bank_transfer')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'kartu' from payment_method enum (rollback)
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'credit_card', 'debit_card', 'qris', 'gopay', 'ovo', 'dana', 'shopeepay', 'bank_transfer')");
    }
};
