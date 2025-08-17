<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'code')) {
                $table->string('code', 20)->unique()->after('id_supplier');
            }
            if (!Schema::hasColumn('suppliers', 'province')) {
                $table->string('province', 100)->nullable()->after('city');
            }
            if (!Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('tax_number');
            }
            if (!Schema::hasColumn('suppliers', 'bank_account')) {
                $table->string('bank_account', 50)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('suppliers', 'bank_account_name')) {
                $table->string('bank_account_name', 100)->nullable()->after('bank_account');
            }
        });

        // Only update status column logic if needed
        if (Schema::hasColumn('suppliers', 'active') && !Schema::hasColumn('suppliers', 'status')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->enum('status', ['active', 'inactive'])->default('active');
            });
            
            DB::statement("UPDATE suppliers SET status = CASE WHEN active = 1 THEN 'active' ELSE 'inactive' END");
            
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }

        // Remove payment_terms column if exists
        if (Schema::hasColumn('suppliers', 'payment_terms')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('payment_terms');
            });
        }
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'code', 'city', 'province', 'postal_code', 'tax_number',
                'bank_name', 'bank_account', 'bank_account_name', 'notes', 'status'
            ]);
            $table->boolean('active')->default(true);
            $table->json('payment_terms')->nullable();
        });
    }
};
