<?php
// app/Http/Controllers/Admin/SubscriptionController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions with stats and filters
     */
    // public function index(Request $request)
    // {
    //     $query = Subscription::with(['business', 'plan']);

    //     // Filter: status
    //     if ($request->filled('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     // Filter: plan
    //     if ($request->filled('plan_id')) {
    //         $query->where('plan_id', $request->plan_id);
    //     }

    //     // Filter: search (business name or owner email)
    //     if ($request->filled('search')) {
    //         $search = $request->search;
    //         $query->where(function ($q) use ($search) {
    //             $q->whereHas('business', function ($bq) use ($search) {
    //                 $bq->where('name', 'like', "%{$search}%")
    //                    ->orWhere('email', 'like', "%{$search}%")
    //                    ->orWhereHas('owner', function ($oq) use ($search) {
    //                        $oq->where('name', 'like', "%{$search}%")
    //                           ->orWhere('email', 'like', "%{$search}%");
    //                    });
    //             });
    //         });
    //     }

    //     // Filter: date range
    //     if ($request->filled('date_from')) {
    //         $query->whereDate('created_at', '>=', $request->date_from);
    //     }
    //     if ($request->filled('date_to')) {
    //         $query->whereDate('created_at', '<=', $request->date_to);
    //     }

    //     // Filter: expiring soon (within N days)
    //     if ($request->filled('expiring_in')) {
    //         $days = (int) $request->expiring_in;
    //         $query->where('status', 'active')
    //               ->whereDate('end_date', '<=', now()->addDays($days))
    //               ->whereDate('end_date', '>=', now());
    //     }

    //     // Sort
    //     $sortBy = $request->get('sort_by', 'created_at');
    //     $sortDir = $request->get('sort_dir', 'desc');
    //     $allowedSorts = ['created_at', 'end_date', 'start_date', 'total_price'];
    //     if (in_array($sortBy, $allowedSorts)) {
    //         $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
    //     } else {
    //         $query->latest();
    //     }

    //     $subscriptions = $query->paginate(20)->withQueryString();

    //     // ✅ Get all plans for filter dropdown
    //     $plans = Plan::orderBy('sort_order')->get(['id', 'name', 'tier']);

    //     return Inertia::render('Admin/Subscriptions/Index', [
    //         'subscriptions' => $subscriptions,
    //         'filters' => $request->only([
    //             'status', 'plan_id', 'search', 'date_from', 'date_to',
    //             'expiring_in', 'sort_by', 'sort_dir',
    //         ]),
    //         'statuses' => [
    //             'active', 'pending', 'failed', 'expired',
    //             'suspended', 'cancelled', 'grace_period', 'expiring_soon',
    //         ],
    //         'plans' => $plans,
    //         'stats' => $this->getStats(),
    //     ]);
    // }

    public function index(Request $request)
    {
        $query = Subscription::with(['user', 'plan', 'business']);

        // Filter: status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: plan
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // ✅ Filter: search by owner (user) name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('business', function ($bq) use ($search) {
                    $bq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Filter: date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter: expiring soon
        if ($request->filled('expiring_in')) {
            $days = (int) $request->expiring_in;
            $query->where('status', 'active')
                ->whereDate('end_date', '<=', now()->addDays($days))
                ->whereDate('end_date', '>=', now());
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'end_date', 'start_date', 'total_price'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $subscriptions = $query->paginate(20)->withQueryString();

        $plans = Plan::orderBy('sort_order')->get(['id', 'name', 'tier']);

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'filters' => $request->only([
                'status',
                'plan_id',
                'search',
                'date_from',
                'date_to',
                'expiring_in',
                'sort_by',
                'sort_dir',
            ]),
            'statuses' => [
                'active',
                'pending',
                'failed',
                'expired',
                'suspended',
                'cancelled',
                'grace_period',
                'expiring_soon',
            ],
            'plans' => $plans,
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * ✅ NEW: Compute dashboard stats
     */
    protected function getStats(): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();

        // Active subscription count
        $activeCount = Subscription::where('status', 'active')->count();

        // MRR: sum of monthly_price of all active subs
        $mrr = Subscription::where('status', 'active')
            ->whereNotNull('monthly_price')
            ->sum('monthly_price');

        // This month's new subscriptions
        $thisMonthNew = Subscription::whereBetween('created_at', [$startOfMonth, $now])->count();
        $lastMonthNew = Subscription::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $growthPercent = $lastMonthNew > 0
            ? round((($thisMonthNew - $lastMonthNew) / $lastMonthNew) * 100, 1)
            : 0;

        // Expiring soon (next 7 days)
        $expiringSoon = Subscription::where('status', 'active')
            ->whereDate('end_date', '<=', $now->copy()->addDays(7))
            ->whereDate('end_date', '>=', $now)
            ->count();

        // Churn: cancelled this month / active at start of month
        $cancelledThisMonth = Subscription::where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$startOfMonth, $now])
            ->count();

        $activeAtStartOfMonth = Subscription::where('status', 'active')
            ->whereDate('created_at', '<', $startOfMonth)
            ->count();

        $churnRate = $activeAtStartOfMonth > 0
            ? round(($cancelledThisMonth / $activeAtStartOfMonth) * 100, 1)
            : 0;

        // Revenue this month (from PaymentTransaction if available)
        $thisMonthRevenue = 0;
        if (class_exists(\App\Models\PaymentTransaction::class)) {
            $thisMonthRevenue = \App\Models\PaymentTransaction::where('status', 'confirmed')
                ->whereBetween('confirmed_at', [$startOfMonth, $now])
                ->sum('amount');
        }

        return [
            'active_count' => $activeCount,
            'mrr' => (float) $mrr,
            'this_month_new' => $thisMonthNew,
            'growth_percent' => $growthPercent,
            'expiring_soon' => $expiringSoon,
            'churn_rate' => $churnRate,
            'this_month_revenue' => (float) $thisMonthRevenue,
        ];
    }

    /**
     * Show the form for creating a new subscription
     */
    public function create()
    {
        // ✅ Get users without active subscriptions (owners or users with businesses)
        $users = \App\Models\User::whereIn('role', ['user', 'owner'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Subscriptions/Create', [
            'users' => $users,
            'plans' => $plans,
        ]);
    }

    /**
     * Store a newly created subscription
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id', // ✅ Now requires user
            'business_id' => 'nullable|exists:businesses,id', // Optional
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:pending,active,expired,suspended,cancelled,grace_period,expiring_soon',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_trial' => 'boolean',
            'trial_end_date' => 'nullable|date',
        ]);

        $plan = Plan::find($validated['plan_id']);
        $durationMonths = $this->calculateDurationMonths($validated['start_date'], $validated['end_date']);

        Subscription::create([
            'user_id' => $validated['user_id'], // ✅ New
            'business_id' => $validated['business_id'] ?? null,
            'plan_id' => $validated['plan_id'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'duration_months' => $durationMonths,
            'total_price' => $plan->price_yearly ?? ($plan->price_monthly * $durationMonths),
            'monthly_price' => $plan->price_monthly,
            'discount_percentage' => $plan->yearly_discount_percentage ?? 0,
            'is_trial' => $validated['is_trial'] ?? false,
            'trial_end_date' => $validated['trial_end_date'] ?? null,
        ]);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription created successfully.');
    }

    /**
     * Show the form for editing a subscription
     */
    public function edit($id)
    {
        $subscription = Subscription::with(['user', 'plan', 'business'])->findOrFail($id);

        $users = \App\Models\User::whereIn('role', ['user', 'owner'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Format dates
        $subscription->start_date_formatted = $subscription->start_date?->format('Y-m-d');
        $subscription->end_date_formatted = $subscription->end_date?->format('Y-m-d');
        $subscription->trial_end_date_formatted = $subscription->trial_end_date?->format('Y-m-d');

        return Inertia::render('Admin/Subscriptions/Edit', [
            'subscription' => $subscription,
            'users' => $users,
            'plans' => $plans,
        ]);
    }

    /**
     * Update a subscription
     */
    public function update(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id', // ✅
            'business_id' => 'nullable|exists:businesses,id',
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:pending,active,expired,suspended,cancelled,grace_period,expiring_soon',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_trial' => 'boolean',
            'trial_end_date' => 'nullable|date',
        ]);

        $plan = Plan::find($validated['plan_id']);
        $durationMonths = $this->calculateDurationMonths($validated['start_date'], $validated['end_date']);

        $subscription->update([
            'user_id' => $validated['user_id'],
            'business_id' => $validated['business_id'] ?? null,
            'plan_id' => $validated['plan_id'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'duration_months' => $durationMonths,
            'total_price' => $plan->price_yearly ?? ($plan->price_monthly * $durationMonths),
            'monthly_price' => $plan->price_monthly,
            'discount_percentage' => $plan->yearly_discount_percentage ?? 0,
            'is_trial' => $validated['is_trial'] ?? false,
            'trial_end_date' => $validated['trial_end_date'] ?? null,
        ]);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription updated successfully.');
    }

    /**
     * ✅ FIXED: Activate with proper logging
     */
    public function activate(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);

        if (!in_array($subscription->status, ['pending', 'failed', 'suspended'])) {
            return back()->with('error', 'This subscription cannot be activated from its current status.');
        }

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now();

        $endDate = $startDate->copy()->addMonths($subscription->duration_months ?: 1);

        $subscription->update([
            'status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_confirmed_at' => now(),
            'failure_reason' => null,
        ]);

        // ⚠️ Removed: `has_active_subscription` update. That column
        //    doesn't exist on the Business model's fillable list, so
        //    the update was a silent no-op (or dead code). Subscription
        //    state is authoritative.

        return back()->with('success', 'Subscription activated successfully.');
    }

    /**
     * ✅ FIXED: Suspend
     */
    public function suspend(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);

        if ($subscription->status !== 'active') {
            return back()->with('error', 'Only active subscriptions can be suspended.');
        }

        $subscription->update(['status' => 'suspended']);

        return back()->with('success', 'Subscription suspended successfully.');
    }

    /**
     * ✅ FIXED: Cancel
     */
    public function cancel(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Subscription cancelled successfully.');
    }

    /**
     * ✅ NEW: Mark subscription as failed
     */
    public function markFailed(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription = Subscription::findOrFail($id);

        $subscription->update([
            'status' => 'failed',
            'failure_reason' => $validated['reason'] ?? 'Marked as failed by admin.',
        ]);

        return back()->with('success', 'Subscription marked as failed.');
    }

    /**
     * ✅ NEW: Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:subscriptions,id',
            'action' => 'required|in:activate,suspend,cancel,delete,extend',
            'extend_days' => 'nullable|integer|min:1|max:365',
        ]);

        $subscriptions = Subscription::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($subscriptions as $subscription) {
            switch ($validated['action']) {
                case 'activate':
                    if (in_array($subscription->status, ['pending', 'failed', 'suspended'])) {
                        $endDate = now()->addMonths($subscription->duration_months ?: 1);
                        $subscription->update([
                            'status' => 'active',
                            'start_date' => now(),
                            'end_date' => $endDate,
                            'failure_reason' => null,
                        ]);
                        $count++;
                    }
                    break;

                case 'suspend':
                    if ($subscription->status === 'active') {
                        $subscription->update(['status' => 'suspended']);
                        $count++;
                    }
                    break;

                case 'cancel':
                    $subscription->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);
                    $count++;
                    break;

                case 'extend':
                    $days = $validated['extend_days'] ?? 30;
                    if ($subscription->end_date) {
                        $subscription->update([
                            'end_date' => $subscription->end_date->addDays($days),
                        ]);
                        $count++;
                    }
                    break;

                case 'delete':
                    $subscription->delete();
                    $count++;
                    break;
            }
        }

        return back()->with('success', "{$count} subscription(s) processed successfully.");
    }

    /**
     * ✅ NEW: Export subscriptions to CSV
     */
    public function export(Request $request)
    {
        $query = Subscription::with(['business:id,name', 'plan:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $subscriptions = $query->latest()->get();

        $filename = 'subscriptions-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($subscriptions) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'ID',
                'Business',
                'Plan',
                'Status',
                'Start Date',
                'End Date',
                'Days Remaining',
                'Duration (months)',
                'Total Price',
                'Monthly Price',
                'Is Trial',
                'Created At',
            ]);

            foreach ($subscriptions as $sub) {
                fputcsv($file, [
                    $sub->id,
                    $sub->business?->name ?? 'N/A',
                    $sub->plan?->name ?? 'N/A',
                    $sub->status,
                    $sub->start_date?->format('Y-m-d') ?? '',
                    $sub->end_date?->format('Y-m-d') ?? '',
                    $sub->days_remaining ?? '',
                    $sub->duration_months ?? '',
                    $sub->total_price ?? '',
                    $sub->monthly_price ?? '',
                    $sub->is_trial ? 'Yes' : 'No',
                    $sub->created_at?->format('Y-m-d H:i') ?? '',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Show a specific subscription
     */
    public function show($id)
    {
        $subscription = Subscription::with([
            'user',
            'plan',
            'business',
            'transactions' => function ($query) {
                $query->with('plan:id,name')->latest();
            },
            'payments' => function ($query) {
                $query->with('recordedBy:id,name')->latest();
            },
        ])->findOrFail($id);

        return Inertia::render('Admin/Subscriptions/Show', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Delete a subscription
     */
    public function destroy($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }

    /**
     * Calculate duration in months between two dates
     */
    private function calculateDurationMonths($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        return max(1, $start->diffInMonths($end));
    }
}