<?php

namespace Database\Factories;

use App\Models\Beacon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Beacon>
 */
class BeaconFactory extends Factory
{
    protected $model = Beacon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'beacon_id' => strtoupper(Str::random(16)),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['campaign', 'location']),
            'description' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
            'is_active' => true,
            'original_audio_path' => null,
            'watermarked_audio_path' => null,
        ];
    }

    /**
     * Indicate the beacon is a campaign.
     */
    public function campaign(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'campaign',
        ]);
    }

    /**
     * Indicate the beacon is a location.
     */
    public function location(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'location',
        ]);
    }

    /**
     * Indicate the beacon is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
