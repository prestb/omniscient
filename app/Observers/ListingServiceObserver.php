<?php

namespace App\Observers;

use App\Models\ListingService;
use App\Support\ListingSearchSync;

/**
 * PHASE 23H — service changes synchronize the owning Listing's search document.
 *
 * `ListingService` rows feed `services_names`, which
 * `meilisearch:configure` declares as a SEARCHABLE attribute. Creating,
 * renaming, hiding, deleting or restoring a service therefore changes what the
 * Listing matches, so the document must be rebuilt.
 *
 * `saved` covers both create and update; `deleted` covers the soft delete;
 * `restored` covers a restore; `forceDeleted` covers a hard delete. Those are
 * the model-level paths. Query-builder bulk operations
 * (`ListingService::whereIn(...)->delete()`) do NOT fire model events; see
 * docs/PHASE_23H_LISTING_CHILD_SEARCH_SYNCHRONIZATION.md for the audited list
 * of those and the decisions taken.
 */
class ListingServiceObserver
{
    public function saved(ListingService $service): void
    {
        ListingSearchSync::forListingId($service->listing_id);
    }

    public function deleted(ListingService $service): void
    {
        ListingSearchSync::forListingId($service->listing_id);
    }

    public function restored(ListingService $service): void
    {
        ListingSearchSync::forListingId($service->listing_id);
    }

    public function forceDeleted(ListingService $service): void
    {
        ListingSearchSync::forListingId($service->listing_id);
    }
}
