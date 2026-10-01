<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 9 — Listing Core.
 *
 * Introduces `listings` as a genuine first-class, discoverable domain entity.
 *
 * ── Architecture (Phase 8, Option C — Listing-centric convergence) ────
 *   Account (User)
 *     └── Listings            (the discoverable entity)
 *           ├── type          business | professional | store
 *           ├── business_id?  → organizations (multi-location brands)
 *           ├── location_id?  → locations     (a physical place)
 *           └── identity / lifecycle / visibility
 *
 * ── Key invariants ───────────────────────────────────────────────────
 *   - owner_id is the canonical Account ownership edge (users.id).
 *   - business_id is OPTIONAL: NULL = standalone listing (e.g. a
 *     locationless Professional); non-NULL = belongs to an organization.
 *   - location_id is OPTIONAL and points at the universal `locations`
 *     table (Phase 10). A Listing has AT MOST ONE location.
 *     Multiple physical locations == multiple Listings, never one Listing
 *     with many locations.
 *   - `type` is the canonical listing-type axis (see App\Support\ListingType).
 *     There is NO `businesses.listing_type` any more — one source of truth.
 *
 * This migration is intentionally created fresh rather than layered on the
 * Phase 6/7 experimental columns: the development database holds no valuable
 * data, so the schema is built cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listings')) {
            return;
        }

        Schema::create('listings', function (Blueprint $table) {
            $table->id();

            // ── Ownership (canonical) ────────────────────────────────
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            // ── Organization (optional) ──────────────────────────────
            // NULL = standalone listing. Non-NULL = belongs to a brand.
            $table->foreignId('business_id')
                ->nullable()
                ->constrained('businesses')
                ->cascadeOnDelete();

            // ── Location (optional, at most one) ─────────────────────
            // Points at the universal `locations` table (Phase 10).
            // nullOnDelete keeps the listing if its location is removed.
            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            // ── Identity ─────────────────────────────────────────────
            // `type` is the authoritative listing-type axis.
            $table->string('type', 32)->default('business');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // ── Lifecycle (discoverability) ──────────────────────────
            $table->string('status', 32)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('hidden_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──────────────────────────────────────────────
            $table->index('owner_id');
            $table->index('business_id');
            $table->index('location_id');
            $table->index('type');
            $table->index('status');
            $table->index('is_featured');
            $table->index('hidden_at');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
