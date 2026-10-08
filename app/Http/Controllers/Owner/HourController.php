<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\LocationHourOverride;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HourController extends Controller
{
    public function index(?Business $business, Location $location)
    {
        try {
            Log::info('Hours index accessed', [
                'business_id' => $business?->id,
                'location_id' => $location->id,
                'user_id' => auth()->id()
            ]);

            // PHASE 22B - hours are Location-owned data. Authorization resolves
            // through the LOCATION owner, so a Business-less Professional is not
            // blocked merely because its Location has no `business_id`.
            try {
                $this->authorizeHours($business, $location);
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                Log::warning('Unauthorized hours access attempt', [
                    'business_id' => $business?->id,
                    'location_id' => $location->id,
                    'user_id' => auth()->id(),
                ]);
                throw $e;
            }

            $hours = $location->hours()->orderBy('day_of_week')->orderBy('sort_order')->get();
            $days = LocationHour::getDays();

            // ✅ Upcoming date overrides (today or later, not expired)
            $overrides = $location->hourOverrides()
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
                'location' => $location,
                // Back-compat prop for the existing page component.
                'branch' => $location,
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
            // PHASE 22B - an authorization failure is not a recoverable payload error.
            // Re-throw it so the caller receives a 403 rather than a redirect
            // that also echoed the exception message back to the user.
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }

            return redirect()->back()->with('error', 'Failed to load hours.');
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
    public function storeBatch(Request $request, ?Business $business, Location $location)
    {
        $this->authorizeHours($business, $location);

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
            \DB::transaction(function () use ($validated, $location) {
                foreach ($validated['hours'] as $day) {
                    $payload = [
                        'day_of_week' => $day['day_of_week'],
                        'opens_at' => $day['opens_at'] ?? null,
                        'closes_at' => $day['closes_at'] ?? null,
                        'is_closed' => $day['is_closed'] ?? false,
                        'is_24h' => $day['is_24h'] ?? false,
                        'sort_order' => $day['sort_order'] ?? 0,
                        'location_id' => $location->id,
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
                    $location->hours()
                        ->where('day_of_week', $payload['day_of_week'])
                        ->delete();

                    LocationHour::create($payload);
                }
            });

            return redirect()->back()
                ->with('success', 'Hours saved successfully.');
        } catch (\Throwable $e) {
            Log::error('Error saving hours batch:', [
                'business_id' => $business?->id,
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to save hours: ' . $e->getMessage());
        }
    }

    public function destroy(?Business $business, Location $location, LocationHour $hour)
    {
        try {
            $this->authorizeHours($business, $location);

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

    public function storeOverride(Request $request, ?Business $business, Location $location)
    {
        $this->authorizeHours($business, $location);

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

            LocationHourOverride::updateOrCreate(
                [
                    'location_id' => $location->id,
                    'date' => $validated['date'],
                ],
                $payload
            );

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Failed to create hour override', [
                'business_id' => $business?->id,
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to add override.');
        }
    }

    public function destroyOverride(?Business $business, Location $location, LocationHourOverride $override)
    {
        $this->authorizeHours($business, $location);

        if ($override->location_id !== $location->id) {
            abort(404);
        }

        $override->delete();

        return redirect()->back();
    }

    public function status(?Business $business, Location $location)
    {
        try {
            $this->authorizeHours($business, $location);

            $isOpen = $location->is_open_now;
            $hours = $location->hours_summary;

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

    /**
     * PHASE 22B � Location hours authorization.
     *
     * The LOCATION owner is authoritative. Business ownership is accepted only
     * as context, so hours are never blocked purely because a Location has no
     * `business_id`. The Location must also belong to the routed Business when
     * one is named, so a Business route cannot reach an unrelated Location.
     */
    private function authorizeHours(?\App\Models\Business $business, \App\Models\Location $location): void
    {
        $user = auth()->user();

        // PHASE 22B - the LOCATION OWNER is the sole authority.
        $ownsLocation = $user !== null
            && (int) $location->owner_id === (int) $user->id;

        //
        // Business ownership must NOT substitute for it: a stranger who owns
        // their OWN Business could otherwise reach another account's Location
        // by naming their business in the route. The route's Business is
        // navigation context only, and when it names an organization the
        // Location must actually belong to that organization (an independent
        // Location is acceptable).
        abort_unless($ownsLocation, 403);

        if ($business && $location->business_id !== null) {
            abort_unless((int) $location->business_id === (int) $business->id, 403);
        }
    }
}