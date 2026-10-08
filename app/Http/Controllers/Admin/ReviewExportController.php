<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReviewExportController extends Controller
{


/**
     * Export reviews to PDF (default)
     */
    public function export(Request $request)
    {
        return $this->exportPdf($request);
    }

    
    /**
     * Export reviews to CSV
     */
    public function exportCsv(Request $request)
    {
        try {
            $query = Review::with(['user', 'listing.business']);
            $this->applyFilters($query, $request);
            $reviews = $query->orderBy('created_at', 'desc')->get();

            // Build CSV manually
            $csv = "ID,Reviewer Name,Reviewer Email,Business Name,Rating,Review Content,Reply,Status,Created At,Updated At\n";
            
            foreach ($reviews as $review) {
                $csv .= implode(',', [
                    $review->id,
                    '"' . str_replace('"', '""', $review->user?->name ?? 'Anonymous') . '"',
                    '"' . str_replace('"', '""', $review->user?->email ?? 'N/A') . '"',
                    '"' . str_replace('"', '""', $review->listing?->business?->name ?? 'Unknown') . '"',
                    $review->rating,
                    '"' . str_replace('"', '""', $review->content ?? 'No content') . '"',
                    '"' . str_replace('"', '""', $review->reply ?? 'No reply') . '"',
                    $review->status,
                    $review->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                    $review->updated_at?->format('Y-m-d H:i:s') ?? 'N/A'
                ]) . "\n";
            }

            $filename = 'reviews_export_' . date('Y-m-d_His') . '.csv';
            $bom = "\xEF\xBB\xBF";

            return response($bom . $csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);

        } catch (\Exception $e) {
            \Log::error('CSV Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export reviews to JSON
     */
    public function exportJson(Request $request)
    {
        try {
            $query = Review::with(['user', 'listing.business']);
            $this->applyFilters($query, $request);
            $reviews = $query->orderBy('created_at', 'desc')->get();

            $data = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'reviewer' => [
                        'name' => $review->user?->name ?? 'Anonymous',
                        'email' => $review->user?->email ?? 'N/A',
                    ],
                    'business' => [
                        'name' => $review->listing?->business?->name ?? 'Unknown',
                    ],
                    'rating' => $review->rating,
                    'content' => $review->content ?? 'No content',
                    'reply' => $review->reply ?? 'No reply',
                    'status' => $review->status,
                    'created_at' => $review->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $review->updated_at?->format('Y-m-d H:i:s'),
                ];
            });

            $filename = 'reviews_export_' . date('Y-m-d_His') . '.json';

            return response()->json($data, 200, [
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);

        } catch (\Exception $e) {
            \Log::error('JSON Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export reviews to PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            $query = Review::with(['user', 'listing.business']);
            $this->applyFilters($query, $request);
            $reviews = $query->orderBy('created_at', 'desc')->get();

            // Calculate summary statistics
            $totalReviews = $reviews->count();
            $averageRating = $reviews->avg('rating') ?? 0;
            $pendingCount = $reviews->where('status', 'pending')->count();
            $approvedCount = $reviews->where('status', 'approved')->count();
            $rejectedCount = $reviews->where('status', 'rejected')->count();

            // Prepare data for PDF view
            $data = [
                'reviews' => $reviews,
                'totalReviews' => $totalReviews,
                'averageRating' => number_format($averageRating, 1),
                'pendingCount' => $pendingCount,
                'approvedCount' => $approvedCount,
                'rejectedCount' => $rejectedCount,
                'exportDate' => now()->format('F d, Y H:i:s'),
                'filters' => [
                    'search' => $request->search,
                    'status' => $request->status,
                    'rating' => $request->rating,
                    'date' => $request->date,
                ],
            ];

            // Generate PDF
            $pdf = Pdf::loadView('exports.reviews-pdf', $data);
            
            // Set paper size and orientation
            $pdf->setPaper('A4', 'landscape');
            
            // Set filename with .pdf extension
            $filename = 'reviews_export_' . date('Y-m-d_His') . '.pdf';

            // Return PDF download with proper headers
            return $pdf->download($filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);

        } catch (\Exception $e) {
            \Log::error('PDF Export Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'PDF Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('email', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('business', function ($bq) use ($search) {
                      $bq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
    }
}