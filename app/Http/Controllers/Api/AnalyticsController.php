<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    /**
     * WAVE 28C - VIEW DEDUPLICATION WINDOW, IN MINUTES.
     *
     * DEFINITION OF `views`: the number of ACCEPTED Listing-view events, where an
     * event is accepted at most ONCE per Listing per browser session per this
     * window. Reloading the page, or any repeated request inside the window, does
     * not add another view. A genuine later visit - after the window - counts
     * again, so the metric is repeatable rather than capped.
     *
     * Deduplication is enforced SERVER-side in the cache, keyed by the session
     * id. No client-supplied flag is trusted as the mechanism, and no IP address
     * or device fingerprint is stored.
     */
    private const VIEW_DEDUP_WINDOW_MINUTES = 30;

    /**
     * WAVE 28C - BEST-EFFORT AUTOMATED-CLIENT TOKENS.
     *
     * User-Agent strings are spoofable, so this is deliberately a blunt deny-list
     * and NOT a guarantee: a crawler presenting a browser User-Agent is still
     * counted. It keeps the obvious majority of crawlers, monitors and
     * command-line clients from inflating the metric without adding a heavyweight
     * bot-detection dependency.
     */
    private const AUTOMATED_TOKENS = [
        'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'preview',
        'curl', 'wget', 'python-requests', 'python-urllib', 'go-http-client',
        'headlesschrome', 'phantomjs', 'lighthouse', 'pingdom', 'uptimerobot',
        'semrush', 'ahrefs', 'bytespider', 'gptbot', 'claudebot', 'ccbot',
    ];

    /** True when the request looks automated. An absent User-Agent is a real visit. */
    private function isAutomated(Request $request): bool
    {
        $agent = strtolower((string) $request->userAgent());

        if ($agent === '') {
            return false;
        }

        foreach (self::AUTOMATED_TOKENS as $token) {
            if (str_contains($agent, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Server-side deduplication: true when this session already counted this
     * Listing inside the window. Cache::add() is atomic, so two concurrent
     * requests cannot both win.
     */
    private function alreadyCounted(Request $request, Listing $listing, string $metric): bool
    {
        $key = 'analytics:' . $metric . ':' . $listing->id . ':' . $request->session()->getId();

        return ! \Illuminate\Support\Facades\Cache::add(
            $key,
            1,
            now()->addMinutes(self::VIEW_DEDUP_WINDOW_MINUTES)
        );
    }

    // PHASE 11 / WAVE 1D-3 — the Business-keyed trackView($businessId) and
    // trackClick($request, $businessId, $type) writers were DELETED. They existed
    // only to resolve an arbitrary Listing through Business::primaryListing() and
    // attribute Listing-owned analytics to it. No compatibility wrapper remains.

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
        // WAVE 28B — INGESTION VISIBILITY.
        //
        // The route binds {listing} by id, so a POST could previously increment a
        // draft, hidden or suspended Listing that the public cannot even reach.
        // The gate is the SAME contract the public Listing page relies on —
        // `Listing::isVisibleToPublic()` (`status = published` AND `hidden_at IS
        // NULL`), with soft-deleted rows already excluded by the global scope — so
        // no second visibility definition is introduced.
        if (!$listing->isVisibleToPublic()) {
            return response()->json(['error' => 'Listing not found'], 404);
        }

        // Automated clients do not create an analytics row at all.
        if ($this->isAutomated($request)) {
            return response()->json(['success' => true, 'counted' => false, 'reason' => 'automated']);
        }

        // Repeat views from the same session inside the window are accepted but
        // NOT counted again, so refreshes cannot inflate the metric.
        if ($this->alreadyCounted($request, $listing, 'view')) {
            return response()->json(['success' => true, 'counted' => false, 'reason' => 'deduplicated']);
        }

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
        // WAVE 28B — the same visibility gate as trackListingView.
        if (!$listing->isVisibleToPublic()) {
            return response()->json(['error' => 'Listing not found'], 404);
        }

        try {
            // WAVE 28C - clicks are NOT deduplicated (a visitor may legitimately
            // press "call" twice); only obvious automated clients are skipped, so
            // the recorded click keeps its established meaning.
            if ($this->isAutomated($request)) {
                return response()->json(['success' => true, 'counted' => false, 'reason' => 'automated']);
            }

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