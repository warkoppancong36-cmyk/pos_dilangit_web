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
        // 1. Sales Daily Summary - untuk laporan penjualan harian
        Schema::create('report_sales_daily', function (Blueprint $table) {
            $table->id('id_report');
            $table->date('report_date');
            $table->integer('total_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_discount', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('average_order_value', 15, 2)->default(0);
            $table->integer('total_items_sold')->default(0);
            $table->integer('unique_customers')->default(0);
            $table->json('hourly_breakdown')->nullable(); // Sales per hour
            $table->json('payment_methods')->nullable(); // Breakdown by payment method
            $table->timestamps();
            
            $table->unique('report_date');
            $table->index('report_date');
        });

        // 2. Transaction History Cache - untuk riwayat transaksi yang cepat
        Schema::create('report_transaction_cache', function (Blueprint $table) {
            $table->id('id_cache');
            $table->unsignedBigInteger('id_order');
            $table->string('order_number');
            $table->date('order_date');
            $table->time('order_time');
            $table->string('customer_name')->nullable();
            $table->string('table_number')->nullable();
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery']);
            $table->string('status');
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method')->nullable();
            $table->integer('items_count')->default(0);
            $table->json('items_detail')->nullable(); // Array of item names and quantities
            $table->timestamps();
            
            $table->index('id_order');
            $table->index('order_date');
            $table->index(['order_date', 'order_time']);
            $table->index('status');
            
            $table->foreign('id_order')->references('id_order')->on('orders')->onDelete('cascade');
        });

        // 3. Product Performance Summary - untuk analisis produk
        Schema::create('report_product_performance', function (Blueprint $table) {
            $table->id('id_report');
            $table->date('report_date');
            $table->unsignedBigInteger('id_product');
            $table->string('product_name');
            $table->string('category_name')->nullable();
            $table->integer('quantity_sold')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('orders_count')->default(0);
            $table->decimal('average_price', 15, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['report_date', 'id_product']);
            $table->index('report_date');
            $table->index('id_product');
        });

        // 4. Customer Analytics Summary
        Schema::create('report_customer_analytics', function (Blueprint $table) {
            $table->id('id_report');
            $table->date('report_date');
            $table->unsignedBigInteger('id_customer')->nullable();
            $table->string('customer_name');
            $table->integer('orders_count')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->decimal('average_order_value', 15, 2)->default(0);
            $table->date('last_order_date')->nullable();
            $table->timestamps();
            
            $table->unique(['report_date', 'id_customer']);
            $table->index('report_date');
            $table->index('id_customer');
        });

        // 5. Hourly Peak Analysis
        Schema::create('report_hourly_analysis', function (Blueprint $table) {
            $table->id('id_report');
            $table->date('report_date');
            $table->integer('hour'); // 0-23
            $table->integer('order_count')->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('average_order_value', 15, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['report_date', 'hour']);
            $table->index('report_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_hourly_analysis');
        Schema::dropIfExists('report_customer_analytics');
        Schema::dropIfExists('report_product_performance');
        Schema::dropIfExists('report_transaction_cache');
        Schema::dropIfExists('report_sales_daily');
    }
};
