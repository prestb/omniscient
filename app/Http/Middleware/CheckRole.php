<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super admin has full access.
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // PHASE 27 — AUTHORIZATION BOUNDARY.
        //
        // `admin` used to short-circuit BEFORE the role check, so it ALSO
        // satisfied `role:super_admin` and the nested super-admin route group was
        // not actually protected: a plain admin could reach
        // `/admin/super-dashboard`.
        //
        // An admin keeps full access EXCEPT where a route is restricted to super
        // admins — i.e. it declares `super_admin` and does NOT also accept
        // `admin`. Owner-side contracts are unaffected: `role:owner` and
        // `role:user,owner` never mention super_admin, so admin access to them is
        // unchanged, as is the graceful redirect for owners and users.
        if ($user->role === 'admin') {
            $superAdminOnly = in_array('super_admin', $roles, true)
                && ! in_array('admin', $roles, true);

            if ($superAdminOnly) {
                abort(403, 'Unauthorized access.');
            }

            return $next($request);
        }

        // Strict role check
        if (!in_array($user->role, $roles)) {
            // Graceful redirect based on role
            if ($user->role === 'owner') {
                return redirect()->route('owner.dashboard');
            }
            if ($user->role === 'user') {
                return redirect()->route('user.dashboard');
            }
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}