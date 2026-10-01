<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * PHASE 11 / WAVE 1D-3 — LISTING-OWNED CONTACTS.
 *
 * A contact belongs to a LISTING:
 *
 *     Listing -> listing_contacts
 *
 * The Listing is supplied explicitly by the route
 * (`/owner/listings/{listing}/contacts`) and authorized through
 * {@see \App\Policies\ListingPolicy}. This controller never resolves a Listing
 * from a Business, and it does not authorize through Business ownership.
 */
class ContactController extends Controller
{
    public function index(Listing $listing)
    {
        Gate::authorize('update', $listing);

        $contacts = $listing->contacts()->orderBy('sort_order')->get();

        return Inertia::render('Owner/Contacts/Index', [
            'listing' => $listing->only(['id', 'name', 'slug', 'type', 'status', 'business_id']),
            'contacts' => $contacts,
        ]);
    }

    public function store(Request $request, Listing $listing)
    {
        Gate::authorize('update', $listing);

        $validated = $request->validate([
            'type' => 'required|in:phone,whatsapp,facebook,instagram,tiktok,twitter,youtube,linkedin,other',
            'value' => 'required|string|max:255',
            'is_primary' => 'boolean',
        ]);

        // If this is primary, unset other primary contacts FOR THIS LISTING
        if ($request->boolean('is_primary')) {
            ListingContact::where('listing_id', $listing->id)->update(['is_primary' => false]);
        }

        $validated['listing_id'] = $listing->id;
        $validated['sort_order'] = $listing->contacts()->count() + 1;

        ListingContact::create($validated);

        return redirect()->back()->with('success', 'Contact added successfully.');
    }

    public function update(Request $request, Listing $listing, ListingContact $contact)
    {
        Gate::authorize('update', $listing);

        // The contact must belong to the Listing named in the route. Owning the
        // Business that contains both Listings is NOT sufficient.
        abort_unless((int) $contact->listing_id === (int) $listing->id, 404);

        $validated = $request->validate([
            'type' => 'required|in:phone,whatsapp,facebook,instagram,tiktok,twitter,youtube,linkedin,other',
            'value' => 'required|string|max:255',
            'is_primary' => 'boolean',
        ]);

        // If this is primary, unset other primary contacts on the same listing
        if ($request->boolean('is_primary')) {
            ListingContact::where('listing_id', $contact->listing_id)
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }

        $contact->update($validated);

        return redirect()->back()->with('success', 'Contact updated successfully.');
    }

    public function destroy(Listing $listing, ListingContact $contact)
    {
        Gate::authorize('update', $listing);

        abort_unless((int) $contact->listing_id === (int) $listing->id, 404);

        $contact->delete();

        return redirect()->back()->with('success', 'Contact deleted successfully.');
    }
}
