<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\ListingImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncImagePaths extends Command
{
    protected $signature = 'images:sync-paths
                            {--dry-run : Show what would change without applying}';

    protected $description = 'Repair DB paths that point to renamed/missing image files.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $disk = Storage::disk('public');

        $this->info($dryRun ? '🔍 DRY RUN' : '🔧 Syncing image paths...');
        $this->newLine();

                // ============== ListingImage.path ==============
        $fixedImages = 0;
        $images = ListingImage::query()->get();

        foreach ($images as $image) {
            if (!$image->path) continue;

            // Already exists on disk → skip
            if ($disk->exists($image->path)) continue;

            // Try the .jpg twin
            $jpgTwin = preg_replace('#\.[^/.]+$#', '.jpg', $image->path);
            if ($disk->exists($jpgTwin)) {
                $this->line("  IMG #{$image->id}: {$image->path} → {$jpgTwin}");
                if (!$dryRun) {
                    $image->update(['path' => $jpgTwin]);
                }
                $fixedImages++;
            }
        }

        $this->info("ListingImage paths fixed: {$fixedImages}");
        $this->newLine();

        // ============== Business.logo + Business.cover_image ==============
        $fixedBiz = 0;
        $businesses = Business::query()->get();

        foreach ($businesses as $business) {
            $changed = false;

            if ($business->logo && !$disk->exists($business->logo)) {
                $jpgTwin = preg_replace('#\.[^/.]+$#', '.jpg', $business->logo);
                if ($disk->exists($jpgTwin)) {
                    $this->line("  BIZ #{$business->id} logo: {$business->logo} → {$jpgTwin}");
                    if (!$dryRun) {
                        $business->logo = $jpgTwin;
                    }
                    $changed = true;
                } else {
                    $this->warn("  BIZ #{$business->id} logo: {$business->logo} — no .jpg twin, clearing");
                    if (!$dryRun) {
                        $business->logo = null;
                    }
                    $changed = true;
                }
            }

            if ($business->cover_image && !$disk->exists($business->cover_image)) {
                $jpgTwin = preg_replace('#\.[^/.]+$#', '.jpg', $business->cover_image);
                if ($disk->exists($jpgTwin)) {
                    $this->line("  BIZ #{$business->id} cover: {$business->cover_image} → {$jpgTwin}");
                    if (!$dryRun) {
                        $business->cover_image = $jpgTwin;
                    }
                    $changed = true;
                } else {
                    $this->warn("  BIZ #{$business->id} cover: {$business->cover_image} — no .jpg twin, clearing");
                    if (!$dryRun) {
                        $business->cover_image = null;
                    }
                    $changed = true;
                }
            }

            if ($changed) {
                if (!$dryRun) {
                    $business->save();
                }
                $fixedBiz++;
            }
        }

        $this->info("Business records fixed: {$fixedBiz}");
        $this->newLine();

        if ($dryRun) {
            $this->warn('Dry run — no changes made. Remove --dry-run to apply.');
        } else {
            $this->info('✅ Sync complete.');
        }

        return self::SUCCESS;
    }
}