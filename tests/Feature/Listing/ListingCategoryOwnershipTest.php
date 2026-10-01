<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-3 — CATEGORIES ARE LISTING-OWNED.
 *
 *     Listing -> listing_categories -> Category
 *
 * Category assignment is an explicit-Listing mutation authorized by
 * ListingPolicy. It is never resolved through a Business, and owning the
 * Business that contains a Listing grants no access to a sibling Listing.
 */

test('category assignment applies to the explicit listing only', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a = Listing::factory()->forBusiness($business)->forOwner($owner)->create();
    $b = Listing::factory()->forBusiness($business)->forOwner($owner)->create();

    $c1 = Category::factory()->create();
    $c2 = Category::factory()->create();

    $this->actingAs($owner)->put("/owner/listings/{$a->id}", [
        'type' => $a->type,
        'name' => $a->name,
        'categories' => [$c1->id, $c2->id],
    ])->assertRedirect();

    // Listing A changed; sibling B untouched.
    expect($a->fresh()->categories()->pluck('categories.id')->sort()->values()->all())
        ->toBe(collect([$c1->id, $c2->id])->sort()->values()->all());
    expect($b->fresh()->categories()->count())->toBe(0);
});

test('category sync replaces only the named listings categories', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a = Listing::factory()->forBusiness($business)->forOwner($owner)->create();
    $b = Listing::factory()->forBusiness($business)->forOwner($owner)->create();

    $cA = Category::factory()->create();
    $cB = Category::factory()->create();

    $a->categories()->sync([$cA->id]);
    $b->categories()->sync([$cB->id]);

    // Re-assign A. B's relationship must survive untouched.
    $this->actingAs($owner)->put("/owner/listings/{$a->id}", [
        'type' => $a->type,
        'name' => $a->name,
        'categories' => [$cB->id],
    ])->assertRedirect();

    expect($a->fresh()->categories()->pluck('categories.id')->all())->toBe([$cB->id]);
    expect($b->fresh()->categories()->pluck('categories.id')->all())->toBe([$cB->id]);

    // A lost its old category; B never gained/lost anything.
    expect($a->fresh()->categories()->whereKey($cA->id)->exists())->toBeFalse();
});

test('a user cannot assign categories to another users listing', function () {
    $ownerA = User::factory()->owner()->create();
    $ownerB = User::factory()->owner()->create();

    $listingA = Listing::factory()->forOwner($ownerA)->create();
    $listingB = Listing::factory()->forOwner($ownerB)->create();

    $category = Category::factory()->create();

    $this->actingAs($ownerA)->put("/owner/listings/{$listingB->id}", [
        'type' => $listingB->type,
        'name' => 'Hijacked',
        'categories' => [$category->id],
    ])->assertForbidden();

    expect($listingB->fresh()->name)->not->toBe('Hijacked');
    expect($listingB->fresh()->categories()->count())->toBe(0);
});

test('an owner cannot reach a sibling listings categories through the business', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a = Listing::factory()->forBusiness($business)->forOwner($owner)->create();
    $b = Listing::factory()->forBusiness($business)->forOwner($owner)->create();

    $cA = Category::factory()->create();
    $a->categories()->sync([$cA->id]);

    // The Business update route no longer accepts categories at all.
    $this->actingAs($owner)->put("/owner/businesses/{$business->id}", [
        'name' => $business->name,
        'categories' => [],
    ])->assertRedirect();

    // Listing A keeps its category — the Business route cannot strip it.
    expect($a->fresh()->categories()->pluck('categories.id')->all())->toBe([$cA->id]);
    expect($b->fresh()->categories()->count())->toBe(0);
});

test('the business update route still performs its business-owned updates', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($owner)->put("/owner/businesses/{$business->id}", [
        'name' => 'Renamed Organization',
        'description' => 'Updated description',
        'email' => 'org@example.test',
        'website' => 'https://org.example.test',
    ])->assertRedirect();

    $fresh = $business->fresh();
    expect($fresh->name)->toBe('Renamed Organization');
    expect($fresh->description)->toBe('Updated description');
    expect($fresh->email)->toBe('org@example.test');
    expect($fresh->website)->toBe('https://org.example.test');
});

test('the category path contains no arbitrary listing resolution', function () {
    $strip = function (string $path): string {
        return collect(preg_split('/\R/', File::get($path)))
            ->reject(function (string $line) {
                $t = ltrim($line);
                return str_starts_with($t, '*') || str_starts_with($t, '//') || str_starts_with($t, '/*');
            })
            ->implode("\n");
    };

    $businessController = $strip(app_path('Http/Controllers/Owner/BusinessController.php'));
    $listingController = $strip(app_path('Http/Controllers/Owner/ListingController.php'));

    foreach ([$businessController, $listingController] as $code) {
        expect(str_contains($code, 'primaryListing'))->toBeFalse();
    }

    // BusinessController must not write listing_categories at all.
    expect(str_contains($businessController, 'categories()->sync'))->toBeFalse();

    // ListingController is the only category writer, on the routed Listing.
    expect(str_contains($listingController, 'categories()->sync'))->toBeTrue();
});
