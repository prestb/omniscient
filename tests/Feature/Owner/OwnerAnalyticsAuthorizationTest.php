<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingAnalytics;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * WAVE 28B — OWNER ANALYTICS AUTHORIZATION AND PRIVACY.
 *
 * Analytics are LISTING-owned and an account's analytics are derived from ALL the
 * Listings it owns. A Business is an optional filter over that authorized set and
 * can only ever NARROW it.
 *
 * The defect these tests pin: the read surfaces used `$business->listings()`,
 * which (a) excluded business-less Professional Listings entirely and (b) included
 * Listings that merely REFERENCE the organization while being owned by another
 * account.
 *
 * Helper names are prefixed `wa`; Pest shares one process across test files.
 */

function waOwner(): User
{
    return User::factory()->owner()->create();
}

function waProfessionalListing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

function waRecord(Listing $listing, int $views, int $phoneClicks = 0, ?string $date = null): void
{
    ListingAnalytics::create([
        'listing_id' => $listing->id,
        'date' => $date ?? now()->toDateString(),
        'views' => $views,
        'unique_visitors' => 0,
        'phone_clicks' => $phoneClicks,
        'whatsapp_clicks' => 0,
        'website_clicks' => 0,
        'direction_clicks' => 0,
        'social_clicks' => 0,
    ]);
}

// ═══ 1. Business-less Professional ══════════════════════════════════════════

test('an owner with only a business-less Professional Listing can view its analytics', function () {
    $owner = waOwner();
    $listing = waProfessionalListing($owner);
    waRecord($listing, 41, 7);

    $props = $this->actingAs($owner)->get('/owner/analytics')
        ->assertOk()
        ->viewData('page')['props'];

    expect((int) $props['summary']['total_views'])->toBe(41)
        ->and((int) $props['summary']['total_phone_clicks'])->toBe(7)
        ->and($props['selectedBusinessId'])->toBeNull();
});

// ═══ 2. Cross-account isolation ═════════════════════════════════════════════

test('an owner cannot read another account\'s analytics', function () {
    $owner = waOwner();
    waRecord(waProfessionalListing($owner), 5);

    $stranger = waOwner();
    waRecord(waProfessionalListing($stranger), 900);

    $props = $this->actingAs($owner)->get('/owner/analytics')
        ->assertOk()
        ->viewData('page')['props'];

    expect((int) $props['summary']['total_views'])->toBe(5);
});

test('a Listing owned by another account is excluded even when it references my Business', function () {
    $owner = waOwner();
    $business = Business::factory()->create([
        'owner_id' => $owner->id,
        'status' => 'published',
    ]);

    // Mine.
    waRecord(waProfessionalListing($owner, ['business_id' => $business->id]), 10);

    // Somebody else's Listing that merely references my organization.
    $stranger = waOwner();
    $foreign = Listing::factory()->create([
        'owner_id' => $stranger->id,
        'business_id' => $business->id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);
    waRecord($foreign, 500);

    $props = $this->actingAs($owner)
        ->get('/owner/analytics?business=' . $business->id)
        ->assertOk()
        ->viewData('page')['props'];

    expect((int) $props['summary']['total_views'])->toBe(10);
});

// ═══ 3 + 4. Exports ═════════════════════════════════════════════════════════

test('the CSV export contains only the account\'s own Listing data', function () {
    $owner = waOwner();
    waRecord(waProfessionalListing($owner), 3);

    $stranger = waOwner();
    waRecord(waProfessionalListing($stranger), 777);

    $csv = $this->actingAs($owner)->get('/owner/analytics/export/csv')->assertOk()->getContent();

    expect($csv)->toContain('Total Views')
        ->and($csv)->toContain('Phone Clicks')
        ->and($csv)->not->toContain('777');
});

test('the PDF export succeeds for a business-less account', function () {
    $owner = waOwner();
    waRecord(waProfessionalListing($owner), 12);

    $this->actingAs($owner)->get('/owner/analytics/export/pdf')->assertOk();
});

// ═══ 5. Sibling attribution ═════════════════════════════════════════════════

test('sibling Listings remain independently attributable in the aggregate', function () {
    $owner = waOwner();
    waRecord(waProfessionalListing($owner), 4);
    waRecord(waProfessionalListing($owner), 6);

    $props = $this->actingAs($owner)->get('/owner/analytics')
        ->assertOk()
        ->viewData('page')['props'];

    expect((int) $props['summary']['total_views'])->toBe(10)
        ->and($props['listingsCount'])->toBe(2);
});

// ═══ 6. Business filtering narrows only ═════════════════════════════════════

test('the Business filter only narrows the account-authorized set', function () {
    $owner = waOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id, 'status' => 'published']);

    waRecord(waProfessionalListing($owner), 100);                                   // business-less
    waRecord(waProfessionalListing($owner, ['business_id' => $business->id]), 9);   // in the org

    $all = $this->actingAs($owner)->get('/owner/analytics')->assertOk()->viewData('page')['props'];
    expect((int) $all['summary']['total_views'])->toBe(109);   // account-wide by default

    $filtered = $this->actingAs($owner)
        ->get('/owner/analytics?business=' . $business->id)
        ->assertOk()
        ->viewData('page')['props'];

    expect((int) $filtered['summary']['total_views'])->toBe(9);   // narrowed, never expanded
});

// ═══ 7. days cannot bypass ownership ════════════════════════════════════════

test('the days parameter cannot bypass ownership restrictions', function () {
    $owner = waOwner();
    $stranger = waOwner();
    waRecord(waProfessionalListing($stranger), 500, 0, now()->subDay()->toDateString());

    foreach ([7, 9999, -1, 0, 'abc'] as $days) {
        $props = $this->actingAs($owner)
            ->get('/owner/analytics?days=' . $days)
            ->assertOk()
            ->viewData('page')['props'];

        expect((int) $props['summary']['total_views'])->toBe(0);
    }
});

test('an unsupported days value falls back to the default period', function () {
    $owner = waOwner();
    waProfessionalListing($owner);

    $props = $this->actingAs($owner)->get('/owner/analytics?days=9999')
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['days'])->toBe(30);
});

// ═══ 8. Established authorization response ══════════════════════════════════

test('an unauthenticated request is redirected to login', function () {
    $this->get('/owner/analytics')->assertRedirect('/login');
    $this->get('/owner/analytics/export/csv')->assertRedirect('/login');
});

test('a non-owner cannot reach owner analytics', function () {
    $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

    $this->actingAs($user)->get('/owner/analytics')->assertRedirect(route('user.dashboard'));
});

// ═══ 9. No private account identifiers ══════════════════════════════════════

test('the analytics payload does not disclose another account\'s identifiers', function () {
    $owner = waOwner();
    waRecord(waProfessionalListing($owner), 1);

    $stranger = waOwner();
    waRecord(waProfessionalListing($stranger), 2);

    $response = $this->actingAs($owner)->get('/owner/analytics')->assertOk();

    expect($response->getContent())->not->toContain($stranger->email);
});
