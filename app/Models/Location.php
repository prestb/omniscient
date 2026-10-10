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
        'owner_id',
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
    /**
     * PHASE 22A - CANONICAL OWNERSHIP.
     *
     * A Location is an account-owned resource. Authorization derives from
     * `owner_id`, NOT from `business_id`. A Business-less Professional owns
     * its Location exactly as a Business-backed owner owns theirs.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * OPTIONAL organization context. `business_id` may be NULL, and is never
     * the ownership or authorization check.
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
     * PHASE 23J — THE authoritative "open at this instant" SQL predicate.
     *
     * Opening status is TIME-VARYING: it changes as the clock advances even when
     * no row is written. It therefore cannot be represented by a Boolean in a
     * search index, and `/search` used to filter on exactly such a stale indexed
     * value while `/directory` evaluated it live — so the two engines disagreed
     * about the same Listing.
     *
     * This scope is the ONE definition both surfaces use. It is a faithful
     * extraction of the predicate `/directory` already applied inline, so the
     * directory's behaviour is unchanged; `/search` now resolves the currently
     * open Locations with it and narrows the engine by `location_id`.
     *
     * Rules, matching {@see self::getIsOpenNowAttribute()}:
     *   1. A closed-day override for the date    -> closed.
     *   2. A special-hours override for the date -> judged solely on those hours.
     *   3. Otherwise -> the weekly hours for the day, including 24h and
     *      overnight windows.
     *
     * The instant is injectable so tests control time deterministically instead
     * of depending on the wall clock.
     */
    public function scopeOpenNow($query, ?\Carbon\CarbonInterface $at = null)
    {
        $at ??= now();

        $dayOfWeek = $at->dayOfWeek;          // 0 = Sunday
        $time = $at->format('H:i:s');
        $date = $at->toDateString();

        return $query->where(function ($q) use ($dayOfWeek, $time, $date) {
            // Case A — no closed override today, no special-hours override
            // today, and the weekly hours say open.
            $q->where(function ($sub) use ($dayOfWeek, $time, $date) {
                $sub->whereDoesntHave('hourOverrides', function ($o) use ($date) {
                    $o->whereDate('date', $date)->where('is_closed', true);
                });

                $sub->whereDoesntHave('hourOverrides', function ($o) use ($date) {
                    $o->whereDate('date', $date)
                        ->where('is_closed', false)
                        ->whereNotNull('opens_at')
                        ->whereNotNull('closes_at');
                });

                $sub->whereHas('hours', function ($h) use ($dayOfWeek, $time) {
                    $h->where('day_of_week', $dayOfWeek)
                        ->where('is_closed', false)
                        ->where(function ($query) use ($time) {
                            $query->where('is_24h', true)
                                ->orWhere(function ($q) use ($time) {
                                    $q->whereTime('opens_at', '<=', $time)
                                        ->whereTime('closes_at', '>=', $time);
                                })
                                ->orWhere(function ($q) use ($time) {
                                    // Overnight window (opens after it closes).
                                    //
                                    // PHASE 23J — `whereColumn`, NOT
                                    // `whereTime($col, '>', 'closes_at')`.
                                    // The latter binds the STRING 'closes_at'
                                    // as a value, producing
                                    //   time(`opens_at`) > 'closes_at'
                                    // which MySQL coerces to `> 0` and is
                                    // therefore TRUE for any time after
                                    // midnight. That made this branch match
                                    // almost every Location with hours today,
                                    // so the directory's `open_now` filter
                                    // reported closed places as open. Case B
                                    // below already used `whereColumn`.
                                    $q->whereColumn('opens_at', '>', 'closes_at')
                                        ->where(function ($sub) use ($time) {
                                            $sub->whereTime('opens_at', '<=', $time)
                                                ->orWhereTime('closes_at', '>=', $time);
                                        });
                                });
                        });
                });
            });

            // Case B — a special-hours override today and the instant falls
            // inside it. Judged solely on the override.
            $q->orWhereHas('hourOverrides', function ($o) use ($date, $time) {
                $o->whereDate('date', $date)
                    ->where('is_closed', false)
                    ->whereNotNull('opens_at')
                    ->whereNotNull('closes_at')
                    ->where(function ($t) use ($time) {
                        $t->where(function ($w) use ($time) {
                            // Same-day window.
                            $w->whereTime('opens_at', '<=', $time)
                                ->whereTime('closes_at', '>=', $time)
                                ->whereColumn('opens_at', '<=', 'closes_at');
                        })
                            ->orWhere(function ($w) use ($time) {
                                // Overnight window.
                                $w->whereColumn('opens_at', '>', 'closes_at')
                                    ->where(function ($sub) use ($time) {
                                        $sub->whereTime('opens_at', '<=', $time)
                                            ->orWhereTime('closes_at', '>=', $time);
                                    });
                            });
                    });
            });
        });
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
