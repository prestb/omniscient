# PHASE 11 — WAVE 1D AUDIT
## Business-as-Listing Collapse: repository audit and classification

Status: **audit complete. No code modified yet** (per the Wave 1D brief: audit
before modifying).

Method: schema inspection against the live `omniscient` database, plus
repository-wide search of routes, controllers, middleware, requests, resources,
services, commands, models, seeders, tests, config and Vue source.

---

## 0. Summary

| # | Finding | Wave 1D section | Severity |
|---|---|---|---|
| 1 | `businesses.listing_type` **does not exist** | §4 | ✅ already satisfied |
| 2 | No `branches` table, no `Branch` model/controller/policy/route | §29 | ✅ already satisfied |
| 3 | `Business` is still a **competing Meilisearch entity** | §10, §34 | **HIGH** |
| 4 | `SearchController` queries `Business::search()` | §10, §11 | **HIGH** |
| 5 | `DirectoryController` returns **Businesses**, not Listings | §10 | **HIGH** |
| 6 | Public canonical URL is `/business/{slug}` — no `/listing/{slug}` | §12 | **HIGH** |
| 7 | **No Listing creation lifecycle exists** — Listings are never created by app code | §5, §26 | **HIGH** |
| 8 | 18 `primaryListing()` bridge call sites | §6 | **HIGH** |
| 9 | Owner child routes are `{business}/...` scoped (services, contacts, images, locations, hours) | §6 | **HIGH** |
| 10 | `PlanEnforcementService` enforces the **listing** quota against **Business** rows | §8 | **MEDIUM** |
| 11 | 32 frontend `max_businesses` / `max_branches` references | §25 | **MEDIUM** |
| 12 | Branch terminology in executable code (names, labels, prop aliases) | §29 | **MEDIUM** |
| 13 | `ListingIdentity` / `ListingOwnership` / `DataOwnership` are Branch-era decision records | §29, §38 | **LOW** (metadata) |

**No hard-stop condition was triggered.** See §14.

---

## 1. Schema — FACT

`businesses` (live):

```
id, owner_id, name, slug, description, logo, cover_image, email, website,
status, has_active_subscription, is_featured, reviewed_by, reviewed_at,
submitted_at, published_at, created_at, updated_at, deleted_at,
average_rating, total_reviews, hidden_at
```

`listings` (live):

```
id, owner_id NOT NULL, business_id NULL, location_id NULL,
type varchar(32) NOT NULL DEFAULT 'business', name, slug UNIQUE, description,
status, is_featured, published_at, hidden_at, created_at, updated_at, deleted_at
```

`SHOW TABLES LIKE '%branch%'` → **empty**. `information_schema` confirms
`businesses.listing_type` has **0** columns.

### Schema classification

| Object | Classification | Note |
|---|---|---|
| `listings` | KEEP | already canonical |
| `businesses` | KEEP | organization identity only |
| `businesses.owner_id/name/description/logo/cover_image/email/website` | KEEP | organisation data |
| `businesses.status/published_at/hidden_at/is_featured/reviewed_*` | **REVIEW** | organization lifecycle — see §6.3 |
| `businesses.average_rating/total_reviews` | **REFACTOR** | Business-level rating aggregate; §16 says derive from Listings |
| `businesses.has_active_subscription` | **REPLACE** | duplicates account-scoped subscription state (§7) |
| `businesses.listing_type` | ✅ ABSENT | dropped by `2026_10_01_000002` |
| `branches` | ✅ ABSENT | replaced by `locations` (Phase 10) |

**Schema change required:** yes, but only for §6.3 / §16 — not for
`listing_type` or Branch.

---

## 2. `businesses.listing_type` — §4

| Reference | Behaviour | Classification | Replacement | Schema change |
|---|---|---|---|---|
| `database/migrations/2026_10_01_000002_…php:184-185` | drops the column if present | HISTORICAL | — | already applied |
| `tests/Feature/Listing/ListingSchemaTest.php:76` | asserts the column is absent | KEEP | — | no |
| `businesses.listing_type` (live DB) | absent | — | — | none required |

**Result: already removed. No action beyond retaining the regression assertion
that `listings.type` is the single source of truth.**

---

## 3. Entitlements and quotas — §7, §8, §9

| File | Line | Current behaviour | Classification | Intended replacement |
|---|---|---|---|---|
| `app/Models/Plan.php` | 180-187 | maps `listings`→`max_listings`, `locations`→`max_locations` | KEEP | — |
| `app/Support/Entitlement.php` | 55, 63 | `CREATE_LISTING='listings'`, `CREATE_LOCATION='locations'` | KEEP | — |
| `app/Support/Entitlement.php` | 33, 60 | docblock mentions `CREATE_BUSINESS`/`CREATE_BRANCH`/"branches" | REFACTOR | reword comment |
| `app/Traits/HasPlanFeatures.php` | 72-73 | `getCurrentUsage('listings')` → `Listing::countFor()` | KEEP | canonical |
| `app/Traits/HasPlanFeatures.php` | 81-91 | `getLocationsCount()` counts via `businesses()->pluck('id')` | **REPLACE** | count Locations through the account's **listings'** `location_id` (+ org-less locations) |
| `app/Services/PlanEnforcementService.php` | 67-99, 132-160 | **listing quota enforced against `Business` rows**; locations enforced per Business | **REPLACE** | enforce against `Listing` rows; locations per account |
| `app/Services/PlanEnforcementService.php` | 25, 75, 99, 132, 288 | `enforceBranches()`, `restoreAllBranches()` names | REFACTOR | rename to Location |
| `app/Models/Business.php` | 123-159 | `canCreateBusiness()` counts Businesses using `max_listings` | **DELETE** | Listing quota lives on the account |
| `app/Models/Business.php` | 198-249 | `canCreateLocation()`, `getRemainingLocationSlots()` using `$maxBranches`/`$branchCount` | REFACTOR | rename locals to Location vocabulary |
| `app/Http/Controllers/Admin/BusinessController.php` | 388 | `max_listings` read for an org count | **REPLACE** | Listing count |
| `app/Helpers/SubscriptionHelper.php` | 32-38, 46-63 | already `max_listings`/`max_locations`; `hasReachedLimit()` has no `counts['locations']` | REFACTOR | add locations count or delete dead method |
| `resources/js/composables/usePlan.js` | 19-20, 34-35 | reads `plan.max_businesses` / `max_branches` (columns no longer exist) | **REPLACE** | `max_listings` / `max_locations` |

**Quota semantics after 1D must be:**

```text
listing quota  = Listing::where('owner_id', $userId)->count()
location quota = Locations reachable from the account
```

**Schema change required:** none (columns already renamed in Wave 1A).

---

## 4. Primary-Listing bridges — §6

`Business::primaryListing()` — `app/Models/Business.php:354`. Wave 1C documented
it as a transitional bridge. **18 call sites:**

| File | Line | Used for | Classification | Intended replacement |
|---|---|---|---|---|
| `app/Models/Business.php` | 354 | definition | **DELETE** | — |
| `app/Http/Controllers/Owner/ServiceController.php` | 43 | service target | REPLACE | Listing-scoped route |
| `app/Http/Controllers/Owner/ImageController.php` | 59 | media target | REPLACE | Listing-scoped route |
| `app/Http/Controllers/Owner/ContactController.php` | 35 | contact target | REPLACE | Listing-scoped route |
| `app/Http/Controllers/Owner/BusinessController.php` | 128, 245 | category target | REPLACE | Listing-scoped category assignment |
| `app/Http/Controllers/Owner/AnalyticsController.php` | 120, 131 | analytics target | REPLACE | Listing context |
| `app/Http/Controllers/Api/AnalyticsController.php` | 21, 50 | analytics target | REPLACE | Listing context |
| `app/Http/Middleware/TrackBusinessView.php` | 27 | analytics target | REPLACE | resolve Listing from the route |
| `database/seeders/BusinessSeeder.php` | 38, 116, 151 | category seed | REPLACE | seed Listings explicitly |
| `database/seeders/ServiceSeeder.php` | 17 | service seed | REPLACE | seed Listings explicitly |
| `database/seeders/ContactSeeder.php` | 17 | contact seed | REPLACE | seed Listings explicitly |
| `database/seeders/DirectoryTestSeeder.php` | 72 | category seed | REPLACE | seed Listings explicitly |
| `tests/Feature/Images/ImagePipelineTest.php` | 22 | comment | REFACTOR | update after route change |

**Also `$business->listings()->first()` style fallbacks:** `TrackBusinessView`
already fixed in 1B; no others found.

**Schema change required:** none.

---

## 5. Routes — §6, §12, §26

### 5.1 Owner child routes are Business-scoped

`routes/web.php:256-323`:

```
POST   /owner/businesses/{business}/services
POST   /owner/businesses/{business}/contacts
POST   /owner/businesses/{business}/images
POST   /owner/businesses/{business}/locations
POST   /owner/businesses/{business}/locations/{location}/hours
```

| Route group | Classification | Intended replacement |
|---|---|---|
| `{business}/services` | **REPLACE** | `{listing}/services` |
| `{business}/contacts` | **REPLACE** | `{listing}/contacts` |
| `{business}/images` | **REPLACE** | `{listing}/images` |
| `{business}/locations` | **REFACTOR** | `{listing}/location` (0/1 invariant) |
| `{business}/locations/{location}/hours` | REFACTOR | hours belong to the Location |

### 5.2 Public URL

`routes/web.php:89-90` → `GET /business/{slug}` (`business.show`) →
`DirectoryController@show`, which resolves a **Business**.

| Route | Classification | Intended replacement |
|---|---|---|
| `GET /business/{slug}` → Business | **REPLACE** | `/listing/{slug}` canonical; `/business/{slug}` repurposed to the organization page |

Consumers to update: `Owner/ReviewController:89`,
`Admin/BusinessController:304,399`, `Admin/ReviewController:71,99`,
`TrackBusinessView:17`, `resources/js/ziggy.js`,
`tests/Feature/Listing/IdentifierContinuityTest.php:23-26`.

**Schema change required:** none.

---

## 6. Business-as-Listing assumptions — §2, §10, §13, §14, §26

### 6.1 Search — HIGH

| File | Line | Current behaviour | Classification | Replacement |
|---|---|---|---|---|
| `app/Models/Business.php` | 829 | `searchableAs()` — Business is indexed | **REPLACE** | Business must not compete with Listing in search |
| `app/Models/Business.php` | 770 | `toSearchableArray()` builds a Business document | **REPLACE** | remove or restrict to contextual organisation metadata |
| `app/Models/Listing.php` | 296 | `searchableAs()==='listings'` | KEEP | canonical |
| `app/Models/Category.php` | 129 | Category is searchable | KEEP | taxonomy lookup, not a result identity |
| `app/Http/Controllers/Public/SearchController.php` | 43 | `Business::search($q)` for main results | **REPLACE** | `Listing::search($q)` |
| `app/Http/Controllers/Public/SearchController.php` | 108 | `Business::search($q)` for autocomplete | **REPLACE** | `Listing::search($q)` |
| `app/Http/Controllers/Public/SearchController.php` | 50-51 | filters on `category_ids` (already a Listing attribute) | REFACTOR | align with Listing result shape |
| `app/Http/Controllers/Public/SearchController.php` | 116-124 | autocomplete returns `business.id/name/slug` | **REPLACE** | Listing identity (§11) |

### 6.2 Database browse

| File | Line | Current behaviour | Classification | Replacement |
|---|---|---|---|---|
| `app/Http/Controllers/Public/DirectoryController.php` | 67-390 | `Business::query()` paginated as the directory result | **REPLACE** | Listing results |
| `app/Http/Controllers/Public/DirectoryController.php` | 381 | `'category' => $business->categories->first()` | REFACTOR | derived (already works) |
| `app/Http/Controllers/Public/DirectoryController.php` | 704-780 | related results built from Businesses | **REPLACE** | Listing results |
| `app/Http/Resources/BusinessDirectoryResource.php` | whole file | serialises a Business as the result card | **REPLACE** | Listing resource |
| `app/Http/Controllers/Public/HomeController.php` | 18-110 | featured/recent/popular built from Businesses | **REPLACE** | Listings |
| `app/Services/ExploreService.php` | 80-204 | explore rows built from Businesses | **REPLACE** | Listings |
| `app/Services/CollectionService.php` | 102-152 | `businessesQuery` / `countBusinesses` | **REPLACE** | Listings |
| `app/Services/SearchIntentParser.php` | whole | intent → categories/cities | REFACTOR | verify Listing-centric |

### 6.3 Organization lifecycle columns

| Column | Classification | Note |
|---|---|---|
| `businesses.status/published_at/hidden_at/is_featured` | **REVIEW** | organization lifecycle vs Listing lifecycle — decide whether an org has its own visibility or derives it from Listings |
| `businesses.reviewed_by/reviewed_at/submitted_at` | REFACTOR | approval of the organization vs of Listings |
| `businesses.average_rating/total_reviews` | **REPLACE** | §16: derive from Listings' Reviews |

---

## 7. Branch remnants — §29

**No `Branch` model, controller, policy, request, service, route or
`branch_id` column exists.** What remains is naming only:

| File | Line | Content | Classification | Replacement |
|---|---|---|---|---|
| `app/Helpers/CacheHelper.php` | 45 | `business_branches_` cache key (never written) | **DELETE** | — |
| `app/Http/Resources/BusinessDirectoryResource.php` | 12-70 | `$branches`, `'branches'` alias, `open_branches_count`, `closed_branches_count` | **REPLACE** | `locations` only |
| `app/Http/Controllers/Owner/LocationController.php` | 47 | `'branches' => $locations` back-compat prop | **DELETE** | — |
| `app/Http/Controllers/Owner/BusinessController.php` | 186, 191-192 | `$branchesCount`, `'branchesCount'` | REFACTOR | Location naming |
| `app/Http/Controllers/Owner/DashboardController.php` | 254, 305, 310 | `'branchesCount'`, `$recentBranches` | REFACTOR | Location naming |
| `app/Services/PlanEnforcementService.php` | 25, 75, 99, 132, 288 | `enforceBranches`, `restoreAllBranches` | REFACTOR | Location naming |
| `app/Services/BusinessCompletenessService.php` | 41, 47 | labels "Branch location", "Branch address & phone" | **REPLACE** | "Location …" |
| `app/Services/ListingMigrationAnalyzer.php` | 65-69, 157, 177 | `$branches`, `$branch`, "branch_id" text | REFACTOR | Location naming |
| `app/Http/Controllers/Public/DirectoryController.php` | 600, 676 | private `isBranchOpen()`, `getBranchAddress()` | REFACTOR | rename |
| `app/Http/Controllers/Public/RedemptionController.php` | 83 | `$branches` local | REFACTOR | rename |
| `app/Http/Controllers/Public/SearchController.php` | 55 | comment "branches" | REFACTOR | comment |
| `app/Console/Commands/ListingMigrationDryRun.php` | 25, 51 | "Business/Branches" text | REFACTOR | text |
| `app/Models/Business.php` | 216-249 | `$maxBranches`, `$branchCount` locals | REFACTOR | rename |
| `resources/js/Components/Public/BranchesSection.vue` | filename | component name | REFACTOR | rename to LocationsSection |
| `resources/js/Components/Public/BusinessCard.vue` | 141-146 | `hasOpenBranch`, `branches` fallback | REFACTOR | locations only |
| `resources/js/Components/Public/RelatedBusinesses.vue` | 133 | `biz.branches` fallback | REFACTOR | locations only |
| `resources/js/composables/useStatusBadge.js` | 21 | "Branch status" comment | REFACTOR | comment |
| `resources/js/Pages/Admin/Locations/Areas.vue` | 323 | "branches" in copy | REFACTOR | "locations" |
| `resources/js/Pages/Public/Pricing.vue` | 335 | FAQ copy "their own branches" | REFACTOR | copy |
| `app/Support/ListingIdentity.php` | throughout | Branch-era decision record | HISTORICAL | keep as documentation |
| `app/Support/ListingOwnership.php` | 63-192 | Branch/branch_id rules | HISTORICAL | keep as documentation |
| `docs/PHASE_1..10_*.md` | — | historical | HISTORICAL | keep |

**Schema change required:** none.

---

## 8. Child ownership — §15, §16, §17, §18, §19, §20, §21, §22, §23

Verified in earlier waves; this audit re-checks each table's authoritative FK:

| Table | Authoritative FK | Status | Wave 1D action |
|---|---|---|---|
| `listing_services` | `listing_id` NOT NULL → listings | ✅ | none |
| `listing_images` | `listing_id` NOT NULL → listings | ✅ | none |
| `listing_contacts` | `listing_id` NOT NULL → listings | ✅ | none |
| `listing_analytics` | `listing_id` NOT NULL → listings | ✅ | none |
| `listing_categories` | `listing_id` NOT NULL → listings | ✅ | none |
| `reviews` | `listing_id` **nullable** + `business_id` present | ⚠️ **REFACTOR** | audit whether NULL is legitimate; §16 |
| `favorites` | `listing_id` **nullable** + `business_id` present | ⚠️ **REFACTOR** | §17 |
| `leads` | `listing_id` **nullable** + `business_id` present | ⚠️ **REFACTOR** | §18 |
| `coupons` | `listing_id` **nullable** + `business_id` present | ⚠️ **REPLACE** | §19 — remove nullable Business-wide semantics |

**Remaining nullable `listing_id` owners to resolve in 1D:**
`reviews`, `favorites`, `leads`, `coupons`. Each currently permits NULL, which
Wave 1B's own rule treats as "legacy compatibility only".

**Schema change required:** likely yes — drop `business_id` and make
`listing_id` NOT NULL on these four if no legitimate NULL workflow exists
(audit per table, mirroring the Wave 1B correction).

---

## 9. Frontend — §24, §25

| File | Line | Content | Classification | Replacement |
|---|---|---|---|---|
| `resources/js/composables/usePlan.js` | 19-20, 34-35 | `max_businesses`, `max_branches` | **REPLACE** | `max_listings`, `max_locations` |
| `resources/js/Pages/Admin/Plans/Create.vue` | 352-353, 381-382 | form keys `max_businesses`/`max_branches` | **REPLACE** | `max_listings`/`max_locations` |
| `resources/js/Pages/Admin/Plans/Edit.vue` | 394-395, 423-424 | same | **REPLACE** | same |
| `resources/js/Pages/Admin/Plans/Index.vue` | 151-169 | displays `plan.max_branches`/`max_businesses` | **REPLACE** | listings/locations |
| `resources/js/Pages/Owner/Subscription/Index.vue` | 120, 126 | `plan.max_branches`/`max_businesses` | **REPLACE** | listings/locations |
| `resources/js/Pages/Owner/Subscription/Payment.vue` | 99, 104 | same | **REPLACE** | same |
| `resources/js/Pages/Owner/Subscription/Renew.vue` | 214-224 | same | **REPLACE** | same |
| `resources/js/Pages/Owner/Businesses/Index.vue` | 33, 77, 126, 152, 436 | `subscription.max_businesses` | **REPLACE** | listings + vocabulary |
| `resources/js/Pages/Owner/Businesses/Create.vue` | whole | "Create Business" flow | **REPLACE** | create Listing (type selector) |
| `resources/js/Pages/Admin/Users/Index.vue` | 252 | `user.businesses_count` | KEEP | organization count |
| `resources/js/Pages/Admin/Businesses/Show.vue` | 266, 601 | `owner.businesses_count` | KEEP | organization count |
| `resources/js/Components/Public/BusinessCard.vue` | 269-276 | reads `business.categories` | KEEP | derived, already correct |
| `resources/js/Pages/Owner/Businesses/Edit.vue` | 61, 271, 505, 560, 569 | category assignment on a Business | REFACTOR | Listing-scoped |

**KEEP:** `businesses_count` where it genuinely counts organizations.

---

## 10. Support metadata classes — §38

| File | Classification | Note |
|---|---|---|
| `app/Support/ListingIdentity.php` | HISTORICAL | Phase 5/6 decision record, entirely Branch-era; no executable effect |
| `app/Support/ListingOwnership.php` | HISTORICAL | Phase 5 rules; `evidence` strings partly updated in 1B/1C |
| `app/Support/DataOwnership.php` | **REFACTOR** | still classifies `business.identity`, `business.slug`; consumed by tests |
| `app/Support/ListingState.php`, `ListingType.php` | KEEP | current |
| `app/Services/ListingMigrationAnalyzer.php`, `MigrationReadinessService.php`, `ListingMigrationDryRun.php` | **DEAD** | diagnostic tooling for a migration that is now complete |

`ListingMigrationAnalyzer` / `MigrationReadinessService` / `ListingMigrationDryRun`
analyse a Business→Listing split that Wave 11 has already performed. They are
candidates for **DELETE** once their tests are retired.

---

## 11. Hard-stop conditions — §36

| Condition | Triggered? | Evidence |
|---|---|---|
| **A. Ambiguous ownership** | **No** | All tables empty (0 rows). No record needs attribution. |
| **B. Legitimate Business-level data** | **No, but REVIEW** | `businesses.name/description/logo/cover_image/email/website/owner_id` are genuine organization data — KEEP. `average_rating`/`total_reviews` are aggregates that §16 says must be derived. |
| **C. Unexpected production data** | **No** | `businesses`, `listings`, `categories`, `listing_categories`, `locations` all **0 rows**. Dev database is disposable as stated. |
| **D. Multiple competing Listing identities** | **YES — reported** | Two competing mechanisms represent the same discoverable entity: the **Meilisearch `businesses` index** (via `Business::searchableAs()`) and the **`listings` index**. Plus `/business/{slug}` vs the (nonexistent) `/listing/{slug}`. Per §36D these are documented here before one is chosen: **the Listing mechanism is chosen.** |
| **E. Unsafe public URL migration** | **REVIEW** | `/business/{slug}` is a public contract referenced by 6 PHP call sites, Ziggy, and 2 tests. No production traffic exists, so it is migratable — but the repurposing of `/business/{slug}` to an organization page must be deliberate, not silent. Redirect strategy required. |

---

## 12. Scope reality

Wave 1D is materially larger than Waves 1A–1C combined. It requires:

1. A **new Listing creation lifecycle** (controller, routes, requests, Vue pages, type selector) — new feature surface.
2. **Re-scoping 5 owner child route groups** from `{business}` to `{listing}`, reworking 5 controllers and ~10 Vue pages.
3. **Rewriting search and browse** so every result is a Listing (SearchController, DirectoryController, HomeController, ExploreService, CollectionService, a new Listing resource, and the result cards).
4. A **URL migration** to `/listing/{slug}` plus repurposing `/business/{slug}`.
5. **32 frontend plan references** plus vocabulary convergence.
6. Branch naming cleanup across ~19 files.
7. **Frontend build** and **Meilisearch reindex**.
8. New/updated tests across identity, types, quotas, search, autocomplete, ownership, child ownership, aggregation and URL.

Recommended execution order (each a verified slice, not one pass):

```text
1D-1  Business search de-indexing + search returns Listings      (HIGH)
1D-2  Listing creation lifecycle + /listing/{slug}               (HIGH)
1D-3  primaryListing bridge removal + Listing-scoped child routes(HIGH)
1D-4  Quota authority: Listing count everywhere                  (MEDIUM)
1D-5  reviews/favorites/leads/coupons ownership convergence       (MEDIUM)
1D-6  Branch naming removal + frontend plan keys + build          (MEDIUM)
1D-7  Dead migration-tooling removal + docs                       (LOW)
```

---

## 13. Classification totals

| Classification | Count |
|---|---|
| KEEP | 14 |
| REFACTOR | 28 |
| REPLACE | 21 |
| DELETE | 5 |
| HISTORICAL | 4 |

---

## 14. Audit conclusion

- `businesses.listing_type` and the entire Branch architecture are **already
  gone** — Wave 1D §4 and §29 are satisfied at the schema/model/route level;
  only naming remnants remain.
- The **real** remaining work is the search/browse collapse (Business is still a
  competing Meilisearch entity and the primary browse result), the missing
  Listing creation lifecycle, the 18 `primaryListing()` bridges with their
  Business-scoped routes, and the four nullable-`listing_id` child tables.
- No destructive blocker exists: the database is empty and disposable.

**This document records the audit only. No code has been modified.**
