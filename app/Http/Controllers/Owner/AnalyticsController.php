<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BusinessAnalytics;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use League\Csv\Writer;
use SplTempFileObject;

class AnalyticsController extends Controller
{
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

        if (!isset($business) || !$business) {
            $business = $businesses->first();
        }

        if (!$business) {
            return redirect()->route('owner.dashboard')
                ->with('error', 'No business to show analytics for.');
        }

        // Get date range filter
        $days = request('days', 30);
        $startDate = now()->subDays($days);

        // Get analytics data
        $analyticsData = BusinessAnalytics::where('business_id', $business->id)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        // Get totals
        $totals = BusinessAnalytics::getTotalStats($business->id);

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
            'business' => $business,
            'analyticsData' => $analyticsData,
            'chartData' => $chartData,
            'summary' => $summary,
            'conversionRate' => $conversionRate,
            'days' => $days,
            'businesses' => $businesses,
            'selectedBusinessId' => $business->id,
        ]);
    }

    public function trackView($businessId)
    {
        $analytics = BusinessAnalytics::trackView($businessId);
        return response()->json(['success' => true]);
    }

    public function trackClick($businessId, $type)
    {
        $analytics = BusinessAnalytics::trackClick($businessId, $type);
        return response()->json(['success' => true]);
    }

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

        if ($selectedBusinessId) {
            $business = $businesses->firstWhere('id', $selectedBusinessId);
        }

        if (!isset($business) || !$business) {
            $business = $businesses->first();
        }

        abort_unless($business, 404, 'No business found for export.');

        $days = (int) $request->input('days', 30);
        $days = max(1, min($days, 365)); // clamp 1..365

        $startDate = now()->subDays($days);
        $endDate = now();

        $analyticsData = BusinessAnalytics::where('business_id', $business->id)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        return [$business, $analyticsData, $days, $startDate, $endDate];
    }
}