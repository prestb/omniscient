<?php
// app/Http/Controllers/Admin/ImpersonationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function start(Request $request, User $user)
    {
        $admin = Auth::user();

        // Only super admins can impersonate
        if (!$admin || !$admin->isSuperAdmin()) {
            abort(403, 'Only super admins can impersonate users.');
        }

        // Already impersonating — refuse to stack
        if ($request->session()->has('impersonator_id')) {
            return back()->with('error', 'You are already impersonating someone. Stop the current session first.');
        }

        // Cannot impersonate another admin or super admin
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return back()->with('error', 'You cannot impersonate admins.');
        }

        // Cannot impersonate yourself
        if ($user->id === $admin->id) {
            return back()->with('error', 'You cannot impersonate yourself.');
        }

        // Only active users
        if ($user->status !== User::STATUS_ACTIVE) {
            return back()->with('error', 'Only active users can be impersonated.');
        }

        // Stash the admin's identity + intended return URL
        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('impersonator_return_url', route('admin.users.index'));

        // Log in as the target user
        Auth::login($user);

        return redirect()
            ->route('dashboard')
            ->with('success', "You are now viewing the platform as {$user->name}.");
    }

    /**
     * Stop impersonating and return to the original admin.
     */
    public function stop(Request $request)
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        $returnUrl = $request->session()->pull('impersonator_return_url', route('admin.users.index'));

        if (!$impersonatorId) {
            return redirect()->route('dashboard')
                ->with('error', 'No active impersonation session.');
        }

        $admin = User::find($impersonatorId);

        if (!$admin || !$admin->isSuperAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'The original admin account could not be restored.');
        }

        Auth::login($admin);

        return redirect($returnUrl)
            ->with('success', "Welcome back, {$admin->name}.");
    }
}