<?php

use App\Models\User;
use App\Models\Vehicle;

function vehiclePayload(array $overrides = []): array
{
    return array_merge([
        'number' => '12 ج 345 67',
        'name' => 'Daily driver',
        'seats' => 4,
        'color' => 'White',
        'model' => 'Peugeot 206',
    ], $overrides);
}

it('lists only the authenticated user\'s vehicles', function () {
    $user = User::factory()->create();
    Vehicle::factory()->count(2)->for($user)->create();
    Vehicle::factory()->create(); // belongs to someone else

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/vehicles')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

it('creates a vehicle for the authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/vehicles', vehiclePayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.number', '12 ج 345 67')
        ->assertJsonPath('data.name', 'Daily driver')
        ->assertJsonPath('data.seats', 4)
        ->assertJsonPath('data.color', 'White')
        ->assertJsonPath('data.model', 'Peugeot 206');

    $this->assertDatabaseHas('vehicles', [
        'user_id' => $user->id,
        'number' => '12 ج 345 67',
        'model' => 'Peugeot 206',
    ]);
});

it('validates required fields when creating a vehicle', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/vehicles', [])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['number', 'name', 'seats', 'color', 'model']);
});

it('rejects a duplicate license plate for the same user', function () {
    $user = User::factory()->create();
    Vehicle::factory()->for($user)->create(['number' => '12 ج 345 67']);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/vehicles', vehiclePayload(['number' => '12 ج 345 67']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['number']);
});

it('allows two users to register the same license plate', function () {
    Vehicle::factory()->create(['number' => '12 ج 345 67']);

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/vehicles', vehiclePayload(['number' => '12 ج 345 67']))
        ->assertCreated();
});

it('shows a single vehicle owned by the user', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson("/api/v1/vehicles/{$vehicle->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $vehicle->id);
});

it('updates a vehicle', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->putJson("/api/v1/vehicles/{$vehicle->id}", ['name' => 'Weekend car', 'seats' => 2])
        ->assertOk()
        ->assertJsonPath('data.name', 'Weekend car')
        ->assertJsonPath('data.seats', 2);

    expect($vehicle->fresh()->name)->toBe('Weekend car');
});

it('deletes a vehicle', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->deleteJson("/api/v1/vehicles/{$vehicle->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
});

it('does not allow accessing another user\'s vehicle', function () {
    $owner = User::factory()->create();
    $vehicle = Vehicle::factory()->for($owner)->create();

    $intruder = User::factory()->create();
    $token = $intruder->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson("/api/v1/vehicles/{$vehicle->id}")
        ->assertStatus(404)
        ->assertJsonPath('success', false);

    $this->withToken($token)->putJson("/api/v1/vehicles/{$vehicle->id}", ['name' => 'Hijacked'])
        ->assertStatus(404);

    $this->withToken($token)->deleteJson("/api/v1/vehicles/{$vehicle->id}")
        ->assertStatus(404);

    $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
});

it('rejects unauthenticated vehicle requests', function () {
    $this->getJson('/api/v1/vehicles')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});
