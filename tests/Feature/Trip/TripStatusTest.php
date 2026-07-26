<?php

use App\Jobs\SendTripUpdatedSms;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('lets the driver start a scheduled trip', function () {
    $driver = User::factory()->create();
    $trip = Trip::factory()->for($driver)->create(); // scheduled
    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/start")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'ongoing')
        ->assertJsonPath('data.role', 'driver');

    expect($trip->fresh())
        ->status->value->toBe('ongoing')
        ->started_at->not->toBeNull();
});

it('lets the driver complete an ongoing trip', function () {
    $driver = User::factory()->create();
    $trip = Trip::factory()->for($driver)->ongoing()->create();
    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    expect($trip->fresh())
        ->status->value->toBe('completed')
        ->ended_at->not->toBeNull();
});

it('lets the driver cancel a scheduled trip', function () {
    $driver = User::factory()->create();
    $trip = Trip::factory()->for($driver)->create(); // scheduled
    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($trip->fresh()->status->value)->toBe('cancelled');
});

it('lets the driver cancel an ongoing trip', function () {
    $driver = User::factory()->create();
    $trip = Trip::factory()->for($driver)->ongoing()->create();
    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($trip->fresh()->status->value)->toBe('cancelled');
});

it('rejects starting a trip that is not scheduled', function () {
    $driver = User::factory()->create();
    $trip = Trip::factory()->for($driver)->ongoing()->create();
    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/start")
        ->assertStatus(409)
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'TRIP_STATUS_TRANSITION_INVALID');
});

it('rejects completing a trip that is not ongoing', function () {
    $driver = User::factory()->create();
    $trip = Trip::factory()->for($driver)->create(); // scheduled
    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/complete")
        ->assertStatus(409)
        ->assertJsonPath('code', 'TRIP_STATUS_TRANSITION_INVALID');
});

it('rejects cancelling a trip that has already finished', function () {
    $driver = User::factory()->create();
    $trip = Trip::factory()->for($driver)->completed()->create();
    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/cancel")
        ->assertStatus(409)
        ->assertJsonPath('code', 'TRIP_STATUS_TRANSITION_INVALID');
});

it('does not let a non-driver start, complete, or cancel a trip', function () {
    $owner = User::factory()->create();
    $scheduled = Trip::factory()->for($owner)->create();
    $ongoing = Trip::factory()->for($owner)->ongoing()->create();

    $intruder = User::factory()->create();
    $token = $intruder->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$scheduled->id}/start")->assertStatus(403);
    $this->withToken($token)->postJson("/api/v1/trips/{$ongoing->id}/complete")->assertStatus(403);
    $this->withToken($token)->postJson("/api/v1/trips/{$scheduled->id}/cancel")->assertStatus(403);

    expect($scheduled->fresh()->status->value)->toBe('scheduled')
        ->and($ongoing->fresh()->status->value)->toBe('ongoing');
});

it('requires authentication for the lifecycle actions', function () {
    $trip = Trip::factory()->create();

    $this->postJson("/api/v1/trips/{$trip->id}/start")->assertStatus(401);
    $this->postJson("/api/v1/trips/{$trip->id}/complete")->assertStatus(401);
    $this->postJson("/api/v1/trips/{$trip->id}/cancel")->assertStatus(401);
});

it('returns not found for an unknown trip', function () {
    $driver = User::factory()->create();
    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/trips/999/start')
        ->assertStatus(404);
});

it('queues an SMS to every passenger when a trip is cancelled', function () {
    Queue::fake();

    $driver = User::factory()->create();
    $trip = Trip::factory()->for($driver)->create();
    $passengers = User::factory()->count(2)->create();
    $trip->passengers()->attach($passengers);

    $token = $driver->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/trips/{$trip->id}/cancel")
        ->assertOk();

    Queue::assertPushed(SendTripUpdatedSms::class, 2);

    foreach ($passengers as $passenger) {
        Queue::assertPushed(
            SendTripUpdatedSms::class,
            fn (SendTripUpdatedSms $job): bool => $job->phoneNumber === $passenger->phone_number,
        );
    }
});
