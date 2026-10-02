<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 19C — ADMIN LISTINGS + verification-semantic removal.
 */

function admin(): User
{
    return User::factory()->create(['role' => User::ROLE_ADMIN]);
}

function c19Listing(array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

// ── Admin Listings index ────────────────────────────────────────────────────

test('an admin can open the listings index', function () {
    $this->actingAs(admin())->get('/admin/listings')->assertOk();
});

test('the listings index lists listings', function () {
    $listing = c19Listing(['name' => 'Visible Plumbing']);

    $props = $this->actingAs(admin())->get('/admin/listings')->viewData('page')['props'];

    expect(collect($props['listings']['data'])->pluck('name')->all())->toContain('Visible Plumbing');
});

test('a business-less professional is a first-class admin record', function () {
    $owner = User::factory()->owner()->create();
    $listing = c19Listing([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
        'name' => 'Independent Electrician',
    ]);

    $props = $this->actingAs(admin())->get('/admin/listings')->viewData('page')['props'];

    $row = collect($props['listings']['data'])->firstWhere('name', 'Independent Electrician');
    expect($row)->not->toBeNull();
    expect($row['business_id'])->toBeNull();
});

test('admin can filter to independent listings', function () {
    $business = Business::factory()->create();
    c19Listing(['business_id' => $business->id, 'name' => 'Grouped One']);
    c19Listing(['business_id' => null, 'name' => 'Independent One']);

    $props = $this->actingAs(admin())
        ->get('/admin/listings?affiliation=independent')
        ->viewData('page')['props'];

    $names = collect($props['listings']['data'])->pluck('name')->all();
    expect($names)->toContain('Independent One');
    expect($names)->not->toContain('Grouped One');
});

test('admin can filter by listing type', function () {
    c19Listing(['type' => 'professional', 'name' => 'A Pro']);
    c19Listing(['type' => 'store', 'name' => 'A Store']);

    $props = $this->actingAs(admin())
        ->get('/admin/listings?type=store')
        ->viewData('page')['props'];

    $names = collect($props['listings']['data'])->pluck('name')->all();
    expect($names)->toContain('A Store');
    expect($names)->not->toContain('A Pro');
});

test('admin can filter by location presence', function () {
    $business = Business::factory()->create();
    $withLoc = c19Listing(['name' => 'Has Place', 'location_id' => null]);
    c19Listing(['name' => 'No Place', 'location_id' => null]);

    $props = $this->actingAs(admin())
        ->get('/admin/listings?location=without')
        ->viewData('page')['props'];

    $names = collect($props['listings']['data'])->pluck('name')->all();
    expect($names)->toContain('No Place');
});

test('admin can search listings', function () {
    c19Listing(['name' => 'Findable Service']);
    c19Listing(['name' => 'Other Service']);

    $props = $this->actingAs(admin())
        ->get('/admin/listings?search=Findable')
        ->viewData('page')['props'];

    expect(collect($props['listings']['data'])->pluck('name')->all())->toBe(['Findable Service']);
});

test('the listings index rejects a non-admin', function () {
    $owner = User::factory()->owner()->create();

    // The `role:admin,super_admin` middleware redirects an unauthorized user.
    $this->actingAs($owner)->get('/admin/listings')->assertRedirect();
});

// ── Admin Listing detail ────────────────────────────────────────────────────

test('admin can inspect a grouped listing and reach its public url', function () {
    $business = Business::factory()->create(['name' => 'Acme Group']);
    $listing = c19Listing(['business_id' => $business->id, 'type' => 'store']);

    $props = $this->actingAs(admin())
        ->get("/admin/listings/{$listing->id}")
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['listing']['listing_type'])->toBe('store');
    expect($props['listing']['status'])->toBe('published');
    // The canonical public destination, so admin can move to what people see.
    expect($props['listing']['public_url'])->toBe('/listing/' . $listing->slug);
    expect($props['listing']['is_independent'])->toBeFalse();

    expect($props['business']['name'])->toBe('Acme Group');
    expect($props['business']['public_url'])->toBe('/business/' . $business->slug);
});

test('admin can inspect a business-less listing without it reading as broken', function () {
    $listing = c19Listing(['business_id' => null, 'location_id' => null]);

    $props = $this->actingAs(admin())
        ->get("/admin/listings/{$listing->id}")
        ->assertOk()
        ->viewData('page')['props'];

    // Absence of a Business is an intentional state, expressed as null + a flag.
    expect($props['business'])->toBeNull();
    expect($props['listing']['is_independent'])->toBeTrue();
    expect($props['location'])->toBeNull();
    // The public page still resolves.
    $this->get('/listing/' . $listing->slug)->assertOk();
});

test('the admin listing surface is read-only', function () {
    $listing = c19Listing();

    // No edit/update/moderation routes were introduced.
    $names = array_keys(app('router')->getRoutes()->getRoutesByName());

    foreach (['admin.listings.update', 'admin.listings.edit', 'admin.listings.destroy', 'admin.listings.approve'] as $forbidden) {
        expect($names)->not->toContain($forbidden);
    }

    expect($names)->toContain('admin.listings.index');
    expect($names)->toContain('admin.listings.show');
});

// ── Verification-semantic removal ───────────────────────────────────────────

test('the paid-as-verification filter is gone from discovery', function () {
    $listing = c19Listing();

    // Requesting it must not change the result set or the payload.
    $plain = $this->get('/directory')->viewData('page')['props']['listings']['total'] ?? null;
    $withFlag = $this->get('/directory?verified=true')->viewData('page')['props']['listings']['total'] ?? null;

    expect($withFlag)->toBe($plain);
});

test('no executable verified filter remains in the directory controller', function () {
    $source = file_get_contents(app_path('Http/Controllers/Public/DirectoryController.php'));

    // Strip comments: the Phase 14 docblock legitimately explains the history.
    $code = preg_replace('#^\s*//.*$#m', '', $source);

    expect($code)->not->toContain("canUse('verified_badge')");
    expect($code)->not->toContain("\$request->verified");
});

test('the public directory offers no verified filter control', function () {
    $filters = file_get_contents(resource_path('js/Components/Public/DirectoryFilters.vue'));
    $code = preg_replace('#<!--.*?-->#s', '', $filters);
    $code = preg_replace('#^\s*//.*$#m', '', $code);

    expect($code)->not->toContain('✓ Verified');
    expect($code)->not->toContain("params.verified = 'true'");

    $directory = file_get_contents(resource_path('js/Pages/Public/Directory.vue'));

    expect($directory)->not->toContain('f.verified');
});

test('the public listing payload carries no is_verified semantic', function () {
    $props = $this->get('/directory')->viewData('page')['props'];
    $first = $props['listings']['data'][0] ?? null;

    if ($first !== null) {
        expect($first)->not->toHaveKey('is_verified');
    }

    $resource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));

    expect($resource)->not->toContain("'is_verified'");
});
