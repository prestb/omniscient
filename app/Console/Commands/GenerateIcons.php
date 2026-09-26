<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class GenerateIcons extends Command
{
    protected $signature = 'icons:generate
                            {--source=images/icon-512.png : Path inside public/ to the source icon}
                            {--force : Overwrite existing icons}';

    protected $description = 'Generate all app icon sizes + favicon from a single source PNG.';

    /**
     * Sizes to generate (in px).
     */
    private const SIZES = [16, 32, 72, 120, 144, 152, 167, 180, 192, 512];

    /**
     * Maskable variants — same sizes but with 20% padding for Android's
     * adaptive icon mask.
     */
    private const MASKABLE_SIZES = [192, 512];

    public function handle(): int
    {
        $source = public_path($this->option('source'));

        if (!file_exists($source)) {
            $this->error("Source icon not found: {$source}");
            return self::FAILURE;
        }

        $driverClass = extension_loaded('imagick')
            ? \Intervention\Image\Drivers\Imagick\Driver::class
            : \Intervention\Image\Drivers\Gd\Driver::class;

        $manager = ImageManager::withDriver($driverClass);
        $force = (bool) $this->option('force');
        $outDir = public_path('images');

        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $this->info("Source: {$source}");
        $this->info("Output: {$outDir}");

        // ============== Standard icon sizes ==============
        foreach (self::SIZES as $size) {
            $target = "{$outDir}/icon-{$size}.png";

            if (file_exists($target) && !$force) {
                $this->line("  ✓ icon-{$size}.png (exists, skipped)");
                continue;
            }

            $image = $manager->read($source);
            $image->cover($size, $size); // square crop centered
            $image->toPng()->save($target);

            $this->line("  ✓ icon-{$size}.png");
        }

        // ============== Maskable (Android adaptive) ==============
        foreach (self::MASKABLE_SIZES as $size) {
            $target = "{$outDir}/icon-{$size}-maskable.png";

            if (file_exists($target) && !$force) {
                $this->line("  ✓ icon-{$size}-maskable.png (exists, skipped)");
                continue;
            }

            $image = $manager->read($source);

            // Safe area: logo occupies ~80% of canvas, 10% padding each side
            $padded = (int) round($size * 0.8);
            $image->cover($padded, $padded);

            // Create the outer canvas and paste the padded logo centered
            $canvas = $manager->create($size, $size)
                ->fill($this->detectBackground($source));

            $canvas->place($image, 'center');
            $canvas->toPng()->save($target);

            $this->line("  ✓ icon-{$size}-maskable.png");
        }

        // ============== Favicon.ico ==============
        $this->generateFavicon($source, $outDir, $manager, $force);

        $this->newLine();
        $this->info('✅ Icon generation complete.');

        return self::SUCCESS;
    }

    private function generateFavicon(string $source, string $outDir, ImageManager $manager, bool $force): void
    {
        $target = public_path('favicon.ico');

        if (file_exists($target) && !$force) {
            $this->line('  ✓ favicon.ico (exists, skipped)');
            return;
        }

        // Generate 16 and 32 PNGs, then combine into ICO
        $png16 = "{$outDir}/favicon-16.png";
        $png32 = "{$outDir}/favicon-32.png";

        $manager->read($source)->cover(16, 16)->toPng()->save($png16);
        $manager->read($source)->cover(32, 32)->toPng()->save($png32);

        // Simple ICO = concatenation of PNG bytes with a small header
        // For simplicity, we write an ICO with a single 32x32 PNG payload
        $icoBytes = $this->buildIcoFromPng($png32);

        if ($icoBytes !== null) {
            file_put_contents($target, $icoBytes);
            $this->line('  ✓ favicon.ico');
        }

        $this->line('  ✓ favicon-16.png');
        $this->line('  ✓ favicon-32.png');
    }

    /**
     * Minimal ICO container for a single PNG payload.
     * The ICO format allows raw PNG bytes as icon data.
     */
    private function buildIcoFromPng(string $pngPath): ?string
    {
        if (!file_exists($pngPath)) {
            return null;
        }

        $png = file_get_contents($pngPath);
        $size = getimagesize($pngPath);

        if ($size === false) {
            return null;
        }

        $width = $size[0] >= 256 ? 0 : $size[0];
        $height = $size[1] >= 256 ? 0 : $size[1];

        // ICONDIR (6 bytes) + ICONDIRENTRY (16 bytes) + PNG payload
        $header = pack('vvv', 0, 1, 1); // reserved, type=1 (icon), count=1

        $entry = pack(
            'CCCCvvVV',
            $width,
            $height,
            0,    // color palette count (0 = no palette)
            0,    // reserved
            1,    // color planes
            32,   // bits per pixel
            strlen($png),
            22    // offset to image data (6 + 16)
        );

        return $header . $entry . $png;
    }

    /**
     * Detect a suitable background color for maskable icons —
     * use the source's corner pixel color, or brand blue as fallback.
     */
    private function detectBackground(string $source): string
    {
        try {
            $image = ImageManager::withDriver(
                extension_loaded('imagick')
                    ? \Intervention\Image\Drivers\Imagick\Driver::class
                    : \Intervention\Image\Drivers\Gd\Driver::class
            )->read($source);

            $cornerPixel = $image->pickColor(0, 0);
            return $cornerPixel->toHex();
        } catch (\Throwable $e) {
            return '#0284c7'; // primary-600 fallback
        }
    }
}