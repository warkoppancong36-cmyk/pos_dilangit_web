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
        Schema::create('orders', function (Blueprint $table) {
            $table->id('id_order');
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('id_customer')->nullable();
            $table->unsignedBigInteger('id_user'); // cashier/staff
            $table->unsignedBigInteger('id_shift')->nullable();
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery']);
            $table->enum('status', ['draft', 'pending', 'preparing', 'ready', 'completed', 'cancelled'])->default('pending');
            $table->string('table_number')->nullable();
            $table->integer('guest_count')->default(1);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('discount_type')->nullable(); // 'percentage', 'fixed'
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('service_charge', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->json('customer_info')->nullable(); // For walk-in customers
            $table->timestamp('order_date');
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_customer')->references('id_customer')->on('customers')->onDelete('set null');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['status', 'order_date']);
            $table->index(['id_customer']);
            $table->index(['id_user']);
            $table->index(['order_number']);
            $table->index(['order_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
