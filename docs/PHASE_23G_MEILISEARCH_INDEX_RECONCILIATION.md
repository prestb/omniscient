# Phase 23G — Meilisearch Index Reconciliation

**Status: COMPLETE.** The development Meilisearch instance was reconciled and one
genuine defect was found, proven and **reported (not fixed)**.

**No production code was changed.**

---

## 1. Why this phase exists

Phase 23F found the development Meilisearch server in an inconsistent state after
an environment rebuild. This phase answers one question — *are `businesses` and
`categories` genuine current indexes, or stale development state?* — and then
reconciles the server accordingly.

## 2. Index audit

| Index | DB source | Current producer | Current consumer | Canonical? | Status |
|---|---|---|---|---|---|
| `listings` | `Listing` | **YES** — `Listing` uses `Scout\Searchable`; Scout model events + `scout:import` | **YES** — `SearchController::index()` L86 and `::autocomplete()` L234 | **YES** | **CANONICAL** — rebuilt |
| `businesses` | `Business` | **NONE** | **NONE** | no | **STALE** — deleted |
| `categories` | `Category` | **YES** — `Category` uses `Scout\Searchable` | **NONE** — every lookup is SQL (`Category::query()`) | no | **PRODUCER CURRENT / DOCUMENTS ORPHANED** — flushed |
| `test_listings` | `Listing` (testing) | test suite | test suite | test-only | untouched |
| `test_categories` | `Category` (testing) | test suite | test suite | test-only | untouched |

## 3. Business index — **STALE**

Proven repository-wide, not from a single negative:

* `app/Models/Business.php` does **not** use `Laravel\Scout\Searchable` (verified by
  trait import scan; the only textual match is a comment mentioning
  `toSearchableArray()`).
* Only **two** models use Searchable: `Listing` and `Category`.
* **No** `Business::search()`, `Business::makeAllSearchable()`,
  `Business::searchable()` or `Business::unsearchable()` exists anywhere in `app/`.
* The **only** raw Meilisearch client in the application is
  `ConfigureMeilisearch`, and it targets `SearchIndexName::listing()`.
* Every `'businesses'` string in the repository is a **database table name**
  (`Rule::unique`, `join`, migrations, `withCount`), an Eloquent **relationship**,
  a **cache tag**, an Inertia **prop key**, or a **route prefix**. None is a
  Meilisearch index name.
* `config/scout.php` has **no** `index-settings.businesses` key (removed in 23C-R5
  F3), so `scout:sync-index-settings` cannot recreate it either.

The surviving index was **created 2026-09-03 and last written 2026-09-04** —
before Phase 11 Wave 1D-1 removed Business's searchability. Its 3 documents are
remnants of a restored data snapshot.

**Conclusion: STALE.** No producer, no consumer, no path to recreation.

## 4. Categories index — current producer, no consumer

* `Category` **does** use Searchable, with an explicit `searchableAs()` →
  `SearchIndexName::category()` and a `toSearchableArray()`. Scout therefore
  writes the index on every Category save.
* **Nothing reads it.** Every category lookup in the application is SQL:
  `Category::query()` in `SearchController::autocomplete`, `ExploreService` and
  `SearchIntentParser`. There is no `Category::search()` anywhere.
* It is **not** a discovery entity — discovery is Listing-canonical.

The index held **71 documents dated 2026-09-03 while the development `categories`
table held 0 rows** — orphaned documents with no source. Its Scout capability is
legitimate and current, so **the capability was kept and only the orphaned
documents were removed.** Removing Scout from `Category` would be a Category
discovery redesign, which this phase forbids.

**Conclusion: producer CURRENT, consumer ABSENT, documents were ORPHANED.**

## 5. Canonical discovery index

```text
Listing → Scout → listings → Meilisearch
```

Unchanged and verified: `Listing::searchableAs()` → `SearchIndexName::listing()`,
`toSearchableArray()`, `shouldBeSearchable()` (published AND `hidden_at IS NULL`)
and `makeAllSearchableUsing()` all still govern the one canonical index. Nothing
about Listing search was modified.

## 6. Reconciliation performed

Resolution was **proven before anything destructive ran**: with `APP_ENV=local`
and `scout.prefix` empty, the resolved targets are `listings` and `categories`,
and `scout:delete-index businesses` targets `businesses`. Only then:

| Action | Command | Result |
|---|---|---|
| Remove the stale Business index | `php artisan scout:delete-index businesses` | `Index "businesses" deleted.` |
| Remove 71 orphaned category documents | `php artisan scout:flush "App\Models\Category"` | `All [App\Models\Category] records have been flushed.` |
| Ensure the canonical index exists with canonical settings | `php artisan meilisearch:configure` | settings written to `listings` |
| Rebuild from the current database | `php artisan scout:import "App\Models\Listing"` | `All [App\Models\Listing] records have been imported.` |

No new command was invented; every step uses the repository's existing mechanism.

## 7. Final development state

```
BEFORE                            AFTER
businesses       3        →       (removed)
categories      71        →       categories       0
listings      absent      →       listings         0   (canonical settings applied)
test_categories 54        →       test_categories 54   (untouched)
test_listings  305        →       test_listings  305   (untouched)
```

The development **database** was empty throughout (0 listings, 0 categories,
0 users), so the reconciled index population is correctly **0**.

§15 population check: searchable Listings in the database
(`status='published' AND hidden_at IS NULL`) = **0**; `listings` index documents =
**0**. ✔

## 8. Verification performed

* **Resolution probe** (read-only, before any mutation): resolved index names,
  environment and prefix printed and confirmed.
* **Live scenarios against the live development server** — a temporary,
  gitignored harness created the minimum dataset, exercised the real search path
  and removed everything afterwards. **All 9 checks passed:**
  Listing search reaches the index; found under the `/search` controller filter
  chain; Business-less Professional carries `type=professional` and no business;
  `where('category_ids', …)` filtering matches; service-name search via
  `services_names` matches; `GET /search` returns HTTP 200; its props contain the
  Listing; its canonical paginator `total` is coherent; and a draft Listing is
  **removed** from the index.
* **Cleanup verified**: the development database was returned to all-zero
  (users, listings, categories, businesses, subscriptions, plans,
  listing_services, listing_categories) and the harness files were deleted.

## 9. DEFECT FOUND — child-relation changes do not reach the index

**Proven against the live index. Reported, not fixed** (Phase 23G forbids
altering Listing search).

`toSearchableArray()` publishes `category_ids`, `categories_names` and
`services_names`; `meilisearch:configure` makes `category_ids` **filterable** and
both name arrays **searchable**; `/search?category=<id>` filters on
`category_ids`. But **nothing re-indexes a Listing when its categories or
services change**:

* Scout observes only the **Listing model's own** `saved`/`deleted` events.
* `Owner\ListingController:161` calls `$listing->categories()->sync(...)` and
  returns — no `save()`, no `touch()`, no `searchable()`.
* `ServiceController` creates/updates `ListingService` rows — no Listing event.
* **Nothing in `app/` calls `->touch()` on a Listing** (verified), and the only
  observers registered are `Review` and `Subscription`.

Measured on the real index:

```
before children: category_ids=[]  services_names=[]
after attaching a category and creating a service (3s settle):
                 category_ids=[]  services_names=[]      <-- no propagation
toSearchableArray() would carry both                      <-- data + contract exist
after saving the Listing: both propagate                  <-- content is right when written
```

**Impact:** a Listing edited into a new category (or given a new service) is
**not findable under that category or service** until the Listing row itself is
saved for some other reason, and a Listing removed from a category can linger
under it. This is a discovery-correctness defect, not an environment issue.

**Not fixed here** because the fix belongs to Listing-search behaviour (an
observer on the `listing_categories` pivot and on `ListingService`, or a touch
after sync), which the phase brief explicitly excludes.

## 10. Application code changes

```text
NO PRODUCTION CODE CHANGED
```

## 11. Residual risks

* `scout:delete-all-indexes` remains destructive under an empty prefix
  (vendor behaviour, recorded in 23C-R5 §13 — Environment-only).
* The `categories` index has a live producer and **no consumer**; it is retained
  deliberately and is now consistent with an empty source table.
* CI is **NOT EXECUTED** — see `docs/PHASE_23C_R5_AUDIT.md` §11.4.
