<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{

    /**
     * Display a listing of all users
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->role) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Search by name, email, or phone
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->withCount(['businesses'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['role', 'status', 'search']),
            'roles' => ['admin', 'super_admin', 'owner'],
            'statuses' => ['pending', 'active', 'suspended'],
        ]);
    }

    /**
     * Show a specific user
     */
    public function show(User $user)
    {
        $user->load([
                        'businesses' => function ($q) {
                $q->with([
                    'locations',
                    'categories',
                    'subscriptions' => function ($q2) {
                        $q2->with(['plan'])->latest();
                    }
                ]);
            }
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing a user
     */
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:admin,super_admin,owner',
            'status' => 'required|in:pending,active,suspended',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete a user
     */
    public function destroy(User $user)
    {
        // Prevent deleting the last super admin
        if ($user->role === 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return redirect()->back()
                    ->with('error', 'Cannot delete the last super admin.');
            }
        }

        // Check if user has businesses with active subscriptions
        $hasActiveSubscriptions = $user->businesses()
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'active');
            })
            ->exists();

        if ($hasActiveSubscriptions) {
            return redirect()->back()
                ->with('error', 'Cannot delete user with active subscriptions.');
        }

        // Delete user's businesses first
        $user->businesses()->delete();

        // Delete the user
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user status (activate/suspend)
     */
    public function toggleStatus(User $user)
    {
        // Prevent toggling the last super admin
        if ($user->role === 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1 && $user->status === 'active') {
                return redirect()->back()
                    ->with('error', 'Cannot suspend the last super admin.');
            }
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        // ✅ Do NOT touch business status. User status is a login gate;
        //    business visibility is managed independently via the
        //    Business admin actions (Suspend / Publish). Silently
        //    publishing draft businesses on user reactivation was a
        //    data-integrity bug.

        return redirect()->back()
            ->with('success', "User {$newStatus} successfully.");
    }

    /**
     * Get user statistics
     */
    public function statistics()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'pending_users' => User::where('status', 'pending')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'admins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
            'owners' => User::where('role', 'owner')->count(),
            'super_admins' => User::where('role', 'super_admin')->count(),
        ];

        return response()->json($stats);
    }
}