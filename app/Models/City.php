<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An Iranian city (shahr), sourced from the typhoon-iran-cities dataset.
 */
#[Fillable(['name', 'province_id', 'county_id', 'sector_id', 'code', 'short_code', 'status'])]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    protected $table = 'iran_cities';

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * The province this city belongs to.
     *
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    /**
     * Limit the query to active (selectable) cities.
     *
     * @param  Builder<City>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', true);
    }
}
