<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\ImageService;
use App\Traits\GuardsHiddenItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * PHASE 11 / WAVE 1D-3 — LISTING MEDIA (LISTING-OWNED).
 *
 * Presentation media belongs to a LISTING:
 *
 *     Listing -> listing_images (logo | cover | gallery)
 *
 * A Business with several Listings can therefore have different media for each.
 * The Listing is supplied explicitly by the route, authorized through
 * {@see \App\Policies\ListingPolicy}, and never resolved from a Business.
 *
 * Organization branding is a SEPARATE concept and stays Business-owned
 * (`businesses.logo` / `businesses.cover_image`) — see {@see ImageController}.
 * This controller never writes a Business branding column.
 */
class ListingImageController extends Controller
{
    use GuardsHiddenItems;

    public function index(Listing $listing)
    {
        Gate::authorize('update', $listing);

        return Inertia::render('Owner/Listings/Images/Index', [
            'listing' => $listing->only(['id', 'name', 'slug', 'type', 'status', 'business_id']),
            'images' => $listing->images()->ordered()->get(),
        ]);
    }

    public function store(Request $request, Listing $listing)
    {
        Gate::authorize('update', $listing);

        // ✅ Server-side lock: can't add media to a hidden listing
        if ($redirect = $this->guardNotHidden($listing, 'listing')) {
            return $redirect;
        }

        // ── Single image (listing logo or cover, or one gallery image) ──
        if ($request->hasFile('image')) {
            $validated = $request->validate([
                'image' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,jpg,gif,webp',
                    'max:5120', // 5MB
                    'dimensions:min_width=100,min_height=100',
                    function ($attribute, $value, $fail) {
                        $imageInfo = getimagesize($value->getPathname());
                        if ($imageInfo === false) {
                            $fail('The file is not a valid image.');
                        }

                        $contents = file_get_contents($value->getPathname());
                        if (
                            strpos($contents, '<?php') !== false ||
                            strpos($contents, 'eval(') !== false ||
                            strpos($contents, 'base64_decode') !== false
                        ) {
                            $fail('The file contains suspicious content.');
                        }
                    },
                ],
                'type' => ['required', 'in:logo,cover,gallery'],
                'caption' => ['nullable', 'string', 'max:100'],
            ]);

            $path = $this->storeFile($request->file('image'), $listing);

            // Only one logo and one cover per Listing: replace the previous one.
            if (in_array($validated['type'], ['logo', 'cover'], true)) {
                $listing->images()->where('type', $validated['type'])->get()
                    ->each(function (ListingImage $existing) {
                        app(ImageService::class)->delete($existing->path);
                        $existing->delete();
                    });
            }

            ListingImage::create([
                'listing_id' => $listing->id,
                'path' => $path,
                'caption' => $validated['caption'] ?? null,
                'type' => $validated['type'],
                'is_primary' => false,
                'sort_order' => $listing->images()->count() + 1,
            ]);

            $typeLabel = match ($validated['type']) {
                'logo' => 'Logo',
                'cover' => 'Cover image',
                default => 'Image',
            };

            return redirect()->back()->with('success', "{$typeLabel} uploaded successfully.");
        }

        // ── Multiple gallery images ──
        if ($request->hasFile('images')) {
            $request->validate([
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'type' => 'required|in:gallery',
            ]);

            $uploaded = 0;

            foreach ($request->file('images') as $file) {
                $path = $this->storeFile($file, $listing, $uploaded);

                ListingImage::create([
                    'listing_id' => $listing->id,
                    'path' => $path,
                    'caption' => null,
                    'type' => 'gallery',
                    'is_primary' => false,
                    'sort_order' => $listing->images()->count() + 1,
                ]);

                $uploaded++;
            }

            Log::info('Listing gallery images uploaded', [
                'listing_id' => $listing->id,
                'count' => $uploaded,
            ]);

            return redirect()->back();
        }

        Log::warning('No image file found in request');

        return redirect()->back()->with('error', 'No image file found.');
    }

    public function destroy(Listing $listing, ListingImage $image)
    {
        Gate::authorize('update', $listing);

        // The image must belong to the Listing named in the route. Owning the
        // Business that contains both Listings is NOT sufficient.
        abort_unless((int) $image->listing_id === (int) $listing->id, 404);

        // ✅ Delete the file + all its variants.
        //    Guard against stale paths (e.g., .png in DB, .jpg on disk).
        $disk = Storage::disk('public');
        $pathsToDelete = [$image->path];

        if (!$disk->exists($image->path)) {
            $jpgTwin = preg_replace('#\.[^/.]+$#', '.jpg', $image->path);
            if ($jpgTwin && $disk->exists($jpgTwin)) {
                $pathsToDelete[] = $jpgTwin;
            }
        }

        $service = app(ImageService::class);
        foreach ($pathsToDelete as $p) {
            $service->delete($p);
        }

        $image->delete();

        return redirect()->back()->with('success', 'Image deleted successfully.');
    }

    /**
     * Store an uploaded file under the Listing's media directory and post-process
     * it (downscale, re-encode, variants). Returns the final path.
     */
    private function storeFile($file, Listing $listing, int $index = 0): string
    {
        $filename = time() . '_' . bin2hex(random_bytes(8)) . '_' . $index . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('listings/' . $listing->id . '/images', $filename, 'public');

        try {
            $processedPath = app(ImageService::class)->process($path);
            if ($processedPath) {
                $path = $processedPath;
            }
        } catch (\Throwable $e) {
            Log::warning('Listing image processing failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        return $path;
    }
}
