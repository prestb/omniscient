<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingService;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-3 — SERVICES ARE LISTING-OWNED.
 *
 *     Listing -> listing_services
 *
 * A service operation must be addressed to an explicit Listing, authorized by
 * ListingPolicy, and must never resolve a Listing from a Business.
 */

/**
 * An owner with an active plan, so the existing `plan.limit:services` route
 * middleware does not short-circuit before authorization is exercised.
 */
function subscribedOwner(): User
{
    $plan = Plan::factory()->create(['max_listings' => 10, 'max_services' => 10]);

    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    return $owner;
}

test('an owner can create a service for their own listing', function () {
    $owner = subscribedOwner();
    $listing = Listing::factory()->forOwner($owner)->create();

    $this->actingAs($owner)
        ->post("/owner/listings/{$listing->id}/services", [
            'name' => 'Graphic Design',
            'description' => 'Logo and brand work',
        ])
        ->assertRedirect();

    $service = ListingService::firstOrFail();

    expect($service->listing_id)->toBe($listing->id);
    expect($service->name)->toBe('Graphic Design');
});

test('services are scoped to one listing and never leak to sibling listings', function () {
    $owner = subscribedOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a = Listing::factory()->forBusiness($business)->forOwner($owner)->create(['name' => 'Acme Yaoundé']);
    $b = Listing::factory()->forBusiness($business)->forOwner($owner)->create(['name' => 'Acme Douala']);
    $c = Listing::factory()->forBusiness($business)->forOwner($owner)->create(['name' => 'Acme Buea']);

    $this->actingAs($owner)
        ->post("/owner/listings/{$b->id}/services", ['name' => 'Delivery'])
        ->assertRedirect();

    // The operation landed on B only.
    expect($b->services()->pluck('name')->all())->toBe(['Delivery']);
    expect($a->services()->count())->toBe(0);
    expect($c->services()->count())->toBe(0);

    // …and the row points at B, never at the Business or a sibling.
    expect(ListingService::firstOrFail()->listing_id)->toBe($b->id);
});

test('an owner cannot manage services for another users listing', function () {
    $ownerA = subscribedOwner();
    $ownerB = subscribedOwner();

    $listingA = Listing::factory()->forOwner($ownerA)->create();
    $listingB = Listing::factory()->forOwner($ownerB)->create();

    $this->actingAs($ownerA)->get("/owner/listings/{$listingB->id}/services")->assertForbidden();

    $this->actingAs($ownerA)
        ->post("/owner/listings/{$listingB->id}/services", ['name' => 'Intrusion'])
        ->assertForbidden();

    expect(ListingService::count())->toBe(0);
});

test('an unauthenticated user cannot reach service management', function () {
    $listing = Listing::factory()->create();

    $this->get("/owner/listings/{$listing->id}/services")->assertRedirect('/login');
    $this->post("/owner/listings/{$listing->id}/services", ['name' => 'X'])->assertRedirect('/login');
});

test('a locationless professional listing can own services', function () {
    $owner = subscribedOwner();

    $listing = Listing::factory()
        ->professional()
        ->forOwner($owner)
        ->create(['business_id' => null, 'location_id' => null]);

    $this->actingAs($owner)
        ->post("/owner/listings/{$listing->id}/services", ['name' => 'Consulting'])
        ->assertRedirect();

    expect($listing->services()->pluck('name')->all())->toBe(['Consulting']);
});

test('a business with no listings cannot manufacture a listing to hold a service', function () {
    $owner = subscribedOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    // No route accepts a Business, so no phantom Listing can be created or
    // selected on its behalf.
    $this->actingAs($owner)->post('/owner/listings/999999/services', ['name' => 'Ghost'])
        ->assertNotFound();

    expect(Listing::where('business_id', $business->id)->count())->toBe(0);
    expect(ListingService::count())->toBe(0);
});

test('the service path contains no arbitrary listing selection', function () {
    $source = File::get(app_path('Http/Controllers/Owner/ServiceController.php'));

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
            ->toBeFalse("ServiceController must not contain executable: {$forbidden}");
    }

    // The Business-scoped route is gone.
    expect(str_contains(File::get(base_path('routes/web.php')), '{business}/services'))
        ->toBeFalse('The Business-scoped services route must be removed.');

    // The controller takes a Listing, not a Business.
    expect(str_contains($code, 'Business $business'))->toBeFalse();
    expect(str_contains($code, 'Listing $listing'))->toBeTrue();
});
