<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'tier', 'description', 'tagline',
        'price_monthly', 'price_yearly', 'yearly_discount_percentage',
        'max_listings', 'max_locations', 'max_services', 'max_images',
        'max_coupons', 'max_staff',
        'features', 'feature_list',
        'is_active', 'is_featured', 'is_popular',
        'sort_order', 'trial_days',
        'badge_text', 'badge_color',
    ];

    protected $casts = [
        'features' => 'array',
        'feature_list' => 'array',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'yearly_discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
        'trial_days' => 'integer',
    ];

    // Default yearly discount
    protected $attributes = [
        'yearly_discount_percentage' => 15,
    ];

    /**
     * Calculate price for monthly duration (1-11 months)
     */
    public function getMonthlyPrice($months)
    {
        if (!$this->price_monthly) {
            return null;
        }
        
        // Monthly price * number of months
        return round($this->price_monthly * $months, 2);
    }

    /**
     * Calculate price for yearly duration (1-10 years)
     */
    public function getYearlyPrice($years)
    {
        if (!$this->price_annual) {
            return null;
        }
        
        // Annual price * number of years * (1 - discount)
        $discount = $this->yearly_discount_percentage ?? 15;
        $basePrice = $this->price_annual * $years;
        $discountedPrice = $basePrice * (1 - ($discount / 100));
        
        return round($discountedPrice, 2);
    }

    /**
     * Get savings for yearly plan compared to monthly
     */
    public function getYearlySavings($years)
    {
        if (!$this->price_monthly || !$this->price_annual) {
            return 0;
        }
        
        $monthlyTotal = $this->price_monthly * 12 * $years;
        $yearlyTotal = $this->getYearlyPrice($years);
        
        return round($monthlyTotal - $yearlyTotal, 2);
    }

    /**
     * Get savings percentage for yearly plan
     */
    public function getYearlySavingsPercentage($years)
    {
        if (!$this->price_monthly || !$this->price_annual) {
            return 0;
        }
        
        $monthlyTotal = $this->price_monthly * 12 * $years;
        $yearlyTotal = $this->getYearlyPrice($years);
        
        if ($monthlyTotal <= 0) return 0;
        
        return round((($monthlyTotal - $yearlyTotal) / $monthlyTotal) * 100, 1);
    }

    /**
     * Get duration label
     */
    public static function getDurationLabel($type, $value)
    {
        if ($type === 'monthly') {
            return $value . ' Month' . ($value > 1 ? 's' : '');
        } else {
            return $value . ' Year' . ($value > 1 ? 's' : '');
        }
    }

    /**
     * Get duration icon
     */
    public static function getDurationIcon($type)
    {
        return $type === 'monthly' ? '📅' : '📆';
    }

    /**
     * Get recommended badge
     */
    public static function getRecommendedBadge($type, $value)
    {
        if ($type === 'yearly' && $value >= 3) {
            return '⭐ Best Value';
        }
        if ($type === 'monthly' && $value === 6) {
            return '⭐ Popular';
        }
        return null;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    // ============== SCOPES ==============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    
    // ============== HELPER METHODS ==============

    /**
     * Check if plan has a specific feature
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        return !empty($features[$feature]);
    }

    /**
     * WAVE A1 - THE canonical unlimited sentinel.
     *
     * Evidence for choosing -1: PlanController validates every max_* field as
     * `integer|min:-1`, so -1 is the only unlimited value an administrator can
     * actually save; the schema columns are NOT NULL integers with finite
     * defaults; and the seeder only ever writes finite values.
     */
    public const UNLIMITED = -1;

    /**
     * WAVE A1 - LEGACY COMPATIBILITY SENTINEL.
     *
     * 999 is not documented anywhere and no seeder, factory or test uses it, but
     * twelve backend call sites and several UI surfaces already treat it as
     * unlimited. Reading it as a hard limit in one layer and as unlimited in
     * another is the inconsistency this wave removes, so it is accepted here as
     * unlimited everywhere. Normalising stored plans to -1 is a data decision and
     * is deliberately NOT performed by this wave.
     */
    public const LEGACY_UNLIMITED = 999;

    /**
     * WAVE A1 - THE single predicate every limit check must use.
     *
     * `null` is deliberately NOT unlimited: the columns are NOT NULL, so null can
     * only mean "not loaded". Treating it as unlimited would fail OPEN and grant
     * an unconfigured plan unlimited access. getLimit() already coalesces a
     * missing value to 0, so this fails CLOSED.
     */
    public static function isUnlimitedLimit(int|string|null $limit): bool
    {
        if ($limit === null || $limit === '') {
            return false;
        }

        $limit = (int) $limit;

        return $limit === self::UNLIMITED || $limit === self::LEGACY_UNLIMITED;
    }

    /**
     * WAVE A1 - the numeric ceiling for an unlimited limit, for callers that must
     * compare or compute rather than branch. Finite limits pass through unchanged.
     */
    public static function normaliseLimit(int|string|null $limit): int
    {
        return self::isUnlimitedLimit($limit) ? PHP_INT_MAX : (int) $limit;
    }

    /**
     * Check if plan has unlimited resource.
     *
     * WAVE A1 - delegates to the single canonical predicate above. Behaviour is
     * unchanged for every input getLimit() can return.
     */
    public function isUnlimited(string $resource): bool
    {
        return self::isUnlimitedLimit($this->getLimit($resource));
    }

    /**
     * Get limit for a resource
     */
    public function getLimit(string $resource): int
    {
                $map = [
            'listings' => 'max_listings',
            'locations' => 'max_locations',
            'services' => 'max_services',
            'images' => 'max_images',
            'coupons' => 'max_coupons',
            'staff' => 'max_staff',
        ];

        $column = $map[$resource] ?? null;
        return $column ? ($this->{$column} ?? 0) : 0;
    }

    /**
     * Check if this is the free plan
     */
    public function isFree(): bool
    {
        return $this->tier === 'free' || $this->price_monthly == 0;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }
        return number_format($this->price_monthly, 0) . ' XAF';
    }
}


    


    

