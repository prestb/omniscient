<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditHelper;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\NotificationHelper;
use Inertia\Inertia;

class BusinessController extends Controller
{

    public function index(Request $request)
    {
        // ✅ Base query — includes soft-deleted if requested
        $query = Business::query()->with(['owner:id,name,email', 'categories']);

        if ($request->boolean('show_deleted')) {
            $query->onlyTrashed();
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($oq) use ($search) {
                        $oq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status') && !$request->boolean('show_deleted')) {
            $query->where('status', $request->status);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // ✅ Featured filter
        if ($request->filled('featured')) {
            $query->where('is_featured', $request->featured === 'true');
        }

        $businesses = $query->latest()->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => Business::count(),
            'published' => Business::where('status', 'published')->count(),
            'pending' => Business::where('status', 'submitted')->count(),
            'deleted' => Business::onlyTrashed()->count(), // ✅ NEW
        ];

        return Inertia::render('Admin/Businesses/Index', [
            'businesses' => $businesses,
            'categories' => Category::active()->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'category_id', 'featured', 'show_deleted']),
            'stats' => $stats,
            'statuses' => ['draft', 'submitted', 'approved', 'published', 'inactive', 'rejected', 'suspended'],
        ]);
    }

    /**
     * Restore a soft-deleted business and all related records
     */
    public function restore($id)
    {
        try {
            $business = Business::withTrashed()->findOrFail($id);

            if (!$business->trashed()) {
                return back()->with('error', 'This business is not deleted.');
            }

            \DB::beginTransaction();

            // ✅ Restore the business itself
            $business->restore();

            // ✅ Restore all related soft-deleted records
            $business->branches()->withTrashed()->restore();
            $business->services()->withTrashed()->restore();
            $business->contacts()->withTrashed()->restore();  // ✅ Now works
            $business->images()->withTrashed()->restore();
            $business->allReviews()->withTrashed()->restore();

            \App\Models\Coupon::withTrashed()
                ->where('business_id', $business->id)
                ->restore();

            \App\Models\Lead::withTrashed()
                ->where('business_id', $business->id)
                ->restore();

            \DB::commit();

            // ✅ Notify owner
            if ($business->owner) {
                \App\Helpers\NotificationHelper::send(
                    $business->owner,
                    'Business Restored',
                    "Your business '{$business->name}' has been restored by an administrator.",
                    route('owner.businesses.edit', $business),
                    ['business_id' => $business->id],
                    'business_restored'
                );
            }

            \Log::info('Business restored by admin', [
                'business_id' => $business->id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', "Business '{$business->name}' has been restored successfully.");

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Business restore failed', [
                'business_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to restore business: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete a soft-deleted business (cannot be undone)
     */
    public function forceDelete($id)
    {
        try {
            $business = Business::withTrashed()->findOrFail($id);

            $businessName = $business->name;
            $businessId = $business->id;
            $ownerId = $business->owner_id;

            \DB::beginTransaction();

            // ✅ Permanently delete all related records
            $business->branches()->withTrashed()->forceDelete();
            $business->services()->withTrashed()->forceDelete();
            $business->contacts()->withTrashed()->forceDelete();  // ✅ Now works
            $business->images()->withTrashed()->forceDelete();
            $business->allReviews()->withTrashed()->forceDelete();

            \App\Models\Coupon::withTrashed()
                ->where('business_id', $business->id)
                ->forceDelete();

            \App\Models\Lead::withTrashed()
                ->where('business_id', $business->id)
                ->forceDelete();

            // ✅ Delete image files from storage
            $images = \App\Models\BusinessImage::withTrashed()
                ->where('business_id', $business->id)
                ->pluck('path');

            foreach ($images as $path) {
                if (\Storage::disk('public')->exists($path)) {
                    \Storage::disk('public')->delete($path);
                }
            }

            // ✅ Force-delete the business
            $business->forceDelete();

            \DB::commit();

            \Log::warning('Business permanently deleted by admin', [
                'business_id' => $businessId,
                'business_name' => $businessName,
                'owner_id' => $ownerId,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', "Business '{$businessName}' has been permanently deleted.");

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Business force-delete failed', [
                'business_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to permanently delete business: ' . $e->getMessage());
        }
    }

    public function show(Business $business)
    {
        // ✅ Validation-rich eager load — the admin needs enough context
        //    to genuinely approve or reject a submitted business.
        $business->load([
            'owner' => fn($q) => $q->withCount('businesses'),
            'categories',
            'primaryBranch',
            'branches' => fn($q) => $q->with(['country', 'region', 'city', 'area'])->ordered(),
            'logo',
            'coverImage',
            'galleryImages',
        ]);

        return Inertia::render('Admin/Businesses/Show', [
            'business' => $business,
        ]);
    }

    public function approve(Business $business)
    {
        $business->update([
            'status' => Business::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // ✅ Notify owner: "Business approved! Get a subscription to publish."
        $owner = $business->owner;
        if ($owner) {
            // Check if owner already has an active subscription
            $hasActiveSubscription = $owner->active_subscription !== null;

            $message = $hasActiveSubscription
                ? "Your business '{$business->name}' has been approved! It will be published shortly."
                : "Your business '{$business->name}' has been approved! Get a subscription to make it live.";

            $actionUrl = $hasActiveSubscription
                ? route('owner.businesses.index')
                : route('owner.subscription.index');

            \App\Helpers\NotificationHelper::send(
                $owner,
                'Business Approved! 🎉',
                $message,
                $actionUrl,
                ['business_id' => $business->id],
                'business_approved'
            );
        }

        // ✅ Auto-publish if owner already has an active subscription that allows more businesses
        if ($owner && $owner->activeSubscription()) {
            // This is a multi-business scenario — auto-publish
            $this->attemptAutoPublish($business);
        }

        return back()->with('success', 'Business approved successfully.');
    }

    public function publish(Business $business)
    {
        $business->update([
            'status' => Business::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        // ✅ Auto-promote user → owner (Case 1: gradual upgrade)
        $owner = $business->owner;
        if ($owner && $owner->role === \App\Models\User::ROLE_USER) {
            $owner->update([
                'role' => \App\Models\User::ROLE_OWNER,
                'status' => \App\Models\User::STATUS_ACTIVE,
            ]);

            \Log::info('User promoted to owner on publish', [
                'user_id' => $owner->id,
                'business_id' => $business->id,
            ]);

            \App\Helpers\NotificationHelper::send(
                $owner,
                'Welcome as a Business Owner! 🎉',
                "Your business '{$business->name}' is now live and you have full owner access. Welcome aboard!",
                route('owner.dashboard'),
                ['business_id' => $business->id],
                'account_promoted_to_owner'
            );
        } else if ($owner) {
            // Already an owner — just notify
            \App\Helpers\NotificationHelper::send(
                $owner,
                'Business Published! 🚀',
                "Your business '{$business->name}' is now live!",
                route('business.show', $business->slug),
                ['business_id' => $business->id],
                'business_published'
            );
        }

        return back()->with('success', 'Business published successfully.');
    }


    /**
     * Attempt to auto-publish a business if the owner has an active subscription
     */
    // protected function attemptAutoPublish(Business $business): void
// {
//     $owner = $business->owner;

    //     if (!$owner) {
//         return;
//     }

    //     // Check if user can have another business on current plan
//     $activeSubscription = $owner->active_subscription;

    //     if (!$activeSubscription) {
//         return;
//     }

    //     // Get business count (excluding current business)
//     $otherBusinesses = $owner->businesses()
//         ->where('id', '!=', $business->id)
//         ->whereIn('status', ['published', 'approved'])
//         ->count();

    //     $maxBusinesses = $activeSubscription->plan->max_businesses ?? 1;
//     $isUnlimited = $maxBusinesses === -1 || $maxBusinesses === 999;

    //     // If plan allows more and we're within limit, auto-publish
//     if ($isUnlimited || ($otherBusinesses + 1) <= $maxBusinesses) {
//         $business->update([
//             'status' => Business::STATUS_PUBLISHED,
//             'published_at' => now(),
//         ]);

    //         \Log::info('Business auto-published (multi-business)', [
//             'business_id' => $business->id,
//             'owner_id' => $owner->id,
//             'plan' => $activeSubscription->plan->name,
//         ]);

    //         \App\Helpers\NotificationHelper::send(
//             $owner,
//             'Business Published! 🚀',
//             "Your business '{$business->name}' is now live!",
//             route('business.show', $business->slug),
//             ['business_id' => $business->id],
//             'business_published'
//         );
//     }
// }

    protected function attemptAutoPublish(Business $business): void
    {
        $owner = $business->owner;

        if (!$owner) {
            return;
        }

        // ✅ Owner's subscription (not business's)
        $activeSubscription = $owner->active_subscription;

        if (!$activeSubscription) {
            return;
        }

        // Count other businesses for this OWNER
        $otherBusinesses = $owner->businesses()
            ->where('id', '!=', $business->id)
            ->whereIn('status', ['published', 'approved'])
            ->count();

        $maxBusinesses = $activeSubscription->plan->max_businesses ?? 1;
        $isUnlimited = $maxBusinesses === -1 || $maxBusinesses === 999;

        if ($isUnlimited || ($otherBusinesses + 1) <= $maxBusinesses) {
            $business->update([
                'status' => Business::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            \App\Helpers\NotificationHelper::send(
                $owner,
                'Business Published! 🚀',
                "Your business '{$business->name}' is now live!",
                route('business.show', $business->slug),
                ['business_id' => $business->id],
                'business_published'
            );
        }
    }
    public function reject(Request $request, Business $business)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);


        $business->update([
            'status' => Business::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $reason = $validated['reason'] ?? null;

        NotificationHelper::send(
            $business->owner,
            'Business Rejected ❌',
            $reason
            ? "Your business '{$business->name}' was rejected. Reason: {$reason}"
            : "Your business '{$business->name}' was rejected.",
            route('owner.businesses.edit', $business),
            ['business_id' => $business->id, 'reason' => $reason],
            'business_rejected'
        );


        return redirect()->back()->with('success', 'Business rejected successfully.');
    }

    public function suspend(Business $business)
    {
        $oldStatus = $business->status;

        $business->update(['status' => Business::STATUS_SUSPENDED]);

        AuditHelper::logBusiness(
            'suspend',
            $business,
            ['status' => $oldStatus],
            ['status' => Business::STATUS_SUSPENDED]
        );

        return redirect()->back()->with('success', 'Business suspended successfully.');
    }

    public function destroy(Business $business)
    {
        $business->delete();

        return redirect()->route('admin.businesses.index')
            ->with('success', 'Business deleted successfully.');
    }

    public function activate(Business $business)
    {
        $oldStatus = $business->status;

        $business->update(['status' => Business::STATUS_PUBLISHED]);

        AuditHelper::logBusiness(
            'activate',
            $business,
            ['status' => $oldStatus],
            ['status' => Business::STATUS_PUBLISHED]
        );

        // Notify owner
        NotificationHelper::send(
            $business->owner,
            'Business Activated! 🔄',
            'Your business "' . $business->name . '" has been reactivated.',
            route('owner.businesses.edit', $business),
            ['business_id' => $business->id],
            'business_approved'
        );

        // Notify admins
        NotificationHelper::sendToAdmins(
            'Business Activated',
            $business->name . ' has been reactivated by ' . auth()->user()->name . '.',
            route('admin.businesses.show', $business),
            ['business_id' => $business->id],
            'admin_notification'
        );

        return redirect()->back()->with('success', 'Business activated successfully.');
    }
}