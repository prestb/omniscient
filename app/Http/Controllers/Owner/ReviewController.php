<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    /**
     * PHASE 11 / WAVE 1D — owner review management is BUSINESS-SPECIFIC.
     * The Business comes from the route and is authorized against the
     * authenticated owner. There is no representative-Business selection.
     */
    private function authorizeBusiness(Business $business): void
    {
        abort_unless((int) $business->owner_id === (int) auth()->id(), 403);
    }

    public function index(Business $business)
    {
        $this->authorizeBusiness($business);

        $reviews = $business->allReviews()
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Owner/Reviews/Index', [
            'business' => $business,
            'reviews' => $reviews,
            'canReply' => $business->hasReviewResponseFeature(),
        ]);
    }

    public function show(Business $business, Review $review)
    {
        $this->authorizeBusiness($business);

        // The Review's Business relationship remains authoritative.
        abort_unless((int) $review->business_id === (int) $business->id, 403);

        $review->load(['user', 'replies.user', 'business']);

        return Inertia::render('Owner/Reviews/Show', [
            'business' => $business,
            'review' => $review,
        ]);
    }

    public function reply(Request $request, Business $business, Review $review)
    {
        $this->authorizeBusiness($business);

        if (!$business->hasReviewResponseFeature()) {
            return back()->with('error', 'Reply feature requires Starter plan or higher.');
        }

        abort_unless((int) $review->business_id === (int) $business->id, 403);

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $reply = ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        // Notify reviewer
        if ($review->user_id) {
            NotificationHelper::send(
                $review->user,
                'Business Owner Replied to Your Review',
                'The owner of "' . $business->name . '" has replied to your review.',
                route('business.show', $business->slug),
                ['reply_id' => $reply->id],
                'review_reply'
            );
        } else if ($review->guest_email) {
            // For guest reviewers, we could send an email notification
            // Mail::to($review->guest_email)->send(new ReviewReplyMail($reply));
        }

        return redirect()->back()->with('success', 'Reply added successfully.');
    }
}