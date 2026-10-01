<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 11 / WAVE 1B (correction) — `listing_id` is REQUIRED on every
 * Listing-owned child table.
 *
 * The four tables below are owned by a LISTING, never by an organization:
 *
 *   listing_services   — a service is offered BY a listing
 *   listing_images     — media belongs to the discoverable entity
 *   listing_contacts   — general (non-place) contact belongs to the listing
 *   listing_analytics  — per-listing metrics; org totals are DERIVED by summing
 *
 * Wave 1B renamed the tables and dropped `business_id`, but `listing_id`
 * remained NULLABLE because Phase 9 had already created the column and the
 * rename migration's "ensure FK" block was skipped by its own
 * `Schema::hasColumn()` guard. A nullable ownership FK permits an orphan row —
 * a child with no owner — which is not a legitimate domain state.
 *
 * This migration makes the invariant explicit at the database level. It
 * deliberately does NOT provide any fallback: no Business-based ownership, no
 * fake Listing ids, no orphan semantics.
 */
return new class extends Migration
{
    /**
     * Listing-owned child tables whose `listing_id` must be NOT NULL.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'listing_services',
        'listing_images',
        'listing_contacts',
        'listing_analytics',
    ];

    public function up(): void
    {
        // Never invent ownership. If any row has no Listing, stop and report it
        // instead of constraining (which would require deleting or guessing).
        foreach ($this->tables as $table) {
            if (!$this->hasListingId($table)) {
                continue;
            }

            $orphans = DB::table($table)->whereNull('listing_id')->count();

            if ($orphans > 0) {
                throw new \RuntimeException(
                    "Refusing to make {$table}.listing_id NOT NULL: {$orphans} row(s) "
                    . 'have a NULL listing_id. Their Listing ownership must be resolved '
                    . 'explicitly first — this migration will not guess an owner.'
                );
            }
        }

        foreach ($this->tables as $table) {
            if (!$this->hasListingId($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                // MODIFY only the nullability; the column type stays
                // `bigint unsigned` so the existing FK to listings.id remains
                // valid and enforced.
                $blueprint->unsignedBigInteger('listing_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!$this->hasListingId($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('listing_id')->nullable()->change();
            });
        }
    }

    private function hasListingId(string $table): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'listing_id');
    }
};
