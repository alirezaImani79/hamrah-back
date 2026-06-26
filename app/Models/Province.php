<?php

namespace App\Models;

use Database\Factories\ProvinceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An Iranian province (ostan), sourced from the typhoon-iran-cities dataset.
 */
#[Fillable(['name', 'code', 'short_code', 'status'])]
class Province extends Model
{
    /** @use HasFactory<ProvinceFactory> */
    use HasFactory;

    protected $table = 'iran_provinces';

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
     * The cities that belong to this province.
     *
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'province_id');
    }

    /**
     * Limit the query to active (selectable) provinces.
     *
     * @param  Builder<Province>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', true);
    }
}
