<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public static function remember($key, $callback, $ttl = 3600)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public static function forget($key)
    {
        return Cache::forget($key);
    }

    public static function rememberForever($key, $callback)
    {
        return Cache::rememberForever($key, $callback);
    }

    public static function tags($tags)
    {
        return Cache::tags($tags);
    }

    // Clear all cache
    public static function clear()
    {
        return Cache::flush();
    }

    // Clear specific cache group
    public static function clearGroup($group)
    {
        return Cache::tags([$group])->flush();
    }

    // Clear business cache
    public static function clearBusiness($businessId)
    {
        Cache::forget('business_' . $businessId);
        Cache::forget('business_branches_' . $businessId);
        Cache::tags(['businesses'])->flush();
    }

    // Clear category cache
    public static function clearCategories()
    {
        Cache::forget('categories_all');
        Cache::forget('categories_root');
        Cache::tags(['categories'])->flush();
    }
}