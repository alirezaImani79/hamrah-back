<?php

namespace Database\Factories;

use App\Enums\TripStatus;
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
            'status' => TripStatus::Scheduled,
        ];
    }

    /**
     * State for a trip whose departure time has already passed and has completed.
     */
    public function departed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'departure_at' => fake()->dateTimeBetween('-2 weeks', '-1 hour'),
            'status' => TripStatus::Completed,
            'started_at' => fake()->dateTimeBetween('-2 weeks', '-2 days'),
            'ended_at' => fake()->dateTimeBetween('-2 days', '-1 hour'),
        ]);
    }

    /**
     * State for a trip the driver has started and that is currently on the road.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TripStatus::Ongoing,
            'started_at' => fake()->dateTimeBetween('-3 hours', '-10 minutes'),
        ]);
    }

    /**
     * State for a trip that finished successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TripStatus::Completed,
            'started_at' => fake()->dateTimeBetween('-2 weeks', '-2 days'),
            'ended_at' => fake()->dateTimeBetween('-2 days', '-1 hour'),
        ]);
    }

    /**
     * State for a trip the driver cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TripStatus::Cancelled,
            'ended_at' => fake()->dateTimeBetween('-2 weeks', '-1 hour'),
        ]);
    }
}
