# PHASE 11 — WAVE 1C: CATEGORY CONVERGENCE

Status: **implemented and verified.**
Companion documents: `PHASE_11_LEGACY_CONVERGENCE_AUDIT.md`,
`PHASE_9_LISTING_CORE_IMPLEMENTATION.md`, `PHASE_10_LOCATION_FOUNDATION.md`.

---

## 1. Previous category architecture — FACT

Before this wave there were **two** category pseudo-pivots:

```text
business_categories   (business_id ↔ category_id, is_primary)   ← organization-scoped
listing_categories    (listing_id  ↔ category_id, is_primary, sort_order)  ← listing-scoped
```

`Category` exposed **both** relationships:

```php
public function businesses()  // belongsToMany(Business::class, 'business_categories')
public function listings()    // belongsToMany(Listing::class,  'listing_categories')
```

`Business` owned a **stored** `categories()` relationship backed by
`business_categories`. The database-browse path (directory, collections,
location chips, home pages, admin filters) resolved categories through that
business-level pivot, while Meilisearch search already filtered on the
listing-level `category_ids` attribute.

---

## 2. Canonical architecture — DECISION

```text
Listing
   ↓
listing_categories
   ↓
Category
```

A Category describes **what a Listing is, does, offers, or can be discovered
for**. The Listing is the discovery owner:

```php
// Listing
public function categories() { return $this->belongsToMany(Category::class, 'listing_categories'); }

// Category
public function listings()   { return $this->belongsToMany(Listing::class, 'listing_categories'); }
```

`Business` is an organization/aggregate. It owns **no stored taxonomy**:

```text
Business → Listings → Categories      (derived on read)
```

---

## 3. Audit findings — FACT

- `business_categories` was a `business_id ↔ category_id` pivot carrying a
  single extra flag, `is_primary` (plus `timestamps`).
- **Every** consumer used it to classify or discover a **Business**: directory
  filtering (`whereHas('categories')`), related-business tiers, collection
  cross-links, location chips, home "popular categories" counts, admin category
  filters, business completeness scoring, the approved-business email, and
  `BusinessDirectoryResource`.
- No consumer used it for organization metadata that was *distinct* from
  discovery. There was no "brand-level" concept separate from "what is
  discoverable".
- Raw SQL in three services (`CollectionService` ×2, `SearchIntentParser` ×1,
  `LocationChipsController` ×2) joined `business_categories` directly.
- Category assignment was already duplicated in intent: Meilisearch already
  filtered Listings by `category_ids`, while the database browse path filtered
  Businesses.

### Did `business_categories` have a legitimate organization-level purpose?

**No.** It was a second, competing discovery taxonomy. Per the Wave 1C decision
rule it is legacy and was removed rather than preserved.

---

## 4. Removal safety — STATE A

Before dropping the pivot, the Wave 1C brief requires exactly one explicit
state:

| State | Condition | Action |
|---|---|---|
| **A** | 0 rows | remove |
| B | every row maps to exactly one Listing deterministically | migrate, verify, remove |
| C | any row maps ambiguously | STOP, preserve, report |

**Result: STATE A.** `business_categories` contained **0 rows** (`businesses`,
`listings`, `categories` and `listing_categories` were all empty too). No row
required migration, so no Listing attribution was inferred, guessed or
fabricated.

> There was no fourth state in which the migration "made the best guess". The
> drop migration throws if any row exists.

---

## 5. What was removed / refactored — IMPLEMENTED

### Database
- `business_categories` **dropped** by
  `2026_10_03_000002_drop_business_categories_table.php`, which counts rows
  first and throws rather than guessing an owner.

### Models
- `Business::categories()` (stored `belongsToMany` on `business_categories`)
  → **replaced** by a derived `getCategoriesAttribute()` accessor that returns
  the union of the organization's listings' categories.
- `Category::businesses()` → **removed**.
- `Category::getBusinessCountAttribute()` → **replaced** by
  `getListingsCountAttribute()`.
- `Business` gained `scopeWithDiscoverableListingInCategory()` — the canonical
  way to constrain organizations by category.
- `Business::$appends` gained `categories` so existing Inertia payloads keep
  exposing `business.categories`.
- `Listing::categories()` / `Category::listings()` were already canonical and
  are unchanged.

### Application
- **Directory browse** (`DirectoryController`): `whereHas('categories', …)` on
  Business → `withDiscoverableListingInCategory()`, in both the main filter and
  the related-business tiers.
- **CollectionService**: both raw cross-link queries and the
  `businessesQuery`/`countBusinesses`/`countCategoryTotal` filters now resolve
  through `listing_categories` → `listings` → `locations`.
- **ExploreService**: all three category filters now use
  `withDiscoverableListingInCategory()`.
- **LocationChipsController**: both chip queries now resolve through
  `listing_categories`.
- **SearchIntentParser::topCitiesForCategory()**: now counts Listings and takes
  the city from the Listing's own Location (`listings.location_id`).
- **HomeController** and **Admin/DashboardController**: category population is
  counted with `withCount('listings')` instead of `withCount('businesses')`.
- **SearchController**: category suggestions already used Listings; the
  response key `businesses_count` → `listings_count`.
- **Admin/BusinessController**: eager load and category filter moved to the
  Listing axis.
- **Owner/BusinessController**: category attach/sync now targets the
  organization's primary Listing (the same deterministic bridge Wave 1B
  established for services, media and contacts).
- **BusinessCompletenessService / ListingMigrationAnalyzer**: `categories`
  removed from `loadMissing` (it is no longer a relation);
  `ListingMigrationAnalyzer` no longer reports `business.categories` as
  business-level data.
- **CacheHelper**: the dead `business_categories_{id}` cache key was removed.
- All eager loads of the former `categories` relation were removed — leaving one
  would throw `RelationNotFoundException`.

### Seeders / tests / frontend
- `BusinessSeeder` (×3) and `DirectoryTestSeeder` now attach categories to the
  primary Listing and skip when the organization has none.
- `SearchSuggestionsTest` now builds a published Listing carrying the category.
- Frontend: category counts renamed `businesses_count` → `listings_count` in
  `Home.vue`, `SearchBar.vue` and `Admin/Dashboard.vue`; the Admin categories
  page and the public categories page no longer say "business categor(y|ies)".

---

## 6. Final category ownership — DECISION

```text
Listing  ──owns──▶  listing_categories  ──▶  Category
```

This is the **only** authoritative discovery taxonomy. No stored
organization-level taxonomy exists.

---

## 7. Derived organization categories — DECISION

`Business::categories` is computed, never stored:

```php
$listings = $this->listings()->with('categories')->get();

return $listings->pluck('categories')->flatten()->unique('id')->values();
```

- The `pivot` object comes from `listing_categories`, so consumers reading
  `pivot.is_primary` keep working.
- **Two listings under one Business keep independent categories.** There is no
  inheritance and no fan-out.
- Cost: one query per organization serialization. Callers that serialize many
  organizations may want an eager-load optimisation later; correctness was
  prioritised here.

> **OPEN (Wave 1D):** owner-facing category assignment currently targets the
> organization's *primary* Listing, matching the Wave 1B bridge for
> services/media/contacts. Genuine multi-Listing category addressing in the
> owner UI belongs to the Business-as-Listing collapse in Wave 1D.

---

## 8. Search implications — FACT

No search redesign. Meilisearch is untouched: no new index, no ranking change,
no autocomplete change, no new entity type.

Meilisearch search was **already** Listing-centric (`Listing::searchableAs() ===
'listings'`, filtering on the Listing's `category_ids`). This wave removed the
*inconsistency* where the database-browse path resolved categories through
Business while search resolved them through Listings. Both now use the Listing
axis.

---

## 9. Migration details — IMPLEMENTED

```text
2026_10_03_000002_drop_business_categories_table.php
  up():   if the pivot has any row → throw (STATE C); else DROP
  down(): recreate the original shape (id, business_id, category_id,
          is_primary, timestamps, unique(business_id, category_id),
          index(is_primary))
```

The historical create migration (`2026_08_31_000008`) and the index migration
(`2026_09_02_035553`) were deliberately left untouched — migration history is
append-only, and a fresh `migrate` correctly creates then drops the pivot.

---

## 10. Tests — IMPLEMENTED

Added `tests/Feature/Listing/CategoryConvergenceTest.php`:

- `listing_categories` is canonical; `business_categories` does not exist
- a Listing can have multiple Categories
- a Category can have multiple Listings
- **two Listings of the same Business can have different Categories**
- Business categories are derived from its Listings, not a stored pivot
- organization matching by category resolves through its Listings
- no production code references `business_categories` (file scan, comment lines
  excluded so documentation of the removal is allowed)

Updated `tests/Feature/Search/SearchSuggestionsTest.php` to build the category
qualification through Listings.

```
targeted (Listing + Search + Unit):  174 passed (473 assertions)
full suite:                          247 passed, 1 incomplete, 0 failed (669 assertions)
```

Run with the real Meilisearch server (`SCOUT_DRIVER=meilisearch`), no
`SCOUT_DRIVER=null`, `phpunit.xml` unmodified. The single incomplete test is
`tests/Feature/ExampleTest.php`, Laravel's default stub (pre-existing).

---

## 11. Remaining category-related legacy references — classified

| Location | Classification |
|---|---|
| `app/Models/Business.php`, `app/Models/Category.php` (doc comments) | HISTORICAL DOCUMENTATION — explain that the pivot is gone |
| `database/migrations/2026_08_31_000008_create_business_categories_table.php` | HISTORICAL — migration history |
| `database/migrations/2026_09_02_035553_add_optimization_indexes.php` | HISTORICAL — migration history |
| `database/migrations/2026_10_03_000002_drop_business_categories_table.php` | LEGITIMATE — the removal itself |
| `tests/Feature/Listing/CategoryConvergenceTest.php` | LEGITIMATE — the regression guard |
| `docs/PHASE_1..9_*.md` | HISTORICAL DOCUMENTATION — describe the pre-1C architecture |
| `CollectionService::MIN_BUSINESSES` (const name) | STALE NAME, not ownership — threshold constant; rename deferred (not a taxonomy reference) |

**No executable application reference to `business_categories` remains.**

---

## 12. Out of scope (explicitly NOT done) — FACT

No Business → Organization rename. No Business-as-Listing collapse. No Location
redesign. No Event domain. No Service Area. No GIS/radius search. No search
architecture redesign. No URL redesign. No full frontend terminology
convergence. No pricing or subscription redesign. No team/collaboration system.
No new taxonomy system. No category hierarchy changes.

> **NOTE:** `public/build/` assets were **not** rebuilt in this wave. The Vue
> source changes above are committed but not yet compiled into the served
> bundle; a frontend build is required before they are live.
