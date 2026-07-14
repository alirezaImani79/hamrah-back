<?php

use App\Enums\Gender;
use App\Models\Trip;
use App\Models\User;

function matchPayload(array $overrides = []): array
{
    return array_merge([
        'origin_lat' => 35.6892,
        'origin_lng' => 51.3890,
        'destination_lat' => 32.6539,
        'destination_lng' => 51.6660,
        'departure_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'requested_seats' => 2,
    ], $overrides);
}

/**
 * Create a trip that matches the default matchPayload() request, owned by $driver.
 */
function matchableTrip(User $driver, array $overrides = []): Trip
{
    return Trip::factory()->for($driver)->create(array_merge([
        'origin_lat' => 35.6892,
        'origin_lng' => 51.3890,
        'destination_lat' => 32.6539,
        'destination_lng' => 51.6660,
        'departure_at' => now()->addDay(),
        'empty_seats' => 3,
    ], $overrides));
}

it('returns matching trips ranked best-first with fit details', function () {
    $preferred = now()->addDay();
    $user = User::factory()->create();
    $driver = User::factory()->create();

    // Slightly off the request points so the distances are non-zero.
    $trip = matchableTrip($driver, [
        'departure_at' => $preferred,
        'origin_lat' => 35.6892 + 0.002,
        'destination_lat' => 32.6539 + 0.003,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $trip->id)
        ->assertJsonPath('data.0.available_seats', 3)
        ->assertJsonPath('data.0.role', 'passenger');

    expect($response->json('data.0.origin_distance_km'))->toBeGreaterThan(0.0)
        ->and($response->json('data.0.destination_distance_km'))->toBeGreaterThan(0.0);
});

it('excludes a trip whose origin is farther than 2 km', function () {
    $preferred = now()->addDay();
    matchableTrip(User::factory()->create(), [
        'departure_at' => $preferred,
        'origin_lat' => 35.6892 + 0.05, // ~5.5 km away
    ]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('excludes a trip whose destination is farther than 2 km', function () {
    $preferred = now()->addDay();
    matchableTrip(User::factory()->create(), [
        'departure_at' => $preferred,
        'destination_lat' => 32.6539 + 0.05, // ~5.5 km away
    ]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('applies the exact haversine filter to trips inside the bounding box', function () {
    $preferred = now()->addDay();

    // Inside the 2 km bounding box, but ~2.25 km by great-circle distance.
    matchableTrip(User::factory()->create(), [
        'departure_at' => $preferred,
        'origin_lat' => 35.6892 + 0.012,
        'origin_lng' => 51.3890 + 0.020,
    ]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('includes trips within 30 minutes and excludes those beyond it', function () {
    $preferred = now()->addDay();
    $inside = matchableTrip(User::factory()->create(), ['departure_at' => $preferred->copy()->addMinutes(29)]);
    matchableTrip(User::factory()->create(), ['departure_at' => $preferred->copy()->addMinutes(31)]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inside->id);
});

it('includes a trip that is one seat short of the request', function () {
    $preferred = now()->addDay();
    $driver = User::factory()->create();
    $trip = matchableTrip($driver, ['departure_at' => $preferred, 'empty_seats' => 3]);
    $trip->passengers()->attach(User::factory()->create()); // available drops to 2

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload([
            'departure_at' => $preferred->format('Y-m-d H:i:s'),
            'requested_seats' => 3,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $trip->id)
        ->assertJsonPath('data.0.available_seats', 2);
});

it('excludes a trip that is more than one seat short of the request', function () {
    $preferred = now()->addDay();
    $driver = User::factory()->create();
    $trip = matchableTrip($driver, ['departure_at' => $preferred, 'empty_seats' => 3]);
    $trip->passengers()->attach(User::factory()->count(2)->create()); // available drops to 1

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload([
            'departure_at' => $preferred->format('Y-m-d H:i:s'),
            'requested_seats' => 3,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('includes a trip with three free seats when four are requested', function () {
    $preferred = now()->addDay();
    $driver = User::factory()->create();
    $trip = matchableTrip($driver, ['departure_at' => $preferred, 'empty_seats' => 4]);
    $trip->passengers()->attach(User::factory()->create()); // available drops to 3

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload([
            'departure_at' => $preferred->format('Y-m-d H:i:s'),
            'requested_seats' => 4,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $trip->id)
        ->assertJsonPath('data.0.available_seats', 3);
});

it('never suggests a full trip', function () {
    $preferred = now()->addDay();
    $driver = User::factory()->create();
    $trip = matchableTrip($driver, ['departure_at' => $preferred, 'empty_seats' => 2]);
    $trip->passengers()->attach(User::factory()->count(2)->create()); // available drops to 0

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload([
            'departure_at' => $preferred->format('Y-m-d H:i:s'),
            'requested_seats' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filters by the requested driver gender when provided', function () {
    $preferred = now()->addDay();
    $femaleTrip = matchableTrip(User::factory()->create(['gender' => Gender::Female]), ['departure_at' => $preferred]);
    matchableTrip(User::factory()->create(['gender' => Gender::Male]), ['departure_at' => $preferred]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload([
            'departure_at' => $preferred->format('Y-m-d H:i:s'),
            'driver_gender' => 'female',
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $femaleTrip->id);
});

it('ignores the driver gender when no preference is given', function () {
    $preferred = now()->addDay();
    matchableTrip(User::factory()->create(['gender' => Gender::Female]), ['departure_at' => $preferred]);
    matchableTrip(User::factory()->create(['gender' => Gender::Male]), ['departure_at' => $preferred]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('does not suggest a trip the user drives', function () {
    $preferred = now()->addDay();
    $user = User::factory()->create();
    matchableTrip($user, ['departure_at' => $preferred]); // the user is the driver

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('does not suggest a trip the user has already joined', function () {
    $preferred = now()->addDay();
    $user = User::factory()->create();
    $trip = matchableTrip(User::factory()->create(), ['departure_at' => $preferred]);
    $trip->passengers()->attach($user);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('does not suggest a trip that has already departed even when it is inside the time window', function () {
    $preferred = now()->addMinutes(20); // window reaches [now-10, now+50]
    matchableTrip(User::factory()->create(), ['departure_at' => now()->subMinutes(5)]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns an empty list when nothing matches', function () {
    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload())
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

it('ranks otherwise-equal matches by closeness to the preferred departure time', function () {
    $preferred = now()->addDay();
    $soon = matchableTrip(User::factory()->create(), ['departure_at' => $preferred->copy()->addMinutes(5)]);
    $mid = matchableTrip(User::factory()->create(), ['departure_at' => $preferred->copy()->addMinutes(10)]);
    $late = matchableTrip(User::factory()->create(), ['departure_at' => $preferred->copy()->addMinutes(20)]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $soon->id)
        ->assertJsonPath('data.1.id', $mid->id)
        ->assertJsonPath('data.2.id', $late->id);
});

it('breaks departure-time ties by combined pickup and dropoff distance', function () {
    $preferred = now()->addDay();
    $nearer = matchableTrip(User::factory()->create(), [
        'departure_at' => $preferred,
        'origin_lat' => 35.6892 + 0.002, // ~0.22 km
    ]);
    $further = matchableTrip(User::factory()->create(), [
        'departure_at' => $preferred,
        'origin_lat' => 35.6892 + 0.010, // ~1.11 km
    ]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => $preferred->format('Y-m-d H:i:s')]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $nearer->id)
        ->assertJsonPath('data.1.id', $further->id);
});

it('requires authentication', function () {
    $this->postJson('/api/v1/trips/match', matchPayload())->assertStatus(401);
});

it('validates the requested seat count', function ($seats) {
    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['requested_seats' => $seats]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['requested_seats']);
})->with([0, 5]);

it('rejects invalid coordinates, gender, and a past departure time', function () {
    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['origin_lat' => 91]))
        ->assertStatus(422)->assertJsonValidationErrors(['origin_lat']);

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['destination_lng' => 181]))
        ->assertStatus(422)->assertJsonValidationErrors(['destination_lng']);

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['driver_gender' => 'other']))
        ->assertStatus(422)->assertJsonValidationErrors(['driver_gender']);

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', matchPayload(['departure_at' => now()->subDay()->format('Y-m-d H:i:s')]))
        ->assertStatus(422)->assertJsonValidationErrors(['departure_at']);
});

it('requires all mandatory fields', function () {
    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/trips/match', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'origin_lat', 'origin_lng', 'destination_lat', 'destination_lng',
            'departure_at', 'requested_seats',
        ]);
});
