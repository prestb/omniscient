<?php

use App\Models\Business;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 12 — LISTING-ATTRIBUTED CONNECTION.
 *
 * An inquiry is attributed to the exact LISTING the visitor viewed. Business is
 * optional context, derived server-side from the Listing — never submitted by
 * the visitor, and never chosen by a representative-Listing rule.
 */

/** An owner whose plan unlocks lead capture (the existing entitlement gate). */
function entitledOwner(): User
{
    $plan = Plan::factory()->create([
        'max_listings' => 10,
        'features' => ['lead_capture' => true],
    ]);

    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    return $owner;
}

function inquiryPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'message' => 'Please send me a quote.',
    ], $overrides);
}

test('a business-less listing can receive an inquiry', function () {
    // PHASE 21B-R2: a business-less Listing is no longer ungated. It obeys
    // the same account-level lead-capture entitlement as any other Listing,
    // so this fixture now needs an owner whose plan grants it.
    $owner = entitledOwner();
    $listing = Listing::factory()->create(['owner_id' => entitledOwner()->id, 
        'owner_id' => $owner->id,
        'business_id' => null,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    $this->post("/listing/{$listing->slug}/contact", inquiryPayload())
        ->assertOk()
        ->assertJson(['success' => true]);

    $lead = Lead::firstOrFail();

    expect((int) $lead->listing_id)->toBe($listing->id);
    expect($lead->business_id)->toBeNull();
});

test('a business-owned listing receives the correct business context', function () {
    $business = Business::factory()->create(['owner_id' => entitledOwner()->id]);
    $listing = Listing::factory()->forBusiness($business)->create([
        'owner_id' => $business->owner_id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    $this->post("/listing/{$listing->slug}/contact", inquiryPayload())->assertOk();

    $lead = Lead::firstOrFail();

    expect((int) $lead->listing_id)->toBe($listing->id);
    expect((int) $lead->business_id)->toBe($business->id);
});

test('two listings of one business attribute inquiries to the exact listing', function () {
    $business = Business::factory()->create(['owner_id' => entitledOwner()->id]);

    $a = Listing::factory()->forBusiness($business)->create([
        'owner_id' => $business->owner_id,'status' => Listing::STATUS_PUBLISHED, 'hidden_at' => null]);
    $b = Listing::factory()->forBusiness($business)->create([
        'owner_id' => $business->owner_id,'status' => Listing::STATUS_PUBLISHED, 'hidden_at' => null]);

    $this->post("/listing/{$a->slug}/contact", inquiryPayload())->assertOk();
    $this->post("/listing/{$b->slug}/contact", inquiryPayload())->assertOk();

    expect(Lead::where('listing_id', $a->id)->count())->toBe(1);
    expect(Lead::where('listing_id', $b->id)->count())->toBe(1);
    expect(Lead::count())->toBe(2);
});

test('a visitor cannot spoof business context', function () {
    $real = Business::factory()->create(['owner_id' => entitledOwner()->id]);
    $other = Business::factory()->create();

    $listing = Listing::factory()->forBusiness($real)->create([
        'owner_id' => $real->owner_id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    $this->post("/listing/{$listing->slug}/contact", inquiryPayload([
        'business_id' => $other->id,
        'listing_id' => 999999,
    ]))->assertOk();

    $lead = Lead::firstOrFail();

    // Server-derived values win; submitted ids are ignored.
    expect((int) $lead->business_id)->toBe($real->id);
    expect((int) $lead->listing_id)->toBe($listing->id);
});

test('an unpublished listing cannot receive an inquiry', function () {
    $listing = Listing::factory()->create(['owner_id' => entitledOwner()->id, 
        'status' => Listing::STATUS_DRAFT,
        'hidden_at' => null,
    ]);

    $this->post("/listing/{$listing->slug}/contact", inquiryPayload())
        ->assertNotFound();

    expect(Lead::count())->toBe(0);
});

test('a hidden listing cannot receive an inquiry', function () {
    $listing = Listing::factory()->create(['owner_id' => entitledOwner()->id, 
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => now(),
    ]);

    $this->post("/listing/{$listing->slug}/contact", inquiryPayload())
        ->assertNotFound();

    expect(Lead::count())->toBe(0);
});

test('an inquiry requires a contact method and a message', function () {
    $listing = Listing::factory()->create(['owner_id' => entitledOwner()->id, 
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    $this->post("/listing/{$listing->slug}/contact", [
        'name' => 'No Contact',
        'message' => 'Hello',
    ])->assertStatus(422);

    $this->post("/listing/{$listing->slug}/contact", [
        'name' => 'No Message',
        'email' => 'x@example.com',
    ])->assertSessionHasErrors();

    expect(Lead::count())->toBe(0);
});

test('every new inquiry has a non-null authoritative listing id', function () {
    $listing = Listing::factory()->create(['owner_id' => entitledOwner()->id, 
        'business_id' => null,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    $this->post("/listing/{$listing->slug}/contact", inquiryPayload())->assertOk();

    expect(Lead::whereNull('listing_id')->count())->toBe(0);
});

test('the owner is notified and the notification names the listing', function () {
    $owner = entitledOwner();
    $listing = Listing::factory()->create(['owner_id' => entitledOwner()->id, 
        'owner_id' => $owner->id,
        'business_id' => null,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    $this->post("/listing/{$listing->slug}/contact", inquiryPayload())->assertOk();

    $lead = Lead::firstOrFail();

    // The notification is written to the owner's notifiable record, including
    // Listing identity, and works without a Business.
    if (Schema::hasTable('notifications')) {
        $row = DB::table('notifications')
            ->where('notifiable_id', $owner->id)
            ->where('notifiable_type', User::class)
            ->latest('created_at')
            ->first();

        expect($row)->not->toBeNull();

        $data = json_decode($row->data, true);
        $blob = json_encode($data);

        expect($blob)->toContain((string) $listing->id);
        expect($blob)->toContain($listing->name);
    }

    expect((int) $lead->listing_id)->toBe($listing->id);
});

test('inquiries are not shared across listings', function () {
    $a = Listing::factory()->create(['owner_id' => entitledOwner()->id, 'business_id' => null, 'status' => Listing::STATUS_PUBLISHED, 'hidden_at' => null]);
    $b = Listing::factory()->create(['owner_id' => entitledOwner()->id, 'business_id' => null, 'status' => Listing::STATUS_PUBLISHED, 'hidden_at' => null]);

    $this->post("/listing/{$a->slug}/contact", inquiryPayload(['name' => 'For A']))->assertOk();

    expect(Lead::where('listing_id', $a->id)->pluck('name')->all())->toBe(['For A']);
    expect(Lead::where('listing_id', $b->id)->count())->toBe(0);
});

test('soft-deleting a listing retains its inquiries without orphaning the reference', function () {
    $listing = Listing::factory()->create(['owner_id' => entitledOwner()->id, 'business_id' => null, 'status' => Listing::STATUS_PUBLISHED, 'hidden_at' => null]);

    $this->post("/listing/{$listing->slug}/contact", inquiryPayload())->assertOk();

    $listing->delete();

    // The lead survives and still points at a real (soft-deleted) Listing row.
    $lead = Lead::firstOrFail();
    expect((int) $lead->listing_id)->toBe($listing->id);
    expect(Listing::withTrashed()->find($lead->listing_id))->not->toBeNull();

    // And the public route no longer resolves the deleted Listing.
    $this->post("/listing/{$listing->slug}/contact", inquiryPayload())->assertNotFound();
});

test('the retired business contact route no longer exists', function () {
    expect(Route::has('business.contact'))->toBeFalse();
    expect(Route::has('listing.contact'))->toBeTrue();
});

test('no representative listing selection exists in the inquiry path', function () {
    $source = file_get_contents(app_path('Http/Controllers/Public/ListingLeadController.php'));

    expect($source)->not->toContain('listings()->first');
    expect($source)->not->toContain('listings()->latest');
    expect($source)->not->toContain('primaryListing');
    expect($source)->not->toContain('defaultListing');
});
