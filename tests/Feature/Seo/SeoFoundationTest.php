<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 15B — SEO FOUNDATION & CRAWL CONTROL.
 *
 * SSR is DISABLED in this application (no resources/js/ssr.js, no bootstrap/ssr,
 * no config/inertia.php, no ssr entry in vite.config.js, build script is plain
 * `vite build`). Pages are client-rendered, so these tests assert the Inertia
 * PROPS that the `<Head>` component renders — that is the honest level at which
 * metadata can be verified here. They do not claim server-rendered HTML.
 */

function seoListing(array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
        'description' => 'A genuinely useful description of the services offered.',
    ], $attrs));
}

// ── Sitemap ─────────────────────────────────────────────────────────────────

test('the sitemap endpoint serves xml', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertSee('<urlset', false);
});

test('a published listing appears in the sitemap', function () {
    $listing = seoListing();

    $this->get('/sitemap.xml')->assertSee('/listing/' . $listing->slug, false);
});

test('unpublished, hidden, suspended and deleted listings do not appear', function () {
    $draft = seoListing(['status' => Listing::STATUS_DRAFT]);
    $hidden = seoListing(['hidden_at' => now()]);
    $suspended = seoListing(['status' => Listing::STATUS_SUSPENDED]);
    $deleted = seoListing();
    $deleted->delete();

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->not->toContain($draft->slug);
    expect($xml)->not->toContain($hidden->slug);
    expect($xml)->not->toContain($suspended->slug);
    expect($xml)->not->toContain($deleted->slug);
});

test('a business with a published listing appears, one without does not', function () {
    $withListing = Business::factory()->create(['status' => 'published']);
    seoListing(['business_id' => $withListing->id]);

    $empty = Business::factory()->create(['status' => 'published']);

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->toContain('/business/' . $withListing->slug);
    expect($xml)->not->toContain('/business/' . $empty->slug);
});

test('the sitemap never contains query-parameter or json urls', function () {
    seoListing();

    $xml = $this->get('/sitemap.xml')->getContent();

    foreach (['/search?', '/directory?', '/explore?', '?page=', '?sort=',
        '/search/autocomplete', '/api/', '/sitemap.xml?'] as $bad) {
        expect($xml)->not->toContain($bad);
    }
});

test('the sitemap emits no duplicate urls', function () {
    $a = seoListing();
    $b = seoListing();

    $xml = $this->get('/sitemap.xml')->getContent();
    preg_match_all('#<loc>(.*?)</loc>#', $xml, $m);

    expect($m[1])->toBe(array_values(array_unique($m[1])));
    expect(count($m[1]))->toBe(2);
});

test('a valid curated collection appears once inventory supports it', function () {
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = Category::create(['name' => 'Plumbers', 'slug' => 'plumbers-' . uniqid(), 'is_active' => true]);

    // CollectionService::isValidCollection() enforces a minimum inventory, so
    // create enough published Listings in the city for it to qualify.
    for ($i = 0; $i < 5; $i++) {
        $listing = seoListing();
        $listing->update(['location_id' => Location::factory()->create(['city_id' => $city->id])->id]);
        $listing->categories()->sync([$category->id]);
    }

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->toContain('in-' . $city->slug);
});

// ── Listing metadata ────────────────────────────────────────────────────────

test('a listing page exposes title, description, canonical and open graph', function () {
    $listing = seoListing(['name' => 'Bamenda Plumbers']);

    $props = $this->get('/listing/' . $listing->slug)
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['seo']['title'])->toContain('Bamenda Plumbers');
    expect($props['seo']['description'])->not->toBe('');
    expect($props['seo']['canonical'])->toBe(url('/listing/' . $listing->slug));
    expect($props['seo']['type'])->toBe('website');
});

test('listing canonical is derived from the configured app url', function () {
    $listing = seoListing();

    $props = $this->get('/listing/' . $listing->slug)->viewData('page')['props'];

    expect($props['seo']['canonical'])->toStartWith(config('app.url'));
    expect($props['seo']['canonical'])->not->toContain('?');
});

test('a business-less listing produces metadata without a business', function () {
    $listing = seoListing(['business_id' => null]);

    $props = $this->get('/listing/' . $listing->slug)
        ->assertOk()
        ->viewData('page')['props'];

    expect($listing->business_id)->toBeNull();
    expect($props['seo']['title'])->not->toBe('');
    expect($props['seo']['canonical'])->toBe(url('/listing/' . $listing->slug));
});

test('a listing with no description falls back to real fields, not invented copy', function () {
    $listing = seoListing(['name' => 'Alpha Works', 'description' => '']);

    $props = $this->get('/listing/' . $listing->slug)->viewData('page')['props'];

    expect($props['seo']['description'])->toContain('Alpha Works');
});

test('a published listing is not marked noindex', function () {
    $listing = seoListing();

    $props = $this->get('/listing/' . $listing->slug)->viewData('page')['props'];

    expect($props['seo']['robots'] ?? null)->toBeNull();
});

// ── Business metadata ───────────────────────────────────────────────────────

test('a business page exposes title, description, canonical and open graph', function () {
    $business = Business::factory()->create([
        'status' => 'published',
        'hidden_at' => null,
        'name' => 'Acme Ltd',
        'description' => 'We do things.',
    ]);
    // The organization page requires the owner's active subscription.
    $owner = User::factory()->owner()->create();
    $business->update(['owner_id' => $owner->id]);
    $plan = \App\Models\Plan::factory()->create(['max_listings' => 5]);
    \App\Models\Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => \App\Models\Subscription::STATUS_ACTIVE,
    ]);

    $props = $this->get('/business/' . $business->slug)
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['seo']['title'])->toContain('Acme Ltd');
    expect($props['seo']['description'])->toContain('We do things.');
    expect($props['seo']['canonical'])->toBe(url('/business/' . $business->slug));
});

// ── Utility indexation ──────────────────────────────────────────────────────

test('search and directory are noindex, follow', function () {
    foreach (['/search', '/directory'] as $path) {
        $props = $this->get($path)->assertOk()->viewData('page')['props'];

        expect($props['seo']['robots'])->toBe('noindex, follow');
    }
});

test('explore keeps its existing canonical', function () {
    $props = $this->get('/explore')->assertOk()->viewData('page')['props'];

    expect($props['seo']['canonical'])->toBe(url('/explore'));
});

// ── Regression ──────────────────────────────────────────────────────────────

test('business is still not searchable and listing remains canonical', function () {
    expect(in_array('Laravel\Scout\Searchable', class_uses_recursive(Business::class)))->toBeFalse();
    expect(in_array('Laravel\Scout\Searchable', class_uses_recursive(Listing::class)))->toBeTrue();
});

test('the search payload was not modified by this phase', function () {
    $payload = seoListing()->toSearchableArray();

    foreach (['completeness', 'seo', 'canonical', 'meta_description'] as $key) {
        expect($payload)->not->toHaveKey($key);
    }
});

test('robots.txt exposes the sitemap and does not block canonical content', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('/sitemap.xml');
    expect($robots)->not->toContain('Disallow: /listing/');
    expect($robots)->not->toContain('Disallow: /business/');
    // Utility pages must stay FETCHABLE so the noindex directive can be seen.
    expect($robots)->not->toContain("Disallow: /search\n");
    expect($robots)->not->toContain("Disallow: /directory\n");
});
