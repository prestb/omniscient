<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'email',
        'in_app',
        'sms',
    ];

    protected $casts = [
        'email' => 'boolean',
        'in_app' => 'boolean',
        'sms' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getPreferences($userId, $type)
    {
        $preference = self::where('user_id', $userId)
            ->where('type', $type)
            ->first();

        if (!$preference) {
            return [
                'email' => true,
                'in_app' => true,
                'sms' => false,
            ];
        }

        return $preference;
    }
}