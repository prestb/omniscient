<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'title',
        'message',
        'action_url',
        'type',
        'recipient_type',
        'recipient_ids',
        'recipient_count',
        'send_push',
        'save_database',
        'scheduled_at',
        'status',
        'sent_at',
        'failure_reason',
        'sent_count',
        'push_sent_count',
        'recurrence',
    ];

    protected $casts = [
        'recipient_ids' => 'array',
        'send_push' => 'boolean',
        'save_database' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeDue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('scheduled_at', '<=', now());
    }

    // Helpers
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            self::STATUS_PROCESSING => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            self::STATUS_SENT => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            self::STATUS_FAILED => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        ];
        return $badges[$this->status] ?? $badges[self::STATUS_PENDING];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING]);
    }

    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
 * Get time until sending (human-readable)
 */
public function getTimeUntilAttribute(): string
{
    if ($this->status !== self::STATUS_PENDING) {
        return '';
    }
    
    if ($this->scheduled_at->isPast()) {
        return 'Now (processing...)';
    }
    
    return $this->scheduled_at->diffForHumans(now(), [
        'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW,
        'parts' => 2,
        'short' => true,
    ]);
}

/**
 * Is the notification due soon (within 5 min)?
 */
public function getIsDueSoonAttribute(): bool
{
    return $this->status === self::STATUS_PENDING 
        && $this->scheduled_at->diffInMinutes(now(), false) <= 5 
        && $this->scheduled_at->isFuture();
}
}