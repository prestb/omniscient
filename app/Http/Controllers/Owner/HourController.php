<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchHourOverride;
use App\Models\Business;
use App\Models\BusinessHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HourController extends Controller
{
    public function index(Business $business, Branch $branch)
    {
        try {
            Log::info('Hours index accessed', [
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'user_id' => auth()->id()
            ]);

            if ($business->owner_id !== auth()->id()) {
                Log::warning('Unauthorized hours access attempt', [
                    'business_id' => $business->id,
                    'user_id' => auth()->id()
                ]);
                abort(403);
            }

            $hours = $branch->hours()->orderBy('day_of_week')->orderBy('sort_order')->get();
            $days = BusinessHour::getDays();

            // ✅ Upcoming date overrides (today or later, not expired)
            $overrides = $branch->hourOverrides()
                ->whereDate('date', '>=', today())
                ->orderBy('date')
                ->get()
                ->map(function ($o) {
                    return [
                        'id' => $o->id,
                        'date' => $o->date->toDateString(),
                        'is_closed' => $o->is_closed,
                        'is_special_hours' => $o->is_special_hours,
                        'opens_at' => $o->opens_at ? $o->opens_at->format('H:i') : null,
                        'closes_at' => $o->closes_at ? $o->closes_at->format('H:i') : null,
                        'formatted_hours' => $o->formatted_hours,
                        'note' => $o->note,
                        'formatted_date' => $o->date->format('M j, Y'),
                    ];
                });

            Log::info('Hours retrieved', ['hours_count' => $hours->count(), 'overrides_count' => $overrides->count()]);

            return Inertia::render('Owner/Hours/Index', [
                'business' => $business,
                'branch' => $branch,
                'hours' => $hours,
                'days' => $days,
                'overrides' => $overrides,   // ✅ new
            ]);
        } catch (\Exception $e) {
            Log::error('Error in hours index:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Redirect back with error
            return redirect()->back()->with('error', 'Failed to load hours: ' . $e->getMessage());
        }
    }

    /**
     * Save a batch of days' hours in a single transaction.
     *
     * The frontend sends only the days the user has actually changed.
     * We validate the whole payload first, then upsert each day inside
     * a DB transaction so a partial failure rolls everything back.
     *
     * ✅ Replaces the old per-day `store()` which fired one request per day.
     */
    public function storeBatch(Request $request, Business $business, Branch $branch)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'hours' => 'required|array|min:1',
            'hours.*.day_of_week' => 'required|integer|between:0,6',
            'hours.*.opens_at' => 'nullable|date_format:H:i',
            'hours.*.closes_at' => 'nullable|date_format:H:i',
            'hours.*.is_closed' => 'boolean',
            'hours.*.is_24h' => 'boolean',
            'hours.*.sort_order' => 'nullable|integer',
        ]);

        try {
            \DB::transaction(function () use ($validated, $branch) {
                foreach ($validated['hours'] as $day) {
                    $payload = [
                        'day_of_week' => $day['day_of_week'],
                        'opens_at' => $day['opens_at'] ?? null,
                        'closes_at' => $day['closes_at'] ?? null,
                        'is_closed' => $day['is_closed'] ?? false,
                        'is_24h' => $day['is_24h'] ?? false,
                        'sort_order' => $day['sort_order'] ?? 0,
                        'branch_id' => $branch->id,
                    ];

                    // If 24/7, clear the time fields
                    if (!empty($payload['is_24h'])) {
                        $payload['opens_at'] = null;
                        $payload['closes_at'] = null;
                        $payload['is_closed'] = false;
                    }

                    // If closed, clear the time fields
                    if (!empty($payload['is_closed'])) {
                        $payload['opens_at'] = null;
                        $payload['closes_at'] = null;
                        $payload['is_24h'] = false;
                    }

                    // Replace the existing row(s) for this day
                    $branch->hours()
                        ->where('day_of_week', $payload['day_of_week'])
                        ->delete();

                    BusinessHour::create($payload);
                }
            });

            return redirect()->back()
                ->with('success', 'Hours saved successfully.');
        } catch (\Throwable $e) {
            Log::error('Error saving hours batch:', [
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to save hours: ' . $e->getMessage());
        }
    }

    public function destroy(Business $business, Branch $branch, BusinessHour $hour)
    {
        try {
            if ($business->owner_id !== auth()->id()) {
                abort(403);
            }

            $hour->delete();

            return redirect()->back()->with('success', 'Hours deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting hours:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to delete hours: ' . $e->getMessage());
        }
    }
    // ============== DATE OVERRIDES ==============

    public function storeOverride(Request $request, Business $business, Branch $branch)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'mode' => 'required|in:closed,special',
            'opens_at' => 'nullable|required_if:mode,special|date_format:H:i',
            'closes_at' => 'nullable|required_if:mode,special|date_format:H:i|after:opens_at',
            'note' => 'nullable|string|max:100',
        ]);

        try {
            $isClosed = $validated['mode'] === 'closed';

            $payload = [
                'is_closed' => $isClosed,
                'opens_at' => $isClosed ? null : $validated['opens_at'],
                'closes_at' => $isClosed ? null : $validated['closes_at'],
                'note' => $validated['note'] ?? null,
                'created_by' => auth()->id(),
            ];

            BranchHourOverride::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'date' => $validated['date'],
                ],
                $payload
            );

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Failed to create hour override', [
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to add override.');
        }
    }

    public function destroyOverride(Business $business, Branch $branch, BranchHourOverride $override)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        if ($override->branch_id !== $branch->id) {
            abort(404);
        }

        $override->delete();

        return redirect()->back();
    }

    public function status(Business $business, Branch $branch)
    {
        try {
            if ($business->owner_id !== auth()->id()) {
                abort(403);
            }

            $isOpen = $branch->is_open_now;
            $hours = $branch->hours_summary;

            return response()->json([
                'is_open' => $isOpen,
                'hours' => $hours,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting hours status:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to get status: ' . $e->getMessage()
            ], 500);
        }
    }
}