<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ListingAnalytics;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use League\Csv\Writer;
use SplTempFileObject;

class AnalyticsController extends Controller
{
    /** WAVE 28B — the period choices the UI offers; anything else falls back to 30. */
    private const SUPPORTED_DAYS = [7, 30, 90, 365];

    /**
     * PHASE 11 / WAVE 1B — analytics are listing-owned. The owner dashboard is
     * still addressed by organization, so we AGGREGATE across the
     * organization's listings for display. This is a derived view, never stored.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    /**
     * WAVE 28B - THE AUTHORIZED LISTING SET IS ACCOUNT-OWNED.
     *
     * Analytics belong to LISTINGS, and an account's analytics are derived from
     * ALL the Listings it owns - independently owned Professional Listings
     * (business_id = NULL) included. A Business is an optional read-time FILTER
     * over this set and is never the authoritative analytics owner.
     *
     * Ownership is Listing.owner_id, so a Listing that merely REFERENCES one of
     * the account's Businesses while being owned by somebody else is excluded.
     * The previous $business->listings() did not make that distinction and could
     * aggregate another account's Listing activity into this account's totals.
     *
     * A selected Business only ever NARROWS this set - it can never expand it.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function authorizedListingIds(int $userId, ?int $businessId = null): \Illuminate\Support\Collection
    {
        return \App\Models\Listing::query()
            ->where('owner_id', $userId)
            ->when($businessId !== null, fn ($q) => $q->where('business_id', $businessId))
            ->pluck('id');
    }

    /**
     * WAVE 28B - the supported period choices, validated. An unvalidated `days`
     * allowed arbitrarily wide ranges; invalid input falls back to the default
     * rather than erroring.
     */
    /**
     * WAVE 28C - PER-LISTING BREAKDOWN over the authorized set.
     *
     * Every authorized Listing appears, so a Listing with no recorded activity is
     * visible rather than silently missing.
     *
     * @return array<int, array<string, mixed>>
     */
    private function perListingBreakdown($listingIds, int $days): array
    {
        if ($listingIds->isEmpty()) {
            return [];
        }

        $rows = ListingAnalytics::whereIn('listing_id', $listingIds)
            ->where('date', '>=', now()->subDays($days - 1))
            ->groupBy('listing_id')
            ->selectRaw('
                listing_id,
                COALESCE(SUM(views), 0) as total_views,
                COALESCE(SUM(unique_visitors), 0) as total_unique_visitors,
                COALESCE(SUM(phone_clicks), 0) as total_phone_clicks,
                COALESCE(SUM(whatsapp_clicks), 0) as total_whatsapp_clicks,
                COALESCE(SUM(website_clicks), 0) as total_website_clicks
            ')
            ->get()
            ->keyBy('listing_id');

        return \App\Models\Listing::whereIn('id', $listingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status', 'hidden_at'])
            ->map(function ($listing) use ($rows) {
                $row = $rows->get($listing->id);

                return [
                    'id' => $listing->id,
                    'name' => $listing->name,
                    'slug' => $listing->slug,
                    'public_url' => '/listing/' . $listing->slug,
                    'is_public' => $listing->isVisibleToPublic(),
                    'total_views' => (int) ($row->total_views ?? 0),
                    'total_unique_visitors' => (int) ($row->total_unique_visitors ?? 0),
                    'total_phone_clicks' => (int) ($row->total_phone_clicks ?? 0),
                    'total_whatsapp_clicks' => (int) ($row->total_whatsapp_clicks ?? 0),
                    'total_website_clicks' => (int) ($row->total_website_clicks ?? 0),
                ];
            })->values()->all();
    }

    /**
     * WAVE 28C - LISTING-SPECIFIC ANALYTICS.
     *
     * AUTHORIZATION: the requested Listing must be OWNED BY THE AUTHENTICATED
     * ACCOUNT (`listings.owner_id`). Owning the Listing's Business, owning a
     * sibling Listing, or supplying an id/slug is NOT sufficient. A non-owned
     * Listing is treated as absent (404), which also avoids confirming that
     * somebody else's Listing exists.
     */
    public function listingAnalytics(Request $request, \App\Models\Listing $listing)
    {
        $user = Auth::user();

        abort_unless((int) $listing->owner_id === (int) $user->id, 404);
        abort_unless($this->authorizedListingIds($user->id)->contains($listing->id), 404);

        // One renderer, one authorization contract: the Listing-specific request
        // renders the SAME analytics view, focused on that Listing.
        return redirect()->route('owner.analytics.index', [
            'listing' => $listing->id,
            'days' => $this->validatedDays($request),
        ]);
    }

    private function validatedDays(Request $request): int
    {
        $days = (int) $request->input('days', 30);

        return in_array($days, self::SUPPORTED_DAYS, true) ? $days : 30;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $businesses = $user->businesses()
            ->whereIn('status', ['approved', 'published'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'slug', 'status']);

        $selectedBusinessId = $request->input('business');

        if ($selectedBusinessId) {
            $business = $businesses->firstWhere('id', $selectedBusinessId);
        }

        // WAVE 28B - a Business is an OPTIONAL filter. An account with only
        // independently owned Professional Listings is no longer redirected away:
        // its analytics resolve from its own Listings.
        $business = $selectedBusinessId
            ? $businesses->firstWhere('id', $selectedBusinessId)
            : null;

        $days = $this->validatedDays($request);

        $startDate = now()->subDays($days);

                // Get analytics data — aggregate across the organization's listings.
        // Account-owned set, narrowed (never expanded) by the filter.
        $listingIds = $business
            ? $this->authorizedListingIds($user->id, $business->id)
            : $this->authorizedListingIds($user->id);

        // WAVE 28C - an optional LISTING filter, INTERSECTED with the
        // account-authorised set, so a foreign id can only ever produce an empty
        // result and never another account's data.
        $requestedListingId = $request->integer('listing');
        if ($requestedListingId) {
            $listingIds = $listingIds->intersect([$requestedListingId])->values();
        }

        $analyticsData = ListingAnalytics::whereIn('listing_id', $listingIds)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        // Get totals (aggregate across listings)
        $totals = ListingAnalytics::whereIn('listing_id', $listingIds)
            ->selectRaw('
                COALESCE(SUM(views), 0) as total_views,
                COALESCE(SUM(unique_visitors), 0) as total_unique_visitors,
                COALESCE(SUM(phone_clicks), 0) as total_phone_clicks,
                COALESCE(SUM(whatsapp_clicks), 0) as total_whatsapp_clicks,
                COALESCE(SUM(website_clicks), 0) as total_website_clicks,
                COALESCE(SUM(direction_clicks), 0) as total_direction_clicks,
                COALESCE(SUM(social_clicks), 0) as total_social_clicks
            ')
            ->first();

        // Prepare chart data
        $chartData = [
            'labels' => $analyticsData->pluck('date')->map(function ($date) {
                return $date->format('M d');
            })->toArray(),
            'views' => $analyticsData->pluck('views')->toArray(),
            'unique_visitors' => $analyticsData->pluck('unique_visitors')->toArray(),
            'phone_clicks' => $analyticsData->pluck('phone_clicks')->toArray(),
            'whatsapp_clicks' => $analyticsData->pluck('whatsapp_clicks')->toArray(),
            'website_clicks' => $analyticsData->pluck('website_clicks')->toArray(),
        ];

        // Get summary stats
        $summary = [
            'total_views' => $totals->total_views ?? 0,
            'total_unique_visitors' => $totals->total_unique_visitors ?? 0,
            'total_phone_clicks' => $totals->total_phone_clicks ?? 0,
            'total_whatsapp_clicks' => $totals->total_whatsapp_clicks ?? 0,
            'total_website_clicks' => $totals->total_website_clicks ?? 0,
            'total_social_clicks' => $totals->total_social_clicks ?? 0,
            'total_direction_clicks' => $totals->total_direction_clicks ?? 0,
        ];

        // Calculate conversion rate (phone clicks / views * 100)
        $conversionRate = $summary['total_views'] > 0
            ? round(($summary['total_phone_clicks'] / $summary['total_views']) * 100, 2)
            : 0;

        return Inertia::render('Owner/Analytics/Index', [
            // WAVE 28B - a neutral subject when no Business is selected, so the
            // page renders for an account with only Professional Listings.
            'business' => $business ?? (object) ['id' => null, 'name' => 'All listings', 'slug' => null],
            'analyticsData' => $analyticsData,
            'chartData' => $chartData,
            'summary' => $summary,
            'conversionRate' => $conversionRate,
            'days' => $days,
            'businesses' => $businesses,
            'selectedBusinessId' => $business?->id,
            'listingsCount' => $listingIds->count(),
            'selectedListingId' => $requestedListingId ?: null,
            // WAVE 28C - per-Listing performance, so an owner can tell their own
            // Listings apart. One grouped query; no new analytics subsystem.
            'perListing' => $this->perListingBreakdown($listingIds, $days),
        ]);
    }

    // PHASE 11 / WAVE 1D-3 — the Business-keyed trackView($businessId) and
    // trackClick($businessId, $type) writers were DELETED. They existed only to
    // attribute Listing-owned analytics to an arbitrary Listing chosen by
    // Business::primaryListing(), and had no in-repository caller. Analytics are
    // recorded on the canonical Listing path instead:
    // /analytics/listing/{listing}/track-view|track-click/{type}.
    //
    // What remains here are the legitimate Business AGGREGATE reads and exports.

    /**
     * ✅ Export analytics as CSV.
     *
     * Output:
     *   - Summary block (Business, Slug, Period, Generated, Totals...)
     *   - Blank line
     *   - Daily breakdown table (Date, Views, Unique Visitors, Phone, WhatsApp, Website)
     */
    public function exportCsv(Request $request)
    {
        [$business, $analyticsData, $days, $startDate, $endDate] = $this->resolveExportContext($request);

        // Build a memory-resident CSV
        $csv = Writer::createFromFileObject(new SplTempFileObject());

        // Summary block
        $csv->insertOne(['Business', $business->name]);
        $csv->insertOne(['Slug', $business->slug]);
        $csv->insertOne(['Period', "Last {$days} days ({$startDate->format('M j, Y')} to {$endDate->format('M j, Y')})"]);
        $csv->insertOne(['Generated', now()->format('M j, Y g:i A')]);
        $csv->insertOne([]);

        // Totals block
        $csv->insertOne(['Total Views', $analyticsData->sum('views')]);
        $csv->insertOne(['Total Unique Visitors', $analyticsData->sum('unique_visitors')]);
        $csv->insertOne(['Total Phone Clicks', $analyticsData->sum('phone_clicks')]);
        $csv->insertOne(['Total WhatsApp Clicks', $analyticsData->sum('whatsapp_clicks')]);
        $csv->insertOne(['Total Website Clicks', $analyticsData->sum('website_clicks')]);
        $csv->insertOne([]);

        // Daily table header
        $csv->insertOne([
            'Date',
            'Views',
            'Unique Visitors',
            'Phone Clicks',
            'WhatsApp Clicks',
            'Website Clicks',
        ]);

        // Daily rows
        foreach ($analyticsData as $row) {
            $csv->insertOne([
                $row->date->format('Y-m-d'),
                $row->views ?? 0,
                $row->unique_visitors ?? 0,
                $row->phone_clicks ?? 0,
                $row->whatsapp_clicks ?? 0,
                $row->website_clicks ?? 0,
            ]);
        }

        $filename = sprintf(
            'analytics_%s_%s_%dd.csv',
            $business->slug,
            now()->format('Y-m-d'),
            $days
        );

        return response((string) $csv->toString(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * ✅ Export analytics as PDF.
     *
     * Uses barryvdh/laravel-dompdf with a Blade view at
     * resources/views/pdf/analytics.blade.php
     */
    public function exportPdf(Request $request)
    {
        [$business, $analyticsData, $days, $startDate, $endDate] = $this->resolveExportContext($request);

        $summary = [
            'views' => $analyticsData->sum('views'),
            'unique_visitors' => $analyticsData->sum('unique_visitors'),
            'phone_clicks' => $analyticsData->sum('phone_clicks'),
            'whatsapp_clicks' => $analyticsData->sum('whatsapp_clicks'),
            'website_clicks' => $analyticsData->sum('website_clicks'),
        ];

        $pdf = Pdf::loadView('pdf.analytics', [
            'business' => $business,
            'rows' => $analyticsData,
            'summary' => $summary,
            'days' => $days,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'analytics_%s_%s_%dd.pdf',
            $business->slug,
            now()->format('Y-m-d'),
            $days
        );

        return $pdf->download($filename);
    }

    /**
     * ✅ Shared context resolver for both exports.
     *
     * Validates business ownership, resolves days range, pulls analytics rows.
     *
     * @return array [Business, Collection, int $days, Carbon $startDate, Carbon $endDate]
     */
    private function resolveExportContext(Request $request)
    {
        $user = Auth::user();

        $businesses = $user->businesses()
            ->whereIn('status', ['approved', 'published'])
            ->get(['id', 'name', 'slug', 'status']);

        $selectedBusinessId = $request->input('business');
        $business = $selectedBusinessId
            ? $businesses->firstWhere('id', $selectedBusinessId)
            : null;

        // WAVE 28B - exports cover the ACCOUNT's authorized Listings, narrowed by
        // the optional filter. A neutral subject keeps the CSV and PDF formats
        // (and the pdf.analytics view) unchanged when no Business is selected.
        $subject = $business ?? (object) ['name' => 'All listings', 'slug' => 'all-listings'];

        $listingIds = $business
            ? $this->authorizedListingIds($user->id, $business->id)
            : $this->authorizedListingIds($user->id);

        $days = $this->validatedDays($request);

        $startDate = now()->subDays($days);
        $endDate = now();

        $analyticsData = ListingAnalytics::whereIn('listing_id', $listingIds)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        return [$subject, $analyticsData, $days, $startDate, $endDate];
    }
}