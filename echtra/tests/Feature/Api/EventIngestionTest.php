<?php

use App\Models\ApiKey;
use App\Models\Beacon;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->beacon = Beacon::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'campaign',
    ]);
});

describe('Event Ingestion API', function () {
    it('accepts valid event with API key', function () {
        // Generate API key
        $result = ApiKey::generateKey($this->user, 'Test Key');
        $plainKey = $result['plain_key'];

        $response = $this->postJson('/api/events', [
            'beacon_id' => $this->beacon->beacon_id,
            'device_id' => 'test_device_123',
            'signal_strength_db' => -45.5,
            'timestamp' => now()->toISOString(),
        ], [
            'Authorization' => "Bearer {$plainKey}",
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'beacon_type' => 'campaign',
            ]);

        $this->assertDatabaseHas('events', [
            'beacon_id' => $this->beacon->id,
        ]);
    });

    it('rejects request without API key', function () {
        $response = $this->postJson('/api/events', [
            'beacon_id' => $this->beacon->beacon_id,
            'device_id' => 'test_device_123',
            'signal_strength_db' => -45.5,
            'timestamp' => now()->toISOString(),
        ]);

        $response->assertStatus(401);
    });

    it('rejects request with invalid API key', function () {
        $response = $this->postJson('/api/events', [
            'beacon_id' => $this->beacon->beacon_id,
            'device_id' => 'test_device_123',
            'signal_strength_db' => -45.5,
            'timestamp' => now()->toISOString(),
        ], [
            'Authorization' => 'Bearer invalid_key_123',
        ]);

        $response->assertStatus(401);
    });

    it('rejects request with unknown beacon', function () {
        $result = ApiKey::generateKey($this->user, 'Test Key');
        $plainKey = $result['plain_key'];

        $response = $this->postJson('/api/events', [
            'beacon_id' => 'NONEXISTENT123',
            'device_id' => 'test_device_123',
            'signal_strength_db' => -45.5,
            'timestamp' => now()->toISOString(),
        ], [
            'Authorization' => "Bearer {$plainKey}",
        ]);

        $response->assertStatus(404);
    });

    it('validates required fields', function () {
        $result = ApiKey::generateKey($this->user, 'Test Key');
        $plainKey = $result['plain_key'];

        $response = $this->postJson('/api/events', [
            // Missing all required fields
        ], [
            'Authorization' => "Bearer {$plainKey}",
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'error',
                'messages' => [
                    'beacon_id',
                    'device_id',
                    'signal_strength_db',
                    'timestamp',
                ],
            ]);
    });

    it('accepts batch events', function () {
        $result = ApiKey::generateKey($this->user, 'Test Key');
        $plainKey = $result['plain_key'];

        $response = $this->postJson('/api/events/batch', [
            'events' => [
                [
                    'beacon_id' => $this->beacon->beacon_id,
                    'device_id' => 'device_1',
                    'signal_strength_db' => -45.0,
                    'timestamp' => now()->toISOString(),
                ],
                [
                    'beacon_id' => $this->beacon->beacon_id,
                    'device_id' => 'device_2',
                    'signal_strength_db' => -50.0,
                    'timestamp' => now()->toISOString(),
                ],
            ],
        ], [
            'Authorization' => "Bearer {$plainKey}",
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'success_count' => 2,
                'failure_count' => 0,
            ]);
    });

    it('updates API key last_used_at', function () {
        $result = ApiKey::generateKey($this->user, 'Test Key');
        $apiKey = $result['api_key'];
        $plainKey = $result['plain_key'];

        expect($apiKey->last_used_at)->toBeNull();

        $this->postJson('/api/events', [
            'beacon_id' => $this->beacon->beacon_id,
            'device_id' => 'test_device',
            'signal_strength_db' => -45.0,
            'timestamp' => now()->toISOString(),
        ], [
            'Authorization' => "Bearer {$plainKey}",
        ]);

        $apiKey->refresh();
        expect($apiKey->last_used_at)->not->toBeNull();
    });
});

describe('Beacon Validation API', function () {
    it('validates active beacon exists', function () {
        $response = $this->getJson("/api/beacons/{$this->beacon->beacon_id}/validate");

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'type' => 'campaign',
                'name' => $this->beacon->name,
            ]);
    });

    it('returns 404 for unknown beacon', function () {
        $response = $this->getJson('/api/beacons/UNKNOWN123/validate');

        $response->assertStatus(404)
            ->assertJson([
                'valid' => false,
            ]);
    });

    it('returns 404 for inactive beacon', function () {
        $this->beacon->update(['is_active' => false]);

        $response = $this->getJson("/api/beacons/{$this->beacon->beacon_id}/validate");

        $response->assertStatus(404)
            ->assertJson([
                'valid' => false,
            ]);
    });
});
