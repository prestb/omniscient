<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Review;
use App\Models\User;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ReviewController extends Controller
{
    /**
     * Max images per review.
     */
    private const MAX_IMAGES = 3;

    /**
     * Max size per image in kilobytes (5 MB).
     */
    private const MAX_IMAGE_SIZE_KB = 5120;

    public function store(Request $request, Business $business)
    {
        Log::info('Review submission started', [
            'business_id' => $business->id,
            'request_has_files' => $request->hasFile('images'),
            'image_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
        ]);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'content' => 'required|string|max:2000',
                'images' => 'nullable|array|max:' . self::MAX_IMAGES,
                'images.*' => 'file|mimes:jpeg,jpg,png,webp,gif|max:' . self::MAX_IMAGE_SIZE_KB,
            ]);

            Log::info('Validation passed');

            // Check if this email has already reviewed this business
            $existingReview = Review::where('business_id', $business->id)
                ->where('guest_email', $validated['email'])
                ->first();

            if ($existingReview) {
                // Handle image uploads (replace old images)
                $newImages = $this->handleImageUploads($request, $existingReview);

                // Delete old images from storage (only if new ones provided)
                if (!empty($newImages)) {
                    $this->deleteImageFiles($existingReview->images ?? []);
                }

                $existingReview->update([
                    'rating' => $validated['rating'],
                    'title' => $validated['title'] ?? null,
                    'content' => $validated['content'],
                    'status' => Review::STATUS_PENDING,
                    'guest_name' => $validated['name'],
                    'images' => !empty($newImages) ? $newImages : $existingReview->images,
                ]);

                Log::info('Review updated', ['review_id' => $existingReview->id]);

                return redirect()->back()->with('info', 'You have already reviewed this business. Your review has been updated and is pending approval.');
            }

            // Find or create user by email
            $user = User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['name'],
                    'password' => Hash::make(uniqid()),
                    'role' => 'owner',
                    'status' => 'pending',
                ]
            );

            Log::info('User found/created', ['user_id' => $user->id]);

            $review = Review::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'content' => $validated['content'],
                'guest_name' => $validated['name'],
                'guest_email' => $validated['email'],
                'status' => Review::STATUS_PENDING,
            ]);

            // Handle image uploads
            $images = $this->handleImageUploads($request, $review);
            if (!empty($images)) {
                $review->update(['images' => $images]);
            }

            Log::info('Review created', ['review_id' => $review->id, 'images_count' => count($images)]);

            // Notify business owner
            if ($business->owner) {
                NotificationHelper::send(
                    $business->owner,
                    'New Review Received',
                    'Your business "' . $business->name . '" has received a new review from ' . $validated['name'] . '.',
                    route('owner.reviews.index'),
                    ['review_id' => $review->id],
                    'review_received'
                );
            }

            // Notify admins
            NotificationHelper::sendToAdmins(
                'New Review Pending Approval',
                $business->name . ' has received a new review from ' . $validated['name'] . '.',
                route('admin.reviews.index'),
                ['review_id' => $review->id],
                'admin_notification'
            );

            return redirect()->back()->with('success', 'Thank you for your review! It has been submitted and is pending approval before being published.');

        } catch (\Exception $e) {
            Log::error('Review submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to submit review. Please try again.');
        }
    }

    /**
     * Handle image uploads for a review.
     * Returns an array of relative storage paths.
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
                // Generate a unique filename
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

                // Store on the public disk
                $path = $file->storeAs($folder, $filename, 'public');

                if ($path) {
                    // ✅ Process image — downscale + variants
                    try {
                        $processedPath = app(\App\Services\ImageService::class)->process($path);
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

    /**
     * Delete physical image files from storage.
     */
    private function deleteImageFiles(array $paths): void
    {
        $service = app(\App\Services\ImageService::class);

        foreach ($paths as $path) {
            try {
                // ✅ Delete original + every variant
                $service->delete($path);
            } catch (\Throwable $e) {
                Log::warning('Failed to delete review image', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function index(Business $business)
    {
        $reviews = $business->reviews()
            ->approved()
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate(10);

        \Log::info('Review ratings:', $reviews->pluck('rating')->toArray());

        return Inertia::render('Public/Reviews/Index', [
            'business' => $business,
            'reviews' => $reviews,
        ]);
    }
}