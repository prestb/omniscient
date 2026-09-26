<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\BusinessAnalytics;
use Closure;
use Illuminate\Http\Request;

class TrackBusinessView
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Track business profile views
        if ($request->route() && $request->route()->getName() === 'business.show') {
            $slug = $request->route('slug');
            
            if ($slug) {
                try {
                    // Find the business by slug
                    $business = Business::where('slug', $slug)->first();
                    
                    if ($business) {
                        // Track view
                        BusinessAnalytics::trackView($business->id);
                        
                        // Track unique visitor (using session)
                        $sessionKey = 'visited_business_' . $business->id;
                        if (!$request->session()->has($sessionKey)) {
                            BusinessAnalytics::trackUniqueVisitor($business->id);
                            $request->session()->put($sessionKey, true);
                        }
                    }
                } catch (\Exception $e) {
                    // Log error but don't break the page
                    \Log::error('Failed to track business view: ' . $e->getMessage());
                }
            }
        }

        return $response;
    }
}