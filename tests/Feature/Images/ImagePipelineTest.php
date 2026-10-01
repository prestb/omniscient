<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Create an owner with verified email + active subscription so
 * owner-side middleware doesn't redirect.
 *
 * PHASE 11 / WAVE 1D-3 — organization branding is BUSINESS-owned
 * (`businesses.logo` / `businesses.cover_image`), while Listing presentation
 * media is LISTING-owned (`listing_images`). They live on separate routes and
 * neither resolves through the other.
 */
function createOwnerWithBusiness(): array
{
    $plan = Plan::factory()->create(['max_listings' => 10]);

    $owner = User::factory()->owner()->create([
        'email_verified_at' => now(),
    ]);

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $business = Business::factory()->published()->create(['owner_id' => $owner->id]);
    $listing = Listing::factory()->forBusiness($business)->create();

    return [$owner, $business, $listing];
}

test('uploading a logo generates all variants', function () {
    [$owner, $business] = createOwnerWithBusiness();

    $file = UploadedFile::fake()->image('logo.png', 800, 800);

    $response = $this->actingAs($owner)->post(
        "/owner/businesses/{$business->id}/images",
        ['image' => $file, 'type' => 'logo']
    );

    $response->assertRedirect();

    $business->refresh();
    expect($business->logo)->not->toBeNull();

    // Variants should exist on disk
    $base = preg_replace('#\.[^/.]+$#', '', $business->logo);

    Storage::disk('public')->assertExists("{$base}.jpg");
    Storage::disk('public')->assertExists("{$base}_thumb.jpg");
    Storage::disk('public')->assertExists("{$base}_medium.jpg");
    Storage::disk('public')->assertExists("{$base}_large.jpg");
});

test('deleting a listing image removes all variants from storage', function () {
    [$owner, $business, $listing] = createOwnerWithBusiness();

    $file = UploadedFile::fake()->image('test.jpg', 800, 600);

    // PHASE 11 / WAVE 1D-3 — gallery media is LISTING-owned, so it is uploaded
    // and deleted through the explicit Listing route.
    $this->actingAs($owner)->post(
        "/owner/listings/{$listing->id}/images",
        ['image' => $file, 'type' => 'gallery']
    );

    $image = ListingImage::where('listing_id', $listing->id)->first();
    expect($image)->not->toBeNull();

    $base = preg_replace('#\.[^/.]+$#', '', $image->path);
    Storage::disk('public')->assertExists("{$base}_thumb.jpg");

    $response = $this->actingAs($owner)->delete(
        "/owner/listings/{$listing->id}/images/{$image->id}"
    );

    $response->assertRedirect();

    Storage::disk('public')->assertMissing($image->path);
    Storage::disk('public')->assertMissing("{$base}_thumb.jpg");
    Storage::disk('public')->assertMissing("{$base}_medium.jpg");
    Storage::disk('public')->assertMissing("{$base}_large.jpg");

    expect(ListingImage::find($image->id))->toBeNull();
});

test('ListingImage has thumbnail_url and medium_url accessors', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->published()->create(['owner_id' => $owner->id]);
    $listing = Listing::factory()->forBusiness($business)->create();

    $image = ListingImage::create([
        'listing_id' => $listing->id,
        'path' => 'businesses/1/images/test.jpg',
        'type' => 'gallery',
        'is_primary' => false,
        'sort_order' => 1,
    ]);

    expect($image->thumbnail_url)->toBeNull();
    expect($image->medium_url)->toBeNull();
    expect($image->url)->toContain('test.jpg');
});

test('owner cannot upload to another business', function () {
    [$owner] = createOwnerWithBusiness();

    // Another owner's business
    $otherOwner = User::factory()->owner()->create();
    $otherBusiness = Business::factory()->published()->create(['owner_id' => $otherOwner->id]);

    $file = UploadedFile::fake()->image('logo.png');

    $response = $this->actingAs($owner)->post(
        "/owner/businesses/{$otherBusiness->id}/images",
        ['image' => $file, 'type' => 'logo']
    );

    $response->assertForbidden();
});
