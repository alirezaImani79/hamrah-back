<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\IdentityVerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone_number' => '+1'.fake()->unique()->numerify('##########'),
            'phone_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is subscribed to the SMS newsletter.
     */
    public function subscribedToNewsletter(): static
    {
        return $this->state(fn (array $attributes): array => [
            'newsletter_subscribed_at' => now(),
        ]);
    }

    /**
     * Attach a complete set of submitted identity data and documents.
     */
    public function withIdentityData(): static
    {
        return $this->state(fn (array $attributes): array => [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'national_code' => fake()->unique()->numerify('##########'),
            'birth_date' => fake()->date(),
            'gender' => fake()->randomElement(Gender::cases()),
            'national_card_image_path' => 'identity/card.jpg',
            'face_image_path' => 'identity/face.jpg',
        ]);
    }

    /**
     * Indicate that the user is awaiting an automated identity review.
     */
    public function identityVerifying(): static
    {
        return $this->withIdentityData()->state(fn (array $attributes): array => [
            'identity_status' => IdentityVerificationStatus::Verifying,
        ]);
    }

    /**
     * Indicate that the user's identity has been verified.
     */
    public function identityVerified(): static
    {
        return $this->withIdentityData()->state(fn (array $attributes): array => [
            'identity_status' => IdentityVerificationStatus::Verified,
            'identity_verified_at' => now(),
            'identity_verification_result' => ['probability' => 0.95, 'reason' => 'All details match.'],
        ]);
    }
}
