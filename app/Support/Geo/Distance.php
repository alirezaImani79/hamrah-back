<?php

namespace App\Support\Geo;

/**
 * Pure great-circle distance and bounding-box helpers used for trip matching.
 */
final class Distance
{
    /**
     * Earth's mean radius in kilometers.
     */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Great-circle distance between two coordinates, in kilometers.
     */
    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(sqrt($a));
    }

    /**
     * Lat/lng bounds enclosing the given radius, with a ~10% safety margin so
     * the result is always a strict superset of the exact haversine circle.
     *
     * @return array{minLat: float, maxLat: float, minLng: float, maxLng: float}
     */
    public static function boundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $margin = 1.1;

        // ~111 km per degree of latitude.
        $latDelta = ($radiusKm * $margin) / 111.0;
        // Longitude degrees shrink with the cosine of the latitude.
        $lngDelta = ($radiusKm * $margin) / (111.0 * cos(deg2rad($lat)));

        return [
            'minLat' => max(-90.0, $lat - $latDelta),
            'maxLat' => min(90.0, $lat + $latDelta),
            'minLng' => max(-180.0, $lng - $lngDelta),
            'maxLng' => min(180.0, $lng + $lngDelta),
        ];
    }
}
