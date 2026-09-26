<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Country;
use App\Models\Region;
use App\Models\City;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\GuardsHiddenItems;

class BranchController extends Controller
{

    use GuardsHiddenItems;

    public function index(Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }


        // ✅ Server-side lock: can't add a branch to a hidden business
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }

        $branches = $business->branches()
            ->with(['country', 'region', 'city', 'area'])
            ->ordered()
            ->get();

        return Inertia::render('Owner/Branches/Index', [
            'business' => $business,
            'branches' => $branches,
        ]);
    }

    public function create(Business $business)
    {
        $user = auth()->user();

        // Verify ownership
        if ($business->owner_id !== $user->id) {
            abort(403, 'You don\'t own this business.');
        }

        // ✅ Server-side lock: can't add a branch to a hidden business
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }

        // ✅ Trait-based check
        if (method_exists($user, 'canAdd') && !$user->canAdd('branches')) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'You have reached the maximum number of branches allowed on your plan.');
        }

        // Get all countries
        $countries = Country::active()->get();

        // Get all regions (initially empty)
        $regions = collect();
        $cities = collect();
        $areas = collect();

        return Inertia::render('Owner/Branches/Create', [
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

        // ✅ Server-side lock: can't add a branch to a hidden business
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }

        // ✅ Trait check
        if (method_exists($user, 'canAdd') && !$user->canAdd('branches')) {
            return back()->with('error', 'You have reached the maximum number of branches.');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'country_id' => 'required|exists:countries,id',
            'region_id' => 'required|exists:regions,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'status' => 'required|in:active,temporarily_unavailable,unlisted',
        ]);

        // If this is the first branch or marked as primary, set is_primary
        if ($business->branches()->count() === 0 || $request->boolean('is_primary')) {
            // Unset any existing primary branch
            $business->branches()->update(['is_primary' => false]);
            $validated['is_primary'] = true;
        }

        $validated['business_id'] = $business->id;
        $validated['sort_order'] = $business->branches()->count() + 1;

        Branch::create($validated);

        return redirect()->route('owner.businesses.branches.index', $business)
            ->with('success', 'Branch added successfully.');
    }

    public function edit(Business $business, Branch $branch)
    {
        // Ensure owner owns this business
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // ✅ Server-side lock: parent business OR the branch itself hidden
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }
        if ($redirect = $this->guardNotHidden($branch, 'branch')) {
            return $redirect;
        }

        // Load the branch with relationships
        $branch->load(['country', 'region', 'city', 'area']);

        // Get all countries
        $countries = Country::active()->get();

        // Get regions for the branch's country
        $regions = Region::active()
            ->where('country_id', $branch->country_id)
            ->get();

        // Get cities for the branch's region
        $cities = City::active()
            ->where('region_id', $branch->region_id)
            ->get();

        // Get areas for the branch's city
        $areas = Area::active()
            ->where('city_id', $branch->city_id)
            ->get();

        return Inertia::render('Owner/Branches/Edit', [
            'business' => $business,
            'branch' => $branch,
            'countries' => $countries,
            'regions' => $regions,
            'cities' => $cities,
            'areas' => $areas,
        ]);
    }

    public function update(Request $request, Business $business, Branch $branch)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // ✅ Server-side lock: parent business OR the branch itself hidden
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }
        if ($redirect = $this->guardNotHidden($branch, 'branch')) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'country_id' => 'required|exists:countries,id',
            'region_id' => 'required|exists:regions,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180', // ✅ add this
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'status' => 'required|in:active,temporarily_unavailable,unlisted',
        ]);

        // If marked as primary, unset any other primary branches
        if ($request->boolean('is_primary')) {
            $business->branches()
                ->where('id', '!=', $branch->id)
                ->update(['is_primary' => false]);
        }

                $branch->update($validated);

        return redirect()->route('owner.businesses.branches.index', $business)
            ->with('success', 'Branch updated successfully.');
    }
    public function destroy(Business $business, Branch $branch)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // If this was the primary branch, set a new primary
        if ($branch->is_primary) {
            $newPrimary = $business->branches()
                ->where('id', '!=', $branch->id)
                ->first();

            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        $branch->delete();

        return redirect()->route('owner.businesses.branches.index', $business)
            ->with('success', 'Branch deleted successfully.');
    }
}