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
        if (!Schema::hasTable('cash_transactions')) {
            Schema::create('cash_transactions', function (Blueprint $table) {
                $table->id('id_cash_transaction');
                $table->unsignedBigInteger('id_cash_register');
                $table->unsignedBigInteger('id_user');
                $table->unsignedBigInteger('id_shift')->nullable();
                $table->unsignedBigInteger('id_order')->nullable();
                $table->enum('type', ['in', 'out']);
                $table->enum('source', ['sale', 'manual', 'initial', 'adjustment']);
                $table->decimal('amount', 15, 2);
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->string('description')->nullable();
                $table->text('notes')->nullable();
                $table->string('reference_number')->unique()->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('transaction_date')->useCurrent();
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index(['id_cash_register', 'transaction_date']);
                $table->index(['type', 'source']);
                $table->index('reference_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
