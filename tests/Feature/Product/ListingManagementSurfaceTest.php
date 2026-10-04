<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingContact;
use App\Models\ListingService;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 21B-G — LISTING MANAGEMENT SURFACE DEPTH & SUBSCRIPTION CONTEXT.
 *
 * Proves the management surfaces are Listing-scoped (not Business-scoped), that a
 * Business-less Professional can operate them, and that the subscription page
 * explains why the owner was sent there after hitting the Listing quota.
 */

function g21Owner(int $maxListings = 10): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => $maxListings,
        'max_locations' => 3,
        'max_images' => 5,
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

function g21Listing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

// ═══ MANAGEMENT SURFACES ARE LISTING-SCOPED, NOT BUSINESS-SCOPED ════════════

test('services and contacts are managed through the listing route', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())->map(fn ($r) => $r->uri())->all();

    // Listing-scoped routes exist (the canonical ownership path).
    expect($uris)->toContain('owner/listings/{listing}/services');
    expect($uris)->toContain('owner/listings/{listing}/contacts');
    expect($uris)->toContain('owner/listings/{listing}/images');
});

test('the service and contact controllers take a listing, not a business', function () {
    foreach (['ServiceController', 'ContactController'] as $name) {
        $source = file_get_contents(app_path("Http/Controllers/Owner/{$name}.php"));

        expect($source)->toContain('Listing $listing');
        // A Business is never the route entity for Listing-owned content.
        expect($source)->not->toContain('Business $business');
    }
});

// ═══ A BUSINESS-LESS PROFESSIONAL CAN OPERATE EVERY SURFACE ════════════════

test('a business-less professional manages services, contacts, media and publication', function () {
    $owner = g21Owner();
    $listing = g21Listing($owner, ['business_id' => null, 'status' => Listing::STATUS_DRAFT]);

    expect($listing->business_id)->toBeNull();

    // Services
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/services", [
        'name' => 'Leak repair',
    ])->assertRedirect();
    expect(ListingService::where('listing_id', $listing->id)->count())->toBe(1);

    // Contacts
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/contacts", [
        'type' => 'phone', 'value' => '+237600000123',
    ])->assertRedirect();
    expect(ListingContact::where('listing_id', $listing->id)->count())->toBe(1);

    // Management index surfaces render.
    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/services")->assertOk();
    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/contacts")->assertOk();
    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/images")->assertOk();

    // Publication
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/publish")->assertRedirect();
    expect($listing->fresh()->status)->toBe(Listing::STATUS_PUBLISHED);

    // No Business was required at any point.
    expect(Business::where('owner_id', $owner->id)->count())->toBe(0);
});

test('a business-backed listing has identical management capability', function () {
    $owner = g21Owner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = g21Listing($owner, ['business_id' => $business->id]);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/services", ['name' => 'Service'])
        ->assertRedirect();
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/contacts", [
        'type' => 'whatsapp', 'value' => '+237600000999',
    ])->assertRedirect();

    expect(ListingService::where('listing_id', $listing->id)->count())->toBe(1);
    expect(ListingContact::where('listing_id', $listing->id)->count())->toBe(1);

    // The Business did not become the owner of the Listing's content.
    expect(ListingService::first()->listing_id)->toBe($listing->id);
});

// ═══ SIBLING ISOLATION ══════════════════════════════════════════════════════

test('managing one listing cannot touch its sibling', function () {
    $owner = g21Owner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = g21Listing($owner, ['business_id' => $business->id, 'name' => 'A1']);
    $a2 = g21Listing($owner, ['business_id' => $business->id, 'name' => 'A2']);

    $this->actingAs($owner)->post("/owner/listings/{$a1->id}/services", ['name' => 'Only A1'])
        ->assertRedirect();
    $this->actingAs($owner)->post("/owner/listings/{$a1->id}/contacts", [
        'type' => 'phone', 'value' => '+237600000A1',
    ])->assertRedirect();

    // The sibling is untouched on every surface.
    expect(ListingService::where('listing_id', $a2->id)->count())->toBe(0);
    expect(ListingContact::where('listing_id', $a2->id)->count())->toBe(0);

    // Publication is per Listing.
    $this->actingAs($owner)->post("/owner/listings/{$a1->id}/unpublish")->assertRedirect();
    expect($a1->fresh()->status)->toBe(Listing::STATUS_DRAFT);
    expect($a2->fresh()->status)->toBe(Listing::STATUS_PUBLISHED);
});

// ═══ AUTHORIZATION ══════════════════════════════════════════════════════════

test('a stranger cannot mutate another owners listing content', function () {
    $owner = g21Owner();
    $stranger = g21Owner();
    $listing = g21Listing($owner);

    $this->actingAs($stranger)->post("/owner/listings/{$listing->id}/services", ['name' => 'Hijack'])
        ->assertForbidden();
    $this->actingAs($stranger)->post("/owner/listings/{$listing->id}/contacts", [
        'type' => 'phone', 'value' => '+237600000000',
    ])->assertForbidden();
    $this->actingAs($stranger)->post("/owner/listings/{$listing->id}/publish")->assertForbidden();

    expect(ListingService::count())->toBe(0);
    expect(ListingContact::count())->toBe(0);
});

// ═══ SUBSCRIPTION CONTEXT AFTER QUOTA EXHAUSTION ════════════════════════════

test('reaching the listing quota redirects to the subscription page with a reason', function () {
    $owner = g21Owner(maxListings: 1);
    g21Listing($owner);

    $response = $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'professional', 'name' => 'Over the limit', 'description' => 'x',
    ]);

    // The middleware redirects and carries the reason.
    $response->assertRedirect(route('owner.subscription.index'));
    $response->assertSessionHas('error');

    // No partial record was created.
    expect(Listing::where('owner_id', $owner->id)->count())->toBe(1);
});

test('the subscription page shows the listing limit and renders the reason', function () {
    $source = file_get_contents(resource_path('js/Pages/Owner/Subscription/Index.vue'));

    // The limit that most often brings an owner here must be visible.
    expect($source)->toContain('subscription.plan?.max_listings');
    expect($source)->toContain('Listings');

    // And the server-supplied reason must actually be rendered: CheckPlanLimit
    // redirects with `error`, which was previously discarded.
    expect($source)->toContain('page.props.flash?.error');
    expect($source)->toContain('role="status"');

    // Server text is shown verbatim; nothing is recomputed in Vue.
    expect($source)->not->toContain('You have reached your');
});

test('the subscription page does not invent its own limit arithmetic', function () {
    $source = file_get_contents(resource_path('js/Pages/Owner/Subscription/Index.vue'));

    expect($source)->not->toContain('canAdd');
    expect($source)->not->toContain('countFor');
    // No hard-coded plan names.
    expect($source)->not->toContain("=== 'premium'");
    expect($source)->not->toContain("=== 'growth'");
});

test('the subscription page still loads for an owner at quota', function () {
    $owner = g21Owner(maxListings: 1);
    g21Listing($owner);

    $this->actingAs($owner)->get('/owner/subscription')->assertOk();
});
