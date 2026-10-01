<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 11 / WAVE 1D-5A — FAVORITES ARE LISTING-OWNED.
 *
 * `2026_10_01_000002` ADDED `favorites.listing_id` but never removed the old
 * Business key. The result was an unusable hybrid:
 *
 *   business_id   NOT NULL   UNIQUE(user_id, business_id)
 *   listing_id    nullable   plain INDEX
 *
 * So a Listing-owned favorite was impossible: `business_id` was required, and
 * UNIQUE(user_id, business_id) prevented favouriting two Listings of the same
 * Business independently — the exact isolation this wave requires.
 *
 * This migration finishes the move: `listing_id` becomes the authoritative,
 * required key and the obsolete Business key is dropped.
 *
 * Authorised destructive change — the project has no production data.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Never invent ownership. If a favorite exists with no Listing there is
        // no safe mapping, so stop and report instead of guessing.
        $orphans = DB::table('favorites')->whereNull('listing_id')->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "Cannot make favorites Listing-owned: {$orphans} favorite row(s) have a NULL listing_id. "
                . 'Refusing to guess a Listing. Resolve these rows first.'
            );
        }

        // Drop the legacy Business key (constraint + index + column).
        Schema::table('favorites', function (Blueprint $table) {
            if (Schema::hasIndex('favorites', ['user_id', 'business_id'])) {
                $table->dropUnique(['user_id', 'business_id']);
            }

            if (Schema::hasColumn('favorites', 'business_id')) {
                $table->dropConstrainedForeignId('business_id');
            }
        });

        Schema::table('favorites', function (Blueprint $table) {
            if (Schema::hasColumn('favorites', 'listing_id')) {
                $table->unsignedBigInteger('listing_id')->nullable(false)->change();
            }
        });

        // One favorite per user per LISTING.
        if (!Schema::hasIndex('favorites', ['user_id', 'listing_id'])) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->unique(['user_id', 'listing_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            if (Schema::hasIndex('favorites', ['user_id', 'listing_id'])) {
                $table->dropUnique(['user_id', 'listing_id']);
            }
        });

        Schema::table('favorites', function (Blueprint $table) {
            if (!Schema::hasColumn('favorites', 'business_id')) {
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            }
        });

        Schema::table('favorites', function (Blueprint $table) {
            if (Schema::hasColumn('favorites', 'listing_id')) {
                $table->unsignedBigInteger('listing_id')->nullable()->change();
            }
        });
    }
};
