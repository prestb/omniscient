<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\GuardsHiddenItems;

class CouponController extends Controller
{
    use GuardsHiddenItems;
    public function index()
    {
        $user = auth()->user();
        $businessIds = $user->businesses()->pluck('id');
        
        $coupons = Coupon::whereIn('business_id', $businessIds)
            ->with('business:id,name')
            ->latest()
            ->paginate(15);
        
        return Inertia::render('Owner/Coupons/Index', [
            'coupons' => $coupons,
            'canCreate' => $user->canAdd('coupons'),
            'remaining' => $user->remaining('coupons'),
        ]);
    }

    public function create()
    {
        $businesses = auth()->user()->businesses()->get(['id', 'name']);
        return Inertia::render('Owner/Coupons/Create', [
            'businesses' => $businesses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'code' => 'nullable|string|max:50|unique:coupons,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);
        
        // Verify ownership
        $business = Business::where('id', $validated['business_id'])
            ->where('owner_id', auth()->id())
            ->firstOrFail();
        
        // Check plan limit
        if (!auth()->user()->canAdd('coupons')) {
            return back()->withErrors(['error' => 'You have reached your coupon limit. Please upgrade.']);
        }
        
        $validated['user_id'] = auth()->id();
        
        Coupon::create($validated);
        
        return redirect()->route('owner.coupons.index')
            ->with('success', 'Coupon created successfully!');
    }

    public function update(Request $request, Coupon $coupon)
    {
        // Verify ownership
        abort_unless($coupon->user_id === auth()->id(), 403);
        
         // ✅ Server-side lock
        if ($redirect = $this->guardNotHidden($coupon, 'coupon')) {
            return $redirect;
        }
        
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'code' => 'nullable|string|max:50|unique:coupons,code,' . $coupon->id,
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);
        
        $coupon->update($validated);
        
        return back()->with('success', 'Coupon updated successfully!');
    }

    public function destroy(Coupon $coupon)
    {
        abort_unless($coupon->user_id === auth()->id(), 403);
        $coupon->delete();
        return back()->with('success', 'Coupon deleted.');
    }


    public function edit(Coupon $coupon)
{
    // Verify ownership
    abort_unless($coupon->user_id === auth()->id(), 403);
    
    // ✅ Server-side lock
    if ($redirect = $this->guardNotHidden($coupon, 'coupon')) {
        return $redirect;
    }
    

    $coupon->load('business:id,name');
    
    return Inertia::render('Owner/Coupons/Edit', [
        'coupon' => [
            'id' => $coupon->id,
            'business' => $coupon->business,
            'title' => $coupon->title,
            'description' => $coupon->description,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
            'min_purchase' => $coupon->min_purchase ? (float) $coupon->min_purchase : null,
            'max_discount' => $coupon->max_discount ? (float) $coupon->max_discount : null,
            'usage_limit' => $coupon->usage_limit,
            'usage_count' => $coupon->usage_count,
            'per_user_limit' => $coupon->per_user_limit,
            'starts_at' => $coupon->starts_at?->toIso8601String(),
            'expires_at' => $coupon->expires_at?->toIso8601String(),
            'is_active' => $coupon->is_active,
            'status' => $coupon->status, // from accessor
        ],
    ]);
}

public function analytics(Coupon $coupon)
{
    abort_unless($coupon->user_id === auth()->id(), 403);
    
    $redemptions = $coupon->redemptions()
        ->with('user:id,name,email')
        ->latest()
        ->paginate(20);
    
    $stats = [
        'total_redemptions' => $coupon->redemptions()->count(),
        'unique_users' => $coupon->redemptions()->distinct('user_id')->count('user_id'),
        'total_discount_given' => $coupon->redemptions()->sum('discount_amount'),
        'redemptions_today' => $coupon->redemptions()->whereDate('created_at', today())->count(),
        'redemptions_this_week' => $coupon->redemptions()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        'redemptions_this_month' => $coupon->redemptions()->whereMonth('created_at', now()->month)->count(),
    ];
    
    return Inertia::render('Owner/Coupons/Analytics', [
        'coupon' => [
            'id' => $coupon->id,
            'title' => $coupon->title,
            'code' => $coupon->code,
            'usage_count' => $coupon->usage_count,
            'usage_limit' => $coupon->usage_limit,
        ],
        'stats' => $stats,
        'redemptions' => $redemptions,
    ]);
}

}