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
    public function index()
    {
        $user = auth()->user();
        $business = $user->businesses()->first();

        if (!$business) {
            return redirect()->route('owner.dashboard')
                ->with('error', 'You don\'t have a business yet.');
        }

        $reviews = $business->allReviews()
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Owner/Reviews/Index', [
            'business' => $business,
            'reviews' => $reviews,
            'canReply' => $business?->hasReviewResponseFeature() ?? false,
        ]);
    }

    public function show(Review $review)
    {
        $user = auth()->user();
        $business = $user->businesses()->first();

        if (!$business) {
            return redirect()->route('owner.dashboard')
                ->with('error', 'You don\'t have a business yet.');
        }

        // Ensure the review belongs to the owner's business
        if ($review->business_id !== $business->id) {
            abort(403, 'This review does not belong to your business.');
        }

        $review->load(['user', 'replies.user', 'business']);

        return Inertia::render('Owner/Reviews/Show', [
            'business' => $business,
            'review' => $review,
        ]);
    }

    public function reply(Request $request, Review $review)
    {
        $user = auth()->user();
        $business = $user->businesses()->first();

        if (!$business || !$business->hasReviewResponseFeature()) {
        return back()->with('error', 'Reply feature requires Starter plan or higher.');
    }

        if ($review->business_id !== $business->id) {
            abort(403);
        }

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