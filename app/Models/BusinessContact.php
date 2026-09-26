<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  // ✅ ADD

class BusinessContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'type',
        'value',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Get icon for contact type
    public static function getIcon($type)
    {
        $icons = [
            'phone' => '📞',
            'whatsapp' => '💬',
            'facebook' => '📘',
            'instagram' => '📸',
            'tiktok' => '🎵',
            'twitter' => '🐦',
            'youtube' => '📺',
            'linkedin' => '🔗',
            'other' => '🔗',
        ];

        return $icons[$type] ?? '🔗';
    }

    // Get label for contact type
    public static function getLabel($type)
    {
        $labels = [
            'phone' => 'Phone',
            'whatsapp' => 'WhatsApp',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'twitter' => 'Twitter',
            'youtube' => 'YouTube',
            'linkedin' => 'LinkedIn',
            'other' => 'Other',
        ];

        return $labels[$type] ?? 'Other';
    }
}