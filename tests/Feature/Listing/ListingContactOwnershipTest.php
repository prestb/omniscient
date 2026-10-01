<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-3 — CONTACTS ARE LISTING-OWNED.
 *
 *     Listing -> listing_contacts
 *
 * A contact operation must be addressed to an explicit Listing, authorized by
 * ListingPolicy, and must never resolve a Listing from a Business.
 */

function contactPayload(string $value = '+237 600 000 000'): array
{
    return ['type' => 'phone', 'value' => $value, 'is_primary' => false];
}

test('an owner can create a contact for their own listing', function () {
    $owner = User::factory()->owner()->create();
    $listing = Listing::factory()->forOwner($owner)->create();

    $this->actingAs($owner)
        ->post("/owner/listings/{$listing->id}/contacts", contactPayload())
        ->assertRedirect();

    $contact = ListingContact::firstOrFail();

    expect($contact->listing_id)->toBe($listing->id);
    expect($contact->value)->toBe('+237 600 000 000');
});

test('contacts are scoped to one listing and never leak to sibling listings', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a = Listing::factory()->forBusiness($business)->forOwner($owner)->create(['name' => 'Acme Yaoundé']);
    $b = Listing::factory()->forBusiness($business)->forOwner($owner)->create(['name' => 'Acme Douala']);
    $c = Listing::factory()->forBusiness($business)->forOwner($owner)->create(['name' => 'Acme Buea']);

    $this->actingAs($owner)
        ->post("/owner/listings/{$b->id}/contacts", contactPayload('+237 699 111 222'))
        ->assertRedirect();

    expect($b->contacts()->pluck('value')->all())->toBe(['+237 699 111 222']);
    expect($a->contacts()->count())->toBe(0);
    expect($c->contacts()->count())->toBe(0);

    expect(ListingContact::firstOrFail()->listing_id)->toBe($b->id);
});

test('an owner cannot manage contacts for another users listing', function () {
    $ownerA = User::factory()->owner()->create();
    $ownerB = User::factory()->owner()->create();

    $listingA = Listing::factory()->forOwner($ownerA)->create();
    $listingB = Listing::factory()->forOwner($ownerB)->create();

    $this->actingAs($ownerA)->get("/owner/listings/{$listingB->id}/contacts")->assertForbidden();

    $this->actingAs($ownerA)
        ->post("/owner/listings/{$listingB->id}/contacts", contactPayload('+237 000'))
        ->assertForbidden();

    expect(ListingContact::count())->toBe(0);
});

test('a contact id cannot be manipulated through a sibling listing url', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $listingA = Listing::factory()->forBusiness($business)->forOwner($owner)->create();
    $listingB = Listing::factory()->forBusiness($business)->forOwner($owner)->create();

    $contactX = ListingContact::create([
        'listing_id' => $listingA->id,
        'type' => 'phone',
        'value' => '+237 111',
        'sort_order' => 1,
    ]);

    // Same owner, same Business — but the contact belongs to Listing A, so the
    // Listing B URL must not reach it.
    $this->actingAs($owner)
        ->put("/owner/listings/{$listingB->id}/contacts/{$contactX->id}", contactPayload('HIJACKED'))
        ->assertNotFound();

    $this->actingAs($owner)
        ->delete("/owner/listings/{$listingB->id}/contacts/{$contactX->id}")
        ->assertNotFound();

    expect($contactX->fresh()->value)->toBe('+237 111');
    expect(ListingContact::count())->toBe(1);
});

test('an unauthenticated user cannot reach contact management', function () {
    $listing = Listing::factory()->create();

    $this->get("/owner/listings/{$listing->id}/contacts")->assertRedirect('/login');
    $this->post("/owner/listings/{$listing->id}/contacts", contactPayload())->assertRedirect('/login');
});

test('a locationless professional listing can own contacts', function () {
    $owner = User::factory()->owner()->create();

    $listing = Listing::factory()
        ->professional()
        ->forOwner($owner)
        ->create(['business_id' => null, 'location_id' => null]);

    $this->actingAs($owner)
        ->post("/owner/listings/{$listing->id}/contacts", contactPayload('+237 677 000 000'))
        ->assertRedirect();

    expect($listing->contacts()->pluck('value')->all())->toBe(['+237 677 000 000']);
});

test('a business with no listings cannot manufacture a listing to hold a contact', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($owner)->post('/owner/listings/999999/contacts', contactPayload())
        ->assertNotFound();

    expect(Listing::where('business_id', $business->id)->count())->toBe(0);
    expect(ListingContact::count())->toBe(0);
});

test('the contact path contains no arbitrary listing selection', function () {
    $source = File::get(app_path('Http/Controllers/Owner/ContactController.php'));

    // Ignore comment lines: the controller documents what it must NOT do.
    $code = collect(preg_split('/\R/', $source))
        ->reject(function (string $line) {
            $trimmed = ltrim($line);
            return str_starts_with($trimmed, '*')
                || str_starts_with($trimmed, '//')
                || str_starts_with($trimmed, '/*');
        })
        ->implode("\n");

    foreach ([
        'primaryListing',
        'listings()->first',
        'listings->first',
        'listings()->latest',
        'listings()->oldest',
        'listings()->value(',
    ] as $forbidden) {
        expect(str_contains($code, $forbidden))
            ->toBeFalse("ContactController must not contain executable: {$forbidden}");
    }

    // The Business-scoped route is gone.
    expect(str_contains(File::get(base_path('routes/web.php')), '{business}/contacts'))
        ->toBeFalse('The Business-scoped contacts route must be removed.');

    // The controller takes a Listing, not a Business, and no longer authorizes
    // through Business ownership.
    expect(str_contains($code, 'Business $business'))->toBeFalse();
    expect(str_contains($code, 'Listing $listing'))->toBeTrue();
    expect(str_contains($code, 'business->owner_id'))->toBeFalse();
});
