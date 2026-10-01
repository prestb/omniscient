<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
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

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * PHASE 9 — the canonical owner of a review is the LISTING. A review
     * belongs to the discoverable entity ("ABC — Buea"), not the abstract
     * organization. `business_id` is retained as an organization pointer for
     * aggregate/organization-page reporting only.
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    /** Reviews scoped to a specific listing. */
    public function scopeForListing($query, int $listingId)
    {
        return $query->where('listing_id', $listingId);
    }

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
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
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