<?php

namespace App\Services\Trip;

use App\Jobs\SendTripUpdatedSms;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TripService
{
    /**
     * Create a new trip for the given driver.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $driver, array $data): Trip
    {
        return $driver->trips()->create($data);
    }

    /**
     * Get the user's upcoming trips (as driver or passenger), soonest first.
     *
     * @return Collection<int, Trip>
     */
    public function upcomingForUser(User $user): Collection
    {
        return Trip::query()
            ->involving($user)
            ->upcoming()
            ->withCount('passengers')
            ->orderBy('departure_at')
            ->get();
    }

    /**
     * Get the user's past trips (as driver or passenger), most recent first.
     *
     * @return Collection<int, Trip>
     */
    public function historyForUser(User $user): Collection
    {
        return Trip::query()
            ->involving($user)
            ->past()
            ->withCount('passengers')
            ->orderByDesc('departure_at')
            ->get();
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
     * Sign the given user up as a passenger on the trip.
     *
     * @throws ValidationException When the trip is full or the user already joined.
     */
    public function addPassenger(Trip $trip, User $user): void
    {
        if ($trip->passengers()->whereKey($user->getKey())->exists()) {
            throw ValidationException::withMessages([
                'trip' => 'You have already joined this trip.',
            ]);
        }

        if ($trip->passengers()->count() >= $trip->empty_seats) {
            throw ValidationException::withMessages([
                'trip' => 'This trip is already full.',
            ]);
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
        $message = "Your trip on {$departure} has been updated by the driver. Please check the app for the latest details.";

        foreach ($trip->passengers as $passenger) {
            SendTripUpdatedSms::dispatch($passenger->phone_number, $message);
        }
    }
}
