<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingAnalytics;
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

test('the business-keyed tracking routes remain in place and untouched', function () {
    // This unit must NOT migrate or remove them.
    expect(str_contains(File::get(base_path('routes/web.php')), '/analytics/track-view/{business}'))->toBeTrue();
    expect(str_contains(File::get(base_path('routes/web.php')), '/analytics/track-click/{business}/{type}'))->toBeTrue();
    expect(str_contains(File::get(base_path('routes/api.php')), '/analytics/track-view/{business}'))->toBeTrue();

    // And the bridge they use is still present on the model.
    expect(method_exists(Business::class, 'primaryListing'))->toBeTrue();
});
