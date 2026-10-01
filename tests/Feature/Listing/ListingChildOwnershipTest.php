<?php

namespace Tests\Feature\Listing;

use App\Models\Business;
use App\Models\Coupon;
use App\Models\Favorite;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\ListingAnalytics;
use App\Models\ListingContact;
use App\Models\ListingImage;
use App\Models\ListingService;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 9 — discoverable children are owned by the LISTING.
 *
 * A Listing owns its services / media / contacts / analytics / leads / coupons /
 * favorites. Two listings under one organization do NOT
 * share children and never duplicate each other's data.
 *
 * PHASE 11 — Reviews are BUSINESS-owned and are deliberately NOT in this list.
 */
class ListingChildOwnershipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Two listings under one organization, each with its own children.
     *
     * @return array{0:Business, 1:Listing, 2:Listing}
     */
    private function twoListings(): array
    {
        $business = Business::factory()->create();
        $buea = Listing::factory()->forBusiness($business)->create(['name' => 'ABC — Buea']);
        $limbe = Listing::factory()->forBusiness($business)->create(['name' => 'ABC — Limbe']);

        return [$business, $buea, $limbe];
    }

    public function test_services_are_listing_scoped_and_not_duplicated(): void
    {
        [$business, $buea, $limbe] = $this->twoListings();

        ListingService::create(['listing_id' => $buea->id, 'name' => 'Graphic Design']);
        ListingService::create(['listing_id' => $limbe->id, 'name' => 'T-Shirt Printing']);

        $this->assertSame(1, $buea->services()->count());
        $this->assertSame(1, $limbe->services()->count());
        $this->assertSame('Graphic Design', $buea->services()->first()->name);
        $this->assertSame('T-Shirt Printing', $limbe->services()->first()->name);
        // No fan-out: exactly two rows.
        $this->assertSame(2, ListingService::count());
    }

    /**
     * PHASE 11 — `test_reviews_belong_to_a_listing_and_are_never_copied` was
     * REMOVED here. It asserted Listing-owned reviews (`reviews.listing_id`),
     * which the audit established no application writer ever populated. Reviews
     * are BUSINESS-owned; the Listing-owned column, relation and scope are gone.
     * Business review behaviour is covered by
     * tests/Feature/Reviews/BusinessAggregateReviewTest.php.
     */

    public function test_media_is_listing_scoped(): void
    {
        [$business, $buea, $limbe] = $this->twoListings();

        ListingImage::create([
            'listing_id' => $buea->id,
            'path' => 'listings/buea/logo.png',
            'type' => ListingImage::TYPE_LOGO,
        ]);
        ListingImage::create([
            'listing_id' => $limbe->id,
            'path' => 'listings/limbe/logo.png',
            'type' => ListingImage::TYPE_LOGO,
        ]);

        $this->assertSame(1, $buea->images()->count());
        $this->assertSame(1, $limbe->images()->count());
        $this->assertSame(2, ListingImage::count());
    }

    public function test_contacts_are_listing_scoped(): void
    {
        [$business, $buea, $limbe] = $this->twoListings();

        ListingContact::create(['listing_id' => $buea->id, 'type' => 'whatsapp', 'value' => '237111']);
        ListingContact::create(['listing_id' => $limbe->id, 'type' => 'whatsapp', 'value' => '237222']);

        $this->assertSame('237111', $buea->contacts()->first()->value);
        $this->assertSame('237222', $limbe->contacts()->first()->value);
    }

    public function test_leads_are_attributed_to_a_listing(): void
    {
        [$business, $buea] = $this->twoListings();

        $lead = Lead::create([
            'business_id' => $business->id,
            'listing_id' => $buea->id,
            'source' => 'contact_form',
            'name' => 'Visitor',
            'message' => 'Hi',
        ]);

        $this->assertSame($buea->id, $lead->fresh()->listing_id);
        $this->assertSame(1, $buea->leads()->count());
        // The Phase 5 competing branch pointer is gone.
        $this->assertFalse(\Schema::hasColumn('leads', 'branch_id'));
    }

    public function test_coupons_are_listing_scoped(): void
    {
        [$business, $buea] = $this->twoListings();
        $user = User::factory()->create();

        $coupon = Coupon::create([
            'business_id' => $business->id,
            'listing_id' => $buea->id,
            'user_id' => $user->id,
            'title' => '10% off',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $this->assertSame($buea->id, $coupon->fresh()->listing_id);
        $this->assertSame(1, $buea->coupons()->count());
    }

    public function test_favorites_target_a_listing(): void
    {
        [$business, $buea] = $this->twoListings();
        $user = User::factory()->create();

        Favorite::create(['user_id' => $user->id, 'business_id' => $business->id, 'listing_id' => $buea->id]);

        $this->assertSame(1, Favorite::count());
        $this->assertSame(1, $buea->favorites()->count());
    }

    public function test_analytics_are_listing_scoped(): void
    {
        [$business, $buea, $limbe] = $this->twoListings();

        ListingAnalytics::create(['listing_id' => $buea->id, 'date' => today(), 'views' => 10]);
        ListingAnalytics::create(['listing_id' => $limbe->id, 'date' => today(), 'views' => 4]);

        $this->assertSame(10, $buea->analytics()->first()->views);
        $this->assertSame(4, $limbe->analytics()->first()->views);
        $this->assertSame(1, ListingAnalytics::forListing($buea->id)->count());
    }

    public function test_categories_attach_to_a_listing_via_pivot(): void
    {
        [$business, $buea, $limbe] = $this->twoListings();
        $catA = \App\Models\Category::factory()->create(['name' => 'Restaurants', 'slug' => 'restaurants']);
        $catB = \App\Models\Category::factory()->create(['name' => 'Bakeries', 'slug' => 'bakeries']);

        $buea->categories()->attach($catA->id, ['is_primary' => true, 'sort_order' => 0]);
        $limbe->categories()->attach($catB->id, ['is_primary' => true, 'sort_order' => 0]);

        $this->assertSame(1, $buea->categories()->count());
        $this->assertSame(1, $limbe->categories()->count());
        $this->assertDatabaseHas('listing_categories', [
            'listing_id' => $buea->id,
            'category_id' => $catA->id,
        ]);
        // The Phase-7 branch pivot is gone.
        $this->assertFalse(\Schema::hasTable('branch_category'));
    }
}
