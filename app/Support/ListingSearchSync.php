<?php

namespace App\Support;

use App\Models\Listing;

/**
 * PHASE 23H — LISTING CHILD → SEARCH SYNCHRONIZATION.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE DEFECT THIS CLOSES
 * ─────────────────────────────────────────────────────────────────────────
 * `Listing::toSearchableArray()` publishes data OWNED BY OTHER ROWS:
 *
 *   category_ids, categories_names  <- the `listing_categories` pivot
 *   services_names                  <- `listing_services`
 *
 * Scout only observes a model's OWN saved/deleted events, so changing one of
 * those children left the Listing's Meilisearch document stale until the
 * Listing row itself happened to be saved again. Measured in Phase 23G against
 * the live index: attaching a category and creating a service produced
 * `category_ids: []` and `services_names: []` indefinitely.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THIS IS THE ONLY SYNCHRONIZATION ENTRY POINT
 * ─────────────────────────────────────────────────────────────────────────
 * Child observers call this; nothing else re-indexes a Listing for a child
 * change. Controllers are deliberately NOT involved: the invariant has to hold
 * for any mutation path, including ones that do not exist yet.
 *
 * It does NOT touch the Listing row. There is no `touch()` and no column
 * update: search synchronization is an explicit act, not a side effect of a
 * timestamp, and coupling the two would obscure why the write happened.
 */
final class ListingSearchSync
{
    /** Synchronize the Listing that owns the given row, if it still exists. */
    public static function forListingId(?int $listingId): void
    {
        if ($listingId === null) {
            return;
        }

        // A soft-deleted Listing resolves to null and is skipped: Scout's own
        // `deleted` event has already removed it from the index.
        self::listing(Listing::find($listingId));
    }

    /**
     * Synchronize many Listings at once — used by the observers whose child is
     * SHARED (a Location, an owner's Subscription) and by the audited bulk-query
     * paths that cannot fire model events.
     *
     * @param  iterable<int|null>  $listingIds
     */
    public static function forListingIds(iterable $listingIds): void
    {
        $ids = collect($listingIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        foreach (Listing::whereIn('id', $ids)->get() as $listing) {
            self::listing($listing);
        }
    }

    /**
     * Push the Listing's CURRENT state through Scout.
     *
     * The canonical visibility contract decides add-versus-remove. Scout's
     * `searchable()` does NOT consult `shouldBeSearchable()`, so it is
     * evaluated here — a draft or hidden Listing must never be pushed into the
     * index merely because one of its children changed.
     */
    public static function listing(?Listing $listing): void
    {
        if ($listing === null) {
            return;
        }

        if ($listing->shouldBeSearchable()) {
            $listing->searchable();
        } else {
            $listing->unsearchable();
        }
    }
}
