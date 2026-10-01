<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ListingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Traits\GuardsHiddenItems;

class ServiceController extends Controller
{
    use GuardsHiddenItems;
    public function index(Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        $services = $business->services()->ordered()->get();

        return Inertia::render('Owner/Services/Index', [
            'business' => $business,
            'services' => $services,
        ]);
    }

    public function store(Request $request, Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // ✅ Server-side lock: can't add service to hidden business
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }

        // PHASE 11 / WAVE 1B — services are listing-owned. Attach to the
        // organization's primary listing.
        $listing = $business->primaryListing();
        if (!$listing) {
            return redirect()->back()->with('error', 'No listing found to attach services to.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:75',
        ]);

        $service = ListingService::create([
            'listing_id' => $listing->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $business->services()->count() + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'service' => $service]);
        }

        return redirect()->back()->with('success', 'Service added successfully.');
    }

    public function update(Request $request, Business $business, ListingService $service)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // ✅ Server-side lock
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }
        if ($redirect = $this->guardNotHidden($service, 'service')) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:75',
        ]);

        $service->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'service' => $service]);
        }

        return redirect()->back()->with('success', 'Service updated successfully.');
    }

        public function destroy(Business $business, ListingService $service)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        $service->delete();

        return redirect()->back()->with('success', 'Service deleted successfully.');
    }
}