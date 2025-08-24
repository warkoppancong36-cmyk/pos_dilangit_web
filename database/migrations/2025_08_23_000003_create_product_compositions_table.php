<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_compositions', function (Blueprint $table) {
            $table->id('id_composition');
            $table->unsignedBigInteger('id_product'); // Main product
            $table->unsignedBigInteger('id_base_product'); // Base product used as ingredient
            $table->decimal('quantity_needed', 15, 3); // How much base product needed
            $table->string('unit')->default('pcs'); // Unit for the quantity
            $table->decimal('cost_per_unit', 15, 2)->default(0); // Cost of base product at time of composition
            $table->boolean('is_essential')->default(true); // Is this ingredient essential for the product
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
            $table->foreign('id_base_product')->references('id_base_product')->on('base_products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('id_product');
            $table->index('id_base_product');
            $table->unique(['id_product', 'id_base_product']); // Prevent duplicate compositions
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_compositions');
    }
};
