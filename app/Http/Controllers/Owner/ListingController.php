<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListingRequest;
use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Location;
use App\Support\ListingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * PHASE 11 / WAVE 1D-2 — the Listing lifecycle.
 *
 * A Listing is created directly as the discoverable entity. Business
 * association and Location are optional. This controller never depends on
 * `Business::primaryListing()` and never infers a Listing from a Business.
 */
class ListingController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Listing::class);

        $listings = Listing::query()
            ->where('owner_id', $request->user()->id)
            ->with(['business:id,name,slug', 'location.city'])
            ->withCount(['reviews', 'images', 'leads'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // PHASE 14 — Listing-scoped completeness. Calculated dynamically and
        // never persisted. It is a quality/owner-guidance signal only and
        // deliberately does NOT reach search or ranking.
        $completeness = new \App\Services\ListingCompletenessService();

        $listings->getCollection()->transform(function ($listing) use ($completeness) {
            $listing->setAttribute('completeness', $completeness->calculate($listing));

            return $listing;
        });

        // PHASE 21B-F - the same canonical shape the dashboard uses. `can_create`
        // answers the question the UI actually asks, so Vue never reconstructs
        // plan or limit rules. Plan names and prices are not exposed here.
        $user = $request->user();
        $plan = $user->getCurrentPlan();

        return Inertia::render('Owner/Listings/Index', [
            'listings' => $listings,
            'quota' => [
                'current' => \App\Models\Listing::countFor($user),
                'limit' => $plan?->max_listings,
                'can_create' => $user->canAdd('listings'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Listing::class);

        return Inertia::render('Owner/Listings/Create', [
            'types' => $this->typeOptions(),
            'businesses' => $this->ownerBusinesses($request),
            'locations' => $this->ownerLocations($request),
        ]);
    }

    public function store(ListingRequest $request)
    {
        Gate::authorize('create', Listing::class);

        $validated = $request->validated();
        $publish = (bool) ($validated['publish'] ?? false);

        $listing = Listing::create([
            'owner_id' => $request->user()->id,
            'business_id' => $validated['business_id'] ?? null,
            'location_id' => $validated['location_id'] ?? null,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $publish ? Listing::STATUS_PUBLISHED : Listing::STATUS_DRAFT,
            'published_at' => $publish ? now() : null,
        ]);

        return redirect()
            ->route('owner.listings.edit', $listing)
            ->with('success', $publish
                ? 'Listing published successfully.'
                : 'Listing created as a draft.');
    }

    public function edit(Request $request, Listing $listing)
    {
        Gate::authorize('update', $listing);

        $listing->load(['business:id,name,slug', 'location.city', 'categories:id,name']);

        return Inertia::render('Owner/Listings/Edit', [
            // PHASE 21B-E - the edit page is where an owner can act on completeness,
            // so it receives the same Listing-scoped score the index shows, including
            // each missing item's label and hint. The service stays the single source
            // of truth; no scoring logic is duplicated here.
            'completeness' => (new \App\Services\ListingCompletenessService())->calculate($listing),
            'listing' => $listing,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'types' => $this->typeOptions(),
            'businesses' => $this->ownerBusinesses($request),
            'locations' => $this->ownerLocations($request),
        ]);
    }

    public function update(ListingRequest $request, Listing $listing)
    {
        Gate::authorize('update', $listing);

        $validated = $request->validated();

        $listing->update([
            'business_id' => $validated['business_id'] ?? null,
            'location_id' => $validated['location_id'] ?? null,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // PHASE 11 / WAVE 1D-3 — categories are LISTING-owned
        // (`listing_categories`). The Listing named in the route is the only
        // entity whose categories this can touch. ListingPolicy already
        // authorized ownership above.
        if (array_key_exists('categories', $validated)) {
            $listing->categories()->sync($validated['categories'] ?? []);
        }

        return redirect()
            ->route('owner.listings.edit', $listing)
            ->with('success', 'Listing updated successfully.');
    }

    /**
     * Publish an owned Listing so it becomes publicly addressable at
     * /listing/{slug}. Status is the existing authority (Wave 1D §22).
     */
    public function publish(Request $request, Listing $listing)
    {
        Gate::authorize('publish', $listing);

        $listing->update([
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => $listing->published_at ?? now(),
            'hidden_at' => null,
        ]);

        return redirect()->back()->with('success', 'Listing published.');
    }

    public function unpublish(Request $request, Listing $listing)
    {
        Gate::authorize('publish', $listing);

        $listing->update(['status' => Listing::STATUS_DRAFT]);

        return redirect()->back()->with('success', 'Listing unpublished.');
    }

    public function destroy(Request $request, Listing $listing)
    {
        Gate::authorize('delete', $listing);

        $listing->delete();

        return redirect()
            ->route('owner.listings.index')
            ->with('success', 'Listing deleted.');
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function typeOptions(): array
    {
        return collect(ListingType::cases())
            ->map(fn($type) => ['value' => $type->value, 'label' => $type->label()])
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function ownerBusinesses(Request $request): array
    {
        return Business::where('owner_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($business) => ['id' => $business->id, 'name' => $business->name])
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function ownerLocations(Request $request): array
    {
        // PHASE 22A - the picker returns LOCATIONS OWNED BY THIS ACCOUNT.
        //
        // It previously filtered by the owner's Business ids, so a
        // Business-less Professional saw an empty list and the field was
        // inert. Ownership is the account, so Business membership must never
        // restrict the result. Business is returned only as UI context.
        return Location::where('owner_id', $request->user()->id)
            ->with('business:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'business_id'])
            ->map(fn($location) => [
                'id' => $location->id,
                'name' => $location->name ?: ('Location #' . $location->id),
                'business_id' => $location->business_id,
                'business_name' => $location->business?->name,
            ])
            ->all();
    }
}
