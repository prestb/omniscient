<?php
// app/Http/Middleware/BlockImpersonatedAdmins.php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BlockImpersonatedAdmins
{
    /**
     * Routes that an impersonated user is NOT allowed to hit.
     * These are dangerous admin routes — if hit while impersonating,
     * we auto-stop the impersonation to protect the admin account.
     */
    private const BLOCKED_ROUTE_PREFIXES = [
        'admin.',
    ];

    /**
     * Routes that are ALWAYS allowed, even if impersonating.
     * These are the escape hatches: the stop route needs to run its
     * own controller logic (which handles flash + redirect properly).
     */
    private const ALLOWED_ROUTES = [
        'admin.impersonate.stop',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        // Not impersonating — nothing to check
        if (!$impersonatorId) {
            return $next($request);
        }

        // Get the current route name
        $routeName = $request->route()?->getName();

        // Always-allow escape routes (e.g. the stop endpoint)
        if ($routeName && in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        // If the route is blocked, force-stop impersonation
        if ($routeName && $this->isBlocked($routeName)) {
            // Restore admin session
            $admin = User::find($impersonatorId);

            $request->session()->forget('impersonator_id');
            $request->session()->forget('impersonator_return_url');

            if ($admin && $admin->isSuperAdmin()) {
                Auth::login($admin);
                return redirect()
                    ->route('admin.users.index')
                    ->with('warning', 'Impersonation was auto-stopped because you tried to access an admin route.');
            }

            // Fallback — safest possible: log out
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Impersonation session ended. Please log in again.');
        }

        return $next($request);
    }

    private function isBlocked(string $routeName): bool
    {
        foreach (self::BLOCKED_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }
        return false;
    }
}