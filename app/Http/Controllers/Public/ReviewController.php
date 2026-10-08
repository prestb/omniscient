<?php

namespace App\Http\Controllers\Public;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Review;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * PHASE 21C-R1 — PUBLIC REVIEW SUBMISSION, LISTING-SCOPED.
 *
 * A Review is a review of a LISTING. The Business is organization context
 * reached through the Listing (`$review->listing->business`), never the
 * reviewed entity.
 *
 * Three things changed from the previous Business-scoped implementation:
 *
 *  1. The route target is a Listing, not a Business.
 *  2. The reviewer must be AUTHENTICATED. The old implementation called
 *     User::firstOrCreate() on the submitted email, silently creating an account
 *     for every "guest" review. That was never a real guest architecture — the
 *     schema has always required `user_id` — so it is replaced with an honest
 *     authentication requirement rather than continued pretending.
 *  3. Duplicate prevention is per authenticated user per LISTING, enforced both
 *     here and by a unique index. It is no longer keyed on an email address,
 *     which a reviewer could simply change.
 *
 * SELF-REVIEW PREVENTION IS NEW. The previous system had none at all, so this
 * is not a migration of an existing rule. The authority is `listing.owner_id`,
 * deliberately NOT `listing.business.owner_id`, so a Business-less Professional
 * is governed by exactly the same rule.
 */
class ReviewController extends Controller
{
    private const MAX_IMAGES = 3;

    private const MAX_IMAGE_SIZE_KB = 5120;

    public function store(Request $request, Listing $listing)
    {
        $user = $request->user();

        // Self-review: the Listing owner is the authority. Not the Business owner.
        if ((int) $listing->owner_id === (int) $user->id) {
            throw ValidationException::withMessages([
                'rating' => 'You cannot review your own Listing.',
            ]);
        }

        // One Review per authenticated user per Listing.
        if (Review::where('listing_id', $listing->id)->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'rating' => 'You have already reviewed this Listing.',
            ]);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:2000',
            'images' => 'nullable|array|max:' . self::MAX_IMAGES,
            'images.*' => 'file|mimes:jpeg,jpg,png,webp,gif|max:' . self::MAX_IMAGE_SIZE_KB,
        ]);

        $review = Review::create([
            'listing_id' => $listing->id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'status' => Review::STATUS_PENDING,
        ]);

        $images = $this->handleImageUploads($request, $review);
        if (!empty($images)) {
            $review->update(['images' => $images]);
        }

        // Notify the LISTING owner — resolved through the Listing, not a Business.
        if ($listing->owner) {
            NotificationHelper::send(
                $listing->owner,
                'New Review Received',
                'Your Listing "' . $listing->name . '" has received a new review from ' . $user->name . '.',
                route('owner.listings.reviews.index', $listing),
                ['review_id' => $review->id],
                'review_received'
            );
        }

        NotificationHelper::sendToAdmins(
            'New Review Pending Approval',
            $listing->name . ' has received a new review from ' . $user->name . '.',
            route('admin.reviews.index'),
            ['review_id' => $review->id],
            'admin_notification'
        );

        return redirect()->back()->with(
            'success',
            'Thank you for your review! It has been submitted and is pending approval before being published.'
        );
    }

    public function index(Listing $listing)
    {
        $reviews = $listing->approvedReviews()
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate(10);

        // PHASE 21D - the Listing's OWN approved reputation. Previously the
        // page read these off `business`, which is NULL for an independent
        // Professional Listing.
        return Inertia::render('Public/Reviews/Index', [
            'listing' => $listing->only(['id', 'name', 'slug', 'type', 'business_id']),
            'business' => $listing->business?->only(['id', 'name', 'slug']),
            'rating' => round((float) $listing->approvedReviews()->avg('rating'), 1),
            'reviewsCount' => (int) $listing->approvedReviews()->count(),
            'reviews' => $reviews,
        ]);
    }

    /**
     * Handle image uploads for a review. Returns relative storage paths.
     */
    private function handleImageUploads(Request $request, Review $review): array
    {
        if (!$request->hasFile('images')) {
            return [];
        }

        $paths = [];
        $folder = 'reviews/' . $review->id;

        foreach ($request->file('images') as $file) {
            if (!$file->isValid()) {
                continue;
            }

            try {
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($folder, $filename, 'public');

                if ($path) {
                    try {
                        $processedPath = app(ImageService::class)->process($path);
                        if ($processedPath) {
                            $path = $processedPath;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Review image processing failed', [
                            'path' => $path,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $paths[] = $path;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to store review image', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $paths;
    }

    private function deleteImageFiles(array $paths): void
    {
        $service = app(ImageService::class);

        foreach ($paths as $path) {
            try {
                $service->delete($path);
            } catch (\Throwable $e) {
                Log::warning('Failed to delete review image', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
