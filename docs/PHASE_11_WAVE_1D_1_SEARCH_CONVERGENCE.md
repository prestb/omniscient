# PHASE 11 — WAVE 1D-1: SEARCH CONVERGENCE
## Business search de-indexing + Listing-centric discovery

Status: **search layer complete and verified. Database-browse layer NOT done**
(see §14). This is a partial 1D-1.

---

## 1. Initial Business search architecture — FACT

Before this slice, **both** entities carried an independent public search identity:

```text
Business::searchableAs() === 'businesses'
Listing::searchableAs()  === 'listings'
```

`Business::toSearchableArray()` produced a rich document (categories, services,
primary + union location ids, subscription flag). `Listing::toSearchableArray()`
existed but **nothing in the application queried it** — the `listings` index was
populated but never searched.

`SearchController` used `Business::search()` for both:

- `index()` — the public search results page
- `autocomplete()` — the typeahead suggestion list

`ConfigureMeilisearch` configured **only** the `businesses` index
(filterable/sortable/searchable attributes). The `listings` index had **no
settings at all**, so filtering it would have failed.

Meilisearch contained three indexes: `businesses`, `branches` (stale, from the
removed Branch era) and `categories`.

---

## 2. Listing search architecture — IMPLEMENTED

`Listing` is now the only public discoverable/search identity.

```text
Listing::searchableAs() === 'listings'
```

`Listing::toSearchableArray()` was completed so the document can carry every
rule the previous Business document supported:

| Field | Why |
|---|---|
| `id, name, description, slug, type, status, is_featured, hidden` | listing identity + lifecycle |
| `created_at, published_at` | sortable (timestamps) |
| `business_id` | **contextual organization reference only** |
| `location_id, city_id, region_id, country_id, city, region, country, address` | physical place (0/1 Location) |
| `is_open_now` | preserves the existing `open_now` filter |
| `category_ids, categories_names` | Listing-owned taxonomy |
| `services_names` | Listing-owned services |
| `has_active_subscription` | **account-scoped** paid-visibility rule, derived from the owner |

Eager loading extended to `location.hours` and `owner.activeSubscription` so the
document builds without N+1 at index time.

> **DECISION:** `has_active_subscription` moves from the Business row to the
> Listing's **owner**, because subscriptions are account-scoped (Wave 1D §7/§1).
> This preserves the previous visibility rule without keeping Business searchable.

---

## 3. Business Scout/index changes — IMPLEMENTED

| Change | Detail |
|---|---|
| `Laravel\Scout\Searchable` trait | **removed** from `Business` |
| `Business::searchableAs()` | **removed** |
| `Business::toSearchableArray()` | **removed** |
| `Business::smartSearch()` | **removed** (was already dead — no callers) |
| Meilisearch `businesses` index | **deleted** |
| Meilisearch `branches` index | **deleted** (stale Branch-era remnant) |

115 lines removed from `app/Models/Business.php` with zero insertions.

**§27 safety check — legitimate non-public `Business::search()` use:** none exists.
The only two call sites were `SearchController::index()` and
`SearchController::autocomplete()`, both public discovery. `smartSearch()` had no
callers at all. No internal/admin search depended on Business being indexed.

---

## 4. SearchController changes — IMPLEMENTED

`Public/SearchController.php`:

| Before | After |
|---|---|
| `Business::search($q)` (results) | `Listing::search($q)` |
| `Business::search($q)` (autocomplete) | `Listing::search($q)` |
| `where('city_ids', …)` (multi-branch union) | `where('city_id', …)` — a Listing has 0/1 Location |
| `where('region_ids', …)` | `where('region_id', …)` |
| Inertia prop `businesses` | Inertia prop `listings` |
| `Business::search()->paginate(12)` | `Listing::search()->paginate(12)` through `ListingDirectoryResource` |

Filters applied on the Listing axis: `status`, `has_active_subscription`,
`hidden`, `category_ids`, `city_id`, `region_id`, `is_featured`, `is_open_now`.

---

## 5. DirectoryController changes — **NOT DONE (deferred)**

`DirectoryController` still builds its result set from `Business::query()`.
This is the database-browse path (not Meilisearch). See §14.

---

## 6. ExploreService changes — **NOT DONE (deferred)**

`ExploreService::fetchRowBusinesses()`, `countForCity()` and `countGlobal()`
still query Businesses. See §14.

---

## 7. CollectionService changes — **NOT DONE (deferred)**

`CollectionService::businessesQuery()`, `countBusinesses()` and
`countCategoryTotal()` still query Businesses. Their **category** filtering was
already converged to `withDiscoverableListingInCategory()` in Wave 1C, but the
returned entity is still a Business. See §14.

---

## 8. HomeController changes — **NOT DONE (deferred)**

`HomeController` featured / recent / popular sections still build from
`Business::with([...])` and serialise through `BusinessDirectoryResource`.
See §14.

---

## 9. Resource changes — IMPLEMENTED (new)

Added `app/Http/Resources/ListingDirectoryResource.php` — the canonical public
discovery result contract.

It emits a payload whose **key names mirror** the previous Business-shaped
contract (`name`, `slug`, `description`, `logo`, `logo_url`, `categories`,
`locations`, `branches`, `primary_location`, `coordinates`, `is_open_now`,
`status`, `rating`, `reviews_count`, `gallery_images_count`, `is_featured`,
`feature_flags`) so existing result cards keep rendering while the **entity** is
a Listing. It adds `type: "listing"` and `listing_type`.

Differences that follow from the domain invariant:

- `locations` is a **0/1** collection (a Listing has zero or one Location) rather
  than the previous multi-branch union.
- `open_branches_count` / `closed_branches_count` are derived from the single
  Location.
- Brand assets prefer listing-owned media, falling back to organization branding
  (Wave 1D §21).

`BusinessDirectoryResource` is **retained** because the deferred browse paths
(§5–§8) still use it. It is named in the remaining-debt list below.

---

## 10. Frontend result contract changes — IMPLEMENTED (minimal)

`resources/js/Pages/Public/Search/Index.vue`: prop `businesses` → `listings`,
loop variable `business` → `listing`, copy "businesses" → "listings".

The result ENTITY is now always a Listing. `BusinessCard.vue`'s prop is still
named `business` — **naming debt deferred to Wave 1D-6**, as permitted by the
1D-1 brief (§15). No half-complete component restructuring was attempted.

---

## 11. Meilisearch changes — IMPLEMENTED

`ConfigureMeilisearch` now targets the **`listings`** index
(`ConfigureMeilisearch::INDEX`):

- **filterable:** `status, type, is_featured, hidden, has_active_subscription,
  is_open_now, business_id, location_id, category_ids, city_id, region_id,
  country_id`
- **sortable:** `created_at, published_at`
- **searchable:** `name, description, categories_names, services_names, city,
  region, country, address`

Performed against the **real Meilisearch** (no `SCOUT_DRIVER=null`):

```text
php artisan meilisearch:configure        → listings index configured
php artisan scout:import "App\Models\Listing" → all Listing records imported
deleteIndex('businesses')                → deleted
deleteIndex('branches')                  → deleted
```

Index state after: `categories`, `listings` (Business and Branch indexes gone).

---

## 12. Tests — IMPLEMENTED

Added `tests/Feature/Search/SearchConvergenceTest.php` (5 tests):

- Business is not a searchable entity (no `Searchable` trait, no
  `searchableAs` / `toSearchableArray` / `smartSearch`)
- Listing is the canonical searchable entity (`searchableAs() === 'listings'`)
- the Listing search document exposes the canonical discovery fields
- a Listing associated with a Business keeps its organization context, and two
  Listings of one Business are two independent documents
- Business and Listing do not share a search index

```
full suite (real Meilisearch):  252 passed, 1 incomplete, 0 failed (700 assertions)
```

The single incomplete is `tests/Feature/ExampleTest.php`, Laravel's default stub
(pre-existing). No test infrastructure was modified; `phpunit.xml` untouched;
`SCOUT_DRIVER` left at `meilisearch`.

---

## 13. Remaining Business search references — classified

| Reference | Classification |
|---|---|
| `app/Models/Business.php` — no `Searchable`, no search methods | **DEAD** (removed) |
| Meilisearch `businesses` index | **DEAD** (deleted) |
| Meilisearch `branches` index | **DEAD** (deleted) |
| `app/Http/Resources/BusinessDirectoryResource.php` | **DEFERRED** — still used by the browse paths in §5–§8 |
| `DirectoryController`, `HomeController`, `ExploreService`, `CollectionService`, `CollectionController` querying `Business::query()` | **DEFERRED** — see §14 |
| `Category` still uses `Searchable` (a `categories` index exists) | **LEGITIMATE** — taxonomy lookup; nothing queries `Category::search()`, so it is not a competing discovery identity |
| `Business::query()` inside owner/admin organization management | **LEGITIMATE** — organization functionality, not public discovery |

**No active public Business search path remains.** The remaining Business
references are browse-path queries, not search entities.

---

## 14. Intentionally deferred — the rest of 1D-1

The 1D-1 brief also requires the **database-browse** paths to return Listings:

```text
DirectoryController  → Business::query()
HomeController       → Business::with([...])
ExploreService       → Business::query()
CollectionService    → Business::query()
CollectionController → BusinessDirectoryResource
```

These are **not** Meilisearch search — they are Eloquent browse queries feeding
`BusinessDirectoryResource` and the public cards. Converting them is a single
interlocking change: the base query, every filter, the sorted/related tiers, the
serialiser and the consuming pages must move together.

`ListingDirectoryResource` (§9) is the prepared adapter that makes this possible
without a frontend component rewrite, because it mirrors the existing contract
keys. The remaining work is:

1. `DirectoryController::index()` — base query → `Listing::query()`; remap
   `status`, `hidden_at`, organization search, category, country/region/city
   (via `location`), featured, open-now, verification and sorting.
2. `DirectoryController::relatedBusinesses()` tiers → Listing.
3. `DirectoryController::show($slug)` → Listing (overlaps **1D-2**'s `/listing/{slug}`).
4. `HomeController` featured / recent / popular → Listing.
5. `ExploreService` `fetchRowBusinesses` / `countForCity` / `countGlobal` → Listing.
6. `CollectionService` `businessesQuery` / `countBusinesses` / `countCategoryTotal` → Listing.
7. `CollectionController` → `ListingDirectoryResource`.
8. Tests for each of the above.

This was not attempted in this pass: doing it partially would leave the public
pages in an inconsistent state, which the 1D-1 brief explicitly forbids
("Do NOT create half-complete frontend restructuring").

---

## 15. Issues discovered

1. **The `listings` index had no Meilisearch settings.** Any attempt to filter
   it would have failed. `ConfigureMeilisearch` only ever configured
   `businesses`. Fixed in this slice.
2. **A stale `branches` Meilisearch index existed**, left over from the removed
   Branch architecture. Deleted.
3. **Two competing search identities existed** (audit §36D). Resolved: the
   Listing mechanism is canonical; the Business index is gone.
4. `Meilisearch\Client::getAllIndexes()` does not exist in the installed SDK
   version (it is `getIndexes()`); the temporary cleanup helper was corrected.
5. `LocationFoundationTest` already asserts Location is not a searchable
   identity; this slice adds the equivalent assertion for Business.
