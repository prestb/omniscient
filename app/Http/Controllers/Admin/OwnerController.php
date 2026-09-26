<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountApprovedMail;
use App\Models\User;
use App\Notifications\AccountApprovedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail; // Add this import
use Inertia\Inertia;
use App\Helpers\NotificationHelper;

class OwnerController extends Controller
{
    /**
     * Display a listing of business owners
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'owner');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $owners = $query->with([
            'businesses' => function ($q) {
                $q->select('id', 'owner_id', 'name', 'status');
            }
        ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Owners/Index', [
            'owners' => $owners,
            'filters' => $request->only(['status', 'search']),
            'statuses' => ['active', 'pending', 'suspended'],
        ]);
    }

    /**
     * Show a specific owner
     */
    public function show(User $owner)
    {
        if ($owner->role !== 'owner') {
            return redirect()->route('admin.owners.index')
                ->with('error', 'This user is not a business owner.');
        }

        $owner->load([
            'businesses' => function ($q) {
                $q->with([
                    'branches',
                    'categories',
                    'subscriptions' => function ($q2) {
                        $q2->with(['plan'])->latest();
                    }
                ]);
            }
        ]);

        return Inertia::render('Admin/Owners/Show', [
            'owner' => $owner,
        ]);
    }

    /**
     * Show the form for editing an owner
     */
    public function edit(User $owner)
    {
        if ($owner->role !== 'owner') {
            return redirect()->route('admin.owners.index')
                ->with('error', 'This user is not a business owner.');
        }

        return Inertia::render('Admin/Owners/Edit', [
            'owner' => $owner,
        ]);
    }

    /**
     * Update the specified owner
     */
    public function update(Request $request, User $owner)
    {
        if ($owner->role !== 'owner') {
            return redirect()->route('admin.owners.index')
                ->with('error', 'This user is not a business owner.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $owner->id,
            'phone' => 'nullable|string|max:50',
            'status' => 'required|in:pending,active,suspended',
        ]);

        $owner->update($validated);

        return redirect()->route('admin.owners.index')
            ->with('success', 'Owner updated successfully.');
    }

    /**
     * Validate (approve) a pending owner
     */
    public function validateOwner(User $owner)
    {
        if ($owner->role !== 'owner') {
            return redirect()->back()->with('error', 'This user is not a business owner.');
        }

        if ($owner->status !== 'pending') {
            return redirect()->back()->with('error', 'This account is not pending approval.');
        }

        // Update owner status
        $owner->update(['status' => 'active']);

        // Send email notification
        try {
            Mail::to($owner->email)->send(new AccountApprovedMail($owner));
            \Log::info('Account approval email sent to: ' . $owner->email);
        } catch (\Exception $e) {
            \Log::error('Failed to send account approval email: ' . $e->getMessage());
        }

        // Send in-app notification to the owner
        try {
            NotificationHelper::send(
                $owner,
                'Account Approved! 🎉',
                'Your account has been approved. You can now start managing your business.',
                route('owner.dashboard'),
                ['user_id' => $owner->id],
                'account_approved'
            );
            \Log::info('In-app notification sent to owner: ' . $owner->email);
        } catch (\Exception $e) {
            \Log::error('Failed to send in-app notification to owner: ' . $e->getMessage());
        }

        // Send notification to admins
        try {
            NotificationHelper::sendToAdmins(
                'New Owner Approved',
                $owner->name . ' has been approved as a business owner.',
                route('admin.owners.show', $owner),
                ['owner_id' => $owner->id],
                'admin_notification'
            );
            \Log::info('Notification sent to admins about new owner approval');
        } catch (\Exception $e) {
            \Log::error('Failed to send notification to admins: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Owner account approved successfully and notifications sent.');
    }

    /**
     * Suspend an owner
     */
    public function suspend(User $owner)
    {
        if ($owner->role !== 'owner') {
            return redirect()->back()->with('error', 'This user is not a business owner.');
        }

        if ($owner->status === 'suspended') {
            return redirect()->back()->with('error', 'This account is already suspended.');
        }

        $owner->update(['status' => 'suspended']);

        // ✅ Do NOT touch business status. Owner status is a login gate;
        //    business visibility is managed independently via the
        //    Business admin actions (Suspend / Publish / Hide). Silently
        //    suspending businesses here could hide published listings
        //    that the admin didn't intend to touch.

        return redirect()->back()->with('success', 'Owner account suspended successfully.');
    }

    /**
     * Activate a suspended owner
     */
    public function activate(User $owner)
    {
        if ($owner->role !== 'owner') {
            return redirect()->back()->with('error', 'This user is not a business owner.');
        }

        if ($owner->status !== 'suspended') {
            return redirect()->back()->with('error', 'This account is not suspended.');
        }

        $owner->update(['status' => 'active']);

        // ✅ Do NOT touch business status. Previously this forced every
        //    business to 'published' — silently publishing drafts,
        //    submitted-but-unreviewed listings, and even rejected ones.
        //    Business status is managed independently.

        return redirect()->back()->with('success', 'Owner account activated successfully.');
    }

    /**
     * Delete an owner
     */
    public function destroy(User $owner)
    {
        if ($owner->role !== 'owner') {
            return redirect()->route('admin.owners.index')
                ->with('error', 'This user is not a business owner.');
        }

        // Check if owner has businesses with active subscriptions
        $hasActiveSubscriptions = $owner->businesses()
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'active');
            })
            ->exists();

        if ($hasActiveSubscriptions) {
            return redirect()->back()
                ->with('error', 'Cannot delete owner with active subscriptions.');
        }

        // Delete owner's businesses first
        $owner->businesses()->delete();

        // Delete the owner
        $owner->delete();

        return redirect()->route('admin.owners.index')
            ->with('success', 'Owner deleted successfully.');
    }

    /**
     * Get owner statistics
     */
    public function statistics()
    {
        $stats = [
            'total_owners' => User::where('role', 'owner')->count(),
            'active_owners' => User::where('role', 'owner')->where('status', 'active')->count(),
            'pending_owners' => User::where('role', 'owner')->where('status', 'pending')->count(),
            'suspended_owners' => User::where('role', 'owner')->where('status', 'suspended')->count(),
            'owners_with_businesses' => User::where('role', 'owner')
                ->whereHas('businesses')
                ->count(),
            'owners_without_businesses' => User::where('role', 'owner')
                ->whereDoesntHave('businesses')
                ->count(),
        ];

        return response()->json($stats);
    }
}