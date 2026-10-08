<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Business;
use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Region;
use App\Traits\GuardsHiddenItems;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Owner\LocationController (Phase 10).
 *
 * Manages an organization's physical Locations. Historically this was
 * `Owner\BranchController`; "Branch" is gone from the physical-place
 * abstraction.
 */
class LocationController extends Controller
{
    use GuardsHiddenItems;

    public function index(Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // ✅ Server-side lock: can't add a location to a hidden business
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }

        $locations = $business->locations()
            ->with(['country', 'region', 'city', 'area'])
            ->ordered()
            ->get();

        return Inertia::render('Owner/Locations/Index', [
            'business' => $business,
            'locations' => $locations,
            // Back-compat prop name for the existing page component.
            'branches' => $locations,
        ]);
    }

    public function create(Business $business)
    {
        $user = auth()->user();

        // Verify ownership
        if ($business->owner_id !== $user->id) {
            abort(403, 'You don\'t own this business.');
        }

        // ✅ Server-side lock: can't add a location to a hidden business
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }

        // ✅ Trait-based check — the canonical quota key is `locations`
        //    (Entitlement::CREATE_LOCATION). `plan.limit:locations` also guards this route.
        if (method_exists($user, 'canAdd') && !$user->canAdd('locations')) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'You have reached the maximum number of locations allowed on your plan.');
        }

        $countries = Country::active()->get();

        $regions = collect();
        $cities = collect();
        $areas = collect();

        return Inertia::render('Owner/Locations/Create', [
            'business' => $business,
            'countries' => $countries,
            'regions' => $regions,
            'cities' => $cities,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request, Business $business)
    {
        $user = auth()->user();

        if ($business->owner_id !== $user->id) {
            abort(403);
        }

        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }

        if (method_exists($user, 'canAdd') && !$user->canAdd('locations')) {
            return back()->with('error', 'You have reached the maximum number of locations.');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'country_id' => 'required|exists:countries,id',
            'region_id' => 'required|exists:regions,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'nullable|string|max:150',
            'landmark' => 'nullable|string|max:150',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'status' => 'required|in:active,temporarily_unavailable,unlisted',
        ]);

        // Serialize Location creation against Business deletion. Both paths
        // lock the Business row, so a location cannot be inserted after the
        // deletion path has enumerated and removed the Business's Locations.
        DB::transaction(function () use ($business, $user, $request, $validated) {
            $lockedBusiness = Business::query()
                ->whereKey($business->id)
                ->where('owner_id', $user->id)
                ->lockForUpdate()
                ->first();

            abort_unless($lockedBusiness, 404);

            // If this is the first location or marked primary, set is_primary.
            if ($lockedBusiness->locations()->count() === 0 || $request->boolean('is_primary')) {
                $lockedBusiness->locations()->update(['is_primary' => false]);
                $validated['is_primary'] = true;
            }

            // PHASE 22A - the canonical owner is always the authenticated account.
            // `business_id` is optional context only and is never the ownership check.
            $validated['owner_id'] = $user->id;
            $validated['business_id'] = $lockedBusiness->id;
            $validated['sort_order'] = $lockedBusiness->locations()->count() + 1;

            Location::create($validated);
        });

        return redirect()->route('owner.businesses.locations.index', $business)
            ->with('success', 'Location added successfully.');
    }

    public function edit(Business $business, Location $location)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }
        if ($redirect = $this->guardNotHidden($location, 'location')) {
            return $redirect;
        }

        $location->load(['country', 'region', 'city', 'area']);

        $countries = Country::active()->get();

        $regions = Region::active()
            ->where('country_id', $location->country_id)
            ->get();

        $cities = City::active()
            ->where('region_id', $location->region_id)
            ->get();

        $areas = Area::active()
            ->where('city_id', $location->city_id)
            ->get();

        return Inertia::render('Owner/Locations/Edit', [
            'business' => $business,
            'location' => $location,
            // Back-compat prop name for the existing page component.
            'branch' => $location,
            'countries' => $countries,
            'regions' => $regions,
            'cities' => $cities,
            'areas' => $areas,
        ]);
    }

    public function update(Request $request, Business $business, Location $location)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }
        if ($redirect = $this->guardNotHidden($location, 'location')) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'country_id' => 'required|exists:countries,id',
            'region_id' => 'required|exists:regions,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'nullable|string|max:150',
            'landmark' => 'nullable|string|max:150',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'status' => 'required|in:active,temporarily_unavailable,unlisted',
        ]);

        if ($request->boolean('is_primary')) {
            $business->locations()
                ->where('id', '!=', $location->id)
                ->update(['is_primary' => false]);
        }

        $location->update($validated);

        return redirect()->route('owner.businesses.locations.index', $business)
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(Business $business, Location $location)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // Use the same lock order as Business deletion (Business, then Location
        // rows ordered by id) so primary-location updates cannot race deletion.
        DB::transaction(function () use ($business, $location) {
            $lockedBusiness = Business::query()
                ->whereKey($business->id)
                ->where('owner_id', auth()->id())
                ->lockForUpdate()
                ->firstOrFail();

            $locations = Location::query()
                ->where('business_id', $lockedBusiness->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $lockedLocation = $locations->firstWhere('id', $location->id);
            abort_unless($lockedLocation, 404);

            // Check while holding the Location lock. Listing create/update takes
            // this same lock before assigning location_id.
            $this->guardLocationInUse($lockedLocation);

            if ($lockedLocation->is_primary) {
                $newPrimary = $locations->first(fn ($candidate) => $candidate->id !== $lockedLocation->id);

                if ($newPrimary) {
                    $newPrimary->update(['is_primary' => true]);
                }
            }

            $lockedLocation->delete();
        });

        return redirect()->route('owner.businesses.locations.index', $business)
            ->with('success', 'Location deleted successfully.');
    }

    // =========================================================================
    // PHASE 22A — OWNER-SCOPED LOCATION DOMAIN
    //
    // A Location is an ACCOUNT-owned resource. These methods deliberately take no
    // Business: a Business-less Professional must be able to create, edit and
    // remove its own Location, which the Business-scoped routes cannot express.
    //
    // Authorization is `location.owner_id`, via LocationPolicy.
    // =========================================================================

    public function ownerIndex(Request $request)
    {
        Gate::authorize('viewAny', Location::class);

        // PHASE 22B - the Listings count lets the UI communicate that a Location
        // is still in use. The BACKEND remains authoritative on deletion; this
        // is convenience and context only.
        $locations = Location::where('owner_id', $request->user()->id)
            ->with('business:id,name')
            ->withCount('listings')
            ->ordered()
            ->get();

        return Inertia::render('Owner/Locations/OwnerIndex', [
            'locations' => $locations,
            'businesses' => $request->user()->businesses()->get(['id', 'name']),
        ]);
    }

    public function ownerCreate(Request $request)
    {
        Gate::authorize('create', Location::class);

        return Inertia::render('Owner/Locations/Create', [
            // Optional organization context. Never required to save.
            'businesses' => $request->user()->businesses()->get(['id', 'name']),
        ]);
    }

    public function ownerStore(Request $request)
    {
        Gate::authorize('create', Location::class);

        $user = $request->user();

        if (method_exists($user, 'canAdd') && !$user->canAdd('locations')) {
            return back()->with('error', 'You have reached the maximum number of locations.');
        }

        try {
            $validated = $request->validate(
                $this->ownerLocationRules($request),
                $this->ownerLocationMessages()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        // PHASE 22A — Business is OPTIONAL context. When supplied it must be one
        // the authenticated user actually owns; it never grants ownership.
        // Lock that Business in the same transaction used by Business deletion.
        DB::transaction(function () use ($validated, $user) {
            if (!empty($validated['business_id'])) {
                $business = Business::query()
                    ->whereKey($validated['business_id'])
                    ->where('owner_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                abort_unless($business, 403);
            }

            $validated['owner_id'] = $user->id;
            $validated['is_primary'] = false;
            $validated['sort_order'] = Location::where('owner_id', $user->id)->count() + 1;

            Location::create($validated);
        });

        return redirect()->route('owner.locations.index')
            ->with('success', 'Location added successfully.');
    }

    public function ownerEdit(Request $request, Location $location)
    {
        Gate::authorize('update', $location);

        return Inertia::render('Owner/Locations/Edit', [
            'location' => $location,
            'businesses' => $request->user()->businesses()->get(['id', 'name']),
        ]);
    }

    public function ownerUpdate(Request $request, Location $location)
    {
        Gate::authorize('update', $location);

        try {
            $validated = $request->validate(
                $this->ownerLocationRules($request),
                $this->ownerLocationMessages()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $user = $request->user();

        // Lock current/target Business rows in stable order before locking the
        // Location. This matches deletion's Business -> Location lock order and
        // prevents reassignment into a Business while it is being deleted.
        DB::transaction(function () use ($validated, $location, $user) {
            $businessIds = collect([
                $location->business_id,
                $validated['business_id'] ?? null,
            ])->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values();

            foreach ($businessIds as $businessId) {
                $business = Business::query()
                    ->whereKey($businessId)
                    ->where('owner_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                // The previous Business may have been deleted concurrently; in
                // that case its Location should also have been soft-deleted.
                // A requested target, however, must still exist and be owned.
                if ((int) ($validated['business_id'] ?? 0) === $businessId) {
                    abort_unless($business, 403);
                }
            }

            $lockedLocation = Location::query()
                ->whereKey($location->id)
                ->where('owner_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            // owner_id is immutable through this endpoint.
            unset($validated['owner_id']);
            $lockedLocation->update($validated);
        });

        return redirect()->route('owner.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function ownerDestroy(Location $location)
    {
        Gate::authorize('delete', $location);

        // The in-use check and soft delete must share a transaction and Location
        // row lock with Listing attachment, otherwise an attach can land between
        // the count and delete.
        DB::transaction(function () use ($location) {
            $lockedLocation = Location::query()
                ->whereKey($location->id)
                ->where('owner_id', auth()->id())
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardLocationInUse($lockedLocation);
            $lockedLocation->delete();
        });

        return redirect()->route('owner.locations.index')
            ->with('success', 'Location removed successfully.');
    }

    /**
     * PHASE 22B — canonical Location validation.
     *
     * Shared by BOTH route families so a Business-backed Location and an
     * account-owned Location obey exactly the same integrity rules.
     *
     *  COORDINATES are an optional PAIR. Latitude without longitude (or the
     *  reverse) is a state no map can render, so it is rejected rather than
     *  stored and silently degraded later.
     *
     *  GEOGRAPHY is validated for COHERENCE, not completeness. Region, City and
     *  Area are independently nullable, but a submitted combination may not
     *  contradict the hierarchy the models already define:
     *      Region -> Country, City -> Region, Area -> City
     */
    private function ownerLocationRules(Request $request): array
    {
        return [
            // Business is OPTIONAL context, never an ownership requirement.
            // PHASE 22C - a soft-deleted Business must not become a Location's
            // organization context. `ListingRequest` already guards this for
            // Listings; the Location rules did not.
            'business_id' => [
                'nullable',
                Rule::exists('businesses', 'id')->whereNull('deleted_at'),
            ],
            'name' => 'nullable|string|max:100',

            'country_id' => 'required|exists:countries,id',
            'region_id' => [
                'required',
                // Coherence: the region must belong to the submitted country.
                Rule::exists('regions', 'id')->where(
                    fn ($q) => $q->where('country_id', $request->input('country_id'))
                ),
            ],
            'city_id' => [
                'required',
                // Coherence: the city must belong to the submitted region.
                Rule::exists('cities', 'id')->where(
                    fn ($q) => $q->where('region_id', $request->input('region_id'))
                ),
            ],
            'area_id' => [
                'nullable',
                // Coherence: the area must belong to the submitted city.
                Rule::exists('areas', 'id')->where(
                    fn ($q) => $q->where('city_id', $request->input('city_id'))
                ),
            ],

            'address' => 'nullable|string|max:150',
            'landmark' => 'nullable|string|max:150',
            'postal_code' => 'nullable|string|max:20',

            // Coordinates: optional as a PAIR, never individually.
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],

            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,temporarily_unavailable,unlisted',
        ];
    }

    /** Human-readable messages for the integrity rules. */
    private function ownerLocationMessages(): array
    {
        return [
            'region_id.exists' => 'That region does not belong to the selected country.',
            'city_id.exists' => 'That city does not belong to the selected region.',
            'area_id.exists' => 'That area does not belong to the selected city.',
            'latitude.required_with' => 'Latitude and longitude must be provided together.',
            'longitude.required_with' => 'Latitude and longitude must be provided together.',
        ];
    }

    /**
     * PHASE 22B — DELETION PROTECTION.
     *
     * A Location may be shared by several Listings. Deleting it while attached
     * would silently strip the physical place from every one of them, so the
     * owner must explicitly detach first. Nothing cascades into Listings.
     */
    private function guardLocationInUse(Location $location): void
    {
        $inUse = $location->listings()->count();

        if ($inUse > 0) {
            abort(422, $inUse === 1
                ? 'This location is still used by 1 Listing. Detach it before deleting.'
                : "This location is still used by {$inUse} Listings. Detach them before deleting.");
        }
    }
}
