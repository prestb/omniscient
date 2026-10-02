<?php
// app/Services/BusinessCompletenessService.php

namespace App\Services;

use App\Models\Business;

class BusinessCompletenessService
{
    /**
     * Weighted checklist. Weights sum to 100.
     * Order here determines tie-breaking when sorting missing items.
     */
    private const CHECKLIST = [
        [
            'key' => 'description',
            'label' => 'Business description',
            'hint' => 'Tell customers what you do',
            'weight' => 15,
        ],
        [
            'key' => 'cover_image',
            'label' => 'Cover image',
            'hint' => 'Stand out in search results',
            'weight' => 15,
        ],
        [
            'key' => 'logo',
            'label' => 'Logo',
            'hint' => 'Make your brand recognizable',
            'weight' => 10,
        ],
        [
            'key' => 'gallery_min_1',
            'label' => 'At least 1 gallery photo',
            'hint' => 'Photos get 3x more engagement',
            'weight' => 10,
        ],
        [
            'key' => 'has_branch',
            'label' => 'Branch location',
            'hint' => 'Customers need to find you',
            'weight' => 10,
        ],
        [
            'key' => 'branch_contact',
            'label' => 'Branch address & phone',
            'hint' => 'Complete your branch details',
            'weight' => 10,
        ],
        [
            'key' => 'services',
            'label' => 'Services or products',
            'hint' => 'Show what you offer',
            'weight' => 10,
        ],
        [
            'key' => 'categories',
            'label' => 'Categories',
            'hint' => 'Help customers discover you',
            'weight' => 5,
        ],
        [
            'key' => 'gallery_min_3',
            'label' => '3 or more gallery photos',
            'hint' => 'Showcase your best work',
            'weight' => 5,
        ],
        [
            'key' => 'email_or_website',
            'label' => 'Email or website',
            'hint' => 'Give customers more ways to reach you',
            'weight' => 5,
        ],
        [
            'key' => 'business_hours',
            'label' => 'Business hours',
            'hint' => 'Let customers know when you are open',
            'weight' => 5,
        ],
    ];

    /**
     * Calculate completeness for a business.
     * Returns score (0-100) + per-item detail.
     */
    public function calculate(Business $business): array
    {
        $results = [];
        $score = 0;
        $maxScore = 0;

        // Preload relationships once
        // PHASE 18B - `logo` and `cover_image` are COLUMNS on Business, not
        // relationships (see Business::$fillable). Eager-loading them as
        // relations threw RelationNotFoundException. The checklist below
        // reads them as attributes, so no relation is required.
        $business->loadMissing(['locations.hours', 'services', 'images', 'galleryImages']);

        foreach (self::CHECKLIST as $item) {
            $complete = $this->isComplete($item['key'], $business);
            $results[] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'hint' => $item['hint'],
                'weight' => $item['weight'],
                'complete' => $complete,
            ];

            $maxScore += $item['weight'];
            if ($complete) {
                $score += $item['weight'];
            }
        }

        // Normalise to 100 in case weights don't sum to exactly 100
        $normalised = $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0;

        // Missing items sorted by weight desc
        $missing = collect($results)
            ->where('complete', false)
            ->sortByDesc('weight')
            ->values()
            ->all();

        return [
            'score' => $normalised,
            'total' => count($results),
            'completed' => collect($results)->where('complete', true)->count(),
            'items' => $results,
            'missing' => $missing,
            'tier' => $this->tier($normalised),
        ];
    }

    /**
     * Individual checks. Keep them cheap — no N+1.
     */
    private function isComplete(string $key, Business $business): bool
    {
        return match ($key) {
            'description' => filled(trim((string) $business->description)),

            // PHASE 11 / WAVE 1D-3 — organization branding is Business-owned.
            // It is no longer read out of an arbitrarily chosen Listing's media.
            'cover_image' => filled($business->cover_image),

            'logo' => filled($business->logo),

            'gallery_min_1' => $business->galleryImages()->count() >= 1,

            'gallery_min_3' => $business->galleryImages()->count() >= 3,

            'has_branch' => $business->locations()->count() >= 1,

            'branch_contact' => $business->locations()
                ->whereNotNull('address')
                ->where('address', '!=', '')
                ->where(function ($q) {
                    $q->whereNotNull('phone')->where('phone', '!=', '');
                })
                ->exists(),

            'services' => $business->services()->count() >= 1,

            'categories' => $business->categories->count() >= 1,

            'email_or_website' => filled($business->email) || filled($business->website),

            'business_hours' => $business->locations()
                ->whereHas('hours', function ($q) {
                    $q->where('is_closed', false);
                })
                ->exists(),

            default => false,
        };
    }

    /**
     * Human-friendly tier label based on score.
     */
    private function tier(int $score): array
    {
        if ($score >= 90) {
            return [
                'label' => 'Excellent',
                'message' => 'Your profile is complete. Nice work!',
                'color' => 'emerald',
            ];
        }
        if ($score >= 70) {
            return [
                'label' => 'Good',
                'message' => 'A few more additions will make it great.',
                'color' => 'blue',
            ];
        }
        if ($score >= 40) {
            return [
                'label' => 'Getting there',
                'message' => 'Add the missing items to stand out more.',
                'color' => 'amber',
            ];
        }
        return [
            'label' => 'Needs work',
            'message' => 'Complete these steps to boost your visibility.',
            'color' => 'red',
        ];
    }
}