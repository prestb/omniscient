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

        // If this is the first location or marked primary, set is_primary
        if ($business->locations()->count() === 0 || $request->boolean('is_primary')) {
            $business->locations()->update(['is_primary' => false]);
            $validated['is_primary'] = true;
        }

        // PHASE 22A - the canonical owner is always the authenticated account.
        // `business_id` is optional context only and is never the ownership check.
        $validated['owner_id'] = $user->id;
        $validated['business_id'] = $business->id;
        $validated['sort_order'] = $business->locations()->count() + 1;

        Location::create($validated);

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

        if ($location->is_primary) {
            $newPrimary = $business->locations()
                ->where('id', '!=', $location->id)
                ->first();

            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        $location->delete();

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

        $locations = Location::where('owner_id', $request->user()->id)
            ->with('business:id,name')
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

        $validated = $request->validate($this->ownerLocationRules());

        // PHASE 22A — Business is OPTIONAL context. When supplied it must be one
        // the authenticated user actually owns; it never grants ownership.
        if (!empty($validated['business_id'])) {
            $ownsBusiness = $user->businesses()->whereKey($validated['business_id'])->exists();
            abort_unless($ownsBusiness, 403);
        }

        $validated['owner_id'] = $user->id;
        $validated['is_primary'] = false;
        $validated['sort_order'] = Location::where('owner_id', $user->id)->count() + 1;

        Location::create($validated);

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

        $validated = $request->validate($this->ownerLocationRules());

        if (!empty($validated['business_id'])) {
            $ownsBusiness = $request->user()->businesses()->whereKey($validated['business_id'])->exists();
            abort_unless($ownsBusiness, 403);
        }

        // owner_id is immutable through this endpoint.
        unset($validated['owner_id']);
        $location->update($validated);

        return redirect()->route('owner.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function ownerDestroy(Location $location)
    {
        Gate::authorize('delete', $location);

        // PHASE 22A — deletion is preserved as-is (soft delete). Resolving what
        // should happen to a Listing whose Location is removed while attached is
        // Phase 22B; this phase does not introduce a destructive cascade.
        $location->delete();

        return redirect()->route('owner.locations.index')
            ->with('success', 'Location removed successfully.');
    }

    /** Shared validation for the owner-scoped family. */
    private function ownerLocationRules(): array
    {
        return [
            // Business is OPTIONAL context, never an ownership requirement.
            'business_id' => 'nullable|exists:businesses,id',
            'name' => 'nullable|string|max:100',
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
            'status' => 'nullable|in:active,temporarily_unavailable,unlisted',
        ];
    }
}
