<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingContact;
use App\Models\ListingService;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 21B-E — LISTING MANAGEMENT & EDIT EXPERIENCE.
 *
 * Proves the edit page carries the same Listing-scoped completeness the index
 * shows, so the one screen where an owner can ACT on it actually tells them what
 * to do next.
 */

function editOwner(int $maxListings = 10): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => $maxListings,
        'max_locations' => 2,
        'features' => ['lead_capture' => true],
    ]);

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
    ]);

    return $owner;
}

function bareListing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
        'status' => Listing::STATUS_DRAFT,
        'hidden_at' => null,
        'description' => null,
    ], $attrs));
}

// ── The edit page exposes actionable completeness ───────────────────────────

test('the edit page supplies listing completeness', function () {
    $owner = editOwner();
    $listing = bareListing($owner);

    $props = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->assertOk()
        ->viewData('page')['props'];

    expect($props)->toHaveKey('completeness');
    expect($props['completeness'])->toHaveKey('score');
    expect($props['completeness'])->toHaveKey('missing');
});

test('the missing items carry actionable labels and hints', function () {
    $owner = editOwner();
    $listing = bareListing($owner);

    $props = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->viewData('page')['props'];

    $missing = collect($props['completeness']['missing']);

    expect($missing)->not->toBeEmpty();

    // Each item tells the owner WHAT is missing and WHY it matters, so the UI can
    // answer "what should I do next?" without inventing its own rules.
    $first = $missing->first();
    expect($first)->toHaveKey('key');
    expect($first)->toHaveKey('label');
    expect($first['label'])->not->toBe('');
});

test('the edit page shows the SAME score the index shows', function () {
    $owner = editOwner();
    $listing = bareListing($owner);

    $editScore = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->viewData('page')['props']['completeness']['score'];

    $indexScore = $this->actingAs($owner)
        ->get('/owner/listings')
        ->viewData('page')['props']['listings']['data'][0]['completeness']['score'];

    // One source of truth: ListingCompletenessService. No scoring in the UI.
    expect($editScore)->toBe($indexScore);
});

test('completeness improves as listing-owned content is added', function () {
    $owner = editOwner();
    $listing = bareListing($owner);

    $before = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->viewData('page')['props']['completeness']['score'];

    // Add Listing-owned content.
    $listing->update(['description' => 'A proper description of what is offered.']);
    ListingService::create(['listing_id' => $listing->id, 'name' => 'Leak repair', 'sort_order' => 0]);
    ListingContact::create(['listing_id' => $listing->id, 'type' => 'phone', 'value' => '+237600000123']);

    $after = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->viewData('page')['props']['completeness']['score'];

    expect($after)->toBeGreaterThan($before);
});

test('a blank contact does not improve completeness', function () {
    $owner = editOwner();
    $listing = bareListing($owner, ['description' => 'Described.']);

    $before = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->viewData('page')['props']['completeness']['score'];

    ListingContact::create(['listing_id' => $listing->id, 'type' => 'phone', 'value' => '   ']);

    $after = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->viewData('page')['props']['completeness']['score'];

    expect($after)->toBe($before);
});

test('a hidden service does not improve completeness', function () {
    $owner = editOwner();
    $listing = bareListing($owner, ['description' => 'Described.']);

    $before = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->viewData('page')['props']['completeness']['score'];

    ListingService::create([
        'listing_id' => $listing->id, 'name' => 'Hidden', 'sort_order' => 0, 'hidden_at' => now(),
    ]);

    $after = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->viewData('page')['props']['completeness']['score'];

    expect($after)->toBe($before);
});

// ── The frontend consumes it (backend ↔ frontend together) ──────────────────

test('the edit page renders the missing items it receives', function () {
    $source = file_get_contents(resource_path('js/Pages/Owner/Listings/Edit.vue'));

    // The prop exists AND the template renders it, so the backend addition is
    // actually consumed rather than left dangling.
    expect($source)->toContain('completeness:');
    expect($source)->toContain('completeness.score');
    expect($source)->toContain('completeness.missing');
    expect($source)->toContain('item.label');
    expect($source)->toContain('item.hint');

    // And it uses the existing design-system primitive rather than a new one.
    expect($source)->toContain("ui/Badge.vue");
});

test('no completeness scoring logic was duplicated in the frontend', function () {
    $source = file_get_contents(resource_path('js/Pages/Owner/Listings/Edit.vue'));

    // The UI displays the score; it does not compute one.
    expect($source)->not->toContain('hasDescription');
    expect($source)->not->toContain('score +=');
    expect($source)->not->toContain('calculateCompleteness');
});

// ── The public route for "view listing" is canonical ────────────────────────

test('the edit page links to the canonical public listing url', function () {
    $owner = editOwner();
    $listing = bareListing($owner, ['business_id' => null]);

    $this->get('/listing/' . $listing->slug)->assertNotFound(); // draft

    $source = file_get_contents(resource_path('js/Pages/Owner/Listings/Edit.vue'));
    expect($source)->toContain('/listing/${listing.slug}');

    // A Listing is never linked through the organization route.
    expect($source)->not->toContain('/business/${');
});

// ── Editing a business-backed listing gets the same treatment ───────────────

test('a business-backed listing edit page also receives completeness', function () {
    $owner = editOwner();
    $business = \App\Models\Business::factory()->create(['owner_id' => $owner->id]);
    $listing = bareListing($owner, ['business_id' => $business->id]);

    $props = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/edit")
        ->assertOk()
        ->viewData('page')['props'];

    // The two journeys do not diverge merely because a Business exists.
    expect($props)->toHaveKey('completeness');
    expect($props['listing']['business_id'])->toBe($business->id);
});

// ── Authorization is unchanged ──────────────────────────────────────────────

test('a stranger cannot open another owners listing edit page', function () {
    $owner = editOwner();
    $stranger = editOwner();
    $listing = bareListing($owner);

    $this->actingAs($stranger)->get("/owner/listings/{$listing->id}/edit")->assertForbidden();
});
