<?php

namespace App\Http\Middleware;

use App\Models\Business;
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
                        // PHASE 11 / WAVE 1D-3 — this middleware runs on
                        // `/business/{slug}`, which is the ORGANIZATION page.
                        //
                        // It previously recorded LISTING analytics against
                        // `$business->primaryListing()` — an arbitrary Listing
                        // standing in for the organization. That is exactly the
                        // Business-as-Listing assumption this wave removes, and a
                        // wrong metric is worse than a temporarily absent one, so
                        // the attribution is gone.
                        //
                        // Organization-level analytics need their own schema and
                        // are deliberately deferred (see
                        // docs/PHASE_11_WAVE_1D_3_PRIMARY_LISTING_AUDIT.md).
                        // Listing analytics are recorded from /listing/{slug}.
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