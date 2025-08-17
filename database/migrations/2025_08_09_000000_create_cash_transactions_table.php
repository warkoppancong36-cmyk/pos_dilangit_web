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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id('id_cash_transaction');
            $table->unsignedBigInteger('id_cash_register');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_shift')->nullable();
            $table->unsignedBigInteger('id_order')->nullable(); // Link to order if this is from a sale
            $table->enum('type', ['in', 'out']); // Kas masuk atau keluar
            $table->enum('source', ['sale', 'manual', 'initial', 'adjustment']); // Source of transaction
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_number')->unique()->nullable();
            $table->json('metadata')->nullable(); // Additional data like payment method, etc
            $table->timestamp('transaction_date');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('id_cash_register')->references('id_cash_register')->on('cash_registers')->onDelete('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_shift')->references('id_shift')->on('shifts')->onDelete('set null');
            $table->foreign('id_order')->references('id_order')->on('orders')->onDelete('set null');
            
            // Indexes
            $table->index(['id_cash_register', 'transaction_date']);
            $table->index(['type']);
            $table->index(['source']);
            $table->index(['transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
