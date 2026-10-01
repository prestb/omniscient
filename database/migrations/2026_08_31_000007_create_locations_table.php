<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 10 — Universal Location Foundation.
 *
 * `locations` is a UNIVERSAL physical-place entity. It is NOT a "branch" of a
 * business, and it is NOT discoverable identity. A Location answers only
 * "where is this physically?".
 *
 *   Listing.location_id → locations.id   (optional)
 *
 * ── Invariants ───────────────────────────────────────────────────────
 *   - A Location does NOT require an organization: `business_id` is NULLABLE.
 *     A standalone Listing (or a future Event) can own a physical Location
 *     without belonging to a Business.
 *   - A Location carries NO listing identity (no slug, type, description,
 *     categories, reviews, media, analytics, leads, coupons).
 *   - One Listing has ZERO OR ONE Location; multiple places == multiple
 *     Listings, never one Listing with many Locations.
 *
 * Replaces the legacy `branches` table. The development database is
 * disposable, so a clean schema is preferred over compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            // ── Optional organization link ───────────────────────────
            // NULLABLE on purpose: a Location is a physical place, not a
            // business child. Standalone Listings and future Events may own
            // a Location with no Business at all.
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();

            // ── Optional human label for the place ───────────────────
            // e.g. "Molyko Campus". NOT a Listing name/slug.
            $table->string('name')->nullable();

            // ── Geographic location ──────────────────────────────────
            $table->foreignId('country_id')->nullable()->constrained();
            $table->foreignId('region_id')->nullable()->constrained();
            $table->foreignId('city_id')->nullable()->constrained();
            $table->foreignId('area_id')->nullable()->constrained();
            $table->text('address')->nullable();
            $table->string('landmark')->nullable();
            $table->string('postal_code')->nullable();

            // ── GPS coordinates ──────────────────────────────────────
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // ── Location-specific contact ────────────────────────────
            // Only when the contact genuinely belongs to the PLACE.
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();

            // ── Lifecycle ────────────────────────────────────────────
            $table->enum('status', [
                'active',
                'temporarily_unavailable',
                'unlisted',
            ])->default('active');

            // Whether this is the organization's primary/preferred place.
            // Only meaningful when business_id is set.
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes (geographic / discovery support; no GIS subsystem) ──
            $table->index('business_id');
            $table->index(['business_id', 'is_primary']);
            $table->index(['country_id', 'region_id', 'city_id', 'area_id']);
            $table->index('status');
            $table->index('latitude');
            $table->index('longitude');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};