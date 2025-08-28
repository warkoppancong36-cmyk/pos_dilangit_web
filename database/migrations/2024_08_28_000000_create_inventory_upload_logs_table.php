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
        Schema::create('inventory_upload_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('total_items_uploaded')->default(0);
            $table->integer('total_items_updated')->default(0);
            $table->integer('total_items_skipped')->default(0);
            $table->integer('total_items_processed')->default(0);
            $table->decimal('total_stock_value', 15, 2)->default(0);
            $table->integer('low_stock_count')->default(0);
            $table->integer('out_of_stock_count')->default(0);
            $table->timestamp('export_timestamp')->nullable();
            $table->json('filters_applied')->nullable();
            $table->enum('upload_status', ['success', 'failed', 'partial'])->default('success');
            $table->text('notes')->nullable();
            $table->text('error_details')->nullable();
            $table->timestamps();

            // Add indexes for better performance
            $table->index('user_id');
            $table->index('upload_status');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_upload_logs');
    }
};
