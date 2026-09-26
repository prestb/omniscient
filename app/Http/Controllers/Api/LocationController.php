<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function countries()
    {
        try {
            $countries = Country::active()->get(['id', 'name', 'code']);
            return response()->json($countries);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function regions(Request $request)
    {
        try {
            $query = Region::active();
            
            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }
            
            $regions = $query->get(['id', 'country_id', 'name']);
            
            return response()->json($regions);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cities(Request $request)
    {
        try {
            $query = City::active();
            
            if ($request->filled('region_id')) {
                $query->where('region_id', $request->region_id);
            }
            
            $cities = $query->get(['id', 'region_id', 'name']);
            
            return response()->json($cities);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function areas(Request $request)
    {
        try {
            $query = Area::active();
            
            if ($request->filled('city_id')) {
                $query->where('city_id', $request->city_id);
            }
            
            $areas = $query->get(['id', 'city_id', 'name']);
            
            return response()->json($areas);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}