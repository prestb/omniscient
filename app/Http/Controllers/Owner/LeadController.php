<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $business = $user->businesses()->first();

        if (!$business) {
            return redirect()->route('owner.dashboard')
                ->with('error', 'You don\'t have a business yet.');
        }

        // ✅ Check feature
        if (!$business->hasLeadCaptureFeature()) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'Lead capture requires Growth plan or higher.');
        }

        $query = Lead::where('business_id', $business->id)
            ->with(['branch:id,name']);

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

    public function show(Lead $lead)
    {
        $user = auth()->user();
        $business = $user->businesses()->first();

        if (!$business || $lead->business_id !== $business->id) {
            abort(403);
        }

        // Auto-mark as read
        $lead->markAsRead();

        $lead->load(['business:id,name', 'branch:id,name']);

        return Inertia::render('Owner/Leads/Show', [
            'business' => $business,
            'lead' => $lead,
        ]);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $user = auth()->user();
        $business = $user->businesses()->first();

        if (!$business || $lead->business_id !== $business->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:new,read,replied,archived',
        ]);

        $lead->update(['status' => $validated['status']]);

        return back()->with('success', 'Lead status updated.');
    }

    public function updateNotes(Request $request, Lead $lead)
    {
        $user = auth()->user();
        $business = $user->businesses()->first();

        if (!$business || $lead->business_id !== $business->id) {
            abort(403);
        }

        $validated = $request->validate([
            'owner_notes' => 'nullable|string|max:2000',
        ]);

        $lead->update(['owner_notes' => $validated['owner_notes']]);

        return back()->with('success', 'Notes saved.');
    }

    public function destroy(Lead $lead)
    {
        $user = auth()->user();
        $business = $user->businesses()->first();

        if (!$business || $lead->business_id !== $business->id) {
            abort(403);
        }

        $lead->delete();

        return redirect()->route('owner.leads.index')
            ->with('success', 'Lead deleted.');
    }
}