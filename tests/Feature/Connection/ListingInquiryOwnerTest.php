<?php

use App\Models\Business;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * PHASE 12B — OWNER ACCESS + LISTING-LEVEL INQUIRY ANALYTICS.
 *
 * Access derives from Inquiry -> Listing -> Listing owner. Business is optional
 * context and is never part of authorization.
 */

/** An inquiry attributed to a Listing, written the way the public route writes it. */
function inquiryFor(Listing $listing, array $overrides = []): Lead
{
    return Lead::create(array_merge([
        'listing_id' => $listing->id,
        'business_id' => $listing->business_id,
        'source' => 'listing_contact_form',
        'name' => 'Sender',
        'email' => 's@example.com',
        'message' => 'Hello',
        'status' => Lead::STATUS_NEW,
    ], $overrides));
}

function publishedListing(array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

// ── Owner access ────────────────────────────────────────────────────────────

test('a business-less listing owner can access their inquiry', function () {
    $owner = User::factory()->owner()->create();
    $listing = publishedListing(['owner_id' => $owner->id, 'business_id' => null]);
    $lead = inquiryFor($listing);

    $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/leads")
        ->assertOk()
        ->assertSee($listing->name);

    $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/leads/{$lead->id}")
        ->assertOk();
});

test('a business-owned listing owner can access its inquiry', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = publishedListing(['owner_id' => $owner->id, 'business_id' => $business->id]);
    $lead = inquiryFor($listing);

    $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/leads/{$lead->id}")
        ->assertOk();
});

test('an inquiry identifies its originating listing', function () {
    $owner = User::factory()->owner()->create();
    $listing = publishedListing(['owner_id' => $owner->id, 'business_id' => null]);
    inquiryFor($listing);

    $props = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/leads")
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['listing']['name'])->toBe($listing->name);
    expect($props['leads']['data'][0]['listing']['name'])->toBe($listing->name);
});

test('multiple listings under one business preserve exact attribution', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a = publishedListing(['owner_id' => $owner->id, 'business_id' => $business->id]);
    $b = publishedListing(['owner_id' => $owner->id, 'business_id' => $business->id]);
    $c = publishedListing(['owner_id' => $owner->id, 'business_id' => $business->id]);

    inquiryFor($a, ['name' => 'For A']);
    inquiryFor($c, ['name' => 'For C']);

    $propsA = $this->actingAs($owner)->get("/owner/listings/{$a->id}/leads")->viewData('page')['props'];
    $propsB = $this->actingAs($owner)->get("/owner/listings/{$b->id}/leads")->viewData('page')['props'];

    expect(collect($propsA['leads']['data'])->pluck('name')->all())->toBe(['For A']);
    expect($propsB['leads']['data'])->toBe([]);
});

test('an owner cannot access another owners inquiry', function () {
    $owner = User::factory()->owner()->create();
    $other = User::factory()->owner()->create();

    $listing = publishedListing(['owner_id' => $other->id, 'business_id' => null]);
    $lead = inquiryFor($listing);

    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/leads")->assertForbidden();
    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/leads/{$lead->id}")->assertForbidden();
    $this->actingAs($owner)->put("/owner/listings/{$listing->id}/leads/{$lead->id}/status", ['status' => 'read'])->assertForbidden();
    $this->actingAs($owner)->delete("/owner/listings/{$listing->id}/leads/{$lead->id}")->assertForbidden();

    expect($lead->fresh()->status)->toBe(Lead::STATUS_NEW);
});

test('an inquiry cannot be handled through a sibling listing', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a = publishedListing(['owner_id' => $owner->id, 'business_id' => $business->id]);
    $b = publishedListing(['owner_id' => $owner->id, 'business_id' => $business->id]);

    $leadA = inquiryFor($a);

    // Same owner, same Business — still not reachable through Listing B.
    $this->actingAs($owner)
        ->get("/owner/listings/{$b->id}/leads/{$leadA->id}")
        ->assertForbidden();
});

test('the owner can act on their own listing inquiry', function () {
    $owner = User::factory()->owner()->create();
    $listing = publishedListing(['owner_id' => $owner->id, 'business_id' => null]);
    $lead = inquiryFor($listing);

    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}/leads/{$lead->id}/status", ['status' => 'replied'])
        ->assertRedirect();

    expect($lead->fresh()->status)->toBe(Lead::STATUS_REPLIED);
});

test('existing business-scoped lead behaviour still works', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = publishedListing(['owner_id' => $owner->id, 'business_id' => $business->id]);

    inquiryFor($listing);

    expect(Route::has('owner.businesses.leads.index'))->toBeTrue();
    expect(Route::has('owner.listings.leads.index'))->toBeTrue();
});

// ── Listing-level inquiry analytics ─────────────────────────────────────────

test('inquiry counts are attributed per listing, not per business', function () {
    $business = Business::factory()->create();

    $a = publishedListing(['business_id' => $business->id]);
    $b = publishedListing(['business_id' => $business->id]);
    $c = publishedListing(['business_id' => $business->id]);

    foreach (range(1, 5) as $i) {
        inquiryFor($a);
    }
    foreach (range(1, 2) as $i) {
        inquiryFor($b);
    }

    $counts = Listing::withCount('leads')
        ->whereIn('id', [$a->id, $b->id, $c->id])
        ->pluck('leads_count', 'id');

    expect((int) $counts[$a->id])->toBe(5);
    expect((int) $counts[$b->id])->toBe(2);
    expect((int) $counts[$c->id])->toBe(0);
});

test('a business-less listing reports its own inquiry count', function () {
    $listing = publishedListing(['business_id' => null]);

    inquiryFor($listing);
    inquiryFor($listing);

    expect((int) Listing::withCount('leads')->find($listing->id)->leads_count)->toBe(2);
});

test('business-level aggregation is not substituted for listing attribution', function () {
    $business = Business::factory()->create();

    $a = publishedListing(['business_id' => $business->id]);
    $b = publishedListing(['business_id' => $business->id]);

    inquiryFor($a);
    inquiryFor($a);
    inquiryFor($a);

    // The Business has 3 inquiries in total, but Listing B must still report 0.
    expect(Lead::where('business_id', $business->id)->count())->toBe(3);
    expect((int) Listing::withCount('leads')->find($a->id)->leads_count)->toBe(3);
    expect((int) Listing::withCount('leads')->find($b->id)->leads_count)->toBe(0);
});

test('the owner listings index exposes per-listing inquiry counts', function () {
    $owner = User::factory()->owner()->create();
    $listing = publishedListing(['owner_id' => $owner->id, 'business_id' => null]);

    inquiryFor($listing);

    $props = $this->actingAs($owner)
        ->get('/owner/listings')
        ->assertOk()
        ->viewData('page')['props'];

    $row = collect($props['listings']['data'])->firstWhere('id', $listing->id);

    expect((int) $row['leads_count'])->toBe(1);
});

test('inquiry counts never read business_id', function () {
    $source = file_get_contents(app_path('Models/Listing.php'));

    expect($source)->toContain('public function leads()');
    expect($source)->toContain('hasMany(Lead::class)');
    // The relation must resolve on listing_id, never through business context.
    expect($source)->not->toContain('hasManyThrough(Lead');
});
