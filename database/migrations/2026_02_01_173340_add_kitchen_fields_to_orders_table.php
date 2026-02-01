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
        Schema::table('orders', function (Blueprint $table) {
            // Station yang membuat order
            $table->enum('created_by_station', ['kasir', 'bar', 'kitchen'])
                ->default('kasir')
                ->after('status')
                ->comment('Station yang membuat order: kasir, bar, atau kitchen');

            // Kitchen status tracking
            $table->enum('kitchen_status', ['pending', 'in_progress', 'completed'])
                ->nullable()
                ->after('created_by_station')
                ->comment('Status order di kitchen: pending, in_progress, completed');

            // Kitchen timestamps
            $table->timestamp('kitchen_acknowledged_at')
                ->nullable()
                ->after('kitchen_status')
                ->comment('Waktu order di-accept oleh kitchen staff');

            $table->timestamp('kitchen_completed_at')
                ->nullable()
                ->after('kitchen_acknowledged_at')
                ->comment('Waktu order selesai diproses kitchen');

            // Index untuk optimasi query polling
            $table->index(['created_at', 'kitchen_status'], 'idx_kitchen_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop index
            $table->dropIndex('idx_kitchen_orders');

            // Drop columns
            $table->dropColumn([
                'created_by_station',
                'kitchen_status',
                'kitchen_acknowledged_at',
                'kitchen_completed_at',
            ]);
        });
    }
};
