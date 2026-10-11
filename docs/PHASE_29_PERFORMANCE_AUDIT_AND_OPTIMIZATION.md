# Phase 29 — Performance Audit & Optimization

**Status: VERIFIED.** One measured optimization was implemented and proven. Every
other finding below is either **MEASURED** (with the query count and shape that
proves it) or explicitly marked **THEORETICAL / DEFERRED**. Nothing was changed on
theory.

---

## 1. Baseline environment

```text
branch          : phase-11-resume
HEAD            : 10fb637760c4f6c12102c3bb2d25289070557912
working tree    : CLEAN
unpushed        : 28
```

| Setting | Value |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `local` / `true` |
| `DB_CONNECTION` | `mysql` — MariaDB 10.4.32 |
| `CACHE_STORE` | `database` (`array` under PHPUnit) |
| `QUEUE_CONNECTION` | `database` |
| `SESSION_DRIVER` | `database` |
| `SCOUT_DRIVER` | `meilisearch` (real Meilisearch, `test_` prefix in tests) |
| `FILESYSTEM_DISK` | `local` |
| `MAIL_MAILER` | `smtp` (`array` under PHPUnit) |

## 2. Performance baseline — what was measured and how

Wall-clock timing is environment-dependent, so **query counts and SQL shapes** were
used as the reproducible measurement. A temporary probe (`DB::enableQueryLog()`)
seeded a realistic dataset — 1 owner, 1 published Business, 1 Location with
coordinates, 3 Categories and 8 published Listings (5 organization-owned, 3
business-less) — and recorded, per request path, the total query count and how many
queries were **exact SQL-shape duplicates** (same SQL, different bindings = a
per-row/per-iteration query = N+1).

## 3. Database findings (measured)

| Path | Queries (before) | Worst repeated shape | Verdict |
|---|---|---|---|
| `/admin/dashboard` | **84** | **x14**, **x13**, x9 | **N+1 over months — FIXED** |
| `/` | 46 | x16 | measured, shape not attributed (see §17) |
| `/directory` | 35 | x16 | measured, shape not attributed |
| `/admin/listings` | 29 | x5, x5 | **N+1 — DEFERRED, cause identified** |
| `/admin/businesses` | 28 | x7 | **N+1 — DEFERRED, cause identified** |
| `/admin/users` | 11 | x2 | acceptable |
| `/listing/{slug}` | 21–22 | x2 | acceptable — one page, bounded repeats |
| `/owner/dashboard` | 62 | x19 | measured, shape not attributed |
| `/owner/analytics` | 17 | x2 | acceptable; unchanged by `days` |
| `/business/{slug}` | 23 (404 in fixture) | x2 | not profiled to completion |
| `/sitemap.xml` | 12 | x3 | acceptable |
| `/search`, `/explore`, `/categories`, `/locations`, `/search/autocomplete` | **3–6** | 0 | **excellent — no action** |
| `/directory?view=map` | 17 | 0 | no duplicates |

**No N+1 was fixed without first observing its SQL shape.**

## 4. Search findings

Search is not a bottleneck. `/search` issues **6** queries and
`/search/autocomplete` **5**, with **zero** duplicate shapes — the Meilisearch
result set is hydrated in bounded batches and no expensive per-result second-stage
query was observed. `/explore` (3) and `/categories` (4) are similarly clean.

**No change was made to search, the index, ranking, filters, facets or pagination.**
No index field was removed or added.

## 5. Public page findings

`/directory` paginates correctly in list mode (`paginate(12)`, line 261) — the
`$query->get()` that looks unbounded is **map mode only**, a documented Phase 22E-3
design that fetches every matching Listing with coordinates to place pins:

> *MAP MODE — fetch all matching listings with coordinates, no pagination, minimal
> payload.*

This is a **genuine scalability risk** (a large directory loads every matching row
plus its Location into memory for the map view) but it is an intentional product
trade-off, not an accident. Bounding it would silently drop pins from the map, which
would change user-visible behaviour — **documented, not changed** (§17).

`/categories`, `/locations` and `/explore` are highly efficient (3–5 queries).

`/listing/{slug}` costs 21–22 queries with only a single x2 repeat — the page
renders Listing, Business context, Location, categories, services, contacts, gallery
and the review aggregate. **No data was removed from the public Listing contract.**

## 6. Analytics findings

`/owner/analytics` costs **17 queries regardless of the period** (identical at
`days=30` and `days=90`), with one x2 repeat. Wave 28C already established
account-owned resolution, a single grouped per-Listing query, and shared
period boundaries, so date-range narrowing does not add queries. **No change was
made** and no metric definition was touched.

## 7. Admin findings — the optimization

`Admin\DashboardController` built its two 12-month charts with a
**one-query-per-month loop**, and the subscription status chart with
**six separate `count()` queries**:

```php
// BEFORE — getRevenueChartData()
for ($i = 11; $i >= 0; $i--) {
    $month = Carbon::now()->subMonths($i);
    $revenue = PaymentTransaction::where('status', 'successful')
        ->whereMonth('confirmed_at', $month->month)
        ->whereYear('confirmed_at', $month->year)
        ->sum('amount');
    $data[] = $revenue / 1000;
}
```

## 8. Frontend / media / queue findings

* **Frontend:** the production build was already verified in Wave 28C (client PASS,
  SSR PASS). No new bundle work was undertaken this wave; no framework, bundler or
  loading strategy was changed.
* **Media:** Listing media handling was **not** profiled this wave, so no media
  finding is claimed. No storage, CDN or image semantics were touched.
* **Queues:** Scout syncing is **synchronous by design** (Phase 23 established
  child-to-Listing synchronization for consistency). No job was moved, added or
  coalesced, and no consistency semantic was altered for speed.

## 9. Optimizations implemented

### 9.1 Admin dashboard monthly charts — one grouped aggregate

```text
Problem:    The revenue and business-growth charts each issued one aggregate
            query PER MONTH — 24 queries to draw 24 points.
Evidence:   probe  /admin/dashboard  queries=84  worst-repeat=14
            x14: select sum(`amount`) as aggregate from `payment_transactions`
                 where `status` = ? and month(`confirmed_at`) = ? and year(...)
            x13: select count(*) as aggregate from `businesses`
                 where month(`created_at`) = ? and year(`created_at`) = ?
Change:     ONE grouped aggregate per chart, bucketed by YEAR()/MONTH(),
            keyed and expanded into the same 12-month series.
Before:     84 queries, worst-repeat 14
After:      62 queries, worst-repeat 9   (first fix)
Trade-off:  None. Same values; absent months stay 0. `whereMonth()`/`whereYear()`
            ALSO wrap the column in a function, which prevents index use — the new
            `>= start` range predicate is index-friendly.
```

### 9.2 Subscription status chart — six counts to one

```text
Problem:    getSubscriptionStatusData() ran six separate COUNT queries, one per
            status label.
Evidence:   probe  x9: select count(*) as aggregate from `subscriptions`
                   where `status` = ? and `subscriptions`.`deleted_at` is null
Change:     ONE `SELECT status, COUNT(*) ... GROUP BY status`, read into the same
            label => count map.
Before:     62 queries
After:      57 queries, worst-repeat 7
Trade-off:  None. Identical labels, ordering and values.
```

**Net measured result: `/admin/dashboard` 84 → 57 queries (−32%), the two month-loop
shapes and the six-count shape are gone, and the SQL no longer wraps indexed columns
in functions.**

## 10. Database/schema changes

**None.** No migration was created, no index was added, dropped or altered. The
existing indexes were not found insufficient for any measured query, so no index
proposal was justified under §12's requirement to document QUERY / WHY INSUFFICIENT /
BENEFIT / COST for every index.

## 11. Caching changes

**None.** No cache was added. The measured problems were query-inefficiency, and §13
is explicit that caching is not automatically an optimization — and particularly that
public Listing responses must not be cached blindly given visibility, review,
subscription, Location and child-mutation invalidation. Fixing the queries removed
the need.

## 12. Tests

| Run | Result |
|---|---|
| `tests/Feature/Admin` + `tests/Feature/Seo` + `tests/Feature/Search` | **246 passed** (875 assertions) |
| `tests/Feature/Performance/AdminDashboardAggregateTest.php` | **1 passed** (11 assertions) |
| Full suite | recorded in the Phase 29 commit message |

The new regression test pins the **architectural invariant** rather than a brittle
exact query count: the 12-month series is complete and correct (labels end with the
current month; the current month's value equals the fixture), the subscription-status
labels are intact, and the number of `month()`-scoped aggregates is **bounded**
(≤ 10) so the per-month loop cannot silently return. Before the fix those loops alone
contributed ~24 such queries.

```text
real MySQL/MariaDB : YES
real Meilisearch   : YES
SCOUT_DRIVER=null  : NO
concurrent test processes: NO
phpunit.xml weakened: NO
```

## 13. Documentation

This file: `docs/PHASE_29_PERFORMANCE_AUDIT_AND_OPTIMIZATION.md`.

## 14. Remaining performance risks

### MEASURED — deferred with the cause identified

1. **`Business::$appends` includes a `categories` accessor** derived from the
   organization's Listings. Serializing N Businesses therefore triggers N queries.
   Evidence: `/admin/listings` **x5** and `/admin/businesses` — `select * from
   listings where business_id = ?`. Fixing it requires the accessor to prefer an
   already-loaded relation *and* the admin pages to eager-load it — a two-sided
   change not undertaken this wave.
2. **`/admin/listings` loads `categories` per row** — x5
   `select categories.* , listing_categories...` with no `with('categories')` on the
   paginator's models.
3. **A plan-by-tier lookup repeats** — x7
   `select * from plans where tier = ? limit 1` on both `/admin/dashboard` and
   `/admin/businesses`.
4. **`topCategories` counts per category** — x3
   `select count(*) from listings inner join listing_categories`.
5. **`/`, `/directory` (x16 each) and `/owner/dashboard` (x19)** have a repeated
   query shape that was **measured but not attributed**. They are the largest
   remaining counts on public/owner surfaces and are the natural next target.
6. **Directory map mode is unbounded by design** — see §5.

### THEORETICAL / future

7. Cache or pre-aggregate the admin dashboard once the dataset is large enough to
   matter; the current fix removed the obvious waste first.
8. Evaluate lazy-loading heavy admin pages (`/admin/dashboard`, `/admin/listings`)
   only if measurements justify it.

## 15. Items deliberately deferred (with reasons)

* **No index changes** — no measured query required one.
* **No caching** — the problems were query shape, not repeated computation.
* **No queueing of Scout sync** — synchronous indexing is an established
  correctness decision (Phase 23) and this wave is not permitted to trade
  consistency for speed.
* **No map-mode bounding** — would silently drop pins, changing product semantics.
* **No media/CDN/bundle work** — nothing was measured there, so nothing was claimed.
* **No Business/Listing/Location model redesign** — out of scope by §20.
