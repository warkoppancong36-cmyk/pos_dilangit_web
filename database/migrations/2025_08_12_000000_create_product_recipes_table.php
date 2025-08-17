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
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->integer('portion_size')->nullable();
            $table->string('portion_unit')->nullable();
            $table->integer('preparation_time')->nullable(); // in minutes
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->json('instructions')->nullable(); // Array of instruction steps
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->foreign('product_id')->references('id_product')->on('products')->onDelete('cascade');
            $table->index(['product_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
