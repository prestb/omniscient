<?php

namespace App\Services;

use App\Models\Listing;
use App\Support\ListingType;

/**
 * PHASE 14 — LISTING-SCOPED COMPLETENESS.
 *
 * Completeness belongs to the LISTING, the canonical discoverable entity. The
 * existing BusinessCompletenessService stays Business-scoped and untouched: a
 * Business-less professional Listing had NO completeness concept at all.
 *
 * The score is DERIVED, never persisted — no column, no table. It is
 * deterministic from the Listing's current, publicly-visible state.
 *
 * It is a quality / owner-guidance / presentation concept. It is deliberately
 * NOT a search signal: nothing here reaches toSearchableArray(), Meilisearch
 * ranking, or any sort.
 */
class ListingCompletenessService
{
    /**
     * Weighted checklist. Only meaningful, currently-visible content counts.
     *
     * `location` is the one conditional item — see applies().
     */
    private const CHECKLIST = [
        [
            'key' => 'description',
            'label' => 'Add a description',
            'hint' => 'Tell visitors what you offer',
            'weight' => 20,
        ],
        [
            'key' => 'categories',
            'label' => 'Choose at least one category',
            'hint' => 'Helps visitors discover you',
            'weight' => 15,
        ],
        [
            'key' => 'services',
            'label' => 'Add at least one service',
            'hint' => 'Show what you actually do',
            'weight' => 15,
        ],
        [
            'key' => 'contacts',
            'label' => 'Add a way to get in touch',
            'hint' => 'Phone, WhatsApp or a social link',
            'weight' => 15,
        ],
        [
            'key' => 'logo',
            'label' => 'Add a logo',
            'hint' => 'Makes your Listing recognizable',
            'weight' => 10,
        ],
        [
            'key' => 'cover',
            'label' => 'Add a cover image',
            'hint' => 'Stands out in search results',
            'weight' => 10,
        ],
        [
            'key' => 'gallery',
            'label' => 'Add at least one gallery photo',
            'hint' => 'Photos build confidence',
            'weight' => 5,
        ],
        [
            'key' => 'location',
            'label' => 'Add your location',
            'hint' => 'Visitors need to find you',
            'weight' => 10,
            // Only a physical presence needs a Location.
            'only_for' => [ListingType::BUSINESS, ListingType::STORE],
        ],
    ];

    /** Minimum trimmed description length considered meaningful. */
    private const MIN_DESCRIPTION_LENGTH = 20;

    /**
     * Calculate completeness for a Listing.
     *
     * Safe for a Business-less Listing: nothing here reads a Business.
     *
     * @return array{score:int,tier:array{key:string,label:string,color:string},completed:int,total:int,missing:array<int,array{key:string,label:string,hint:string}>}
     */
    public function calculate(Listing $listing): array
    {
        $listing->loadMissing([
            'categories:id',
            'services:id,listing_id,name,hidden_at',
            'contacts:id,listing_id,type,value',
            'images:id,listing_id,type,hidden_at',
            'location:id',
        ]);

        $earned = 0;
        $applicable = 0;
        $completed = 0;
        $total = 0;
        $missing = [];

        foreach (self::CHECKLIST as $item) {
            if (!$this->applies($item, $listing)) {
                continue;
            }

            $total++;
            $applicable += $item['weight'];

            if ($this->isSatisfied($item['key'], $listing)) {
                $earned += $item['weight'];
                $completed++;
                continue;
            }

            $missing[] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'hint' => $item['hint'],
            ];
        }

        // An inapplicable item is excluded entirely, so a locationless
        // professional is scored out of the remaining 90 points and can still
        // reach 100%.
        $score = $applicable > 0 ? (int) round(($earned / $applicable) * 100) : 0;

        return [
            'score' => $score,
            'tier' => $this->tier($score),
            'completed' => $completed,
            'total' => $total,
            'missing' => $missing,
        ];
    }

    /**
     * Whether a checklist item applies to this Listing.
     *
     * Location applies only to Listing types that represent a physical
     * presence (BUSINESS, STORE). A PROFESSIONAL may legitimately operate
     * remotely, so a missing Location is never counted against them — neither
     * required nor penalised.
     */
    private function applies(array $item, Listing $listing): bool
    {
        if (!isset($item['only_for'])) {
            return true;
        }

        return in_array($listing->getListingType(), $item['only_for'], true);
    }

    /** Only meaningful, currently-visible content counts. */
    private function isSatisfied(string $key, Listing $listing): bool
    {
        return match ($key) {
            'description' => $this->hasMeaningfulDescription($listing),

            'categories' => $listing->categories->isNotEmpty(),

            // hidden/deleted services must not count.
            'services' => $listing->services
                ->filter(fn($s) => $s->hidden_at === null && trim((string) $s->name) !== '')
                ->isNotEmpty(),

            // listing_contacts has no hidden_at; soft-deletes are already
            // excluded by the relation. A blank value still does not count.
            'contacts' => $listing->contacts
                ->filter(fn($c) => trim((string) $c->value) !== '')
                ->isNotEmpty(),

            'logo' => $this->hasImage($listing, 'logo'),
            'cover' => $this->hasImage($listing, 'cover'),
            'gallery' => $this->hasImage($listing, 'gallery'),

            'location' => $listing->location_id !== null && $listing->location !== null,

            default => false,
        };
    }

    private function hasMeaningfulDescription(Listing $listing): bool
    {
        // Whitespace-only is not a description.
        return mb_strlen(trim((string) $listing->description)) >= self::MIN_DESCRIPTION_LENGTH;
    }

    /** A hidden image is not a present image. */
    private function hasImage(Listing $listing, string $type): bool
    {
        return $listing->images
            ->filter(fn($i) => $i->hidden_at === null && $i->type === $type)
            ->isNotEmpty();
    }

    /**
     * Tier naming follows the existing dashboard conventions.
     *
     * @return array{key:string,label:string,color:string}
     */
    private function tier(int $score): array
    {
        return match (true) {
            $score >= 90 => ['key' => 'excellent', 'label' => 'Excellent', 'color' => 'emerald'],
            $score >= 70 => ['key' => 'good', 'label' => 'Good', 'color' => 'blue'],
            $score >= 40 => ['key' => 'fair', 'label' => 'Needs work', 'color' => 'amber'],
            default => ['key' => 'poor', 'label' => 'Incomplete', 'color' => 'red'],
        };
    }
}
