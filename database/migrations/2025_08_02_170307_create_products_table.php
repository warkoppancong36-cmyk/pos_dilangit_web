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
        Schema::create('products', function (Blueprint $table) {
            $table->id('id_product');
            $table->string('name');
            $table->string('slug');
            $table->text('description');
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->decimal('price', 15, 2);
            $table->decimal('cost', 15, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->string('unit')->default('pcs'); // pcs, kg, liter, etc
            $table->decimal('weight', 8, 2)->nullable(); // in grams
            $table->json('dimensions')->nullable(); // {"length": 10, "width": 5, "height": 3}
            $table->string('image')->nullable(); // single main image path
            $table->unsignedBigInteger('category_id');
            $table->string('brand')->nullable();
            $table->json('tags')->nullable(); // ["tag1", "tag2", "tag3"]
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('active')->default(true);
            $table->boolean('featured')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('category_id')->references('id_category')->on('categories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['active', 'status']);
            $table->index(['featured']);
            $table->index(['category_id']);
            $table->index(['sku']);
            $table->index(['barcode']);
            $table->index(['slug']);
            $table->index(['stock', 'min_stock']); // for low stock queries
            $table->index(['price']); // for price range queries
            $table->index(['brand']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
