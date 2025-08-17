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
        Schema::create('product_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_recipe_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 10, 3);
            $table->string('unit', 20);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('product_recipe_id')->references('id')->on('product_recipes')->onDelete('cascade');
            $table->foreign('item_id')->references('id_item')->on('items')->onDelete('cascade');
            $table->index(['product_recipe_id']);
            $table->unique(['product_recipe_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recipe_items');
    }
};
