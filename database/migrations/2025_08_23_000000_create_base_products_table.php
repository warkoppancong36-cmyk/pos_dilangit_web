<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('base_products', function (Blueprint $table) {
            $table->id('id_base_product');
            $table->string('name');
            $table->string('sku')->unique()->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('unit', 20)->default('pcs'); // unit measurement (pcs, kg, liter, etc)
            $table->decimal('cost_per_unit', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_perishable')->default(false); // for items with expiry
            $table->integer('shelf_life_days')->nullable(); // days before expiry
            $table->string('storage_type')->nullable(); // freezer, refrigerator, room_temp
            $table->string('supplier_info')->nullable();
            $table->text('notes')->nullable();
            $table->string('image_url')->nullable();
            $table->json('nutritional_info')->nullable(); // for food items
            $table->json('allergen_info')->nullable(); // allergen information
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('category_id')->references('id_category')->on('categories')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['is_active', 'name']);
            $table->index('category_id');
            $table->index('sku');
        });
    }

    public function down()
    {
        Schema::dropIfExists('base_products');
    }
};
