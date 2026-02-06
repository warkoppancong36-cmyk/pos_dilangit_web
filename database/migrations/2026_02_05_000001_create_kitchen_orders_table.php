<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Membuat tabel kitchen_orders dan kitchen_order_items
     * untuk menangani kitchen orders yang terpisah dari orders utama.
     * 
     * Flow:
     * 1. Saat item kitchen ditambahkan ke order yang sudah ada
     * 2. Backend membuat KitchenOrder baru (ID berbeda, order_number sama)
     * 3. Polling di Kitchen Display detect order baru (status: pending)
     * 4. Order muncul di tab "Pesanan Baru"
     * 5. Kitchen staff print order → pindah ke "Pesanan Sudah di Print"
     */
    public function up(): void
    {
        // Create kitchen_orders table
        Schema::create('kitchen_orders', function (Blueprint $table) {
            $table->id('id_kitchen_order');
            
            // Relasi ke order utama
            $table->unsignedBigInteger('id_order');
            $table->foreign('id_order')
                  ->references('id_order')
                  ->on('orders')
                  ->onDelete('cascade');
            
            // Informasi order untuk referensi cepat
            $table->string('order_number', 50);
            $table->string('table_number', 20)->nullable();
            $table->string('order_type', 30)->default('dine_in');
            $table->string('customer_name')->nullable();
            
            // Status dan tracking
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                  ->default('pending');
            $table->string('created_by_station', 20)->default('kasir'); // bar, kasir
            
            // Timestamps untuk tracking
            $table->timestamp('acknowledged_at')->nullable(); // Saat kitchen accept
            $table->timestamp('completed_at')->nullable();     // Saat selesai diproses
            $table->timestamp('printed_at')->nullable();       // Saat di-print
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes untuk performa query polling
            $table->index('status');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index('order_number');
        });

        // Create kitchen_order_items table
        Schema::create('kitchen_order_items', function (Blueprint $table) {
            $table->id('id_kitchen_order_item');
            
            // Relasi ke kitchen_order
            $table->unsignedBigInteger('id_kitchen_order');
            $table->foreign('id_kitchen_order')
                  ->references('id_kitchen_order')
                  ->on('kitchen_orders')
                  ->onDelete('cascade');
            
            // Relasi opsional ke order_items
            $table->unsignedBigInteger('id_order_item')->nullable();
            $table->foreign('id_order_item')
                  ->references('id_order_item')
                  ->on('order_items')
                  ->onDelete('set null');
            
            // Detail item
            $table->string('product_name');
            $table->integer('quantity')->default(1);
            $table->string('variant_name')->nullable();
            $table->json('customizations')->nullable();
            $table->text('notes')->nullable();
            
            // Status item individual
            $table->enum('status', ['pending', 'in_progress', 'completed'])
                  ->default('pending');
            
            $table->timestamps();
            
            // Index
            $table->index('id_kitchen_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_order_items');
        Schema::dropIfExists('kitchen_orders');
    }
};
