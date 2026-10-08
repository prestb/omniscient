<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'listing_id',
        'user_id',
        'rating',
        'title',
        'content',
        'guest_name',
        'guest_email',
        'status',
        'approved_at',
        'approved_by',
        'images',
    ];

    protected $casts = [
        'rating' => 'integer',
        'approved_at' => 'datetime',
        'images' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * PHASE 21C-R1 - the Listing being reviewed is the CANONICAL owner.
     *
     * Business context, where it exists, is reached THROUGH the Listing
     * (`$review->listing->business`) so there is exactly one ownership path.
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * PHASE 11 — REVIEWS ARE BUSINESS-OWNED.
     *
     * `business_id` is the authoritative owner and the only Review ownership
     * relation. The former `listing()` relation and `scopeForListing()` were
     * obsolete scaffolding resolving on `reviews.listing_id`, a column no
     * application writer ever populated. Both are removed with the column.
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function replies()
    {
        return $this->hasMany(ReviewReply::class);
    }

    public function getReviewerNameAttribute()
    {
        if ($this->guest_name) {
            return $this->guest_name;
        }
        return $this->user?->name ?? 'Anonymous';
    }

    public function getReviewerEmailAttribute()
    {
        if ($this->guest_email) {
            return $this->guest_email;
        }
        return $this->user?->email ?? '';
    }

    public function getRatingStarsAttribute()
    {
        return str_repeat('⭐', $this->rating);
    }

    public function scopeApproved($query)
    {
        // PHASE 21C-R1 - qualified: the derived Business aggregate joins
        // `listings`, which also has a `status` column.
        return $query->where('reviews.status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('reviews.status', self::STATUS_PENDING);
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function approve()
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
        return $this;
    }

    public function reject()
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
        return $this;
    }
}