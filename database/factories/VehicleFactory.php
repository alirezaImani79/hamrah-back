<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
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
            'number' => mb_strtoupper(fake()->bothify('##? ### ##')),
            'name' => fake()->randomElement(['Daily driver', 'Work van', 'Weekend car', 'Family ride']),
            'seats' => fake()->numberBetween(2, 7),
            'color' => fake()->safeColorName(),
            'model' => fake()->randomElement(['Peugeot 206', 'Pride 131', 'Samand LX', 'Dena Plus', 'Tiba 2']),
        ];
    }
}
