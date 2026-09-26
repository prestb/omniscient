<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Branch extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'business_id',
        'name',
        'is_primary',
        'country_id',
        'region_id',
        'city_id',
        'area_id',
        'address',
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

    // Relationships
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

    public function hours()
    {
        return $this->hasMany(BusinessHour::class)->orderBy('day_of_week');
    }

    public function hourOverrides()
    {
        return $this->hasMany(BranchHourOverride::class)->orderBy('date');
    }

    /**
     * Is this branch closed today by a date override?
     * Returns the override model if closed, null otherwise.
     *
     * ✅ Memoized per model instance.
     */
    protected ?BranchHourOverride $memoizedTodayOverride = null;
    protected bool $todayOverrideMemoized = false;

    public function getTodayOverrideAttribute()
    {
        if ($this->todayOverrideMemoized) {
            return $this->memoizedTodayOverride;
        }

        // ✅ Always use the SQL query — the relation-loaded fast path was
        //    fragile due to timezone drift between the stored date and isToday().
        //    The query uses `whereDate('date', today())` which handles date
        //    comparison at the DB level regardless of timezone.
        $override = $this->hourOverrides()
            ->forDate(today())
            ->first();

        $this->memoizedTodayOverride = $override;
        $this->todayOverrideMemoized = true;

        return $override;
    }

    // Scopes
    // public function scopeActive($query)
    // {
    //     return $query->where('status', self::STATUS_ACTIVE);
    // }

    // public function scopePrimary($query)
    // {
    //     return $query->where('is_primary', true);
    // }

    // public function scopeOrdered($query)
    // {
    //     return $query->orderBy('sort_order')->orderBy('name');
    // }

    // Helpers
    // public function getFullAddressAttribute()
    // {
    //     $parts = [];

    //     if ($this->address) {
    //         $parts[] = $this->address;
    //     }

    //     if ($this->area) {
    //         $parts[] = $this->area->name;
    //     }

    //     if ($this->city) {
    //         $parts[] = $this->city->name;
    //     }

    //     if ($this->region) {
    //         $parts[] = $this->region->name;
    //     }

    //     if ($this->country) {
    //         $parts[] = $this->country->name;
    //     }

    //     return implode(', ', $parts);
    // }

    // public function getStatusBadgeAttribute()
    // {
    //     $badges = [
    //         self::STATUS_ACTIVE => 'badge-active',
    //         self::STATUS_TEMPORARILY_UNAVAILABLE => 'badge-pending',
    //         self::STATUS_UNLISTED => 'badge-suspended',
    //     ];

    //     return $badges[$this->status] ?? 'badge-draft';
    // }

    // public function getStatusLabelAttribute()
    // {
    //     return ucfirst(str_replace('_', ' ', $this->status));
    // }

    // public function isActive()
    // {
    //     return $this->status === self::STATUS_ACTIVE;
    // }

    // public function isTemporarilyUnavailable()
    // {
    //     return $this->status === self::STATUS_TEMPORARILY_UNAVAILABLE;
    // }

    /**
     * ✅ Memoized per model instance.
     */
    protected ?bool $memoizedIsOpenNow = null;

    /**
     * ✅ Is this branch closed today by a date override?
     * Returns the override model if closed, null otherwise.
     *
     * ✅ Memoized per model instance.
     */
    // protected ?BranchHourOverride $memoizedTodayOverride = null;
    // protected bool $todayOverrideMemoized = false;

    /**
     * ✅ Today's special-hours override (if any).
     *    Derived from today_override — no extra query.
     */
    protected ?BranchHourOverride $memoizedTodaySpecialHours = null;
    protected bool $todaySpecialHoursMemoized = false;

    /**
     * ✅ Returns ANY override for today (closed OR special hours).
     *    Previously only returned closed overrides.
     */
    // public function getTodayOverrideAttribute()
    // {
    //     if ($this->todayOverrideMemoized) {
    //         return $this->memoizedTodayOverride;
    //     }

    //     // Loaded relation fast path — avoids N+1 when preloaded
    //     if ($this->relationLoaded('hourOverrides')) {
    //         $override = $this->hourOverrides
    //             ->first(fn($o) => $o->date && $o->date->isToday());
    //     } else {
    //         $override = $this->hourOverrides()
    //             ->forDate(today())
    //             ->first();
    //     }

    //     $this->memoizedTodayOverride = $override;
    //     $this->todayOverrideMemoized = true;

    //     return $override;
    // }

    /**
     * ✅ Today's special-hours override, or null.
     *    Only returns an override when it's NOT closed AND has both times.
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

    // Scopes
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

    // Helpers
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

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isTemporarilyUnavailable()
    {
        return $this->status === self::STATUS_TEMPORARILY_UNAVAILABLE;
    }

    /**
     * ✅ Memoized per model instance.
     */
    // protected ?bool $memoizedIsOpenNow = null;

    /**
     * ✅ Open-now logic, override-aware:
     *    1. If today has a CLOSED override → closed, full stop.
     *    2. If today has SPECIAL HOURS override → judge solely on those hours.
     *    3. Otherwise → weekly hours.
     */
    public function getIsOpenNowAttribute()
    {
        if ($this->memoizedIsOpenNow !== null) {
            return $this->memoizedIsOpenNow;
        }

        $override = $this->today_override;

        // 1. Closed all day by override
        if ($override && $override->is_closed) {
            $this->memoizedIsOpenNow = false;
            return false;
        }

        // 2. Special hours override — the ONLY truth for today
        if ($override && $override->is_special_hours) {
            $this->memoizedIsOpenNow = $override->isOpenAt(now());
            return $this->memoizedIsOpenNow;
        }

        // 3. No override → weekly hours
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
     * ✅ Weekly hours summary, override-aware.
     *    - Today's slot is replaced with the override's text when one exists.
     *    - Appends " (special)" so consumers can style it distinctly.
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

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'city' => $this->city?->name,
            'city_id' => $this->city_id,
            'region' => $this->region?->name,
            'region_id' => $this->region_id,
            'is_primary' => $this->is_primary,
        ];
    }

}



// public function toSearchableArray()
// {
//     return [
//         'id' => $this->id,
//         'business_id' => $this->business_id,
//         'name' => $this->name,
//         'address' => $this->address,
//         'phone' => $this->phone,
//         'city' => $this->city?->name,
//         'city_id' => $this->city_id,
//         'region' => $this->region?->name,
//         'region_id' => $this->region_id,
//         'is_primary' => $this->is_primary,
//     ];
// }

// public function getIsOpenNowAttribute()
// {
//     if ($this->memoizedIsOpenNow !== null) {
//         return $this->memoizedIsOpenNow;
//     }

//     // ✅ Date override check — reuse the memoized today_override accessor
//     //    so we don't run a duplicate existence query.
//     if ($this->today_override !== null) {
//         $this->memoizedIsOpenNow = false;
//         return false;
//     }

//     $now = now();
//     $day = $now->dayOfWeek; // 0=Sunday, 6=Saturday

//     // ✅ Prefer already-loaded relation to avoid extra queries
//     if ($this->relationLoaded('hours')) {
//         $hours = $this->hours->where('day_of_week', $day)->where('is_closed', false);
//     } else {
//         $hours = $this->hours()
//             ->where('day_of_week', $day)
//             ->where('is_closed', false)
//             ->get();
//     }

//     if ($hours->isEmpty()) {
//         $this->memoizedIsOpenNow = false;
//         return false;
//     }

//     foreach ($hours as $hour) {
//         if ($hour->is_24h) {
//             $this->memoizedIsOpenNow = true;
//             return true;
//         }

//         if ($hour->isOpenAt($now)) {
//             $this->memoizedIsOpenNow = true;
//             return true;
//         }
//     }

//     $this->memoizedIsOpenNow = false;
//     return false;
// }

// public function getHoursSummaryAttribute()
// {
//     $days = [];
//     $hours = $this->hours()->orderBy('sort_order')->get();

//     for ($i = 0; $i < 7; $i++) {
//         $dayHours = $hours->where('day_of_week', $i);
//         if ($dayHours->isEmpty()) {
//             $days[] = 'Closed';
//         } else {
//             $formatted = $dayHours->map(function ($hour) {
//                 return $hour->formatted_hours;
//             })->implode(', ');
//             $days[] = $formatted;
//         }
//     }

//     return $days;
// }





