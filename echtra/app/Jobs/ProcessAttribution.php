<?php

namespace App\Jobs;

use App\Models\Attribution;
use App\Models\Beacon;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAttribution implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Event $locationEvent
    ) {}

    /**
     * Execute the job.
     *
     * Check if this device heard a campaign beacon within the attribution window.
     */
    public function handle(): void
    {
        $locationBeacon = $this->locationEvent->beacon;

        // Only process location beacon events
        if ($locationBeacon->type !== 'location') {
            return;
        }

        // Get attribution window (default 7 days)
        $attributionWindowDays = 7;
        $windowStart = $this->locationEvent->heard_at->subDays($attributionWindowDays);

        // Find campaign events from this device within the window
        $campaignEvents = Event::query()
            ->where('device_id', $this->locationEvent->device_id)
            ->where('heard_at', '>=', $windowStart)
            ->where('heard_at', '<', $this->locationEvent->heard_at)
            ->whereHas('beacon', function ($query) {
                $query->where('type', 'campaign');
            })
            ->with('beacon')
            ->orderBy('heard_at', 'desc')
            ->get();

        foreach ($campaignEvents as $campaignEvent) {
            // Check if this attribution already exists
            $existingAttribution = Attribution::query()
                ->where('campaign_beacon_id', $campaignEvent->beacon_id)
                ->where('location_beacon_id', $locationBeacon->id)
                ->where('device_id', $this->locationEvent->device_id)
                ->where('campaign_heard_at', $campaignEvent->heard_at)
                ->first();

            if ($existingAttribution) {
                continue; // Skip duplicate attributions
            }

            // Calculate time to conversion
            $timeToConversionHours = $campaignEvent->heard_at->diffInHours($this->locationEvent->heard_at);

            // Create the attribution
            Attribution::create([
                'campaign_beacon_id' => $campaignEvent->beacon_id,
                'location_beacon_id' => $locationBeacon->id,
                'device_id' => $this->locationEvent->device_id,
                'campaign_heard_at' => $campaignEvent->heard_at,
                'location_heard_at' => $this->locationEvent->heard_at,
                'attribution_window_days' => $attributionWindowDays,
                'time_to_conversion_hours' => $timeToConversionHours,
            ]);
        }
    }
}
