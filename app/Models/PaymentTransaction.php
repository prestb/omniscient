<?php
// app/Models/PaymentTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'plan_id',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'phone',
        'email',
        'duration_months',
        'action_type',      // ✅ NEW: new, renew, upgrade
        'billing_type',     // ✅ NEW: monthly, yearly
        'payment_data',
        'confirmed_at',
    ];

    protected $casts = [
        'payment_data' => 'array',
        'amount' => 'float',
        'confirmed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}