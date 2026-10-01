<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 11 / WAVE 1C — remove the competing business-level taxonomy.
 *
 * `business_categories` was a `business_id ↔ category_id` pivot used only to
 * classify/discover a BUSINESS. Phase 9 made the LISTING the canonical
 * discoverable entity and introduced `listing_categories`; Wave 1C completes
 * the convergence, so the organization no longer owns a stored taxonomy.
 *
 * Safe-removal states (Wave 1C brief):
 *   STATE A — 0 rows                                                   → remove
 *   STATE B — every row maps to exactly one Listing deterministically  → migrate, then remove
 *   STATE C — one or more rows map ambiguously                         → STOP and report
 *
 * This migration implements STATE A. If any row exists it REFUSES to run.
 * A business-scoped category cannot be attributed to a Listing without
 * evidence, and this migration will not guess one — no primary/first/oldest/
 * alphabetical Listing heuristic, no duplication across Listings, no
 * fabricated Listing, no compatibility pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_categories')) {
            return;
        }

        $rows = DB::table('business_categories')->count();

        if ($rows > 0) {
            throw new \RuntimeException(
                "Refusing to drop `business_categories`: it contains {$rows} row(s) whose "
                . 'Listing ownership cannot be established deterministically (STATE C). '
                . 'Wave 1C must stop here — resolve the ambiguous mappings explicitly. '
                . 'No Listing was inferred, selected or fabricated.'
            );
        }

        Schema::drop('business_categories');
    }

    public function down(): void
    {
        if (Schema::hasTable('business_categories')) {
            return;
        }

        Schema::create('business_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['business_id', 'category_id']);
            $table->index('is_primary');
        });
    }
};
