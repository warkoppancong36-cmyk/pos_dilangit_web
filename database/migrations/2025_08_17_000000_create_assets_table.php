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
        Schema::create('assets', function (Blueprint $table) {
            $table->id('id_asset');
            $table->string('name'); // Nama asset
            $table->string('type')->default('file'); // file, image, document, etc
            $table->string('category')->nullable(); // product_images, documents, receipts, etc
            $table->string('original_name'); // Nama file asli
            $table->string('file_name'); // Nama file yang disimpan
            $table->string('file_path'); // Path lengkap file
            $table->string('mime_type')->nullable(); // MIME type
            $table->bigInteger('file_size')->default(0); // Ukuran file dalam bytes
            $table->string('extension', 10)->nullable(); // Extension file
            $table->json('metadata')->nullable(); // Metadata tambahan (dimensions, etc)
            $table->string('disk')->default('public'); // Storage disk
            $table->string('storage_path')->nullable(); // Path di storage
            $table->string('public_url')->nullable(); // URL public untuk akses
            $table->text('description')->nullable(); // Deskripsi asset
            $table->json('tags')->nullable(); // Tags untuk pencarian
            $table->boolean('is_public')->default(false); // Apakah bisa diakses public
            $table->boolean('is_active')->default(true); // Status aktif
            $table->unsignedBigInteger('uploaded_by'); // User yang upload
            $table->unsignedBigInteger('updated_by')->nullable(); // User yang update
            $table->timestamp('accessed_at')->nullable(); // Terakhir diakses
            $table->integer('access_count')->default(0); // Jumlah akses
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['type', 'category']);
            $table->index(['is_active', 'is_public']);
            $table->index('uploaded_by');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
