# Phase 22E — Geospatial Foundation

**Status: DELIVERED (22E-1 … 22E-4).**

> This document was created during **Phase 23D** as part of a repository
> reconciliation. Phase 22E had already shipped in code under the labels
> `PHASE 22E-1` … `PHASE 22E-4`, but had **no document**, which is why the
> roadmap still listed it as future work. Nothing here is a plan; it records
> what exists.

## Delivered slices

| Slice | Delivery | Commit |
|---|---|---|
| 22E-1 | Coordinate-pair integrity | `dac571d` |
| 22E-2 | Location distance primitive | `88c1171` |
| 22E-3 | Server-side radius filtering | `b230dbd` |
| 22E-4 | Client-side radius authority removed | `5369b19` |

---

## 22E-1 — Coordinate-pair integrity

**Invariant: `latitude` and `longitude` are both NULL or both non-NULL.** There
is no third state.

Two layers enforce it, and the database one exists because the request layer is
not the only writer:

* **Database** — `locations_coordinate_pair_check`, added by
  `database/migrations/2026_10_08_230000_add_coordinate_pair_check_to_locations_table.php`:

  ```sql
  ALTER TABLE `locations` ADD CONSTRAINT `locations_coordinate_pair_check`
  CHECK ((`latitude` IS NULL AND `longitude` IS NULL)
      OR (`latitude` IS NOT NULL AND `longitude` IS NOT NULL))
  ```

  The migration is idempotent (`constraintExists()`), and its `up()` refuses
  loudly — listing the offending row ids, modifying nothing — if a populated
  database already violates the invariant. `down()` handles both engines:
  MariaDB's `DROP CONSTRAINT` with a MySQL 8 `DROP CHECK` fallback.
  Engine behaviour was **measured** on MariaDB 10.4.32 rather than assumed:
  `(NULL, NULL)` and `(value, value)` accepted; `(value, NULL)` and
  `(NULL, value)` rejected with `ERROR 4025`.

* **Request validation** — `app/Http/Controllers/Owner/LocationController.php`:

  ```php
  'latitude'  => ['nullable', 'numeric', 'between:-90,90',  'required_with:longitude'],
  'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
  ```

  Valid ranges are therefore **−90…90** and **−180…180**. Partial coordinates
  are rejected with an explicit message ("Latitude and longitude must be
  provided together."), and the same rules guard the map-origin request in
  `DirectoryController::validatedMapOrigin()` (`between:-90,90` /
  `between:-180,180`, `required`).

Columns are `decimal(10,8)` and `decimal(11,8)`, both nullable, declared in
`2026_08_31_000007_create_locations_table.php`.

## 22E-2 — Distance primitive

`app/Support/LocationDistance.php` is the single backend distance primitive.
`kmExpression(float $latitude, float $longitude): array` returns
`[sql, bindings]` for a Haversine great-circle distance.

* **Units: kilometres**, `EARTH_RADIUS_KM = 6371` — matching the convention the
  former browser-side calculation used.
* **Every coordinate is bound, never interpolated.** Bindings are ordered
  `[$latitude, $latitude, $longitude]`; latitude appears twice because it is used
  both for the latitude delta and for the cosine of the *request* latitude. A
  test asserts that order and asserts the literals never appear in the SQL.
* **`LEAST(1, …)` clamps the `ASIN` argument**, so floating-point drift can
  never produce `NULL` from `ASIN` out of domain.
* **It is deliberately NOT `ST_Distance_Sphere`.** The docblock records that
  MariaDB's `ST_Distance_Sphere` exists and agrees to within one metre, but that
  its **cross-engine semantics were not established** — local development is
  MariaDB while CI runs MySQL 8.0. Only core math functions (`RADIANS`, `SIN`,
  `COS`, `ASIN`, `SQRT`, `POWER`, `LEAST`) are used, all present on both.
  Swapping the expression body behind this same signature remains possible;
  note `ST_Distance_Sphere` returns **metres** and would need dividing by 1000.
* **Caller contract, deliberately not repeated inside the helper:** inputs must
  already be validated, and rows with NULL coordinates must be excluded by the
  caller (`whereNotNull`). The helper is equally callable from console commands
  where no HTTP validation ever ran, so duplicating range checks here would
  drift from the request layer.

## 22E-3 — Server-side radius filtering

`app/Http/Controllers/Public/DirectoryController.php`, **map mode only**
(`if ($viewMode === 'map')`):

```php
$origin = $this->validatedMapOrigin($request);

if ($origin !== null) {
    [$originLat, $originLng, $radiusKm] = $origin;
    [$distanceSql, $distanceBindings] = LocationDistance::kmExpression($originLat, $originLng);

    $query->whereHas('location', function ($q) use ($distanceSql, $distanceBindings, $radiusKm) {
        $q->whereNotNull('locations.latitude')
          ->whereNotNull('locations.longitude');
        $q->whereRaw($distanceSql . ' <= ?', array_merge($distanceBindings, [$radiusKm]));
    });
}
```

* The cull runs **in SQL**, in the request path — not in the browser.
* The path is **Listing → Location** (`whereHas('location')`), consistent with
  the canonical `Listing → Location (0..1)` ownership model.
* The comparison is `<= radius`, i.e. **inclusive**.
* **Listings without a Location are excluded** whenever a radius is active:
  `whereHas` requires a Location, and the anchor clause additionally requires
  both coordinates. Map mode also post-filters to records that actually carry a
  coordinate pair.
* **Partial coordinates are impossible**, so no half-populated row can reach
  the predicate (22E-1).
* Supported radii are the ones the map UI offers: **1, 5, 10, 25, 50 km**.
  An unsupported radius, an out-of-range latitude/longitude, a radius without
  an origin, and a single coordinate are all rejected through the standard
  validation channel. Supplying nothing radius-related returns `null`, which
  leaves map mode behaving exactly as before.
* **List mode is deliberately unaffected** by radius parameters (asserted by a
  test). Radius narrowing is a map-mode capability.

## 22E-4 — Client authority removal

The browser used to re-filter the server's response with its own Haversine, so
two authorities could disagree and the client could silently drop rows the
server had deliberately included. It no longer does:

* `resources/js/Components/Public/DirectoryMap.vue` now renders
  `props.businesses` directly (`visibleBusinesses = computed(() => props.businesses)`);
  the only surviving mention of Haversine is a comment recording the removal.
* The selected radius is URL state owned by `Directory.vue` and is carried
  across the list ↔ map switch, so the server receives it on every request.

**The server is the single radius authority.**

---

## Verification

| Suite | Result |
|---|---|
| `tests/Feature/Location/LocationDistanceTest.php` | 6 passed (28 assertions) |
| `tests/Feature/Location/LocationCoordinatePairConstraintTest.php` | 6 passed (15 assertions) |
| `tests/Feature/Location/DirectoryMapRadiusTest.php` | 11 passed (31 assertions) |
| `tests/Feature/Location` (whole suite) | 126 passed (362 assertions) |

Ownership and authorization were not disturbed by any of this: Location
ownership, lifecycle, concurrency and business-deletion integrity are covered by
the same suite and pass.

The radius tests cover: inside the radius included; outside excluded; a listing
without coordinates excluded while a radius is active; out-of-range latitude and
longitude rejected; unsupported radius rejected; one coordinate without the
other rejected; radius without origin rejected; existing filters still applying
alongside the radius; map mode without a radius unchanged; list mode unaffected.

---

## Current limitations

These are **known and accepted for the 22E contract, not defects.** They are
recorded so the next phase can decide deliberately rather than rediscover them.

1. **No distance ordering.** Distance is used to *filter*, never to *sort*.
   There is no `nearest`/`distance` entry in `DiscoverySort`, and no `orderBy`
   on distance anywhere. "Closest first" does not exist.
2. **No radius filtering on `/search`.** Geographic narrowing exists only on
   `/directory` in map mode. The Meilisearch-backed `/search` page has no
   geographic capability.
3. **No bounding-box prefilter.** The predicate applies the Haversine function
   to every candidate row, so map-with-radius is a function-scan rather than an
   index range scan.
4. **The single-column `latitude` / `longitude` indexes do not accelerate the
   Haversine predicate.** They exist (declared in the original `locations`
   migration), but a B-tree index cannot serve a predicate that wraps its column
   in a function. They remain useful for equality/range lookups on a coordinate,
   which is a different question.
5. **No Meilisearch `_geo`.** There is no `_geo` field, `geoRadius` or
   `geoBoundingBox` anywhere. The `proximity` string in `ConfigureMeilisearch`
   is Meilisearch's *textual* ranking rule, not geography.
6. **MySQL 8 execution of the Haversine SQL is unverified from CI.** Local
   development is MariaDB 10.4.32; CI runs MySQL 8.0. The expression uses only
   core math functions on the strength of that argument, not of a CI
   observation.
7. **No exact-boundary test.** The predicate is inclusive (`<=`), and tests
   cover clearly-inside and clearly-outside points, but a listing sitting
   exactly on the radius is not asserted.
8. **Coordinate provenance and geocoding do not exist.** Coordinates are
   operator-entered; there is no provider, no reverse geocoding and no "where
   did this coordinate come from" field.
