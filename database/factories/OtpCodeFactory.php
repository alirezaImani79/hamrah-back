<?php

namespace Database\Factories;

use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_number' => '+1'.fake()->numerify('##########'),
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'consumed_at' => null,
            'attempts' => 0,
        ];
    }

    /**
     * Store a specific (hashed) plain-text code on the model.
     */
    public function forCode(string $code): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => Hash::make($code),
        ]);
    }

    /**
     * Indicate that the code has already expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    /**
     * Indicate that the code has already been consumed.
     */
    public function consumed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'consumed_at' => now(),
        ]);
    }
}
