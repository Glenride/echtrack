<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'prefix',
        'user_id',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'key',
    ];

    /**
     * Generate a new API key and return the plain text version.
     * The hashed version is stored in the database.
     */
    public static function generateKey(User $user, string $name): array
    {
        $plainKey = 'eck_' . Str::random(40);
        $prefix = substr($plainKey, 0, 8);

        $apiKey = static::create([
            'name' => $name,
            'key' => hash('sha256', $plainKey),
            'prefix' => $prefix,
            'user_id' => $user->id,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_key' => $plainKey,
        ];
    }

    /**
     * Find an API key by its plain text value.
     */
    public static function findByKey(string $plainKey): ?static
    {
        $hashedKey = hash('sha256', $plainKey);

        return static::where('key', $hashedKey)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Mark the API key as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get the user that owns the API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all events submitted with this API key.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Check if the API key is valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
