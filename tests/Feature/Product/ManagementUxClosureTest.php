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
 * PHASE 21B-G-R2 — MANAGEMENT UX CLOSURE.
 *
 * Guards the empty-state guidance on each management surface, the mutation
 * failure behaviour, and the regression contracts (business-less parity,
 * isolation, authorization) that the UX changes must not disturb.
 */

function gx2Owner(int $maxListings = 10): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => $maxListings,
        'max_locations' => 3,
        'max_images' => 10,
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

function gx2Listing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'status' => Listing::STATUS_DRAFT,
        'hidden_at' => null,
    ], $attrs));
}

// ═══ EMPTY STATES EXPLAIN THE SURFACE AND OFFER AN ACTION ═══════════════════

test('every management surface has an intentional empty state', function (string $rel, array $mustContain) {
    $source = file_get_contents(resource_path('js/' . $rel));

    foreach ($mustContain as $phrase) {
        expect($source)->toContain($phrase);
    }
})->with([
    // Services: what it is for + an action.
    ['Pages/Owner/Services/Index.vue', ['No services yet', 'understand what you do', 'Add Your First Service']],
    // Contacts: why it matters + an action.
    ['Pages/Owner/Contacts/Index.vue', ['No contacts yet', 'so customers can reach you', 'Add Your First Contact']],
    // Media: what each type is for + where the action is.
    ['Pages/Owner/Listings/Images/Index.vue', [
        'No media on this Listing yet',
        'Images help visitors understand',
        'Logo', 'Cover', 'Gallery',
        'Use the upload controls above',
    ]],
]);

test('the media empty state explains the three media types distinctly', function () {
    $source = file_get_contents(resource_path('js/Pages/Owner/Listings/Images/Index.vue'));

    // It must not blur logo / cover / gallery into one concept.
    expect($source)->toContain('shown beside the name');
    expect($source)->toContain('wide image at the top');
    expect($source)->toContain('Photos of your work');
});

test('no empty state makes an unsupported performance claim', function (string $rel) {
    $source = file_get_contents(resource_path('js/' . $rel));

    foreach (['increase conversions', 'boost sales', 'more customers', 'get verified', 'trusted', 'best '] as $claim) {
        expect(strtolower($source))->not->toContain($claim);
    }
})->with([
    'Pages/Owner/Services/Index.vue',
    'Pages/Owner/Contacts/Index.vue',
    'Pages/Owner/Listings/Images/Index.vue',
]);

test('the categories surface is not a separate page', function () {
    // Categories are managed inside the Listing edit form via CategoryMultiSelect.
    // A dedicated page was deliberately NOT created for symmetry.
    expect(file_exists(resource_path('js/Pages/Owner/Categories/Index.vue')))->toBeFalse();
    expect(file_exists(resource_path('js/Components/CategoryMultiSelect.vue')))->toBeTrue();
});

// ═══ MUTATION FAILURE BEHAVIOUR ═════════════════════════════════════════════

test('an invalid service is rejected with a visible field error', function () {
    $owner = gx2Owner();
    $listing = gx2Listing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/services", [
        'name' => '',
    ])->assertSessionHasErrors('name');

    expect(ListingService::count())->toBe(0);
});

test('an invalid contact is rejected with a visible field error', function () {
    $owner = gx2Owner();
    $listing = gx2Listing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/contacts", [
        'type' => 'phone', 'value' => '',
    ])->assertSessionHasErrors('value');

    expect(ListingContact::count())->toBe(0);
});

test('an invalid contact type is rejected', function () {
    $owner = gx2Owner();
    $listing = gx2Listing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/contacts", [
        'type' => 'carrier-pigeon', 'value' => 'something',
    ])->assertSessionHasErrors('type');

    expect(ListingContact::count())->toBe(0);
});

test('an invalid category selection is rejected and persists nothing', function () {
    $owner = gx2Owner();
    $listing = gx2Listing($owner);

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional',
        'name' => 'Still valid',
        'categories' => [999999],
    // The rule is `categories.*` => exists, so Laravel reports it per item.
    ])->assertSessionHasErrors();

    expect($listing->fresh()->categories()->count())->toBe(0);
});

// ═══ BUSINESS-LESS REGRESSION ═══════════════════════════════════════════════

test('a business-less professional can still manage every surface and publish', function () {
    $owner = gx2Owner();
    $listing = gx2Listing($owner, ['business_id' => null]);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/services", ['name' => 'Leak repair'])
        ->assertRedirect();
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/contacts", [
        'type' => 'phone', 'value' => '+237600000123',
    ])->assertRedirect();

    // Each management index renders.
    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/services")->assertOk();
    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/contacts")->assertOk();
    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/images")->assertOk();

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/publish")->assertRedirect();
    expect($listing->fresh()->status)->toBe(Listing::STATUS_PUBLISHED);

    // No Business was ever required.
    expect(Business::where('owner_id', $owner->id)->count())->toBe(0);
});

// ═══ BUSINESS-BACKED REGRESSION ═════════════════════════════════════════════

test('a business-backed listing keeps identical management capability', function () {
    $owner = gx2Owner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = gx2Listing($owner, ['business_id' => $business->id]);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/services", ['name' => 'Service'])
        ->assertRedirect();

    // The Business did not take ownership of the Listing's content.
    expect(ListingService::firstOrFail()->listing_id)->toBe($listing->id);
});

// ═══ ISOLATION ══════════════════════════════════════════════════════════════

test('mutating A1 leaves A2 untouched on every surface', function () {
    $owner = gx2Owner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = gx2Listing($owner, ['business_id' => $business->id, 'name' => 'A1', 'status' => Listing::STATUS_PUBLISHED]);
    $a2 = gx2Listing($owner, ['business_id' => $business->id, 'name' => 'A2', 'status' => Listing::STATUS_PUBLISHED]);

    $this->actingAs($owner)->post("/owner/listings/{$a1->id}/services", ['name' => 'Only A1'])
        ->assertRedirect();
    $this->actingAs($owner)->post("/owner/listings/{$a1->id}/contacts", [
        'type' => 'phone', 'value' => '+237600000A1',
    ])->assertRedirect();
    $this->actingAs($owner)->post("/owner/listings/{$a1->id}/unpublish")->assertRedirect();

    // Asserted by ID and content, not merely counts.
    expect(ListingService::where('listing_id', $a2->id)->count())->toBe(0);
    expect(ListingContact::where('listing_id', $a2->id)->count())->toBe(0);
    expect(ListingService::where('listing_id', $a1->id)->value('name'))->toBe('Only A1');

    // A2's publication state is unchanged.
    expect($a1->fresh()->status)->toBe(Listing::STATUS_DRAFT);
    expect($a2->fresh()->status)->toBe(Listing::STATUS_PUBLISHED);
});

// ═══ AUTHORIZATION ══════════════════════════════════════════════════════════

test('a stranger is denied on every management mutation', function () {
    $owner = gx2Owner();
    $stranger = gx2Owner();
    $listing = gx2Listing($owner);

    $this->actingAs($stranger)->post("/owner/listings/{$listing->id}/services", ['name' => 'Hijack'])
        ->assertForbidden();
    $this->actingAs($stranger)->post("/owner/listings/{$listing->id}/contacts", [
        'type' => 'phone', 'value' => '+237600000000',
    ])->assertForbidden();
    $this->actingAs($stranger)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional', 'name' => 'Hijack', 'description' => 'x',
    ])->assertForbidden();
    $this->actingAs($stranger)->post("/owner/listings/{$listing->id}/publish")->assertForbidden();

    expect(ListingService::count())->toBe(0);
    expect(ListingContact::count())->toBe(0);
    expect($listing->fresh()->status)->not->toBe(Listing::STATUS_PUBLISHED);
});

// ═══ RESPONSIVE SOURCE INVARIANT ════════════════════════════════════════════

test('management grids are responsive rather than fixed multi-column', function (string $rel) {
    $source = file_get_contents(resource_path('js/' . $rel));

    // Any multi-column grid must have a mobile-first single-column base or an
    // explicit small-screen breakpoint.
    preg_match_all('/grid-cols-(\d+)(?![a-z0-9-])/', $source, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    foreach ($m as $match) {
        $columns = (int) $match[1][0];

        // 1 and 2 columns are safe on a narrow screen (a 2-up gallery is
        // intentional). THREE or more without a responsive variant is the
        // genuine narrow-screen risk.
        if ($columns < 3) {
            continue;
        }

        // Find the opening class attribute containing this utility.
        $before = substr($source, 0, $match[0][1]);
        $attrStart = strrpos($before, 'class="');
        $attr = $attrStart !== false ? substr($source, $attrStart, $match[0][1] - $attrStart + 40) : '';

        // A bare grid-cols-N with no responsive prefix and no sm:/lg: variant
        // would be a fixed multi-column layout on phones.
        $isPrefixed = preg_match('/(sm|md|lg|xl):grid-cols-' . $columns . '/', $attr) === 1;
        $hasBase = preg_match('/grid-cols-1/', $attr) === 1;

        expect($isPrefixed || $hasBase)->toBeTrue();
    }

    // Some surfaces legitimately contain no 3+ column grid; assert that
    // explicitly so the dataset never runs without an assertion (Pest
    // marks assertion-free tests as risky).
    expect(true)->toBeTrue();
})->with([
    'Pages/Owner/Services/Index.vue',
    'Pages/Owner/Contacts/Index.vue',
    'Pages/Owner/Listings/Images/Index.vue',
]);
