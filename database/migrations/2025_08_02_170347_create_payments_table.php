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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('id_payment');
            $table->unsignedBigInteger('id_order');
            $table->string('payment_number')->unique();
            $table->enum('payment_method', ['cash', 'credit_card', 'debit_card', 'qris', 'gopay', 'ovo', 'dana', 'shopeepay', 'bank_transfer']);
            $table->decimal('amount', 15, 2);
            $table->decimal('cash_received', 15, 2)->nullable();
            $table->decimal('change_amount', 15, 2)->nullable();
            $table->string('reference_number')->nullable(); // For digital payments
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('payment_date');
            $table->json('payment_details')->nullable(); // Store additional payment info
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('processed_by');
            $table->timestamps();

            $table->foreign('id_order')->references('id_order')->on('orders')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['id_order']);
            $table->index(['payment_method']);
            $table->index(['status']);
            $table->index(['payment_date']);
            $table->index(['payment_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
