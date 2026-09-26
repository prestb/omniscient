<?php
// app/Services/FapshiPaymentService.php

namespace App\Services;

use App\Helpers\NotificationHelper;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Services\PlanEnforcementService;   // ✅ NEW

class FapshiPaymentService
{
    protected $apiUser;
    protected $apiKey;
    protected $baseUrl;
    protected $webhookSecret;

    public function __construct()
    {
        $this->apiUser = config('services.fapshi.api_user');
        $this->apiKey = config('services.fapshi.api_key');
        $this->webhookSecret = config('services.fapshi.webhook_secret');

        $isSandbox = config('services.fapshi.env') === 'sandbox';

        if ($isSandbox) {
            $this->baseUrl = config('services.fapshi.sandbox_url', 'https://sandbox.fapshi.com');
        } else {
            $this->baseUrl = config('services.fapshi.live_url', 'https://live.fapshi.com');
        }

        Log::info('Fapshi Service Initialized', [
            'env' => config('services.fapshi.env'),
            'baseUrl' => $this->baseUrl,
            'api_user' => $this->apiUser ? '***' . substr($this->apiUser, -4) : null,
        ]);
    }

    /**
     * Headers for all API requests
     */
    protected function getHeaders(): array
    {
        return [
            'apiuser' => $this->apiUser,
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Initiate a Direct Payment Request
     * POST /direct-pay
     */
    public function directPay(array $data): array
    {
        try {
            // ✅ Check if base URL is set
            if (empty($this->baseUrl)) {
                Log::error('Fapshi base URL is empty');
                return [
                    'success' => false,
                    'message' => 'Payment service configuration error: base URL is not set.',
                ];
            }

            // ✅ Build the full URL
            $url = rtrim($this->baseUrl, '/') . '/direct-pay';

            Log::info('Fapshi Direct Pay Request', [
                'url' => $url,
                'amount' => $data['amount'],
                'phone' => $data['phone'],
                'userId' => $data['userId'] ?? null,
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->post($url, [
                    'amount' => (int) $data['amount'],
                    'phone' => $data['phone'],
                    'medium' => $data['medium'] ?? null,
                    'name' => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'userId' => $data['userId'] ?? null,
                    'externalId' => $data['externalId'] ?? null,
                    'message' => $data['message'] ?? null,
                ]);

            Log::info('Fapshi Direct Pay Response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'transId' => $response->json('transId'),
                    'message' => $response->json('message'),
                    'dateInitiated' => $response->json('dateInitiated'),
                ];
            }

            Log::error('Fapshi Direct Pay Error', [
                'status' => $response->status(),
                'body' => $response->json(),
                'request_data' => $data,
            ]);

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Payment initiation failed.',
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Fapshi Direct Pay Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'Payment service error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Payment Transaction Status
     * GET /payment-status/{transId}
     */
    public function getPaymentStatus(string $transId): array
    {
        try {
            if (empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'Payment service configuration error: base URL is not set.',
                ];
            }

            $url = rtrim($this->baseUrl, '/') . '/payment-status/' . $transId;

            $response = Http::withHeaders($this->getHeaders())
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Fapshi Status Check Error', [
                'transId' => $transId,
                'status' => $response->status(),
                'message' => $response->json('message'),
            ]);

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to get payment status.',
            ];
        } catch (\Exception $e) {
            Log::error('Fapshi Status Check Exception', [
                'transId' => $transId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error checking payment status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Search Transactions
     * GET /search
     */
    public function searchTransactions(array $filters = []): array
    {
        try {
            if (empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'Payment service configuration error: base URL is not set.',
                ];
            }

            $url = rtrim($this->baseUrl, '/') . '/search';

            $response = Http::withHeaders($this->getHeaders())
                ->get($url, $filters);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Search failed.',
            ];
        } catch (\Exception $e) {
            Log::error('Fapshi Search Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error searching transactions: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Service Balance
     * GET /balance
     */
    public function getBalance(): array
    {
        try {
            if (empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'Payment service configuration error: base URL is not set.',
                ];
            }

            $url = rtrim($this->baseUrl, '/') . '/balance';

            $response = Http::withHeaders($this->getHeaders())
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->json('balance'),
                    'currency' => $response->json('currency'),
                    'service' => $response->json('service'),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to get balance.',
            ];
        } catch (\Exception $e) {
            Log::error('Fapshi Balance Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error getting balance: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Webhook Signature
     * Returns true if the secret matches, or if no secret is configured (skip verification)
     */
    public function verifyWebhookSignature(?string $receivedSecret): bool
    {
        // ✅ If no webhook secret is configured, skip verification
        if (empty($this->webhookSecret)) {
            Log::info('Fapshi Webhook: No secret configured, skipping verification');
            return true;
        }

        // ✅ If secret is configured but none received, reject
        if (empty($receivedSecret)) {
            Log::warning('Fapshi Webhook: Missing webhook secret in request');
            return false;
        }

        return $receivedSecret === $this->webhookSecret;
    }

    /**
     * Process Webhook Payload
     */
    public function processWebhook(array $payload): void
    {
        Log::info('Fapshi Webhook Received', ['payload' => $payload]);

        $transId = $payload['transId'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$transId || !$status) {
            Log::warning('Fapshi Webhook: Missing transId or status', ['payload' => $payload]);
            return;
        }

        // Find the transaction
        $transaction = PaymentTransaction::where('transaction_id', $transId)->first();

        if (!$transaction) {
            Log::warning('Fapshi Webhook: Transaction not found', ['transId' => $transId]);
            return;
        }

        // Update transaction status
        $transaction->update([
            'status' => strtolower($status),
            'payment_data' => $payload,
            'confirmed_at' => now(),
        ]);

        // Handle payment status
        if ($status === 'SUCCESSFUL') {
            $this->handleSuccessfulPayment($transaction);
        } elseif ($status === 'FAILED') {
            $this->handleFailedPayment($transaction, $payload['reason'] ?? null);
        } elseif ($status === 'EXPIRED') {
            $this->handleExpiredPayment($transaction);
        }
    }


    /**
     * Handle Successful Payment — supports new / renew / upgrade.
     */
    protected function handleSuccessfulPayment($transaction): void
    {
        if (!$transaction->subscription_id) {
            \Log::warning('Fapshi: No subscription for transaction', ['transaction_id' => $transaction->id]);
            return;
        }

        $subscription = Subscription::find($transaction->subscription_id);
        if (!$subscription) {
            \Log::warning('Fapshi: Subscription not found', ['subscription_id' => $transaction->subscription_id]);
            return;
        }

        $plan = $subscription->plan;
        $owner = $subscription->user ?? $subscription->business?->owner;

        $startDate = now();
        $endDate = $startDate->copy()->addMonths($subscription->duration_months ?? 12);

        // ============================================================
        // ✅ UPGRADE HANDLING — cancel old sub via link, preserve audit trail
        // ============================================================
        $upgradedFrom = null;
        if ($subscription->upgraded_from_subscription_id) {
            $upgradedFrom = Subscription::find($subscription->upgraded_from_subscription_id);

            if ($upgradedFrom && $upgradedFrom->status !== Subscription::STATUS_CANCELLED) {
                $upgradedFrom->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'credit_balance' => 0,
                    'failure_reason' => 'Upgraded to ' . ($plan->name ?? 'new plan'),
                ]);

                \Log::info('Fapshi: Previous subscription cancelled after upgrade', [
                    'old_subscription_id' => $upgradedFrom->id,
                    'new_subscription_id' => $subscription->id,
                    'new_plan' => $plan?->name,
                ]);
            }
        }

        // ============================================================
        // ✅ SAFETY NET — cancel any OTHER active sub for this user
        //    Catches orphaned actives the link-based cancel would miss.
        //    Also zeroes their credit_balance (already transferred).
        // ============================================================
        if ($owner) {
            $cancelled = Subscription::where('user_id', $owner->id)
                ->where('id', '!=', $subscription->id)
                ->whereIn('status', [
                    Subscription::STATUS_ACTIVE,
                    Subscription::STATUS_EXPIRING_SOON,
                    Subscription::STATUS_GRACE_PERIOD,
                ])
                ->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'credit_balance' => 0,
                    'failure_reason' => 'Superseded by subscription #' . $subscription->id,
                ]);

            if ($cancelled > 0) {
                \Log::info('Fapshi: Safety-net cancelled other active subscriptions', [
                    'new_subscription_id' => $subscription->id,
                    'user_id' => $owner->id,
                    'cancelled_count' => $cancelled,
                ]);
            }
        }

        // ============================================================
        // Activate the new subscription
        // ============================================================
        $subscription->update([
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_confirmed_at' => now(),
            'failure_reason' => null,
            'user_id' => $owner?->id,
        ]);

        if ($subscription->business) {
            $subscription->business->update(['has_active_subscription' => true]);
        }

        // ============================================================
        // Auto-publish approved businesses + promote owner role
        // ============================================================
        if ($owner) {
            $approvedBusinesses = Business::where('owner_id', $owner->id)
                ->where('status', 'approved')
                ->get();

            foreach ($approvedBusinesses as $approvedBusiness) {
                $approvedBusiness->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                \Log::info('Business auto-published', ['business_id' => $approvedBusiness->id]);
            }

            if ($owner->role === User::ROLE_USER) {
                $owner->update([
                    'role' => User::ROLE_OWNER,
                    'status' => User::STATUS_ACTIVE,
                ]);
            }

            \App\Helpers\NotificationHelper::send(
                $owner,
                'Welcome as a Business Owner! 🎉',
                "Your subscription is active and your business is live!",
                route('owner.dashboard'),
                ['subscription_id' => $subscription->id],
                'account_promoted_to_owner',
                true
            );
        }

        // ============================================================
        // ✅ RESTORE HIDDEN ITEMS — new plan may accommodate them
        // ============================================================
        try {
            app(PlanEnforcementService::class)->enforce($subscription);

            \Log::info('Fapshi: Plan enforcement applied after activation', [
                'subscription_id' => $subscription->id,
                'plan' => $plan?->name,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Fapshi: Plan enforcement failed after activation', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't fail the whole activation — just log it.
        }

        \Log::info('Fapshi: Subscription activated', [
            'subscription_id' => $subscription->id,
            'user_id' => $owner?->id,
            'plan' => $plan?->name,
            'upgraded_from' => $upgradedFrom?->id,
            'amount_paid' => $transaction->amount,
        ]);

    }


    /**
     * Handle Failed Payment - UPDATED with NotificationHelper
     */
    protected function handleFailedPayment($transaction, $reason = null): void
    {
        if (!$transaction->subscription_id) {
            return;
        }

        $subscription = Subscription::find($transaction->subscription_id);
        if (!$subscription) {
            return;
        }

        $business = $subscription->business;
        $plan = $subscription->plan;

        $subscription->update([
            'status' => Subscription::STATUS_PENDING,
            'failure_reason' => $reason ?? 'Payment failed on operator network',
        ]);

        // ✅ Notify owner about payment failure
        if ($business && $business->owner) {
            try {
                NotificationHelper::send(
                    $business->owner,
                    'Payment Failed ❌',
                    "Your payment for {$plan->name} subscription failed. Reason: " . ($reason ?? 'Please try again.'),
                    route('owner.subscription.index'),
                    [
                        'subscription_id' => $subscription->id,
                        'plan_name' => $plan->name,
                        'reason' => $reason,
                    ],
                    'payment_failed',
                    true // Send push notification
                );

                // Also notify admins
                NotificationHelper::sendToAdmins(
                    'Payment Failed Alert',
                    "Payment for {$business->name}'s subscription to {$plan->name} failed. Reason: " . ($reason ?? 'Unknown'),
                    route('admin.subscriptions.show', $subscription->id),
                    [
                        'subscription_id' => $subscription->id,
                        'business_name' => $business->name,
                        'plan_name' => $plan->name,
                        'reason' => $reason,
                    ],
                    'payment_failed_alert',
                    true
                );
            } catch (\Exception $e) {
                Log::error('Fapshi: Failed to send payment failure notifications', [
                    'error' => $e->getMessage(),
                    'subscription_id' => $subscription->id,
                ]);
            }
        }

        Log::warning('Fapshi: Payment failed', [
            'subscription_id' => $subscription->id,
            'reason' => $reason,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Handle Expired Payment - UPDATED with NotificationHelper
     */
    protected function handleExpiredPayment($transaction): void
    {
        if (!$transaction->subscription_id) {
            return;
        }

        $subscription = Subscription::find($transaction->subscription_id);
        if (!$subscription) {
            return;
        }

        $business = $subscription->business;
        $plan = $subscription->plan;

        $subscription->update([
            'status' => Subscription::STATUS_PENDING,
            'failure_reason' => 'Payment link expired after 24 hours. Please initiate a new payment.',
        ]);

        // ✅ Notify owner about expired payment
        if ($business && $business->owner) {
            try {
                NotificationHelper::send(
                    $business->owner,
                    'Payment Expired ⏰',
                    "Your payment link for {$plan->name} subscription has expired. Please initiate a new payment.",
                    route('owner.subscription.index'),
                    [
                        'subscription_id' => $subscription->id,
                        'plan_name' => $plan->name,
                    ],
                    'payment_expired',
                    true // Send push notification
                );
            } catch (\Exception $e) {
                Log::error('Fapshi: Failed to send payment expired notification', [
                    'error' => $e->getMessage(),
                    'subscription_id' => $subscription->id,
                ]);
            }
        }

        Log::info('Fapshi: Payment expired', [
            'subscription_id' => $subscription->id,
            'transaction_id' => $transaction->id,
        ]);
    }
}