<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'listing_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * PHASE 11 / WAVE 1D-5A — a favorite targets a LISTING, and `listing_id` is
     * now the authoritative, required key. `business_id` was dropped from the
     * schema and from this model: it was the obsolete Business-as-favorite-target
     * key and never participated in the Listing-centric invariant.
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
