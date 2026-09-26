<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationController extends Controller
{
    // Countries
    public function countries()
    {
        $countries = Country::withCount('regions')->paginate(20);
        return Inertia::render('Admin/Locations/Countries', [
            'countries' => $countries,
        ]);
    }

    public function storeCountry(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:3|unique:countries',
            'is_active' => 'boolean',
        ]);

        Country::create($validated);

        return redirect()->back()->with('success', 'Country created successfully.');
    }

    public function updateCountry(Request $request, Country $country)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:3|unique:countries,code,' . $country->id,
            'is_active' => 'boolean',
        ]);

        $country->update($validated);

        return redirect()->back()->with('success', 'Country updated successfully.');
    }

    public function toggleCountry(Country $country)
    {
        $country->update(['is_active' => !$country->is_active]);

        return redirect()->back()->with(
            'success',
            $country->is_active
            ? "{$country->name} is now active."
            : "{$country->name} is now inactive."
        );
    }

    public function destroyCountry(Country $country)
    {
        // ✅ Prevent deletion when regions exist. Deleting would either
        //    cascade (data loss) or fail on FK constraint (500).
        if ($country->regions()->count() > 0) {
            return redirect()->back()->with(
                'error',
                "Cannot delete {$country->name} — it has regions under it. Delete or move those first."
            );
        }

        $country->delete();

        return redirect()->back()->with('success', "{$country->name} deleted successfully.");
    }

    // Regions
    public function regions(Request $request)
    {
        $query = Region::with('country');

        if ($request->country_id) {
            $query->where('country_id', $request->country_id);
        }

        $regions = $query->paginate(20);
        $countries = Country::active()->get();

        return Inertia::render('Admin/Locations/Regions', [
            'regions' => $regions,
            'countries' => $countries,
            'filters' => $request->only('country_id'),
        ]);
    }

    public function storeRegion(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255|unique:regions,name,NULL,id,country_id,' . $request->country_id,
            'is_active' => 'boolean',
        ]);

        Region::create($validated);

        return redirect()->back()->with('success', 'Region created successfully.');
    }

    public function updateRegion(Request $request, Region $region)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255|unique:regions,name,' . $region->id . ',id,country_id,' . $request->country_id,
            'is_active' => 'boolean',
        ]);

        $region->update($validated);

        return redirect()->back()->with('success', 'Region updated successfully.');
    }

    public function toggleRegion(Region $region)
    {
        $region->update(['is_active' => !$region->is_active]);

        return redirect()->back()->with(
            'success',
            $region->is_active
            ? "{$region->name} is now active."
            : "{$region->name} is now inactive."
        );
    }

    public function destroyRegion(Region $region)
    {
        // ✅ Prevent deletion when cities exist under this region.
        if ($region->cities()->count() > 0) {
            return redirect()->back()->with(
                'error',
                "Cannot delete {$region->name} — it has cities under it. Delete or move those first."
            );
        }

        $region->delete();

        return redirect()->back()->with('success', "{$region->name} deleted successfully.");
    }

    // Cities
    public function cities(Request $request)
    {
        $query = City::with('region.country');

        if ($request->region_id) {
            $query->where('region_id', $request->region_id);
        }

        $cities = $query->paginate(20);
        $regions = Region::active()->with('country')->get();

        return Inertia::render('Admin/Locations/Cities', [
            'cities' => $cities,
            'regions' => $regions,
            'filters' => $request->only('region_id'),
        ]);
    }

    public function storeCity(Request $request)
    {
        $validated = $request->validate([
            'region_id' => 'required|exists:regions,id',
            'name' => 'required|string|max:255|unique:cities,name,NULL,id,region_id,' . $request->region_id,
            'is_active' => 'boolean',
        ]);

        City::create($validated);

        return redirect()->back()->with('success', 'City created successfully.');
    }

    public function updateCity(Request $request, City $city)
    {
        $validated = $request->validate([
            'region_id' => 'required|exists:regions,id',
            'name' => 'required|string|max:255|unique:cities,name,' . $city->id . ',id,region_id,' . $request->region_id,
            'is_active' => 'boolean',
        ]);

        $city->update($validated);

        return redirect()->back()->with('success', 'City updated successfully.');
    }

    public function toggleCity(City $city)
    {
        $city->update(['is_active' => !$city->is_active]);

        return redirect()->back()->with(
            'success',
            $city->is_active
            ? "{$city->name} is now active."
            : "{$city->name} is now inactive."
        );
    }

    public function destroyCity(City $city)
    {
        // ✅ Prevent deletion when areas exist under this city.
        if ($city->areas()->count() > 0) {
            return redirect()->back()->with(
                'error',
                "Cannot delete {$city->name} — it has areas under it. Delete or move those first."
            );
        }

        $city->delete();

        return redirect()->back()->with('success', "{$city->name} deleted successfully.");
    }

    // Areas
    public function areas(Request $request)
    {
        $query = Area::with('city.region.country');

        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        $areas = $query->paginate(20);
        $cities = City::active()->with('region')->get();

        return Inertia::render('Admin/Locations/Areas', [
            'areas' => $areas,
            'cities' => $cities,
            'filters' => $request->only('city_id'),
        ]);
    }

    public function storeArea(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:255|unique:areas,name,NULL,id,city_id,' . $request->city_id,
            'is_active' => 'boolean',
        ]);

        Area::create($validated);

        return redirect()->back()->with('success', 'Area created successfully.');
    }

    public function updateArea(Request $request, Area $area)
    {
        $validated = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:255|unique:areas,name,' . $area->id . ',id,city_id,' . $request->city_id,
            'is_active' => 'boolean',
        ]);

        $area->update($validated);

        return redirect()->back()->with('success', 'Area updated successfully.');
    }

    public function toggleArea(Area $area)
    {
        $area->update(['is_active' => !$area->is_active]);

        return redirect()->back()->with(
            'success',
            $area->is_active
            ? "{$area->name} is now active."
            : "{$area->name} is now inactive."
        );
    }

    public function destroyArea(Area $area)
    {
        // ✅ Prevent deletion when branches reference this area.
        //    Branches have `area_id` FK; deleting would cascade or 500.
        if (\App\Models\Branch::where('area_id', $area->id)->exists()) {
            return redirect()->back()->with(
                'error',
                "Cannot delete {$area->name} — it's used by one or more branches. Reassign those branches first."
            );
        }

        $area->delete();

        return redirect()->back()->with('success', "{$area->name} deleted successfully.");
    }
}