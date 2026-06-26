<?php

use App\Models\City;
use App\Models\Province;

it('lists provinces without authentication', function () {
    $province = Province::factory()->create(['name' => 'Tehran']);

    $this->getJson('/api/v1/locations/provinces')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Provinces retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $province->id)
        ->assertJsonPath('data.0.name', 'Tehran');
});

it('lists only the active cities of the given province', function () {
    $province = Province::factory()->create();
    $city = City::factory()->create(['province_id' => $province->id, 'name' => 'Shemiran']);

    // An inactive city in the same province and an active city elsewhere must be excluded.
    City::factory()->create(['province_id' => $province->id, 'status' => false]);
    City::factory()->create();

    $this->getJson("/api/v1/locations/provinces/{$province->id}/cities")
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $city->id)
        ->assertJsonPath('data.0.name', 'Shemiran')
        ->assertJsonPath('data.0.province_id', $province->id);
});

it('omits inactive provinces', function () {
    Province::factory()->create(['status' => false]);
    $active = Province::factory()->create(['status' => true]);

    $this->getJson('/api/v1/locations/provinces')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->id);
});

it('returns a 404 envelope for an unknown province', function () {
    $this->getJson('/api/v1/locations/provinces/999999/cities')
        ->assertStatus(404)
        ->assertJsonPath('success', false);
});
