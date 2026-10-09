<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 22E-1 — COORDINATE PAIR INTEGRITY.
 *
 * `locations.latitude` and `locations.longitude` are independently nullable, so
 * the database accepted a Location with only one of the two. That state cannot
 * be rendered on a map and silently degrades any future proximity work. The
 * pairing rule existed only in `LocationController` validation, which any
 * non-HTTP write path (seeder, import, console command, direct query) bypasses.
 *
 * This adds a table-level CHECK so the invariant holds regardless of caller.
 *
 * WHY A RAW STATEMENT
 *   Laravel 12's `Blueprint` exposes no `check()`/`constraint()` method
 *   (verified by reflection), so the constraint is added with `DB::statement`.
 *
 * ENGINE EVIDENCE (MariaDB 10.4.32, measured on this deployment — not assumed)
 *   - `ALTER TABLE ... ADD CONSTRAINT ... CHECK (...)`            accepted
 *   - (NULL, NULL) and (value, value)                            accepted
 *   - (value, NULL)                                              rejected, ERROR 4025
 *   - (NULL, value)                                              rejected, ERROR 4025
 *   - `ALTER TABLE ... DROP CONSTRAINT`                          works
 *   The schema already carries 13 enforced CHECK constraints
 *   (implicit `json_valid` from Laravel's `json()` columns).
 *
 * SAFETY ON A POPULATED DATABASE
 *   Adding the constraint FAILS LOUDLY if any existing row holds only one
 *   coordinate. That is deliberate: the alternative would be silently nulling
 *   or inventing coordinates. The pre-flight below reports the exact offender
 *   count and ids so the data can be corrected before re-running.
 */
return new class extends Migration
{
    private const CONSTRAINT = 'locations_coordinate_pair_check';

    public function up(): void
    {
        if (! Schema::hasTable('locations')) {
            return;
        }

        // Idempotent: never add the constraint twice.
        if ($this->constraintExists()) {
            return;
        }

        $this->assertNoPartialCoordinates();

        DB::statement(
            'ALTER TABLE `locations` ADD CONSTRAINT `' . self::CONSTRAINT . '` '
            . 'CHECK ((`latitude` IS NULL AND `longitude` IS NULL) '
            . 'OR (`latitude` IS NOT NULL AND `longitude` IS NOT NULL))'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('locations') || ! $this->constraintExists()) {
            return;
        }

        // MariaDB (local + production) uses DROP CONSTRAINT.
        // MySQL 8 — used by the CI service — historically used DROP CHECK.
        // Try the native form first and fall back, so rollback works on both.
        try {
            DB::statement('ALTER TABLE `locations` DROP CONSTRAINT `' . self::CONSTRAINT . '`');
        } catch (\Throwable $e) {
            DB::statement('ALTER TABLE `locations` DROP CHECK `' . self::CONSTRAINT . '`');
        }
    }

    /**
     * Fail with a useful message instead of a bare SQL 4025 when existing data
     * already violates the invariant. Reads only; it never rewrites a row.
     */
    private function assertNoPartialCoordinates(): void
    {
        $offenders = DB::table('locations')
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('latitude')->whereNull('longitude');
                })->orWhere(function ($inner) {
                    $inner->whereNull('latitude')->whereNotNull('longitude');
                });
            })
            ->select('id')
            ->limit(50)
            ->pluck('id')
            ->all();

        if ($offenders === []) {
            return;
        }

        throw new RuntimeException(
            'PHASE 22E-1: cannot add the coordinate-pair constraint because '
            . count($offenders) . ' location row(s) hold only one coordinate: '
            . implode(', ', $offenders)
            . '. Correct those rows (set both coordinates or neither) and re-run. '
            . 'No data was modified.'
        );
    }

    /** MariaDB reports CHECK constraints through information_schema. */
    private function constraintExists(): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'locations')
            ->where('CONSTRAINT_NAME', self::CONSTRAINT)
            ->exists();
    }
};
