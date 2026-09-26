<?php
// app/Http/Controllers/Payment/FapshiController.php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\Plan;
use App\Services\FapshiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\PlanEnforcementService;   // ✅ NEW


class FapshiController extends Controller
{
    protected $fapshi;

    public function __construct(FapshiPaymentService $fapshi)
    {
        $this->fapshi = $fapshi;
    }

    /**
     * Show the checkout page
     */
    public function checkout(Request $request)
    {
        $plan = Plan::findOrFail($request->plan_id);
        $duration = $request->duration ?? 12;
        $totalPrice = $request->total_price ?? $plan->price_annual;
        $billingType = $request->billing_type ?? 'yearly';
        $actionType = $request->action_type ?? 'new';
        $subscriptionId = $request->subscription_id ?? null;

        return Inertia::render('Payment/FapshiCheckout', [
            'plan' => $plan,
            'duration' => $duration,
            'totalPrice' => $totalPrice,
            'billingType' => $billingType,
            'actionType' => $actionType,
            'subscriptionId' => $subscriptionId,
            'user' => auth()->user(),
        ]);
    }

    /**
     * Initiate payment with Fapshi
     */
    public function initiatePayment(Request $request)
    {

        // ✅ Log the incoming request
        Log::info('Fapshi Initiate Payment Request', [
            'all' => $request->all(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
        ]);
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
                'duration' => 'required|integer|min:1|max:12',
                'billing_type' => 'required|in:monthly,yearly',
                'action_type' => 'required|in:new,renew,upgrade',
                'total_price' => 'required|numeric|min:0',
                'subscription_id' => 'nullable|exists:subscriptions,id',
                'phone' => 'required|string|regex:/^[0-9]{9,12}$/',
                'medium' => 'nullable|string|in:mobile money,orange money',
                'name' => 'nullable|string|max:100',
                'email' => 'nullable|email',
            ]);

            Log::info('Fapshi Initiate Payment Validated', ['validated' => $validated]);


            $user = auth()->user();
            $plan = Plan::findOrFail($validated['plan_id']);
            $duration = $validated['duration'];
            $totalPrice = $validated['total_price'];
            $actionType = $validated['action_type'];
            $billingType = $validated['billing_type'];

            // ✅ Get the pending subscription (or create one if not passed)
            $subscription = null;
            if ($validated['subscription_id']) {
                $subscription = Subscription::find($validated['subscription_id']);
            }

            if (!$subscription) {
                // If no subscription passed, find the latest pending one
                $business = $user->businesses()->first();
                $subscription = Subscription::where('business_id', $business->id)
                    ->where('plan_id', $plan->id)
                    ->where('status', Subscription::STATUS_PENDING)
                    ->latest()
                    ->first();
            }

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending subscription found. Please try again.',
                ], 400);
            }

            // ✅ Create transaction record
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'duration_months' => $duration,
                'amount' => $totalPrice,
                'currency' => 'XAF',
                'status' => 'created',
                'payment_method' => 'fapshi',
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? $user->email,
                'action_type' => $actionType,
                'billing_type' => $billingType,
            ]);

            // ✅ Initiate payment with Fapshi
            $result = $this->fapshi->directPay([
                'amount' => (int) $totalPrice,
                'phone' => $validated['phone'],
                'medium' => $validated['medium'] ?? null,
                'name' => $validated['name'] ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
                'userId' => (string) $user->id,
                'externalId' => (string) $transaction->id,
                'message' => "Subscription to {$plan->name} plan ({$duration} months) - {$actionType}",
            ]);

            // ✅ Return more detailed error
            if (!$result['success']) {
                $transaction->update(['status' => 'failed']);

                // ✅ Log the exact error
                Log::error('Payment Initiation Failed', [
                    'result' => $result,
                    'request_data' => $validated,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Payment initiation failed. Please check your phone number and try again.',
                    'debug' => config('app.debug') ? $result : null, // Only in debug mode
                ], 400);
            }

            // ✅ Update transaction with Fapshi transId
            $transaction->update([
                'transaction_id' => $result['transId'],
                'status' => 'pending',
            ]);

            // ✅ Update subscription with transaction reference
            $subscription->update([
                'transaction_id' => $result['transId'],
            ]);

            return response()->json([
                'success' => true,
                'transId' => $result['transId'],
                'message' => 'Payment request sent to your phone.',
            ]);

        } catch (\Exception $e) {
            Log::error('Fapshi Initiate Payment Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Webhook handler
     */
    public function webhook(Request $request)
    {
        Log::info('Fapshi Webhook Request Received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        // ✅ Verify webhook signature (allow null)
        $secret = $request->header('x-wh-secret');

        if (!$this->fapshi->verifyWebhookSignature($secret)) {
            Log::warning('Fapshi webhook: Invalid or missing signature', [
                'secret_present' => !empty($secret),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // ✅ Process webhook payload
        try {
            $this->fapshi->processWebhook($request->all());
            Log::info('Fapshi webhook: Processed successfully');
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Fapshi webhook: Processing error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get status display message
     */
    protected function getStatusMessage($status)
    {
        return [
            'CREATED' => 'Payment initiated. Waiting for user to complete.',
            'PENDING' => 'User is processing the payment.',
            'SUCCESSFUL' => 'Payment completed successfully!',
            'FAILED' => 'Payment failed. Please try again.',
            'EXPIRED' => 'Payment link expired. Please initiate a new payment.',
        ][$status] ?? 'Unknown status';
    }

    /**
     * Calculate price with discount
     */
    protected function calculatePrice($plan, $duration)
    {
        $monthlyPrice = $plan->price_monthly;
        $totalPrice = $monthlyPrice * $duration;
        $discount = $this->getDiscount($duration);
        return round($totalPrice * (1 - ($discount / 100)), 2);
    }

    protected function getDiscount($duration)
    {
        if ($duration >= 12)
            return 15;
        if ($duration >= 6)
            return 10;
        if ($duration >= 3)
            return 5;
        return 0;
    }

    /**
     * Check payment status
     */
    public function checkStatus($transId)
    {
        Log::info('Fapshi Status Check Request', ['transId' => $transId]);

        // ✅ First check if transaction exists in our database
        $transaction = PaymentTransaction::where('transaction_id', $transId)->first();

        if ($transaction) {
            Log::info('Fapshi Status: Found transaction in database', [
                'transId' => $transId,
                'status' => $transaction->status,
                'confirmed_at' => $transaction->confirmed_at,
            ]);

            // ✅ If already successful in our database, return that
            if ($transaction->status === 'successful') {
                return response()->json([
                    'success' => true,
                    'status' => 'SUCCESSFUL',
                    'transId' => $transId,
                    'amount' => $transaction->amount,
                    'message' => 'Payment completed successfully!',
                ]);
            }

            // ✅ If already failed or expired
            if (in_array($transaction->status, ['failed', 'expired'])) {
                return response()->json([
                    'success' => true,
                    'status' => strtoupper($transaction->status),
                    'transId' => $transId,
                    'message' => 'Payment ' . $transaction->status,
                ]);
            }
        }

        // Otherwise check with Fapshi API
        $result = $this->fapshi->getPaymentStatus($transId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        $data = $result['data'];
        $status = strtoupper($data['status']);

        Log::info('Fapshi Status: Response from API', [
            'transId' => $transId,
            'status' => $status,
            'data' => $data,
        ]);

        // Update transaction in database
        if ($transaction) {
            $transaction->update([
                'status' => strtolower($status),
                'payment_data' => $data,
                'confirmed_at' => $status === 'SUCCESSFUL' ? now() : null,
            ]);

            // If successful, activate subscription
            if ($status === 'SUCCESSFUL' && $transaction->subscription_id) {
                $subscription = Subscription::find($transaction->subscription_id);
                if ($subscription && $subscription->status !== Subscription::STATUS_ACTIVE) {
                    $subscription->update([
                        'status' => Subscription::STATUS_ACTIVE,
                        'start_date' => now(),
                        'end_date' => now()->addMonths($subscription->duration_months),
                        'payment_confirmed_at' => now(),
                        'failure_reason' => null,
                    ]);

                    // Update business
                    $business = $subscription->business;
                    if ($business) {
                        $business->update(['has_active_subscription' => true]);
                    }

                    // ✅ RESTORE HIDDEN ITEMS — new plan may accommodate them
                    try {
                        app(PlanEnforcementService::class)->enforce($subscription);
                        Log::info('Fapshi Status: Plan enforcement applied after polling activation', [
                            'subscription_id' => $subscription->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Fapshi Status: Plan enforcement failed', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::info('Fapshi Status: Subscription activated via polling', [
                        'subscription_id' => $subscription->id,
                        'transaction_id' => $transId,
                    ]);
                }
            }
        }

        // ✅ Return the status in a consistent format
        return response()->json([
            'success' => true,
            'status' => $status,
            'transId' => $data['transId'] ?? $transId,
            'amount' => $data['amount'] ?? null,
            'message' => $this->getStatusMessage($status),
        ]);
    }
}