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
        Schema::create('customer_loyalty', function (Blueprint $table) {
            $table->id('id_customer_loyalty');
            $table->unsignedBigInteger('id_customer');
            $table->unsignedBigInteger('id_loyalty_program');
            $table->integer('current_points')->default(0);
            $table->integer('lifetime_points')->default(0);
            $table->integer('redeemed_points')->default(0);
            $table->string('current_tier')->nullable();
            $table->date('tier_achieved_date')->nullable();
            $table->date('tier_expiry_date')->nullable();
            $table->integer('visits_count')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->timestamp('last_activity')->nullable();
            $table->boolean('active')->default(true);
            $table->json('achievements')->nullable(); // Badges, milestones
            $table->timestamps();

            $table->foreign('id_customer')->references('id_customer')->on('customers')->onDelete('cascade');
            $table->foreign('id_loyalty_program')->references('id_loyalty_program')->on('loyalty_programs')->onDelete('cascade');
            
            $table->unique(['id_customer', 'id_loyalty_program']);
            $table->index(['current_points']);
            $table->index(['current_tier']);
            $table->index(['active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_loyalty');
    }
};
