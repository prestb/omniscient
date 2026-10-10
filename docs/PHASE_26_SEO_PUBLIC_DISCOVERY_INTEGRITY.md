# Phase 26 — SEO & Public Discovery Integrity

**Status: AUDIT COMPLETE, ONE CONFIRMED DEFECT FIXED.**

Audited at `4fdfa86`. The SEO foundation established by Phase 15B was re-checked
against the current Listing-first architecture after Listing convergence,
Listing-owned Reviews, Location convergence, search changes, subscription
convergence and connection convergence. It survived almost entirely intact; one
mechanism disagreed with application visibility and was corrected.

> No `docs/PHASE_15_*` document exists in this repository — the SEO foundation was
> recorded in `tests/Feature/Seo/SeoFoundationTest.php` and
> `tests/Feature/Seo/SsrReadinessTest.php` only. This document is therefore the
> first written record of the policy, not a duplicate of one.

---

## 1. The defect

`Business::isPubliclyReachable()` is the canonical organization-visibility
contract:

```php
status === published && hidden_at === null && owner?->active_subscription !== null
```

`DirectoryController::show()` applies it and **aborts 404** without it — proven by
`BusinessReachabilityTest` ("a business without an active subscription is not
publicly reachable" → `assertNotFound`).

`SitemapController::businessUrls()` did **not** apply it. Its own docblock claimed
it used "the same gate as `DirectoryController::show`", which was false: it gated
only on `status = published` + `hidden_at IS NULL` + "has a published Listing".

**Consequence:** for any organization whose owner had no active subscription, the
sitemap advertised `/business/{slug}` URLs that returned **404**. The sitemap
contradicted application visibility and advertised dead URLs to crawlers.

The existing test encoded the bug — it created businesses with
`Business::factory()->create(['status' => 'published'])`, **no subscription**, and
asserted the business appeared in the sitemap.

**Fix:** `businessUrls()` now applies `Business::isPubliclyReachable()` itself,
after eager-loading `owner.activeSubscription`, so the route and the sitemap share
one source of truth and cannot drift again.

## 2. Canonical Listing URL

`/listing/{slug}` — one canonical URL per Listing. The public payload's
`seo.canonical` is `url('/listing/' . $listing->slug)`, and Open Graph
`og:url`/`og:title`/`og:description` are derived from the same page. A Listing
never canonicalizes to `/business/{slug}` because it belongs to a Business; the
Business is linked as secondary organization context.

**Business-less Professional:** identical treatment — 200, canonical, title,
description, no Business requirement and no broken Business URL.

## 3. Business organization URL

`/business/{slug}` is an **organization page**, never a discovery result. It is
reachable only when `isPubliclyReachable()` holds (see §7) and renders its
Listings as independent public identities.

## 4. Sitemap policy

`GET /sitemap.xml` is derived per request with no table, cache or queue; it
automatically reflects database state, so visibility transitions
(`draft → published`, `published → hidden`, delete, subscription lapse/activation)
need no invalidation step.

**Included:** `/listing/{slug}` (published, not hidden, not soft-deleted, slug
present); `/business/{slug}` (published, not hidden, **publicly reachable** with an
active subscription, and holding at least one published visible Listing);
`/{category}-in-{city}` collections confirmed through the same
`CollectionService::isValidCollection()` gate the public route uses.

**Excluded:** `/search`, `/directory`, `/explore`, owner/admin/auth routes, JSON
endpoints, hidden/unpublished/suspended/deleted Listings, non-public Businesses,
query-parameter URLs, pagination variants and theoretical category×city pairs.

## 5. Collection page policy

`/{category}-in-{city}` collections are Listing-first: they resolve to canonical
Listing pages, carry their own canonical, and are validated by
`isValidCollection()` so an empty or theoretical pair 404s instead of rendering.
`/categories`, `/locations` and `/explore` keep their existing canonicals and are
not new SEO surfaces.

## 6. Search / directory indexation policy

`/search` and `/directory` remain **`noindex, follow`**. `robots.txt` deliberately
does **not** disallow them: a crawler must be able to FETCH the page to see the
`noindex` directive; blocking them would leave them indexed. JSON utility
endpoints (`/search/autocomplete`, `/api/`) are disallowed. `Sitemap:` is emitted
relative so the host comes from `APP_URL`.

## 7. Listing visibility rules

A Listing is publicly discoverable when `status = published AND hidden_at IS NULL`
— the same condition as `shouldBeSearchable()` and the SQL directory query. Hidden,
unpublished, suspended and soft-deleted Listings are absent from the sitemap, the
public route and the public collections.

## 8. Business visibility rules

A Business page requires `published AND not hidden AND the owner's active
subscription`, plus published Listings to be a meaningful discovery surface. This
gate is intentionally **subscription-dependent** and deliberately does **not**
apply to the organization's Listings — `BusinessReachabilityTest` asserts that a
Listing stays publicly reachable while its organization page 404s.

## 9. Review structured-data ownership

Reviews are **Listing-owned** (PHASE 21C-R1). The Listing page renders the
Listing's own **approved** aggregate (`listing.rating`, `listing.reviews_count`)
and no longer gates its rating behind a Business. A Business page presents its
organization-level aggregate, derived across its Listings' approved reviews.
Pending, rejected and soft-deleted reviews never contribute, and no rating or
review count is fabricated for a Listing that has none.

## 10. Business-less Professional & multi-Listing behaviour

A `business_id = NULL` Professional Listing is fully reachable and canonical with
no Business dependency. Two Listings under one Business keep distinct canonicals,
titles, structured data and URLs; nothing collapses them onto the organization
page, and no `primaryListing()` SEO shortcut exists anywhere.

## 11. Remaining deferred SEO work

1. Structured data is limited to the collection `ItemList` plus page-level
   meta/Open Graph; there is no `LocalBusiness`/`Organization`/`Review` JSON-LD on
   Listing and Business pages. Adding schema types is a product decision, and this
   phase must not introduce them speculatively.
2. Page metadata is verified at the Inertia-props level, because SSR is disabled in
   this application (no `resources/js/ssr.js`, no `bootstrap/ssr`, no `ssr` entry in
   `vite.config.js`). Client-rendered `<Head>` output is not asserted as
   server-rendered HTML.
3. Two dead Inertia routes recorded in Phase 25 (`admin.categories.tree`, generic
   `notifications.index`) remain; neither is public or SEO-relevant.
