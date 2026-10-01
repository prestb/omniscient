<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * LocationHourOverride — a date-specific override of a {@see Location}'s
 * normal open hours (a closed day, or special hours for one date).
 *
 * PHASE 10 DECISION: this is the SINGLE coherent mechanism for special/holiday
 * overrides, owned by the physical Location (table `location_hour_overrides`,
 * FK `location_id`).
 */
class LocationHourOverride extends Model
{
    use HasFactory;

    protected $table = 'location_hour_overrides';

    protected $fillable = [
        'location_id',
        'date',
        'is_closed',
        'opens_at',
        'closes_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_closed' => 'boolean',
        'opens_at' => 'datetime:H:i',
        'closes_at' => 'datetime:H:i',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============== SCOPES ==============

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('date', '>=', today());
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    // ============== SPECIAL HOURS HELPERS ==============

    /**
     * ✅ True when this override is a "special hours" entry:
     *    not closed, with both times set.
     */
    public function getIsSpecialHoursAttribute(): bool
    {
        return !$this->is_closed
            && $this->opens_at !== null
            && $this->closes_at !== null;
    }

    /**
     * ✅ Human-readable override text:
     *    - "Closed"                when is_closed
     *    - "10:00 - 14:00"         when special hours
     *    - "Not set"               otherwise (shouldn't happen — validation blocks it)
     */
    public function getFormattedHoursAttribute(): string
    {
        if ($this->is_closed) {
            return 'Closed';
        }

        if (!$this->is_special_hours) {
            return 'Not set';
        }

        $opens = \Carbon\Carbon::parse($this->opens_at)->format('H:i');
        $closes = \Carbon\Carbon::parse($this->closes_at)->format('H:i');

        return "{$opens} - {$closes}";
    }

    /**
     * ✅ Is the current moment inside this override's window?
     */
    public function isOpenAt($time = null): bool
    {
        if ($this->is_closed) {
            return false;
        }

        if (!$this->is_special_hours) {
            return false;
        }

        $time = $time ? \Carbon\Carbon::parse($time) : now();
        $opens = \Carbon\Carbon::parse($this->opens_at);
        $closes = \Carbon\Carbon::parse($this->closes_at);

        // Overnight special hours (e.g., 22:00 - 02:00)
        if ($closes->lessThan($opens)) {
            $closes->addDay();
            return $time->between($opens, $closes);
        }

        return $time->between($opens, $closes);
    }
}
