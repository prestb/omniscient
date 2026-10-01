<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingService;
use App\Traits\GuardsHiddenItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * PHASE 11 / WAVE 1D-3 — LISTING-OWNED SERVICES.
 *
 * A service belongs to a LISTING:
 *
 *     Listing -> listing_services
 *
 * The Listing is supplied explicitly by the route
 * (`/owner/listings/{listing}/services`) and authorized through
 * {@see \App\Policies\ListingPolicy}. This controller never resolves a Listing
 * from a Business — no `primaryListing()`, no `listings()->first()`, and no
 * other arbitrary selection.
 */
class ServiceController extends Controller
{
    use GuardsHiddenItems;

    public function index(Listing $listing)
    {
        Gate::authorize('update', $listing);

        $services = $listing->services()->ordered()->get();

        return Inertia::render('Owner/Services/Index', [
            'listing' => $listing->only(['id', 'name', 'slug', 'type', 'status', 'business_id']),
            'services' => $services,
        ]);
    }

    public function store(Request $request, Listing $listing)
    {
        Gate::authorize('update', $listing);

        // ✅ Server-side lock: can't add services to a hidden listing
        if ($redirect = $this->guardNotHidden($listing, 'listing')) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:75',
        ]);

        $service = ListingService::create([
            'listing_id' => $listing->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $listing->services()->count() + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'service' => $service]);
        }

        return redirect()->back()->with('success', 'Service added successfully.');
    }

    public function update(Request $request, Listing $listing, ListingService $service)
    {
        Gate::authorize('update', $listing);

        // ✅ Server-side lock
        if ($redirect = $this->guardNotHidden($listing, 'listing')) {
            return $redirect;
        }
        if ($redirect = $this->guardNotHidden($service, 'service')) {
            return $redirect;
        }

        // The service must belong to the Listing named in the route.
        abort_unless((int) $service->listing_id === (int) $listing->id, 404);

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

    public function destroy(Listing $listing, ListingService $service)
    {
        Gate::authorize('update', $listing);

        abort_unless((int) $service->listing_id === (int) $listing->id, 404);

        $service->delete();

        return redirect()->back()->with('success', 'Service deleted successfully.');
    }
}
