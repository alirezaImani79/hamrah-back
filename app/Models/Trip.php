<?php

namespace App\Models;

use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A trip a driver offers from an origin to a destination using one of their vehicles.
 */
#[Fillable([
    'user_id',
    'vehicle_id',
    'origin_lat',
    'origin_lng',
    'destination_lat',
    'destination_lng',
    'departure_at',
    'empty_seats',
    'trunk_empty',
])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'origin_lat' => 'decimal:7',
            'origin_lng' => 'decimal:7',
            'destination_lat' => 'decimal:7',
            'destination_lng' => 'decimal:7',
            'departure_at' => 'datetime',
            'empty_seats' => 'integer',
            'trunk_empty' => 'boolean',
        ];
    }

    /**
     * The driver who owns this trip.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The vehicle used for this trip.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The users who have signed up as passengers for this trip.
     *
     * @return BelongsToMany<User, $this>
     */
    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trip_passengers')->withTimestamps();
    }

    /**
     * Determine if the trip's departure time has already passed.
     */
    public function hasDeparted(): bool
    {
        return $this->departure_at->isPast();
    }

    /**
     * Scope to trips the given user is part of, as the driver or a passenger.
     *
     * @param  Builder<Trip>  $query
     */
    public function scopeInvolving(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->where('user_id', $user->getKey())
                ->orWhereHas('passengers', function (Builder $passengers) use ($user): void {
                    $passengers->whereKey($user->getKey());
                });
        });
    }

    /**
     * Scope to trips that have not departed yet.
     *
     * @param  Builder<Trip>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('departure_at', '>=', now());
    }

    /**
     * Scope to trips whose departure time has already passed.
     *
     * @param  Builder<Trip>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->where('departure_at', '<', now());
    }
}
