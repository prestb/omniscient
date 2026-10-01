<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'listing_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * PHASE 9 — a favorite targets a LISTING (e.g. "ABC — Buea"), not an
     * abstract organization. `business_id` is retained as the organization
     * pointer during the transition.
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
