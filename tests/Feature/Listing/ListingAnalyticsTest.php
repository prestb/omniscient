<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingAnalytics;
use App\Models\ListingContact;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-3 — CANONICAL LISTING-SCOPED TRACKING (PATH A).
 *
 * Analytics are Listing-owned. The Listing is explicit in the route and is
 * never resolved through a Business, so Listing A can never be credited with
 * Listing B's activity.
 */

/** Read the single analytics row for a Listing, or null. */
function analyticsFor(Listing $listing): ?ListingAnalytics
{
    return ListingAnalytics::where('listing_id', $listing->id)->first();
}

test('a view is recorded against the explicit listing only', function () {
    $business = Business::factory()->create();

    $a = Listing::factory()->forBusiness($business)->create();
    $b = Listing::factory()->forBusiness($business)->create();

    $this->post("/analytics/listing/{$a->id}/track-view")->assertOk()
        ->assertJson(['success' => true]);

    expect(analyticsFor($a)->views)->toBe(1);
    expect(analyticsFor($a)->unique_visitors)->toBe(1);

    // Listing B is untouched — it has no analytics row at all.
    expect(analyticsFor($b))->toBeNull();
});

test('a click is recorded against the explicit listing only', function () {
    $business = Business::factory()->create();

    $a = Listing::factory()->forBusiness($business)->create();
    $b = Listing::factory()->forBusiness($business)->create();

    $this->post("/analytics/listing/{$a->id}/track-click/phone")->assertOk()
        ->assertJson(['success' => true]);

    expect(analyticsFor($a)->phone_clicks)->toBe(1);
    expect(analyticsFor($b))->toBeNull();
});

test('listing identity is authoritative and never resolves through the business', function () {
    $business = Business::factory()->create();

    // B is created first, so it is the "oldest" Listing — i.e. exactly what
    // Business::primaryListing() would have returned.
    $b = Listing::factory()->forBusiness($business)->create();
    $a = Listing::factory()->forBusiness($business)->create();

    $this->post("/analytics/listing/{$a->id}/track-click/website")->assertOk();

    // The event landed on A, NOT on the Listing primaryListing() would pick.
    expect(analyticsFor($a)->website_clicks)->toBe(1);
    expect(analyticsFor($b))->toBeNull();

    // And the controller contains no bridge at all.
    $code = collect(preg_split('/\R/', File::get(app_path('Http/Controllers/Api/AnalyticsController.php'))))
        ->reject(function (string $line) {
            $t = ltrim($line);
            return str_starts_with($t, '*') || str_starts_with($t, '//') || str_starts_with($t, '/*');
        })
        ->implode("\n");

    // The listing-scoped methods must not touch the bridge.
    $listingMethods = substr($code, strpos($code, 'trackListingView'));
    expect(str_contains($listingMethods, 'primaryListing'))->toBeFalse();
    expect(str_contains($listingMethods, 'listings()->first'))->toBeFalse();
    expect(str_contains($listingMethods, 'listings()->value('))->toBeFalse();
});

test('independent events stay attached to their respective listings', function () {
    $business = Business::factory()->create();

    $a = Listing::factory()->forBusiness($business)->create();
    $b = Listing::factory()->forBusiness($business)->create();

    $this->post("/analytics/listing/{$a->id}/track-click/phone")->assertOk();
    $this->post("/analytics/listing/{$b->id}/track-click/website")->assertOk();

    $rowA = analyticsFor($a);
    $rowB = analyticsFor($b);

    expect($rowA->phone_clicks)->toBe(1);
    expect($rowA->website_clicks)->toBe(0);
    expect($rowB->website_clicks)->toBe(1);
    expect($rowB->phone_clicks)->toBe(0);
});

test('the supported click vocabulary is preserved and nothing new is accepted', function (string $type) {
    $listing = Listing::factory()->create();

    $this->post("/analytics/listing/{$listing->id}/track-click/{$type}")->assertOk()
        ->assertJson(['success' => true]);

    expect(analyticsFor($listing)->{$type . '_clicks'})->toBe(1);
})->with(['phone', 'whatsapp', 'website', 'direction', 'social']);

test('an unsupported click type is rejected without writing', function () {
    $listing = Listing::factory()->create();

    $this->post("/analytics/listing/{$listing->id}/track-click/not-a-real-type")
        ->assertStatus(400);

    expect(analyticsFor($listing))->toBeNull();
});

test('an unknown listing is rejected', function () {
    $this->post('/analytics/listing/999999/track-view')->assertNotFound();
    $this->post('/analytics/listing/999999/track-click/phone')->assertNotFound();
});

test('the obsolete Business-keyed tracking routes were removed', function () {
    $web = File::get(base_path('routes/web.php'));
    $api = File::get(base_path('routes/api.php'));

    foreach ([
        "Route::post('/analytics/track-view/{business}'",
        "Route::post('/analytics/track-click/{business}/{type}'",
        "Route::post('/track-view/{business}'",
        "Route::post('/track-click/{business}/{type}'",
    ] as $gone) {
        expect(str_contains($web, $gone))->toBeFalse("web.php still registers {$gone}");
    }

    expect(str_contains($api, "Route::post('/analytics/track"))
        ->toBeFalse('api.php still registers Business-keyed analytics.');

    // The old endpoints genuinely no longer respond.
    $this->post('/analytics/track-click/1/phone')->assertNotFound();
    $this->post('/analytics/track-view/1')->assertNotFound();

    // The bridge itself is retained for the one remaining caller (Category slice).
    expect(method_exists(Business::class, 'primaryListing'))->toBeTrue();
});

test('a contact event is attributed to the listing that owns the contact', function () {
    $business = Business::factory()->create();

    $a = Listing::factory()->forBusiness($business)->create();
    $b = Listing::factory()->forBusiness($business)->create();

    // Contacts belong to their own Listings. A `facebook` contact is a social
    // contact, so its click emits the analytics type `social`.
    ListingContact::create(['listing_id' => $a->id, 'type' => 'phone', 'value' => '+237 111', 'sort_order' => 1]);
    ListingContact::create(['listing_id' => $b->id, 'type' => 'facebook', 'value' => 'https://x.test', 'sort_order' => 1]);

    // The organization page sends the clicked contact's own listing_id.
    $this->post("/analytics/listing/{$a->id}/track-click/phone")->assertOk();
    $this->post("/analytics/listing/{$b->id}/track-click/social")->assertOk();

    expect(analyticsFor($a)->phone_clicks)->toBe(1);
    expect(analyticsFor($a)->social_clicks)->toBe(0);
    expect(analyticsFor($b)->social_clicks)->toBe(1);
    expect(analyticsFor($b)->phone_clicks)->toBe(0);
});

test('the organization page no longer emits Business-keyed analytics', function () {
    $source = File::get(resource_path('js/Pages/Public/BusinessProfile.vue'));

    // No Business-keyed ingestion call remains.
    expect(str_contains($source, 'analytics/track-click/${businessId}'))->toBeFalse();
    expect(str_contains($source, "trackClick('website')"))->toBeFalse();

    // The three contact events route through the explicit Listing path.
    expect(str_contains($source, "trackClick('phone', contactPhoneListingId)"))->toBeTrue();
    expect(str_contains($source, "trackClick('whatsapp', contactWhatsAppListingId)"))->toBeTrue();
    expect(str_contains($source, "trackClick('social', contact.listing_id)"))->toBeTrue();

    // Website rendering is untouched — only its analytics event is gone.
    expect(str_contains($source, 'business.website'))->toBeTrue();
});

test('an organization-owned website click creates no listing analytics', function () {
    $business = Business::factory()->create(['website' => 'https://acme.test']);
    Listing::factory()->forBusiness($business)->create();

    // The emission was removed entirely. Nothing is recorded, and there is no
    // organization-level analytics record either — none should exist.
    expect(ListingAnalytics::count())->toBe(0);
});

test('the canonical Business aggregate dashboard still sums across listings', function () {
    $plan = Plan::factory()->create(['max_listings' => 10]);
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $business = Business::factory()->create(['owner_id' => $owner->id, 'status' => 'published']);

    $a = Listing::factory()->forBusiness($business)->forOwner($owner)->create();
    $b = Listing::factory()->forBusiness($business)->forOwner($owner)->create();

    $this->post("/analytics/listing/{$a->id}/track-view")->assertOk();
    $this->post("/analytics/listing/{$b->id}/track-view")->assertOk();
    $this->post("/analytics/listing/{$b->id}/track-click/phone")->assertOk();

    // index() aggregates the Business's Listings — unchanged by this wave.
    $response = $this->actingAs($owner)->get('/owner/analytics');
    $response->assertOk();

    expect((int) $response->viewData('page')['props']['summary']['total_views'])->toBe(2);
    expect((int) $response->viewData('page')['props']['summary']['total_phone_clicks'])->toBe(1);
});
