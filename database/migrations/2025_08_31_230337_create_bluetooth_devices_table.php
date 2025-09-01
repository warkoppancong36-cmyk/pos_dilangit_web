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
        Schema::create('bluetooth_devices', function (Blueprint $table) {
            $table->id('id_bluetooth_device');
            $table->unsignedBigInteger('id_user'); // User who registered this device
            $table->string('device_name'); // Display name for the device
            $table->string('device_address'); // Bluetooth MAC address (unique)
            $table->string('device_type'); // Device type
            $table->string('manufacturer')->nullable(); // Device manufacturer
            $table->string('model')->nullable(); // Device model
            $table->json('device_capabilities')->nullable(); // Supported features (print, scan, etc)
            $table->json('connection_settings')->nullable(); // Connection parameters, print settings
            $table->boolean('is_default')->default(false); // Is this the default device for this type
            $table->boolean('is_active')->default(true); // Is device enabled
            $table->timestamp('last_connected_at')->nullable(); // Last successful connection
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index(['id_user', 'device_type']);
            $table->index(['device_address']);
            $table->index(['is_active']);
            $table->unique(['id_user', 'device_address']); // One device per user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bluetooth_devices');
    }
};
