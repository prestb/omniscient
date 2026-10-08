<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * PHASE 21C-R1P — REVIEW FACTORY (PREPARATION).
 *
 * Reflects the CURRENT schema, where a Review is BUSINESS-owned
 * (`reviews.business_id` NOT NULL). It deliberately does NOT use `listing_id`.
 *
 * The purpose is to centralise Review fixture construction so the later
 * Listing-owned migration becomes a small, mechanical change:
 *
 *     CURRENT   Review::factory()->for($business)
 *     FUTURE    Review::factory()->for($listing)
 *
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * A valid, publicly-visible Review by a signed-in user is the common case.
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'user_id' => User::factory(),
            'rating' => $this->faker->numberBetween(3, 5),
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'guest_name' => null,
            'guest_email' => null,
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => null,
            'images' => null,
        ];
    }

    /**
     * Keep moderation state internally consistent.
     *
     * An approved Review must carry an approval timestamp; anything that is not
     * approved must not. This prevents a factory-produced "approved" row that no
     * moderation action could actually have produced.
     */
    public function configure(): static
    {
        // afterMaking runs inside both make() and create() (create() builds the
        // model through make() before saving), so this single hook is enough and
        // does not need a second UPDATE after the insert.
        return $this->afterMaking(function (Review $review) {
            if ($review->status === Review::STATUS_APPROVED) {
                $review->approved_at ??= now();
            } else {
                $review->approved_at = null;
            }
        });
    }

    // ── Moderation states ───────────────────────────────────────────────────

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Review::STATUS_PENDING,
            'approved_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => Review::STATUS_REJECTED,
            'approved_at' => null,
        ]);
    }

    // ── Reviewer identity ───────────────────────────────────────────────────

    /**
     * GUEST REVIEWS ARE NOT REPRESENTABLE IN THE CURRENT SCHEMA.
     *
     * `reviews.user_id` is declared NOT NULL
     * (`2026_09_02_043456_create_reviews_table.php`:
     * `$table->foreignId('user_id')->constrained()->cascadeOnDelete()`), and no
     * migration has since made it nullable.
     *
     * `guest_name` and `guest_email` DO exist as nullable columns
     * (`2026_09_02_130455_add_guest_fields_to_reviews_table.php`), so guest
     * support was begun but never completed — the columns are vestigial.
     *
     * A `guest()` state was written here and immediately failed with
     * "Column 'user_id' cannot be null". It was removed rather than left as a
     * state that cannot produce a row. Relaxing `user_id` is a SCHEMA change and
     * belongs to a separately authorised phase; it is deliberately not done here.
     */

    // ── Deterministic values ────────────────────────────────────────────────

    /**
     * A specific rating, for tests that assert on aggregates.
     *
     * Distinct rating values are what expose cross-association bugs: if every
     * fixture shared a rating, a Listing reading its sibling's reviews would
     * still produce the expected number.
     */
    public function rated(int $rating): static
    {
        return $this->state(fn () => ['rating' => $rating]);
    }

    public function approvedBy(User $admin): static
    {
        return $this->state(fn () => [
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);
    }
}
