<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessContact;
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
        
        $validated = $request->validate([
            'type' => 'required|in:phone,whatsapp,facebook,instagram,tiktok,twitter,youtube,linkedin,other',
            'value' => 'required|string|max:255',
            'is_primary' => 'boolean',
        ]);
        
        // If this is primary, unset other primary contacts
        if ($request->boolean('is_primary')) {
            $business->contacts()->update(['is_primary' => false]);
        }
        
        $validated['business_id'] = $business->id;
        $validated['sort_order'] = $business->contacts()->count() + 1;
        
        BusinessContact::create($validated);
        
        return redirect()->back()->with('success', 'Contact added successfully.');
    }

    public function update(Request $request, Business $business, BusinessContact $contact)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }
        
        $validated = $request->validate([
            'type' => 'required|in:phone,whatsapp,facebook,instagram,tiktok,twitter,youtube,linkedin,other',
            'value' => 'required|string|max:255',
            'is_primary' => 'boolean',
        ]);
        
        // If this is primary, unset other primary contacts
        if ($request->boolean('is_primary')) {
            $business->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }
        
        $contact->update($validated);
        
        return redirect()->back()->with('success', 'Contact updated successfully.');
    }

    public function destroy(Business $business, BusinessContact $contact)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }
        
        $contact->delete();
        
        return redirect()->back()->with('success', 'Contact deleted successfully.');
    }
}