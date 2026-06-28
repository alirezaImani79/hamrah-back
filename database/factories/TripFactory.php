<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'origin_lat' => fake()->latitude(25, 40),
            'origin_lng' => fake()->longitude(44, 63),
            'destination_lat' => fake()->latitude(25, 40),
            'destination_lng' => fake()->longitude(44, 63),
            'departure_at' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
            'empty_seats' => fake()->numberBetween(1, 4),
            'trunk_empty' => fake()->boolean(),
        ];
    }

    /**
     * State for a trip whose departure time has already passed.
     */
    public function departed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'departure_at' => fake()->dateTimeBetween('-2 weeks', '-1 hour'),
        ]);
    }
}
