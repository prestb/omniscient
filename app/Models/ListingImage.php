<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * PHASE 11 / WAVE 1B — discoverable media is owned by the LISTING.
 *
 * `listing_id` is the authoritative ownership relationship. Brand assets
 * (logo/cover) remain mirrorable onto the Business columns for fast rendering,
 * but the canonical media rows belong to the listing.
 */
class ListingImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'listing_images';

    protected $fillable = [
        'listing_id',
        'path',
        'caption',
        'type',
        'is_primary',
        'sort_order',
        'hidden_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'hidden_at' => 'datetime',
    ];

    protected $appends = [
        'url',
        'full_url',
        'thumbnail_url',
        'medium_url',
        'large_url',
        'thumb_webp',
        'medium_webp',
        'large_webp',
    ];

    // Image types
    const TYPE_LOGO = 'logo';
    const TYPE_COVER = 'cover';
    const TYPE_GALLERY = 'gallery';

    /** The listing that owns this image (authoritative owner). */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function scopeLogo($query)
    {
        return $query->where('type', self::TYPE_LOGO);
    }

    public function scopeCover($query)
    {
        return $query->where('type', self::TYPE_COVER);
    }

    public function scopeGallery($query)
    {
        return $query->where('type', self::TYPE_GALLERY);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * URL of the original file.
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Full / original URL — same as url() now (we don't have a separate full-res).
     */
    public function getFullUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }

        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $this->path;
        }

        return Storage::disk('public')->url($this->path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->variantUrl('thumb', 'jpg');
    }

    public function getMediumUrlAttribute(): ?string
    {
        return $this->variantUrl('medium', 'jpg');
    }

    public function getLargeUrlAttribute(): ?string
    {
        return $this->variantUrl('large', 'jpg');
    }

    public function getThumbWebpAttribute(): ?string
    {
        return $this->variantUrl('thumb', 'webp');
    }

    public function getMediumWebpAttribute(): ?string
    {
        return $this->variantUrl('medium', 'webp');
    }

    public function getLargeWebpAttribute(): ?string
    {
        return $this->variantUrl('large', 'webp');
    }

    /**
     * Get a URL for any size+format combination.
     * Falls back gracefully when a variant is missing.
     */
    public function variantUrl(string $sizeKey, string $format = 'jpg'): ?string
    {
        if (!$this->path) {
            return null;
        }

        $disk = Storage::disk('public');
        $base = preg_replace('#\.[^/.]+$#', '', $this->path);

        // Requested format
        $candidate = "{$base}_{$sizeKey}.{$format}";
        if ($disk->exists($candidate)) {
            return $disk->url($candidate);
        }

        // JPEG fallback
        $jpegFallback = "{$base}_{$sizeKey}.jpg";
        if ($disk->exists($jpegFallback)) {
            return $disk->url($jpegFallback);
        }

        // Original
        if ($disk->exists($this->path)) {
            return $disk->url($this->path);
        }

        return null;
    }
}
