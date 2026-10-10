<?php

namespace App\Observers;

use App\Models\ListingCategoryPivot;
use App\Support\ListingSearchSync;

/**
 * PHASE 23H — category changes synchronize the owning Listing's search document.
 *
 * `listing_categories` rows carry `category_ids` and `categories_names` in
 * `Listing::toSearchableArray()`. Attaching or detaching one changes the
 * Listing's discoverable classification, so the document must be rebuilt.
 *
 * This observer exists ONLY because `Listing::categories()` declares
 * `->using(ListingCategoryPivot::class)`. Without that, Laravel bulk-inserts
 * and bulk-deletes pivot rows and these events never fire — which was the
 * defect.
 *
 * Deletion is covered because an empty `category_ids` is a real, discoverable
 * change: a Listing removed from a category must stop matching that category.
 */
class ListingCategoryPivotObserver
{
    public function created(ListingCategoryPivot $pivot): void
    {
        ListingSearchSync::forListingId($pivot->listing_id);
    }

    public function deleted(ListingCategoryPivot $pivot): void
    {
        ListingSearchSync::forListingId($pivot->listing_id);
    }
}
