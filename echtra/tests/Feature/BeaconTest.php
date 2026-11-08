<?php

use App\Models\Beacon;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Beacon CRUD', function () {
    it('lists user beacons on index page', function () {
        Beacon::factory()->count(3)->create(['user_id' => $this->user->id]);
        Beacon::factory()->count(2)->create(); // Other user's beacons

        $response = $this->actingAs($this->user)->get('/beacons');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('beacons/index')
                ->has('beacons.data', 3)
            );
    });

    it('shows create beacon form', function () {
        $response = $this->actingAs($this->user)->get('/beacons/create');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('beacons/create')
            );
    });

    it('creates new beacon', function () {
        $response = $this->actingAs($this->user)->post('/beacons', [
            'name' => 'Test Campaign',
            'type' => 'campaign',
            'description' => 'A test campaign beacon',
        ]);

        $response->assertRedirect('/beacons');

        $this->assertDatabaseHas('beacons', [
            'user_id' => $this->user->id,
            'name' => 'Test Campaign',
            'type' => 'campaign',
        ]);
    });

    it('validates beacon creation', function () {
        $response = $this->actingAs($this->user)->post('/beacons', [
            // Missing required fields
        ]);

        $response->assertSessionHasErrors(['name', 'type']);
    });

    it('shows beacon details', function () {
        $beacon = Beacon::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/beacons/{$beacon->id}");

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('beacons/show')
                ->has('beacon')
                ->where('beacon.id', $beacon->id)
            );
    });

    it('prevents viewing other users beacons', function () {
        $otherUser = User::factory()->create();
        $beacon = Beacon::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->get("/beacons/{$beacon->id}");

        $response->assertStatus(403);
    });

    it('updates beacon', function () {
        $beacon = Beacon::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->put("/beacons/{$beacon->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'is_active' => false,
        ]);

        $response->assertRedirect("/beacons/{$beacon->id}");

        $this->assertDatabaseHas('beacons', [
            'id' => $beacon->id,
            'name' => 'Updated Name',
            'is_active' => false,
        ]);
    });

    it('deletes beacon', function () {
        $beacon = Beacon::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete("/beacons/{$beacon->id}");

        $response->assertRedirect('/beacons');

        $this->assertDatabaseMissing('beacons', ['id' => $beacon->id]);
    });

    it('auto-generates unique beacon_id', function () {
        $beacon1 = Beacon::create([
            'name' => 'Beacon 1',
            'type' => 'campaign',
            'user_id' => $this->user->id,
        ]);

        $beacon2 = Beacon::create([
            'name' => 'Beacon 2',
            'type' => 'location',
            'user_id' => $this->user->id,
        ]);

        expect($beacon1->beacon_id)->not->toBeEmpty();
        expect($beacon2->beacon_id)->not->toBeEmpty();
        expect($beacon1->beacon_id)->not->toBe($beacon2->beacon_id);
        expect(strlen($beacon1->beacon_id))->toBe(16);
    });
});

describe('Beacon Types', function () {
    it('creates campaign beacon', function () {
        $beacon = Beacon::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'campaign',
        ]);

        expect($beacon->type)->toBe('campaign');
    });

    it('creates location beacon', function () {
        $beacon = Beacon::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'location',
        ]);

        expect($beacon->type)->toBe('location');
    });

    it('scopes to campaigns only', function () {
        Beacon::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'campaign',
        ]);
        Beacon::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'type' => 'location',
        ]);

        $campaigns = Beacon::campaigns()->get();

        expect($campaigns)->toHaveCount(3);
    });

    it('scopes to locations only', function () {
        Beacon::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'campaign',
        ]);
        Beacon::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'type' => 'location',
        ]);

        $locations = Beacon::locations()->get();

        expect($locations)->toHaveCount(2);
    });
});
