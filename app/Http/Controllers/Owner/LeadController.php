<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * PHASE 11 / WAVE 1D — owner lead management is BUSINESS-SPECIFIC.
 *
 * Leads remain Business-owned. The Business comes from the route
 * (`owner/businesses/{business}/leads`) and is authorized against the
 * authenticated owner; it is never selected from the owner's Businesses.
 *
 * `listing_id` is display/eager-load information only and is deliberately NOT
 * part of authorization.
 */
class LeadController extends Controller
{
    private function authorizeBusiness(Business $business): void
    {
        abort_unless((int) $business->owner_id === (int) auth()->id(), 403);
    }

    private function authorizeLead(Business $business, Lead $lead): void
    {
        abort_unless((int) $lead->business_id === (int) $business->id, 403);
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeBusiness($business);

        // ✅ Check feature
        if (!$business->hasLeadCaptureFeature()) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'Lead capture requires Growth plan or higher.');
        }

        $query = Lead::where('business_id', $business->id)
            ->with(['listing:id,name']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: exclude archived
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

        // Stats
        $stats = [
            'total' => Lead::where('business_id', $business->id)->count(),
            'new' => Lead::where('business_id', $business->id)->where('status', Lead::STATUS_NEW)->count(),
            'read' => Lead::where('business_id', $business->id)->where('status', Lead::STATUS_READ)->count(),
            'replied' => Lead::where('business_id', $business->id)->where('status', Lead::STATUS_REPLIED)->count(),
            'archived' => Lead::where('business_id', $business->id)->where('status', Lead::STATUS_ARCHIVED)->count(),
            'this_week' => Lead::where('business_id', $business->id)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];

        return Inertia::render('Owner/Leads/Index', [
            'business' => $business,
            'leads' => $leads,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Business $business, Lead $lead)
    {
        $this->authorizeBusiness($business);
        $this->authorizeLead($business, $lead);

        // Auto-mark as read
        $lead->markAsRead();

        $lead->load(['business:id,name', 'listing:id,name']);

        return Inertia::render('Owner/Leads/Show', [
            'business' => $business,
            'lead' => $lead,
        ]);
    }

    public function updateStatus(Request $request, Business $business, Lead $lead)
    {
        $this->authorizeBusiness($business);
        $this->authorizeLead($business, $lead);

        $validated = $request->validate([
            'status' => 'required|in:new,read,replied,archived',
        ]);

        $lead->update(['status' => $validated['status']]);

        return back()->with('success', 'Lead status updated.');
    }

    public function updateNotes(Request $request, Business $business, Lead $lead)
    {
        $this->authorizeBusiness($business);
        $this->authorizeLead($business, $lead);

        $validated = $request->validate([
            'owner_notes' => 'nullable|string|max:2000',
        ]);

        $lead->update(['owner_notes' => $validated['owner_notes']]);

        return back()->with('success', 'Notes saved.');
    }

    public function destroy(Business $business, Lead $lead)
    {
        $this->authorizeBusiness($business);
        $this->authorizeLead($business, $lead);

        $lead->delete();

        return redirect()->route('owner.businesses.leads.index', $business)
            ->with('success', 'Lead deleted.');
    }
}
