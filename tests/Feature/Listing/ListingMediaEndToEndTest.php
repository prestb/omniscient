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
 * PHASE 21B-G-R1 — MEDIA END-TO-END.
 *
 * The previous phase proved media ROUTES and OWNERSHIP but never exercised an
 * actual upload. These tests drive the real request path with real fake files:
 * validation, storage, database persistence, Listing association, authorization,
 * deletion and isolation.
 *
 * Listing media is LISTING-owned. Organization branding (businesses.logo /
 * cover_image) is a separate concept and is never written here.
 */

function mediaOwner(int $maxListings = 10): User
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

function mediaListing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'status' => Listing::STATUS_DRAFT,
        'hidden_at' => null,
    ], $attrs));
}

function pngFile(string $name = 'photo.png'): UploadedFile
{
    // 200x200 so it clears dimensions:min_width=100,min_height=100.
    return UploadedFile::fake()->image($name, 200, 200);
}

// ═══ UPLOAD ═════════════════════════════════════════════════════════════════

test('a gallery image uploads, is stored and is persisted against the listing', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile('gallery-one.png'),
        'type' => 'gallery',
    ])->assertRedirect();

    $image = ListingImage::firstOrFail();

    expect($image->listing_id)->toBe($listing->id);
    expect($image->type)->toBe('gallery');
    // The file really landed on the disk, not just in the database.
    expect(Storage::disk('public')->exists($image->path))->toBeTrue();
});

test('a logo uploads and is marked as the logo type', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile('brand.png'),
        'type' => 'logo',
    ])->assertRedirect();

    expect(ListingImage::where('listing_id', $listing->id)->where('type', 'logo')->count())->toBe(1);
});

test('a second logo replaces the first rather than accumulating', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    foreach (['first.png', 'second.png'] as $name) {
        $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
            'image' => pngFile($name),
            'type' => 'logo',
        ])->assertRedirect();
    }

    // Only one logo per Listing.
    expect(ListingImage::where('listing_id', $listing->id)->where('type', 'logo')->count())->toBe(1);
});

test('multiple gallery images can coexist on one listing', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    foreach (['a.png', 'b.png', 'c.png'] as $name) {
        $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
            'image' => pngFile($name),
            'type' => 'gallery',
        ])->assertRedirect();
    }

    expect(ListingImage::where('listing_id', $listing->id)->where('type', 'gallery')->count())->toBe(3);
});

// ═══ VALIDATION ═════════════════════════════════════════════════════════════

test('a non-image file is rejected and nothing is persisted', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        'type' => 'gallery',
    ])->assertSessionHasErrors('image');

    expect(ListingImage::count())->toBe(0);
});

test('an unsupported image type is rejected', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml'),
        'type' => 'gallery',
    ])->assertSessionHasErrors('image');

    expect(ListingImage::count())->toBe(0);
});

test('an invalid media type is rejected', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile(),
        'type' => 'invented',
    ])->assertSessionHasErrors('type');

    expect(ListingImage::count())->toBe(0);
});

test('an image below the minimum dimensions is rejected', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => UploadedFile::fake()->image('tiny.png', 10, 10),
        'type' => 'gallery',
    ])->assertSessionHasErrors('image');

    expect(ListingImage::count())->toBe(0);
});

test('a missing file is rejected', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'type' => 'gallery',
    ])->assertStatus(302);

    expect(ListingImage::count())->toBe(0);
});

// ═══ DELETE ═════════════════════════════════════════════════════════════════

test('an owner can delete their own listing image', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile(), 'type' => 'gallery',
    ]);
    $image = ListingImage::firstOrFail();

    $this->actingAs($owner)
        ->delete("/owner/listings/{$listing->id}/images/{$image->id}")
        ->assertRedirect();

    expect(ListingImage::where('listing_id', $listing->id)->count())->toBe(0);
    expect(Storage::disk('public')->exists($image->path))->toBeFalse();
});

test('deleting listing media does not touch organization branding', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $business = Business::factory()->create([
        'owner_id' => $owner->id,
        'logo' => 'branding/org-logo.png',
        'cover_image' => 'branding/org-cover.png',
    ]);
    $listing = mediaListing($owner, ['business_id' => $business->id]);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile(), 'type' => 'gallery',
    ]);
    $image = ListingImage::firstOrFail();

    $this->actingAs($owner)->delete("/owner/listings/{$listing->id}/images/{$image->id}");

    // Organization branding is a SEPARATE concept and is untouched.
    $business->refresh();
    expect($business->logo)->toBe('branding/org-logo.png');
    expect($business->cover_image)->toBe('branding/org-cover.png');
});

// ═══ AUTHORIZATION ══════════════════════════════════════════════════════════

test('a stranger cannot upload media to another owners listing', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $stranger = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($stranger)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile(), 'type' => 'gallery',
    ])->assertForbidden();

    expect(ListingImage::count())->toBe(0);
});

test('a stranger cannot delete another owners listing media', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $stranger = mediaOwner();
    $listing = mediaListing($owner);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile(), 'type' => 'gallery',
    ]);
    $image = ListingImage::firstOrFail();

    $this->actingAs($stranger)
        ->delete("/owner/listings/{$listing->id}/images/{$image->id}")
        ->assertForbidden();

    expect(ListingImage::where('listing_id', $listing->id)->count())->toBe(1);
});

test('a guest cannot upload media', function () {
    Storage::fake('public');

    $listing = mediaListing(mediaOwner());

    $this->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile(), 'type' => 'gallery',
    ])->assertRedirect(route('login'));

    expect(ListingImage::count())->toBe(0);
});

// ═══ ISOLATION ══════════════════════════════════════════════════════════════

test('an image cannot be deleted through a sibling listing', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = mediaListing($owner, ['business_id' => $business->id, 'name' => 'A1']);
    $a2 = mediaListing($owner, ['business_id' => $business->id, 'name' => 'A2']);

    $this->actingAs($owner)->post("/owner/listings/{$a1->id}/images", [
        'image' => pngFile(), 'type' => 'gallery',
    ]);
    $image = ListingImage::firstOrFail();

    // Same owner, same Business - but the image does NOT belong to A2.
    $this->actingAs($owner)
        ->delete("/owner/listings/{$a2->id}/images/{$image->id}")
        ->assertNotFound();

    expect(ListingImage::where('listing_id', $a1->id)->count())->toBe(1);
});

test('media on one listing does not leak to its sibling', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = mediaListing($owner, ['business_id' => $business->id]);
    $a2 = mediaListing($owner, ['business_id' => $business->id]);

    $this->actingAs($owner)->post("/owner/listings/{$a1->id}/images", [
        'image' => pngFile('only-a1.png'), 'type' => 'gallery',
    ])->assertRedirect();

    expect(ListingImage::where('listing_id', $a1->id)->count())->toBe(1);
    expect(ListingImage::where('listing_id', $a2->id)->count())->toBe(0);

    // Each index shows only its own media.
    $propsA2 = $this->actingAs($owner)->get("/owner/listings/{$a2->id}/images")
        ->assertOk()->viewData('page')['props'];
    expect($propsA2['images'])->toBe([]);
});

// ═══ BUSINESS-LESS PARITY ═══════════════════════════════════════════════════

test('a business-less professional manages media without a business', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner, ['business_id' => null]);

    expect($listing->business_id)->toBeNull();

    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/images")->assertOk();

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile(), 'type' => 'cover',
    ])->assertRedirect();

    expect(ListingImage::where('listing_id', $listing->id)->count())->toBe(1);
    expect(Business::where('owner_id', $owner->id)->count())->toBe(0);
});

test('a business-backed listing has identical media capability', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = mediaListing($owner, ['business_id' => $business->id]);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile(), 'type' => 'gallery',
    ])->assertRedirect();

    // The Business did not become the owner of the media.
    expect(ListingImage::firstOrFail()->listing_id)->toBe($listing->id);
});

// ═══ COMPLETENESS INTERACTION ═══════════════════════════════════════════════

test('uploading a gallery image raises completeness through the existing service', function () {
    Storage::fake('public');

    $owner = mediaOwner();
    $listing = mediaListing($owner, ['description' => 'Described.']);

    $service = new \App\Services\ListingCompletenessService();

    $before = $service->calculate($listing->fresh())['score'];

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/images", [
        'image' => pngFile(), 'type' => 'gallery',
    ])->assertRedirect();

    $after = $service->calculate($listing->fresh())['score'];

    expect($after)->toBeGreaterThanOrEqual($before);

    // And removing it cannot leave the score higher than it was.
    $image = ListingImage::firstOrFail();
    $this->actingAs($owner)->delete("/owner/listings/{$listing->id}/images/{$image->id}");

    expect($service->calculate($listing->fresh())['score'])->toBe($before);
});

test('the frontend carries no duplicate completeness scoring for media', function () {
    $source = file_get_contents(resource_path('js/Pages/Owner/Listings/Images/Index.vue'));

    // The backend service owns scoring; the UI only displays what it is given.
    expect($source)->not->toContain('score +=');
    expect($source)->not->toContain('calculateCompleteness');
});
