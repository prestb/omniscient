<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\NotificationHelper;
use App\Traits\GuardsHiddenItems;
use Illuminate\Support\Facades\Auth;

class BusinessController extends Controller
{

    use GuardsHiddenItems;
    public function index()
    {
        $user = auth()->user();

        // Get businesses with relationships
        $businesses = Business::with(['locations'])
            ->where('owner_id', $user->id)
            ->get();

        // Get subscription details
        $subscription = $user->activeSubscription;

        // PHASE 11 / WAVE 1D-4 — a Business is an optional ORGANIZATION and is
        // NOT a quota unit. There is no Business allowance: `max_listings`
        // governs LISTINGS (enforced by PlanEnforcementService) and has nothing
        // to do with how many Businesses an account may create.
        $canCreate = true;

        // Get subscription plan name
        $planName = $subscription ? $subscription->plan->name : 'No Plan';
        $planStatus = $subscription ? $subscription->status : null;
        $daysRemaining = $subscription ? $subscription->days_remaining : null;

        return Inertia::render('Owner/Businesses/Index', [
            'businesses' => $businesses,
            'canCreate' => $canCreate,
            'subscription' => [
                'plan_name' => $planName,
                'status' => $planStatus,
                'status_label' => $subscription ? $subscription->status_label : 'No Active Subscription',
                'days_remaining' => $daysRemaining,
                'current_businesses' => $businesses->count(),
                'has_active_subscription' => $subscription !== null,
            ],
        ]);
    }

    public function create()
    {
        $user = auth()->user();

                // ✅ Use the trait method (from HasPlanFeatures) — handles users without subscriptions
        if (method_exists($user, 'canAdd') && !$user->canAdd('listings')) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'You have reached the maximum number of businesses allowed on your plan.');
        }

        // ✅ Fallback: if the trait method doesn't exist, allow users to try
        // (the store() method will enforce limits properly)

        $categories = Category::active()->get();

        return Inertia::render('Owner/Businesses/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

                // Check if user can create a business
        if (!auth()->user()->canAdd('listings')) {
            return back()->with('error', 'You have reached the maximum number of listings.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('businesses', 'name')
                    ->where(fn($q) => $q->where('owner_id', $user->id))
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
        ], [
            'name.unique' => 'You already have a business with this name. Please choose a different name.',
        ]);

        try {
            $business = Business::create([
                'owner_id' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'email' => $validated['email'] ?? null,
                'website' => $validated['website'] ?? null,
                'status' => Business::STATUS_DRAFT,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'name' => 'A business with this name already exists. Please choose a different name.',
                ]);
        }

        // PHASE 11 / WAVE 1D-3 — category assignment is a LISTING operation and
        // belongs to the Listing lifecycle (`/owner/listings/{listing}/edit`).
        //
        // This block used to attach the submitted category to
        // `$business->primaryListing()`. It never executed: the Business had just
        // been created on the line above and nothing creates a Listing for it, so
        // `primaryListing()` always returned null. It is removed rather than
        // replaced — organization creation does not create or select a Listing,
        // and silently doing so would be a product decision, not a refactor.


        if ($business->status === 'submitted') {
            NotificationHelper::sendToAdmins(
                'New Business Submitted',
                "{$business->name} has been submitted for review by {$business->owner->name}",
                route('admin.businesses.show', $business),
                ['business_id' => $business->id],
                'business_submitted'
            );
        }

        if (auth()->user()->role === 'user') {
            return redirect()->route('owner.businesses.edit', $business)
                ->with('success', "\"{$business->name}\" has been created. Add more details to complete your listing.");
        }

        return redirect()->route('owner.businesses.index')
            ->with('success', "\"{$business->name}\" has been created successfully.");

    }

    public function edit(Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }


        // ✅ Server-side lock: hidden businesses are read-only
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }



        // Load all relationships with proper eager loading
        $business->load([
            'locations' => function ($query) {
                $query->with(['country', 'region', 'city', 'area'])->ordered();
            },
            'services',
            'contacts',
            'logo',
            'coverImage',
            'galleryImages',
        ]);

        // Get all active categories
        $categories = Category::active()->ordered()->get();

        // Get locations count
        $locationsCount = $business->locations()->count();

        return Inertia::render('Owner/Businesses/Edit', [
            'business' => $business,
            'categories' => $categories,
            'locationsCount' => $locationsCount,
        ]);
    }

    public function update(Request $request, Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }


        // ✅ Server-side lock
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }


        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('businesses', 'name')
                    ->where(fn($q) => $q->where('owner_id', $business->owner_id))
                    ->whereNull('deleted_at')
                    ->ignore($business->id),
            ],
            'description' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            // PHASE 11 / WAVE 1D-3 — `categories` is no longer a Business input.
            // Categories are Listing-owned (`listing_categories`) and are edited
            // on the Listing that owns the relationship.
        ], [
            'name.unique' => 'You already have a business with this name. Please choose a different name.',
        ]);

        try {
            $business->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'email' => $validated['email'] ?? null,
                'website' => $validated['website'] ?? null,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'name' => 'A business with this name already exists. Please choose a different name.',
                ]);
        }

        // PHASE 11 / WAVE 1D-3 — the category sync that used to run here was
        // REMOVED. It wrote Listing-owned `listing_categories` through
        // Business::primaryListing(), i.e. onto an ARBITRARY Listing, and its
        // `sync()` REPLACED that Listing's entire category set while silently
        // leaving sibling Listings untouched — with no way for the owner to say
        // which Listing they meant.
        //
        // Categories are now edited on the Listing that owns them:
        // /owner/listings/{listing}/edit.

        return redirect()->back()
            ->with('success', 'Business details updated successfully.');
    }

    public function submit(Business $business)
    {
        $user = auth()->user();

        // Verify ownership
        abort_unless($business->owner_id === $user->id, 403);


        // ✅ Server-side lock
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }


        $business->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Notify admins
        \App\Helpers\NotificationHelper::sendToAdmins(
            'New Business Submitted',
            "{$business->name} has been submitted for review by {$user->name}.",
            route('admin.businesses.show', $business),
            ['business_id' => $business->id],
            'business_submitted'
        );

        // ✅ Redirect based on role
        if ($user->role === 'user') {
            return redirect()->route('user.dashboard')
                ->with('success', "\"{$business->name}\" has been submitted for review. We'll notify you once it's approved.");
        }

        return redirect()->route('owner.businesses.index')
            ->with('success', "\"{$business->name}\" has been submitted for review. We'll notify you once it's approved.");
    }

    /**
     * Soft-delete a business and its related data
     */
    public function destroy(Business $business)
    {
        $user = auth()->user();

        abort_unless($business->owner_id === $user->id, 403);

        \Log::info('Business deletion requested', [
            'business_id' => $business->id,
            'business_name' => $business->name,
            'owner_id' => $user->id,
        ]);

        // PHASE 22C hardening: lock the Business and its Location rows before
        // checking references. Listing writes acquire the same Location row
        // lock before assigning location_id, so either the Listing commits
        // first and deletion is blocked, or deletion commits first and the
        // attaching request rechecks the now-deleted Location and is rejected.
        $blocked = \DB::transaction(function () use ($business, $user) {
            $lockedBusiness = Business::query()
                ->whereKey($business->id)
                ->lockForUpdate()
                ->firstOrFail();

            $locationIds = \App\Models\Location::query()
                ->where('business_id', $lockedBusiness->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            $locationsInUse = $locationIds->isEmpty()
                ? collect()
                : \App\Models\Location::query()
                    ->whereIn('id', $locationIds)
                    ->whereHas('listings')
                    ->withCount('listings')
                    ->get();

            if ($locationsInUse->isNotEmpty()) {
                return [
                    'location_count' => $locationsInUse->count(),
                    'listing_count' => (int) $locationsInUse->sum('listings_count'),
                    'location_ids' => $locationsInUse->pluck('id')->all(),
                ];
            }

            // Every existing Location row is locked and confirmed unused before
            // any related records are mutated.
            $lockedBusiness->locations()->delete();
            $lockedBusiness->services()->delete();
            $lockedBusiness->contacts()->delete();
            $lockedBusiness->images()->delete();
            $lockedBusiness->allReviews()->delete();
            \App\Models\Coupon::where('business_id', $lockedBusiness->id)->delete();
            \App\Models\Lead::where('business_id', $lockedBusiness->id)->delete();
            $lockedBusiness->delete();

            return null;
        });

        if ($blocked !== null) {
            \Log::warning('Business deletion blocked: Locations still in use', [
                'business_id' => $business->id,
                'owner_id' => $user->id,
                'locations_in_use' => $blocked['location_ids'],
                'listings_affected' => $blocked['listing_count'],
            ]);

            $message = $blocked['location_count'] === 1
                ? 'This business has 1 location still used by ' . $blocked['listing_count']
                    . ' Listing(s). Detach it from those Listings before deleting the business.'
                : 'This business has ' . $blocked['location_count'] . ' locations still used by '
                    . $blocked['listing_count'] . ' Listing(s). Detach them from those Listings '
                    . 'before deleting the business.';

            return back()->with('error', $message);
        }

        // Notify admins only after the deletion transaction commits.
        \App\Helpers\NotificationHelper::sendToAdmins(
            'Business Deleted',
            "{$user->name} deleted their business '{$business->name}'.",
            route('admin.businesses.index'),
            ['business_id' => $business->id, 'owner_id' => $user->id],
            'business_deleted'
        );

        if ($user->role === 'user') {
            return redirect()->route('user.dashboard')
                ->with('success', "\"{$business->name}\" has been deleted.");
        }

        return redirect()->route('owner.businesses.index')
            ->with('success', "\"{$business->name}\" has been deleted.");
    }

    /**
     * Toggle featured status for a business
     */
    public function toggleFeatured(Business $business)
    {
        $user = auth()->user();

        // Verify ownership
        abort_unless($business->owner_id === $user->id, 403);


        // ✅ Server-side lock
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }


        // Check feature is available
        if (!$business->hasFeaturedListingFeature()) {
            return back()->with('error', 'Featured listing requires Growth plan or higher.');
        }

        // Toggle
        $business->update([
            'is_featured' => !$business->is_featured,
        ]);

        return back()->with('success', $business->is_featured
            ? 'Business is now featured!'
            : 'Business removed from featured.');
    }


    /**
     * Toggle business active/inactive status
     * Owner can hide/show their business without admin involvement
     */
    public function toggleActive(Request $request, Business $business)
    {
        abort_unless($business->owner_id === auth()->id(), 403);


        // ✅ Server-side lock
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }


        if (!$business->canBeToggled()) {
            return back()->with('error', 'This business cannot be toggled.');
        }

        // ✅ Dedupe: same request_id within 5 seconds = reject
        $requestId = $request->input('request_id');
        if ($requestId) {
            $cacheKey = "toggle_business_{$business->id}_{$requestId}";
            if (\Cache::has($cacheKey)) {
                \Log::info('[Toggle] Duplicate request rejected', [
                    'business_id' => $business->id,
                    'request_id' => $requestId,
                ]);
                return back();
            }
            \Cache::put($cacheKey, true, 5); // 5 second dedupe window
        }

        $user = auth()->user();
        $subscription = $user->active_subscription;

        if ($business->status === Business::STATUS_INACTIVE && !$subscription) {
            return back()->with('error', 'You need an active subscription to reactivate this business.');
        }

        // ✅ Refresh to prevent stale state
        $business->refresh();

        $newStatus = $business->status === Business::STATUS_PUBLISHED
            ? Business::STATUS_INACTIVE
            : Business::STATUS_PUBLISHED;

        $business->update([
            'status' => $newStatus,
            'published_at' => $newStatus === Business::STATUS_PUBLISHED
                ? ($business->published_at ?? now())
                : $business->published_at,
        ]);

        \Log::info('[Toggle] Business status changed', [
            'business_id' => $business->id,
            'new_status' => $newStatus,
            'request_id' => $requestId ?? 'none',
        ]);

        $message = $newStatus === Business::STATUS_PUBLISHED
            ? "Business '{$business->name}' is now live!"
            : "Business '{$business->name}' has been hidden from the public.";

        return back()->with('success', $message);
    }
}