<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ListingContact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index(Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }
        
        $contacts = $business->contacts()->orderBy('sort_order')->get();
        
        return Inertia::render('Owner/Contacts/Index', [
            'business' => $business,
            'contacts' => $contacts,
        ]);
    }

    public function store(Request $request, Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // PHASE 11 / WAVE 1B — contacts are listing-owned. Attach to the
        // organization's primary listing.
        $listing = $business->primaryListing();
        if (!$listing) {
            return redirect()->back()->with('error', 'No listing found to attach contacts to.');
        }

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
        $validated['sort_order'] = $business->contacts()->count() + 1;
        
        ListingContact::create($validated);
        
        return redirect()->back()->with('success', 'Contact added successfully.');
    }

    public function update(Request $request, Business $business, ListingContact $contact)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }
        
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

    public function destroy(Business $business, ListingContact $contact)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }
        
        $contact->delete();
        
        return redirect()->back()->with('success', 'Contact deleted successfully.');
    }
}