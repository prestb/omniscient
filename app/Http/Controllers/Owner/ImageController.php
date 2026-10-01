<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\ImageService;
use App\Traits\GuardsHiddenItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * PHASE 11 / WAVE 1D-3 — ORGANIZATION BRANDING (BUSINESS-OWNED).
 *
 * This controller manages ONLY the organization's own branding:
 * `businesses.logo` and `businesses.cover_image`.
 *
 * It is Business-scoped and Business-authorized on purpose: branding belongs to
 * the organization, and a Business may legitimately exist with ZERO Listings.
 *
 * It no longer writes `listing_images` rows and no longer calls
 * `primaryListing()`. The old dual-write (branding column + Listing media row)
 * is gone. Listing presentation media is handled by
 * {@see ListingImageController}.
 */
class ImageController extends Controller
{
    use GuardsHiddenItems;

    /** Branding slots this controller is allowed to touch. */
    private const SLOTS = [
        'logo' => 'logo',
        'cover' => 'cover_image',
    ];

    public function index(Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Owner/Images/Index', [
            'business' => $business,
            'logo' => $business->logo,
            'cover' => $business->cover_image,
            'logo_url' => $business->logo_url,
            'cover_image_url' => $business->cover_image_url,
        ]);
    }

    public function store(Request $request, Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // ✅ Server-side lock: can't change branding of a hidden business
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }

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
            // PHASE 11 / WAVE 1D-3 — organization branding accepts logo|cover only.
            // Gallery media is Listing-owned; it is not an organization concern.
            'type' => ['required', 'in:logo,cover'],
            'caption' => ['nullable', 'string', 'max:100'],
        ]);

        $file = $request->file('image');
        $filename = time() . '_' . bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('businesses/' . $business->id . '/branding', $filename, 'public');

        try {
            $processedPath = app(ImageService::class)->process($path);
            if ($processedPath) {
                $path = $processedPath;
            }
        } catch (\Throwable $e) {
            Log::warning('Branding image processing failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        // Remove the file currently occupying this branding slot.
        $previous = $business->{self::SLOTS[$validated['type']]};
        if ($previous && Storage::disk('public')->exists($previous)) {
            app(ImageService::class)->delete($previous);
        }

        $business->update([self::SLOTS[$validated['type']] => $path]);

        $label = $validated['type'] === 'logo' ? 'Logo' : 'Cover image';

        return redirect()->back()->with('success', "{$label} uploaded successfully.");
    }

    /**
     * Clear one organization branding slot. `$type` is `logo` or `cover`.
     */
    public function destroy(Business $business, string $type)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        abort_unless(array_key_exists($type, self::SLOTS), 404);

        $column = self::SLOTS[$type];
        $current = $business->{$column};

        if ($current) {
            app(ImageService::class)->delete($current);
        }

        $business->update([$column => null]);

        return redirect()->back()->with('success', 'Organization branding removed.');
    }
}
