<?php

namespace App\Helpers;

use App\Models\AuditLog;

class AuditHelper
{
    public static function log($action, $resourceType, $resourceId = null, $oldValues = null, $newValues = null)
    {
        return AuditLog::log($action, $resourceType, $resourceId, $oldValues, $newValues);
    }

    public static function logBusiness($action, $business, $oldValues = null, $newValues = null)
    {
        return self::log($action, 'business', $business->id, $oldValues, $newValues);
    }

    public static function logUser($action, $user, $oldValues = null, $newValues = null)
    {
        return self::log($action, 'user', $user->id, $oldValues, $newValues);
    }

    public static function logSubscription($action, $subscription, $oldValues = null, $newValues = null)
    {
        return self::log($action, 'subscription', $subscription->id, $oldValues, $newValues);
    }
}