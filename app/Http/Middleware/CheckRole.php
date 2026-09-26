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

        // Super admin has full access
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Admin has full access
        if ($user->role === 'admin') {
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