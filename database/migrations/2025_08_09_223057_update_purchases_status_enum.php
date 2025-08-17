<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Update status enum to include pending, ordered, completed
            DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('pending', 'ordered', 'received', 'completed', 'cancelled') DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Revert back to original enum
            DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('draft', 'sent', 'partial', 'received', 'cancelled') DEFAULT 'draft'");
        });
    }
};
