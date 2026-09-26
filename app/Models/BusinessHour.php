<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'day_of_week',
        'opens_at',
        'closes_at',
        'is_closed',
        'is_24h',
        'sort_order',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
        'is_24h' => 'boolean',
        'sort_order' => 'integer',
        'opens_at' => 'datetime:H:i',
        'closes_at' => 'datetime:H:i',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Day names
    public static function getDayName($day)
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$day] ?? 'Unknown';
    }

    public static function getDays()
    {
        return [
            ['value' => 0, 'label' => 'Sunday'],
            ['value' => 1, 'label' => 'Monday'],
            ['value' => 2, 'label' => 'Tuesday'],
            ['value' => 3, 'label' => 'Wednesday'],
            ['value' => 4, 'label' => 'Thursday'],
            ['value' => 5, 'label' => 'Friday'],
            ['value' => 6, 'label' => 'Saturday'],
        ];
    }

    // Check if open at a specific time
    public function isOpenAt($time)
    {
        if ($this->is_closed) {
            return false;
        }

        if ($this->is_24h) {
            return true;
        }

        if (!$this->opens_at || !$this->closes_at) {
            return false;
        }

        $time = \Carbon\Carbon::parse($time);
        $opens = \Carbon\Carbon::parse($this->opens_at);
        $closes = \Carbon\Carbon::parse($this->closes_at);

        // Handle overnight hours (e.g., 22:00 - 02:00)
        if ($closes->lessThan($opens)) {
            $closes->addDay();
            return $time->between($opens, $closes);
        }

        return $time->between($opens, $closes);
    }

    // Get formatted hours for display
    public function getFormattedHoursAttribute()
    {
        if ($this->is_closed) {
            return 'Closed';
        }

        if ($this->is_24h) {
            return 'Open 24 Hours';
        }

        if (!$this->opens_at || !$this->closes_at) {
            return 'Not set';
        }

        $opens = \Carbon\Carbon::parse($this->opens_at)->format('H:i');
        $closes = \Carbon\Carbon::parse($this->closes_at)->format('H:i');

        return "{$opens} - {$closes}";
    }

    // Get open/closed status for display
    public function getOpenStatusAttribute()
    {
        if ($this->is_closed) {
            return 'closed';
        }

        if ($this->is_24h) {
            return 'open';
        }

        if (!$this->opens_at || !$this->closes_at) {
            return 'not_set';
        }

        $now = now();
        $opens = \Carbon\Carbon::parse($this->opens_at);
        $closes = \Carbon\Carbon::parse($this->closes_at);

        if ($closes->lessThan($opens)) {
            $closes->addDay();
        }

        return $now->between($opens, $closes) ? 'open' : 'closed';
    }
}