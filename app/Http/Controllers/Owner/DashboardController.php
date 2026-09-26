<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BusinessAnalytics;
use App\Models\Business;
use App\Models\Subscription;
use Illuminate\Http\Request;      // ✅ ADD THIS
use App\Services\BusinessCompletenessService;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {

        $user = Auth::user();
        // ✅ Get all businesses for the selector
        $businesses = $user->businesses()
            ->whereIn('status', ['approved', 'published'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'slug', 'status', 'created_at', 'owner_id']);

        if ($businesses->isEmpty()) {
            return Inertia::render('Owner/Dashboard', [
                'user' => $user,
                'hasBusiness' => false,
                'businesses' => [],
                'selectedBusinessId' => null,
            ]);
        }

        // ✅ Determine selected business
        $selectedBusinessId = $request->input('business');

        if ($selectedBusinessId) {
            $business = $businesses->firstWhere('id', $selectedBusinessId);
        }

        // Fallback to most recent
        if (!isset($business) || !$business) {
            $business = $businesses->first();
        }

        // Add status badge and label
        $business->status_badge = $this->getStatusBadge($business->status);
        $business->status_label = ucfirst(str_replace('_', ' ', $business->status));

        // Get primary branch
        if ($business->primaryBranch) {
            $business->primary_branch = $business->primaryBranch;
            $business->primary_branch->full_address = $business->primaryBranch->full_address;
        }

        // Get active subscription
        // ✅ User-based active subscription
        $activeSubscription = $user->active_subscription;

        if ($activeSubscription) {
            $activeSubscription->days_remaining = $activeSubscription->days_remaining;
        }

        // ============== ANALYTICS ==============
        $analyticsStats = BusinessAnalytics::getTotalStats($business->id);
        $analyticsTrend = BusinessAnalytics::getStats($business->id, 30);
        $comparison = BusinessAnalytics::getPeriodComparison($business->id, 30);

        $chartData = [
            'labels' => $analyticsTrend->pluck('date')->map(function ($date) {
                return $date->format('M d');
            })->toArray(),
            'views' => $analyticsTrend->pluck('views')->toArray(),
            'unique_visitors' => $analyticsTrend->pluck('unique_visitors')->toArray(),
        ];

        // ✅ NEW: Click breakdown for doughnut chart
        $clickBreakdown = [
            'phone' => (int) ($analyticsStats->total_phone_clicks ?? 0),
            'whatsapp' => (int) ($analyticsStats->total_whatsapp_clicks ?? 0),
            'website' => (int) ($analyticsStats->total_website_clicks ?? 0),
            'directions' => (int) ($analyticsStats->total_direction_clicks ?? 0),
            'social' => (int) ($analyticsStats->total_social_clicks ?? 0),
        ];

        // ============== RECENT REVIEWS ==============
        $recentReviews = $business->allReviews()
            ->with('user:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'content' => \Str::limit($review->content, 100),
                    'user_name' => $review->user?->name ?? 'Anonymous',
                    'status' => $review->status,
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            });

        $reviewStats = [
            'total' => $business->allReviews()->count(),
            'average' => round($business->reviews()->avg('rating') ?? 0, 1),
            'pending' => $business->allReviews()->where('status', 'pending')->count(),
            'approved' => $business->allReviews()->where('status', 'approved')->count(),
        ];

        // ============== RECENT LEADS ==============
        $recentLeads = collect();
        $leadStats = ['total' => 0, 'new' => 0];

        // Only fetch if business has lead_capture feature
        if ($business->hasLeadCaptureFeature()) {
            $recentLeads = \App\Models\Lead::where('business_id', $business->id)
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'name' => $lead->name,
                        'email' => $lead->email,
                        'phone' => $lead->phone,
                        'subject' => $lead->subject,
                        'message' => \Str::limit($lead->message, 80),
                        'status' => $lead->status,
                        'created_at' => $lead->created_at->diffForHumans(),
                    ];
                });

            $leadStats = [
                'total' => \App\Models\Lead::where('business_id', $business->id)->count(),
                'new' => \App\Models\Lead::where('business_id', $business->id)->where('status', 'new')->count(),
            ];
        }

        // ============== RECENT COUPONS ==============
        $recentCoupons = collect();
        $couponStats = ['active' => 0, 'total_redemptions' => 0, 'total_discount' => 0];

        if ($business->canUseCoupons()) {
            $recentCoupons = \App\Models\Coupon::where('business_id', $business->id)
                ->latest()
                ->take(3)
                ->get()
                ->map(function ($coupon) {
                    return [
                        'id' => $coupon->id,
                        'title' => $coupon->title,
                        'code' => $coupon->code,
                        'discount_type' => $coupon->discount_type,
                        'discount_value' => (float) $coupon->discount_value,
                        'usage_count' => $coupon->usage_count,
                        'usage_limit' => $coupon->usage_limit,
                        'is_active' => $coupon->is_active,
                        'expires_at' => $coupon->expires_at?->format('M d'),
                    ];
                });

            $couponStats = [
                'active' => \App\Models\Coupon::where('business_id', $business->id)->where('is_active', true)->count(),
                'total_redemptions' => \App\Models\Coupon::where('business_id', $business->id)->sum('usage_count'),
                'total_discount' => (float) \App\Models\CouponRedemption::whereHas('coupon', function ($q) use ($business) {
                    $q->where('business_id', $business->id);
                })->sum('discount_amount'),
            ];
        }

        // ============== PLAN USAGE ==============
// ============== PLAN USAGE ==============
        $plan = null;
        $usage = null;

        // ✅ User-based plan
        if (method_exists($user, 'getCurrentPlan')) {
            $plan = $user->getCurrentPlan();
        }

        if (!$plan && $activeSubscription && $activeSubscription->plan) {
            $plan = $activeSubscription->plan;
        }

        if (!$plan) {
            $plan = \App\Models\Plan::where('tier', 'free')->first();
        }

        if ($plan) {
            // Usage counts are user-based
            $businessCount = $user->businesses()->count();
            $branchCount = \App\Models\Branch::whereHas('business', fn($q) => $q->where('owner_id', $user->id))->count();
            $serviceCount = \App\Models\BusinessService::whereHas('business', fn($q) => $q->where('owner_id', $user->id))->count();
            $imageCount = \App\Models\BusinessImage::whereHas('business', fn($q) => $q->where('owner_id', $user->id))->count();
            $couponCount = \App\Models\Coupon::where('user_id', $user->id)->count();

            $usage = [
                'plan_name' => $plan->name ?? 'Free',
                'plan_tier' => $plan->tier ?? 'free',
                'plan_price' => (float) ($plan->price_monthly ?? 0),
                'businesses' => ['current' => $businessCount, 'limit' => $plan->max_businesses ?? 1],
                'branches' => ['current' => $branchCount, 'limit' => $plan->max_branches ?? 1],
                'services' => ['current' => $serviceCount, 'limit' => $plan->max_services ?? 3],
                'images' => ['current' => $imageCount, 'limit' => $plan->max_images ?? 3],
                'coupons' => ['current' => $couponCount, 'limit' => $plan->max_coupons ?? 0],
            ];
        }

        // Get recent activity (your existing method)
        $recentActivity = $this->getRecentActivity($business);

        // ✅ Plan overflow state (for the over-limit banner)
        $planOverflow = $this->computePlanOverflow($user, $plan);

        // ✅ Profile completeness score
        // Note: `$business` was loaded with a partial column select
        // (id, name, slug, status, created_at), so column-based checks
        // (description, email, website, cover_image) would always fail.
        // Re-fetch with full columns before calculating.
        $businessForCompleteness = Business::find($business->id) ?? $business;
        $completeness = (new BusinessCompletenessService())->calculate($businessForCompleteness);

        return Inertia::render('Owner/Dashboard', [
            'user' => $user,
            'business' => $business,
            'hasBusiness' => true,
            'completeness' => $completeness,
            'branchesCount' => $business->branches_count,
            'activeSubscription' => $activeSubscription,
            'analytics' => [
                'stats' => [
                    'total_views' => $analyticsStats->total_views ?? 0,
                    'total_unique_visitors' => $analyticsStats->total_unique_visitors ?? 0,
                    'total_phone_clicks' => $analyticsStats->total_phone_clicks ?? 0,
                    'total_whatsapp_clicks' => $analyticsStats->total_whatsapp_clicks ?? 0,
                    'total_website_clicks' => $analyticsStats->total_website_clicks ?? 0,
                    'total_social_clicks' => $analyticsStats->total_social_clicks ?? 0,
                    'total_direction_clicks' => $analyticsStats->total_direction_clicks ?? 0,
                ],
                'chart' => $chartData,
                'comparison' => $comparison,
                'has_data' => ($analyticsStats->total_views ?? 0) > 0,
                'click_breakdown' => $clickBreakdown, // ✅ NEW
            ],
            'recentActivity' => $recentActivity,
            'recentReviews' => $recentReviews, // ✅ NEW
            'reviewStats' => $reviewStats, // ✅ NEW
            'recentLeads' => $recentLeads, // ✅ NEW
            'leadStats' => $leadStats, // ✅ NEW
            'recentCoupons' => $recentCoupons, // ✅ NEW
            'couponStats' => $couponStats, // ✅ NEW
            'usage' => $usage, // ✅ NEW
            'hasLeadCapture' => $business->hasLeadCaptureFeature(), // ✅ NEW
            'hasCoupons' => $business->canUseCoupons(), // ✅ NEW
            'businesses' => $businesses, // ✅ NEW
            'selectedBusinessId' => $business->id, // ✅ NEW
            'planOverflow' => $planOverflow, // ✅ NEW — over-limit banner state
        ]);
    }
    private function getStatusBadge($status)
    {
        $badges = [
            'draft' => 'bg-gray-100 text-gray-700',
            'submitted' => 'bg-yellow-100 text-yellow-700',
            'approved' => 'bg-blue-100 text-blue-700',
            'published' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700',
            'suspended' => 'bg-red-100 text-red-700',
        ];
        return $badges[$status] ?? 'bg-gray-100 text-gray-700';
    }

    private function getRecentActivity($business)
    {
        $activities = [];

        // Recent branches added
        $recentBranches = $business->branches()
            ->latest()
            ->take(3)
            ->get();

        foreach ($recentBranches as $branch) {
            $activities[] = [
                'type' => 'branch',
                'message' => "Added branch: {$branch->name}",
                'time' => $branch->created_at->diffForHumans(),
                'icon' => '📍',
            ];
        }

        // Recent services added
        $recentServices = $business->services()
            ->latest()
            ->take(3)
            ->get();

        foreach ($recentServices as $service) {
            $activities[] = [
                'type' => 'service',
                'message' => "Added service: {$service->name}",
                'time' => $service->created_at->diffForHumans(),
                'icon' => '🛠️',
            ];
        }

        // Sort by time
        usort($activities, function ($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        return array_slice($activities, 0, 10);
    }

    public function usage()
    {
        $user = auth()->user();
        $plan = $user->getCurrentPlan();

        if (!$plan) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'No active plan found.');
        }

        $usage = [
            'businesses' => [
                'label' => 'Businesses',
                'icon' => '🏢',
                'current' => $user->getCurrentUsage('businesses'),
                'limit' => $plan->max_businesses,
            ],
            'branches' => [
                'label' => 'Branches',
                'icon' => '📍',
                'current' => $user->getCurrentUsage('branches'),
                'limit' => $plan->max_branches,
            ],
            'services' => [
                'label' => 'Services',
                'icon' => '🛠️',
                'current' => $user->getCurrentUsage('services'),
                'limit' => $plan->max_services,
            ],
            'images' => [
                'label' => 'Photos',
                'icon' => '📸',
                'current' => $user->getCurrentUsage('images'),
                'limit' => $plan->max_images,
            ],
            'coupons' => [
                'label' => 'Coupons',
                'icon' => '🎟️',
                'current' => $user->getCurrentUsage('coupons'),
                'limit' => $plan->max_coupons,
            ],
        ];

        // ✅ Plan overflow state (for the usage page breakdown)
        $planOverflow = $this->computePlanOverflow($user, $plan);

        return Inertia::render('Owner/Usage/Index', [
            'planOverflow' => $planOverflow,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'tier' => $plan->tier,
                'price_monthly' => (float) $plan->price_monthly,
                'currency' => $plan->currency ?? 'XAF',
                'features' => $plan->features ?? [],
            ],
            'usage' => $usage,
        ]);
    }


    /**
     * Compute plan overflow state for the current user.
     *
     * Returns:
     *   [
     *     'isOver' => bool,                  // any resource is over limit
     *     'graceActive' => bool,             // downgrade_grace_ends_at in future
     *     'graceEndsAt' => 'Y-m-d' | null,
     *     'items' => [
     *        'businesses' => ['current' => X, 'limit' => Y, 'over' => Z],
     *        ...
     *     ]
     *   ]
     */
    private function computePlanOverflow($user, $plan): ?array
    {
        if (!$plan)
            return null;

        $activeSubscription = $user->active_subscription;

        // Grace state — only relevant if a downgrade happened
        $graceEndsAt = $activeSubscription?->downgrade_grace_ends_at;
        $graceActive = $graceEndsAt && \Carbon\Carbon::parse($graceEndsAt)->isFuture();

        $counts = [
            'businesses' => $user->businesses()->whereNotIn('status', ['deleted', 'rejected'])->count(),
            'branches' => \App\Models\Branch::whereHas('business', fn($q) => $q->where('owner_id', $user->id))->count(),
            'services' => \App\Models\BusinessService::whereHas('business', fn($q) => $q->where('owner_id', $user->id))->count(),
            'images' => \App\Models\BusinessImage::whereHas('business', fn($q) => $q->where('owner_id', $user->id))->count(),
            'coupons' => \App\Models\Coupon::where('user_id', $user->id)->count(),
        ];

        $limits = [
            'businesses' => $plan->max_businesses ?? 0,
            'branches' => $plan->max_branches ?? 0,
            'services' => $plan->max_services ?? 0,
            'images' => $plan->max_images ?? 0,
            'coupons' => $plan->max_coupons ?? 0,
        ];

        $items = [];
        $isOver = false;
        $totalOver = 0;

        foreach ($counts as $key => $current) {
            $limit = $limits[$key];

            // Unlimited plan — never over
            if ($limit === -1 || $limit === 999) {
                $items[$key] = ['current' => $current, 'limit' => $limit, 'over' => 0];
                continue;
            }

            $over = max(0, $current - $limit);
            if ($over > 0) {
                $isOver = true;
                $totalOver += $over;
            }
            $items[$key] = ['current' => $current, 'limit' => $limit, 'over' => $over];
        }

        return [
            'isOver' => $isOver,
            'totalOver' => $totalOver,
            'graceActive' => $graceActive,
            'graceEndsAt' => $graceEndsAt ? \Carbon\Carbon::parse($graceEndsAt)->format('Y-m-d') : null,
            'items' => $items,
        ];
    }
}