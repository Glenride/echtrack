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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beacon_id')->constrained()->onDelete('cascade');
            $table->string('device_id', 64)->comment('Hashed device identifier for privacy');
            $table->decimal('signal_strength_db', 5, 2)->comment('Signal strength in decibels');
            $table->timestamp('heard_at')->comment('When the beacon was heard on device');
            $table->foreignId('api_key_id')->constrained()->onDelete('cascade');
            $table->json('metadata')->nullable()->comment('Additional data from SDK');
            $table->timestamps();

            $table->index(['beacon_id', 'heard_at']);
            $table->index(['device_id', 'heard_at']);
            $table->index('api_key_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
