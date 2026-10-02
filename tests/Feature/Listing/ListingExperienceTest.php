<?php

use App\Models\Favorite;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * PHASE 16E CORRECTION — Listing favorite surfaced; gallery/contact payload
 * gaps recorded rather than papered over.
 */

function favListing(): Listing
{
    return Listing::factory()->create([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
        'business_id' => null,
    ]);
}

// ── Listing-scoped favorite ─────────────────────────────────────────────────

test('a guest sees the listing as not saved', function () {
    $listing = favListing();

    $props = $this->get('/listing/' . $listing->slug)->viewData('page')['props'];

    expect($props['is_favorited'])->toBeFalse();
});

test('the viewer own favorite is reflected for that exact listing', function () {
    $user = User::factory()->create();
    $listing = favListing();

    Favorite::create(['user_id' => $user->id, 'listing_id' => $listing->id]);

    $props = $this->actingAs($user)
        ->get('/listing/' . $listing->slug)
        ->viewData('page')['props'];

    expect($props['is_favorited'])->toBeTrue();
});

test('another user favorite does not leak into the page state', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $listing = favListing();

    Favorite::create(['user_id' => $other->id, 'listing_id' => $listing->id]);

    $props = $this->actingAs($owner)
        ->get('/listing/' . $listing->slug)
        ->viewData('page')['props'];

    expect($props['is_favorited'])->toBeFalse();
});

test('favoriting one listing does not mark a sibling listing as saved', function () {
    $user = User::factory()->create();
    $a = favListing();
    $b = favListing();

    Favorite::create(['user_id' => $user->id, 'listing_id' => $a->id]);

    $propsB = $this->actingAs($user)
        ->get('/listing/' . $b->slug)
        ->viewData('page')['props'];

    expect($propsB['is_favorited'])->toBeFalse();
});

test('favorites remain listing-owned, never business-owned', function () {
    // Wave 1D-5A invariant: a favorite targets a Listing.
    expect(Schema::hasColumn('favorites', 'listing_id'))->toBeTrue();
    expect(Schema::hasColumn('favorites', 'business_id'))->toBeFalse();
});

test('the favorite endpoint remains listing-scoped and no new route was added', function () {
    $routes = collect(app('router')->getRoutes())->map(fn($r) => $r->uri())->all();

    expect($routes)->toContain('favorites/{listing}/toggle');
    // No business-targeted favorite route.
    expect(collect($routes)->filter(fn($u) => str_contains($u, 'favorites') && str_contains($u, 'business'))->all())->toBe([]);
});

// ── Documented payload gaps (asserted, not fixed) ───────────────────────────

test('gallery images are genuinely absent from the public listing payload', function () {
    // Reported gap: the resource exposes only a COUNT, never the image records,
    // so the Listing page cannot render a gallery without a backend resource
    // change — which this phase is not permitted to make.
    $resource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));

    expect($resource)->toContain('gallery_images_count');
    expect($resource)->not->toContain("'gallery_images' =>");
    expect($resource)->not->toContain("'images' =>");
});

test('listing contacts are exposed and the inquiry form remains the attributed path', function () {
    // CORRECTED FINDING: neither a contacts collection nor a location phone is
    // exposed. The Listing page's existing tel: link is therefore dead code and
    // can never render. The only engagement path is the Phase 12 inquiry form,
    // which IS Listing-scoped. Surfacing phone/WhatsApp/website requires a
    // resource change this phase is not permitted to make.
    $resource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));

    expect($resource)->toContain("'contacts' =>");
    // PHASE 17 supersedes the Phase 16E finding above: contacts ARE now\n    // serialized from the Listing-owned listing_contacts records.\n    expect(file_get_contents(resource_path('js/urls.js')))->toContain('export function contactHref');
    expect(file_get_contents(resource_path('js/Pages/Public/ListingProfile.vue')))->toContain('contactHref');

    // The inquiry form remains the real, Listing-attributed engagement path.
    expect(file_get_contents(resource_path('js/Pages/Public/ListingProfile.vue')))
        ->toContain('LeadCaptureForm');
});

test('listing detail is opt-in so discovery payloads stay lean', function () {
    // PHASE 17 changed this contract: services/contacts/gallery are now
    // serialized, but ONLY for the canonical Listing page. The invariant
    // that still matters is that discovery payloads are not bloated.
    $resource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));

    expect($resource)->toContain('mergeWhen($this->detailed');
    expect($resource)->toContain('private bool $detailed = false');
});

// ── Preserved invariants ────────────────────────────────────────────────────

test('no verification implication was introduced on the listing page', function () {
    $page = file_get_contents(resource_path('js/Pages/Public/ListingProfile.vue'));

    expect($page)->not->toContain('is_verified');
    expect($page)->not->toContain('Verified');
});

test('reviews remain business-owned after the correction', function () {
    expect(Schema::hasColumn('reviews', 'listing_id'))->toBeFalse();
    expect(Schema::hasColumn('reviews', 'business_id'))->toBeTrue();
});

test('the listing page uses no browser APIs at render or setup scope', function () {
    $page = file_get_contents(resource_path('js/Pages/Public/ListingProfile.vue'));

    expect($page)->not->toContain('window.');
    expect($page)->not->toContain('navigator.');
    expect($page)->not->toContain('localStorage');
    expect($page)->not->toContain('matchMedia');
});
