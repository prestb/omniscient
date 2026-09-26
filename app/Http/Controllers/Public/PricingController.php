<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Inertia\Inertia;

class PricingController extends Controller
{
    public function index()
    {
        $plans = Plan::active()
            ->ordered()
            ->get()
            ->map(function ($plan) {
                return [
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
                    'is_free' => $plan->isFree(),
                    'is_featured' => (bool) $plan->is_featured,
                    'is_popular' => (bool) $plan->is_popular,
                    'feature_list' => $plan->feature_list ?? [],
                    'features' => $plan->features ?? [],
                    'limits' => [
                        'businesses' => $plan->max_businesses,
                        'branches' => $plan->max_branches,
                        'services' => $plan->max_services,
                        'images' => $plan->max_images,
                        'coupons' => $plan->max_coupons,
                        'staff' => $plan->max_staff,
                    ],
                ];
            });

        return Inertia::render('Public/Pricing', [
            'plans' => $plans,
        ]);
    }
}