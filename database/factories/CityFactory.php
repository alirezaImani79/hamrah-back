<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city(),
            'province_id' => Province::factory(),
            'county_id' => null,
            'sector_id' => null,
            'code' => (string) fake()->unique()->numerify('##########'),
            'short_code' => (string) fake()->numerify('####'),
            'status' => true,
        ];
    }
}
