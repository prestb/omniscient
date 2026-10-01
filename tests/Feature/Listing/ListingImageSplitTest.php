<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-3 — SPLIT IMAGE OWNERSHIP.
 *
 *   Organization branding  -> businesses.logo / businesses.cover_image  (BUSINESS)
 *   Listing media          -> listing_images                            (LISTING)
 *
 * The two concepts are independent: neither is derived from the other.
 */

/** An owner with an active plan, for the `plan.limit:images` branding route. */
function brandingOwner(array $businessAttributes = []): array
{
    $plan = Plan::factory()->create(['max_listings' => 10, 'max_services' => 10, 'max_images' => 10]);

    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $business = Business::factory()->create(
        array_merge(['owner_id' => $owner->id], $businessAttributes)
    );

    return [$owner, $business];
}

function fakeImage(string $name = 'test.jpg'): UploadedFile
{
    return UploadedFile::fake()->image($name, 200, 200);
}

test('a business with zero listings can hold organization branding', function () {
    $business = Business::factory()->create([
        'logo' => 'businesses/1/branding/logo.jpg',
        'cover_image' => 'businesses/1/branding/cover.jpg',
    ]);

    expect($business->listings()->count())->toBe(0);
    expect($business->fresh()->logo)->toBe('businesses/1/branding/logo.jpg');
    expect($business->fresh()->cover_image)->toBe('businesses/1/branding/cover.jpg');
    expect($business->fresh()->logo_url)->not->toBeNull();
});

test('organization branding is not derived through a listing', function () {
    $business = Business::factory()->create(['logo' => 'org/acme.png']);

    $a = Listing::factory()->forBusiness($business)->create();
    $b = Listing::factory()->forBusiness($business)->create();

    // Two Listings, two different Listing logos.
    ListingImage::create(['listing_id' => $a->id, 'path' => 'a-logo.png', 'type' => 'logo', 'sort_order' => 1]);
    ListingImage::create(['listing_id' => $b->id, 'path' => 'b-logo.png', 'type' => 'logo', 'sort_order' => 1]);

    // The organization's branding is its own column — never one of the Listings'.
    expect($business->fresh()->logo)->toBe('org/acme.png');
    expect($business->fresh()->logo_url)->toContain('org/acme.png');

    // The through-Listing branding relations no longer exist at all.
    expect(method_exists($business, 'logo'))->toBeFalse();
    expect(method_exists($business, 'coverImage'))->toBeFalse();
});

test('listing media belongs to the explicit listing and not its siblings', function () {
    Storage::fake('public');

    [$owner, $business] = brandingOwner();

    $a = Listing::factory()->forBusiness($business)->forOwner($owner)->create();
    $b = Listing::factory()->forBusiness($business)->forOwner($owner)->create();
    $c = Listing::factory()->forBusiness($business)->forOwner($owner)->create();

    $this->actingAs($owner)->post("/owner/listings/{$b->id}/images", [
        'image' => fakeImage(),
        'type' => 'gallery',
    ])->assertRedirect();

    $image = ListingImage::firstOrFail();

    expect($image->listing_id)->toBe($b->id);
    expect($a->images()->count())->toBe(0);
    expect($b->images()->count())->toBe(1);
    expect($c->images()->count())->toBe(0);
});

test('an image id cannot be deleted through a sibling listing url', function () {
    Storage::fake('public');

    [$owner, $business] = brandingOwner();

    $a = Listing::factory()->forBusiness($business)->forOwner($owner)->create();
    $b = Listing::factory()->forBusiness($business)->forOwner($owner)->create();

    $imageX = ListingImage::create([
        'listing_id' => $a->id,
        'path' => 'x.png',
        'type' => 'gallery',
        'sort_order' => 1,
    ]);

    // Same owner, same Business — but the image belongs to Listing A.
    $this->actingAs($owner)
        ->delete("/owner/listings/{$b->id}/images/{$imageX->id}")
        ->assertNotFound();

    expect(ListingImage::count())->toBe(1);
    expect($imageX->fresh())->not->toBeNull();
});

test('an owner cannot manage media for another users listing', function () {
    Storage::fake('public');

    $ownerA = User::factory()->owner()->create();
    $ownerB = User::factory()->owner()->create();

    $listingA = Listing::factory()->forOwner($ownerA)->create();
    $listingB = Listing::factory()->forOwner($ownerB)->create();

    $this->actingAs($ownerA)->get("/owner/listings/{$listingB->id}/images")->assertForbidden();

    $this->actingAs($ownerA)->post("/owner/listings/{$listingB->id}/images", [
        'image' => fakeImage(),
        'type' => 'gallery',
    ])->assertForbidden();

    expect(ListingImage::count())->toBe(0);
});

test('organization branding does not create listing media implicitly', function () {
    Storage::fake('public');

    [$owner, $business] = brandingOwner();

    // The Business HAS Listings — branding must still not touch them.
    Listing::factory()->forBusiness($business)->forOwner($owner)->create();

    $this->actingAs($owner)->post("/owner/businesses/{$business->id}/images", [
        'image' => fakeImage('org-logo.jpg'),
        'type' => 'logo',
    ])->assertRedirect();

    expect($business->fresh()->logo)->not->toBeNull();
    expect(ListingImage::count())->toBe(0);
});

test('listing media does not modify organization branding implicitly', function () {
    Storage::fake('public');

    [$owner, $business] = brandingOwner(['logo' => 'org/acme.png', 'cover_image' => 'org/cover.png']);

    $listing = Listing::factory()->forBusiness($business)->forOwner($owner)->create();

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => fakeImage('listing-logo.jpg'),
        'type' => 'logo',
    ])->assertRedirect();

    expect(ListingImage::count())->toBe(1);
    expect(ListingImage::firstOrFail()->type)->toBe('logo');

    // The organization's branding is untouched.
    expect($business->fresh()->logo)->toBe('org/acme.png');
    expect($business->fresh()->cover_image)->toBe('org/cover.png');
});

test('the image path contains no arbitrary listing resolution', function () {
    $strip = function (string $path): string {
        return collect(preg_split('/\R/', File::get($path)))
            ->reject(function (string $line) {
                $t = ltrim($line);
                return str_starts_with($t, '*') || str_starts_with($t, '//') || str_starts_with($t, '/*');
            })
            ->implode("\n");
    };

    foreach ([
        app_path('Http/Controllers/Owner/ImageController.php'),
        app_path('Http/Controllers/Owner/ListingImageController.php'),
    ] as $path) {
        $code = $strip($path);

        foreach (['primaryListing', 'listings()->first', 'listings->first', 'listings()->value('] as $forbidden) {
            expect(str_contains($code, $forbidden))
                ->toBeFalse(basename($path) . " must not contain executable: {$forbidden}");
        }

        // Only the Listing media controller must be Listing-signatured. The
        // branding controller legitimately takes a Business — branding IS
        // Business-owned.
        if (str_contains($path, 'ListingImageController')) {
            expect(str_contains($code, 'Business $business'))->toBeFalse();
            expect(str_contains($code, 'Listing $listing'))->toBeTrue();
        }
    }

    // The branding controller must not write Listing media.
    $branding = $strip(app_path('Http/Controllers/Owner/ImageController.php'));
    expect(str_contains($branding, 'ListingImage::create'))->toBeFalse();

    // The Listing controller must not write organization branding columns.
    $media = $strip(app_path('Http/Controllers/Owner/ListingImageController.php'));
    expect(str_contains($media, "update(['logo'"))->toBeFalse();
    expect(str_contains($media, "cover_image' =>"))->toBeFalse();
});
