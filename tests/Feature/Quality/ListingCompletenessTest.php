<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingContact;
use App\Models\ListingImage;
use App\Models\ListingService;
use App\Models\User;
use App\Services\ListingCompletenessService;
use App\Support\ListingType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 14 — LISTING QUALITY & TRUST CLARITY.
 */

function completeness(Listing $listing): array
{
    return (new ListingCompletenessService())->calculate($listing);
}

/** A fully-populated physical Listing. */
function completeListing(array $attrs = []): Listing
{
    $listing = Listing::factory()->create(array_merge([
        'business_id' => null,
        'type' => ListingType::BUSINESS->value,
        'description' => str_repeat('A genuinely useful description. ', 3),
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));

    $cat = Category::create(['name' => 'Cat ' . uniqid(), 'slug' => 'cat-' . uniqid(), 'is_active' => true]);
    $listing->categories()->sync([$cat->id]);

    ListingService::create(['listing_id' => $listing->id, 'name' => 'Service', 'sort_order' => 0]);
    ListingContact::create(['listing_id' => $listing->id, 'type' => 'phone', 'value' => '+237600000000', 'is_primary' => true]);

    foreach (['logo', 'cover', 'gallery'] as $type) {
        ListingImage::create(['listing_id' => $listing->id, 'path' => "x/{$type}.png", 'type' => $type]);
    }

    $location = \App\Models\Location::factory()->create(['business_id' => $listing->business_id]);
    $listing->update(['location_id' => $location->id]);

    return $listing->fresh();
}

// ── 1. Verification correction ──────────────────────────────────────────────

test('a paid verified_badge plan feature never produces is_verified', function () {
    $owner = User::factory()->owner()->create();
    $listing = completeListing(['owner_id' => $owner->id]);

    // The map payload must not carry the field at all.
    $source = file_get_contents(app_path('Http/Controllers/Public/DirectoryController.php'));

    expect($source)->not->toContain("'is_verified'");

    // And no directory/resource payload exposes is_verified anywhere.
    foreach (['Http/Resources/ListingDirectoryResource.php', 'Http/Resources/BusinessDirectoryResource.php'] as $res) {
        expect(file_get_contents(app_path($res)))->not->toContain("'is_verified'");
    }
});

test('the map no longer renders a verified badge', function () {
    $map = file_get_contents(resource_path('js/Components/Public/DirectoryMap.vue'));

    expect($map)->not->toContain('business.is_verified');
    expect($map)->not->toContain('Verified</span>');
});

// ── 2. Listing completeness ─────────────────────────────────────────────────

test('a fully complete listing scores 100', function () {
    $result = completeness(completeListing());

    expect($result['score'])->toBe(100);
    expect($result['completed'])->toBe($result['total']);
    expect($result['missing'])->toBe([]);
});

test('a missing description reduces completeness', function () {
    $listing = completeListing();
    $listing->update(['description' => '']);

    $result = completeness($listing->fresh());

    expect($result['score'])->toBeLessThan(100);
    expect(collect($result['missing'])->pluck('key')->all())->toContain('description');
});

test('a whitespace-only description counts as missing', function () {
    $listing = completeListing();
    $listing->update(['description' => "   \n\t  "]);

    expect(collect(completeness($listing->fresh())['missing'])->pluck('key')->all())->toContain('description');
});

test('missing categories reduces completeness', function () {
    $listing = completeListing();
    $listing->categories()->sync([]);

    expect(collect(completeness($listing->fresh())['missing'])->pluck('key')->all())->toContain('categories');
});

test('missing services reduces completeness', function () {
    $listing = completeListing();
    ListingService::where('listing_id', $listing->id)->delete();

    expect(collect(completeness($listing->fresh())['missing'])->pluck('key')->all())->toContain('services');
});

test('missing contacts reduces completeness', function () {
    $listing = completeListing();
    ListingContact::where('listing_id', $listing->id)->delete();

    expect(collect(completeness($listing->fresh())['missing'])->pluck('key')->all())->toContain('contacts');
});

test('missing media reduces completeness', function () {
    $listing = completeListing();
    ListingImage::where('listing_id', $listing->id)->delete();

    $missing = collect(completeness($listing->fresh())['missing'])->pluck('key')->all();

    expect($missing)->toContain('logo');
    expect($missing)->toContain('cover');
    expect($missing)->toContain('gallery');
});

test('hidden child records do not count as complete', function () {
    $listing = completeListing();

    ListingService::where('listing_id', $listing->id)->update(['hidden_at' => now()]);
    ListingImage::where('listing_id', $listing->id)->update(['hidden_at' => now()]);
    // listing_contacts has no hidden_at; a soft-deleted contact must not count.
    ListingContact::where('listing_id', $listing->id)->delete();

    $missing = collect(completeness($listing->fresh())['missing'])->pluck('key')->all();

    expect($missing)->toContain('services');
    expect($missing)->toContain('contacts');
    expect($missing)->toContain('logo');
});

test('an empty contact value does not count as complete', function () {
    $listing = completeListing();
    ListingContact::where('listing_id', $listing->id)->update(['value' => '   ']);

    expect(collect(completeness($listing->fresh())['missing'])->pluck('key')->all())->toContain('contacts');
});

// ── 3. Locationless professional rule ───────────────────────────────────────

test('a locationless professional is not penalised for lacking a location', function () {
    $listing = completeListing(['type' => ListingType::PROFESSIONAL->value, 'business_id' => null]);
    $listing->update(['location_id' => null]);

    $result = completeness($listing->fresh());

    // Location is excluded entirely for a professional, so 100% is still reachable.
    expect($result['score'])->toBe(100);
    expect(collect($result['missing'])->pluck('key')->all())->not->toContain('location');
});

test('a physical listing is expected to have a location', function () {
    $listing = completeListing(['type' => ListingType::BUSINESS->value]);
    $listing->update(['location_id' => null]);

    $result = completeness($listing->fresh());

    expect(collect($result['missing'])->pluck('key')->all())->toContain('location');
    expect($result['score'])->toBeLessThan(100);
});

// ── 4. Business-less + independence ─────────────────────────────────────────

test('a business-less listing calculates successfully without a business', function () {
    $listing = completeListing(['business_id' => null, 'type' => ListingType::PROFESSIONAL->value]);
    $listing->update(['location_id' => null]);

    $result = completeness($listing->fresh());

    expect($listing->business_id)->toBeNull();
    expect($result)->toHaveKeys(['score', 'tier', 'completed', 'total', 'missing']);
    expect($result['score'])->toBe(100);
});

test('multiple listings of one business calculate independently', function () {
    $business = Business::factory()->create();

    $full = completeListing(['business_id' => $business->id]);
    $sparse = Listing::factory()->create([
        'business_id' => $business->id,
        'type' => ListingType::BUSINESS->value,
        'description' => null,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    $a = completeness($full);
    $b = completeness($sparse->fresh());

    expect($a['score'])->toBe(100);
    expect($b['score'])->toBeLessThan($a['score']);
    expect($a['score'])->not->toBe($b['score']);
});

test('the owner listings index exposes completeness per listing', function () {
    $owner = User::factory()->owner()->create();
    $listing = completeListing(['owner_id' => $owner->id]);

    $props = $this->actingAs($owner)
        ->get('/owner/listings')
        ->assertOk()
        ->viewData('page')['props'];

    $row = collect($props['listings']['data'])->firstWhere('id', $listing->id);

    expect($row['completeness'])->toHaveKeys(['score', 'tier', 'completed', 'total', 'missing']);
    expect($row['completeness']['score'])->toBe(100);
});

// ── 5. Search must not change ───────────────────────────────────────────────

test('completeness is not a search signal', function () {
    $listing = completeListing();
    $payload = $listing->toSearchableArray();

    expect($payload)->not->toHaveKey('completeness');
    expect($payload)->not->toHaveKey('completeness_score');

    $config = file_get_contents(app_path('Console/Commands/ConfigureMeilisearch.php'));
    expect($config)->not->toContain('completeness');
});

// ── 6. Regression guards ────────────────────────────────────────────────────

test('listing reputation is listing-owned and correctly attributed', function () {
    // PHASE 21C-R1 - the Listing page shows the LISTING's own approved reviews.
    // It is no longer gated on the existence of an owning Business, so a
    // Business-less Professional displays its own reputation.
    $profile = file_get_contents(resource_path('js/Pages/Public/ListingProfile.vue'));
    $summary = file_get_contents(resource_path('js/Components/Public/ui/RatingSummary.vue'));

    // PHASE 21C-R1 - the rating shown is the LISTING's own, so it is no longer
    // gated on the existence of a Business, and it is attributed to the Listing.
    expect($profile)->toContain('<RatingSummary');
    expect($profile)->toContain('attribution');
    // The organization section still legitimately keys off business_id;
    // the RATING must not. So assert on the RatingSummary binding itself.
    expect($profile)->not->toContain('<RatingSummary v-if="listing.business_id"');
    expect($profile)->toContain('v-if="listing.reviews_count > 0"');
    expect($summary)->toContain('ownerLabel');
    expect($summary)->not->toContain('Business rating: ');

    // PHASE 21C-R1 - ownership inverted: a Review belongs to a LISTING.
    expect(Schema::hasColumn('reviews', 'listing_id'))->toBeTrue();
    expect(Schema::hasColumn('reviews', 'business_id'))->toBeFalse();
});

test('no representative listing selection exists in completeness or owner listings', function () {
    foreach ([
        app_path('Services/ListingCompletenessService.php'),
        app_path('Http/Controllers/Owner/ListingController.php'),
    ] as $path) {
        // Executable code only: removal docblocks legitimately mention these names.
        $source = preg_replace('#/\\*.*?\\*/#s', '', file_get_contents($path));
        $source = preg_replace('#^\\s*//.*$#m', '', $source);

        foreach (['listings()->first', 'primaryListing', 'defaultListing', 'mainListing', 'representativeListing'] as $pattern) {
            expect($source)->not->toContain($pattern);
        }
    }
});
