<?php

namespace App\Services\Trip;

use App\Enums\TripStatus;
use App\Exceptions\ApiException;
use App\Jobs\SendTripUpdatedSms;
use App\Models\Trip;
use App\Models\User;
use App\Support\ErrorCode;
use App\Support\Geo\Distance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class TripService
{
    /**
     * How far, in kilometers, a trip's origin and destination may be from the
     * passenger's requested points to count as a match.
     */
    private const RADIUS_KM = 2.0;

    /**
     * How many minutes before or after the preferred departure a trip may start.
     */
    private const WINDOW_MINUTES = 30;

    /**
     * Create a new trip for the given driver.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $driver, array $data): Trip
    {
        return $driver->trips()->create(array_merge([
            'status' => TripStatus::Scheduled,
        ], $data));
    }

    /**
     * Get the user's scheduled trips (as driver or passenger), soonest first.
     *
     * @return Collection<int, Trip>
     */
    public function upcomingForUser(User $user): Collection
    {
        return Trip::query()
            ->involving($user)
            ->scheduled()
            ->with(['user', 'vehicle'])
            ->withCount('passengers')
            ->orderBy('departure_at')
            ->get();
    }

    /**
     * Get the user's ongoing trips (as driver or passenger), most recently started first.
     *
     * @return Collection<int, Trip>
     */
    public function ongoingForUser(User $user): Collection
    {
        return Trip::query()
            ->involving($user)
            ->ongoing()
            ->with(['user', 'vehicle'])
            ->withCount('passengers')
            ->orderByDesc('started_at')
            ->get();
    }

    /**
     * Get the user's past trips (as driver or passenger), most recently active first.
     *
     * @return Collection<int, Trip>
     */
    public function historyForUser(User $user): Collection
    {
        return Trip::query()
            ->involving($user)
            ->past()
            ->with(['user', 'vehicle'])
            ->withCount('passengers')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Find upcoming trips that fit the passenger's travel request, ranked best-first.
     *
     * A trip matches when its origin and destination are each within the radius,
     * its departure falls inside the time window around the preferred time, it has
     * enough free seats, and (optionally) its driver matches the requested gender.
     * Trips the user drives or has already joined are never suggested.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Trip>
     */
    public function matching(User $user, array $data): Collection
    {
        $departureAt = Carbon::parse($data['departure_at']);
        $requested = (int) $data['requested_seats'];

        $originBox = Distance::boundingBox((float) $data['origin_lat'], (float) $data['origin_lng'], self::RADIUS_KM);
        $destinationBox = Distance::boundingBox((float) $data['destination_lat'], (float) $data['destination_lng'], self::RADIUS_KM);

        $windowStart = $departureAt->copy()->subMinutes(self::WINDOW_MINUTES);
        $windowEnd = $departureAt->copy()->addMinutes(self::WINDOW_MINUTES);

        $query = Trip::query()
            ->with('user')
            ->withCount('passengers')
            ->scheduled()
            ->where('departure_at', '>=', now())
            ->whereBetween('departure_at', [$windowStart, $windowEnd])
            ->whereBetween('origin_lat', [$originBox['minLat'], $originBox['maxLat']])
            ->whereBetween('origin_lng', [$originBox['minLng'], $originBox['maxLng']])
            ->whereBetween('destination_lat', [$destinationBox['minLat'], $destinationBox['maxLat']])
            ->whereBetween('destination_lng', [$destinationBox['minLng'], $destinationBox['maxLng']])
            ->where('user_id', '!=', $user->getKey())
            ->whereDoesntHave('passengers', fn ($passengers) => $passengers->whereKey($user->getKey()));

        if (! empty($data['driver_gender'])) {
            $query->whereHas('user', fn ($driver) => $driver->where('gender', $data['driver_gender']));
        }

        return $query->get()
            ->filter(function (Trip $trip) use ($data, $requested): bool {
                $originDistance = Distance::haversineKm(
                    (float) $trip->origin_lat,
                    (float) $trip->origin_lng,
                    (float) $data['origin_lat'],
                    (float) $data['origin_lng'],
                );
                $destinationDistance = Distance::haversineKm(
                    (float) $trip->destination_lat,
                    (float) $trip->destination_lng,
                    (float) $data['destination_lat'],
                    (float) $data['destination_lng'],
                );

                if ($originDistance > self::RADIUS_KM || $destinationDistance > self::RADIUS_KM) {
                    return false;
                }

                $available = (int) $trip->empty_seats - (int) $trip->passengers_count;

                // Tolerate being a single seat short, but never suggest a full trip.
                if ($available < max(1, $requested - 1)) {
                    return false;
                }

                $trip->origin_distance_km = round($originDistance, 2);
                $trip->destination_distance_km = round($destinationDistance, 2);
                $trip->available_seats = $available;

                return true;
            })
            ->sortBy(fn (Trip $trip) => [
                // Closest departure time first...
                abs($trip->departure_at->getTimestamp() - $departureAt->getTimestamp()),
                // ...then nearest combined pickup and dropoff distance...
                $trip->origin_distance_km + $trip->destination_distance_km,
                // ...then tightest seat fit, finally the trip id for a stable order.
                abs($trip->available_seats - $requested),
                $trip->id,
            ])
            ->values();
    }

    /**
     * Update the given trip with the provided attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Trip $trip, array $data): Trip
    {
        $trip->update($data);

        return $trip;
    }

    /**
     * Delete the given trip.
     */
    public function delete(Trip $trip): void
    {
        $trip->delete();
    }

    /**
     * Start the trip: scheduled → ongoing.
     *
     * @throws ApiException When the trip is not scheduled ({@see ErrorCode::TripStatusTransitionInvalid}).
     */
    public function start(Trip $trip): Trip
    {
        $this->ensureStatus($trip, TripStatus::Scheduled);

        $trip->update([
            'status' => TripStatus::Ongoing,
            'started_at' => now(),
        ]);

        return $trip;
    }

    /**
     * Complete the trip: ongoing → completed.
     *
     * @throws ApiException When the trip is not ongoing ({@see ErrorCode::TripStatusTransitionInvalid}).
     */
    public function complete(Trip $trip): Trip
    {
        $this->ensureStatus($trip, TripStatus::Ongoing);

        $trip->update([
            'status' => TripStatus::Completed,
            'ended_at' => now(),
        ]);

        return $trip;
    }

    /**
     * Cancel the trip: scheduled or ongoing → cancelled, notifying passengers.
     *
     * @throws ApiException When the trip can no longer be cancelled ({@see ErrorCode::TripStatusTransitionInvalid}).
     */
    public function cancel(Trip $trip): Trip
    {
        if (! in_array($trip->status, [TripStatus::Scheduled, TripStatus::Ongoing], true)) {
            throw $this->transitionException($trip);
        }

        $trip->update([
            'status' => TripStatus::Cancelled,
            'ended_at' => now(),
        ]);

        $this->notifyPassengers(
            $trip->load('passengers'),
            'The trip you joined has been cancelled by the driver.'
        );

        return $trip;
    }

    /**
     * Guard that the trip is in the expected status, else throw.
     *
     * @throws ApiException
     */
    private function ensureStatus(Trip $trip, TripStatus $status): void
    {
        if ($trip->status !== $status) {
            throw $this->transitionException($trip);
        }
    }

    /**
     * Build the exception for an illegal status transition.
     */
    private function transitionException(Trip $trip): ApiException
    {
        return new ApiException(
            errorCode: ErrorCode::TripStatusTransitionInvalid,
            message: "This trip cannot be changed because its status is {$trip->status->value}.",
            statusCode: 409,
        );
    }

    /**
     * Sign the given user up as a passenger on the trip.
     *
     * @throws ApiException When the user already joined ({@see ErrorCode::TripAlreadyJoined})
     *                      or the trip is full ({@see ErrorCode::TripFull}).
     */
    public function addPassenger(Trip $trip, User $user): void
    {
        if ($trip->passengers()->whereKey($user->getKey())->exists()) {
            throw new ApiException(
                errorCode: ErrorCode::TripAlreadyJoined,
                message: 'You have already joined this trip.',
                errors: ['trip' => ['You have already joined this trip.']],
                statusCode: 422,
            );
        }

        if ($trip->passengers()->count() >= $trip->empty_seats) {
            throw new ApiException(
                errorCode: ErrorCode::TripFull,
                message: 'This trip is already full.',
                errors: ['trip' => ['This trip is already full.']],
                statusCode: 422,
            );
        }

        $trip->passengers()->attach($user);
    }

    /**
     * Remove the given user from the trip's passengers.
     */
    public function removePassenger(Trip $trip, User $user): void
    {
        $trip->passengers()->detach($user);
    }

    /**
     * Notify every signed-up passenger that the trip details changed.
     */
    public function notifyPassengersOfUpdate(Trip $trip): void
    {
        $departure = $trip->departure_at->format('Y-m-d H:i');

        $this->notifyPassengers(
            $trip,
            "Your trip on {$departure} has been updated by the driver. Please check the app for the latest details."
        );
    }

    /**
     * Dispatch an SMS to every signed-up passenger of the trip.
     */
    private function notifyPassengers(Trip $trip, string $message): void
    {
        foreach ($trip->passengers as $passenger) {
            SendTripUpdatedSms::dispatch($passenger->phone_number, $message);
        }
    }
}
