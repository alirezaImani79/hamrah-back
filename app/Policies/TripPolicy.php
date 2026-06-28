<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    /**
     * Determine whether the user can view the trip.
     *
     * The driver and any signed-up passenger may view it.
     */
    public function view(User $user, Trip $trip): bool
    {
        return $this->isDriver($user, $trip)
            || $trip->passengers()->whereKey($user->getKey())->exists();
    }

    /**
     * Determine whether the user can update the trip.
     *
     * Only the driver may edit, and only before departure.
     */
    public function update(User $user, Trip $trip): bool
    {
        return $this->isDriver($user, $trip) && ! $trip->hasDeparted();
    }

    /**
     * Determine whether the user can delete the trip.
     */
    public function delete(User $user, Trip $trip): bool
    {
        return $this->isDriver($user, $trip);
    }

    /**
     * Determine whether the user can join the trip as a passenger.
     *
     * The driver cannot join their own trip, and a departed trip is closed.
     */
    public function join(User $user, Trip $trip): bool
    {
        return ! $this->isDriver($user, $trip) && ! $trip->hasDeparted();
    }

    /**
     * Determine whether the user can leave the trip.
     */
    public function leave(User $user, Trip $trip): bool
    {
        return $trip->passengers()->whereKey($user->getKey())->exists();
    }

    /**
     * Determine whether the given user drives the trip.
     */
    private function isDriver(User $user, Trip $trip): bool
    {
        return $user->getKey() === $trip->user_id;
    }
}
