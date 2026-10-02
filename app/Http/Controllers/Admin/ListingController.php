<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Listing;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * PHASE 19C — ADMIN LISTINGS (read-only).
 *
 * Listing is the canonical discoverable entity, but Admin had no Listings
 * surface at all: it could only see Businesses. An administrator therefore could
 * not inspect what people actually discover, and could not see a Business-less
 * Professional without going through an owner.
 *
 * This is an INSPECTION surface. It adds no editing, no moderation workflow, no
 * permissions model and no new source of truth — it reads the existing Listing
 * model and its existing relationships.
 *
 * The domain it reflects:
 *
 *     Business          (optional organization)
 *         | optionally groups
 *     Listing           (canonical discoverable entity)
 *         | optionally has
 *     Location
 *
 * A Listing with business_id = NULL is a first-class record ("Independent"), NOT
 * broken or orphaned data.
 */
class ListingController extends Controller
{
    public function index(Request $request)
    {
        $query = Listing::query()->with([
            'owner:id,name,email',
            'business:id,name,slug',
            'location:id,address,city_id',
            'location.city:id,name',
        ]);

        // Search by the things an administrator actually identifies a Listing by.
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($oq) use ($search) {
                        $oq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('business', fn($bq) => $bq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Business-grouped vs Independent. `business_id = NULL` is a legitimate
        // state, so it is an explicit, first-class filter rather than a gap.
        if ($request->input('affiliation') === 'business') {
            $query->whereNotNull('business_id');
        } elseif ($request->input('affiliation') === 'independent') {
            $query->whereNull('business_id');
        }

        // Location presence.
        if ($request->input('location') === 'with') {
            $query->whereNotNull('location_id');
        } elseif ($request->input('location') === 'without') {
            $query->whereNull('location_id');
        }

        $listings = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Listings/Index', [
            'listings' => $listings,
            'filters' => $request->only(['search', 'type', 'status', 'affiliation', 'location']),
            'listingTypes' => $this->listingTypes(),
            'statuses' => ['draft', 'submitted', 'approved', 'published', 'inactive', 'rejected', 'suspended'],
            'stats' => [
                'total' => Listing::count(),
                'published' => Listing::where('status', 'published')->count(),
                // Business-less Professionals are a real, countable segment.
                'independent' => Listing::whereNull('business_id')->count(),
                'with_location' => Listing::whereNotNull('location_id')->count(),
            ],
        ]);
    }

    public function show(Listing $listing)
    {
        $listing->load([
            'owner:id,name,email',
            'business:id,name,slug,status',
            'location.city:id,name',
            'location.region:id,name',
            'location.country:id,name',
            'categories:id,name',
        ]);

        return Inertia::render('Admin/Listings/Show', [
            // Explicit serialization — no full Eloquent dump.
            'listing' => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
                'listing_type' => $listing->type,
                'status' => $listing->status,
                'description' => $listing->description,
                'created_at' => $listing->created_at?->toIso8601String(),
                'published_at' => $listing->published_at?->toIso8601String(),
                // The canonical public destination, so an administrator can move
                // straight from the administrative record to what people see.
                'public_url' => '/listing/' . $listing->slug,
                'is_independent' => $listing->business_id === null,
            ],
            'owner' => $listing->owner ? [
                'id' => $listing->owner->id,
                'name' => $listing->owner->name,
                'email' => $listing->owner->email,
            ] : null,
            // Present only when the Listing is grouped under an organization.
            'business' => $listing->business ? [
                'id' => $listing->business->id,
                'name' => $listing->business->name,
                'slug' => $listing->business->slug,
                'status' => $listing->business->status,
                'public_url' => '/business/' . $listing->business->slug,
            ] : null,
            'location' => $listing->location ? [
                'id' => $listing->location->id,
                'address' => $listing->location->address,
                'city' => $listing->location->city?->name,
                'region' => $listing->location->region?->name,
                'country' => $listing->location->country?->name,
            ] : null,
            'categories' => $listing->categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
        ]);
    }

    /**
     * The Listing types the domain actually supports. Read from the model's own
     * constants so Admin cannot drift from the canonical vocabulary.
     */
    private function listingTypes(): array
    {
        return ['business', 'professional', 'store'];
    }
}
