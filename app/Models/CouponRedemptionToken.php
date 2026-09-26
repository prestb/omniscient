<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CouponRedemptionToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'token',
        'intended_use',
        'context',
        'expires_at',
        'redeemed_at',
        'redeemed_by',
    ];

    protected $casts = [
        'context' => 'array',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    // ============== RELATIONSHIPS ==============

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function redeemer()
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    // ============== HELPERS ==============

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isRedeemed(): bool
    {
        return $this->redeemed_at !== null;
    }

    /**
     * Is this token still usable?
     * Not redeemed, not expired.
     */
    public function isUsable(): bool
    {
        return !$this->isRedeemed() && !$this->isExpired();
    }

    /**
     * Scope: only tokens that haven't been redeemed and haven't expired.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('redeemed_at')
                     ->where('expires_at', '>', now());
    }

    /**
     * Generate a fresh token string. 64 URL-safe characters.
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Mark this token as redeemed by the given owner/user.
     */
    public function markRedeemed(User $by): void
    {
        $this->update([
            'redeemed_at' => now(),
            'redeemed_by' => $by->id,
        ]);
    }

    // ============== ROUTE MODEL BINDING ==============
    // Laravel resolves `/redeem/{token}` by the token column, not by ID.

    public function getRouteKeyName(): string
    {
        return 'token';
    }
}