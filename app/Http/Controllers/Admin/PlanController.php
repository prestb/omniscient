<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PlanController extends Controller
{
    /**
     * Feature definitions — used by frontend to render toggles
     */
    protected function featureDefinitions(): array
    {
        return [
            'whatsapp_button' => [
                'label' => 'WhatsApp Button',
                'description' => 'Show WhatsApp contact button on profile',
                'icon' => 'whatsapp',
                'min_tier' => 'starter',
            ],
            'phone_display' => [
                'label' => 'Phone Display',
                'description' => 'Show phone number on profile',
                'icon' => 'phone',
                'min_tier' => 'starter',
            ],
            'respond_to_reviews' => [
                'label' => 'Respond to Reviews',
                'description' => 'Allow owner to reply to customer reviews',
                'icon' => 'chat',
                'min_tier' => 'starter',
            ],
            'verified_badge' => [
                'label' => 'Verified Badge',
                'description' => 'Display blue verified badge',
                'icon' => 'shield-check',
                'min_tier' => 'growth',
            ],
            'featured_listing' => [
                'label' => 'Featured Listing',
                'description' => 'Priority placement in search + homepage',
                'icon' => 'star',
                'min_tier' => 'growth',
            ],
            'lead_capture' => [
                'label' => 'Lead Capture',
                'description' => 'Contact forms on business profile',
                'icon' => 'mail',
                'min_tier' => 'growth',
            ],
            'homepage_feature' => [
                'label' => 'Homepage Feature',
                'description' => 'Featured on the homepage spotlight',
                'icon' => 'home',
                'min_tier' => 'growth',
            ],
            'custom_branding' => [
                'label' => 'Custom Branding',
                'description' => 'Custom colors and logo on profile',
                'icon' => 'palette',
                'min_tier' => 'growth',
            ],
            'priority_support' => [
                'label' => 'Priority Support',
                'description' => 'Priority email + chat support',
                'icon' => 'headset',
                'min_tier' => 'growth',
            ],
            'coupons' => [
                'label' => 'Coupons',
                'description' => 'Create discount coupons',
                'icon' => 'ticket',
                'min_tier' => 'starter',
            ],
            'booking_system' => [
                'label' => 'Booking System',
                'description' => 'Allow customers to book appointments',
                'icon' => 'calendar',
                'min_tier' => 'premium',
            ],
        ];
    }

    /**
     * Analytics levels allowed
     */
    protected function analyticsLevels(): array
    {
        return [
            'none' => 'None',
            'basic' => 'Basic (views only)',
            'standard' => 'Standard (views + clicks)',
            'advanced' => 'Advanced (full breakdown)',
            'enterprise' => 'Enterprise (custom reports)',
        ];
    }

    public function index()
    {
        $plans = Plan::query()
            ->withCount('subscriptions')
            ->withCount(['subscriptions as active_subscriptions_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('sort_order')
            ->paginate(20);

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Plans/Create', [
            'featureDefinitions' => $this->featureDefinitions(),
            'analyticsLevels' => $this->analyticsLevels(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        $validated['slug'] = Str::slug($validated['slug'] ?? $validated['name']);
        
        // Ensure slug is unique
        $originalSlug = $validated['slug'];
        $count = 1;
        while (Plan::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $count++;
        }

        // Set defaults
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured', false);
        $validated['is_popular'] = $request->boolean('is_popular', false);

        // Auto-calculate yearly discount if not provided
        if (empty($validated['yearly_discount_percentage']) 
            && !empty($validated['price_monthly']) 
            && !empty($validated['price_yearly'])) {
            $monthlyTotal = $validated['price_monthly'] * 12;
            $validated['yearly_discount_percentage'] = round((($monthlyTotal - $validated['price_yearly']) / $monthlyTotal) * 100, 2);
        }

        Plan::create($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan)
    {
        return Inertia::render('Admin/Plans/Edit', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'tier' => $plan->tier,
                'description' => $plan->description,
                'tagline' => $plan->tagline,
                'badge_text' => $plan->badge_text,
                'badge_color' => $plan->badge_color,
                'price_monthly' => (float) $plan->price_monthly,
                'price_yearly' => (float) $plan->price_yearly,
                'yearly_discount_percentage' => (float) $plan->yearly_discount_percentage,
                'currency' => $plan->currency ?? 'XAF',
                'trial_days' => (int) $plan->trial_days,
                'max_businesses' => $plan->max_businesses,
                'max_branches' => $plan->max_branches,
                'max_services' => $plan->max_services,
                'max_images' => $plan->max_images,
                'max_coupons' => $plan->max_coupons,
                'max_staff' => $plan->max_staff,
                'features' => $plan->features ?? [],
                'feature_list' => $plan->feature_list ?? [],
                'is_active' => (bool) $plan->is_active,
                'is_featured' => (bool) $plan->is_featured,
                'is_popular' => (bool) $plan->is_popular,
                'sort_order' => (int) $plan->sort_order,
                'subscriptions_count' => $plan->subscriptions()->count(),
            ],
            'featureDefinitions' => $this->featureDefinitions(),
            'analyticsLevels' => $this->analyticsLevels(),
        ]);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $this->validatePlan($request, $plan);

        $validated['slug'] = Str::slug($validated['slug'] ?? $validated['name']);
        
        // Ensure slug is unique (excluding current plan)
        $originalSlug = $validated['slug'];
        $count = 1;
        while (Plan::where('slug', $validated['slug'])->where('id', '!=', $plan->id)->exists()) {
            $validated['slug'] = $originalSlug . '-' . $count++;
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured', false);
        $validated['is_popular'] = $request->boolean('is_popular', false);

        // Auto-calculate yearly discount
        if (empty($validated['yearly_discount_percentage']) 
            && !empty($validated['price_monthly']) 
            && !empty($validated['price_yearly'])) {
            $monthlyTotal = $validated['price_monthly'] * 12;
            $validated['yearly_discount_percentage'] = round((($monthlyTotal - $validated['price_yearly']) / $monthlyTotal) * 100, 2);
        }

        $plan->update($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete plan with existing subscriptions. Deactivate it instead.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }

    public function toggleActive(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);

        return redirect()->back()
            ->with('success', 'Plan status updated successfully.');
    }

    public function duplicate(Plan $plan)
    {
        $newPlan = $plan->replicate();
        $newPlan->name = $plan->name . ' (Copy)';
        $newPlan->slug = Str::slug($newPlan->name);
        
        $originalSlug = $newPlan->slug;
        $count = 1;
        while (Plan::where('slug', $newPlan->slug)->exists()) {
            $newPlan->slug = $originalSlug . '-' . $count++;
        }
        
        $newPlan->is_active = false; // Default inactive for safety
        $newPlan->save();

        return redirect()->route('admin.plans.edit', $newPlan->id)
            ->with('success', 'Plan duplicated. Review and activate when ready.');
    }

    /**
     * Validation rules for store/update
     */
    protected function validatePlan(Request $request, ?Plan $plan = null): array
    {
        $featureKeys = array_keys($this->featureDefinitions());
        $analyticsLevels = array_keys($this->analyticsLevels());

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('plans', 'name')->ignore($plan?->id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'tier' => ['required', 'string', 'in:free,starter,growth,premium'],
            'description' => 'nullable|string|max:1000',
            'tagline' => 'nullable|string|max:255',
            'badge_text' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|in:primary,purple,gold,green,red',
            
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'yearly_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'currency' => 'required|string|max:3',
            'trial_days' => 'nullable|integer|min:0|max:365',
            
            'max_businesses' => 'required|integer|min:-1',
            'max_branches' => 'required|integer|min:-1',
            'max_services' => 'required|integer|min:-1',
            'max_images' => 'required|integer|min:-1',
            'max_coupons' => 'required|integer|min:-1',
            'max_staff' => 'required|integer|min:-1',
            
            'features' => 'nullable|array',
            'feature_list' => 'nullable|array',
            'feature_list.*' => 'nullable|string|max:255',
            
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];

        // Ensure features only include known keys
        $validated = $request->validate($rules);

        // Normalize features (only true/false values, only known keys)
        $features = [];
        foreach ($featureKeys as $key) {
            $features[$key] = (bool) ($validated['features'][$key] ?? false);
        }
        
        // Add analytics level
        if (isset($validated['features']['analytics'])) {
            $level = $validated['features']['analytics'];
            $features['analytics'] = in_array($level, $analyticsLevels) ? $level : 'basic';
        } else {
            $features['analytics'] = 'basic';
        }

        $validated['features'] = $features;

        // Clean up feature_list (remove empty entries)
        if (isset($validated['feature_list'])) {
            $validated['feature_list'] = array_values(array_filter($validated['feature_list'], fn($item) => !empty(trim($item))));
        }

        return $validated;
    }
}