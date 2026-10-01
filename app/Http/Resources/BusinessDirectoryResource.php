<?php
// app/Http/Resources/BusinessDirectoryResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessDirectoryResource extends JsonResource
{
    public function toArray($request)
    {
        $branches = $this->locations->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'city' => $branch->city?->name,
                // ✅ Map integration — coordinates for this branch
                'latitude' => $branch->latitude !== null ? (float) $branch->latitude : null,
                'longitude' => $branch->longitude !== null ? (float) $branch->longitude : null,
                'is_primary' => (bool) $branch->is_primary,

                // ✅ Override-aware — reads from Branch model accessors
                'is_open_now' => $branch->is_open_now,
                'has_override_today' => $branch->today_override !== null,
                'is_special_hours' => $branch->today_special_hours !== null,
                'override_note' => $branch->today_override?->note,
                'override_opens_at' => $branch->today_special_hours?->opens_at?->format('H:i'),
                'override_closes_at' => $branch->today_special_hours?->closes_at?->format('H:i'),

                'hours' => $branch->hours->map(function ($hour) {
                    return [
                        'day' => $hour->day_of_week,
                        'opens_at' => $hour->opens_at,
                        'closes_at' => $hour->closes_at,
                        'is_closed' => $hour->is_closed,
                        'is_24h' => $hour->is_24h,
                    ];
                }),
            ];
        });

        $openBranches = $branches->filter(fn($b) => $b['is_open_now']);
        $closedBranches = $branches->filter(fn($b) => !$b['is_open_now']);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            // Raw columns (backward compat)
            'logo' => $this->logo,
            'cover_image' => $this->cover_image,

            // Explicit URL accessors (primary path used by BusinessCard)
            'logo_url' => $this->logo_url,
            'cover_image_url' => $this->cover_image_url,

                        'categories' => $this->categories,
            'primary_branch' => $this->primaryLocation,
            'primary_location' => $this->primaryLocation,
            // ✅ Map integration — top-level coordinates shortcut
            'coordinates' => $this->resolvePrimaryCoordinates(),
            'locations' => $branches,
            // ✅ Back-compat alias — older components read `branches`
            'branches' => $branches,
            'is_open_now' => $openBranches->count() > 0,
            'open_branches_count' => $openBranches->count(),
            'closed_branches_count' => $closedBranches->count(),
            'status' => $this->status,

            // Rating — both keys for compat
            'rating' => $this->average_rating,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->total_reviews,

            // ✅ NEW — gallery photo count (drives the BusinessCard badge)
            'gallery_images_count' => $this->gallery_images_count ?? $this->galleryImages->count(),

            'is_featured' => $this->is_featured,
            'feature_flags' => [
                'verified_badge' => $this->hasVerifiedBadgeFeature(),
                'featured_listing' => $this->hasFeaturedListingFeature(),
            ],
        ];
    }

    /**
     * Return [lat, lng] for the primary branch, or the first branch
     * that has coordinates, or null.
     */
    private function resolvePrimaryCoordinates(): ?array
    {
        $primary = $this->locations->firstWhere('is_primary', true)
            ?? $this->locations->first();

        if ($primary && $primary->latitude !== null && $primary->longitude !== null) {
            return [
                'latitude' => (float) $primary->latitude,
                'longitude' => (float) $primary->longitude,
            ];
        }

        // Fall back to the first branch that has coordinates
        $withCoords = $this->locations
            ->first(fn($b) => $b->latitude !== null && $b->longitude !== null);

        if ($withCoords) {
            return [
                'latitude' => (float) $withCoords->latitude,
                'longitude' => (float) $withCoords->longitude,
            ];
        }

        return null;
    }
}