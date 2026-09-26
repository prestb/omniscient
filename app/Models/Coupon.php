<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'title',
        'description',
        'code',
        'image',
        'discount_type',
        'discount_value',
        'min_purchase',
        'max_discount',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'starts_at',
        'expires_at',
        'is_active',
        'scope',
        'hidden_at',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'hidden_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active)
            return false;
        if ($this->starts_at && $this->starts_at->isFuture())
            return false;
        if ($this->expires_at && $this->expires_at->isPast())
            return false;
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit)
            return false;
        return true;
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active)
            return 'inactive';
        if ($this->expires_at && $this->expires_at->isPast())
            return 'expired';
        if ($this->starts_at && $this->starts_at->isFuture())
            return 'scheduled';
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit)
            return 'used_up';
        return 'active';
    }

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function redemptionTokens()
    {
        return $this->hasMany(CouponRedemptionToken::class);
    }

    /**
     * How many times has this user already redeemed this coupon?
     */
    public function redemptionCountByUser(User $user): int
    {
        return $this->redemptions()->where('user_id', $user->id)->count();
    }

    /**
     * Has this user already hit the per-user limit?
     */
    public function userHasHitLimit(User $user): bool
    {
        if (!$this->per_user_limit) {
            return false;
        }
        return $this->redemptionCountByUser($user) >= $this->per_user_limit;
    }

    /**
     * Redeem a coupon (increment usage, log redemption)
     */
    public function redeem(?User $user = null, ?float $originalAmount = null): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Calculate discount
        $discount = 0;
        if ($originalAmount) {
            if ($this->discount_type === 'percentage') {
                $discount = $originalAmount * ($this->discount_value / 100);
                if ($this->max_discount && $discount > $this->max_discount) {
                    $discount = $this->max_discount;
                }
            } else {
                $discount = $this->discount_value;
            }
        }

        // Log redemption
        $this->redemptions()->create([
            'user_id' => $user?->id,
            'code_used' => $this->code,
            'discount_amount' => $discount,
            'original_amount' => $originalAmount,
            'final_amount' => $originalAmount ? ($originalAmount - $discount) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Increment usage
        $this->increment('usage_count');

        return true;
    }
}