<?php

namespace App\Support;

/**
 * PHASE 13 — CANONICAL DISCOVERY SORT VOCABULARY.
 *
 * `/search` (Meilisearch) and `/directory` (SQL) are two engines, but they must
 * expose the SAME user-facing sort vocabulary and mean the same thing by it.
 * This class is the single source of truth for:
 *
 *   - the user-facing option keys
 *   - the Meilisearch `orderBy` mapping
 *   - the SQL `orderBy` mapping
 *
 * Behavioural consistency is the requirement, not implementation symmetry.
 */
final class DiscoverySort
{
    public const RELEVANCE = 'relevance';
    public const RATING = 'rating';
    public const REVIEWS = 'reviews';
    public const NEWEST = 'newest';
    public const FEATURED = 'featured';

    /**
     * The canonical, user-facing vocabulary. Both engines render exactly this.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::RELEVANCE => 'Relevance',
            self::RATING => 'Highest rated',
            self::REVIEWS => 'Most reviewed',
            self::NEWEST => 'Newest',
            self::FEATURED => 'Featured first',
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    /** Normalize any user-supplied sort to a canonical key. */
    public static function normalize(?string $sort): string
    {
        $sort = is_string($sort) ? strtolower(trim($sort)) : '';

        return match ($sort) {
            'rating', 'top_rated', 'highest_rated', 'best' => self::RATING,
            'reviews', 'review', 'review_count', 'most_reviewed' => self::REVIEWS,
            'newest', 'latest', 'recent', 'new' => self::NEWEST,
            'featured', 'promoted' => self::FEATURED,
            // Existing directory-only sort retained so it is not silently lost.
            'name', 'alphabetical', 'a_z' => 'name',
            default => self::RELEVANCE,
        };
    }

    /**
     * Meilisearch sort applied via Scout `orderBy`.
     *
     * `rating` and `reviews_count` are the OWNING BUSINESS's review aggregate —
     * reviews are Business-owned (Phase 11/12) and a Listing displays its
     * organization's metrics through `Listing::businessReviews()`. A
     * Business-less Listing indexes 0.0 / 0, which is the existing zero
     * semantics, not a fabricated rating.
     *
     * @return array<int, array{0:string,1:string}>
     */
    public static function meilisearchOrder(string $canonical): array
    {
        return match ($canonical) {
            self::RATING => [['rating', 'desc']],
            self::REVIEWS => [['reviews_count', 'desc']],
            self::NEWEST => [['published_at', 'desc']],
            self::FEATURED => [['is_featured_rank', 'desc']],
            default => [],
        };
    }

    /**
     * SQL sort for the directory engine. Mirrors `meilisearchOrder()` using the
     * Business review aggregate columns the directory already selects.
     *
     * @return array<int, array{0:string,1:string}>
     */
    public static function sqlOrder(string $canonical): array
    {
        return match ($canonical) {
            self::RATING => [['business_reviews_avg_rating', 'desc']],
            self::REVIEWS => [['business_reviews_count', 'desc']],
            self::NEWEST => [['published_at', 'desc']],
            self::FEATURED => [['is_featured', 'desc']],
            default => [],
        };
    }

    /**
     * The aggregate columns each engine must load for a given sort to work.
     * Returning them from one place stops the two engines drifting.
     *
     * @return array<int, string>
     */
    public static function requiredAggregates(string $canonical): array
    {
        return match ($canonical) {
            self::RATING => ['businessReviews'],
            self::REVIEWS => ['businessReviews'],
            default => [],
        };
    }
}
