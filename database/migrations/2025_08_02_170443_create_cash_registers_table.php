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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id('id_cash_register');
            $table->string('register_name');
            $table->string('register_code')->unique();
            $table->string('location')->nullable();
            $table->boolean('active')->default(true);
            $table->decimal('current_cash_balance', 15, 2)->default(0);
            $table->json('supported_payment_methods'); // ["cash", "credit_card", "qris"]
            $table->json('hardware_config')->nullable(); // Printer, cash drawer settings
            $table->text('description')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['active']);
            $table->index(['register_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
