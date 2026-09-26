<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RevenueController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Revenue/Index', [
            'kpis' => $this->getKpis(),
            'charts' => [
                'revenue' => $this->getRevenueChart(),
                'new_subs' => $this->getNewSubsChart(),
                'plan_distribution' => $this->getPlanDistribution(),
                'revenue_by_plan' => $this->getRevenueByPlan(),
            ],
            'growth' => $this->getGrowthMetrics(),
            'top_plans' => $this->getTopPlans(),
            'top_businesses' => $this->getTopBusinesses(),
            'recent_events' => $this->getRecentEvents(),
        ]);
    }

    // ============== KPIs ==============
    protected function getKpis(): array
{
    $mrr = Subscription::where('status', 'active')
        ->sum(DB::raw('COALESCE(monthly_price, total_price / GREATEST(duration_months, 1))'));

    $arr = $mrr * 12;

    $activeCount = Subscription::where('status', 'active')->count();

    $arpu = $activeCount > 0 ? $mrr / $activeCount : 0;

    // LTV = ARPU / churn_rate (as decimal)
    $churnRate = $this->calculateChurnRate();
    $ltv = $churnRate > 0 ? $arpu / ($churnRate / 100) : $arpu * 24;

    // ✅ Revenue this month from PaymentTransaction
    $thisMonthRevenue = \App\Models\PaymentTransaction::where('status', 'successful')
        ->whereBetween('confirmed_at', [now()->startOfMonth(), now()])
        ->sum('amount');

    return [
        'mrr' => (float) round($mrr, 2),
        'arr' => (float) round($arr, 2),
        'arpu' => (float) round($arpu, 2),
        'ltv' => (float) round($ltv, 2),
        'active_count' => $activeCount,
        'this_month_revenue' => (float) $thisMonthRevenue, // ✅ NEW
    ];
}

    // ============== 12-Month Revenue Chart ==============
    protected function getRevenueChart(): array
{
    $labels = [];
    $data = [];

    for ($i = 11; $i >= 0; $i--) {
        $month = now()->subMonths($i);
        $labels[] = $month->format('M Y');

        // ✅ Revenue from successful PaymentTransactions
        $revenue = \App\Models\PaymentTransaction::where('status', 'successful')
            ->whereMonth('confirmed_at', $month->month)
            ->whereYear('confirmed_at', $month->year)
            ->sum('amount');

        $data[] = (float) $revenue;
    }

    return [
        'labels' => $labels,
        'data' => $data,
    ];
}

    // ============== New Subscriptions Chart ==============
    protected function getNewSubsChart(): array
    {
        $labels = [];
        $newSubs = [];
        $cancelled = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M');

            $newSubs[] = Subscription::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $cancelled[] = Subscription::where('status', 'cancelled')
                ->whereMonth('cancelled_at', $month->month)
                ->whereYear('cancelled_at', $month->year)
                ->count();
        }

        return [
            'labels' => $labels,
            'new' => $newSubs,
            'cancelled' => $cancelled,
        ];
    }

    // ============== Plan Distribution ==============
    protected function getPlanDistribution(): array
    {
        $data = Subscription::where('status', 'active')
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->with('plan:id,name,tier')
            ->get();

        $labels = [];
        $counts = [];
        $colors = [
            '#0ea5e9', // sky
            '#8b5cf6', // purple
            '#f59e0b', // amber
            '#10b981', // emerald
            '#ef4444', // red
            '#ec4899', // pink
        ];

        foreach ($data as $i => $row) {
            $labels[] = $row->plan?->name ?? 'Unknown';
            $counts[] = $row->count;
        }

        return [
            'labels' => $labels,
            'data' => $counts,
            'colors' => array_slice($colors, 0, count($labels)),
        ];
    }

    // ============== Revenue by Plan ==============
    protected function getRevenueByPlan(): array
    {
        $data = Subscription::where('status', 'active')
            ->select('plan_id', DB::raw('SUM(COALESCE(monthly_price, total_price / GREATEST(duration_months, 1))) as mrr'))
            ->groupBy('plan_id')
            ->with('plan:id,name')
            ->get();

        $labels = [];
        $amounts = [];
        $colors = ['#0ea5e9', '#8b5cf6', '#f59e0b', '#10b981', '#ef4444', '#ec4899'];

        foreach ($data as $i => $row) {
            $labels[] = $row->plan?->name ?? 'Unknown';
            $amounts[] = (float) $row->mrr;
        }

        return [
            'labels' => $labels,
            'data' => $amounts,
            'colors' => array_slice($colors, 0, count($labels)),
        ];
    }

    // ============== Growth Metrics ==============
    protected function getGrowthMetrics(): array
{
    $thisMonth = now()->startOfMonth();
    $lastMonth = now()->subMonth()->startOfMonth();
    $lastMonthEnd = now()->subMonth()->endOfMonth();

    // ✅ This month revenue from PaymentTransaction
    $thisMonthRevenue = \App\Models\PaymentTransaction::where('status', 'successful')
        ->whereBetween('confirmed_at', [$thisMonth, now()])
        ->sum('amount');

    $thisMonthNew = Subscription::whereBetween('created_at', [$thisMonth, now()])->count();
    $thisMonthChurned = Subscription::where('status', 'cancelled')
        ->whereBetween('cancelled_at', [$thisMonth, now()])
        ->count();

    // ✅ Last month revenue from PaymentTransaction
    $lastMonthRevenue = \App\Models\PaymentTransaction::where('status', 'successful')
        ->whereBetween('confirmed_at', [$lastMonth, $lastMonthEnd])
        ->sum('amount');

    $lastMonthNew = Subscription::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();
    $lastMonthChurned = Subscription::where('status', 'cancelled')
        ->whereBetween('cancelled_at', [$lastMonth, $lastMonthEnd])
        ->count();

    $revenueGrowth = $lastMonthRevenue > 0
        ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
        : 0;

    $newSubsGrowth = $lastMonthNew > 0
        ? round((($thisMonthNew - $lastMonthNew) / $lastMonthNew) * 100, 1)
        : 0;

    return [
        'this_month' => [
            'revenue' => (float) $thisMonthRevenue,
            'new' => $thisMonthNew,
            'churned' => $thisMonthChurned,
        ],
        'last_month' => [
            'revenue' => (float) $lastMonthRevenue,
            'new' => $lastMonthNew,
            'churned' => $lastMonthChurned,
        ],
        'growth' => [
            'revenue' => $revenueGrowth,
            'new_subs' => $newSubsGrowth,
            'churn_rate' => $this->calculateChurnRate(),
        ],
    ];
}

    // ============== Top Plans ==============
    protected function getTopPlans(): array
    {
        return Plan::withCount([
            'subscriptions as active_count' => function ($q) {
                $q->where('status', 'active');
            },
        ])
            ->withSum([
                'subscriptions as mrr' => function ($q) {
                    $q->where('status', 'active');
                },
            ], DB::raw('COALESCE(monthly_price, total_price / GREATEST(duration_months, 1))'))
            ->orderByDesc('mrr')
            ->limit(5)
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'tier' => $plan->tier,
                    'is_featured' => $plan->is_featured,
                    'active_count' => $plan->active_count,
                    'mrr' => (float) ($plan->mrr ?? 0),
                ];
            })
            ->toArray();
    }

    // ============== Top Businesses ==============
    protected function getTopBusinesses(): array
    {
        return Business::query()
            ->with('owner:id,name')
            ->withCount('subscriptions')
            ->withSum([
                'subscriptions as total_paid' => function ($q) {
                    $q->whereIn('status', ['active', 'expired']);
                },
            ], 'total_price')
            ->orderByDesc('total_paid')
            ->limit(5)
            ->get()
            ->map(function ($business) {
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'owner_name' => $business->owner?->name,
                    'subscriptions_count' => $business->subscriptions_count,
                    'total_paid' => (float) ($business->total_paid ?? 0),
                ];
            })
            ->toArray();
    }

    // ============== Recent Events ==============
    protected function getRecentEvents(): array
    {
        // Recent activations, cancellations, and new subs
        $events = [];

        // Last 10 activated
        Subscription::where('status', 'active')
            ->with(['business:id,name', 'plan:id,name'])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->each(function ($sub) use (&$events) {
                $events[] = [
                    'type' => 'activated',
                    'label' => 'Subscription Activated',
                    'business' => $sub->business?->name,
                    'plan' => $sub->plan?->name,
                    'amount' => (float) $sub->total_price,
                    'date' => $sub->updated_at?->toIso8601String(),
                    'icon' => '✅',
                ];
            });

        // Last 5 cancelled
        Subscription::where('status', 'cancelled')
            ->with(['business:id,name', 'plan:id,name'])
            ->latest('cancelled_at')
            ->limit(5)
            ->get()
            ->each(function ($sub) use (&$events) {
                $events[] = [
                    'type' => 'cancelled',
                    'label' => 'Subscription Cancelled',
                    'business' => $sub->business?->name,
                    'plan' => $sub->plan?->name,
                    'amount' => 0,
                    'date' => $sub->cancelled_at?->toIso8601String(),
                    'icon' => '❌',
                ];
            });

        // Sort by date desc
        usort($events, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($events, 0, 10);
    }

    // ============== Helper: Churn Rate ==============
    protected function calculateChurnRate(): float
    {
        $startOfMonth = now()->startOfMonth();

        $activeAtStart = Subscription::where('status', 'active')
            ->whereDate('created_at', '<', $startOfMonth)
            ->count();

        $cancelledThisMonth = Subscription::where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$startOfMonth, now()])
            ->count();

        return $activeAtStart > 0
            ? round(($cancelledThisMonth / $activeAtStart) * 100, 2)
            : 0;
    }
}