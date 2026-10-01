<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    /**
     * PHASE 11 / WAVE 1D-3 — the click vocabulary owned by ListingAnalytics.
     * Reused verbatim; no event type was added or renamed.
     */
    private const VALID_CLICK_TYPES = ['phone', 'whatsapp', 'website', 'direction', 'social'];

    public function trackView($businessId)
    {
        try {
            Log::info('Tracking view for business: ' . $businessId);
            $business = Business::findOrFail($businessId);

            // PHASE 11 / WAVE 1B — analytics are listing-owned. Attribute to the
            // organization's primary listing.
            $listing = $business->primaryListing();
            if (!$listing) {
                return response()->json(['success' => false, 'error' => 'No listing for business'], 422);
            }

            ListingAnalytics::trackView($listing->id);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error tracking view: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function trackClick(Request $request, $businessId, $type)
    {
        try {
            Log::info('Tracking click for business: ' . $businessId . ' type: ' . $type);
            
            $business = Business::findOrFail($businessId);
            
            // Validate click type
            $validTypes = ['phone', 'whatsapp', 'website', 'direction', 'social'];
            if (!in_array($type, $validTypes)) {
                Log::warning('Invalid click type: ' . $type);
                return response()->json(['error' => 'Invalid click type'], 400);
            }

            // PHASE 11 / WAVE 1B — analytics are listing-owned.
            $listing = $business->primaryListing();
            if (!$listing) {
                return response()->json(['success' => false, 'error' => 'No listing for business'], 422);
            }

            $result = ListingAnalytics::trackClick($listing->id, $type);
            
            if ($result) {
                Log::info('Click tracked successfully for: ' . $businessId . ' type: ' . $type);
                return response()->json(['success' => true]);
            } else {
                return response()->json(['error' => 'Failed to track click'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error tracking click: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PHASE 11 / WAVE 1D-3 — CANONICAL LISTING-SCOPED TRACKING (PATH A)
    //
    // The Listing is supplied EXPLICITLY by the route and is never resolved
    // through a Business. Listing A can therefore never be credited with
    // Listing B's activity, which is what the Business -> primaryListing()
    // bridge below does.
    //
    // These endpoints are public event ingestion, matching the existing
    // /analytics/track-* convention. No owner authentication and no policy:
    // a public Listing page records its own public interactions.
    // =========================================================================

    public function trackListingView(Request $request, Listing $listing)
    {
        try {
            ListingAnalytics::trackView($listing->id);

            // Unique visitor — reuse the application's existing session
            // mechanism. No new visitor-identification model is introduced.
            $sessionKey = 'visited_listing_' . $listing->id;

            if (!$request->session()->has($sessionKey)) {
                ListingAnalytics::trackUniqueVisitor($listing->id);
                $request->session()->put($sessionKey, true);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error tracking listing view: ' . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function trackListingClick(Request $request, Listing $listing, string $type)
    {
        try {
            // Same vocabulary as the existing endpoint. Nothing renamed.
            if (!in_array($type, self::VALID_CLICK_TYPES, true)) {
                return response()->json(['error' => 'Invalid click type'], 400);
            }

            $result = ListingAnalytics::trackClick($listing->id, $type);

            return $result
                ? response()->json(['success' => true])
                : response()->json(['error' => 'Failed to track click'], 500);
        } catch (\Exception $e) {
            Log::error('Error tracking listing click: ' . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}