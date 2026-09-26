<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    /**
     * Usage: middleware('plan.limit:businesses')
     */
    // public function handle(Request $request, Closure $next, string $resource): Response
    // {
    //     $user = $request->user();
        
    //     if (!$user) {
    //         return redirect()->route('login');
    //     }
        
    //     if (!$user->canAdd($resource)) {
    //         $plan = $user->getCurrentPlan();
    //         $limit = $plan?->getLimit($resource) ?? 0;
            
    //         if ($request->expectsJson()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "You've reached your {$resource} limit ({$limit}). Upgrade your plan to add more.",
    //                 'upgrade_required' => true,
    //                 'resource' => $resource,
    //                 'limit' => $limit,
    //             ], 403);
    //         }
            
    //         return redirect()->route('owner.subscription.index')
    //             ->with('error', "You've reached your {$resource} limit ({$limit}). Please upgrade.");
    //     }
        
    //     return $next($request);
    // }


    public function handle(Request $request, Closure $next, string $resource): Response
{
    $user = $request->user();
    
    if (!$user) {
        return redirect()->route('login');
    }
    
    // ✅ Uses user-based plan (already correct)
    if (!$user->canAdd($resource)) {
        $plan = $user->getCurrentPlan();
        $limit = $plan?->getLimit($resource) ?? 0;
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => "You've reached your {$resource} limit ({$limit}). Upgrade your plan to add more.",
                'upgrade_required' => true,
                'resource' => $resource,
                'limit' => $limit,
            ], 403);
        }
        
        return redirect()->route('owner.subscription.index')
            ->with('error', "You've reached your {$resource} limit ({$limit}). Please upgrade.");
    }
    
    return $next($request);
}
}