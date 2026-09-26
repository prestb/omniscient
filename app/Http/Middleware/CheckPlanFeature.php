<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Super admin bypass
        if ($user->role === 'super_admin') {
            return $next($request);
        }
        
        // Check via trait
        $canUse = method_exists($user, 'canUse') 
            ? $user->canUse($feature) 
            : false;
        
        if (!$canUse) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This feature requires a higher plan.',
                    'upgrade_required' => true,
                    'feature' => $feature,
                ], 403);
            }
            
            return back()->with('error', 'This feature requires a higher plan. Please upgrade to continue.');
        }
        
        return $next($request);
    }
}