<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRING_SOON = 'expiring_soon';
    const STATUS_EXPIRED = 'expired';
    const STATUS_GRACE_PERIOD = 'grace_period';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'business_id',
        'plan_id',
        'status',
        'start_date',
        'end_date',
        'duration_months',
        'total_price',
        'monthly_price',
        'discount_percentage',
        'credit_balance',
        'upgraded_from_subscription_id',
        'is_trial',
        'trial_end_date',
        'cancelled_at',
        'grace_period_ends_at',
        'failure_reason',
        'payment_confirmed_at',
        'action_type', // ✅ NEW — tracks the action that led to this subscription (e.g., 'upgrade', 'renewal', etc.)
        'downgrade_grace_ends_at',
    ];

    protected $guarded = [
        'days_remaining',
        'duration_label',
        'formatted_price',
        'formatted_monthly_price',
        'status_label',
        'status_badge',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'trial_end_date' => 'date',
        'cancelled_at' => 'datetime',
        'grace_period_ends_at' => 'date',
        'is_trial' => 'boolean',
        'duration_months' => 'integer',
        'total_price' => 'float',
        'monthly_price' => 'float',
        'discount_percentage' => 'float',
        'credit_balance' => 'float',
        'downgrade_grace_ends_at' => 'date',   // ✅ correct: key + cast
    ];

    protected $appends = [
        'duration_label',
        'formatted_price',
        'formatted_monthly_price',
        'days_remaining',
        'status_label',
        'status_badge',
        'grace_period_end',   // ✅ NEW — alias for grace_period_ends_at
    ];

    // ============== RELATIONSHIPS ==============

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function upgradedFrom()
    {
        return $this->belongsTo(Subscription::class, 'upgraded_from_subscription_id');
    }

    public function upgradedTo()
    {
        return $this->hasOne(Subscription::class, 'upgraded_from_subscription_id');
    }

    // ============== ACCESSORS ==============

    public function getDurationLabelAttribute()
    {
        if (!$this->duration_months) {
            if ($this->start_date && $this->end_date) {
                $start = Carbon::parse($this->start_date);
                $end = Carbon::parse($this->end_date);
                $months = $start->diffInMonths($end);
                if ($months > 0) {
                    $this->duration_months = $months;
                    $this->updateQuietly(['duration_months' => $months]);
                }
            }
            if (!$this->duration_months) {
                return 'N/A';
            }
        }

        $months = $this->duration_months;

        if ($months >= 12 && $months % 12 === 0) {
            $years = $months / 12;
            return $years . ' Year' . ($years > 1 ? 's' : '');
        }

        return $months . ' Month' . ($months > 1 ? 's' : '');
    }

    public function getFormattedPriceAttribute()
    {
        if (!$this->total_price) {
            if ($this->monthly_price && $this->duration_months) {
                $basePrice = $this->monthly_price * $this->duration_months;
                $discount = $this->discount_percentage ?? 0;
                $total = $basePrice * (1 - ($discount / 100));

                $this->total_price = $total;
                $this->updateQuietly(['total_price' => $total]);
            }
            if (!$this->total_price) {
                return 'N/A';
            }
        }
        return number_format($this->total_price, 0, '.', ',') . ' FCFA';
    }

    public function getFormattedMonthlyPriceAttribute()
    {
        $price = $this->monthly_price ?? $this->plan?->price_monthly ?? 0;
        return $price ? number_format($price, 0, '.', ',') . ' FCFA' : 'N/A';
    }

    public function getDaysRemainingAttribute()
    {
        if (!$this->end_date) {
            return null;
        }
        $now = now()->startOfDay();
        $end = Carbon::parse($this->end_date)->startOfDay();
        $days = $now->diffInDays($end, false);
        return $days < 0 ? 0 : $days;
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRING_SOON => 'Expiring Soon',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_GRACE_PERIOD => 'Grace Period',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_ACTIVE => 'bg-green-100 text-green-800 border border-green-200',
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
            self::STATUS_EXPIRING_SOON => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
            self::STATUS_EXPIRED => 'bg-red-100 text-red-800 border border-red-200',
            self::STATUS_GRACE_PERIOD => 'bg-purple-100 text-purple-800 border border-purple-200',
            self::STATUS_SUSPENDED => 'bg-orange-100 text-orange-800 border border-orange-200',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-600 border border-gray-200',
        ];
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-600 border border-gray-200';
    }

    /**
     * Alias for grace_period_ends_at — used in ProcessGracePeriod log line.
     */
    public function getGracePeriodEndAttribute()
    {
        return $this->grace_period_ends_at;
    }

    // ============== SCOPES ==============

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpiringSoon($query)
    {
        return $query->where('status', self::STATUS_EXPIRING_SOON);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeGracePeriod($query)
    {
        return $query->where('status', self::STATUS_GRACE_PERIOD);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    /**
     * Scope: subscriptions whose end_date has passed and need status transition.
     */
    public function scopeNeedsExpirationCheck($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ACTIVE,
            self::STATUS_EXPIRING_SOON,
        ])->where('end_date', '<=', now()->toDateString());
    }

    /**
     * Scope: subscriptions whose grace period has ended and need suspension.
     */
    public function scopeGracePeriodExpired($query)
    {
        return $query->where('status', self::STATUS_GRACE_PERIOD)
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', now()->toDateString());
    }

    // ============== HELPER METHODS ==============

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isExpiringSoon()
    {
        return $this->status === self::STATUS_EXPIRING_SOON;
    }

    public function isInGracePeriod()
    {
        return $this->status === self::STATUS_GRACE_PERIOD;
    }

    public function isSuspended()
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * True when end_date has passed.
     */
    public function hasExpired(): bool
    {
        if (!$this->end_date) {
            return false;
        }
        return Carbon::parse($this->end_date)->isPast();
    }

    /**
     * True when days_remaining is within the expiring_soon threshold.
     */
    public function shouldBeExpiringSoon(): bool
    {
        $threshold = config('subscription.expiring_soon_threshold', 30);
        $days = $this->days_remaining;

        return $days !== null
            && $days > 0
            && $days <= $threshold;
    }

    /**
     * Move an active/expiring subscription into grace period.
     */
    public function expireWithGracePeriod(int $graceDays = 7): void
    {
        $this->update([
            'status' => self::STATUS_GRACE_PERIOD,
            'grace_period_ends_at' => now()->addDays($graceDays)->toDateString(),
        ]);
    }

    /**
     * Suspend a subscription whose grace period has ended.
     */
    public function suspendAfterGracePeriod(): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
        ]);

        // Suspend the business too — but only after grace ends
        if ($this->business) {
            $this->business->update(['status' => 'draft']);
        }
    }

    public function markAsExpiringSoon(): void
    {
        $this->update(['status' => self::STATUS_EXPIRING_SOON]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    public function renew($newEndDate, $planId = null)
    {
        $planId = $planId ?? $this->plan_id;

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'start_date' => now()->toDateString(),
            'end_date' => $newEndDate,
            'plan_id' => $planId,
            'grace_period_ends_at' => null, // ✅ clear grace on renewal
        ]);

        if ($this->business && $this->business->status === 'suspended') {
            $this->business->update(['status' => 'published']);
        }

        return true;
    }

    public function canCreateBranch($businessId)
    {
        if (!$this->isActive()) {
            return false;
        }

        $maxBranches = $this->plan->max_branches ?? 0;
        if ($maxBranches === -1) {
            return true;
        }

        $business = null;
        if ($businessId instanceof \App\Models\Business) {
            $business = $businessId;
        } elseif (is_numeric($businessId)) {
            $business = \App\Models\Business::find($businessId);
        }

        if (!$business) {
            return false;
        }

        $branchCount = $business->branches()->count();
        return $branchCount < $maxBranches;
    }

    public static function calculateEndDate($startDate, $months)
    {
        return $startDate->copy()->addMonths($months);
    }

    public function canCreateBusiness()
    {
        if (!$this->isActive()) {
            return false;
        }

        $maxBusinesses = $this->plan->max_businesses ?? 0;
        if ($maxBusinesses === -1) {
            return true;
        }

        $businessCount = Business::where('owner_id', $this->business?->owner_id)
            ->whereNotIn('status', ['deleted', 'rejected'])
            ->count();

        return $businessCount < $maxBusinesses;
    }

    // ============== PRORATION METHODS ==============

    public function calculateUpgradeCredit(): float
    {
        if (!$this->isActive() && $this->status !== self::STATUS_EXPIRING_SOON) {
            return 0.0;
        }

        if (!$this->start_date || !$this->end_date || !$this->total_price) {
            return 0.0;
        }

        $start = Carbon::parse($this->start_date)->startOfDay();
        $end = Carbon::parse($this->end_date)->startOfDay();
        $today = now()->startOfDay();

        if ($end->lessThanOrEqualTo($today)) {
            return 0.0;
        }

        $totalDays = max(1, $start->diffInDays($end));
        $daysLeft = max(0, $today->diffInDays($end));

        if ($daysLeft <= 0) {
            return 0.0;
        }

        $credit = ((float) $this->total_price) * ($daysLeft / $totalDays);

        return round($credit, 2);
    }

    public function consumeCredit(float $amount): float
    {
        $amount = max(0, $amount);
        $balance = (float) ($this->credit_balance ?? 0);

        if ($amount <= 0 || $balance <= 0) {
            return 0.0;
        }

        $consumed = min($amount, $balance);
        $this->update(['credit_balance' => round($balance - $consumed, 2)]);

        return $consumed;
    }

    public function getUpgradeCreditSummaryAttribute(): array
    {
        $credit = $this->calculateUpgradeCredit();
        return [
            'credit_amount' => $credit,
            'days_remaining' => $this->days_remaining ?? 0,
            'total_days' => $this->start_date && $this->end_date
                ? max(1, Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)))
                : 0,
            'old_plan_name' => $this->plan?->name ?? 'Current Plan',
            'old_plan_price' => (float) ($this->total_price ?? 0),
        ];
    }
}