<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\ListingAnalytics;
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
                        // PHASE 11 / WAVE 1B — analytics are listing-owned.
                        $listing = $business->primaryListing();

                        // PHASE 11 / WAVE 1B — analytics are listing-owned and
                        // `listing_id` is NOT NULL. Without a Listing there is no
                        // valid owner, so skip tracking entirely rather than
                        // fabricating one from the Business id.
                        if (!$listing) {
                            return $response;
                        }

                        $listingId = $listing->id;

                        // Track view
                        ListingAnalytics::trackView($listingId);
                        
                        // Track unique visitor (using session)
                        $sessionKey = 'visited_business_' . $business->id;
                        if (!$request->session()->has($sessionKey)) {
                            ListingAnalytics::trackUniqueVisitor($listingId);
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