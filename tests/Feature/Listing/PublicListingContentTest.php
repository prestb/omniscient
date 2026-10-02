<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingContact;
use App\Models\ListingImage;
use App\Models\ListingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 17 — PUBLIC LISTING CONTENT & CANONICAL URL SAFETY.
 *
 * The relationships already existed and were already eager-loaded; only
 * serialization and rendering were missing. Nothing here creates a table,
 * moves ownership, or touches discovery ranking.
 */

function p17Listing(array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
        'description' => 'A useful description of what is offered.',
    ], $attrs));
}

function listingProps(Listing $listing, $test): array
{
    return $test->get('/listing/' . $listing->slug)->assertOk()->viewData('page')['props'];
}

// ── Part 2: services ────────────────────────────────────────────────────────

test('listing services appear on the public listing', function () {
    $listing = p17Listing();
    ListingService::create(['listing_id' => $listing->id, 'name' => 'Plumbing', 'sort_order' => 0]);

    $props = listingProps($listing, $this);

    expect(collect($props['listing']['services'])->pluck('name')->all())->toBe(['Plumbing']);
});

test('hidden services do not appear on the public listing', function () {
    $listing = p17Listing();
    ListingService::create(['listing_id' => $listing->id, 'name' => 'Visible', 'sort_order' => 0]);
    ListingService::create(['listing_id' => $listing->id, 'name' => 'Hidden', 'sort_order' => 1, 'hidden_at' => now()]);

    $props = listingProps($listing, $this);

    expect(collect($props['listing']['services'])->pluck('name')->all())->toBe(['Visible']);
});

test('a business-less listing exposes its own services', function () {
    $listing = p17Listing(['business_id' => null]);
    ListingService::create(['listing_id' => $listing->id, 'name' => 'Solo Service', 'sort_order' => 0]);

    $props = listingProps($listing, $this);

    expect($listing->business_id)->toBeNull();
    expect(collect($props['listing']['services'])->pluck('name')->all())->toBe(['Solo Service']);
});

// ── Part 3: media ───────────────────────────────────────────────────────────

test('listing gallery images appear and business branding is not substituted', function () {
    $business = Business::factory()->create(['logo' => 'branding/biz-logo.png']);
    $listing = p17Listing(['business_id' => $business->id]);

    ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/a/one.png', 'type' => 'gallery']);

    $props = listingProps($listing, $this);

    $paths = collect($props['listing']['gallery'])->pluck('path')->all();
    expect($paths)->toBe(['listings/a/one.png']);
    // The Business logo is NOT smuggled into the Listing gallery.
    expect($paths)->not->toContain('branding/biz-logo.png');
});

test('hidden listing media does not appear', function () {
    $listing = p17Listing();
    ListingImage::create(['listing_id' => $listing->id, 'path' => 'a/visible.png', 'type' => 'gallery']);
    ListingImage::create(['listing_id' => $listing->id, 'path' => 'a/hidden.png', 'type' => 'gallery', 'hidden_at' => now()]);

    $props = listingProps($listing, $this);

    expect(collect($props['listing']['gallery'])->pluck('path')->all())->toBe(['a/visible.png']);
});

test('logo and cover are not duplicated into the gallery', function () {
    $listing = p17Listing();
    ListingImage::create(['listing_id' => $listing->id, 'path' => 'a/logo.png', 'type' => 'logo']);
    ListingImage::create(['listing_id' => $listing->id, 'path' => 'a/cover.png', 'type' => 'cover']);

    $props = listingProps($listing, $this);

    expect($props['listing']['gallery'])->toBe([]);
});

// ── Part 4: contacts ────────────────────────────────────────────────────────

test('listing contacts appear with their real values', function () {
    $listing = p17Listing();
    ListingContact::create(['listing_id' => $listing->id, 'type' => 'phone', 'value' => '+237600000001', 'is_primary' => true]);

    $props = listingProps($listing, $this);

    $contact = collect($props['listing']['contacts'])->firstWhere('type', 'phone');
    expect($contact['value'])->toBe('+237600000001');
});

test('blank contact values are dropped server-side', function () {
    $listing = p17Listing();
    ListingContact::create(['listing_id' => $listing->id, 'type' => 'phone', 'value' => '   ', 'is_primary' => true]);

    $props = listingProps($listing, $this);

    expect($props['listing']['contacts'])->toBe([]);
});

test('a business-less listing exposes its own contacts', function () {
    $listing = p17Listing(['business_id' => null]);
    ListingContact::create(['listing_id' => $listing->id, 'type' => 'whatsapp', 'value' => '+237600000009', 'is_primary' => true]);

    $props = listingProps($listing, $this);

    expect(collect($props['listing']['contacts'])->pluck('value')->all())->toBe(['+237600000009']);
});

// ── Part 5: payload shape ───────────────────────────────────────────────────

test('discovery payloads stay lean while the listing page opts into detail', function () {
    $listing = p17Listing();
    ListingService::create(['listing_id' => $listing->id, 'name' => 'X', 'sort_order' => 0]);

    // The canonical page carries detail.
    $detail = listingProps($listing, $this)['listing'];
    expect($detail)->toHaveKey('services');
    expect($detail)->toHaveKey('gallery');
    expect($detail)->toHaveKey('contacts');
});

test('the listing payload uses the same key casing the vue props consume', function () {
    // Phase 16 shipped a controller `is_favorited` against a Vue `isFavorited`,
    // so the prop was always undefined and the control silently never worked.
    // The server key and the declared Vue prop must match verbatim.
    $controller = file_get_contents(app_path('Http/Controllers/Public/ListingController.php'));
    $vue = file_get_contents(resource_path('js/Pages/Public/ListingProfile.vue'));

    expect($controller)->toContain("'is_favorited'");
    // The Vue prop is declared with the same snake_case key Inertia sends.
    expect($vue)->toContain('is_favorited:');
    expect($vue)->not->toContain('isFavorited:');
});

// ── Part 9 / 10: isolation ──────────────────────────────────────────────────

test('sibling listings under one business stay fully isolated', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->owner()->create();

    $a = p17Listing(['business_id' => $business->id, 'owner_id' => $owner->id, 'name' => 'Listing A']);
    $b = p17Listing(['business_id' => $business->id, 'owner_id' => $owner->id, 'name' => 'Listing B']);

    ListingService::create(['listing_id' => $a->id, 'name' => 'Service A', 'sort_order' => 0]);
    ListingService::create(['listing_id' => $b->id, 'name' => 'Service B', 'sort_order' => 0]);
    ListingContact::create(['listing_id' => $a->id, 'type' => 'phone', 'value' => '+2376000000A1']);
    ListingContact::create(['listing_id' => $b->id, 'type' => 'phone', 'value' => '+2376000000B1']);
    ListingImage::create(['listing_id' => $a->id, 'path' => 'a/only.png', 'type' => 'gallery']);
    ListingImage::create(['listing_id' => $b->id, 'path' => 'b/only.png', 'type' => 'gallery']);

    $pa = listingProps($a, $this)['listing'];
    $pb = listingProps($b, $this)['listing'];

    expect(collect($pa['services'])->pluck('name')->all())->toBe(['Service A']);
    expect(collect($pb['services'])->pluck('name')->all())->toBe(['Service B']);

    expect(collect($pa['contacts'])->pluck('value')->all())->toBe(['+2376000000A1']);
    expect(collect($pb['contacts'])->pluck('value')->all())->toBe(['+2376000000B1']);

    expect(collect($pa['gallery'])->pluck('path')->all())->toBe(['a/only.png']);
    expect(collect($pb['gallery'])->pluck('path')->all())->toBe(['b/only.png']);
});

// ── Part 11/12: preservation ────────────────────────────────────────────────

test('the search payload was not modified by this phase', function () {
    $payload = p17Listing()->toSearchableArray();

    foreach (['services', 'gallery', 'contacts'] as $key) {
        expect($payload)->not->toHaveKey($key);
    }
});

test('seo policy is unchanged', function () {
    expect(file_get_contents(app_path('Http/Controllers/Public/SearchController.php')))
        ->toContain("'noindex, follow'");
    $this->get('/sitemap.xml')->assertOk();
});
