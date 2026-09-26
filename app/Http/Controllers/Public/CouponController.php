<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponRedemptionToken;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Get active coupons for a business (public API)
     */
    public function forBusiness($businessId)
    {
        $coupons = Coupon::where('business_id', $businessId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                  ->orWhereColumn('usage_count', '<', 'usage_limit');
            })
            ->latest()
            ->get()
            ->map(function ($coupon) {
                return [
                    'id' => $coupon->id,
                    'title' => $coupon->title,
                    'description' => $coupon->description,
                    'code' => $coupon->code,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => (float) $coupon->discount_value,
                    'min_purchase' => $coupon->min_purchase ? (float) $coupon->min_purchase : null,
                    'expires_at' => $coupon->expires_at?->toIso8601String(),
                ];
            });

        return response()->json(['data' => $coupons]);
    }

    /**
     * Redeem a coupon directly (legacy — still supported).
     */
    public function redeem(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'original_amount' => 'nullable|numeric|min:0',
        ]);

        if (!$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon is no longer valid.',
            ], 400);
        }

        // Check min purchase
        if ($coupon->min_purchase && $validated['original_amount'] < $coupon->min_purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum purchase of ' . number_format($coupon->min_purchase) . ' XAF required.',
            ], 400);
        }

        $success = $coupon->redeem(
            auth()->user(),
            $validated['original_amount'] ?? null
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem coupon.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Coupon redeemed successfully!',
        ]);
    }

    /**
     * ✅ Generate a one-time redemption token for QR-based redemption.
     *
     * Called when a logged-in customer clicks "Redeem Coupon" on the
     * public business profile. Returns a URL that the QR code encodes.
     */
    public function generateToken(Request $request, Coupon $coupon)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to redeem a coupon.',
                'requires_login' => true,
            ], 401);
        }

        // Coupon must be active / valid
        if (!$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon is no longer valid.',
            ], 400);
        }

        // Customer must not have hit the per-user limit
        if ($coupon->userHasHitLimit($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You have already redeemed this coupon the maximum number of times.',
            ], 400);
        }

        // Kill any existing active token for this user + coupon so we
        // don't accumulate dangling tokens if they click twice.
        CouponRedemptionToken::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->whereNull('redeemed_at')
            ->delete();

        $token = CouponRedemptionToken::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'token' => CouponRedemptionToken::generateToken(),
            'intended_use' => 'in_person',
            'context' => [
                'generated_from' => 'public_business_profile',
                'generated_at' => now()->toIso8601String(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token->token,
                'url' => url('/redeem/' . $token->token),
                'expires_at' => $token->expires_at->toIso8601String(),
                'expires_in_seconds' => 600,
                'coupon' => [
                    'id' => $coupon->id,
                    'title' => $coupon->title,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => (float) $coupon->discount_value,
                ],
            ],
        ]);
    }
}