<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CouponRedemptionToken;
use App\Models\CouponRedemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RedemptionController extends Controller
{
    /**
     * Owner-facing page. Reads the token from the URL and shows a
     * confirmation screen with the coupon + customer details.
     *
     * Requires authentication. The owner must own the business the
     * coupon belongs to.
     */
    public function show(Request $request, string $token)
    {
        $user = auth()->user();

        // If not logged in, kick to login with a return-to for this redeem URL
        if (!$user) {
            return redirect()->guest(route('login', [
                'redirect' => route('coupons.redeem.show', ['token' => $token]),
            ]));
        }

        $redemptionToken = CouponRedemptionToken::where('token', $token)
            ->with(['coupon.business', 'user'])
            ->first();

        // ============== VALIDATION ==============

        if (!$redemptionToken) {
            return Inertia::render('Public/RedeemCoupon', [
                'state' => 'invalid',
                'message' => 'This redemption link is invalid.',
            ]);
        }

        $coupon = $redemptionToken->coupon;
        $business = $coupon->business;

        // Only the owner of the business can redeem
        if (!$user->isOwner() || $user->id !== $business->owner_id) {
            // Super admins can view for support purposes, but not redeem (yet)
            if (!$user->isAdmin()) {
                return Inertia::render('Public/RedeemCoupon', [
                    'state' => 'forbidden',
                    'message' => 'You do not have permission to redeem this coupon.',
                ]);
            }
        }

        if ($redemptionToken->isRedeemed()) {
            return Inertia::render('Public/RedeemCoupon', [
                'state' => 'already_redeemed',
                'message' => 'This coupon has already been redeemed.',
                'redeemed_at' => $redemptionToken->redeemed_at?->toIso8601String(),
            ]);
        }

        if ($redemptionToken->isExpired()) {
            return Inertia::render('Public/RedeemCoupon', [
                'state' => 'expired',
                'message' => 'This redemption link has expired. Please ask the customer to refresh their QR code.',
            ]);
        }

        if (!$coupon->isValid()) {
            return Inertia::render('Public/RedeemCoupon', [
                'state' => 'coupon_invalid',
                'message' => 'This coupon is no longer valid.',
            ]);
        }

        // ============== READY TO CONFIRM ==============

        $branches = $business->branches()
            ->whereNull('hidden_at')
            ->get(['id', 'name', 'is_primary']);

        return Inertia::render('Public/RedeemCoupon', [
            'state' => 'ready',
            'token' => $redemptionToken->token,
            'coupon' => [
                'id' => $coupon->id,
                'title' => $coupon->title,
                'description' => $coupon->description,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float) $coupon->discount_value,
                'min_purchase' => $coupon->min_purchase ? (float) $coupon->min_purchase : null,
                'max_discount' => $coupon->max_discount ? (float) $coupon->max_discount : null,
                'usage_limit' => $coupon->usage_limit,
                'usage_count' => $coupon->usage_count,
                'per_user_limit' => $coupon->per_user_limit,
            ],
            'customer' => [
                'id' => $redemptionToken->user->id,
                'name' => $redemptionToken->user->name,
                'email' => $redemptionToken->user->email,
            ],
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'branches' => $branches,
            'expires_at' => $redemptionToken->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Confirm and log the redemption.
     */
    public function confirm(Request $request, string $token)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // ============== VALIDATION ==============

        $redemptionToken = CouponRedemptionToken::where('token', $token)
            ->with(['coupon.business', 'user'])
            ->first();

        if (!$redemptionToken) {
            return response()->json(['success' => false, 'message' => 'Invalid token.'], 400);
        }

        if ($redemptionToken->isRedeemed()) {
            return response()->json(['success' => false, 'message' => 'This coupon has already been redeemed.'], 400);
        }

        if ($redemptionToken->isExpired()) {
            return response()->json(['success' => false, 'message' => 'This link has expired.'], 400);
        }

        $coupon = $redemptionToken->coupon;
        $business = $coupon->business;

        if ($user->id !== $business->owner_id) {
            return response()->json(['success' => false, 'message' => 'You are not the owner of this business.'], 403);
        }

        if (!$coupon->isValid()) {
            return response()->json(['success' => false, 'message' => 'This coupon is no longer valid.'], 400);
        }

        // Branch must belong to this business if provided
        if (!empty($validated['branch_id'])) {
            $belongs = $business->branches()->where('id', $validated['branch_id'])->exists();
            if (!$belongs) {
                return response()->json(['success' => false, 'message' => 'Invalid branch.'], 400);
            }
        }

        // ============== DO THE REDEMPTION ==============

        try {
            DB::transaction(function () use ($redemptionToken, $coupon, $user, $validated) {
                // Create the redemption record
                CouponRedemption::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $redemptionToken->user_id,
                    'code_used' => $coupon->code,
                    'discount_amount' => null,   // no cart total at in-person checkout
                    'original_amount' => null,
                    'final_amount' => null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'method' => 'qr_scan',
                        'branch_id' => $validated['branch_id'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'redeemed_by' => $user->id,
                        'token_id' => $redemptionToken->id,
                    ],
                ]);

                $coupon->increment('usage_count');
                $redemptionToken->markRedeemed($user);
            });

            return response()->json([
                'success' => true,
                'message' => 'Coupon redeemed successfully.',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Coupon redemption failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem coupon. Please try again.',
            ], 500);
        }
    }
}