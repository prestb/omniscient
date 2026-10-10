<?php

namespace App\Observers;

use App\Models\Listing;
use App\Models\Location;
use App\Support\ListingSearchSync;

/**
 * PHASE 23H — Location changes synchronize every affected Listing's document.
 *
 * `Listing::toSearchableArray()` publishes `city_id`, `region_id`, `country_id`,
 * `city`, `region`, `country`, `address` and `is_open_now` — all read from the
 * Listing's Location. `/search` filters on the location ids and sorts on the
 * location names, so editing a Location silently invalidated every Listing
 * attached to it.
 *
 * A Location is ACCOUNT-owned and may be shared, so this fans out to every
 * Listing that references it. The fan-out is bounded by an indexed
 * `listings.location_id`.
 *
 * The LISTING is the synchronization path — never the Business. A Business-less
 * Professional with a Location synchronizes exactly like any other Listing.
 */
class LocationObserver
{
    public function saved(Location $location): void
    {
        $this->sync($location);
    }

    public function deleted(Location $location): void
    {
        $this->sync($location);
    }

    public function restored(Location $location): void
    {
        $this->sync($location);
    }

    public function forceDeleted(Location $location): void
    {
        $this->sync($location);
    }

    private function sync(Location $location): void
    {
        ListingSearchSync::forListingIds(
            Listing::where('location_id', $location->id)->pluck('id')
        );
    }
}
