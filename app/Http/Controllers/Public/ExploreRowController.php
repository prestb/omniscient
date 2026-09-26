<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ExploreService;
use Illuminate\Http\Request;

class ExploreRowController extends Controller
{
    public function __construct(private ExploreService $explore)
    {
    }

    /**
     * GET /api/explore/row?category=15&city_id=1
     */
    public function show(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|integer|exists:categories,id',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        $businesses = $this->explore->fetchRowBusinesses(
            (int) $validated['category'],
            isset($validated['city_id']) ? (int) $validated['city_id'] : null,
        );

        return response()->json(['businesses' => $businesses]);
    }
}