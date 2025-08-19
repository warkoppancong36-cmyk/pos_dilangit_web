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
        Schema::create('variant_attributes', function (Blueprint $table) {
            $table->id('id_variant_attribute');
            $table->unsignedBigInteger('id_product');
            $table->string('attribute_name', 100); // Nama atribut seperti "Size", "Color", "Temperature"
            $table->string('attribute_type', 50)->default('select'); // select, color, text, number
            $table->json('attribute_values'); // ["Small", "Medium", "Large"] atau ["Red", "Blue", "Green"]
            $table->boolean('is_required')->default(true); // Apakah atribut ini wajib dipilih
            $table->integer('sort_order')->default(0); // Urutan tampilan atribut
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['id_product', 'active']);
            $table->index(['sort_order']);
            $table->unique(['id_product', 'attribute_name'], 'product_attribute_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_attributes');
    }
};
