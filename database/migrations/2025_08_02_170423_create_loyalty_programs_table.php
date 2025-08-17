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
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id('id_loyalty_program');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['points', 'visits', 'amount_spent']);
            $table->json('earning_rules'); // {"points_per_rupiah": 1, "bonus_multiplier": 2}
            $table->json('redemption_rules'); // {"points_per_rupiah": 100, "minimum_points": 500}
            $table->integer('tier_threshold')->nullable(); // Points/visits needed for tier
            $table->string('tier_name')->nullable(); // Bronze, Silver, Gold
            $table->decimal('tier_discount_percentage', 5, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->json('benefits')->nullable(); // Additional tier benefits
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['active']);
            $table->index(['type']);
            $table->index(['tier_threshold']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};
