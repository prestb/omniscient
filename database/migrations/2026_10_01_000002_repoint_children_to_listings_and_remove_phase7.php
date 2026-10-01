<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 9 — child ownership convergence + removal of Phase 6/7 experiments.
 *
 * ── Additive part ────────────────────────────────────────────────────
 * Adds a nullable `listing_id` to every discoverable-child table so a
 * Listing can own its services / media / contacts / reviews / analytics /
 * leads / favorites / coupons. Existing `business_id` columns are KEPT as
 * an organization pointer (see PHASE 9 doc §"transitional dual-FK"), but
 * `listing_id` is the AUTHORITATIVE listing-scope FK going forward.
 *
 * ── Destructive part (authorised: no valuable production data) ───────
 * Drops the Phase 6/7 Branch-as-Listing experiments that Phase 8 rejected:
 *   - `branch_id` on business_services / business_images / business_contacts
 *     / reviews / business_analytics / coupons
 *   - the `branch_category` pivot
 *   - `branches.slug` (Branch is a Location, not a discoverable entity)
 *   - `businesses.listing_type` (Listing now owns type)
 */
return new class extends Migration
{
    /**
     * Child tables that gain a nullable, listing-scoped foreign key.
     *
     * @var array<int, string>
     */
    private array $listingChildren = [
        'business_services',
        'business_images',
        'business_contacts',
        'reviews',
        'business_analytics',
        'leads',
        'coupons',
    ];

    /**
     * Tables that still carried the Phase 7 `branch_id` experiment.
     *
     * @var array<int, string>
     */
    private array $phase7BranchIdTables = [
        'business_services',
        'business_images',
        'business_contacts',
        'reviews',
        'business_analytics',
        'coupons',
    ];

    public function up(): void
    {
        // ── 1. Add listing_id to child tables ────────────────────────
        foreach ($this->listingChildren as $table) {
            if (Schema::hasColumn($table, 'listing_id')) {
                continue; // idempotent
            }

            Schema::table($table, function (Blueprint $blueprint) {
                // `constrained()` creates both the FK and its supporting index.
                $blueprint->foreignId('listing_id')
                    ->nullable()
                    ->after('business_id')
                    ->constrained('listings')
                    ->cascadeOnDelete();
            });
        }

        // ── 2. listing_categories pivot (replaces branch_category) ───
        if (!Schema::hasTable('listing_categories')) {
            Schema::create('listing_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['listing_id', 'category_id']);
                $table->index('listing_id');
                $table->index('category_id');
            });
        }

        // ── 3. favorites: move from business_id to listing_id ────────
        // Favorites target a discoverable Listing (e.g. "ABC — Buea"),
        // not an abstract organization.
        if (!Schema::hasColumn('favorites', 'listing_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->foreignId('listing_id')
                    ->nullable()
                    ->after('business_id')
                    ->constrained('listings')
                    ->cascadeOnDelete();
            });
        }

        // ── 4. Re-scope analytics uniqueness onto the listing axis ────
        //
        // The original analytics table was unique on (business_id, date),
        // which prevented two listings under one organization from having
        // analytics on the same day. Phase 9 moves the unique scope onto
        // (listing_id, date) — with organization-level (listing_id = NULL)
        // rows allowed to repeat.
        if (Schema::hasTable('business_analytics')) {
            // The original (business_id, date) unique is the ONLY index
            // backing the business_id foreign key, so MySQL refuses to drop
            // it while the FK stands. Drop the FK, drop the unique, then
            // re-create the FK (which rebuilds its own index).
            $indexes = collect(\DB::select('SHOW INDEX FROM business_analytics'))
                ->pluck('Key_name')->unique();

            $foreignKeys = collect(\DB::select(
                'SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE '
                . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                ['business_analytics']
            ))->pluck('name');

            $hasCompositeUnique = $indexes->contains('business_analytics_business_id_date_unique')
                || $indexes->contains('business_analytics_scope_date_unique');

            if ($hasCompositeUnique) {
                // 1. Drop the business_id FK if present (MySQL will not drop
                //    an index that a foreign key depends on).
                foreach ($foreignKeys as $fk) {
                    if (str_starts_with($fk, 'business_analytics_business_id')
                        || $fk === 'business_analytics_business_id_foreign') {
                        \DB::statement("ALTER TABLE business_analytics DROP FOREIGN KEY `{$fk}`");
                    }
                }

                // 2. Drop the composite unique(s).
                foreach ([
                    'business_analytics_scope_date_unique',
                    'business_analytics_business_id_date_unique',
                ] as $index) {
                    if ($indexes->contains($index)) {
                        \DB::statement("ALTER TABLE business_analytics DROP INDEX `{$index}`");
                    }
                }

                // 3. Re-create the business_id FK (its index is rebuilt here).
                \DB::statement(
                    'ALTER TABLE business_analytics ADD CONSTRAINT business_analytics_business_id_foreign '
                    . 'FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE'
                );
            }
        }

        foreach ($this->phase7BranchIdTables as $table) {
            if (!Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('branch_id');
            });
        }

        // Re-establish a clean analytics uniqueness on the listing axis:
        // one analytics row per (listing_id, date). Nullable listing_id
        // rows (organization-level aggregates) are allowed to repeat, so we
        // scope the unique to non-null listings via a plain index instead.
        Schema::table('business_analytics', function (Blueprint $table) {
            $table->index(['listing_id', 'date'], 'business_analytics_listing_date_index');
        });

        if (Schema::hasTable('branch_category')) {
            Schema::dropIfExists('branch_category');
        }

        if (Schema::hasColumn('locations', 'slug')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });
        }

        if (Schema::hasColumn('businesses', 'listing_type')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropIndex(['listing_type']);
                $table->dropColumn('listing_type');
            });
        }

        // Leads carried a Phase 5 `branch_id`. The clean final model is a
        // single listing attribution (`leads.listing_id`), so the competing
        // branch pointer is removed.
        if (Schema::hasColumn('leads', 'branch_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }

    public function down(): void
    {
        // Best-effort reversal of the additive listing_id columns.
        foreach ($this->listingChildren as $table) {
            if (Schema::hasColumn($table, 'listing_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropConstrainedForeignId('listing_id');
                });
            }
        }

        if (Schema::hasColumn('favorites', 'listing_id')) {
            Schema::table('favorites', function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('listing_id');
            });
        }

        Schema::dropIfExists('listing_categories');
    }
};
