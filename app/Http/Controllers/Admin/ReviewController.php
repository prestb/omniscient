<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['listing.business', 'user']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('guest_name', 'like', "%{$search}%")
                    ->orWhere('guest_email', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('listing', function ($lq) use ($search) {
                        $lq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $reviews = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Review $review)
    {
        $review->load(['listing.business', 'listing.owner', 'user', 'replies.user']);

        return Inertia::render('Admin/Reviews/Show', [
            'review' => $review,
        ]);
    }

    public function approve(Review $review)
    {
        $review->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Notify reviewer
        $reviewer = $review->user ?? \App\Models\User::where('email', $review->guest_email)->first();

        if ($reviewer) {
            NotificationHelper::send(
                $reviewer,
                'Your Review Has Been Approved',
                'Your review for "' . $review->listing->name . '" has been approved and is now visible.',
                route('listing.show', $review->listing->slug),
                ['review_id' => $review->id],
                'review_approved'
            );
        }

        return redirect()->back()->with('success', 'Review approved successfully.');
    }

    public function reject(Review $review)
    {
        $review->update([
            'status' => 'rejected',
            // ⚠️ These columns are used for both approve and reject. They
            //    represent "last reviewed by admin" — the naming is legacy
            //    (see backlog: rename to reviewed_at / reviewed_by).
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // ✅ Notify reviewer — matching approve() behavior.
        $reviewer = $review->user ?? \App\Models\User::where('email', $review->guest_email)->first();

        if ($reviewer) {
            NotificationHelper::send(
                $reviewer,
                'Your Review Was Not Approved',
                'Your review for "' . $review->listing->name . '" was not approved. You can submit a new review that follows our guidelines.',
                route('listing.show', $review->listing->slug),
                ['review_id' => $review->id],
                'review_rejected'
            );
        }

        return redirect()->back()->with('success', 'Review rejected successfully.');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Review deleted successfully.');
    }
}