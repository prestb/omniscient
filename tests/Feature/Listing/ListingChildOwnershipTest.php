<?php

namespace Tests\Feature\Listing;

use App\Models\Business;
use App\Models\BusinessAnalytics;
use App\Models\BusinessContact;
use App\Models\BusinessImage;
use App\Models\BusinessService;
use App\Models\Coupon;
use App\Models\Favorite;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 9 — discoverable children are owned by the LISTING.
 *
 * A Listing owns its services / media / contacts / reviews / analytics /
 * leads / coupons / favorites. Two listings under one organization do NOT
 * share children and never duplicate each other's data.
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

        BusinessService::create(['business_id' => $business->id, 'listing_id' => $buea->id, 'name' => 'Graphic Design']);
        BusinessService::create(['business_id' => $business->id, 'listing_id' => $limbe->id, 'name' => 'T-Shirt Printing']);

        $this->assertSame(1, $buea->services()->count());
        $this->assertSame(1, $limbe->services()->count());
        $this->assertSame('Graphic Design', $buea->services()->first()->name);
        $this->assertSame('T-Shirt Printing', $limbe->services()->first()->name);
        // No fan-out: exactly two rows.
        $this->assertSame(2, BusinessService::count());
    }

    public function test_reviews_belong_to_a_listing_and_are_never_copied(): void
    {
        [$business, $buea] = $this->twoListings();
        $user = User::factory()->create();

        $review = Review::create([
            'business_id' => $business->id,
            'listing_id' => $buea->id,
            'user_id' => $user->id,
            'rating' => 5,
            'content' => 'Great place.',
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $this->assertSame(1, Review::count());
        $this->assertSame($buea->id, $review->fresh()->listing_id);
        $this->assertSame(1, $buea->reviews()->count());
    }

    public function test_media_is_listing_scoped(): void
    {
        [$business, $buea, $limbe] = $this->twoListings();

        BusinessImage::create([
            'business_id' => $business->id,
            'listing_id' => $buea->id,
            'path' => 'listings/buea/logo.png',
            'type' => BusinessImage::TYPE_LOGO,
        ]);
        BusinessImage::create([
            'business_id' => $business->id,
            'listing_id' => $limbe->id,
            'path' => 'listings/limbe/logo.png',
            'type' => BusinessImage::TYPE_LOGO,
        ]);

        $this->assertSame(1, $buea->images()->count());
        $this->assertSame(1, $limbe->images()->count());
        $this->assertSame(2, BusinessImage::count());
    }

    public function test_contacts_are_listing_scoped(): void
    {
        [$business, $buea, $limbe] = $this->twoListings();

        BusinessContact::create(['business_id' => $business->id, 'listing_id' => $buea->id, 'type' => 'whatsapp', 'value' => '237111']);
        BusinessContact::create(['business_id' => $business->id, 'listing_id' => $limbe->id, 'type' => 'whatsapp', 'value' => '237222']);

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

        BusinessAnalytics::create(['business_id' => $business->id, 'listing_id' => $buea->id, 'date' => today(), 'views' => 10]);
        BusinessAnalytics::create(['business_id' => $business->id, 'listing_id' => $limbe->id, 'date' => today(), 'views' => 4]);

        $this->assertSame(10, $buea->analytics()->first()->views);
        $this->assertSame(4, $limbe->analytics()->first()->views);
        $this->assertSame(1, BusinessAnalytics::forListing($buea->id)->count());
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
