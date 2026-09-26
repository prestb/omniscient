<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Display a listing of payment transactions.
     */
    public function index(Request $request)
    {
        $query = PaymentTransaction::with(['user', 'plan', 'subscription'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            // ✅ Wrap OR conditions in a closure so they don't escape the
            //    surrounding WHERE clause and clobber the status filter.
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Payments/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Display the specified transaction.
     */
    public function show($id)
    {
        $transaction = PaymentTransaction::with(['user', 'plan', 'subscription'])
            ->findOrFail($id);

        return Inertia::render('Admin/Payments/Show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit($id)
    {
        $transaction = PaymentTransaction::with(['user', 'plan', 'subscription'])
            ->findOrFail($id);

        return Inertia::render('Admin/Payments/Edit', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Update the specified transaction.
     *
     * ✅ Uses PaymentTransaction (matching show/index/edit). Previously this
     *    method took `Payment $payment` — a different model with a
     *    separate table — which caused wrong-record updates or 404s
     *    when the user edited a PaymentTransaction from the admin UI.
     */
    public function update(Request $request, $id)
    {
        $transaction = PaymentTransaction::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'required|in:created,pending,successful,failed,expired',
            'confirmed_at' => 'nullable|date',
        ]);

        $transaction->update($validated);

        return redirect()->route('admin.payments.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified transaction.
     *
     * ✅ Uses PaymentTransaction (matching show/index/edit/update).
     */
    public function destroy($id)
    {
        $transaction = PaymentTransaction::findOrFail($id);
        $transaction->delete();

        return redirect()->route('admin.payments.index')
            ->with('success', 'Transaction deleted successfully.');
    }

    /**
     * Get subscriptions for a business (API endpoint).
     */
    public function getSubscriptions(Request $request)
    {
        $subscriptions = Subscription::with(['plan', 'business'])
            ->where('business_id', $request->business_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'plan_name' => $subscription->plan->name,
                    'status' => $subscription->status,
                    'status_label' => $subscription->status_label,
                    'start_date' => $subscription->start_date?->format('Y-m-d'),
                    'end_date' => $subscription->end_date?->format('Y-m-d'),
                ];
            });

        return response()->json($subscriptions);
    }
}