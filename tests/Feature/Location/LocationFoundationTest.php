<?php

namespace Tests\Feature\Location;

use App\Models\Business;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PHASE 10 — Universal Location Foundation.
 *
 * Executable specification of the Phase 10 decision: Location is a universal
 * physical-place entity, NOT a "branch" of a Business and NOT discoverable
 * identity.
 *
 *   Listing.location_id → locations.id   (optional: 0 or 1)
 *
 * Covers the Phase 10 §19 checklist:
 *   - Location creation (independent of Business)
 *   - Listing without a Location (location_id = NULL)
 *   - Listing with a Location
 *   - Multiple Listings / multiple Locations
 *   - Multiple Listings under one Business
 *   - Standalone Listing (business_id = NULL) + Location
 *   - Location does not require a Business
 *   - Location is not discoverable (no identity on Location)
 */
class LocationFoundationTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────
    // Location creation — independence from Business
    // ─────────────────────────────────────────────────────────────

    public function test_a_location_can_be_created_without_a_business(): void
    {
        $location = Location::factory()->create();

        $this->assertNull($location->business_id);
        $this->assertNull($location->business);
        $this->assertTrue($location->exists);
    }

    public function test_creating_a_location_does_not_require_a_business(): void
    {
        $location = Location::factory()->standalone()->create([
            'name' => 'PTSMI Auditorium',
            'address' => 'Molyko, Buea',
        ]);

        $this->assertSame('PTSMI Auditorium', $location->name);
        $this->assertNull($location->business_id);
        $this->assertSame(1, Location::count());
    }

    public function test_a_location_may_optionally_belong_to_a_business(): void
    {
        $business = Business::factory()->create();
        $location = Location::factory()->forBusiness($business)->create();

        $this->assertSame($business->id, $location->business_id);
        $this->assertTrue($location->business->is($business));
        $this->assertTrue($business->locations()->whereKey($location->id)->exists());
    }

    // ─────────────────────────────────────────────────────────────
    // Listing without a Location
    // ─────────────────────────────────────────────────────────────

    public function test_a_listing_may_have_no_location(): void
    {
        $listing = Listing::factory()->professional()->create();

        $this->assertNull($listing->location_id);
        $this->assertNull($listing->location);
    }

    // ─────────────────────────────────────────────────────────────
    // Listing with a Location
    // ─────────────────────────────────────────────────────────────

    public function test_a_listing_can_reference_a_location(): void
    {
        $location = Location::factory()->create();
        $listing = Listing::factory()->atLocation($location)->create();

        $this->assertSame($location->id, $listing->location_id);
        $this->assertTrue($listing->location->is($location));
        $this->assertTrue($location->listings()->whereKey($listing->id)->exists());
    }

    // ─────────────────────────────────────────────────────────────
    // Multiple Listings / Locations
    // ─────────────────────────────────────────────────────────────

    public function test_listing_a_to_location_a_and_listing_b_to_location_b(): void
    {
        $locationA = Location::factory()->create();
        $locationB = Location::factory()->create();

        $listingA = Listing::factory()->atLocation($locationA)->create();
        $listingB = Listing::factory()->atLocation($locationB)->create();

        $this->assertSame($locationA->id, $listingA->location_id);
        $this->assertSame($locationB->id, $listingB->location_id);
        $this->assertNotSame($listingA->location_id, $listingB->location_id);
    }

    public function test_multiple_listings_under_one_business_each_point_at_their_own_location(): void
    {
        $business = Business::factory()->create();

        $buea = Location::factory()->forBusiness($business)->primary()->create();
        $limbe = Location::factory()->forBusiness($business)->create();

        $listingA = Listing::factory()->forBusiness($business)->atLocation($buea)->create();
        $listingB = Listing::factory()->forBusiness($business)->atLocation($limbe)->create();

        $this->assertSame(2, $business->listings()->count());
        $this->assertSame($buea->id, $listingA->location_id);
        $this->assertSame($limbe->id, $listingB->location_id);
    }

    // ─────────────────────────────────────────────────────────────
    // Standalone Listing + Location (no Business at all)
    // ─────────────────────────────────────────────────────────────

    public function test_a_standalone_listing_can_own_a_location(): void
    {
        // Business = NULL, Listing → Location. This is the canonical
        // standalone case the Phase 10 brief requires to work.
        $location = Location::factory()->standalone()->create();
        $listing = Listing::factory()->atLocation($location)->create();

        $this->assertNull($listing->business_id);
        $this->assertSame($location->id, $listing->location_id);
        $this->assertTrue($listing->location->is($location));
        $this->assertNull($listing->location->business_id);
    }

    // ─────────────────────────────────────────────────────────────
    // Location is NOT discoverable identity
    // ─────────────────────────────────────────────────────────────

    public function test_location_carries_no_listing_identity_columns(): void
    {
        foreach (['slug', 'type', 'listing_type'] as $identityColumn) {
            $this->assertFalse(
                Schema::hasColumn('locations', $identityColumn),
                "locations must NOT own listing identity column: {$identityColumn}"
            );
        }
    }

    public function test_locations_table_has_no_required_business_id(): void
    {
        // The schema must not require business_id for a Location.
        $this->assertTrue(Schema::hasColumn('locations', 'business_id'));

        $location = Location::create([
            'name' => 'Independent Place',
            'status' => Location::STATUS_ACTIVE,
        ]);

        $this->assertNull($location->business_id);
    }

    public function test_location_is_not_searchable_identity(): void
    {
        // Only Listings are the searchable entity. Location must not announce
        // itself as a discoverable/searchable model.
        $this->assertFalse(
            method_exists(Location::class, 'toSearchableArray'),
            'Location must NOT be a searchable (discoverable) entity'
        );
    }
}
