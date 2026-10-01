<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-2 — the Listing lifecycle and canonical public identity.
 *
 * A Listing is first-class, independently owned and independently addressable.
 * A Business is an optional organization; a Location is an optional place.
 */

/**
 * The organization page enforces the existing paid-visibility rule: the
 * organization's owner must have an active subscription.
 */
function businessWithActiveOwner(array $attributes = []): Business
{
    $plan = Plan::factory()->create(['max_listings' => 10]);
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    return Business::factory()->published()->create(
        array_merge(['owner_id' => $owner->id], $attributes)
    );
}

test('an authenticated user can create each listing type', function (string $type) {
    $user = User::factory()->owner()->create();

    $this->actingAs($user)
        ->post('/owner/listings', [
            'type' => $type,
            'name' => "A {$type} listing",
            'description' => 'Created by the lifecycle test.',
            'business_id' => null,
            'location_id' => null,
            'publish' => true,
        ])
        ->assertRedirect();

    $listing = Listing::where('owner_id', $user->id)->firstOrFail();

    expect($listing->type)->toBe($type);
    expect($listing->owner_id)->toBe($user->id);
    expect($listing->status)->toBe(Listing::STATUS_PUBLISHED);
    expect($listing->slug)->not->toBeEmpty();
})->with(['professional', 'business', 'store']);

test('a professional listing can be created without a business or a location', function () {
    $user = User::factory()->owner()->create();

    $this->actingAs($user)->post('/owner/listings', [
        'type' => 'professional',
        'name' => 'John Doe',
        'business_id' => null,
        'location_id' => null,
    ])->assertRedirect();

    $listing = Listing::where('owner_id', $user->id)->firstOrFail();

    expect($listing->business_id)->toBeNull();
    expect($listing->location_id)->toBeNull();
    expect($listing->status)->toBe(Listing::STATUS_DRAFT);
});

test('a store listing can be created without a business', function () {
    $user = User::factory()->owner()->create();

    $this->actingAs($user)->post('/owner/listings', [
        'type' => 'store',
        'name' => "Jane's Fashion Store",
        'business_id' => null,
    ])->assertRedirect();

    $listing = Listing::where('owner_id', $user->id)->firstOrFail();

    expect($listing->type)->toBe('store');
    expect($listing->business_id)->toBeNull();
});

test('a listing can be associated with a business', function () {
    $user = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user)->post('/owner/listings', [
        'type' => 'business',
        'name' => 'Acme Yaoundé',
        'business_id' => $business->id,
        'publish' => true,
    ])->assertRedirect();

    expect(Listing::where('business_id', $business->id)->count())->toBe(1);
});

test('a user cannot associate a listing with another users business', function () {
    $user = User::factory()->owner()->create();
    $other = User::factory()->owner()->create();
    $foreign = Business::factory()->create(['owner_id' => $other->id]);

    $this->actingAs($user)->post('/owner/listings', [
        'type' => 'business',
        'name' => 'Not mine',
        'business_id' => $foreign->id,
    ])->assertSessionHasErrors('business_id');

    expect(Listing::count())->toBe(0);
});

test('a user cannot edit or update another users listing', function () {
    $owner = User::factory()->owner()->create();
    $intruder = User::factory()->owner()->create();

    $listing = Listing::factory()->forOwner($owner)->create();

    $this->actingAs($intruder)->get("/owner/listings/{$listing->id}/edit")->assertForbidden();

    $this->actingAs($intruder)->put("/owner/listings/{$listing->id}", [
        'type' => $listing->type,
        'name' => 'Hijacked',
    ])->assertForbidden();

    expect($listing->fresh()->name)->not->toBe('Hijacked');
});

test('an unauthenticated user cannot access the listing lifecycle', function () {
    $listing = Listing::factory()->create();

    $this->get('/owner/listings')->assertRedirect('/login');
    $this->get("/owner/listings/{$listing->id}/edit")->assertRedirect('/login');
});

test('the owner can publish and unpublish their own listing', function () {
    $owner = User::factory()->owner()->create();
    $listing = Listing::factory()->forOwner($owner)->create(['status' => Listing::STATUS_DRAFT]);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/publish")->assertRedirect();
    expect($listing->fresh()->status)->toBe(Listing::STATUS_PUBLISHED);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/unpublish")->assertRedirect();
    expect($listing->fresh()->status)->toBe(Listing::STATUS_DRAFT);
});

test('the canonical public listing url resolves a listing by its own slug', function () {
    $listing = Listing::factory()->published()->create(['name' => 'Canonical Listing']);

    $this->get('/listing/' . $listing->slug)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/ListingProfile')
            ->where('listing.id', $listing->id)
            ->where('listing.slug', $listing->slug)
            ->where('listing.type', 'listing'));
});

test('a locationless professional listing renders publicly', function () {
    $listing = Listing::factory()->professional()->published()->create(['location_id' => null]);

    $this->get('/listing/' . $listing->slug)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/ListingProfile')
            ->where('listing.id', $listing->id));
});

test('a non-public listing is not exposed on the canonical url', function () {
    $draft = Listing::factory()->create(['status' => Listing::STATUS_DRAFT]);
    $this->get('/listing/' . $draft->slug)->assertNotFound();

    $hidden = Listing::factory()->published()->create();
    $hidden->update(['hidden_at' => now()]);
    $this->get('/listing/' . $hidden->slug)->assertNotFound();
});

test('the business organization page lists all of its listings', function () {
    $business = businessWithActiveOwner();

    $a = Listing::factory()->forBusiness($business)->published()->create(['name' => 'Acme Yaoundé']);
    $b = Listing::factory()->forBusiness($business)->published()->create(['name' => 'Acme Douala']);
    $c = Listing::factory()->forBusiness($business)->published()->create(['name' => 'Acme Buea']);

    $this->get('/business/' . $business->slug)
        ->assertOk()
        ->assertInertia(function ($page) use ($business, $a, $b, $c) {
            $page->component('Public/BusinessProfile')->where('business.id', $business->id);

            $props = $page->toArray()['props']['listings'];

            $ids = collect($props)->pluck('id')->sort()->values()->all();
            expect($ids)->toBe(collect([$a->id, $b->id, $c->id])->sort()->values()->all());

            // Each Listing keeps its own slug identity — the value the
            // organization page links to as /listing/{slug}.
            $slugs = collect($props)->pluck('slug')->sort()->values()->all();
            expect($slugs)->toBe(collect([$a->slug, $b->slug, $c->slug])->sort()->values()->all());

            // Every entry is a LISTING, never a Business.
            expect(collect($props)->pluck('type')->unique()->values()->all())->toBe(['listing']);
        });

    // Each Listing keeps its own independent public identity.
    foreach ([$a, $b, $c] as $listing) {
        $this->get('/listing/' . $listing->slug)->assertOk();
    }
});

test('business and listing are distinct public identities', function () {
    $business = businessWithActiveOwner(['slug' => 'acme-ltd']);
    $listing = Listing::factory()->forBusiness($business)->published()->create(['slug' => 'acme-yaounde']);

    expect($business->slug)->not->toBe($listing->slug);

    $this->get('/business/acme-ltd')->assertOk()->assertInertia(
        fn ($page) => $page->component('Public/BusinessProfile')
    );
    $this->get('/listing/acme-yaounde')->assertOk()->assertInertia(
        fn ($page) => $page->component('Public/ListingProfile')
    );
});

test('a created listing is indexed in the real listings Meilisearch index', function () {
    $marker = 'Zebra Unicorn ' . uniqid();

    $listing = Listing::factory()->published()->create(['name' => $marker]);

    $found = false;

    // Meilisearch indexing is task-based; allow a brief settle window.
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $ids = Listing::search($marker)->take(5)->get()->pluck('id');

        if ($ids->contains($listing->id)) {
            $found = true;
            break;
        }

        usleep(300000);
    }

    expect($found)->toBeTrue('The created Listing should be present in the listings index.');
});
