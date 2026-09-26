<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'branch_id',
        'source',
        'name',
        'email',
        'phone',
        'whatsapp',
        'subject',
        'message',
        'ip_address',
        'user_agent',
        'metadata',
        'status',
        'read_at',
        'replied_at',
        'owner_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    // Status constants
    const STATUS_NEW = 'new';
    const STATUS_READ = 'read';
    const STATUS_REPLIED = 'replied';
    const STATUS_ARCHIVED = 'archived';

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // Helpers
    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update([
                'status' => self::STATUS_READ,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsReplied()
    {
        $this->update([
            'status' => self::STATUS_REPLIED,
            'replied_at' => now(),
        ]);
    }

    public function markAsArchived()
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            self::STATUS_NEW => 'bg-blue-100 text-blue-800 border border-blue-200',
            self::STATUS_READ => 'bg-gray-100 text-gray-800 border border-gray-200',
            self::STATUS_REPLIED => 'bg-green-100 text-green-800 border border-green-200',
            self::STATUS_ARCHIVED => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
        ];
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}