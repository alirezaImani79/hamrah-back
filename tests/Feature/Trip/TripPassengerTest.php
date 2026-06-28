<?php

use App\Models\Trip;
use App\Models\User;

it('lets a user join a trip as a passenger', function () {
    $trip = Trip::factory()->create(['empty_seats' => 3]);
    $passenger = User::factory()->create();
    $token = $passenger->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/join")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.passengers_count', 1);

    $this->assertDatabaseHas('trip_passengers', [
        'trip_id' => $trip->id,
        'user_id' => $passenger->id,
    ]);
});

it('does not let the driver join their own trip', function () {
    $driver = User::factory()->create();
    $trip = Trip::factory()->for($driver)->create();
    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/join")
        ->assertStatus(403);

    $this->assertDatabaseMissing('trip_passengers', [
        'trip_id' => $trip->id,
        'user_id' => $driver->id,
    ]);
});

it('does not let a user join a full trip', function () {
    $trip = Trip::factory()->create(['empty_seats' => 1]);
    $trip->passengers()->attach(User::factory()->create());

    $passenger = User::factory()->create();
    $token = $passenger->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/join")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['trip']);
});

it('does not let a user join the same trip twice', function () {
    $trip = Trip::factory()->create(['empty_seats' => 3]);
    $passenger = User::factory()->create();
    $trip->passengers()->attach($passenger);
    $token = $passenger->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/join")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['trip']);
});

it('does not let a user join a trip that has departed', function () {
    $trip = Trip::factory()->departed()->create(['empty_seats' => 3]);
    $passenger = User::factory()->create();
    $token = $passenger->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/join")
        ->assertStatus(403);
});

it('lets a passenger leave a trip', function () {
    $trip = Trip::factory()->create();
    $passenger = User::factory()->create();
    $trip->passengers()->attach($passenger);
    $token = $passenger->createToken('test')->plainTextToken;

    $this->withToken($token)->deleteJson("/api/v1/trips/{$trip->id}/leave")
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('trip_passengers', [
        'trip_id' => $trip->id,
        'user_id' => $passenger->id,
    ]);
});

it('does not let a user leave a trip they never joined', function () {
    $trip = Trip::factory()->create();
    $stranger = User::factory()->create();
    $token = $stranger->createToken('test')->plainTextToken;

    $this->withToken($token)->deleteJson("/api/v1/trips/{$trip->id}/leave")
        ->assertStatus(403);
});
