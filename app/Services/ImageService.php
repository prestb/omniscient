<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ImageService
{
    /**
     * ✅ Sizes generated for each uploaded image.
     *    Key is used in the filename; value is the max edge in pixels.
     */
    public const SIZES = [
        'thumb'  => 300,
        'medium' => 800,
        'large'  => 1600,
    ];

    public const QUALITY = 80;

    /**
     * The max edge for the "large" variant — also the max we accept on upload.
     */
    public const MAX_UPLOAD_EDGE = 1600;

    public const FORMATS = ['jpg', 'webp'];

    private ImageManager $manager;

    public function __construct()
    {
        // ✅ Intervention v3: pass a driver class, not a string.
        //    Prefer Imagick when the extension is available.
        $driverClass = extension_loaded('imagick')
            ? \Intervention\Image\Drivers\Imagick\Driver::class
            : \Intervention\Image\Drivers\Gd\Driver::class;

        $this->manager = ImageManager::withDriver($driverClass);
    }

    /**
     * ✅ Process a freshly uploaded image.
     *
     * - Downscales the original to MAX_UPLOAD_EDGE
     * - Re-encodes it as JPEG (so the extension matches the content)
     * - Generates all SIZES × FORMATS variants
     * - Returns the disk-relative path of the (possibly rewritten) original
     *
     * Safe to call repeatedly — existing variants are overwritten.
     */
    public function process(string $path): ?string
    {
        $disk = Storage::disk('public');
        $fullPath = $disk->path($path);

        if (!file_exists($fullPath)) {
            Log::warning('ImageService::process — source not found', ['path' => $path]);
            return null;
        }

        try {
            // 1. Normalize the original — downscale + re-encode as JPEG
            $original = $this->manager->read($fullPath);
            $original->scaleDown(self::MAX_UPLOAD_EDGE, self::MAX_UPLOAD_EDGE);

            $originalBase = $this->stripExtension($path);
            $originalPath = $originalBase . '.jpg';
            $original->toJpeg(self::QUALITY)->save($disk->path($originalPath));

            // Remove the incoming file if it was a different extension (.png, .webp, etc.)
            if ($originalPath !== $path && $disk->exists($path)) {
                $disk->delete($path);
            }

            // 2. Generate variants
            foreach (self::SIZES as $sizeKey => $maxEdge) {
                $this->generateVariant($originalPath, $sizeKey, $maxEdge);
            }

            return $originalPath;
        } catch (\Throwable $e) {
            Log::error('ImageService::process failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * ✅ Generate one size in all formats.
     */
    private function generateVariant(string $originalPath, string $sizeKey, int $maxEdge): void
    {
        $disk = Storage::disk('public');
        $fullPath = $disk->path($originalPath);
        $base = $this->stripExtension($originalPath);

        // Read fresh each time — Intervention mutates state
        $image = $this->manager->read($fullPath);
        $image->scaleDown($maxEdge, $maxEdge);

        // JPEG
        $jpegPath = "{$base}_{$sizeKey}.jpg";
        $image->toJpeg(self::QUALITY)->save($disk->path($jpegPath));

        // WebP — supported by Intervention 3 on both GD + Imagick
        if (extension_loaded('imagick') || function_exists('imagewebp')) {
            try {
                $webpPath = "{$base}_{$sizeKey}.webp";
                $image->toWebp(self::QUALITY)->save($disk->path($webpPath));
            } catch (\Throwable $e) {
                Log::warning('WebP variant failed', [
                    'path' => $originalPath,
                    'size' => $sizeKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * ✅ Get a URL for a specific size + format.
     *    Prefers the requested format, falls back to JPEG, then original.
     */
    public function url(string $originalPath, string $sizeKey = 'medium', string $format = 'jpg'): string
    {
        if (!$originalPath) {
            return '';
        }

        $disk = Storage::disk('public');
        $base = $this->stripExtension($originalPath);

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

        // Last resort: original
        if ($disk->exists($originalPath)) {
            return $disk->url($originalPath);
        }

        return '';
    }

    /**
     * ✅ Public URL of the original file.
     */
    public function originalUrl(string $path): string
    {
        if (!$path) {
            return '';
        }
        return Storage::disk('public')->url($path);
    }

    /**
     * ✅ Check whether variants exist for a given image.
     */
    public function hasVariants(string $path): bool
    {
        if (!$path) return false;
        $disk = Storage::disk('public');
        $base = $this->stripExtension($path);
        return $disk->exists("{$base}_medium.jpg");
    }

    /**
     * ✅ Delete an image and every variant.
     */
    public function delete(string $path): void
    {
        if (!$path) {
            return;
        }

        $base = $this->stripExtension($path);

        $this->safeDelete($path);
        $this->safeDelete($base . '.jpg');

        foreach (array_keys(self::SIZES) as $sizeKey) {
            foreach (self::FORMATS as $format) {
                $this->safeDelete("{$base}_{$sizeKey}.{$format}");
            }
        }
    }

    /**
     * ✅ Alias kept for backwards compatibility with existing callers.
     */
    public function deleteImages(string $path): void
    {
        $this->delete($path);
    }

    /**
     * ✅ Legacy shim — the old ImageController called this signature.
     *    Delegates to process() which does the correct thing.
     */
    public function optimize($path, $width = null, $height = null, $quality = null)
    {
        // Old signature kept so we don't break on any stray callers.
        // Real work happens in process().
        return $this->process($path);
    }

    /**
     * ✅ Legacy shim — no-op now. Variants are generated by process().
     */
    public function generateThumbnail($path, $width = 150, $height = 150)
    {
        // No-op — process() already generates thumb variants.
        // Kept so existing callers don't fatal.
        return null;
    }

    /**
     * ✅ Backfill: process every image missing variants.
     *
     * @param  callable|null  $onProgress  Called with each processed BusinessImage
     * @return int  Number of images processed
     */
    public function backfill(?callable $onProgress = null): int
    {
        $count = 0;
        $disk = Storage::disk('public');

        $images = \App\Models\BusinessImage::query()
            ->whereNull('hidden_at')
            ->get();

        foreach ($images as $image) {
            if (!$image->path) {
                continue;
            }

            $oldPath = $image->path;
            $newPath = $oldPath;

            // Case A — source missing but a .jpg twin exists (post-rename desync).
            //          Update the DB to point at the .jpg.
            if (!$disk->exists($oldPath)) {
                $jpgTwin = preg_replace('#\.[^/.]+$#', '.jpg', $oldPath);
                if ($disk->exists($jpgTwin)) {
                    $newPath = $jpgTwin;
                } else {
                    continue; // nothing we can do
                }
            }
            // Case B — source exists but has no variants yet.
            elseif (!$this->hasVariants($oldPath)) {
                $processed = $this->process($oldPath);
                if ($processed) {
                    $newPath = $processed;
                }
            }
            // Otherwise — already processed and DB in sync. Skip.
            else {
                continue;
            }

            // ✅ Sync the DB record if the path changed
            if ($newPath !== $oldPath) {
                $image->update(['path' => $newPath]);

                // Also sync the parent business if this is a logo or cover
                if (in_array($image->type, ['logo', 'cover']) && $image->business) {
                    $column = $image->type === 'logo' ? 'logo' : 'cover_image';
                    $image->business->update([$column => $newPath]);
                }
            }

            $count++;

            if ($onProgress) {
                $onProgress($image);
            }
        }

        return $count;
    }

    // ============== HELPERS ==============

    private function stripExtension(string $path): string
    {
        $info = pathinfo($path);
        $dir = $info['dirname'] ?? '';
        $name = $info['filename'] ?? '';

        return $dir && $dir !== '.' ? "{$dir}/{$name}" : $name;
    }

    private function safeDelete(string $path): void
    {
        try {
            $disk = Storage::disk('public');
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        } catch (\Throwable $e) {
            Log::warning('ImageService::safeDelete failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }
}