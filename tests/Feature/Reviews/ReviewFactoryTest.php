<?php

use App\Models\Listing;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 21C-R1P — the Review factory, now against LISTING ownership.
 *
 * These guard the factory itself, so the later Listing-owned migration has a
 * trustworthy starting point. Helper names are deliberately prefixed: Pest loads
 * every test file into one process, and a duplicate global helper fatally aborts
 * the full suite while the focused run still passes.
 */

// ── Basic creation ──────────────────────────────────────────────────────────

test('the review factory produces a valid review', function () {
    $review = Review::factory()->create();

    expect($review->exists)->toBeTrue();
    expect($review->id)->toBeGreaterThan(0);
    // PHASE 21C-R1: a Review is LISTING-owned.
    expect($review->listing_id)->not->toBeNull();
    expect($review->rating)->toBeGreaterThanOrEqual(1);
    expect($review->rating)->toBeLessThanOrEqual(5);
});

test('the review factory sets listing_id through for()', function () {
    $listing = Listing::factory()->create();

    $review = Review::factory()->for($listing)->create();

    expect($review->listing_id)->toBe($listing->id);
    expect($review->listing->id)->toBe($listing->id);
});

// ── Rating ──────────────────────────────────────────────────────────────────

test('the review factory allows a deterministic rating', function () {
    foreach ([1, 2, 3, 4, 5] as $rating) {
        $review = Review::factory()->rated($rating)->create();
        expect($review->fresh()->rating)->toBe($rating);
    }
});

// ── Moderation state consistency ────────────────────────────────────────────

test('an approved review carries an approval timestamp', function () {
    $review = Review::factory()->approved()->create();

    expect($review->status)->toBe(Review::STATUS_APPROVED);
    expect($review->approved_at)->not->toBeNull();
});

test('a pending review has no approval timestamp', function () {
    $review = Review::factory()->pending()->create();

    expect($review->status)->toBe(Review::STATUS_PENDING);
    // A pending review with an approval timestamp is a state no moderation
    // action could produce.
    expect($review->approved_at)->toBeNull();
});

test('a rejected review has no approval timestamp', function () {
    $review = Review::factory()->rejected()->create();

    expect($review->status)->toBe(Review::STATUS_REJECTED);
    expect($review->approved_at)->toBeNull();
});

test('the default factory state is approved', function () {
    // The common case in tests, so the default should not need restating.
    expect(Review::factory()->create()->status)->toBe(Review::STATUS_APPROVED);
});

// ── Reviewer identity ───────────────────────────────────────────────────────

test('a review can be created for an authenticated user', function () {
    $user = User::factory()->create();

    $review = Review::factory()->for($user)->create();

    expect($review->user_id)->toBe($user->id);
});

test('a review cannot be created without a user in the current schema', function () {
    // FINDING (Phase 21C-R1P): guest reviews are NOT supported by the CURRENT
    // schema. `reviews.user_id` is NOT NULL, and no migration makes it nullable.
    // `guest_name` / `guest_email` exist but are vestigial — guest support was
    // begun and never completed.
    //
    // This test documents the real contract so the constraint is not mistaken
    // for a factory defect. Relaxing `user_id` is a schema change and is
    // deliberately out of scope for this preparation phase.
    expect(fn () => Review::factory()->create(['user_id' => null]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

// ── Recording who approved it ───────────────────────────────────────────────

test('a review can record the approving admin', function () {
    $admin = User::factory()->create();

    $review = Review::factory()->approvedBy($admin)->create();

    expect($review->approved_by)->toBe($admin->id);
    expect($review->approved_at)->not->toBeNull();
});

// ── The approved scope is what public reputation will read ──────────────────

test('the approved scope sees only approved reviews', function () {
    $listing = Listing::factory()->create();

    Review::factory()->for($listing)->approved()->count(2)->create();
    Review::factory()->for($listing)->pending()->create();
    Review::factory()->for($listing)->rejected()->create();

    expect(Review::where('listing_id', $listing->id)->approved()->count())->toBe(2);
    expect(Review::where('listing_id', $listing->id)->count())->toBe(4);
});

test('a soft-deleted review is excluded from the approved scope', function () {
    $listing = Listing::factory()->create();
    $review = Review::factory()->for($listing)->approved()->create();

    $review->delete();

    expect(Review::where('listing_id', $listing->id)->approved()->count())->toBe(0);
    // Still present for audit, which is why soft deletes are used.
    expect(Review::withTrashed()->where('listing_id', $listing->id)->count())->toBe(1);
});
