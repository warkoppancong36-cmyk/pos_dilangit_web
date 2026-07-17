<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the index on "active" first — sqlite cannot drop a column
        // that is still referenced by an index (MySQL drops it implicitly)
        $indexes = collect(Schema::getIndexes('suppliers'))->pluck('name');
        if ($indexes->contains('suppliers_active_index')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropIndex('suppliers_active_index');
            });
        }

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('tax_number');
            $table->string('bank_account', 50)->nullable()->after('bank_name');
            $table->string('bank_account_name', 100)->nullable()->after('bank_account');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('bank_account_name');

            if (Schema::hasColumn('suppliers', 'payment_terms')) {
                $table->dropColumn('payment_terms');
            }
            if (Schema::hasColumn('suppliers', 'active')) {
                $table->dropColumn('active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account', 'bank_account_name', 'status']);
            $table->json('payment_terms')->nullable();
            $table->boolean('active')->default(true);
        });
    }
};
