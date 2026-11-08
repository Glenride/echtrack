<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Beacon extends Model
{
    use HasFactory;

    protected $fillable = [
        'beacon_id',
        'type',
        'name',
        'description',
        'user_id',
        'original_audio_path',
        'watermarked_audio_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model and auto-generate beacon_id.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($beacon) {
            if (empty($beacon->beacon_id)) {
                $beacon->beacon_id = static::generateUniqueBeaconId();
            }
        });
    }

    /**
     * Generate a unique 16-character hex beacon ID.
     */
    public static function generateUniqueBeaconId(): string
    {
        do {
            $id = strtoupper(Str::random(16));
        } while (static::where('beacon_id', $id)->exists());

        return $id;
    }

    /**
     * Get the user that owns the beacon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all events for this beacon.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get attributions where this beacon was the campaign.
     */
    public function campaignAttributions(): HasMany
    {
        return $this->hasMany(Attribution::class, 'campaign_beacon_id');
    }

    /**
     * Get attributions where this beacon was the location.
     */
    public function locationAttributions(): HasMany
    {
        return $this->hasMany(Attribution::class, 'location_beacon_id');
    }

    /**
     * Scope to only active beacons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only campaign beacons.
     */
    public function scopeCampaigns($query)
    {
        return $query->where('type', 'campaign');
    }

    /**
     * Scope to only location beacons.
     */
    public function scopeLocations($query)
    {
        return $query->where('type', 'location');
    }
}
