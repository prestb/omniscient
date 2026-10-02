<?php

use App\Mail\NotificationMail;
use App\Models\Business;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * PHASE 18B — CONNECTION & MEASUREMENT CONTINUITY.
 */

function b18Listing(array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

function b18Lead(Listing $listing, array $attrs = []): Lead
{
    return Lead::create(array_merge([
        'listing_id' => $listing->id,
        'business_id' => $listing->business_id,
        'name' => 'Visitor',
        'email' => 'visitor@example.com',
        'message' => 'Please tell me more.',
        'status' => Lead::STATUS_NEW,
    ], $attrs));
}

// ── Objective 1: contact analytics ──────────────────────────────────────────

test('a listing contact click is recorded against that exact listing', function (string $clickType) {
    $listing = b18Listing();

    $this->post("/analytics/listing/{$listing->id}/track-click/{$clickType}")->assertSuccessful();

    $row = DB::table('listing_analytics')->where('listing_id', $listing->id)->first();
    expect($row)->not->toBeNull();
    expect($row->{$clickType . '_clicks'})->toBeGreaterThan(0);
})->with(['phone', 'whatsapp', 'website', 'social']);

test('a contact click does not leak into a sibling listing', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->owner()->create();

    $a = b18Listing(['business_id' => $business->id, 'owner_id' => $owner->id]);
    $b = b18Listing(['business_id' => $business->id, 'owner_id' => $owner->id]);

    $this->post("/analytics/listing/{$a->id}/track-click/phone")->assertSuccessful();

    $rowB = DB::table('listing_analytics')->where('listing_id', $b->id)->first();
    expect($rowB)->toBeNull();
});

test('the click vocabulary is unchanged and closed', function () {
    $listing = b18Listing();

    // A type outside the established vocabulary is rejected, not silently stored.
    $this->post("/analytics/listing/{$listing->id}/track-click/invented")
        ->assertStatus(400);
});

test('the dead location.phone analytics path is retired', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/ListingProfile.vue'));

    // The unreachable element that held the only analytics call is gone.
    expect($source)->not->toContain('v-if="listing.location.phone"');

    // And the real, Listing-owned contact records are now measured.
    expect($source)->toContain('trackContact(contact)');
    expect($source)->toContain('const trackContact');

    // Contact types map onto the EXISTING vocabulary only.
    $urls = file_get_contents(resource_path('js/urls.js'));
    expect($urls)->toContain('export function clickTypeFor');
    foreach (['phone', 'whatsapp', 'website', 'social'] as $t) {
        expect($urls)->toContain("return '{$t}';");
    }
});

// ── Objective 2/3: truthful reply ───────────────────────────────────────────

test('an owner reply sends a real email and stamps replied_at', function () {
    Mail::fake();

    $owner = User::factory()->owner()->create();
    $listing = b18Listing(['owner_id' => $owner->id]);
    $lead = b18Lead($listing, ['replied_at' => null]);

    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}/leads/{$lead->id}/reply", [
            'message' => 'Thanks for reaching out — yes, we cover that area.',
        ])
        ->assertRedirect();

    Mail::assertSent(NotificationMail::class, fn ($mail) => $mail->hasTo('visitor@example.com'));

    $lead->refresh();
    expect($lead->replied_at)->not->toBeNull();
    expect($lead->status)->toBe(Lead::STATUS_REPLIED);
});

test('a failed reply does not falsely mark the lead as replied', function () {
    $owner = User::factory()->owner()->create();
    $listing = b18Listing(['owner_id' => $owner->id]);
    $lead = b18Lead($listing, ['replied_at' => null]);

    Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('transport down'));

    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}/leads/{$lead->id}/reply", ['message' => 'Hello'])
        ->assertRedirect();

    $lead->refresh();
    expect($lead->replied_at)->toBeNull();
    expect($lead->status)->not->toBe(Lead::STATUS_REPLIED);
});

test('changing status alone never fabricates a reply timestamp', function () {
    $owner = User::factory()->owner()->create();
    $listing = b18Listing(['owner_id' => $owner->id]);
    $lead = b18Lead($listing, ['replied_at' => null]);

    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}/leads/{$lead->id}/status", ['status' => 'replied'])
        ->assertRedirect();

    // Truthful data: no message was sent, so there is no reply timestamp.
    expect($lead->fresh()->replied_at)->toBeNull();
});

test('a lead with no reply address cannot be marked replied', function () {
    Mail::fake();

    $owner = User::factory()->owner()->create();
    $listing = b18Listing(['owner_id' => $owner->id]);
    $lead = b18Lead($listing, ['email' => '', 'replied_at' => null]);

    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}/leads/{$lead->id}/reply", ['message' => 'Hello'])
        ->assertRedirect();

    Mail::assertNothingSent();
    expect($lead->fresh()->replied_at)->toBeNull();
});

// ── Authorization ───────────────────────────────────────────────────────────

test('an unrelated owner cannot reply', function () {
    Mail::fake();

    $owner = User::factory()->owner()->create();
    $stranger = User::factory()->owner()->create();
    $listing = b18Listing(['owner_id' => $owner->id]);
    $lead = b18Lead($listing);

    $this->actingAs($stranger)
        ->put("/owner/listings/{$listing->id}/leads/{$lead->id}/reply", ['message' => 'Hi'])
        ->assertForbidden();

    expect($lead->fresh()->replied_at)->toBeNull();
});

test('a lead cannot be replied to through a sibling listing of the same business', function () {
    Mail::fake();

    $business = Business::factory()->create();
    $owner = User::factory()->owner()->create();

    $a = b18Listing(['business_id' => $business->id, 'owner_id' => $owner->id]);
    $b = b18Listing(['business_id' => $business->id, 'owner_id' => $owner->id]);

    $leadA = b18Lead($a);

    // Same owner, same Business - but the lead does not belong to Listing B.
    $this->actingAs($owner)
        ->put("/owner/listings/{$b->id}/leads/{$leadA->id}/reply", ['message' => 'Hi'])
        ->assertForbidden();

    Mail::assertNothingSent();
    expect($leadA->fresh()->replied_at)->toBeNull();
});

// ── Business-less + multi-listing isolation ─────────────────────────────────

test('a business-less professional can be replied to', function () {
    Mail::fake();

    $owner = User::factory()->owner()->create();
    $listing = b18Listing(['owner_id' => $owner->id, 'business_id' => null]);
    $lead = b18Lead($listing, ['business_id' => null, 'replied_at' => null]);

    expect($listing->business_id)->toBeNull();

    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}/leads/{$lead->id}/reply", ['message' => 'Happy to help.'])
        ->assertRedirect();

    Mail::assertSent(NotificationMail::class, fn ($mail) => $mail->hasTo('visitor@example.com'));
    expect($lead->fresh()->replied_at)->not->toBeNull();
});

test('replies across sibling listings stay attributed to their own lead', function () {
    Mail::fake();

    $business = Business::factory()->create();
    $owner = User::factory()->owner()->create();

    $a = b18Listing(['business_id' => $business->id, 'owner_id' => $owner->id]);
    $b = b18Listing(['business_id' => $business->id, 'owner_id' => $owner->id]);

    $leadA = b18Lead($a, ['email' => 'a@example.com', 'replied_at' => null]);
    $leadB = b18Lead($b, ['email' => 'b@example.com', 'replied_at' => null]);

    $this->actingAs($owner)->put("/owner/listings/{$a->id}/leads/{$leadA->id}/reply", ['message' => 'For A']);
    $this->actingAs($owner)->put("/owner/listings/{$b->id}/leads/{$leadB->id}/reply", ['message' => 'For B']);

    Mail::assertSent(NotificationMail::class, fn ($m) => $m->hasTo('a@example.com'));
    Mail::assertSent(NotificationMail::class, fn ($m) => $m->hasTo('b@example.com'));

    expect($leadA->fresh()->replied_at)->not->toBeNull();
    expect($leadB->fresh()->replied_at)->not->toBeNull();
    expect($leadA->fresh()->listing_id)->toBe($a->id);
    expect($leadB->fresh()->listing_id)->toBe($b->id);
});

// ── Objective 4: Business completeness regression ───────────────────────────

test('the owner dashboard loads for an owner with a business', function () {
    $owner = User::factory()->owner()->create();
    Business::factory()->create(['owner_id' => $owner->id]);

    // Phase 18A: BusinessCompletenessService eager-loaded `logo`/`coverImage`
    // as relations on a model where they are columns, throwing
    // RelationNotFoundException. This is the regression guard.
    $this->actingAs($owner)->get('/owner/dashboard')->assertOk();
});

test('the business completeness service operates on the current business model', function () {
    $business = Business::factory()->create();

    $result = (new \App\Services\BusinessCompletenessService())->calculate($business);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('score');
    expect($result['items'] ?? $result['results'])->not->toBeEmpty();
});

test('the completeness service no longer treats columns as relations', function () {
    $source = file_get_contents(app_path('Services/BusinessCompletenessService.php'));

    // logo / cover_image are Business COLUMNS.
    // The eager load must not treat the columns as relationships.
    expect($source)->not->toContain("'logo', 'coverImage'");
    expect($source)->toContain("loadMissing(['locations.hours', 'services', 'images', 'galleryImages'])");
    expect($source)->toContain("filled(\$business->logo)");
    expect($source)->toContain("filled(\$business->cover_image)");
});

// ── Contract safety ─────────────────────────────────────────────────────────

test('the reply route and its vue-facing contract are consistent', function () {
    // The route exists under the Listing-scoped lead group, never Business-scoped.
    expect(file_get_contents(base_path('routes/web.php')))
        ->toContain("'listings/{listing}/leads'");

    $route = app('router')->getRoutes()->getByName('owner.listings.leads.reply');
    expect($route)->not->toBeNull();
    expect($route->uri())->toBe('owner/listings/{listing}/leads/{lead}/reply');
});
