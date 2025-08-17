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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->unsignedBigInteger('role_id')->default(1)->after('phone'); // Foreign key to roles table
            $table->boolean('is_active')->default(true)->after('role_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->string('last_login_device')->nullable()->after('last_login_ip');
            $table->integer('login_attempts')->default(0)->after('last_login_device');
            $table->timestamp('locked_until')->nullable()->after('login_attempts');
            
            // Foreign key constraint
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn([
                'username',
                'phone',
                'role_id',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'last_login_device',
                'login_attempts',
                'locked_until'
            ]);
        });
    }
};
