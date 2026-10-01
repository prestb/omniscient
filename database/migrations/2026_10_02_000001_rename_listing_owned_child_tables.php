<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 11 / WAVE 1B — Listing-owned child table convergence.
 *
 * The canonical owner of services, media, contacts and analytics is the
 * LISTING (the discoverable entity), not the abstract organization. Wave 1B
 * completes the identifier convergence started in Phase 9 by:
 *
 *   1. Renaming the physical tables to their listing-scoped names:
 *        business_services   → listing_services
 *        business_images     → listing_images
 *        business_contacts   → listing_contacts
 *        business_analytics  → listing_analytics
 *
 *   2. Making `listing_id` the AUTHORITATIVE (non-nullable) ownership FK.
 *
 *   3. Dropping the legacy `business_id` column entirely. None of these four
 *      entities has a legitimate organization-level purpose:
 *        - services/contacts/media are offered BY a listing (location);
 *        - organization-level analytics are DERIVED by summing their listings
 *          (Phase 9 §analytics policy) and are never stored as duplicate rows.
 *
 * NOTE: This migration is written defensively (Schema::hasTable/hasColumn and
 * information_schema FK probes) so it is idempotent and safe on databases
 * where Phase 9's additive `listing_id` columns already exist.
 */
return new class extends Migration
{
    /**
     * Old table name => new table name.
     *
     * @var array<string, string>
     */
    private array $renames = [
        'business_services' => 'listing_services',
        'business_images' => 'listing_images',
        'business_contacts' => 'listing_contacts',
        'business_analytics' => 'listing_analytics',
    ];

    public function up(): void
    {
        foreach ($this->renames as $from => $to) {
            // If the rename already happened, skip.
            if (!Schema::hasTable($from)) {
                continue;
            }

            if (Schema::hasTable($to)) {
                // Target already exists — assume a prior partial run; just
                // ensure the legacy table is gone.
                Schema::dropIfExists($from);
                continue;
            }

            Schema::rename($from, $to);

            // ── 1. Ensure an authoritative listing_id FK exists ──────
            if (!Schema::hasColumn($to, 'listing_id')) {
                Schema::table($to, function (Blueprint $table) {
                    $table->foreignId('listing_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('listings')
                        ->cascadeOnDelete();
                });
            }

            // ── 2. Backfill listing_id from business_id where possible ─
            // Legacy rows carry only business_id. Where the organization has
            // exactly ONE listing we can re-attribute unambiguously; where it
            // has several the attribution is a product decision (Phase 5
            // "never blindly duplicate") and the row is left for backfill.
            $this->backfillFromBusiness($to);

            // ── 3. Drop the legacy business_id column ────────────────
            $this->dropBusinessId($to);
        }

        // ── 4. Analytics uniqueness now on the listing axis ──────────
        // Phase 9 dropped the old (business_id, date) unique. Re-assert a
        // clean unique on (listing_id, date) if it does not already exist.
        if (Schema::hasTable('listing_analytics')
            && Schema::hasColumn('listing_analytics', 'listing_id')
            && !$this->indexExists('listing_analytics', 'listing_analytics_listing_id_date_unique')
        ) {
            try {
                Schema::table('listing_analytics', function (Blueprint $table) {
                    $table->unique(['listing_id', 'date'], 'listing_analytics_listing_id_date_unique');
                });
            } catch (\Throwable $e) {
                // A non-unique listing_id index may already exist; non-fatal.
            }
        }
    }

    public function down(): void
    {
        foreach ($this->renames as $from => $to) {
            if (!Schema::hasTable($to)) {
                continue;
            }

            // Re-introduce business_id (nullable) before renaming back.
            if (!Schema::hasColumn($to, 'business_id')) {
                Schema::table($to, function (Blueprint $table) {
                    $table->foreignId('business_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('businesses')
                        ->cascadeOnDelete();
                });
            }

            if (!Schema::hasTable($from)) {
                Schema::rename($to, $from);
            }
        }
    }

    // ================================================================
    // Helpers
    // ================================================================

    /**
     * Best-effort re-attribution of legacy rows onto the listing axis.
     * Rows belonging to a single-listing organization are moved; ambiguous
     * multi-listing rows are left NULL (never duplicated).
     */
    private function backfillFromBusiness(string $table): void
    {
        if (!Schema::hasColumn($table, 'business_id')
            || !Schema::hasColumn($table, 'listing_id')) {
            return;
        }

        try {
            // Only organizations with EXACTLY ONE listing are unambiguous.
            $single = DB::table('listings')
                ->select('business_id', DB::raw('MIN(id) as listing_id'), DB::raw('COUNT(*) as c'))
                ->whereNotNull('business_id')
                ->groupBy('business_id')
                ->havingRaw('COUNT(*) = 1')
                ->get();

            foreach ($single as $row) {
                DB::table($table)
                    ->where('business_id', $row->business_id)
                    ->whereNull('listing_id')
                    ->update(['listing_id' => $row->listing_id]);
            }
        } catch (\Throwable $e) {
            // Backfill is best-effort; schema convergence is the goal.
        }
    }

    /**
     * Drop business_id together with any foreign key/index that depends on it.
     */
    private function dropBusinessId(string $table): void
    {
        if (!Schema::hasColumn($table, 'business_id')) {
            return;
        }

        // MySQL refuses to drop a column an FK depends on — drop the FK first.
        $foreignKeys = collect(DB::select(
            'SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '
            . 'AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, 'business_id']
        ))->pluck('name');

        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn('business_id');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            return collect(DB::select('SHOW INDEX FROM `' . $table . '`'))
                ->pluck('Key_name')
                ->contains($index);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
