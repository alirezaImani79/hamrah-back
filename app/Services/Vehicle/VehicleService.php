<?php

namespace App\Services\Vehicle;

use App\Models\User;
use App\Models\Vehicle;

class VehicleService
{
    /**
     * Attach a new vehicle to the given user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Vehicle
    {
        return $user->vehicles()->create($data);
    }

    /**
     * Update the given vehicle with the provided attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $vehicle->update($data);

        return $vehicle;
    }

    /**
     * Detach (delete) the given vehicle from its owner.
     */
    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }
}
