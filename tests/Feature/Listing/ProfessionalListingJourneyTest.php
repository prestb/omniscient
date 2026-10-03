<?php

use App\Mail\NotificationMail;
use App\Models\Business;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\ListingContact;
use App\Models\ListingService;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * PHASE 21B-C — PROFESSIONAL LISTING CREATION & PUBLISHING.
 *
 * Proves the creator journey end to end without a Business:
 *
 *   Register -> Create -> Complete -> Publish -> Discover -> Inquire -> Reply
 */

/** A Professional on a plan that grants lead capture (Growth shape). */
function creator(): User
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

// ── 1. The entry point is Listing-first ─────────────────────────────────────

test('the listing creation screen is reachable for a professional with no business', function () {
    $owner = creator();

    expect(Business::where('owner_id', $owner->id)->count())->toBe(0);

    $props = $this->actingAs($owner)->get('/owner/listings/create')->assertOk()->viewData('page')['props'];

    expect($props['businesses'])->toBe([]);
    expect($props['types'])->not->toBeEmpty();
});

test('the creation screen tells the user a listing is the discoverable entity', function () {
    $source = file_get_contents(resource_path('js/Pages/Owner/Listings/Create.vue'));

    expect($source)->toContain('A Listing is the discoverable entity');
    expect($source)->toContain('optional');
    // No Business-first requirement is presented.
    expect($source)->not->toContain('Create your business first');
    expect($source)->not->toContain('Business profile required');
});

// ── 2. Creation without a Business ──────────────────────────────────────────

test('a professional can create a listing with no business', function () {
    $owner = creator();

    $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'professional',
        'name' => 'Ada the Plumber',
        'description' => 'Emergency plumbing across the city.',
        'publish' => false,
    ])->assertRedirect();

    $listing = Listing::firstOrFail();

    expect($listing->owner_id)->toBe($owner->id);
    expect($listing->business_id)->toBeNull();
    expect($listing->location_id)->toBeNull();
    expect($listing->type)->toBe('professional');
    expect(Business::where('owner_id', $owner->id)->count())->toBe(0);
});

test('listing type is required and constrained to the canonical values', function () {
    $owner = creator();

    $this->actingAs($owner)->post('/owner/listings', [
        'name' => 'No type', 'description' => 'x',
    ])->assertSessionHasErrors('type');

    $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'invented', 'name' => 'Bad type', 'description' => 'x',
    ])->assertSessionHasErrors('type');
});

test('a name is required and bounded', function () {
    $owner = creator();

    $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'professional', 'description' => 'x',
    ])->assertSessionHasErrors('name');

    $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'professional', 'name' => str_repeat('a', 101), 'description' => 'x',
    ])->assertSessionHasErrors('name');
});

test('a professional cannot attach a listing to someone elses business', function () {
    $owner = creator();
    $stranger = creator();
    $foreign = Business::factory()->create(['owner_id' => $stranger->id]);

    $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'professional',
        'name' => 'Hijack attempt',
        'description' => 'x',
        'business_id' => $foreign->id,
    ])->assertSessionHasErrors('business_id');

    expect(Listing::count())->toBe(0);
});

// ── 3. Listing-owned content ────────────────────────────────────────────────

test('services, contacts and categories belong to the listing', function () {
    $owner = creator();

    $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'professional', 'name' => 'Ada the Plumber', 'description' => 'Plumbing.',
    ])->assertRedirect();

    $listing = Listing::firstOrFail();

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/services", [
        'name' => 'Leak repair',
    ])->assertRedirect();

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/contacts", [
        'type' => 'phone', 'value' => '+237600000123',
    ])->assertRedirect();

    expect(ListingService::where('listing_id', $listing->id)->count())->toBe(1);
    expect(ListingContact::where('listing_id', $listing->id)->count())->toBe(1);
});

// ── 4. Publish ↔ public ─────────────────────────────────────────────────────

test('publishing makes the listing publicly reachable and unpublishing withdraws it', function () {
    $owner = creator();

    $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'professional', 'name' => 'Ada the Plumber', 'description' => 'Plumbing.',
    ]);

    $listing = Listing::firstOrFail();

    // Draft: not publicly reachable.
    $this->get('/listing/' . $listing->slug)->assertNotFound();

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/publish")->assertRedirect();
    expect($listing->fresh()->status)->toBe(Listing::STATUS_PUBLISHED);
    $this->get('/listing/' . $listing->slug)->assertOk();

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/unpublish")->assertRedirect();
    $this->get('/listing/' . $listing->slug)->assertNotFound();
});

test('publish is owner-scoped and enforced server side', function () {
    $owner = creator();
    $stranger = creator();

    $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'professional', 'name' => 'Mine', 'description' => 'x',
    ]);
    $listing = Listing::firstOrFail();

    $this->actingAs($stranger)->post("/owner/listings/{$listing->id}/publish")->assertForbidden();
    expect($listing->fresh()->status)->not->toBe(Listing::STATUS_PUBLISHED);
});

// ── 5. THE COMPLETE ACCEPTANCE JOURNEY ──────────────────────────────────────

test('a professional completes the whole journey without a business', function () {
    Mail::fake();

    // 1-3. Register-equivalent: owner role, zero Businesses.
    $owner = creator();
    expect($owner->role)->toBe(User::ROLE_OWNER);
    expect(Business::where('owner_id', $owner->id)->count())->toBe(0);

    // 4-5. Create a professional Listing.
    $this->actingAs($owner)->post('/owner/listings', [
        'type' => 'professional',
        'name' => 'Ada the Plumber',
        'description' => 'Emergency plumbing across the city.',
    ])->assertRedirect();

    $listing = Listing::firstOrFail();
    expect($listing->business_id)->toBeNull();

    // 6-9. Listing-owned completeness content.
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/services", ['name' => 'Leak repair'])->assertRedirect();
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/contacts", [
        'type' => 'phone', 'value' => '+237600000123',
    ])->assertRedirect();

    // 11-12. Publish.
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/publish")->assertRedirect();

    // 13-15. Canonical public Listing carries its own content.
    $props = $this->get('/listing/' . $listing->slug)->assertOk()->viewData('page')['props'];

    expect($props['listing']['business_id'])->toBeNull();
    expect($props['listing']['listing_type'])->toBe('professional');
    expect($props['listing']['status'])->toBe(Listing::STATUS_PUBLISHED);
    expect(collect($props['listing']['services'])->pluck('name')->all())->toBe(['Leak repair']);
    expect(collect($props['listing']['contacts'])->pluck('value')->all())->toBe(['+237600000123']);

    // 16. A visitor sends an inquiry (the account's plan grants lead capture).
    $this->post('/listing/' . $listing->slug . '/contact', [
        'name' => 'Visitor', 'email' => 'visitor@example.com', 'message' => 'Do you cover Bastos?',
    ])->assertOk()->assertJson(['success' => true]);

    $lead = Lead::firstOrFail();
    expect($lead->listing_id)->toBe($listing->id);
    expect($lead->business_id)->toBeNull();

    // 17-19. Owner inbox and a REAL reply that stamps replied_at truthfully.
    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/leads")->assertOk();

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}/leads/{$lead->id}/reply", [
        'message' => 'Yes, we cover Bastos.',
    ])->assertRedirect();

    // 20. The visitor is notified.
    Mail::assertSent(NotificationMail::class, fn ($m) => $m->hasTo('visitor@example.com'));
    expect($lead->fresh()->replied_at)->not->toBeNull();
});

// ── 6. Business-backed uses the SAME lifecycle ──────────────────────────────

test('a business-backed listing follows the same lifecycle independently', function () {
    Mail::fake();

    $owner = creator();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    foreach (['Listing A', 'Listing B'] as $name) {
        $this->actingAs($owner)->post('/owner/listings', [
            'type' => 'store',
            'name' => $name,
            'description' => 'Sibling listing.',
            'business_id' => $business->id,
        ])->assertRedirect();
    }

    $a = Listing::where('name', 'Listing A')->firstOrFail();
    $b = Listing::where('name', 'Listing B')->firstOrFail();

    expect($a->business_id)->toBe($business->id);
    expect($b->business_id)->toBe($business->id);

    // Each keeps its own identity and content.
    $this->actingAs($owner)->post("/owner/listings/{$a->id}/services", ['name' => 'Service A'])->assertRedirect();
    $this->actingAs($owner)->post("/owner/listings/{$b->id}/services", ['name' => 'Service B'])->assertRedirect();

    expect(ListingService::where('listing_id', $a->id)->value('name'))->toBe('Service A');
    expect(ListingService::where('listing_id', $b->id)->value('name'))->toBe('Service B');

    // Each is published and reachable at its OWN canonical URL.
    $this->actingAs($owner)->post("/owner/listings/{$a->id}/publish")->assertRedirect();
    $this->actingAs($owner)->post("/owner/listings/{$b->id}/publish")->assertRedirect();

    $this->get('/listing/' . $a->slug)->assertOk();
    $this->get('/listing/' . $b->slug)->assertOk();

    // Inquiries attribute to the exact Listing.
    $this->post('/listing/' . $a->slug . '/contact', [
        'name' => 'V', 'email' => 'v@example.com', 'message' => 'About A',
    ])->assertOk();

    expect(Lead::firstOrFail()->listing_id)->toBe($a->id);
    expect(Lead::where('listing_id', $b->id)->count())->toBe(0);
});

// ── 7. Semantic corrections from this phase ─────────────────────────────────

test('the business surfaces no longer conflate a business with a listing', function () {
    foreach ([
        'Pages/Owner/Businesses/Create.vue',
        'Pages/Owner/Businesses/Edit.vue',
        'Pages/Owner/Businesses/Index.vue',
    ] as $rel) {
        $source = file_get_contents(resource_path('js/' . $rel));
        $code = preg_replace('#<!--.*?-->#s', '', $source);

        expect($code)->not->toContain('business listing');
        expect($code)->not->toContain('business listings');
    }
});

test('the owner business page no longer sells verification or claims a public badge', function () {
    foreach ([
        'Pages/Owner/Businesses/Edit.vue',
        'Pages/Owner/Businesses/Create.vue',
    ] as $rel) {
        $source = file_get_contents(resource_path('js/' . $rel));
        $code = preg_replace('#<!--.*?-->#s', '', $source);

        // Phase 19B removed the public badge; the owner UI must not claim it.
        expect($code)->not->toContain('Get verified');
        expect($code)->not->toContain('Your business is verified');
        expect($code)->not->toContain('is displayed on your public profile');
        expect($code)->not->toContain('Increase conversions by up to 40%');
        expect($code)->not->toContain('Build trust with customers');
    }
});
