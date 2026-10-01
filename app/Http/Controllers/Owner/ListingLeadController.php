<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Listing;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * PHASE 12B — LISTING-ATTRIBUTED OWNER INQUIRY ACCESS.
 *
 * The authoritative relationship for access is:
 *
 *     Inquiry -> Listing -> Listing owner
 *
 * Business is OPTIONAL context and is deliberately NOT part of authorization.
 * Before this controller, owner lead management was reachable only through
 * `owner/businesses/{business}/leads`, so an inquiry belonging to a Listing with
 * `business_id = NULL` could not be reached by its own owner at all.
 *
 * Reuses the existing `Owner/Leads/*` Inertia pages rather than introducing a
 * second lead-management product.
 */
class ListingLeadController extends Controller
{
    /**
     * Authorization derives from Listing ownership only. No representative
     * Listing is ever selected, and a submitted owner id is never trusted.
     */
    private function authorizeListing(Listing $listing): void
    {
        abort_unless((int) $listing->owner_id === (int) auth()->id(), 403);
    }

    /** A lead may only be handled through the Listing that generated it. */
    private function authorizeLead(Listing $listing, Lead $lead): void
    {
        abort_unless((int) $lead->listing_id === (int) $listing->id, 403);
    }

    public function index(Request $request, Listing $listing)
    {
        $this->authorizeListing($listing);

        $query = Lead::where('listing_id', $listing->id)
            ->with(['listing:id,name', 'business:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', Lead::STATUS_ARCHIVED);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $leads = $query->latest()->paginate(20)->withQueryString();

        $base = Lead::where('listing_id', $listing->id);

        $stats = [
            'total' => (clone $base)->count(),
            'new' => (clone $base)->where('status', Lead::STATUS_NEW)->count(),
            'read' => (clone $base)->where('status', Lead::STATUS_READ)->count(),
            'replied' => (clone $base)->where('status', Lead::STATUS_REPLIED)->count(),
            'archived' => (clone $base)->where('status', Lead::STATUS_ARCHIVED)->count(),
            'this_week' => (clone $base)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];

        return Inertia::render('Owner/Leads/Index', [
            // `business` is null for a Business-less Listing; the page resolves
            // its title and links from `listing` in that case.
            'business' => $listing->business,
            'listing' => $listing->only(['id', 'name', 'slug']),
            'leads' => $leads,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Listing $listing, Lead $lead)
    {
        $this->authorizeListing($listing);
        $this->authorizeLead($listing, $lead);

        $lead->markAsRead();
        $lead->load(['business:id,name', 'listing:id,name,slug']);

        return Inertia::render('Owner/Leads/Show', [
            'business' => $listing->business,
            'listing' => $listing->only(['id', 'name', 'slug']),
            'lead' => $lead,
        ]);
    }

    public function updateStatus(Request $request, Listing $listing, Lead $lead)
    {
        $this->authorizeListing($listing);
        $this->authorizeLead($listing, $lead);

        $validated = $request->validate([
            'status' => 'required|in:new,read,replied,archived',
        ]);

        $lead->update(['status' => $validated['status']]);

        return back()->with('success', 'Inquiry status updated.');
    }

    public function updateNotes(Request $request, Listing $listing, Lead $lead)
    {
        $this->authorizeListing($listing);
        $this->authorizeLead($listing, $lead);

        $validated = $request->validate([
            'owner_notes' => 'nullable|string|max:2000',
        ]);

        $lead->update(['owner_notes' => $validated['owner_notes']]);

        return back()->with('success', 'Notes saved.');
    }

    public function destroy(Listing $listing, Lead $lead)
    {
        $this->authorizeListing($listing);
        $this->authorizeLead($listing, $lead);

        $lead->delete();

        return redirect()->route('owner.listings.leads.index', $listing)
            ->with('success', 'Inquiry deleted.');
    }
}
