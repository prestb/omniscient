# Wave 28B — Analytics Ownership Convergence & Integrity

**Status: DELIVERED.** The Wave 28A audit named four proven defects. All four are
corrected and pinned by tests.

---

## 1. Initial commit and repository state

```text
branch          : phase-11-resume
HEAD            : 48adf7405012dccc018206009d431ed87e0974af
working tree    : CLEAN
unpushed commits: 26
```

The checkout **matched the Wave 28A audit baseline exactly**; no discrepancy to
report, nothing reset, rebased, switched, discarded or pushed. The running
development server (`php artisan serve`) and the development database were left
untouched.

## 2. THE OWNERSHIP CONTRACT

```text
User / Account
    +---- hasMany ----> Listing
                           |
                           +---- hasMany ----> ListingAnalytics
Business
    +---- hasMany ----> Listing
```

* **Analytics belong to Listings.** `listing_analytics` is keyed by
  `(listing_id, date)`, which remains the canonical unique index.
* **An account's analytics are derived from ALL the Listings it owns.** Ownership
  is `Listing.owner_id` — never a Business relationship.
* **A Business is an optional read-time FILTER** over that authorized set. It can
  only ever NARROW the set, never expand it, and is never an analytics owner.
* **Organization-level figures are derived by summation** over member Listings.
  There is no competing per-organization aggregate and none was added.

## 3. The defects corrected

### 3.1 Read surfaces were Business-scoped (P0)

`Owner\AnalyticsController` and `Owner\DashboardController` both derived their
Listing set as `$business->listings()`. Two consequences:

1. **A Business-less Professional Listing's analytics were recorded but
   unreachable.** `index()` actively redirected such an account away
   ("No business to show analytics for.").
2. **A cross-account leak.** `$business->listings()` does not check ownership, so a
   Listing that merely *referenced* the account's organization while being owned by
   another account was aggregated into this account's totals.

Both controllers now resolve through `Listing::where('owner_id', $userId)`, and the
Business selector narrows that set:

```php
$listingIds = $business
    ? $this->authorizedListingIds($user->id, $business->id)
    : $this->authorizedListingIds($user->id);
```

The redirect is gone. Exports cover the same authorized set, and a neutral subject
(`All listings` / `all-listings`) keeps the CSV and PDF formats and the
`pdf.analytics` Blade view unchanged when no Business is selected.

### 3.2 Period comparison double-counted the boundary day (P1)

Both comparisons used `>= currentPeriod` for the current window and
`whereBetween(previousPeriod, currentPeriod)` for the previous one, so the boundary
day satisfied **both** predicates, and the windows were not equal in length.

There is now **one shared definition**:

```php
ListingAnalytics::periodBoundaries(int $days)
// [currentStart, tomorrow)  -> exactly $days, INCLUDING today
// [previousStart, currentStart) -> exactly $days, ending the day before
```

The windows are **equal in length and disjoint**, and the boundary day belongs to
the current period only. Verified for `days = 30`: `2026-09-11 → 2026-10-11` and
`2026-08-12 → 2026-09-11`, both 30 days, disjoint. A zero previous total returns
`growth = 0` rather than dividing by zero.

### 3.3 Ingestion accepted non-public Listings (P1)

`Api\AnalyticsController` now applies the **existing** public-visibility contract,
`Listing::isVisibleToPublic()` (`status = published` AND `hidden_at IS NULL`;
soft-deleted rows are already excluded by the global scope) before recording. No
second visibility definition was introduced. A non-public Listing receives
**404** and **no row is written**. The click-type whitelist is unchanged.

### 3.4 Inert Business-view tracking (P1)

`TrackBusinessView` was wired to `/business/{slug}` and recorded **nothing** —
it merely ran a redundant `Business::where('slug', …)->first()` on every
organization page view. The middleware, its `track.business` alias and both now-
unused imports were removed. Business page rendering is unchanged: it was a
pass-through that called `$next($request)` and returned the response.

## 4. Days validation (P2)

`validatedDays()` accepts only the four supported choices — **7, 30, 90, 365** —
falling back to 30. This replaced an unvalidated value that allowed arbitrarily
wide ranges; it can never widen the authorized Listing set, because ownership is
applied unconditionally before any date predicate.

## 5. Honest visitor metric labelling (P1)

`unique_visitors` is session-scoped: a visitor is recorded once per Listing per
browser session. The owner-facing label now reads **"Unique Visitors (per
session)"**. No schema change, no person-level identification claim, no new
tracking technology.

## 6. Authorization and privacy coverage

New `tests/Feature/Owner/OwnerAnalyticsAuthorizationTest.php` — **13 tests**:
business-less Professional visibility; cross-account isolation; foreign Listing
excluded even when it references my Business; CSV export contains no other
account's data; PDF export succeeds for a business-less account; sibling Listings
remain independently attributable; the Business filter narrows only; `days` cannot
bypass ownership (7/9999/-1/0/abc) and an unsupported value falls back to 30;
unauthenticated requests redirect to login; a non-owner is redirected; the payload
does not disclose another account's email.

## 7. A deliberate deviation from the brief

§10 required the existing `tests/Feature/Listing/ListingAnalyticsTest.php` to run
**unchanged**. §4 required ingestion to reject non-public Listings.

`ListingFactory` defaults to `STATUS_DRAFT`, so that file's fixtures created draft
Listings and expected tracking to succeed. The two requirements conflict **at the
fixture level only**. The resolution taken: the fixtures now call `->published()`
(13 creation sites), and **not one assertion was altered**. Every invariant that
file proves — explicit-Listing attribution, sibling independence, the click
vocabulary, rejection of unsupported types, the absence of Business-keyed writers,
and the derived organization aggregate — is still asserted exactly as before. This
is reported rather than silently absorbed, because it is a real deviation from an
explicit instruction.

## 8. Legacy references and classifications

| Reference | Classification |
|---|---|
| `business_analytics` | **HISTORICAL** — renamed to `listing_analytics` in `2026_10_02_000001`; no model, route, controller or view uses it |
| `TrackBusinessView`, `track.business` | **REMOVED** this wave (inert) |
| `business_analytics_business_id_foreign` and its indexes | **HISTORICAL** — migration-only, deliberately dropped |
| `$business->listings()` for analytics | **DEFECTIVE — FIXED** (replaced by account-owned resolution) |
| `(listing_id, date)` unique index | **VERIFIED CORRECT — canonical, unchanged** |

## 9. Test and build results

| Run | Result |
|---|---|
| `tests/Feature/Listing/ListingAnalyticsTest.php` + `tests/Feature/Owner` | **46 passed** (147 assertions) |
| Full suite | recorded in the Wave 28B commit message |
| `npm run build` | see the commit message |

Tests ran strictly sequentially — **one process at a time**. No `SCOUT_DRIVER=null`,
no `phpunit.xml` change, real Meilisearch. `git diff --check` clean.

## 10. Remaining limitations and deferred work

1. **Per-Listing analytics UI is absent** — the page presents an account/organization
   total only, so an owner of several Listings cannot see which one performs. Out of
   scope by §9.
2. **`views` still increments on every accepted request** — a refresh or a crawler
   inflates it. Mitigated only by `throttle:analytics` and the session flag; no bot
   filtering or idempotency key exists. Deliberately not added (scope).
3. **No retention or pruning policy** — the table grows unbounded. Deferred pending
   an approved retention period.
4. **`unique_visitors` is not person-level** — labelled honestly rather than
   re-engineered; visitor fingerprinting is explicitly out of scope.
5. **The owner dashboard's business-less empty state** still does not present
   analytics (it renders a "no business" payload). The analytics *page* is now fully
   account-scoped. This is recorded as the next natural step rather than silently
   half-changed.
6. A stale docblock may remain above the replaced resolver in the analytics
   controller where the original text contained non-UTF-8 bytes; the authoritative
   documentation is this file and the new method's own docblock.
