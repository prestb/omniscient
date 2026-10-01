<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 11 / WAVE 1B — analytics are owned by the LISTING.
 *
 * `listing_id` is the authoritative ownership relationship. Organization-level
 * analytics are DERIVED by summing their listings' rows — never stored as
 * duplicate per-organization history (Phase 9 §analytics policy).
 */
class ListingAnalytics extends Model
{
    use HasFactory;

    protected $table = 'listing_analytics';

    protected $fillable = [
        'listing_id',
        'date',
        'views',
        'unique_visitors',
        'phone_clicks',
        'whatsapp_clicks',
        'website_clicks',
        'direction_clicks',
        'social_clicks',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'unique_visitors' => 'integer',
        'phone_clicks' => 'integer',
        'whatsapp_clicks' => 'integer',
        'website_clicks' => 'integer',
        'direction_clicks' => 'integer',
        'social_clicks' => 'integer',
    ];

    /** The listing that owns this analytics row (authoritative owner). */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    /** Listing-scoped analytics. */
    public function scopeForListing($query, int $listingId)
    {
        return $query->where('listing_id', $listingId);
    }

    /**
     * Resolve the analytics scope key. The listing scope is the ONLY axis.
     *
     * @return array{listing_id: int, date: string}
     */
    protected static function scopeKey(int $listingId): array
    {
        return [
            'listing_id' => $listingId,
            'date' => now()->toDateString(),
        ];
    }

    public static function trackView(int $listingId)
    {
        try {
            $analytics = self::firstOrCreate(self::scopeKey($listingId));

            $analytics->increment('views');
            Log::info('View tracked for listing: ' . $listingId . ' total: ' . $analytics->views);
            return $analytics;
        } catch (\Exception $e) {
            Log::error('Failed to track view: ' . $e->getMessage());
            return null;
        }
    }

    public static function trackUniqueVisitor(int $listingId)
    {
        try {
            $analytics = self::firstOrCreate(self::scopeKey($listingId));

            $analytics->increment('unique_visitors');
            Log::info('Unique visitor tracked for listing: ' . $listingId . ' total: ' . $analytics->unique_visitors);
            return $analytics;
        } catch (\Exception $e) {
            Log::error('Failed to track unique visitor: ' . $e->getMessage());
            return null;
        }
    }

    public static function trackClick(int $listingId, string $type)
    {
        try {
            $analytics = self::firstOrCreate(self::scopeKey($listingId));

            $column = $type . '_clicks';
            $validColumns = ['phone_clicks', 'whatsapp_clicks', 'website_clicks', 'direction_clicks', 'social_clicks'];

            if (in_array($column, $validColumns)) {
                $analytics->increment($column);
                Log::info('Click tracked for listing: ' . $listingId . ' type: ' . $type . ' total: ' . $analytics->$column);
                return $analytics;
            } else {
                Log::warning('Invalid column: ' . $column);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to track click: ' . $e->getMessage());
            return null;
        }
    }

    public static function getStats(int $listingId, int $days = 30)
    {
        try {
            $startDate = now()->subDays($days)->toDateString();

            return self::where('listing_id', $listingId)
                ->where('date', '>=', $startDate)
                ->orderBy('date')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get stats: ' . $e->getMessage());
            return collect();
        }
    }

    public static function getTotalStats(int $listingId)
    {
        try {
            return self::where('listing_id', $listingId)
                ->selectRaw('
                    COALESCE(SUM(views), 0) as total_views,
                    COALESCE(SUM(unique_visitors), 0) as total_unique_visitors,
                    COALESCE(SUM(phone_clicks), 0) as total_phone_clicks,
                    COALESCE(SUM(whatsapp_clicks), 0) as total_whatsapp_clicks,
                    COALESCE(SUM(website_clicks), 0) as total_website_clicks,
                    COALESCE(SUM(direction_clicks), 0) as total_direction_clicks,
                    COALESCE(SUM(social_clicks), 0) as total_social_clicks
                ')
                ->first();
        } catch (\Exception $e) {
            Log::error('Failed to get total stats: ' . $e->getMessage());
            return (object) [
                'total_views' => 0,
                'total_unique_visitors' => 0,
                'total_phone_clicks' => 0,
                'total_whatsapp_clicks' => 0,
                'total_website_clicks' => 0,
                'total_direction_clicks' => 0,
                'total_social_clicks' => 0,
            ];
        }
    }

    public static function getPeriodComparison(int $listingId, int $days = 30)
    {
        try {
            $currentPeriod = now()->subDays($days);
            $previousPeriod = now()->subDays($days * 2);

            $current = self::where('listing_id', $listingId)
                ->where('date', '>=', $currentPeriod)
                ->sum('views');

            $previous = self::where('listing_id', $listingId)
                ->whereBetween('date', [$previousPeriod, $currentPeriod])
                ->sum('views');

            $growth = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;

            return [
                'current' => $current,
                'previous' => $previous,
                'growth' => $growth,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get period comparison: ' . $e->getMessage());
            return [
                'current' => 0,
                'previous' => 0,
                'growth' => 0,
            ];
        }
    }
}
