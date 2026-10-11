<?php

use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * CONTRACT B - LISTING-TYPE AVAILABILITY FOLLOW-UP.
 *
 * Business, Professional and Store are all available under the existing GENERAL
 * Listing-creation rules. There is no type-specific plan gate: the account-level
 * Listing quota (`canAdd('listings')` / `plan.limit:listings`) is the binding
 * control, and it applies to CREATION only.
 *
 * These two tests close the sole coverage gap found after the Contract B
 * reconciliation. Helper prefix `cb` is unique to this file.
 */

function cbOwner(int $maxListings = 5, array $features = []): User
{
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => Plan::factory()->create([
            'max_listings' => $maxListings,
            'features' => $features,
        ])->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    return User::find($owner->id);
}

test('changing a Listing type updates the existing Listing and consumes no extra quota slot', function () {
    // 1. A finite Listing allowance of exactly one.
    $owner = cbOwner(1);

    // 2. The owner already holds a Listing, so the allowance is fully consumed.
    $listing = Listing::factory()->published()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
        'type' => 'business',
        'name' => 'Original name',
    ]);

    expect(Listing::where('owner_id', $owner->id)->count())->toBe(1);

    // 3 + 4. A meaningful Business -> Professional transition succeeds under the
    // existing authorization rules (ownership via ListingPolicy, no type gate).
    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}", ['type' => 'professional', 'name' => 'Original name'])
        ->assertRedirect();

    // 5. The SAME record was updated - not duplicated.
    $fresh = $listing->fresh();

    expect($fresh->id)->toBe($listing->id)
        ->and($fresh->type)->toBe('professional')
        ->and($fresh->owner_id)->toBe($owner->id);

    // 6. The Listing count is unchanged and still exactly at the finite limit.
    expect(Listing::where('owner_id', $owner->id)->count())->toBe(1);
});

test('changing a Listing type from Business to Store also reuses the same record', function () {
    $owner = cbOwner(1);

    $listing = Listing::factory()->published()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
        'type' => 'business',
    ]);

    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}", ['type' => 'store', 'name' => $listing->name])
        ->assertRedirect();

    expect($listing->fresh()->type)->toBe('store')
        ->and(Listing::where('owner_id', $owner->id)->count())->toBe(1);
});

test('an existing Professional Listing stays editable without a stores/professionals flag', function () {
    // 5. Deliberately NO `features` at all - the legacy type-specific flags are
    // absent. Under the retired gating design an absent flag denied the type; under
    // Contract B it must not block ordinary maintenance of an existing Listing.
    $owner = cbOwner(5, []);

    $listing = Listing::factory()->professional()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
        'name' => 'Before edit',
    ]);

    $countBefore = Listing::where('owner_id', $owner->id)->count();

    // An ordinary editable field, with the type left unchanged (as the edit form
    // submits it - `ListingRequest` requires `type`, so it is always sent).
    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}", ['type' => 'professional', 'name' => 'After edit'])
        ->assertRedirect();

    $fresh = $listing->fresh();

    expect($fresh->name)->toBe('After edit')
        ->and($fresh->type)->toBe('professional')
        ->and($fresh->owner_id)->toBe($owner->id)
        ->and((int) $fresh->id)->toBe((int) $listing->id);

    // No duplicate was created.
    expect(Listing::where('owner_id', $owner->id)->count())->toBe($countBefore)
        ->and($countBefore)->toBe(1);
});

test('an existing Store Listing stays editable without any type flag', function () {
    $owner = cbOwner(5, []);
    $listing = Listing::factory()->ofStoreType()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
    ]);

    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}", ['type' => 'store', 'name' => 'Store renamed'])
        ->assertRedirect();

    expect($listing->fresh()->name)->toBe('Store renamed')
        ->and($listing->fresh()->type)->toBe('store')
        ->and(Listing::where('owner_id', $owner->id)->count())->toBe(1);
});
