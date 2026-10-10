# Phase 23H — Listing Child → Search Synchronization

**Status: COMPLETE.** Category and service mutations now synchronize the owning
Listing's Meilisearch document, and the audit was extended to **every** child
whose data `Listing::toSearchableArray()` publishes.

---

## 1. The defect

`Listing::toSearchableArray()` publishes data OWNED BY OTHER ROWS, but Scout only
observes a model's **own** `saved`/`deleted` events. Changing a child therefore
left the Listing's document stale until the Listing row happened to be saved
again. Phase 23G measured this on the live index:

```
Listing indexed          -> category_ids = []   services_names = []
attach Category          \
create  ListingService   / -> category_ids = []   services_names = []   (3s settle)
save Listing                -> category_ids = [X]  services_names = [Y]
```

So `DATABASE STATE ≠ SEARCH INDEX STATE` until an unrelated Listing save
happened to repair it.

## 2. Every search-affecting child (the audit)

`toSearchableArray()` classified field by field:

| Document field(s) | Source | Child mutation? | Propagation before 23H |
|---|---|---|---|
| `id, name, description, slug, type, status, is_featured, hidden, created_at, published_at, business_id, location_id, is_featured_rank` | Listing columns | no | Scout covers the Listing's own save |
| `category_ids`, `categories_names` | **`listing_categories` pivot** | **yes** | **BROKEN — fixed here** |
| `services_names` | **`listing_services`** | **yes** | **BROKEN — fixed here** |
| `city_id`, `region_id`, `country_id`, `city`, `region`, `country`, `address`, `is_open_now` | **`Location`** | **yes** | **BROKEN — fixed here** |
| `has_active_subscription` | **owner's `Subscription`** | **yes** | **BROKEN — fixed here** |
| `rating`, `reviews_count` | **the Listing's approved `Review`s** | **yes** | **BROKEN — fixed here** |
| `images`, `contacts`, `favorites`, `analytics`, `coupons`, `leads` | not published | no | **none required — deliberate decision** |

Images, contacts, favourites and analytics are **not** in the document at all, so
no synchronization is required; adding one would be work with no observable
effect. That is a decision, not an omission.

## 3. Root cause

Two independent causes, both model-level:

1. **Pivot mutations were invisible.** `Listing::categories()` was a plain
   `belongsToMany`. Laravel only fires pivot MODEL events when the relation
   declares `->using(...)`; otherwise `attach()` **bulk-inserts** and `detach()`
   **bulk-deletes** straight through the query builder
   (`InteractsWithPivotTable::attach()` / `::detach()`), firing nothing. Laravel
   12.69.2 does **not** fire `pivotAttached`/`pivotDetached` either — verified in
   vendor.
2. **Service mutations were visible but unobserved.** `ListingService` had no
   observer, and Scout had no reason to look at it.

## 4. Synchronization architecture

One mechanism, one entry point, no controller involvement:

```
child mutation
   ↓
observer on the child boundary        (ListingCategoryPivot, ListingService,
   ↓                                   Location, Review, Subscription)
App\Support\ListingSearchSync
   ↓  shouldBeSearchable()
Listing::searchable() / unsearchable()
   ↓
listings index
```

| Boundary | File | Covers |
|---|---|---|
| Pivot `listing_categories` | `app/Models/ListingCategoryPivot.php` + `ListingCategoryPivotObserver` | attach, detach, sync, syncWithoutDetaching, toggle, updateExistingPivot |
| `ListingService` | `ListingServiceObserver` | create, update, delete, restore, forceDelete |
| `Location` | `LocationObserver` | save, delete, restore, forceDelete — fans out to every Listing referencing it |
| `Review` | `ReviewObserver` (extended) | approve, rating edit, delete |
| `Subscription` | `SubscriptionObserver` (extended) | create, status change, delete, restore — fans out to the owner's Listings |

**Why this is authoritative**

* It is the **only** place a Listing is re-indexed for a child change
  (verified: `ListingSearchSync` is the sole `->searchable()`/`->unsearchable()`
  call site in `app/` outside a comment).
* It is **not** a blind parent save. There is **no `->touch()` anywhere**
  (verified repository-wide) and no arbitrary column write: search
  synchronization is an explicit act, not a side effect of a timestamp.
* The LISTING is always the path — never the Business. A Business-less
  Professional synchronizes identically.
* `shouldBeSearchable()` is evaluated **before** indexing. Scout's `searchable()`
  does not consult it, so a draft or hidden Listing must never be pushed into the
  index merely because a child changed.

## 5. Transaction and queue behaviour

Audited, not redesigned:

* **Scout indexing is SYNCHRONOUS.** `config('scout.queue')` is `false` and
  `scout.after_commit` is `false`, so an observer write reaches Meilisearch
  immediately. This mechanism adds no queue and no async work.
* **Child mutations can fire inside a DB transaction**
  (`Owner\ListingController` wraps create/update in `DB::transaction`). The sync
  reads the **same connection**, so in the normal path it publishes the correct
  new state — but if the transaction **rolls back**, the index would hold state
  that never committed.
* That exposure is **pre-existing and systemic**: Scout's own observer already
  indexes a Listing inside `DB::transaction`. Moving to after-commit dispatch
  would change every Scout write in the application, which this phase must not
  do. **Documented, not changed** — recorded as a remaining issue.

## 6. Bulk operations

Query-builder bulk operations fire **no** model events. Audited:

| Site | Operation | Resolution |
|---|---|---|
| `Owner\ListingController:161` | `categories()->sync()` | CANONICAL — routed through the custom pivot |
| `Owner\ServiceController` store/update/destroy | single-model | CANONICAL — observer fires |
| `Admin\BusinessController` (restore) | `ListingService::withTrashed()->whereIn(...)->restore()` | **BYPASS — explicit `ListingSearchSync::forListingIds()` added** |
| `Admin\BusinessController` (forceDelete) | `...->forceDelete()` | **BYPASS — explicit sync added** |
| `Owner\BusinessController` (destroy) | `$business->services()->delete()` | **BYPASS — explicit sync added** |
| `SubscriptionObserver` superseding update | bulk | INTENTIONAL — superseding leaves the account active, so `has_active_subscription` is unchanged |

`PlanEnforcementService`'s bulk `hidden_at` updates on **services** are harmless:
`services_names` publishes every service name and does not filter on
`hidden_at`, so hiding a service does not change the document.

## 7. Tests

`tests/Feature/Search/ListingChildSearchSyncTest.php` — **13 tests, 39 assertions**,
all asserting REAL index behaviour through `Listing::search()` (the actual
`test_listings` index on the running Meilisearch server) or the document itself.
None inspects observer registration.

* categories: attach, detach, sibling isolation
* services: create, delete, sibling isolation
* visibility: draft stays absent, hidden stays absent — each also proves the
  children ARE persisted and that publishing/unhiding publishes the current
  child state
* Business-less Professional synchronizes identically
* combined category + service changes converge on one current document
* Location, Subscription and Review each synchronize

**These tests fail without the fix**: reverting only the two wiring edits
(`->using(...)` and the observer registrations) produces **8 failures**, proving
they assert the defect and not the implementation.

## 8. Live development verification

Against the **live development Meilisearch server** (§16), 11/11 checks passed:

* Listing A found by its Category filter and by its Service name
* Listing B (Business-less Professional) found by both
* cross-Listing isolation in both directions
* after removing the Category and Service, Listing A **no longer** matches either
* Listing B's untouched classification survives A's removals

The development database and indexes were returned to their pre-phase state
(`listings` 0, `categories` 0, no `businesses`, database all-zero) and the
temporary harness was deleted.

## 9. Not changed

No new index, no ranking change, no filter change, no `/search` or `/directory`
change, no SQL fallback, no Business search, no Category discovery redesign, and
no `phpunit.xml` change. `db` isolation from Phase 22D is untouched.
