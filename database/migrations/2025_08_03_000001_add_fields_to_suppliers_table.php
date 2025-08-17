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
        Schema::table('suppliers', function (Blueprint $table) {
            // Add missing columns
            $table->string('code')->unique()->after('name'); // SUP-001
            $table->string('city')->nullable()->after('address');
            $table->string('province')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('province');
            $table->string('tax_number')->nullable()->after('postal_code'); // NPWP
            $table->text('notes')->nullable()->after('payment_terms');
            
            // Add index for code
            $table->index(['code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropColumn([
                'code',
                'city', 
                'province',
                'postal_code',
                'tax_number',
                'notes'
            ]);
        });
    }
};
