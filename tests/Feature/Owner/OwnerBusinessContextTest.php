<?php

use App\Models\Business;
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

// ── Reviews ─────────────────────────────────────────────────────────────────
test('the review index contains only the routed business reviews', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    Review::create(['user_id' => User::factory()->create()->id, 'business_id' => $a->id, 'rating' => 5, 'content' => 'A review', 'status' => Review::STATUS_APPROVED]);
    Review::create(['user_id' => User::factory()->create()->id, 'business_id' => $b->id, 'rating' => 1, 'content' => 'B review', 'status' => Review::STATUS_APPROVED]);

    $response = $this->actingAs($owner)->get("/owner/businesses/{$a->id}/reviews");
    $response->assertOk();

    $props = $response->viewData('page')['props'];

    expect($props['business']['id'])->toBe($a->id);
    expect(collect($props['reviews']['data'])->pluck('content')->all())->toBe(['A review']);
});

test('sibling business reviews are isolated from each other', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    Review::create(['user_id' => User::factory()->create()->id, 'business_id' => $a->id, 'rating' => 5, 'content' => 'Only A', 'status' => Review::STATUS_APPROVED]);
    Review::create(['user_id' => User::factory()->create()->id, 'business_id' => $b->id, 'rating' => 4, 'content' => 'Only B', 'status' => Review::STATUS_APPROVED]);

    $propsB = $this->actingAs($owner)->get("/owner/businesses/{$b->id}/reviews")
        ->viewData('page')['props'];

    expect(collect($propsB['reviews']['data'])->pluck('content')->all())->toBe(['Only B']);
});

test('a review cannot be shown through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $reviewA = Review::create(['user_id' => User::factory()->create()->id, 'business_id' => $a->id, 'rating' => 5, 'content' => 'A', 'status' => Review::STATUS_APPROVED]);

    $this->actingAs($owner)->get("/owner/businesses/{$b->id}/reviews/{$reviewA->id}")->assertForbidden();
});

test('a review cannot be replied to through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $reviewA = Review::create(['user_id' => User::factory()->create()->id, 'business_id' => $a->id, 'rating' => 5, 'content' => 'A', 'status' => Review::STATUS_APPROVED]);

    $this->actingAs($owner)
        ->post("/owner/businesses/{$b->id}/reviews/{$reviewA->id}/reply", ['content' => 'Intrusion'])
        ->assertForbidden();

    expect(\App\Models\ReviewReply::where('review_id', $reviewA->id)->count())->toBe(0);
});

test('another owners business cannot be accessed for reviews', function () {
    $owner = ownerWithPlan();
    $other = ownerWithPlan();
    $foreign = Business::factory()->create(['owner_id' => $other->id]);

    $this->actingAs($owner)->get("/owner/businesses/{$foreign->id}/reviews")->assertForbidden();
});

// ── Leads ───────────────────────────────────────────────────────────────────
test('the lead index contains only the routed business leads', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    Lead::create(['business_id' => $a->id, 'name' => 'Lead A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);
    Lead::create(['business_id' => $b->id, 'name' => 'Lead B', 'message' => 'y', 'status' => Lead::STATUS_NEW]);

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

    Lead::create(['business_id' => $a->id, 'name' => 'Only A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);
    Lead::create(['business_id' => $b->id, 'name' => 'Only B', 'message' => 'y', 'status' => Lead::STATUS_NEW]);

    $props = $this->actingAs($owner)->get("/owner/businesses/{$b->id}/leads")
        ->viewData('page')['props'];

    expect(collect($props['leads']['data'])->pluck('name')->all())->toBe(['Only B']);
});

test('a lead cannot be shown through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $leadA = Lead::create(['business_id' => $a->id, 'name' => 'A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);

    $this->actingAs($owner)->get("/owner/businesses/{$b->id}/leads/{$leadA->id}")->assertForbidden();
});

test('a lead status cannot be mutated through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $leadA = Lead::create(['business_id' => $a->id, 'name' => 'A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);

    $this->actingAs($owner)
        ->put("/owner/businesses/{$b->id}/leads/{$leadA->id}/status", ['status' => 'archived'])
        ->assertForbidden();

    expect($leadA->fresh()->status)->toBe(Lead::STATUS_NEW);
});

test('lead notes cannot be mutated through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $leadA = Lead::create(['business_id' => $a->id, 'name' => 'A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);

    $this->actingAs($owner)
        ->put("/owner/businesses/{$b->id}/leads/{$leadA->id}/notes", ['owner_notes' => 'Intrusion'])
        ->assertForbidden();

    expect($leadA->fresh()->owner_notes)->toBeNull();
});

test('a lead cannot be deleted through a sibling business context', function () {
    $owner = ownerWithPlan();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    $leadA = Lead::create(['business_id' => $a->id, 'name' => 'A', 'message' => 'x', 'status' => Lead::STATUS_NEW]);

    $this->actingAs($owner)->delete("/owner/businesses/{$b->id}/leads/{$leadA->id}")->assertForbidden();

    expect(Lead::find($leadA->id))->not->toBeNull();
});

test('another owners business cannot be accessed for leads', function () {
    $owner = ownerWithPlan();
    $other = ownerWithPlan();
    $foreign = Business::factory()->create(['owner_id' => $other->id]);

    $this->actingAs($owner)->get("/owner/businesses/{$foreign->id}/leads")->assertForbidden();

    $foreignLead = Lead::create([
        'business_id' => $foreign->id, 'name' => 'F', 'message' => 'x', 'status' => Lead::STATUS_NEW,
    ]);

    $this->actingAs($owner)->get("/owner/businesses/{$foreign->id}/leads/{$foreignLead->id}")->assertForbidden();
});

// ── Source guard ────────────────────────────────────────────────────────────
test('the affected owner controllers no longer select a representative business', function () {
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
        expect(str_contains($code, 'Business $business'))->toBeTrue();
    }
});
