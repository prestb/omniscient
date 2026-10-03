<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 21B-R2 — LEAD-CAPTURE ENTITLEMENT CONTRACT.
 *
 * CONTRACT PROVEN from database/seeders/PlanSeeder.php, the authoritative plan
 * source:
 *
 *     Free     max_listings 1   lead_capture false
 *     Starter  max_listings 1   lead_capture false
 *     Growth   max_listings 3   lead_capture true
 *     Premium  max_listings -1  lead_capture true
 *
 *   Is lead capture subscription-gated?   YES
 *   Who owns the entitlement?             USER ACCOUNT (the Listing's owner)
 *   Do business-less Listings obey it?    YES — there is no bypass
 *
 * Entitlement resolves as:
 *
 *     Listing.owner -> User subscription -> Plan -> lead_capture
 *
 * for business-backed AND business-less Listings alike. A Business is never a
 * subscription owner and never influences the result.
 */

/** An owner on a plan that GRANTS lead_capture (Growth/Premium shape). */
function entitledListingOwner(): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'max_listings' => 3,
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

/** An owner on a plan that does NOT grant lead_capture (Free/Starter shape). */
function unentitledListingOwner(): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'max_listings' => 1,
        'features' => ['lead_capture' => false],
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

function r2Listing(User $owner, ?Business $business = null): Listing
{
    return Listing::factory()->create([
        'owner_id' => $owner->id,
        'business_id' => $business?->id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);
}

function r2Inquire(Listing $listing, array $overrides = [])
{
    return test()->post('/listing/' . $listing->slug . '/contact', array_merge([
        'name' => 'Visitor',
        'email' => 'visitor@example.com',
        'message' => 'Do you cover this area?',
    ], $overrides));
}

// ── 1 & 2. Business-less ────────────────────────────────────────────────────

test('a business-less listing with an entitled owner accepts an inquiry', function () {
    $listing = r2Listing(entitledListingOwner(), business: null);

    r2Inquire($listing)->assertOk()->assertJson(['success' => true]);

    expect(\App\Models\Lead::first()->listing_id)->toBe($listing->id);
});

test('a business-less listing with a NON-entitled owner is refused', function () {
    // THE OLD BUG, now impossible: business-less Listings used to bypass the
    // entitlement check entirely because the `&&` short-circuited.
    $listing = r2Listing(unentitledListingOwner(), business: null);

    r2Inquire($listing)->assertStatus(403);
    expect(\App\Models\Lead::count())->toBe(0);
});

// ── 3 & 4. Business-backed ──────────────────────────────────────────────────

test('a business-backed listing with an entitled listing owner accepts an inquiry', function () {
    $owner = entitledListingOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = r2Listing($owner, $business);

    r2Inquire($listing)->assertOk()->assertJson(['success' => true]);
});

test('a business-backed listing with a NON-entitled listing owner is refused', function () {
    $owner = unentitledListingOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = r2Listing($owner, $business);

    r2Inquire($listing)->assertStatus(403);
});

// ── 5. Business owner differs from Listing owner ────────────────────────────

test('entitlement follows the LISTING owner when the business owner differs', function () {
    // The schema permits these to differ. An entitled Listing owner grouped
    // under someone else's (unentitled) Business must still be able to receive
    // inquiries: the Business must not become a hidden subscription owner.
    $listingOwner = entitledListingOwner();
    $businessOwner = unentitledListingOwner();
    $business = Business::factory()->create(['owner_id' => $businessOwner->id]);

    $listing = r2Listing($listingOwner, $business);

    expect($listing->owner_id)->not->toBe($business->owner_id);

    r2Inquire($listing)->assertOk();
});

test('an unentitled listing owner cannot borrow the business owners entitlement', function () {
    // The inverse: an unentitled Listing owner must NOT be granted access just
    // because the organization that groups the Listing is on a better plan.
    $listingOwner = unentitledListingOwner();
    $businessOwner = entitledListingOwner();
    $business = Business::factory()->create(['owner_id' => $businessOwner->id]);

    $listing = r2Listing($listingOwner, $business);

    r2Inquire($listing)->assertStatus(403);
});

// ── 6 & 7. No bypass remains ────────────────────────────────────────────────

test('business grouping cannot bypass entitlement', function () {
    $owner = unentitledListingOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $solo = r2Listing($owner, business: null);
    $grouped = r2Listing($owner, $business);

    // Identical treatment — the previous asymmetry is gone.
    r2Inquire($solo)->assertStatus(403);
    r2Inquire($grouped)->assertStatus(403);
});

test('the entitlement is no longer resolved through the business', function () {
    $source = file_get_contents(app_path('Http/Controllers/Public/ListingLeadController.php'));

    expect($source)->not->toContain('$listing->business &&');
    expect($source)->toContain('$listing->owner->canUse');
    expect($source)->toContain('Entitlement::LEAD_CAPTURE');
});

// ── 8 & 9. Multi-listing account isolation ──────────────────────────────────

test('every listing owned by the same account uses that accounts entitlement', function () {
    $owner = entitledListingOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = r2Listing($owner, $business);
    $a2 = r2Listing($owner, business: null);

    r2Inquire($a1)->assertOk();
    r2Inquire($a2)->assertOk();
});

test('different owners entitlements stay isolated', function () {
    $entitled = entitledListingOwner();
    $unentitled = unentitledListingOwner();

    r2Inquire(r2Listing($entitled))->assertOk();
    r2Inquire(r2Listing($unentitled))->assertStatus(403);
});

// ── Plan configuration is feature-driven, not name-driven ───────────────────

test('feature lookup reads plan configuration rather than a plan name', function () {
    // Two plans with the SAME name but different features must behave
    // differently, which is only possible if lookup is feature-driven.
    $granting = Plan::factory()->create(['name' => 'Identical', 'features' => ['lead_capture' => true]]);
    $denying = Plan::factory()->create(['name' => 'Identical', 'features' => ['lead_capture' => false]]);

    expect($granting->hasFeature('lead_capture'))->toBeTrue();
    expect($denying->hasFeature('lead_capture'))->toBeFalse();

    $source = file_get_contents(app_path('Http/Controllers/Public/ListingLeadController.php'));
    // No plan-name conditionals were introduced.
    expect($source)->not->toContain("=== 'premium'");
    expect($source)->not->toContain("=== 'growth'");
});

test('the authoritative plans define lead_capture exactly where intended', function () {
    // Encodes the contract found in database/seeders/PlanSeeder.php so a future
    // plan change cannot silently alter who may receive inquiries.
    $seeder = file_get_contents(database_path('seeders/PlanSeeder.php'));

    expect(substr_count($seeder, "'lead_capture' => true"))->toBe(2);
    expect(substr_count($seeder, "'lead_capture' => false"))->toBe(2);

    foreach (['Free', 'Starter', 'Growth', 'Premium'] as $plan) {
        expect($seeder)->toContain("'name' => '{$plan}'");
    }
});

// ── Attribution contract unchanged (Phase 12/18) ────────────────────────────

test('lead attribution remains listing-first with optional business context', function () {
    $owner = entitledListingOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = r2Listing($owner, $business);

    r2Inquire($listing)->assertOk();

    $lead = \App\Models\Lead::first();
    expect($lead->listing_id)->toBe($listing->id);
    expect($lead->business_id)->toBe($business->id);
});

test('a business-less inquiry stores a null business context', function () {
    $listing = r2Listing(entitledListingOwner(), business: null);

    r2Inquire($listing)->assertOk();

    $lead = \App\Models\Lead::first();
    expect($lead->listing_id)->toBe($listing->id);
    expect($lead->business_id)->toBeNull();
});
