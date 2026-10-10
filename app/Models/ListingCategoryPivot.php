<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * PHASE 23H — the `listing_categories` pivot as an observable MODEL.
 *
 * Laravel's `BelongsToMany` only fires pivot MODEL events when the relation
 * declares `->using(...)`. Without it, `attach()` bulk-inserts and `detach()`
 * bulk-deletes straight through the query builder
 * (`InteractsWithPivotTable::attach()` / `::detach()`), firing nothing at all —
 * which is why a category change could never reach the search index.
 *
 * Declaring this class routes every category mutation — attach, detach, sync,
 * syncWithoutDetaching, toggle, updateExistingPivot — through
 * `newPivot()->save()` and `$pivot->delete()`, so ONE observer covers them all.
 *
 * `Pivot` supplies `$incrementing = false` and `$guarded = []`; composite
 * pivots have no primary key, which is why it is a Pivot and not a Model.
 */
class ListingCategoryPivot extends Pivot
{
    protected $table = 'listing_categories';
}
