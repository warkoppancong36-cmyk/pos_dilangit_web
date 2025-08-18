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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id('id_permission');
            $table->string('name')->unique(); // create_product, edit_product, delete_product, etc
            $table->string('display_name'); // Nama yang user-friendly
            $table->string('module'); // products, orders, users, reports, etc
            $table->string('action'); // create, read, update, delete, view, export, etc
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // System permission, tidak bisa dihapus
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['module', 'action']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
