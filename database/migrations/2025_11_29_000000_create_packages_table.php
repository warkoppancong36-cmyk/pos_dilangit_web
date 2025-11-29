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
        Schema::create('packages', function (Blueprint $table) {
            $table->id('id_package');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('image')->nullable();
            
            // Package Type
            $table->enum('package_type', ['fixed', 'customizable'])->default('fixed');
            // fixed: paket sudah ditentukan isinya
            // customizable: bisa pilih sendiri (future feature)
            
            // Pricing
            $table->decimal('regular_price', 15, 2)->comment('Total harga normal semua item');
            $table->decimal('package_price', 15, 2)->comment('Harga jual paket (setelah diskon)');
            $table->decimal('savings_amount', 15, 2)->default(0)->comment('Selisih hemat');
            $table->decimal('savings_percentage', 5, 2)->default(0)->comment('Persentase hemat');
            
            // Category (optional)
            $table->unsignedBigInteger('category_id')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            
            // Stock Management
            $table->integer('stock')->default(0)->comment('Stok paket yang tersedia');
            $table->boolean('track_stock')->default(false)->comment('Apakah track stok paket atau based on items');
            
            // Metadata
            $table->json('tags')->nullable();
            $table->integer('sort_order')->default(0);
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('category_id')->references('id_category')->on('categories')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['is_active', 'status']);
            $table->index(['package_type']);
            $table->index(['category_id']);
            $table->index(['slug']);
            $table->index(['sku']);
            $table->index(['barcode']);
            $table->index(['is_featured']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
