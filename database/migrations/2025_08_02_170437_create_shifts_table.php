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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id('id_shift');
            $table->string('shift_name'); // Morning, Afternoon, Night
            $table->unsignedBigInteger('id_user'); // Staff assigned to shift
            $table->unsignedBigInteger('id_cash_register')->nullable();
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable();
            $table->integer('total_orders')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->enum('status', ['active', 'closed', 'suspended'])->default('active');
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->json('payment_summary')->nullable(); // Summary by payment method
            $table->unsignedBigInteger('opened_by');
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('opened_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['id_user', 'status']);
            $table->index(['start_time']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
