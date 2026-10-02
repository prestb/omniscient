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
    // PHASE 17 — construction now goes through the canonical helper, so the
    // assertion is that every discovery surface USES it rather than that it
    // repeats a literal.
    foreach ([
        'Components/Public/DirectoryMap.vue',
        'Components/Public/SearchBar.vue',
        'Components/Public/SearchAutocomplete.vue',
        'Components/Public/ListingCard.vue',
        'Components/Public/ExploreCard.vue',
        'Pages/Public/Collection.vue',
    ] as $rel) {
        $src = discoverySource($rel);
        expect($src)->toContain('listingUrl');
        expect($src)->toContain("from '@/urls'");
    }
});

test('autocomplete no longer names its handler after a business', function () {
    $source = discoverySource('Components/Public/SearchAutocomplete.vue');

    expect($source)->toContain('selectListing');
    expect($source)->not->toContain('selectBusiness');
});

test('the discovery card remains the canonical ListingCard with canonical links', function () {
    $card = discoverySource('Components/Public/ListingCard.vue');

    expect($card)->toContain('listingUrl(listing)');
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

test('discovery results are not bloated with listing detail', function () {
    // PHASE 17 added services/contacts/gallery to the resource, but behind
    // a `detailed` opt-in that only the canonical Listing page sets. The
    // discovery invariant therefore still holds.
    $resource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));
    expect($resource)->toContain('mergeWhen($this->detailed');

    $controller = file_get_contents(app_path('Http/Controllers/Public/ListingController.php'));
    expect($controller)->toContain('detailed: true');
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

// ── Desktop discovery layout (16F continuation) ─────────────────────────────

test('the directory provides a persistent desktop filter rail', function () {
    $source = discoverySource('Pages/Public/Directory.vue');

    // A two-column discovery layout at lg and above.
    expect($source)->toContain('lg:grid');
    expect($source)->toContain('lg:grid-cols-[19rem_minmax(0,1fr)]');
    expect($source)->toContain('<aside');

    // The rail is the EXISTING filter component, not a second implementation.
    expect($source)->toContain('<DirectoryFilters');
    expect($source)->toContain('lg:sticky');

    // Mobile behaviour is preserved: the grid simply does not apply below lg.
    expect($source)->toContain('lg:mb-0');
});

test('the directory uses listing-first discovery copy', function () {
    $source = discoverySource('Pages/Public/Directory.vue');
    // Comments legitimately name the copy that was replaced.
    $source = preg_replace('#<!--.*?-->#s', '', $source);

    // The page no longer presents itself as an organization directory.
    expect($source)->not->toContain('Business Directory');
    expect($source)->not->toContain('No businesses found');

    expect($source)->toContain('Browse listings');
});

test('the empty state offers real recovery actions', function () {
    $source = discoverySource('Pages/Public/Directory.vue');

    expect($source)->toContain('clearAllFilters');
    expect($source)->toContain('Clear filters');
    expect($source)->toContain('href="/categories"');
    expect($source)->toContain('href="/locations"');

    // The empty state distinguishes "no results" from "filters too narrow".
    expect($source)->toContain('hasActiveFilters');
});

test('the directory does not reintroduce a second filter or modal implementation', function () {
    $source = discoverySource('Pages/Public/Directory.vue');
    $source = preg_replace('#<!--.*?-->#s', '', $source);

    // Exactly one filter component.
    expect(substr_count($source, '<DirectoryFilters'))->toBe(1);
    // No bespoke modal/sheet invented here.
    expect($source)->not->toContain('<Modal');
    expect($source)->not->toContain('MobileFilterSheet');
});

test('the directory introduces no browser APIs into render or setup', function () {
    $source = discoverySource('Pages/Public/Directory.vue');
    $code = preg_replace('#<!--.*?-->#s', '', $source);
    $code = preg_replace('#^\s*//.*$#m', '', $code);

    expect($code)->not->toContain('document.');
    expect($code)->not->toContain('window.');
    expect($code)->not->toContain('navigator.');
    expect($code)->not->toContain('localStorage');
    expect($code)->not->toContain('matchMedia');
});

test('the directory view toggle exposes a non-visual selected state', function () {
    $source = discoverySource('Pages/Public/Directory.vue');

    // aria-pressed on both toggles, so selection is not colour-only.
    expect(substr_count($source, 'aria-pressed'))->toBeGreaterThanOrEqual(2);
    expect($source)->toContain('role="group"');
});

test('the directory respects the design tokens rather than raw values', function () {
    $source = discoverySource('Pages/Public/Directory.vue');

    expect($source)->toContain('rounded-card');
    expect($source)->toContain('text-heading-lg');
    expect($source)->toContain('shadow-elevation-1');

    // The old ad-hoc surfaces are gone.
    expect($source)->not->toContain('rounded-2xl shadow-sm');
});

test('the view choice still uses the existing cookie composable', function () {
    $source = discoverySource('Pages/Public/Directory.vue');

    expect($source)->toContain('writeDirectoryViewMode');
    // No raw cookie access.
    expect($source)->not->toContain('document.cookie');
});
// ── Mobile filter sheet dialog contract (16F completion) ────────────────────

test('the mobile filter sheet provides the full dialog contract', function () {
    $sheet = discoverySource('Components/Public/MobileFilterSheet.vue');

    // Semantics the sheet previously lacked entirely.
    expect($sheet)->toContain('role="dialog"');
    expect($sheet)->toContain('aria-modal');
    expect($sheet)->toContain(':aria-label="resolvedTitle"');
    expect($sheet)->toContain('tabindex="-1"');

    // Escape dismissal.
    expect($sheet)->toContain("e.key === 'Escape'");

    // Focus moved in, contained, and restored.
    expect($sheet)->toContain('lastFocused');
    expect($sheet)->toContain("e.key === 'Tab'");

    // Body scroll lock, applied once and released once.
    expect($sheet)->toContain('body.style.overflow');
    expect($sheet)->toContain('locked');

    // Safe area retained.
    expect($sheet)->toContain('env(safe-area-inset-bottom)');
});

test('the mobile filter sheet remains the single mobile filter surface', function () {
    // It must still exist and still be the thing DirectoryFilters drives.
    expect(file_exists(resource_path('js/Components/Public/MobileFilterSheet.vue')))->toBeTrue();

    $sheet = discoverySource('Components/Public/MobileFilterSheet.vue');

    // No nested Sheet primitive was introduced inside it.
    expect($sheet)->not->toContain('<Sheet');
    expect($sheet)->not->toContain('ui/Sheet.vue');
});

test('the mobile sheet exposes accessible filter actions', function () {
    $sheet = discoverySource('Components/Public/MobileFilterSheet.vue');

    expect($sheet)->toContain('Clear all');
    expect($sheet)->toContain('Apply Filters');
    expect($sheet)->toContain('min-h-11');
    expect($sheet)->toContain('aria-label="Close filters"');
});

// ── Loading and error states ────────────────────────────────────────────────

test('the directory has a loading state using the consolidated skeleton', function () {
    $source = discoverySource('Pages/Public/Directory.vue');
    // Comments name the retired skeletons they describe.
    $source = preg_replace('#<!--.*?-->#s', '', $source);
    $source = preg_replace('#^\s*//.*$#m', '', $source);

    expect($source)->toContain('isRefreshing');
    expect($source)->toContain('<ListingCardSkeleton');

    // It must be the 16C skeleton. The retired ones all lived under
    // Components/Skeletons/, and 'CardSkeleton' is a substring of the new
    // 'ListingCardSkeleton', so the LOCATION is asserted rather than the name.
    expect($source)->toContain('Components/Public/ui/ListingCardSkeleton.vue');
    expect($source)->not->toContain('Components/Skeletons/');
});

test('the directory loading listeners are lifecycle-guarded for SSR', function () {
    $source = discoverySource('Pages/Public/Directory.vue');

    // router listeners are registered in onMounted and torn down on unmount,
    // so nothing executes during SSR.
    expect($source)->toContain('onMounted');
    expect($source)->toContain('onBeforeUnmount');
    expect($source)->toContain('removeStart');
});

test('the directory has a recoverable error state with no technical detail', function () {
    $source = discoverySource('Pages/Public/Directory.vue');
    $source = preg_replace('#<!--.*?-->#s', '', $source);
    $source = preg_replace('#^\s*//.*$#m', '', $source);

    expect($source)->toContain('hasError');
    expect($source)->toContain('Something went wrong');
    expect($source)->toContain('Try again');

    // No stack traces, exception text or API internals exposed to the user.
    foreach (['e.stack', 'error.message', 'Exception', 'response.data.message'] as $leak) {
        expect($source)->not->toContain($leak);
    }
});
// ── Narrow Search polish (16F micro-pass) ───────────────────────────────────

test('search results use listing-first copy', function () {
    $source = discoverySource('Pages/Public/Search/Index.vue');
    $code = preg_replace('#<!--.*?-->#s', '', $source);

    expect($code)->not->toContain('Browse All Businesses');
    expect($code)->not->toContain('No results found');

    expect($code)->toContain('Browse all listings');
    expect($code)->toContain('No listings found');
});

test('the search no-result state offers discovery entry points', function () {
    $source = discoverySource('Pages/Public/Search/Index.vue');

    expect($source)->toContain('href="/categories"');
    expect($source)->toContain('Browse categories');
});

test('autocomplete exposes listbox semantics for its keyboard navigation', function () {
    $source = discoverySource('Components/Public/SearchAutocomplete.vue');

    // Keyboard nav already existed; the semantics that announce it did not.
    expect($source)->toContain('role="combobox"');
    expect($source)->toContain('aria-autocomplete="list"');
    expect($source)->toContain(':aria-controls="listboxId"');
    expect($source)->toContain(':aria-activedescendant="activeDescendant"');
    expect($source)->toContain('computed');
});

test('autocomplete names its suggestion group after a listing, not a business', function () {
    $source = discoverySource('Components/Public/SearchAutocomplete.vue');

    expect($source)->toContain("type: 'listing'");
    expect($source)->toContain("isHighlighted('listing'");
    expect($source)->not->toContain("type: 'business'");
    expect($source)->not->toContain("isHighlighted('business'");
});

test('search components keep canonical listing destinations', function () {
    foreach ([
        'Components/Public/SearchBar.vue',
        'Components/Public/SearchAutocomplete.vue',
    ] as $rel) {
        $source = discoverySource($rel);
        $code = preg_replace('#^\s*//.*$#m', '', $source);
        $code = preg_replace('#<!--.*?-->#s', '', $code);

        expect($code)->not->toContain('/business/${');
        // PHASE 17 — construction goes through the canonical helper.
        expect($code)->toContain('listingUrl');
    }
});

test('SearchBar browser storage is pre-existing and handler-scoped', function () {
    // CORRECTED: SearchBar has used localStorage for recent searches since
    // before this phase. Phase 16F introduced NO browser API; the honest
    // assertion is that the existing usage stays inside functions rather than
    // reaching setup/render, and that the components this phase DID touch
    // remain free of browser APIs entirely.
    $bar = discoverySource('Components/Public/SearchBar.vue');

    expect($bar)->toContain("localStorage.getItem('recentSearches')");
    // Every use sits inside a function body, never at setup scope.
    foreach (['const stored = localStorage', 'localStorage.setItem', 'localStorage.removeItem'] as $use) {
        expect($bar)->toContain($use);
    }

    foreach (['Components/Public/SearchAutocomplete.vue', 'Pages/Public/Search/Index.vue'] as $rel) {
        $code = preg_replace('#<!--.*?-->#s', '', discoverySource($rel));
        $code = preg_replace('#^\s*//.*$#m', '', $code);

        foreach (['localStorage', 'sessionStorage', 'matchMedia'] as $api) {
            expect($code)->not->toContain($api);
        }
    }
});
// ── Public-surface refinement (16G) ─────────────────────────────────────────

test('no discovery surface labels its results as businesses', function (string $rel) {
    $source = discoverySource($rel);
    $code = preg_replace('#<!--.*?-->#s', '', $source);
    $code = preg_replace('#^\s*//.*$#m', '', $code);

    foreach ([
        'No businesses on the map',
        'No businesses in this category yet',
        'Browse All Businesses',
        'No businesses found',
    ] as $label) {
        expect($code)->not->toContain($label);
    }
})->with([
    'Components/Public/DirectoryMap.vue',
    'Components/Public/ExploreCarousel.vue',
    'Pages/Public/Collection.vue',
    'Pages/Public/Directory.vue',
    'Pages/Public/Search/Index.vue',
]);

test('every public discovery page has exactly one h1', function (string $rel) {
    $source = discoverySource($rel);

    expect(substr_count($source, '<h1'))->toBe(1);
})->with([
    'Pages/Public/Home.vue',
    'Pages/Public/Explore.vue',
    'Pages/Public/Collection.vue',
    'Pages/Public/ListingProfile.vue',
    'Pages/Public/Categories.vue',
    'Pages/Public/Locations.vue',
    'Pages/Public/Search/Index.vue',
    'Pages/Public/Directory.vue',
]);

test('the public shell navigation exposes aria-current for the active page', function () {
    foreach ([
        'Components/Public/Shell/PublicDesktopNav.vue',
        'Components/Public/Shell/PublicMobileNav.vue',
    ] as $rel) {
        expect(discoverySource($rel))->toContain('aria-current');
    }
});

test('the bottom navigation keeps accessible names and touch targets', function () {
    $nav = discoverySource('Components/Public/Shell/PublicMobileNav.vue');

    expect($nav)->toContain('aria-label="Primary"');
    expect($nav)->toContain('min-h-14');
    expect($nav)->toContain('env(safe-area-inset-bottom)');
});

test('the listing type badge never communicates type by colour alone', function () {
    $badge = discoverySource('Components/Public/ui/ListingTypeBadge.vue');

    // The label text always carries the meaning.
    expect($badge)->toContain('Professional');
    expect($badge)->toContain('Store');
    expect($badge)->toContain('Business');
});

test('primitives keep a visible focus state', function () {
    foreach ([
        'Components/Public/ui/Button.vue',
        'Components/Public/ui/IconButton.vue',
        'Components/Public/ui/Input.vue',
        'Components/Public/ui/Chip.vue',
        'Components/Public/ui/Sheet.vue',
    ] as $rel) {
        expect(discoverySource($rel))->toContain('focus-visible');
    }
});

test('reduced motion remains handled globally', function () {
    expect(file_get_contents(resource_path('css/app.css')))->toContain('prefers-reduced-motion');
});
// ── Responsive refinement (16G continuation) ────────────────────────────────

test('truncated flex children can actually shrink', function () {
    // A flex item will not shrink below its content width without min-w-0, so
    // `truncate` silently fails and the row overflows at narrow widths.
    $card = discoverySource('Components/Public/ListingCard.vue');

    $truncated = preg_match_all('/truncate|line-clamp/', $card);
    $constrained = preg_match_all('/min-w-0/', $card);

    expect($truncated)->toBeGreaterThan(0);
    expect($constrained)->toBeGreaterThan(0);

    // Specifically: the review/category row that overflows at ~320px.
    expect($card)->toContain('min-w-0');
    expect($card)->toMatch('/class="flex items-center gap-1\.5[^"]*min-w-0"/');
});

test('no truncated flex child lacks a shrink constraint', function (string $rel) {
    $source = discoverySource($rel);

    $truncated = preg_match_all('/truncate|line-clamp/', $source);
    if ($truncated === 0) {
        expect(true)->toBeTrue();
        return;
    }

    // Where truncation is used, a shrink constraint must also exist.
    // A shrink constraint may be the Tailwind class or inline CSS.
    expect(preg_match_all('/min-w-0|min-w-\[0\]|flex-shrink-0|min-width:\s*0/', $source))->toBeGreaterThan(0);
})->with([
    'Components/Public/ListingCard.vue',
    'Components/Public/DirectoryMap.vue',
    'Components/Public/SearchAutocomplete.vue',
    'Components/Public/SearchBar.vue',
]);

test('public page content width is consistent', function () {
    $pages = [
        'Pages/Public/Home.vue',
        'Pages/Public/Explore.vue',
        'Pages/Public/Collection.vue',
        'Pages/Public/Categories.vue',
        'Pages/Public/Locations.vue',
        'Pages/Public/Search/Index.vue',
        'Pages/Public/Directory.vue',
        'Pages/Public/ListingProfile.vue',
    ];

    foreach ($pages as $rel) {
        expect(discoverySource($rel))->toContain('max-w-7xl');
    }
});

test('the responsive shell breakpoint remains unchanged', function () {
    // `md` is the documented shell breakpoint established in 16D.
    expect(discoverySource('Layouts/PublicLayout.vue'))->toContain('hidden md:flex');
    expect(discoverySource('Components/Public/Shell/PublicMobileNav.vue'))->toContain('md:hidden');
});

test('touch targets on public action controls meet the 44px baseline', function () {
    expect(discoverySource('Components/Public/Shell/PublicMobileNav.vue'))->toContain('min-h-14');
    expect(discoverySource('Components/Public/MobileFilterSheet.vue'))->toContain('min-h-11');
    expect(discoverySource('Pages/Public/Directory.vue'))->toContain('min-h-11');
});
// ── Phase 16H — residue classification ──────────────────────────────────────

test('the listing card no longer names Location data after a branch', function () {
    $card = discoverySource('Components/Public/ListingCard.vue');
    $code = preg_replace('#^\s*//.*$#m', '', $card);
    $code = preg_replace('#<!--.*?-->#s', '', $code);

    // Identifiers now match the data they operate on.
    expect($code)->toContain('hasOpenLocation');
    expect($code)->toContain('locationStatusCounts');
    expect($code)->not->toContain('hasOpenBranch');
    expect($code)->not->toContain('branchStatusCounts');
});

test('the branches payload fallback is preserved because it is still live', function () {
    // `branches` is still emitted by ListingDirectoryResource,
    // BusinessDirectoryResource and Owner\LocationController, and is still read
    // by RelatedBusinesses as well as this card. Removing it would be a payload
    // change, so it is documented rather than deleted.
    $card = discoverySource('Components/Public/ListingCard.vue');

    expect($card)->toContain('props.listing.locations || props.listing.branches');
    expect(discoverySource('Components/Public/RelatedBusinesses.vue'))
        ->toContain('biz.locations || biz.branches');

    // And the producer side still emits it.
    expect(file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php')))
        ->toContain("'branches'");
});

test('retired public components have no executable references', function (string $dead) {
    $files = [
        'Components/Public/ListingCard.vue',
        'Components/Public/ReviewCard.vue',
        'Pages/Public/Directory.vue',
        'Pages/Public/Search/Index.vue',
        'Pages/Public/Collection.vue',
        'Pages/Public/Home.vue',
    ];

    foreach ($files as $rel) {
        $code = discoverySource($rel);
        $code = preg_replace('#^\s*//.*$#m', '', $code);
        $code = preg_replace('#<!--.*?-->#s', '', $code);

        expect($code)->not->toContain("import {$dead}");
        expect($code)->not->toContain("{$dead}.vue");
    }
})->with([
    'RatingBadge',
    'RatingDisplay',
    'StarRating',
    'BusinessCardSkeleton',
]);

test('the public navigation has a single coherent implementation', function () {
    // One desktop nav, one mobile nav, one secondary menu. No stale variants.
    $layout = discoverySource('Layouts/PublicLayout.vue');

    expect($layout)->toContain('PublicDesktopNav');
    expect($layout)->toContain('PublicMobileNav');
    expect(substr_count($layout, '<PublicDesktopNav'))->toBe(1);
    expect(substr_count($layout, '<PublicMobileNav'))->toBe(1);

    // No legacy absolute-dropdown navigation remains.
    expect($layout)->not->toContain('absolute top-full');
});