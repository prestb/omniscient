<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Location — a UNIVERSAL physical place.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PHASE 10 — LOCATION IS A PHYSICAL PLACE (not a "branch", not a Listing)
 * ─────────────────────────────────────────────────────────────────────────
 * A Location answers only "where is this physically?". It is:
 *   - NOT the discoverable entity — the {@see \App\Models\Listing} is.
 *   - NOT a "branch of a business". The word Branch is gone from this
 *     abstraction.
 *   - NOT required to belong to a Business. `business_id` is nullable so a
 *     standalone Listing (or a future Event / venue / campus) can own a
 *     physical place with no organization at all.
 *
 *   Listing.location_id ──► Location        (optional: 0 or 1)
 *
 * ── Invariants ───────────────────────────────────────────────────────
 *   - A Location has MANY Listings at most in edge cases, but the canonical
 *     model is one Listing per Location.
 *   - A Listing has ZERO OR ONE Location; multiple places == multiple
 *     Listings, never one Listing with many locations.
 *   - A Location carries NO discoverable identity (no slug / type /
 *     description / categories / services / reviews / media / analytics /
 *     leads / coupons). Those belong to the Listing.
 *   - Hours (`location_hours`) and date overrides
 *     (`location_hour_overrides`) describe when the PHYSICAL PLACE operates
 *     and are owned here. General/discoverable contact stays on the Listing;
 *     a place-specific phone/WhatsApp lives here.
 *
 * Location vs Service Area (OPEN): a Location is WHERE a listing physically
 * is. A "Service Area" (where a listing *operates*, e.g. a plumber covering
 * Buea + Limbe + Mutengene) is a FUTURE concern and is deliberately NOT
 * modelled here, so it can be added later without redefining Location.
 *
 * See docs/PHASE_10_LOCATION_FOUNDATION.md.
 */
class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'is_primary',
        'country_id',
        'region_id',
        'city_id',
        'area_id',
        'address',
        'landmark',
        'postal_code',
        'latitude',
        'longitude',
        'phone',
        'whatsapp',
        'status',
        'sort_order',
        'hidden_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'sort_order' => 'integer',
        'hidden_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';
    const STATUS_UNLISTED = 'unlisted';

    // ============== RELATIONSHIPS ==============

    /**
     * The organization this place MAY belong to. NULLABLE — a Location does
     * not require a Business.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * The Listing(s) that physically happen at this place.
     *
     * A Listing has at most one Location; the canonical model is one Listing
     * per Location.
     */
    public function listings()
    {
        return $this->hasMany(Listing::class, 'location_id');
    }

    /**
     * Weekly operating hours of this physical place.
     */
    public function hours()
    {
        return $this->hasMany(LocationHour::class)->orderBy('sort_order');
    }

    /**
     * Date-specific overrides (closed days / special hours).
     */
    public function hourOverrides()
    {
        return $this->hasMany(LocationHourOverride::class)->orderBy('date');
    }

    // ============== SCOPES ==============

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeVisible($query)
    {
        return $query->whereNull('hidden_at');
    }

    public function scopeHidden($query)
    {
        return $query->whereNotNull('hidden_at');
    }

    // ============== HOURLY STATE (override-aware) ==============

    protected ?LocationHourOverride $memoizedTodayOverride = null;
    protected bool $todayOverrideMemoized = false;
    protected ?bool $memoizedIsOpenNow = null;
    protected ?LocationHourOverride $memoizedTodaySpecialHours = null;
    protected bool $todaySpecialHoursMemoized = false;

    /**
     * Is this location closed today by a date override?
     * Returns the override model if one exists for today, null otherwise.
     */
    public function getTodayOverrideAttribute()
    {
        if ($this->todayOverrideMemoized) {
            return $this->memoizedTodayOverride;
        }

        $override = $this->hourOverrides()
            ->forDate(today())
            ->first();

        $this->memoizedTodayOverride = $override;
        $this->todayOverrideMemoized = true;

        return $override;
    }

    /**
     * Today's special-hours override, or null.
     */
    public function getTodaySpecialHoursAttribute()
    {
        if ($this->todaySpecialHoursMemoized) {
            return $this->memoizedTodaySpecialHours;
        }

        $override = $this->today_override;

        $this->memoizedTodaySpecialHours = ($override && $override->is_special_hours)
            ? $override
            : null;

        $this->todaySpecialHoursMemoized = true;

        return $this->memoizedTodaySpecialHours;
    }

    /**
     * Open-now logic, override-aware:
     *   1. Closed-day override → closed.
     *   2. Special-hours override → judge solely on those hours.
     *   3. Otherwise → weekly hours.
     */
    public function getIsOpenNowAttribute()
    {
        if ($this->memoizedIsOpenNow !== null) {
            return $this->memoizedIsOpenNow;
        }

        $override = $this->today_override;

        if ($override && $override->is_closed) {
            $this->memoizedIsOpenNow = false;
            return false;
        }

        if ($override && $override->is_special_hours) {
            $this->memoizedIsOpenNow = $override->isOpenAt(now());
            return $this->memoizedIsOpenNow;
        }

        $now = now();
        $day = $now->dayOfWeek; // 0=Sunday, 6=Saturday

        if ($this->relationLoaded('hours')) {
            $hours = $this->hours->where('day_of_week', $day)->where('is_closed', false);
        } else {
            $hours = $this->hours()
                ->where('day_of_week', $day)
                ->where('is_closed', false)
                ->get();
        }

        if ($hours->isEmpty()) {
            $this->memoizedIsOpenNow = false;
            return false;
        }

        foreach ($hours as $hour) {
            if ($hour->is_24h) {
                $this->memoizedIsOpenNow = true;
                return true;
            }

            if ($hour->isOpenAt($now)) {
                $this->memoizedIsOpenNow = true;
                return true;
            }
        }

        $this->memoizedIsOpenNow = false;
        return false;
    }

    /**
     * Weekly hours summary, override-aware.
     */
    public function getHoursSummaryAttribute()
    {
        $days = [];
        $hours = $this->hours()->orderBy('sort_order')->get();
        $todayOverride = $this->today_override;
        $todayDow = now()->dayOfWeek;

        for ($i = 0; $i < 7; $i++) {
            if ($i === $todayDow && $todayOverride !== null) {
                if ($todayOverride->is_closed) {
                    $days[] = 'Closed';
                } else {
                    $days[] = $todayOverride->formatted_hours . ' (special)';
                }
                continue;
            }

            $dayHours = $hours->where('day_of_week', $i);
            if ($dayHours->isEmpty()) {
                $days[] = 'Closed';
            } else {
                $formatted = $dayHours->map(function ($hour) {
                    return $hour->formatted_hours;
                })->implode(', ');
                $days[] = $formatted;
            }
        }

        return $days;
    }

    // ============== HELPERS ==============

    /**
     * Whether this location is publicly visible: active AND not hidden.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->hidden_at === null;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isTemporarilyUnavailable(): bool
    {
        return $this->status === self::STATUS_TEMPORARILY_UNAVAILABLE;
    }

    public function getFullAddressAttribute()
    {
        $parts = [];

        if ($this->address) {
            $parts[] = $this->address;
        }

        if ($this->area) {
            $parts[] = $this->area->name;
        }

        if ($this->city) {
            $parts[] = $this->city->name;
        }

        if ($this->region) {
            $parts[] = $this->region->name;
        }

        if ($this->country) {
            $parts[] = $this->country->name;
        }

        return implode(', ', $parts);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_ACTIVE => 'badge-active',
            self::STATUS_TEMPORARILY_UNAVAILABLE => 'badge-pending',
            self::STATUS_UNLISTED => 'badge-suspended',
        ];

        return $badges[$this->status] ?? 'badge-draft';
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }
}
