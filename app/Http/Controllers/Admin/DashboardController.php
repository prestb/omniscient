<?php
// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Category;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ============== KPIs ==============
        $totalBusinesses = Business::count();
        $publishedBusinesses = Business::where('status', 'published')->count();
        $totalUsers = User::count();
        $activeOwners = User::where('role', 'owner')->where('status', 'active')->count();

        // Subscription stats
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $expiringSubscriptions = Subscription::where('status', 'expiring_soon')->count();
        $expiredSubscriptions = Subscription::whereIn('status', ['expired', 'grace_period'])->count();
        $suspendedSubscriptions = Subscription::where('status', 'suspended')->count();

        // ✅ Revenue stats from payment_transactions table (successful only)
        $totalRevenue = PaymentTransaction::where('status', 'successful')->sum('amount');

        $thisMonthRevenue = PaymentTransaction::where('status', 'successful')
            ->whereMonth('confirmed_at', now()->month)
            ->whereYear('confirmed_at', now()->year)
            ->sum('amount');

        $lastMonthRevenue = PaymentTransaction::where('status', 'successful')
            ->whereMonth('confirmed_at', now()->subMonth()->month)
            ->whereYear('confirmed_at', now()->subMonth()->year)
            ->sum('amount');

        // Revenue change (percent when possible, absolute fallback)
        $revenueChangePercent = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : null;
        $revenueChangeDelta = $thisMonthRevenue - $lastMonthRevenue; // absolute XAF change

        // ✅ Total successful transactions
        $totalTransactions = PaymentTransaction::where('status', 'successful')->count();
        $monthlyTransactions = PaymentTransaction::where('status', 'successful')
            ->whereMonth('confirmed_at', now()->month)
            ->whereYear('confirmed_at', now()->year)
            ->count();

        // ============== KPI BADGE METRICS ==============
        // Businesses added this month
        $businessesThisMonth = Business::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // User growth (MoM)
        $usersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $usersLastMonth = User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $userGrowthPercent = $usersLastMonth > 0
            ? round((($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : null;
        $userGrowthDelta = $usersThisMonth - $usersLastMonth; // absolute change

        // Subscription rate (active subs / total owners)
        $totalOwners = User::where('role', 'owner')->count();
        $subscriptionRate = $totalOwners > 0
            ? round(($activeSubscriptions / $totalOwners) * 100, 1)
            : 0;

        // ============== NEEDS ATTENTION ==============
        $pendingBusinesses = Business::whereIn('status', ['submitted', 'pending'])->count();
        $failedPayments = PaymentTransaction::where('status', 'failed')->count();
        $reviewsPending = Review::where('status', Review::STATUS_PENDING)->count();

        // ============== CHART DATA ==============

        // Revenue Chart (Last 12 months)
        $revenueData = $this->getRevenueChartData();

        // Business Growth Chart (Last 12 months)
        $businessGrowthData = $this->getBusinessGrowthChartData();

        // Subscription Status Distribution
        $subscriptionStatusData = $this->getSubscriptionStatusData();

        // Recent Businesses
        $recentBusinesses = Business::with(['owner', 'primaryLocation'])
            ->latest()
            ->take(10)
            ->get();

        // Top Plans
        $topPlans = Plan::withCount([
            'subscriptions' => function ($query) {
                $query->where('status', 'active');
            }
        ])
            ->orderBy('subscriptions_count', 'desc')
            ->take(5)
            ->get();

        // ✅ Recent Transactions (from payment_transactions)
        $recentPayments = PaymentTransaction::with(['user', 'plan', 'subscription'])
            ->where('status', 'successful')
            ->orderBy('confirmed_at', 'desc')
            ->take(10)
            ->get();

        // ✅ Top Categories (by business count)
        $topCategories = Category::withCount('businesses')
            ->orderBy('businesses_count', 'desc')
            ->take(5)
            ->get(['id', 'name', 'slug', 'icon']);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                // KPIs
                'total_businesses' => $totalBusinesses,
                'published_businesses' => $publishedBusinesses,
                'total_users' => $totalUsers,
                'active_owners' => $activeOwners,
                'active_subscriptions' => $activeSubscriptions,
                'expiring_subscriptions' => $expiringSubscriptions,
                'expired_subscriptions' => $expiredSubscriptions,
                'suspended_subscriptions' => $suspendedSubscriptions,
                'total_revenue' => $totalRevenue,
                'this_month_revenue' => $thisMonthRevenue,
                'revenue_change' => $revenueChangePercent,
                'revenue_change_delta' => $revenueChangeDelta,
                'total_transactions' => $totalTransactions,
                'monthly_transactions' => $monthlyTransactions,

                // Needs attention
                'pending_businesses' => $pendingBusinesses,
                'failed_payments' => $failedPayments,
                'reviews_pending' => $reviewsPending,

                // KPI badge metrics
                'businesses_this_month' => $businessesThisMonth,
                'user_growth' => $userGrowthPercent,
                'user_growth_delta' => $userGrowthDelta,
                'users_this_month' => $usersThisMonth,
                'subscription_rate' => $subscriptionRate,
            ],
            'charts' => [
                'revenue' => $revenueData,
                'business_growth' => $businessGrowthData,
                'subscription_status' => $subscriptionStatusData,
            ],
            'recentBusinesses' => $recentBusinesses,
            'topPlans' => $topPlans,
            'recentPayments' => $recentPayments,
            'topCategories' => $topCategories,
        ]);
    }

    private function getRevenueChartData()
    {
        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $revenue = PaymentTransaction::where('status', 'successful')
                ->whereMonth('confirmed_at', $month->month)
                ->whereYear('confirmed_at', $month->year)
                ->sum('amount');

            $data[] = $revenue / 1000;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenue (XAF)',
                    'data' => $data,
                    'borderColor' => '#0284c7',
                    'backgroundColor' => 'rgba(2, 132, 199, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    private function getBusinessGrowthChartData()
    {
        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $count = Business::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'New Businesses',
                    'data' => $data,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    private function getSubscriptionStatusData()
    {
        $statuses = [
            'Active' => Subscription::where('status', 'active')->count(),
            'Expiring Soon' => Subscription::where('status', 'expiring_soon')->count(),
            'Grace Period' => Subscription::where('status', 'grace_period')->count(),
            'Expired' => Subscription::where('status', 'expired')->count(),
            'Suspended' => Subscription::where('status', 'suspended')->count(),
            'Cancelled' => Subscription::where('status', 'cancelled')->count(),
        ];

        $colors = [
            '#22c55e',
            '#eab308',
            '#f97316',
            '#ef4444',
            '#8b5cf6',
            '#6b7280',
        ];

        return [
            'labels' => array_keys($statuses),
            'datasets' => [
                [
                    'data' => array_values($statuses),
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
        ];
    }
}