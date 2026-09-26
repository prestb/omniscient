<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'business_id',
        'amount',
        'currency',
        'method',
        'reference',
        'status',
        'recorded_by',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function getMethodLabelAttribute()
    {
        $labels = [
            'cash' => 'Cash',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Card',
            'other' => 'Other',
        ];
        return $labels[$this->method] ?? $this->method;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'badge-pending',
            'confirmed' => 'badge-active',
            'failed' => 'badge-suspended',
            'refunded' => 'badge-draft',
        ];
        return $badges[$this->status] ?? 'badge-draft';
    }
}