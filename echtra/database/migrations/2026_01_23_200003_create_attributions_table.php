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
        Schema::create('attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_beacon_id')->constrained('beacons')->onDelete('cascade');
            $table->foreignId('location_beacon_id')->constrained('beacons')->onDelete('cascade');
            $table->string('device_id', 64)->comment('Hashed device identifier');
            $table->timestamp('campaign_heard_at')->comment('When user heard the campaign');
            $table->timestamp('location_heard_at')->comment('When user visited the location');
            $table->integer('attribution_window_days')->default(7);
            $table->integer('time_to_conversion_hours')->comment('Hours between campaign and location');
            $table->timestamps();

            $table->index(['campaign_beacon_id', 'location_beacon_id']);
            $table->index('device_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributions');
    }
};
