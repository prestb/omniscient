<?php

use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 16F — SEARCH, FILTER & MAP EXPERIENCE.
 *
 * Discovery is Listing-centric (Phase 11/1D-1): the directory, the map and the
 * autocomplete endpoint all return LISTINGS. These tests assert that the
 * discovery UI reflects that and that no discovery component sends a visitor to
 * a Business URL built from a Listing slug.
 */

function discoverySource(string $rel): string
{
    return file_get_contents(resource_path('js/' . $rel));
}

// ── Result identity: Listing, not Business ──────────────────────────────────

test('no discovery component links a listing slug to the business route', function (string $rel) {
    $source = discoverySource($rel);

    // Comments in this codebase legitimately explain the fixed defect.
    $code = preg_replace('#<!--.*?-->#s', '', $source);
    $code = preg_replace('#^\s*//.*$#m', '', $code);

    expect($code)->not->toContain('/business/${');
})->with([
    'Components/Public/DirectoryMap.vue',
    'Components/Public/SearchBar.vue',
    'Components/Public/SearchAutocomplete.vue',
    'Components/Public/ListingCard.vue',
    'Pages/Public/Directory.vue',
    'Pages/Public/Search/Index.vue',
    'Pages/Public/Collection.vue',
]);

test('discovery results navigate to the canonical listing url', function () {
    // Map popup.
    expect(discoverySource('Components/Public/DirectoryMap.vue'))
        ->toContain('`/listing/${encodeURIComponent(business.slug)}`');

    // Search bar suggestion.
    expect(discoverySource('Components/Public/SearchBar.vue'))
        ->toContain('`/listing/${suggestion.slug}`');

    // Autocomplete.
    expect(discoverySource('Components/Public/SearchAutocomplete.vue'))
        ->toContain('`/listing/${biz.slug}`');
});

test('autocomplete no longer names its handler after a business', function () {
    $source = discoverySource('Components/Public/SearchAutocomplete.vue');

    expect($source)->toContain('selectListing');
    expect($source)->not->toContain('selectBusiness');
});

test('the discovery card remains the canonical ListingCard with canonical links', function () {
    $card = discoverySource('Components/Public/ListingCard.vue');

    expect($card)->toContain('`/listing/${listing.slug}`');
    expect($card)->toContain('ListingTypeBadge');

    // Old primitives must not return to the canonical card.
    foreach (['RatingBadge', 'RatingDisplay', 'StarRating'] as $old) {
        expect($card)->not->toContain($old);
    }
});

// ── Filters are backed by real data ─────────────────────────────────────────

test('the directory filter vocabulary matches the indexed filterable attributes', function () {
    $config = file_get_contents(app_path('Console/Commands/ConfigureMeilisearch.php'));

    // Every filter the directory exposes must correspond to something the
    // backend can actually filter on.
    foreach (['status', 'type', 'is_featured', 'hidden', 'is_open_now', 'category_ids', 'city_id'] as $attribute) {
        expect($config)->toContain($attribute);
    }
});

// ── SEO policy preserved ────────────────────────────────────────────────────

test('search and directory remain non-indexable utilities', function () {
    $search = file_get_contents(app_path('Http/Controllers/Public/SearchController.php'));
    $directory = file_get_contents(app_path('Http/Controllers/Public/DirectoryController.php'));

    expect($search)->toContain("'noindex, follow'");
    expect($directory)->toContain("'noindex, follow'");
});

test('the sitemap route is untouched', function () {
    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8');
});

// ── No ranking policy change ────────────────────────────────────────────────

test('search ranking rules were not modified in this phase', function () {
    $config = file_get_contents(app_path('Console/Commands/ConfigureMeilisearch.php'));

    // The Phase 13 contract must still hold.
    foreach (['words', 'typo', 'proximity', 'attributeRank', 'exactness',
        'rating:desc', 'is_featured_rank:desc'] as $rule) {
        expect($config)->toContain($rule);
    }

    // No completeness / verification / popularity weighting was introduced.
    foreach (['completeness', 'verified', 'popularity'] as $forbidden) {
        expect($config)->not->toContain($forbidden);
    }
});

test('no payload expansion was made for discovery', function () {
    $resource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));

    // The three known gaps stay gaps, as Phase 16F requires.
    expect($resource)->not->toContain("'services' =>");
    expect($resource)->not->toContain("'contacts' =>");
    expect($resource)->not->toContain("'images' =>");
});

// ── Listing-type neutrality ─────────────────────────────────────────────────

test('discovery works for a business-less listing', function () {
    $listing = Listing::factory()->create([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
        'business_id' => null,
    ]);

    $this->get('/listing/' . $listing->slug)->assertOk();

    expect($listing->fresh()->business_id)->toBeNull();
});

test('every listing type resolves independently of business presence', function (string $type) {
    $listing = Listing::factory()->create([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
        'business_id' => null,
        'type' => $type,
    ]);

    $this->get('/listing/' . $listing->slug)->assertOk();
})->with([
    'business' => [\App\Support\ListingType::BUSINESS->value],
    'professional' => [\App\Support\ListingType::PROFESSIONAL->value],
    'store' => [\App\Support\ListingType::STORE->value],
]);
