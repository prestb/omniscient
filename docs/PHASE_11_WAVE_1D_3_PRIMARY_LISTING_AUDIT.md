# PHASE 11 — WAVE 1D-3 AUDIT
## Primary-Listing Bridge Elimination — audit and classification

Baseline: Phase 11 / Wave 1D-2, commit `6d8002b`, branch `phase-11-resume`.

**This is an audit only. No application code, routes, schemas, controllers, Vue
components or tests were modified.**

---

## 0. Headline numbers

| Measure | Count |
|---|---|
| Total `primaryListing` text occurrences in the repo | **19** |
| of which: method definition | 1 |
| of which: comments (not active code) | 2 |
| **Active `primaryListing()` call sites** | **16** |
| Additional equivalent "first Listing" assumptions | **1** (`Public/LeadController:47`) |
| Business-scoped owner route groups operating on Listing-owned data | **4** (+1 hours group) |
| Policies authorizing Listing operations through Business ownership | **0 formal policies; 21 inline `owner_id` checks** |

---

## 1. The bridge itself — FACT

`app/Models/Business.php:353`

```php
public function primaryListing(): ?Listing
{
    return $this->listings()
        ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
        ->orderBy('id')
        ->first();
}
```

It deterministically picks **one** Listing — oldest published, else oldest — and
presents it as if it were the organization's canonical Listing. Wave 1C/1D-2
documented it as an explicitly transitional bridge. A Business may own 0, 1 or
many Listings, so this is only unambiguous when the count is exactly 1.

**Not a call site** (excluded):
- `app/Models/Business.php:353` — the definition
- `app/Http/Controllers/Owner/ListingController.php:20` — a docblock stating the new lifecycle does *not* use it
- `tests/Feature/Images/ImagePipelineTest.php:22` — a docblock

---

## 2. MASTER TABLE — every active `primaryListing()` occurrence

| # | File | Method / Component | Route / Context | Current Purpose | Entity Actually Needed | Class | Recommended Target | Why |
|---|---|---|---|---|---|---|---|---|
| 1 | `app/Http/Controllers/Owner/ServiceController.php:43` | `store()` | `POST /owner/businesses/{business}/services` (owner) | Resolve the Listing that will own the new service | **Listing** | **E** | `POST /owner/listings/{listing}/services` | A Business may own many Listings; each offers its own services (Wave 1D-1 proved this). The route supplies no Listing, so the app silently picks one. |
| 2 | `app/Http/Controllers/Owner/ContactController.php:35` | `store()` | `POST /owner/businesses/{business}/contacts` (owner) | Resolve the Listing that will own the new contact | **Listing** | **E** | `POST /owner/listings/{listing}/contacts` | Discoverable contact is Listing-owned (`listing_contacts`). No Listing in context. |
| 3 | `app/Http/Controllers/Owner/ImageController.php:59` | `store()` | `POST /owner/businesses/{business}/images` (owner) | Resolve the Listing that will own the new media | **Listing** | **E** | `POST /owner/listings/{listing}/images` | Media is Listing-owned (`listing_images`). No Listing in context. |
| 4 | `app/Http/Controllers/Owner/BusinessController.php:128` | `store()` | `POST /owner/businesses` (owner) | Attach the chosen category to the Listing created for a brand-new Business | **Listing** | **D** | Remove; category assignment belongs to the Listing lifecycle | **Dead in practice:** the Business was created on the previous line and the application never creates a Listing, so `primaryListing()` always returns `null` and the block never executes. Evidence: no `Listing::create` exists in `app/` (verified 1D-2). |
| 5 | `app/Http/Controllers/Owner/BusinessController.php:245` | `update()` | `PUT /owner/businesses/{business}` (owner) | Sync the submitted categories onto the Listing | **Listing(s)** | **E** | Category assignment on `/owner/listings/{listing}/edit` | Multi-Listing businesses cannot express "which Listing's categories". Silent single-Listing selection. |
| 6 | `app/Http/Controllers/Owner/AnalyticsController.php:120` | `trackView()` | owner analytics endpoint (owner) | Attribute a tracked view to a Listing | **Listing** | **E** | Listing-scoped tracking route | Analytics are Listing-owned (`listing_analytics`). Business-scoped input forces an arbitrary choice. |
| 7 | `app/Http/Controllers/Owner/AnalyticsController.php:131` | `trackClick()` | owner analytics endpoint (owner) | Attribute a tracked click to a Listing | **Listing** | **E** | Listing-scoped tracking route | Same as #6. |
| 8 | `app/Http/Controllers/Api/AnalyticsController.php:21` | `trackView($businessId)` | public API (API) | Attribute a public view to a Listing | **Listing** | **E** | API taking `listing` id | The endpoint is keyed by `businessId`; with many Listings the attribution is arbitrary and the analytics are wrong. |
| 9 | `app/Http/Controllers/Api/AnalyticsController.php:50` | `trackClick(...)` | public API (API) | Attribute a public click to a Listing | **Listing** | **E** | API taking `listing` id | Same as #8. |
| 10 | `app/Http/Middleware/TrackBusinessView.php:27` | `handle()` | `GET /business/{slug}` (`track.business`) | Attribute a profile view to a Listing | **Listing** | **E** | Resolve the Listing from the route, or move tracking to `/listing/{slug}` | The middleware runs on the **organization** page but records Listing-scoped analytics for an arbitrarily chosen Listing. Symptomatic of Business-as-Listing. |
| 11 | `database/seeders/BusinessSeeder.php:38` | `run()` | `db:seed` | Seed ABC Pharmacy categories | **Listing** | **A** | Create the Listing explicitly, then attach | A seeder needs no route/UX redesign — it simply must operate on an explicit Listing instead of guessing one. |
| 12 | `database/seeders/BusinessSeeder.php:116` | `run()` | `db:seed` | Seed hospital categories | **Listing** | **A** | same as #11 | Same as #11. |
| 13 | `database/seeders/BusinessSeeder.php:151` | `run()` | `db:seed` | Seed restaurant categories | **Listing** | **A** | same as #11 | Same as #11. |
| 14 | `database/seeders/ContactSeeder.php:17` | `run()` | `db:seed` | Seed listing contacts | **Listing** | **A** | Create the Listing explicitly, then attach | Same as #11. |
| 15 | `database/seeders/ServiceSeeder.php:17` | `run()` | `db:seed` | Seed listing services | **Listing** | **A** | Create the Listing explicitly, then attach | Same as #11. |
| 16 | `database/seeders/DirectoryTestSeeder.php:72` | `run()` | `db:seed` | Seed directory categories | **Listing** | **A** | Create the Listing explicitly, then attach | Same as #11. |

**Class totals: A = 6 · B = 0 · C = 0 · D = 1 · E = 9.**

---

## 3. SECOND TABLE — equivalent "first Listing" assumptions

| # | File | Pattern | Context | Why It Selects One Listing | Class | Recommended Target |
|---|---|---|---|---|---|---|
| 1 | `app/Http/Controllers/Public/LeadController.php:47` | `'listing_id' => $business->listings()->value('id')` | Public business-scoped contact form → `Lead::create` | Attributes a lead to the organization's first Listing because the form is keyed by Business | **E** | The public form should post from a Listing page (`/listing/{slug}`) and carry `listing_id` explicitly; or leads become organization-level until 1D-5. |

No occurrences of `->listings->first()`, `->listings()->latest()->first()`,
`->listings()->oldest()->first()` were found.

---

## 4. THIRD TABLE — owner route migration

| Current Route | Current Entity | Actual Entity | Future Route / Context | Class | Notes |
|---|---|---|---|---|---|
| `POST /owner/businesses/{business}/services` | Business | **Listing** | `POST /owner/listings/{listing}/services` | E | Service is Listing-owned. |
| `PUT /owner/businesses/{business}/services/{service}` | Business (auth) | Listing (via service) | `PUT /owner/listings/{listing}/services/{service}` | E | Authorization is checked on the Business, not the Listing. |
| `DELETE /owner/businesses/{business}/services/{service}` | Business (auth) | Listing (via service) | `DELETE /owner/listings/{listing}/services/{service}` | E | Same. |
| `POST /owner/businesses/{business}/contacts` | Business | **Listing** | `POST /owner/listings/{listing}/contacts` | E | Contact is Listing-owned. |
| `PUT/DELETE /owner/businesses/{business}/contacts/{contact}` | Business (auth) | Listing (via contact) | `…/listings/{listing}/contacts/{contact}` | E | Same. |
| `POST /owner/businesses/{business}/images` | Business | **Listing** | `POST /owner/listings/{listing}/images` | E | Media is Listing-owned. |
| `DELETE /owner/businesses/{business}/images/{image}` | Business (auth) | Listing (via image) | `…/listings/{listing}/images/{image}` | E | Same. |
| `GET /owner/businesses/{business}/images` | Business | Listing(s) | `…/listings/{listing}/images` | E | The index aggregates a Business's media across Listings. |
| `GET/PUT /owner/businesses/{business}/analytics` | Business | **Listing(s)** | Listing-scoped analytics, or an explicit organization aggregate (Class C) | E | Needs a product decision: per-Listing view or derived aggregate. |
| `POST /owner/businesses/{business}/locations` … | Business | **Location** (already Business-owned, nullable org link) | Business-scoped is acceptable | **B** | Locations belong to the organization; `locations.business_id` is a legitimate org edge. Not a Listing bridge. |
| `…/locations/{location}/hours` | Location | Location | unchanged | **B** | Hours describe the physical place. |
| `PUT /owner/businesses/{business}` (category sync) | Business | **Listing(s)** | Category assignment moves to the Listing editor | E | See master table #5. |
| Owner child routes that already `guardNotHidden($location, 'location')` / `guardNotHidden($service, 'service')` | mixed | mixed | — | — | Evidence that the codebase already models Listing-owned children as first-class, only the *route* is Business-scoped. |

---

## 5. FOURTH TABLE — authorization

| File | Current Authorization Basis | Actual Entity | Required Future Basis | Class |
|---|---|---|---|---|
| `app/Http/Controllers/Owner/ServiceController.php` | `$business->owner_id !== auth()->id()` (4 checks) | Listing-owned service | `ListingPolicy@update` through the owning Listing | **E** |
| `app/Http/Controllers/Owner/ContactController.php` | `$business->owner_id !== auth()->id()` (4 checks) | Listing-owned contact | Listing ownership | **E** |
| `app/Http/Controllers/Owner/ImageController.php` | `$business->owner_id !== auth()->id()` (3 active checks) | Listing-owned media | Listing ownership | **E** |
| `app/Http/Controllers/Owner/HourController.php` | `$business->owner_id !== auth()->id()` (6 checks) | Location-owned hours | Business/Location ownership is **correct** | **B** |
| `app/Http/Controllers/Owner/LocationController.php` | `$business->owner_id` (6 checks) | Location (org-linked) | Business ownership is **correct** | **B** |
| `app/Http/Controllers/Owner/BusinessController.php` | `$business->owner_id` (5 checks) | Organization | Business ownership is **correct** | **B** |
| `app/Http/Controllers/Owner/ListingController.php` | `Gate::authorize(... ListingPolicy)` | Listing | **Already correct** (Wave 1D-2) | — |

**No formal policy currently authorizes Listing operations through Business
ownership** — there is no `BusinessPolicy`. The mechanism is **21 inline
`owner_id` comparisons**, of which **11 authorize Listing-owned operations**
through Business ownership (Service 4 + Contact 4 + Image 3), and **10 are
legitimately Business/Location-scoped**.

---

## 6. SPECIAL AUDIT — frontend

`primaryListing` / `primary_listing` / `primary-listing`: **zero occurrences** in
Vue/JS source.

However the frontend does carry the same assumption structurally: owner pages
are addressed by a Business and operate on Listing-owned data. Components
receiving a `business` prop and then acting on Listing-owned information:

| Component | Assumption | Class |
|---|---|---|
| `Pages/Owner/Services/Index.vue` | `business` → its services | E |
| `Pages/Owner/Contacts/Index.vue` | `business` → its contacts | E |
| `Pages/Owner/Images/Index.vue` | `business` → its media | E |
| `Pages/Owner/Analytics/Index.vue` | `business` → its analytics | E |
| `Pages/Owner/Hours/Index.vue` | `business` + `location` → hours | B (correct) |
| `Pages/Owner/Locations/*` | `business` → locations | B (correct) |
| `Pages/Owner/Businesses/Edit.vue` | `business` → category assignment | E |
| `Pages/Public/BusinessProfile.vue` | `business` → organization + its **Listings** | **B (correct after Wave 1D-2 gap closure)** |
| `Pages/Public/ListingProfile.vue` | `listing` → the Listing | Already correct |
| `Components/Public/BusinessCard.vue` | prop named `business`, entity is a Listing | Naming debt → **1D-6** |

Not every `business` variable is wrong: `BusinessProfile.vue` legitimately
renders the organization, and Business/Location management is correctly
Business-scoped.

---

## 7. SPECIAL AUDIT — services, jobs, commands, listeners

- **Jobs / Events / Listeners: none exist** (`app/Jobs`, `app/Events`, `app/Listeners` absent).
- **Services**: no `primaryListing()` use. `ExploreService`, `CollectionService`, `PlanEnforcementService`, `BusinessCompletenessService`, `SubscriptionService`, `EntitlementService`, `ImageService`, `ListingMigrationAnalyzer`, `MigrationReadinessService` were all checked. `PlanEnforcementService` iterates `$business->listings()` (aggregate — Class C by nature, but it is enforcement across all Listings, which is correct).
- **Commands**: `ListingMigrationDryRun` and `SyncImagePaths` / `BackfillImageVariants` query Listings directly; no bridge.
- **Middleware**: `TrackBusinessView` (master table #10) is the only one.

---

## 8. SPECIAL AUDIT — resources

| Resource | Serialises | Internally uses `primaryListing()` | Correct identity |
|---|---|---|---|
| `ListingDirectoryResource` | a **Listing** | No | Listing — correct |
| `BusinessDirectoryResource` | a **Business** | No | Business organization (used only by `/business/{slug}` after 1D-1) — correct, but its *shape* still reads as a discovery card |
| `Business` model `$appends` / `toArray` | Business | No | correct |

No resource obtains `primaryListing()`. This is a clean area.

---

## 9. SPECIAL AUDIT — tests

Only one test file mentions `primaryListing`, and only in a docblock
(`tests/Feature/Images/ImagePipelineTest.php:22`).

Tests that will need architectural updates when the owner routes become
Listing-scoped (they currently set up Business → one Listing because the route
demands a Business):

- `tests/Feature/Images/ImagePipelineTest.php` — posts to `/owner/businesses/{id}/images`
- `tests/Unit/Services/MigrationReadinessTest.php`, `ListingMigrationAnalyzerTest.php` — create one Listing per Business for the analyzer
- Any test hitting `{business}/services|contacts|images`

No test will need rewriting **during this audit**.

---

## 10. ARCHITECTURAL SUMMARY

**Q1 — How many `primaryListing()` call sites?**
16 active (19 raw occurrences − 1 definition − 2 comments), plus 1 equivalent
first-Listing assumption in `Public/LeadController`.

**Q2 — Classification breakdown**

```text
A — Listing-scoped (no UX change needed)   6   (all seeders)
B — Business-scoped (correct as-is)        0   (no primaryListing() site is legitimately Business-scoped)
C — Aggregate                              0
D — Dead                                   1   (BusinessController::store category attach)
E — UX / context redesign                  9
```

Nothing is Class B: every remaining bridge is applied to Listing-owned data.
Business-scoped *routes* do exist (locations, hours, organization CRUD) but they
do not use `primaryListing()` at all — which is the correct signal.

**Q3 — How many owner routes expose Listing-owned operations through a Business parameter?**
**4 route groups** (`{business}/services`, `{business}/contacts`,
`{business}/images`, `{business}/analytics`) plus the category sync on
`PUT /owner/businesses/{business}` — **5 surfaces** in total.

**Q4 — How many policies authorize Listing operations indirectly through Business ownership?**
**0 policies** and **11 inline `owner_id` checks** across three controllers
(Service 4, Contact 4, Image 3). There is no `BusinessPolicy`.

**Q5 — Hidden "first Listing" assumptions not using `primaryListing()`?**
**Yes, one:** `Public/LeadController:47` uses `$business->listings()->value('id')`.
No `->listings->first()` style patterns exist elsewhere.

**Q6 — What should the future owner-side canonical context be?**

```text
/owner/listings/{listing}/services
/owner/listings/{listing}/contacts
/owner/listings/{listing}/images
/owner/listings/{listing}/analytics
/owner/listings/{listing}/categories   (assignment on the Listing editor)
```

**Business-scoped context correctly remains** for:

```text
/owner/businesses/{business}                  organization identity/branding
/owner/businesses/{business}/locations        organization's physical places
/owner/businesses/{business}/locations/{l}/hours
```

> **Important distinction (Wave 1D §21 safety rule):** there is **no** legitimate
> "default / featured / canonical Listing" concept in the codebase today.
> `hasFeaturedListingFeature()` is a **plan feature flag** (a boolean on the
> owner's plan), not a chosen Listing — it must not be conflated with, or
> replaced by, a chosen Listing. No `defaultListing` / `mainListing` /
> `canonicalListing` methods exist.

---

## 11. PROPOSED 1D-3 IMPLEMENTATION SEQUENCE — NOT EXECUTED

Adjusted to what the audit actually found.

**Batch 1 — Listing-scoped owner routes (Class E)**
Add `/owner/listings/{listing}/{services,contacts,images,analytics}` route
groups alongside the existing Business-scoped ones. No removals yet.

**Batch 2 — Authorization (Class E)**
Route the 11 inline `owner_id` checks through `ListingPolicy` using the owning
Listing. Keep the existing Location/Hour/Business checks as-is (Class B).

**Batch 3 — Listing-owned controllers and services (Class E)**
Move `ServiceController`, `ContactController`, `ImageController` and the two
`AnalyticsController`s to take a `Listing`. Update `TrackBusinessView` so
organization-page views are not recorded as Listing analytics. Resolve
`LeadController` by carrying an explicit `listing_id` from the Listing page.

**Batch 4 — Frontend context (Class E)**
Change the listed `Pages/Owner/*` screens to receive and submit a `listing`.

**Batch 5 — Seeder and dead-code cleanup (Class A + D)**
Seeders create their Listing explicitly, then attach; remove the no-op category
attach in `BusinessController::store`.

**Batch 6 — Remove the bridge**
Only once no call site remains: delete `Business::primaryListing()` and add a
regression test asserting it does not exist and that no owner route resolves a
Listing from a Business.

**Batch 7 — Regression tests**
Multi-Listing owner scenarios: two Listings of one Business each with
independent services, contacts, images, categories and analytics.

---

## 12. GIT

```text
git status      : one new untracked file (this document)
git diff --stat : empty (no tracked file modified)
git log -1      : 6d8002b Phase 11 Wave 1D-2 gap closure: render the organization's Listings
```

**No application code, routes, schemas, controllers, Vue components, tests or
migrations were modified.** The only artifact is this audit document, which was
deliberately **not committed**.
