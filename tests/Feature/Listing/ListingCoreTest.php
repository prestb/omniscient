<?php

namespace Tests\Feature\Listing;

use App\Models\Location;
use App\Models\Business;
use App\Models\Listing;
use App\Models\User;
use App\Support\ListingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 9 — the canonical Listing entity.
 *
 * Executable specification of the Phase 8 "Listing-centric convergence"
 * decision: Listing is the discoverable entity owned by an Account, with an
 * optional organization (Business) and an optional Location (a physical place).
 */
class ListingCoreTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────
    // Ownership
    // ─────────────────────────────────────────────────────────────

    public function test_a_listing_is_owned_by_an_account(): void
    {
        $owner = User::factory()->owner()->create();
        $listing = Listing::factory()->forOwner($owner)->create();

        $this->assertSame($owner->id, $listing->owner_id);
        $this->assertTrue($listing->owner->is($owner));
        $this->assertTrue($owner->listings()->whereKey($listing->id)->exists());
    }

    public function test_an_account_can_own_multiple_listings_of_different_types(): void
    {
        $owner = User::factory()->owner()->create();

        $business = Listing::factory()->forOwner($owner)->create();
        $pro = Listing::factory()->professional()->forOwner($owner)->create();
        $store = Listing::factory()->ofStoreType()->forOwner($owner)->create();

        $this->assertSame(3, $owner->listings()->count());
        $this->assertSame(ListingType::BUSINESS, $business->getListingType());
        $this->assertSame(ListingType::PROFESSIONAL, $pro->getListingType());
        $this->assertSame(ListingType::STORE, $store->getListingType());
    }

    // ─────────────────────────────────────────────────────────────
    // Optional organization
    // ─────────────────────────────────────────────────────────────

    public function test_a_standalone_listing_has_no_organization(): void
    {
        // e.g. a locationless Professional.
        $listing = Listing::factory()->professional()->create();

        $this->assertNull($listing->business_id);
        $this->assertNull($listing->business);
        $this->assertTrue($listing->isListingType(ListingType::PROFESSIONAL));
    }

    public function test_a_listing_may_belong_to_an_organization(): void
    {
        $business = Business::factory()->create();
        $listing = Listing::factory()->forBusiness($business)->create();

        $this->assertSame($business->id, $listing->business_id);
        $this->assertTrue($listing->business->is($business));
        $this->assertTrue($business->listings()->whereKey($listing->id)->exists());
    }

    // ─────────────────────────────────────────────────────────────
    // Optional location (at most one)
    // ─────────────────────────────────────────────────────────────

    public function test_a_listing_may_have_at_most_one_location(): void
    {
        $business = Business::factory()->create();
        $buea = Location::factory()->forBusiness($business)->primary()->create();
        $limbe = Location::factory()->forBusiness($business)->create();

        $listing = Listing::factory()->forBusiness($business)->atLocation($buea)->create();

        $this->assertSame($buea->id, $listing->location_id);
        $this->assertTrue($listing->location->is($buea));

        // A second location is NOT an option on the same listing — the model
        // moves to a different listing.
        $listing->update(['location_id' => $limbe->id]);
        $this->assertSame($limbe->id, $listing->refresh()->location_id);
        $this->assertTrue(Location::find($buea->id)->listings()->whereKey($listing->id)->doesntExist());
    }

    public function test_multiple_locations_are_multiple_listings_under_one_organization(): void
    {
        $business = Business::factory()->create();
        $buea = Location::factory()->forBusiness($business)->primary()->create();
        $limbe = Location::factory()->forBusiness($business)->create();

        Listing::factory()->forBusiness($business)->atLocation($buea)->create();
        Listing::factory()->forBusiness($business)->atLocation($limbe)->create();

        // One organization → two listings; never one listing with two locations.
        $this->assertSame(2, $business->listings()->count());
        $this->assertSame(1, Listing::where('location_id', $buea->id)->count());
        $this->assertSame(1, Listing::where('location_id', $limbe->id)->count());
    }

    // ─────────────────────────────────────────────────────────────
    // Lifecycle / visibility
    // ─────────────────────────────────────────────────────────────

    public function test_visibility_requires_published_and_not_hidden(): void
    {
        $draft = Listing::factory()->create();
        $published = Listing::factory()->published()->create();
        $hidden = Listing::factory()->published()->hidden()->create();

        $this->assertFalse($draft->isVisibleToPublic());
        $this->assertTrue($published->isVisibleToPublic());
        $this->assertFalse($hidden->isVisibleToPublic());
        $this->assertTrue($hidden->isHidden());
    }

    public function test_published_and_visible_scopes(): void
    {
        Listing::factory()->create();
        Listing::factory()->published()->count(2)->create();
        Listing::factory()->published()->hidden()->create();

        $this->assertSame(3, Listing::published()->count());
        $this->assertSame(3, Listing::visible()->count());
        $this->assertSame(1, Listing::hidden()->count());
    }

    // ─────────────────────────────────────────────────────────────
    // Identity (slug)
    // ─────────────────────────────────────────────────────────────

    public function test_slug_is_generated_and_unique(): void
    {
        $a = Listing::factory()->create(['name' => 'ABC Restaurant', 'slug' => null]);
        $b = Listing::factory()->create(['name' => 'ABC Restaurant', 'slug' => null]);

        $this->assertSame('abc-restaurant', $a->slug);
        $this->assertSame('abc-restaurant-2', $b->slug);
    }

    // ─────────────────────────────────────────────────────────────
    // Type scope
    // ─────────────────────────────────────────────────────────────

    public function test_type_scope_filters_by_listing_type(): void
    {
        Listing::factory()->count(2)->create();
        Listing::factory()->ofStoreType()->create();

        $this->assertSame(2, Listing::ofType(ListingType::BUSINESS)->count());
        $this->assertSame(1, Listing::ofType(ListingType::STORE)->count());
        $this->assertSame(0, Listing::ofType(ListingType::PROFESSIONAL)->count());
    }
}
