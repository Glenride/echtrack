<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_beacon_id',
        'location_beacon_id',
        'device_id',
        'campaign_heard_at',
        'location_heard_at',
        'attribution_window_days',
        'time_to_conversion_hours',
    ];

    protected $casts = [
        'campaign_heard_at' => 'datetime',
        'location_heard_at' => 'datetime',
        'attribution_window_days' => 'integer',
        'time_to_conversion_hours' => 'integer',
    ];

    /**
     * Get the campaign beacon.
     */
    public function campaignBeacon(): BelongsTo
    {
        return $this->belongsTo(Beacon::class, 'campaign_beacon_id');
    }

    /**
     * Get the location beacon.
     */
    public function locationBeacon(): BelongsTo
    {
        return $this->belongsTo(Beacon::class, 'location_beacon_id');
    }

    /**
     * Scope to attributions for a specific campaign.
     */
    public function scopeForCampaign($query, int $campaignBeaconId)
    {
        return $query->where('campaign_beacon_id', $campaignBeaconId);
    }

    /**
     * Scope to attributions for a specific location.
     */
    public function scopeForLocation($query, int $locationBeaconId)
    {
        return $query->where('location_beacon_id', $locationBeaconId);
    }

    /**
     * Scope to attributions within a date range.
     */
    public function scopeConvertedBetween($query, $start, $end)
    {
        return $query->whereBetween('location_heard_at', [$start, $end]);
    }
}
