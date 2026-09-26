<?php

namespace App\Console\Commands;

use App\Models\BusinessImage;
use App\Services\ImageService;
use Illuminate\Console\Command;

class BackfillImageVariants extends Command
{
    protected $signature = 'images:backfill
                            {--dry-run : Count how many images would be processed}';

    protected $description = 'Generate optimized variants (sizes + WebP) for existing images.';

    public function handle(ImageService $service): int
    {
        if ($this->option('dry-run')) {
            $total = BusinessImage::query()
                ->whereNull('hidden_at')
                ->count();

            $needProcessing = 0;
            BusinessImage::query()
                ->whereNull('hidden_at')
                ->get()
                ->each(function (BusinessImage $img) use (&$needProcessing, $service) {
                    if ($img->path && !$service->hasVariants($img->path)) {
                        $needProcessing++;
                    }
                });

            $this->info("Total images: {$total}");
            $this->info("Need processing: {$needProcessing}");
            $this->info("Already optimized: " . ($total - $needProcessing));

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar();
        $processed = 0;

        $bar->start();

        $total = $service->backfill(function (BusinessImage $image) use ($bar, &$processed) {
            $processed++;
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Processed {$total} images.");
        $this->line('Variants written next to each original in storage/app/public.');

        return self::SUCCESS;
    }
}