<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'email',
        'name',
        'phone',
        'business_name',
        'notes',
        'invited_by',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    // Boot method to auto-generate token
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
        });
    }

    // Relationships
    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // Scopes
    public function scopeUnused($query)
    {
        return $query->whereNull('used_at');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    // Helpers
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isUsed() && !$this->isExpired();
    }

    public function getInvitationLinkAttribute(): string
    {
        return route('invitations.accept', $this->token);
    }
}