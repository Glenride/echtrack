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
        Schema::create('beacons', function (Blueprint $table) {
            $table->id();
            $table->string('beacon_id', 16)->unique()->comment('Unique 16-bit hex identifier');
            $table->enum('type', ['campaign', 'location'])->default('campaign');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_audio_path')->nullable();
            $table->string('watermarked_audio_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['beacon_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beacons');
    }
};
