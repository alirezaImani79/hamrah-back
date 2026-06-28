<?php

use App\Contracts\SmsSender;
use App\Jobs\SendTripUpdatedSms;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Sms\FakeSmsSender;
use Illuminate\Support\Facades\Queue;

function tripPayload(array $overrides = []): array
{
    return array_merge([
        'origin_lat' => 35.6892,
        'origin_lng' => 51.3890,
        'destination_lat' => 32.6539,
        'destination_lng' => 51.6660,
        'departure_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'empty_seats' => 3,
        'trunk_empty' => true,
    ], $overrides);
}

it('lists only the trips the authenticated user drives', function () {
    $user = User::factory()->create();
    Trip::factory()->count(2)->for($user)->create();
    Trip::factory()->create(); // belongs to someone else

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/trips')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

it('creates a trip for the authenticated user', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/trips', tripPayload(['vehicle_id' => $vehicle->id]))
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.vehicle_id', $vehicle->id)
        ->assertJsonPath('data.origin.lat', 35.6892)
        ->assertJsonPath('data.empty_seats', 3)
        ->assertJsonPath('data.trunk_empty', true)
        ->assertJsonPath('data.passengers_count', 0);

    $this->assertDatabaseHas('trips', [
        'user_id' => $user->id,
        'vehicle_id' => $vehicle->id,
        'empty_seats' => 3,
    ]);
});

it('validates required fields when creating a trip', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/trips', [])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors([
            'vehicle_id', 'origin_lat', 'origin_lng', 'destination_lat',
            'destination_lng', 'departure_at', 'empty_seats', 'trunk_empty',
        ]);
});

it('rejects a vehicle that belongs to another user', function () {
    $user = User::factory()->create();
    $othersVehicle = Vehicle::factory()->create(); // owned by someone else
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/trips', tripPayload(['vehicle_id' => $othersVehicle->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['vehicle_id']);
});

it('rejects a departure time in the past', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/trips', tripPayload([
        'vehicle_id' => $vehicle->id,
        'departure_at' => now()->subDay()->format('Y-m-d H:i:s'),
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['departure_at']);
});

it('shows a trip the user drives', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->for($user)->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson("/api/v1/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $trip->id);
});

it('lets a passenger view a trip they joined', function () {
    $trip = Trip::factory()->create();
    $passenger = User::factory()->create();
    $trip->passengers()->attach($passenger);
    $token = $passenger->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson("/api/v1/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $trip->id);
});

it('forbids viewing a trip the user is unrelated to', function () {
    $trip = Trip::factory()->create();
    $stranger = User::factory()->create();
    $token = $stranger->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson("/api/v1/trips/{$trip->id}")
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('updates a trip', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->for($user)->create(['empty_seats' => 3]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->putJson("/api/v1/trips/{$trip->id}", ['empty_seats' => 1, 'trunk_empty' => false])
        ->assertOk()
        ->assertJsonPath('data.empty_seats', 1)
        ->assertJsonPath('data.trunk_empty', false);

    expect($trip->fresh()->empty_seats)->toBe(1);
});

it('cannot update a trip that has already departed', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->for($user)->departed()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->putJson("/api/v1/trips/{$trip->id}", ['empty_seats' => 1])
        ->assertStatus(403);
});

it('does not allow a non-driver to update or delete a trip', function () {
    $owner = User::factory()->create();
    $trip = Trip::factory()->for($owner)->create();

    $intruder = User::factory()->create();
    $token = $intruder->createToken('test')->plainTextToken;

    $this->withToken($token)->putJson("/api/v1/trips/{$trip->id}", ['empty_seats' => 1])
        ->assertStatus(403);

    $this->withToken($token)->deleteJson("/api/v1/trips/{$trip->id}")
        ->assertStatus(403);

    $this->assertDatabaseHas('trips', ['id' => $trip->id]);
});

it('deletes a trip', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->for($user)->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->deleteJson("/api/v1/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
});

it('queues an SMS to every passenger when a trip is updated', function () {
    Queue::fake();

    $user = User::factory()->create();
    $trip = Trip::factory()->for($user)->create();
    $passengers = User::factory()->count(2)->create();
    $trip->passengers()->attach($passengers);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->putJson("/api/v1/trips/{$trip->id}", ['empty_seats' => 2])
        ->assertOk();

    Queue::assertPushed(SendTripUpdatedSms::class, 2);

    foreach ($passengers as $passenger) {
        Queue::assertPushed(
            SendTripUpdatedSms::class,
            fn (SendTripUpdatedSms $job): bool => $job->phoneNumber === $passenger->phone_number,
        );
    }
});

it('delivers the trip update message through the sms sender when the job runs', function () {
    $sms = new FakeSmsSender;
    $this->app->instance(SmsSender::class, $sms);

    (new SendTripUpdatedSms('+15551230000', 'Your trip changed.'))->handle($sms);

    expect($sms->lastMessageTo('+15551230000'))->toBe('Your trip changed.');
});

it('rejects unauthenticated trip requests', function () {
    $this->getJson('/api/v1/trips')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});
