<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\ImageService;
use App\Traits\GuardsHiddenItems;

class ImageController extends Controller
{
    use GuardsHiddenItems;
    public function index(Business $business)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        $images = $business->images()->ordered()->get();
        $logo = $business->logo;
        $cover = $business->coverImage;
        $gallery = $business->galleryImages;

        return Inertia::render('Owner/Images/Index', [
            'business' => $business,
            'images' => $images,
            'logo' => $logo,
            'cover' => $cover,
            'gallery' => $gallery,
        ]);
    }

    public function store(Request $request, Business $business)
    {
        Log::info('Image upload started', [
            'business_id' => $business->id,
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'files' => $request->hasFile('image') ? 'has image' : 'no image',
            'files_array' => $request->hasFile('images') ? 'has images array' : 'no images array'
        ]);

        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // ✅ Server-side lock: can't upload images to a hidden business
        if ($redirect = $this->guardNotHidden($business, 'business')) {
            return $redirect;
        }

        // Handle single image upload (logo or cover)
        if ($request->hasFile('image')) {
            $validated = $request->validate([
                'image' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,jpg,gif,webp',
                    'max:5120', // 5MB
                    'dimensions:min_width=100,min_height=100',
                    function ($attribute, $value, $fail) {
                        // Check if file is actually an image (server-side)
                        $imageInfo = getimagesize($value->getPathname());
                        if ($imageInfo === false) {
                            $fail('The file is not a valid image.');
                        }

                        // Check for malicious content (basic)
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

            // Get the file
            $file = $request->file('image');

            // Generate secure filename
            $filename = time() . '_' . bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs('businesses/' . $business->id . '/images', $filename, 'public');

            // ✅ Process image — downscale, re-encode, generate variants
            try {
                $processedPath = app(ImageService::class)->process($path);

                // If the service rewrote the file (.png → .jpg), use the new path
                if ($processedPath) {
                    $path = $processedPath;
                }
            } catch (\Throwable $e) {
                Log::warning('Image processing failed', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Single image uploaded + processed', ['path' => $path]);

            // Remove existing if logo or cover
            if ($validated['type'] === 'logo') {
                // Check if business has an existing logo (string path)
                if ($business->logo) {
                    // $business->logo is a string path, not an object
                    if (Storage::disk('public')->exists($business->logo)) {
                        Storage::disk('public')->delete($business->logo);
                    }
                }

                // Also check if there's a logo record in BusinessImage table
                $existingLogoRecord = $business->logo()->first();
                if ($existingLogoRecord) {
                    if (Storage::disk('public')->exists($existingLogoRecord->path)) {
                        Storage::disk('public')->delete($existingLogoRecord->path);
                    }
                    $existingLogoRecord->delete();
                }

                // Update business logo field with the new path
                $business->update(['logo' => $path]);
            }

            if ($validated['type'] === 'cover') {
                // Check if business has an existing cover (string path)
                if ($business->cover_image) {
                    // $business->cover_image is a string path
                    if (Storage::disk('public')->exists($business->cover_image)) {
                        Storage::disk('public')->delete($business->cover_image);
                    }
                }

                // Also check if there's a cover record in BusinessImage table
                $existingCoverRecord = $business->coverImage()->first();
                if ($existingCoverRecord) {
                    if (Storage::disk('public')->exists($existingCoverRecord->path)) {
                        Storage::disk('public')->delete($existingCoverRecord->path);
                    }
                    $existingCoverRecord->delete();
                }

                // Update business cover_image field with the new path
                $business->update(['cover_image' => $path]);
            }

            // Create the image record
            $image = BusinessImage::create([
                'business_id' => $business->id,
                'path' => $path,
                'caption' => $validated['caption'] ?? null,
                'type' => $validated['type'],
                'is_primary' => false,
                'sort_order' => $business->images()->count() + 1,
            ]);

            Log::info('Image record created', ['image_id' => $image->id]);

            $typeLabel = match ($validated['type']) {
                'logo' => 'Logo',
                'cover' => 'Cover image',
                default => 'Image',
            };

            return redirect()->back()
                ->with('success', "{$typeLabel} uploaded successfully.");
        }
        // Handle multiple gallery images
        if ($request->hasFile('images')) {
            $validated = $request->validate([
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'type' => 'required|in:gallery',
            ]);

            $uploaded = 0;
            $imageService = app(ImageService::class);

            foreach ($request->file('images') as $file) {
                $filename = time() . '_' . bin2hex(random_bytes(8)) . '_' . $uploaded . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('businesses/' . $business->id . '/images', $filename, 'public');

                // ✅ Process each gallery image
                try {
                    $processedPath = $imageService->process($path);
                    if ($processedPath) {
                        $path = $processedPath;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gallery image processing failed', [
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                }

                BusinessImage::create([
                    'business_id' => $business->id,
                    'path' => $path,
                    'caption' => null,
                    'type' => 'gallery',
                    'is_primary' => false,
                    'sort_order' => $business->images()->count() + 1,
                ]);
                $uploaded++;
            }

            Log::info('Multiple images uploaded', ['count' => $uploaded]);

            return redirect()->back();
        }

        Log::warning('No image file found in request');
        return redirect()->back()->with('error', 'No image file found.');
    }

    // public function setPrimary(Business $business, BusinessImage $image)
    // {
    //     if ($business->owner_id !== auth()->id()) {
    //         abort(403);
    //     }

    //     // ✅ Server-side lock
    //     if ($redirect = $this->guardNotHidden($business, 'business')) {
    //         return $redirect;
    //     }
    //     if ($redirect = $this->guardNotHidden($image, 'image')) {
    //         return $redirect;
    //     }


    //     if ($image->type !== 'gallery') {
    //         return redirect()->back()->with('error', 'Only gallery images can be set as primary.');
    //     }

    //     // Unset other primary gallery images
    //     $business->images()->where('type', 'gallery')->update(['is_primary' => false]);

    //     $image->update(['is_primary' => true]);

    //     return redirect()->back();
    // }

    public function destroy(Business $business, BusinessImage $image)
    {
        if ($business->owner_id !== auth()->id()) {
            abort(403);
        }

        // ✅ Delete the file + all its variants.
        //    Guard against stale paths (e.g., .png in DB, .jpg on disk).
        $disk = Storage::disk('public');
        $pathsToDelete = [$image->path];

        if (!$disk->exists($image->path)) {
            $jpgTwin = preg_replace('#\.[^/.]+$#', '.jpg', $image->path);
            if ($disk->exists($jpgTwin)) {
                $pathsToDelete[] = $jpgTwin;
            }
        }

        $service = app(ImageService::class);
        foreach ($pathsToDelete as $p) {
            $service->delete($p);
        }

        // If this was the logo, clear the business logo field
        if ($image->type === 'logo') {
            $business->update(['logo' => null]);
        }

        // If this was the cover, clear the business cover_image field
        if ($image->type === 'cover') {
            $business->update(['cover_image' => null]);
        }

        $image->delete();

        return redirect()->back()
            ->with('success', 'Image deleted successfully.');
    }
}