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
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->enum('type', ['grant', 'deny'])->default('grant'); // Grant atau Deny permission
            $table->text('reason')->nullable(); // Alasan pemberian/pencabutan permission
            $table->unsignedBigInteger('granted_by'); // User yang memberikan permission
            $table->timestamp('expires_at')->nullable(); // Kapan permission expired
            $table->timestamps();

            // Composite unique key
            $table->unique(['user_id', 'permission_id']);
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('permission_id')->references('id_permission')->on('permissions')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('restrict');
            
            // Indexes
            $table->index(['user_id', 'type']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
