<?php

namespace App\Policies;

use App\Enums\TripStatus;
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
     * Only the driver may edit, and only while the trip is still scheduled.
     */
    public function update(User $user, Trip $trip): bool
    {
        return $this->isDriver($user, $trip) && $trip->status === TripStatus::Scheduled;
    }

    /**
     * Determine whether the user can delete the trip.
     *
     * A trip can be hard-deleted only while it is still scheduled; once it has
     * started, the driver must cancel it instead so passengers keep a record.
     */
    public function delete(User $user, Trip $trip): bool
    {
        return $this->isDriver($user, $trip) && $trip->status === TripStatus::Scheduled;
    }

    /**
     * Determine whether the user can join the trip as a passenger.
     *
     * The driver cannot join their own trip, and only scheduled trips are open.
     */
    public function join(User $user, Trip $trip): bool
    {
        return ! $this->isDriver($user, $trip) && $trip->status === TripStatus::Scheduled;
    }

    /**
     * Determine whether the user can leave the trip.
     *
     * Passengers may drop out only before the trip starts.
     */
    public function leave(User $user, Trip $trip): bool
    {
        return $trip->passengers()->whereKey($user->getKey())->exists()
            && $trip->status === TripStatus::Scheduled;
    }

    /**
     * Determine whether the user can start the trip.
     *
     * Ownership only — the scheduled → ongoing transition itself is enforced by
     * the service, which raises a `409` when the trip is in the wrong status.
     */
    public function start(User $user, Trip $trip): bool
    {
        return $this->isDriver($user, $trip);
    }

    /**
     * Determine whether the user can complete the trip.
     *
     * Ownership only — the ongoing → completed transition is enforced by the service.
     */
    public function complete(User $user, Trip $trip): bool
    {
        return $this->isDriver($user, $trip);
    }

    /**
     * Determine whether the user can cancel the trip.
     *
     * Ownership only — the allowed source statuses are enforced by the service.
     */
    public function cancel(User $user, Trip $trip): bool
    {
        return $this->isDriver($user, $trip);
    }

    /**
     * Determine whether the given user drives the trip.
     */
    private function isDriver(User $user, Trip $trip): bool
    {
        return $user->getKey() === $trip->user_id;
    }
}
