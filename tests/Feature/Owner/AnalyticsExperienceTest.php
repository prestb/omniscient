<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingAnalytics;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * WAVE 28C — ANALYTICS EXPERIENCE AND TRACKING INTEGRITY.
 *
 * Pins: the view-count definition and its server-side deduplication window, the
 * automated-client policy, Listing-specific authorization, the business-less
 * dashboard analytics contract, and account isolation.
 *
 * Helper names are prefixed `wc`; Pest shares one process across test files.
 */

function wcOwner(): User
{
    return User::factory()->owner()->create();
}

function wcListing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->published()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'hidden_at' => null,
    ], $attrs));
}

function wcViews(Listing $listing): int
{
    return (int) ListingAnalytics::where('listing_id', $listing->id)->sum('views');
}

// ═══ §5 VIEW-COUNT INTEGRITY ════════════════════════════════════════════════

test('a public Listing records an accepted view', function () {
    $listing = wcListing(wcOwner());

    $this->post("/analytics/listing/{$listing->id}/track-view")
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(wcViews($listing))->toBe(1);
});

test('repeated requests inside the deduplication window do not inflate the count', function () {
    $listing = wcListing(wcOwner());
    $url = "/analytics/listing/{$listing->id}/track-view";

    // Deduplication is SERVER-side and keyed by the browser session, so the test
    // must behave like a browser and return its session cookie. The test client
    // does not replay cookies automatically; a real browser always does.
    $cookie = config('session.cookie');
    $first = $this->post($url)->assertOk();
    $jar = [$cookie => $first->getCookie($cookie)->getValue()];

    // A refresh, and another refresh: accepted, but NOT counted again.
    for ($i = 0; $i < 4; $i++) {
        $this->withCookies($jar)->post($url)
            ->assertOk()
            ->assertJson(['counted' => false, 'reason' => 'deduplicated']);
    }

    expect(wcViews($listing))->toBe(1);
});

test('a visit after the window is counted again', function () {
    $listing = wcListing(wcOwner());
    $url = "/analytics/listing/{$listing->id}/track-view";

    $cookie = config('session.cookie');
    $first = $this->post($url)->assertOk();
    $jar = [$cookie => $first->getCookie($cookie)->getValue()];

    expect(wcViews($listing))->toBe(1);

    // The window is 30 minutes; the SAME session visiting later counts again, so
    // the metric is repeatable rather than permanently capped.
    $this->travel(31)->minutes();

    $this->withCookies($jar)->post($url)->assertOk();
    expect(wcViews($listing))->toBe(2);
});

test('a spoofable client flag cannot bypass deduplication', function () {
    $listing = wcListing(wcOwner());
    $url = "/analytics/listing/{$listing->id}/track-view";

    $cookie = config('session.cookie');
    $first = $this->post($url)->assertOk();
    $jar = [$cookie => $first->getCookie($cookie)->getValue()];

    // Deduplication is server-side; client-supplied hints change nothing.
    foreach (['X-Analytics-Unique' => '1', 'X-Requested-With' => 'XMLHttpRequest'] as $k => $v) {
        $this->withCookies($jar)->withHeaders([$k => $v])->post($url)->assertOk();
    }

    expect(wcViews($listing))->toBe(1);
});

test('an automated client does not create an analytics row', function () {
    $listing = wcListing(wcOwner());

    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
        ->post("/analytics/listing/{$listing->id}/track-view")
        ->assertOk()
        ->assertJson(['counted' => false, 'reason' => 'automated']);

    expect(ListingAnalytics::where('listing_id', $listing->id)->count())->toBe(0);
});

test('an ordinary mobile browser is still counted', function () {
    $listing = wcListing(wcOwner());

    $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
    ])->post("/analytics/listing/{$listing->id}/track-view")->assertOk();

    expect(wcViews($listing))->toBe(1);
});

test('non-public Listings are rejected without writing a row', function () {
    $owner = wcOwner();

    foreach ([
        Listing::STATUS_DRAFT,
        Listing::STATUS_SUSPENDED,
    ] as $status) {
        $listing = Listing::factory()->create([
            'owner_id' => $owner->id,
            'business_id' => null,
            'status' => $status,
            'hidden_at' => null,
        ]);

        $this->post("/analytics/listing/{$listing->id}/track-view")->assertNotFound();
        expect(ListingAnalytics::where('listing_id', $listing->id)->count())->toBe(0);
    }

    $hidden = wcListing($owner, ['hidden_at' => now()]);
    $this->post("/analytics/listing/{$hidden->id}/track-view")->assertNotFound();

    $deleted = wcListing($owner);
    $deleted->delete();
    $this->post("/analytics/listing/{$deleted->id}/track-view")->assertNotFound();
});

test('tracking stays Listing-specific with the click whitelist intact', function () {
    $owner = wcOwner();
    $a = wcListing($owner);
    $b = wcListing($owner);

    $this->post("/analytics/listing/{$a->id}/track-click/phone")->assertOk();

    expect((int) ListingAnalytics::where('listing_id', $a->id)->sum('phone_clicks'))->toBe(1)
        ->and((int) ListingAnalytics::where('listing_id', $b->id)->sum('phone_clicks'))->toBe(0);

    // An unsupported type is still rejected and writes nothing.
    $this->post("/analytics/listing/{$a->id}/track-click/telepathy")->assertStatus(400);
    expect((int) ListingAnalytics::where('listing_id', $a->id)->sum('phone_clicks'))->toBe(1);
});

test('tracking responses expose no analytics data', function () {
    $listing = wcListing(wcOwner());

    $body = $this->post("/analytics/listing/{$listing->id}/track-view")->assertOk()->json();

    expect(array_keys($body))->not->toContain('views')
        ->and(array_keys($body))->not->toContain('unique_visitors');
});

// ═══ §4 LISTING-SPECIFIC ANALYTICS ══════════════════════════════════════════

test('an owner can open analytics for a Listing they own', function () {
    $owner = wcOwner();
    $listing = wcListing($owner);

    $this->actingAs($owner)
        ->get("/owner/analytics/listing/{$listing->id}")
        ->assertRedirect(route('owner.analytics.index', ['listing' => $listing->id, 'days' => 30]));
});

test('a foreign Listing cannot be inspected', function () {
    $stranger = wcOwner();
    $foreign = wcListing($stranger);

    $this->actingAs(wcOwner())
        ->get("/owner/analytics/listing/{$foreign->id}")
        ->assertNotFound();
});

test('owning the Business does NOT authorize a Listing owned by another account', function () {
    $owner = wcOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id, 'status' => 'published']);

    $stranger = wcOwner();
    $foreign = wcListing($stranger, ['business_id' => $business->id]);

    $this->actingAs($owner)
        ->get("/owner/analytics/listing/{$foreign->id}")
        ->assertNotFound();
});

test('a foreign listing filter cannot leak another account\'s analytics', function () {
    $owner = wcOwner();
    wcListing($owner);

    $stranger = wcOwner();
    $foreign = wcListing($stranger);
    ListingAnalytics::create([
        'listing_id' => $foreign->id, 'date' => now()->toDateString(),
        'views' => 999, 'unique_visitors' => 0, 'phone_clicks' => 0,
        'whatsapp_clicks' => 0, 'website_clicks' => 0, 'direction_clicks' => 0, 'social_clicks' => 0,
    ]);

    $props = $this->actingAs($owner)
        ->get('/owner/analytics?listing=' . $foreign->id)
        ->assertOk()
        ->viewData('page')['props'];

    expect((int) $props['summary']['total_views'])->toBe(0)
        ->and($props['perListing'])->toHaveCount(0);
});

test('the per-Listing breakdown lists every authorized Listing', function () {
    $owner = wcOwner();
    $withData = wcListing($owner);
    $quiet = wcListing($owner);

    ListingAnalytics::create([
        'listing_id' => $withData->id, 'date' => now()->toDateString(),
        'views' => 12, 'unique_visitors' => 3, 'phone_clicks' => 2,
        'whatsapp_clicks' => 0, 'website_clicks' => 0, 'direction_clicks' => 0, 'social_clicks' => 0,
    ]);

    $props = $this->actingAs($owner)->get('/owner/analytics')->assertOk()->viewData('page')['props'];

    $byId = collect($props['perListing'])->keyBy('id');

    expect($props['perListing'])->toHaveCount(2)
        ->and($byId[$withData->id]['total_views'])->toBe(12)
        ->and($byId[$withData->id]['total_phone_clicks'])->toBe(2)
        ->and($byId[$quiet->id]['total_views'])->toBe(0);
});

// ═══ §3 DASHBOARD — BUSINESS-LESS ACCOUNTS ══════════════════════════════════

test('a business-less owner receives account analytics on the dashboard', function () {
    $owner = wcOwner();
    $listing = wcListing($owner);

    ListingAnalytics::create([
        'listing_id' => $listing->id, 'date' => now()->toDateString(),
        'views' => 25, 'unique_visitors' => 9, 'phone_clicks' => 4,
        'whatsapp_clicks' => 0, 'website_clicks' => 0, 'direction_clicks' => 0, 'social_clicks' => 0,
    ]);

    $props = $this->actingAs($owner)->get('/owner/dashboard')->assertOk()->viewData('page')['props'];

    expect($props['hasBusiness'])->toBeFalse()
        ->and($props['analytics']['stats']['total_views'])->toBe(25)
        ->and($props['analytics']['stats']['total_unique_visitors'])->toBe(9)
        ->and($props['analytics']['stats']['total_phone_clicks'])->toBe(4)
        ->and($props['analytics']['has_data'])->toBeTrue()
        ->and($props['analytics']['listings_count'])->toBe(1);
});

test('an empty business-less account gets deliberate zeros, not misleading totals', function () {
    $props = $this->actingAs(wcOwner())->get('/owner/dashboard')->assertOk()->viewData('page')['props'];

    expect($props['hasBusiness'])->toBeFalse()
        ->and($props['analytics']['has_data'])->toBeFalse()
        ->and($props['analytics']['listings_count'])->toBe(0)
        ->and((int) $props['analytics']['stats']['total_views'])->toBe(0);
});

test('the business-less dashboard never includes another account\'s analytics', function () {
    $owner = wcOwner();
    wcListing($owner);

    $stranger = wcOwner();
    $foreign = wcListing($stranger);
    ListingAnalytics::create([
        'listing_id' => $foreign->id, 'date' => now()->toDateString(),
        'views' => 500, 'unique_visitors' => 0, 'phone_clicks' => 0,
        'whatsapp_clicks' => 0, 'website_clicks' => 0, 'direction_clicks' => 0, 'social_clicks' => 0,
    ]);

    $props = $this->actingAs($owner)->get('/owner/dashboard')->assertOk()->viewData('page')['props'];

    expect((int) $props['analytics']['stats']['total_views'])->toBe(0);
});
