<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PHASE 11 / WAVE 1B — a service is owned by the LISTING.
 *
 * `listing_id` is the authoritative (and only) ownership relationship.
 * Different locations of the same brand can offer different services because
 * each location is its own Listing.
 */
class ListingService extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'listing_services';

    protected $fillable = [
        'listing_id',
        'name',
        'description',
        'sort_order',
        'hidden_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'hidden_at' => 'datetime',
    ];

    /** The listing that owns this service (authoritative owner). */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
