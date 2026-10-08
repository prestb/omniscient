<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D — OWNER BUSINESS CONTEXT.
 *
 * Owner Reviews and Leads are BUSINESS-SPECIFIC. The Business comes from the
 * route and is authorized against the authenticated owner. Nothing selects a
 * representative Business from the owner's Businesses.
 */

/** An owner with a plan that unlocks review replies and lead capture. */
function ownerWithPlan(): User
{
    $plan = Plan::factory()->create([
        'max_listings' => 10,
        'features' => [
            'respond_to_reviews' => true,
            'lead_capture' => true,
        ],
    ]);

    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    return $owner;
}

// ── Reviews (LISTING-scoped) ────────────────────────────────────────────────
test('the review index contains only the routed listing reviews', function () {
    $owner = ownerWithPlan();

    // Two Businesses, each with one Listing. The Listing is the reviewed entity.
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);
    $listingA = Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $a->id]);
    $listingB = Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $b->id]);

    Review::factory()->for($listingA)->approved()->create(['rating' => 5, 'content' => 'A review']);
    Review::factory()->for($listingB)->approved()->create(['rating' => 1, 'content' => 'B review']);

    $response = $this->actingAs($owner)->get("/owner/listings/{$listingA->id}/reviews");
    $response->assertOk();

    $props = $response->viewData('page')['props'];

    expect($props['listing']['id'])->toBe($listingA->id);
    // Only THIS Listing's reviews, never its sibling's.
    expect(collect($props['reviews']['data'])->pluck('content')->all())->toBe(['A review']);
});

test('sibling listing reviews are isolated from each other', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);
    $listingA = Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $a->id]);
    $listingB = Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $b->id]);

    Review::factory()->for($listingA)->approved()->create(['rating' => 5, 'content' => 'Only A']);
    Review::factory()->for($listingB)->approved()->create(['rating' => 4, 'content' => 'Only B']);

    $propsB = $this->actingAs($owner)->get("/owner/listings/{$listingB->id}/reviews")
        ->viewData('page')['props'];

    expect(collect($propsB['reviews']['data'])->pluck('content')->all())->toBe(['Only B']);
});

test('sibling listings under the SAME business remain isolated', function () {
    // The central invariant: sharing an organization must not merge reputation.
    $owner = ownerWithPlan();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $listingA = Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $business->id]);
    $listingB = Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $business->id]);

    Review::factory()->for($listingA)->approved()->create(['rating' => 5, 'content' => 'Only A1']);

    $propsB = $this->actingAs($owner)->get("/owner/listings/{$listingB->id}/reviews")
        ->viewData('page')['props'];

    expect($propsB['listing']['id'])->toBe($listingB->id);
    expect($propsB['reviews']['data'])->toBe([]);
    expect((int) $propsB['reviewsCount'])->toBe(0);
});

test('a review cannot be shown through a sibling listing context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);
    $listingB = Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $b->id]);

    $reviewA = Review::factory()->for(
        Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $a->id])
    )->approved()->create(['rating' => 5, 'content' => 'A']);

    // Review A does not belong to listingB.
    $this->actingAs($owner)
        ->get("/owner/listings/{$listingB->id}/reviews/{$reviewA->id}")
        ->assertNotFound();
});

test('a review cannot be replied to through a sibling listing context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);
    $listingB = Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $b->id]);

    $reviewA = Review::factory()->for(
        Listing::factory()->create(['owner_id' => $owner->id, 'business_id' => $a->id])
    )->approved()->create(['rating' => 5, 'content' => 'A']);

    $this->actingAs($owner)
        ->post("/owner/listings/{$listingB->id}/reviews/{$reviewA->id}/reply", ['content' => 'Intrusion'])
        ->assertNotFound();

    expect(\App\Models\ReviewReply::where('review_id', $reviewA->id)->count())->toBe(0);
});

test('another owners listing cannot be accessed for reviews', function () {
    $owner = ownerWithPlan();
    $foreign = ownerWithPlan();
    $foreignListing = Listing::factory()->create(['owner_id' => $foreign->id, 'business_id' => null]);

    // authorization is listing.owner_id, NOT business ownership.
    $this->actingAs($owner)
        ->get("/owner/listings/{$foreignListing->id}/reviews")
        ->assertForbidden();
});
// ── Leads ───────────────────────────────────────────────────────────────────
test('the lead index contains only the routed business leads', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    Lead::create(['business_id' => $a->id, 'listing_id' => App\Models\Listing::factory()->forBusiness($a)->create()->id, 'name' => 'Lead A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);
    Lead::create(['business_id' => $b->id, 'listing_id' => App\Models\Listing::factory()->forBusiness($b)->create()->id, 'name' => 'Lead B', 'message' => 'y', 'status' => Lead::STATUS_NEW]);

    $props = $this->actingAs($owner)->get("/owner/businesses/{$a->id}/leads")
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['business']['id'])->toBe($a->id);
    expect(collect($props['leads']['data'])->pluck('name')->all())->toBe(['Lead A']);
    expect($props['stats']['total'])->toBe(1);
});

test('sibling business leads are isolated from each other', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    Lead::create(['business_id' => $a->id, 'listing_id' => App\Models\Listing::factory()->forBusiness($a)->create()->id, 'name' => 'Only A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);
    Lead::create(['business_id' => $b->id, 'listing_id' => App\Models\Listing::factory()->forBusiness($b)->create()->id, 'name' => 'Only B', 'message' => 'y', 'status' => Lead::STATUS_NEW]);

    $props = $this->actingAs($owner)->get("/owner/businesses/{$b->id}/leads")
        ->viewData('page')['props'];

    expect(collect($props['leads']['data'])->pluck('name')->all())->toBe(['Only B']);
});

test('a lead cannot be shown through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $leadA = Lead::create(['business_id' => $a->id, 'listing_id' => App\Models\Listing::factory()->forBusiness($a)->create()->id, 'name' => 'A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);

    $this->actingAs($owner)->get("/owner/businesses/{$b->id}/leads/{$leadA->id}")->assertForbidden();
});

test('a lead status cannot be mutated through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $leadA = Lead::create(['business_id' => $a->id, 'listing_id' => App\Models\Listing::factory()->forBusiness($a)->create()->id, 'name' => 'A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);

    $this->actingAs($owner)
        ->put("/owner/businesses/{$b->id}/leads/{$leadA->id}/status", ['status' => 'archived'])
        ->assertForbidden();

    expect($leadA->fresh()->status)->toBe(Lead::STATUS_NEW);
});

test('lead notes cannot be mutated through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $leadA = Lead::create(['business_id' => $a->id, 'listing_id' => App\Models\Listing::factory()->forBusiness($a)->create()->id, 'name' => 'A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);

    $this->actingAs($owner)
        ->put("/owner/businesses/{$b->id}/leads/{$leadA->id}/notes", ['owner_notes' => 'Intrusion'])
        ->assertForbidden();

    expect($leadA->fresh()->owner_notes)->toBeNull();
});

test('a lead cannot be deleted through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $leadA = Lead::create(['business_id' => $a->id, 'listing_id' => App\Models\Listing::factory()->forBusiness($a)->create()->id, 'name' => 'A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);

    $this->actingAs($owner)->delete("/owner/businesses/{$b->id}/leads/{$leadA->id}")->assertForbidden();

    expect(Lead::find($leadA->id))->not->toBeNull();
});

test('another owners business cannot be accessed for leads', function () {
    $owner = ownerWithPlan();
    $other = ownerWithPlan();
    $foreign = Business::factory()->create(['owner_id' => $other->id]);

    $this->actingAs($owner)->get("/owner/businesses/{$foreign->id}/leads")->assertForbidden();

    $foreignLead = Lead::create([
        'business_id' => $foreign->id,
        'listing_id' => App\Models\Listing::factory()->forBusiness($foreign)->create()->id,
        'name' => 'F', 'message' => 'x', 'status' => Lead::STATUS_NEW,
    ]);

    $this->actingAs($owner)->get("/owner/businesses/{$foreign->id}/leads/{$foreignLead->id}")->assertForbidden();
});

// ── Source guard ────────────────────────────────────────────────────────────
test('the affected owner controllers no longer select a representative business', function () {
    // Reviews are LISTING-owned after 21C-R1; leads remain Business-scoped.
    foreach ([
        app_path('Http/Controllers/Owner/ReviewController.php'),
        app_path('Http/Controllers/Owner/LeadController.php'),
    ] as $path) {
        $code = collect(preg_split('/\R/', File::get($path)))
            ->reject(function (string $line) {
                $t = ltrim($line);
                return str_starts_with($t, '*') || str_starts_with($t, '//') || str_starts_with($t, '/*');
            })
            ->implode("\n");

        expect(str_contains($code, 'businesses()->first'))
            ->toBeFalse(basename($path) . ' must not select a representative Business.');

        // No controller may resolve its route entity by selecting a Business.
        expect(str_contains($code, '$owner->businesses()'))
            ->toBeFalse(basename($path) . ' must not select from the owners Businesses.');
    }

    // PHASE 21C-R1 — the route entity differs by ownership:
    //   Review -> Listing (canonical), Lead -> Business (unchanged).
    $reviewCode = File::get(app_path('Http/Controllers/Owner/ReviewController.php'));
    expect(str_contains($reviewCode, 'Listing $listing'))->toBeTrue();
    expect(str_contains($reviewCode, 'Business $business'))->toBeFalse();

    $leadCode = File::get(app_path('Http/Controllers/Owner/LeadController.php'));
    expect(str_contains($leadCode, 'Business $business'))->toBeTrue();
});
