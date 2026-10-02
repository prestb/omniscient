<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 19B — FRONT DOOR & PRODUCT MODEL ALIGNMENT.
 *
 * Source-level assertions are used where the change is copy/markup; feature
 * tests are used where the change is behaviour.
 */

// ── Registration: the Professional path ─────────────────────────────────────

test('a professional can register without being sent to create a business', function () {
    $response = $this->post('/register', [
        'name' => 'Ada Professional',
        'email' => 'ada@example.com',
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
        'terms' => true,
        'account_type' => 'professional',
    ]);

    $response->assertRedirect(route('owner.listings.create'));

    $user = User::where('email', 'ada@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe(User::ROLE_OWNER);
});

test('professional registration does not create a business', function () {
    $this->post('/register', [
        'name' => 'Ada Professional',
        'email' => 'ada2@example.com',
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
        'terms' => true,
        'account_type' => 'professional',
    ]);

    $user = User::where('email', 'ada2@example.com')->first();

    expect(Business::where('owner_id', $user->id)->count())->toBe(0);
});

test('an organization registration keeps the existing business flow', function () {
    $response = $this->post('/register', [
        'name' => 'Org Owner',
        'email' => 'org@example.com',
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
        'terms' => true,
        'account_type' => 'owner',
    ]);

    $response->assertRedirect(route('owner.businesses.create'));
});

test('a browsing registration still reaches the user dashboard', function () {
    $response = $this->post('/register', [
        'name' => 'Browser',
        'email' => 'browser@example.com',
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
        'terms' => true,
        'account_type' => 'user',
    ]);

    $response->assertRedirect(route('user.dashboard'));
});

test('an unknown account type is rejected', function () {
    $this->post('/register', [
        'name' => 'Nope',
        'email' => 'nope@example.com',
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
        'terms' => true,
        'account_type' => 'invented',
    ])->assertSessionHasErrors('account_type');
});

test('the registration screen offers a professional option that needs no business', function () {
    $source = file_get_contents(resource_path('js/Pages/Auth/Register.vue'));

    expect($source)->toContain("accountType = 'professional'");
    expect($source)->toContain('No Business required.');
    // The old Business-only framing is gone.
    expect($source)->not->toContain('Own a business');
});

// ── Front door copy ─────────────────────────────────────────────────────────

test('the landing hero is Listing-first and carries no unsupported claim', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/Home.vue'));

    // Listing-neutral, discovery-oriented positioning.
    expect($source)->toContain('Find who can do it');
    expect($source)->toContain('Search professionals, businesses, stores and places');

    // Removed unsupported / paid-as-trust claims.
    foreach ([
        'Trusted by thousands',
        'trusted by our',
        'Verified badges',
        'Discover Local',
    ] as $claim) {
        expect($source)->not->toContain($claim);
    }
});

test('the hero explains discover and connect, not just search', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/Home.vue'));

    // Subtitle and CTAs are no longer commented out.
    expect($source)->toContain('then contact or save it');
    expect($source)->toContain('Browse listings');
    expect($source)->toContain('Create a Listing');
});

test('no public surface sells verification', function () {
    foreach ([
        'Pages/Public/Home.vue',
        'Pages/Public/About.vue',
        'Pages/Public/BusinessProfile.vue',
    ] as $rel) {
        $source = file_get_contents(resource_path('js/' . $rel));
        expect($source)->not->toContain('Verified badges on premium plans');
    }
});

test('the public business profile no longer renders a paid verified badge', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/BusinessProfile.vue'));

    // The badge was entitlement-driven, i.e. paid, which is not verification.
    expect($source)->not->toContain('business.feature_flags?.verified_badge');
});

test('the about page is discovery-oriented rather than business-only', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/About.vue'));

    expect($source)->not->toContain('the best local businesses');
    expect($source)->toContain('find what they need, and who can do it');
});

test('the owner shell speaks of publishing a listing, not a business', function () {
    $source = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.vue'));

    expect($source)->toContain('publish a listing');
    expect($source)->not->toContain('publish a business');
});

// ── Business-less Listing remains fully supported ───────────────────────────

test('a business-less listing is creatable and reaches its public page', function () {
    $owner = User::factory()->owner()->create();

    $listing = App\Models\Listing::factory()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
        'status' => App\Models\Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    expect($listing->business_id)->toBeNull();
    expect($listing->location_id)->toBeNull();

    $this->get('/listing/' . $listing->slug)->assertOk();
});
