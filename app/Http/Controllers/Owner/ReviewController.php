<?php

namespace App\Http\Controllers\Owner;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Review;
use App\Models\ReviewReply;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * PHASE 21C-R1 — OWNER REVIEW MANAGEMENT, LISTING-SCOPED.
 *
 * AUTHORIZATION AUTHORITY IS `listing.owner_id`.
 *
 * Deliberately NOT `listing.business.owner_id`. Authorizing through the Business
 * would let a Business owner reach Reviews on a sibling Listing they do not
 * own, and would leave a Business-less Professional with no path at all.
 *
 * A Business-backed owner with several Listings therefore sees each Listing's
 * Reviews separately, which is the point of Listing-owned reputation.
 */
class ReviewController extends Controller
{
    private function authorizeListing(Listing $listing): void
    {
        abort_unless((int) $listing->owner_id === (int) auth()->id(), 403);
    }

    public function index(Listing $listing)
    {
        $this->authorizeListing($listing);

        // Scoped to THIS Listing. Sibling Reviews never appear here.
        $reviews = $listing->reviews()
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Owner/Reviews/Index', [
            'listing' => $listing,
            'business' => $listing->business,
            'reviews' => $reviews,
            'rating' => $listing->approvedReviews()->avg('rating'),
            'reviewsCount' => $listing->approvedReviews()->count(),
            'canReply' => $listing->owner ? $listing->owner->canUse(\App\Support\Entitlement::RESPOND_TO_REVIEWS) : false,
        ]);
    }

    public function show(Listing $listing, Review $review)
    {
        $this->authorizeListing($listing);

        // The Review must belong to the Listing named in the route. Owning the
        // Business that contains both Listings is NOT sufficient.
        abort_unless((int) $review->listing_id === (int) $listing->id, 404);

        $review->load(['user', 'replies.user', 'listing.business']);

        return Inertia::render('Owner/Reviews/Show', [
            'listing' => $listing,
            'business' => $listing->business,
            'review' => $review,
        ]);
    }

    public function reply(Request $request, Listing $listing, Review $review)
    {
        $this->authorizeListing($listing);

        abort_unless((int) $review->listing_id === (int) $listing->id, 404);

        $owner = $listing->owner;

        if (!$owner || !$owner->canUse(\App\Support\Entitlement::RESPOND_TO_REVIEWS)) {
            return back()->with('error', 'Reply feature requires Starter plan or higher.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $reply = ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        if ($review->user_id && $review->user) {
            NotificationHelper::send(
                $review->user,
                'Listing Owner Replied to Your Review',
                'The owner of "' . $listing->name . '" has replied to your review.',
                route('listing.show', $listing->slug),
                ['reply_id' => $reply->id],
                'review_reply'
            );
        }

        return redirect()->back()->with('success', 'Reply added successfully.');
    }
}
