<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'beacon_id',
        'device_id',
        'signal_strength_db',
        'heard_at',
        'api_key_id',
        'metadata',
    ];

    protected $casts = [
        'signal_strength_db' => 'decimal:2',
        'heard_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the beacon that was heard.
     */
    public function beacon(): BelongsTo
    {
        return $this->belongsTo(Beacon::class);
    }

    /**
     * Get the API key that submitted this event.
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Scope to events within a date range.
     */
    public function scopeHeardBetween($query, $start, $end)
    {
        return $query->whereBetween('heard_at', [$start, $end]);
    }

    /**
     * Scope to events for a specific device.
     */
    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope to events with strong signal.
     */
    public function scopeStrongSignal($query, float $threshold = -60.0)
    {
        return $query->where('signal_strength_db', '>=', $threshold);
    }
}
