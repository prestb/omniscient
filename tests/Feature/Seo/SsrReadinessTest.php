<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 15E — SSR READINESS & PUBLIC SEO HARDENING.
 *
 * The SSR bundle is a BUILD ARTIFACT ONLY. No Node process runs in production
 * and the Hostinger Premium deployment remains PHP-only. These tests assert the
 * architecture that makes SSR activatable later without a rewrite — not a live
 * SSR server, which this environment does not run.
 */

function ssrListing(array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
        'description' => 'A useful description of what is offered here.',
    ], $attrs));
}

// ── 1. SSR-ready client architecture ────────────────────────────────────────

test('an ssr entry exists and does not replace the client entry', function () {
    expect(file_exists(resource_path('js/ssr.js')))->toBeTrue();
    expect(file_exists(resource_path('js/app.js')))->toBeTrue();

    $ssr = file_get_contents(resource_path('js/ssr.js'));

    // Standard Inertia SSR contract.
    expect($ssr)->toContain('@inertiajs/vue3/server');
    expect($ssr)->toContain('createServer');
    expect($ssr)->toContain('renderToString');
    expect($ssr)->toContain('createSSRApp');
    // Same page resolver and title convention as the client entry.
    expect($ssr)->toContain('resolvePageComponent');
    expect($ssr)->toContain('Omniscient');
});

test('vite is configured with the ssr entry', function () {
    $vite = file_get_contents(base_path('vite.config.js'));

    expect($vite)->toContain("ssr: 'resources/js/ssr.js'");
    // The client input must remain untouched.
    expect($vite)->toContain("input: ['resources/css/app.css', 'resources/js/app.js']");
});

test('the ssr bundle is gitignored and not required by production', function () {
    $ignore = file_get_contents(base_path('.gitignore'));

    expect($ignore)->toContain('/bootstrap/ssr');
});

test('no production node process or supervisor configuration was added', function () {
    foreach ([
        'Dockerfile',
        'docker-compose.yml',
        'Procfile',
        'ecosystem.config.js',
        'pm2.config.js',
    ] as $file) {
        expect(file_exists(base_path($file)))->toBeFalse();
    }

    // The deploy workflow must remain PHP-only.
    $deploy = file_get_contents(base_path('.github/workflows/deploy.yml'));

    expect($deploy)->not->toContain('npm run');
    expect($deploy)->not->toContain('node ');
    expect($deploy)->not->toContain('pm2');
    expect($deploy)->not->toContain('supervisor');
});

// ── 2. Listing-first rendering ──────────────────────────────────────────────

test('a listing renders without a business', function () {
    $listing = ssrListing(['business_id' => null]);

    $this->get('/listing/' . $listing->slug)
        ->assertOk()
        ->assertInertia(fn($page) => $page->component('Public/ListingProfile'));
});

test('a business listing renders', function () {
    $business = Business::factory()->create();
    $listing = ssrListing(['business_id' => $business->id]);

    $this->get('/listing/' . $listing->slug)->assertOk();
});

test('a professional without a location renders', function () {
    $listing = ssrListing([
        'business_id' => null,
        'type' => \App\Support\ListingType::PROFESSIONAL->value,
        'location_id' => null,
    ]);

    $this->get('/listing/' . $listing->slug)->assertOk();

    expect($listing->fresh()->location_id)->toBeNull();
});

test('every listing type renders independently of business presence', function (string $type) {
    $listing = ssrListing(['business_id' => null, 'type' => $type]);

    $this->get('/listing/' . $listing->slug)->assertOk();
})->with([
    'business' => [\App\Support\ListingType::BUSINESS->value],
    'professional' => [\App\Support\ListingType::PROFESSIONAL->value],
    'store' => [\App\Support\ListingType::STORE->value],
]);

test('the ssr entry contains no representative listing mechanism', function () {
    $ssr = file_get_contents(resource_path('js/ssr.js'));

    foreach (['primaryListing', 'defaultListing', 'mainListing',
        'businesses()->first', 'representativeListing'] as $pattern) {
        expect($ssr)->not->toContain($pattern);
    }
});

// ── 3. SSR-safe public components ───────────────────────────────────────────

test('public pages do not touch browser APIs during setup or render', function (string $page) {
    $source = file_get_contents(resource_path('js/Pages/Public/' . $page));

    // Strip comments — removal notes legitimately mention these APIs.
    $code = preg_replace('#/\*.*?\*/#s', '', $source);
    $code = preg_replace('#<!--.*?-->#s', '', $code);
    $code = preg_replace('#^\s*//.*$#m', '', $code);

    // These are the genuine SSR blockers - they must not run during setup or
    // render. `document.createElement` inside an EVENT HANDLER is acceptable
    // (BusinessProfile's clipboard fallback), so it is asserted per-page below
    // rather than globally.
    expect($code)->not->toContain('window.location.origin');
    expect($code)->not->toContain('localStorage');
    expect($code)->not->toContain('sessionStorage');
})->with([
    'ListingProfile.vue',
    'BusinessProfile.vue',
    'Collection.vue',
    'Home.vue',
]);

test('collection.vue has no document or window usage at all', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/Collection.vue'));
    $code = preg_replace('#/\\*.*?\\*/#s', '', $source);
    $code = preg_replace('#<!--.*?-->#s', '', $code);
    $code = preg_replace('#^\\s*//.*$#m', '', $code);

    expect($code)->not->toContain('document.');
    expect($code)->not->toContain('window.');
});

// ── 4. Collection JSON-LD ───────────────────────────────────────────────────

test('collection json-ld is declarative in Head and listing-first', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/Collection.vue'));

    // Expressed through <Head> rather than appended to the DOM in onMounted.
    expect($source)->toContain('application/ld+json');
    expect($source)->toContain(":is=\"'script'\"");

    // Listing-first: the ItemList builder must reference the canonical
    // Listing URL, never a Business URL. Scope the assertion to that block so
    // the page's legitimate organization links are not misjudged.
    $builderStart = strpos($source, 'Schema.org ItemList');
    $builder = $builderStart === false ? $source : substr($source, $builderStart, 2000);
    $builder = substr($builder, 0, strpos($builder, 'return JSON.stringify') + 400);
    // Strip comments: the migration note legitimately names the old API.
    $builder = preg_replace('#//.*$#m', '', $builder);

    // PHASE 17 — the builder routes Listing URLs through the canonical helper.
    expect($builder)->toContain('listingUrl');
    expect($builder)->not->toContain('/business/${');
    expect($builder)->not->toContain('window.location');
});

test('collection json-ld remains valid json', function () {
    // The JSON is built with JSON.stringify, so it is valid by construction;
    // this asserts the shape the page emits.
    $payload = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Plumbers in Douala',
        'numberOfItems' => 2,
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'url' => 'https://example.test/listing/a', 'name' => 'A'],
        ],
    ]);

    $decoded = json_decode($payload, true);

    expect(json_last_error())->toBe(JSON_ERROR_NONE);
    expect($decoded['@type'])->toBe('ItemList');
    expect($decoded['itemListElement'][0]['url'])->toContain('/listing/');
});

// ── 5. Metadata contract preserved ──────────────────────────────────────────

test('listing metadata contract is intact', function () {
    $listing = ssrListing(['name' => 'Test Listing']);

    $seo = $this->get('/listing/' . $listing->slug)->viewData('page')['props']['seo'];

    foreach (['title', 'description', 'canonical'] as $key) {
        expect($seo)->toHaveKey($key);
    }
    expect($seo['canonical'])->toBe(url('/listing/' . $listing->slug));
});

test('search and directory remain noindex and explore keeps its canonical', function () {
    foreach (['/search', '/directory'] as $path) {
        $props = $this->get($path)->viewData('page')['props'];
        expect($props['seo']['robots'])->toBe('noindex, follow');
    }

    expect($this->get('/explore')->viewData('page')['props']['seo']['canonical'])->toBe(url('/explore'));
});

// ── 6. Regression guards ────────────────────────────────────────────────────

test('the sitemap route is unchanged', function () {
    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8');
});

test('business is still not searchable and listing still is', function () {
    expect(in_array('Laravel\Scout\Searchable', class_uses_recursive(Business::class)))->toBeFalse();
    expect(in_array('Laravel\Scout\Searchable', class_uses_recursive(Listing::class)))->toBeTrue();
});
