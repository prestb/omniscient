<?php

namespace App\Support;

/**
 * LocationDistance — the backend distance primitive.
 *
 * Returns a portable Haversine SQL expression measuring the great-circle
 * distance, in KILOMETRES, from a request coordinate to a `locations` row.
 *
 * ── Why a portable expression rather than ST_Distance_Sphere ────────────────
 * `ST_Distance_Sphere` DOES exist on MariaDB 10.4.32 — verified live, and it
 * agrees with this expression to within one metre. It is nevertheless not used
 * here because its cross-engine semantics have NOT been established: the CI
 * database is MySQL 8.0 and only MariaDB is reachable in local development.
 * Every primitive below (`RADIANS`, `SIN`, `COS`, `ASIN`, `SQRT`, `POWER`,
 * `LEAST`) is a core SQL math function present on both engines and was verified
 * live on MariaDB. Phase 23 may swap the expression body for
 * `ST_Distance_Sphere` behind this same signature once CI verifies it — note
 * that `ST_Distance_Sphere` returns METRES and would need dividing by 1000.
 *
 * ── Caller contract ─────────────────────────────────────────────────────────
 * 1. INPUTS MUST ALREADY BE VALIDATED. `$latitude` and `$longitude` must be
 *    finite and within −90..90 and −180..180. Range validation lives in the
 *    request layer (see `LocationController`); it is deliberately NOT repeated
 *    here, because a second copy would drift from the first and this helper is
 *    equally callable from console commands and future search code where no
 *    HTTP validation ever ran.
 *
 * 2. ROWS WITH NULL COORDINATES MUST BE EXCLUDED BY THE CALLER:
 *
 *        $query->whereNotNull('locations.latitude')
 *              ->whereNotNull('locations.longitude');
 *
 *    Phase 22E-1 guarantees the two coordinates are both NULL or both present,
 *    but the distance to a coordinate-less Location is undefined. Without the
 *    filter a NULL row yields a NULL distance, which is falsy in a `<= ?`
 *    comparison yet would still appear in an unfiltered `selectRaw` — silently
 *    inconsistent. Excluding them is the caller's contract, not a side effect
 *    of the expression.
 *
 * ── Units and convention ────────────────────────────────────────────────────
 * Kilometres, Earth radius 6371 km — matching the only existing convention in
 * the codebase (`DirectoryMap.vue`, the browser-side haversine).
 */
final class LocationDistance
{
    /** Mean Earth radius in kilometres, matching the existing frontend value. */
    public const EARTH_RADIUS_KM = 6371;

    /**
     * The `locations` table qualifier is hardcoded rather than parameterised.
     * The directory query joins `listings`, which would make an unqualified
     * `latitude` ambiguous, and a caller-supplied table name interpolated into
     * SQL would be an injection surface for no real benefit.
     */
    private const TABLE = 'locations';

    /**
     * Haversine distance in kilometres from `($latitude, $longitude)` to the
     * `locations` row, as `[sql, bindings]`.
     *
     * Bindings are ordered `[$latitude, $latitude, $longitude]` — latitude
     * appears twice because it is used both in the latitude delta and in the
     * cosine of the *request* latitude. Coordinates are always bound, never
     * interpolated.
     *
     * @return array{0: string, 1: array<int, float>}
     */
    public static function kmExpression(float $latitude, float $longitude): array
    {
        $table = self::TABLE;

        $sql = '('
            . '2 * ' . self::EARTH_RADIUS_KM . ' * ASIN('
            . 'SQRT('
            . 'LEAST(1, '
            // Latitude delta, squared.
            . 'POWER(SIN(RADIANS(' . $table . '.latitude - ?) / 2), 2) '
            // Longitude delta squared, scaled by the cosines of both latitudes.
            . '+ COS(RADIANS(?)) * COS(RADIANS(' . $table . '.latitude)) '
            . '* POWER(SIN(RADIANS(' . $table . '.longitude - ?) / 2), 2)'
            . ')'
            . ')'
            . ')'
            . ')';

        return [$sql, [$latitude, $latitude, $longitude]];
    }
}
