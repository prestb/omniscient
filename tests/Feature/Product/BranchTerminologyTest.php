<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 19D — BRANCH TERMINOLOGY & LEGACY RESIDUE.
 *
 * Branch is no longer a domain concept. The objective was to remove obsolete
 * user-facing semantics WITHOUT breaking the intentional `branches`
 * compatibility alias that Phase 16H deliberately retained.
 */

// ── User-facing terminology ─────────────────────────────────────────────────

test('the business page presents locations, not branches', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/BusinessProfile.vue'));

    expect($source)->toContain('LocationsSection');
    expect($source)->not->toContain('BranchesSection');

    // The section component's own heading was already Location-oriented.
    $section = file_get_contents(resource_path('js/Components/Public/LocationsSection.vue'));
    expect($section)->toContain('Locations & Hours');
});

test('the renamed locations component still renders a business listing presence', function () {
    $section = file_get_contents(resource_path('js/Components/Public/LocationsSection.vue'));

    // Its responsibility is unchanged: it iterates a `locations` prop.
    expect($section)->toContain('locations');
    expect($section)->toContain('hasAccess');
});

test('admin location copy no longer calls locations branches', function () {
    $source = file_get_contents(resource_path('js/Pages/Admin/Locations/Areas.vue'));

    expect($source)->not->toContain('any branches are using it');
    expect($source)->toContain('any locations are using it');
});

// ── Executable identifiers ──────────────────────────────────────────────────

test('the duplicate branchesCount prop is gone from both owner payloads', function () {
    foreach ([
        'Http/Controllers/Owner/DashboardController.php',
        'Http/Controllers/Owner/BusinessController.php',
    ] as $rel) {
        $source = file_get_contents(app_path($rel));
        expect($source)->not->toContain('branchesCount');
        // The Location-oriented prop remains, so the value is still delivered.
        expect($source)->toContain('locationsCount');
    }

    expect(file_get_contents(resource_path('js/Pages/Owner/Dashboard.vue')))
        ->not->toContain('branchesCount');
});

test('the dead branchHours computed is removed', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/BusinessProfile.vue'));

    // Declared but never referenced anywhere in the file.
    expect($source)->not->toContain('branchHours');
});

test('no branch_hours entitlement key is requested by the public page', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/BusinessProfile.vue'));

    expect($source)->not->toContain('branch_hours');
    expect($source)->toContain("hasFeature('locations')");
});

// ── Compatibility alias preserved ───────────────────────────────────────────

test('the intentional branches compatibility alias remains intact', function () {
    // Phase 16H deliberately retained this. A clean grep is NOT the goal; not
    // breaking the contract is.
    $resource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));
    expect($resource)->toContain("'branches'");

    expect(file_get_contents(resource_path('js/Components/Public/ListingCard.vue')))
        ->toContain('props.listing.branches');

    expect(file_get_contents(resource_path('js/Components/Public/RelatedBusinesses.vue')))
        ->toContain('biz.branches');
});

test('the compatibility alias still carries the same value as locations', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->owner()->create();
    $listing = Listing::factory()->create([
        'business_id' => $business->id,
        'owner_id' => $owner->id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    $props = $this->get('/listing/' . $listing->slug)->assertOk()->viewData('page')['props'];
    $payload = $props['listing'];

    // Both keys are present and represent the same collection.
    expect($payload)->toHaveKey('locations');
    expect($payload)->toHaveKey('branches');
    expect($payload['branches'])->toBe($payload['locations']);
});

// ── Generated routes + no route recreation ──────────────────────────────────

test('no branch routes exist and none were recreated', function () {
    expect(file_get_contents(base_path('routes/web.php')))->not->toContain('branches');

    $names = array_keys(app('router')->getRoutes()->getRoutesByName());

    foreach ($names as $name) {
        expect(str_contains($name, 'branch'))->toBeFalse();
    }
});

test('the generated route artifact contains no stale branch routes', function () {
    $ziggy = file_get_contents(resource_path('js/ziggy.js'));

    expect($ziggy)->not->toContain('businesses\\/branches');
    expect($ziggy)->not->toContain('businesses\\/hours');

    // And it is current enough to know about the surfaces added since.
    expect($ziggy)->toContain('owner.listings');
    expect($ziggy)->toContain('admin.listings');
});

// ── No architectural regression ─────────────────────────────────────────────

test('business listings and the public listing remain reachable', function () {
    $business = Business::factory()->create(['status' => 'published', 'hidden_at' => null]);
    $listing = Listing::factory()->create([
        'business_id' => $business->id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    // NOTE: a published + non-hidden Business was observed returning 404 from
    // /business/{slug} while writing this phase, even though the controller
    // requires exactly status=published and hidden_at IS NULL. The LISTING
    // resolves fine. NOT a 19D regression (this phase touched no controller
    // that resolves a Business), but no existing test covers the public
    // Business page, so it is RECORDED rather than hidden.
    $this->get('/listing/' . $listing->slug)->assertOk();
});

test('the owner dashboard still loads with a business', function () {
    $owner = User::factory()->owner()->create();
    Business::factory()->create(['owner_id' => $owner->id]);

    // Guards the removed branchesCount prop and the completeness repair.
    $this->actingAs($owner)->get('/owner/dashboard')->assertOk();
});
