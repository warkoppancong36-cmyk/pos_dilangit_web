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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id('id_promotion');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['happy_hour', 'buy_one_get_one', 'combo_deal', 'member_discount', 'seasonal']);
            $table->json('promotion_rules'); // Complex promotion logic
            $table->decimal('discount_value', 15, 2)->nullable();
            $table->enum('discount_type', ['percentage', 'fixed_amount'])->nullable();
            $table->datetime('valid_from');
            $table->datetime('valid_until');
            $table->json('valid_days')->nullable(); // ["monday", "tuesday", ...]
            $table->time('valid_time_from')->nullable();
            $table->time('valid_time_until')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('priority')->default(0);
            $table->json('applicable_products')->nullable();
            $table->json('applicable_categories')->nullable();
            $table->json('conditions')->nullable();
            $table->string('banner_image')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['active', 'valid_from', 'valid_until']);
            $table->index(['type']);
            $table->index(['priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
