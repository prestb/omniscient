# Wave 28C — Analytics Experience & Tracking Integrity

**Status: VERIFIED.** Every behaviour below is covered by executed tests, not by
inspection. Where something is imperfect, the limitation is stated rather than
smoothed over.

---

## 1. Audit findings (what was actually wrong)

| Concern | Finding |
|---|---|
| Dashboard omission | `Owner\DashboardController::index()` returned **early** when the account had no Business, with no `analytics` key at all, and `Owner/Dashboard.vue` gated every analytics element on `hasBusiness`. A business-less owner therefore had no analytics surface even though the analytics *page* had become account-scoped in Wave 28B. |
| Listing-level analytics | **Did not exist.** No per-Listing figures, no Listing-scoped route, no way to compare one's own Listings. |
| View counting | `trackView()` ran `increment('views')` on **every** accepted POST. `throttle:analytics` limited the *rate*, not the *repetition*, so a refresh, a crawler or a repeated client call each added a view. `views` had no written definition. |
| Retention | **None.** Storage is daily aggregates (`listing_analytics`, unique on `listing_id + date`) — not events. No sessions, IPs or event metadata are stored. |

## 2. Dashboard behaviour

Analytics are presented on **availability**, never on Business existence:

```js
const showAnalytics = computed(() => !!props.analytics);
```

`Owner\DashboardController` now always supplies `analytics`:

* **Account with a Business** — unchanged path and unchanged payload; `analytics`
  is still the organization's derived aggregate over the account-owned,
  business-narrowed Listing set.
* **Business-less account with Listings** — the early-return payload now includes
  `accountAnalytics($user)`, resolving `Listing::where('owner_id', $user->id)`. It
  returns the **same shape** as the Business path (`stats`, `chart`, `comparison`,
  `has_data`, `listings_count`), so the same cards render.
* **Empty account** — deliberate zeros with `has_data = false` and
  `listings_count = 0`. Zeros are accurate, not misleading; no fabricated totals.
* **Isolation** — the set is strictly `owner_id`-scoped. A test proves another
  account's 500 views never appear.

The genuinely Business-specific dashboard sections (completeness, leads, reviews,
coupons, business profile) **still** gate on `hasBusiness` — correctly, because
they require a Business. Only the four analytics gates were switched.

## 3. Listing-specific analytics

* **Route:** `GET /owner/analytics/listing/{listing}` → `owner.analytics.listing`.
* **`listingAnalytics()`** authorizes on **`listings.owner_id`** twice over —
  `abort_unless((int) $listing->owner_id === (int) $user->id, 404)` **and**
  membership of `authorizedListingIds()` — then renders through the **same**
  analytics view via `?listing=…`, so there is one renderer and one authorization
  contract. A non-owned Listing is **404** (never 403), which also avoids
  confirming that somebody else's Listing exists.
* **`?listing=` filter** on the index and both exports is *intersected* with the
  authorized set, so a foreign id yields an empty result rather than a leak.
* **`perListingBreakdown()`** returns one row per authorized Listing from a
  **single grouped query**, including Listings with no activity, each with
  `is_public` and the canonical `/listing/{slug}`.
* **UI** (`Owner/Analytics/Index.vue`): a *Performance by Listing* table listing
  the owner's Listings with Views, Unique Visitors (per session) and Phone Clicks,
  a canonical link per Listing, a "Not public" marker, a *View analytics* action
  that filters the page to that Listing, a highlighted selected row, a
  **View all listings** escape back to account level, and an empty state. It
  reuses the page's existing table conventions and the existing `formatNumber`
  helper — no second renderer, no new charting system.

## 4. Authorization

| Rule | Enforcement |
|---|---|
| Owner may inspect their own Listing | `owner_id` equality **and** authorized-set membership |
| Foreign Listing | **404** (unauthenticated → login; non-owner → their dashboard) |
| Owning the Listing's Business | **does NOT** authorize — tested |
| Sibling ownership / supplied id / slug | **does NOT** authorize |
| Invalid period input | cannot widen the set: `validatedDays()` allows only 7/30/90/365, and ownership is applied before any date predicate |
| Exports | same authorized set as the page |

## 5. View definition and deduplication

**`views` = the number of ACCEPTED Listing-view events, where an event is accepted
at most once per Listing per browser session per 30-minute window**
(`Api\AnalyticsController::VIEW_DEDUP_WINDOW_MINUTES = 30`).

* Enforced **server-side** with `Cache::add()` keyed by
  `analytics:view:{listing_id}:{session_id}`. `Cache::add` is atomic, so two
  concurrent requests cannot both win; no client-supplied flag is trusted as the
  mechanism.
* A repeated request inside the window is **accepted but not counted**
  (`{"counted": false, "reason": "deduplicated"}`), so refreshes do not inflate it.
* A later visit **after** the window counts again — the metric is repeatable, not
  capped.
* **No IP address, fingerprint or new personal data is stored.**

## 6. Crawler / automated traffic

A blunt deny-list (`AUTOMATED_TOKENS`: `bot`, `crawl`, `spider`, `curl`,
`headlesschrome`, `gptbot`, …) means an obviously automated client **creates no
analytics row** and receives `{"counted": false, "reason": "automated"}`.

**This is explicitly best-effort, not a guarantee:** User-Agent strings are
spoofable, so a crawler presenting a browser User-Agent is still counted. There is
no heavyweight detection dependency, and an absent User-Agent is treated as a real
visit so ordinary and mobile browsers keep working (tested with an iPhone UA).

**Clicks are deliberately NOT deduplicated** — a visitor may legitimately press
"call" twice — they are only bot-filtered, preserving their established meaning.

## 7. Metric semantics

| Label | What it actually measures |
|---|---|
| **Views** | Accepted Listing-view events under the 30-minute session policy above |
| **Unique Visitors (per session)** | One per Listing per browser session — **not verified unique people** |
| **Phone / WhatsApp / Website Clicks** | Button presses, **not** completed calls, messages or sessions |

The UI says "Unique Visitors (per session)" in both the per-Listing table and the
daily table, and spells out the definitions in the *Performance by Listing* panel.
No conversion rate is invented; no metric is relabelled as people or users.

## 8. Retention — deliberately deferred

```text
No automated retention/pruning is currently implemented.

Reason:
analytics are stored as daily aggregates and no reporting horizon has
yet been defined. Pruning would destroy historical reporting data.

Future implementation requires a deliberate product decision about
the supported historical reporting horizon.
```

No migration, no scheduled job, no pruning code was added. The trend charts, period
comparisons and exports read those same daily rows, so deleting them by age would
remove the history the feature exists to report.

## 9. Historical metric caveat

Pre-28C `views` rows were counted under the previous, looser policy. They are
**left as recorded** and have **not** been redefined or back-filled. Totals that
span the change therefore blend two counting policies; this is deliberate, since
retroactively rewriting history would be a fabrication.

## 10. API / export changes

* **New route:** `GET /owner/analytics/listing/{listing}` (`owner.analytics.listing`).
* **New optional parameter:** `?listing=` on `owner.analytics.index`,
  `owner.analytics.export.csv` and `owner.analytics.export.pdf` — narrowing only.
* **New payload keys:** `perListing`, `selectedListingId`, `listingsCount` on the
  analytics page; `analytics` on the business-less dashboard path.
* **Ingestion responses** gained `counted` / `reason` fields. The success envelope
  (`success`) and the click-type whitelist are unchanged, and no analytics data is
  returned.

CSV/PDF formats are unchanged; when no Business is selected the export uses the
neutral subject `All listings` / `all-listings`.

## 11. Tests performed

New `tests/Feature/Owner/AnalyticsExperienceTest.php` — **17 tests, 66 assertions,
all passing**:

*Tracking:* an accepted view; repeated requests inside the window do not inflate the
count; a visit after the window counts again; a spoofable client flag cannot bypass
deduplication; an automated client writes no row; an ordinary mobile browser is
still counted; non-public (draft / suspended / hidden / soft-deleted) Listings are
rejected without a row; clicks stay Listing-specific with the whitelist intact and
an unsupported type rejected; tracking responses expose no analytics data.

*Listing-specific:* an owner can open their own Listing's analytics; a foreign
Listing is 404; **owning the Business does not authorize** a Listing owned by
another account; a foreign `?listing=` filter leaks nothing; the per-Listing
breakdown lists every authorized Listing.

*Dashboard:* a business-less owner receives account analytics; an empty
business-less account gets deliberate zeros; the business-less dashboard never
includes another account's analytics.

**A note on the deduplication tests.** Deduplication is keyed by the browser
session, and Laravel's test client does **not** replay the session cookie between
requests (a real browser always does). The tests therefore carry the session cookie
explicitly — verified by a temporary probe showing `session.driver=database`, the
cache store `array`, `Cache::add` returning `false` on the second call, and the
session id genuinely changing between uncookied requests. Without the cookie there
is no session identity to deduplicate against; that is a property of the test
transport, not of the implementation.

## 12. Build

`npm run build` — **client PASS, SSR PASS, exit code 0.** Generated `public/build`
churn was restored and not committed.

## 13. Bugs found and fixed during this wave

* **Route regression (fixed).** An earlier mechanical edit wrote a literal `~NL~`
  token into `routes/web.php`, turning the inserted line into a comment and thereby
  **commenting out `owner.analytics.export.csv` as well**. The block was rewritten
  with real newlines; `Route::has()` now confirms `owner.analytics.index`,
  `owner.analytics.listing`, `owner.analytics.export.csv` and
  `owner.analytics.export.pdf` are all registered.
* **Ordering bug (fixed).** The `?listing=` filter had been inserted *before*
  `$listingIds` was assigned, causing a 500 on `?listing=`. It now runs after the
  authorized set is resolved.

## 14. Remaining limitations

1. **Bot filtering is best-effort.** A spoofed browser User-Agent is counted.
2. **Deduplication is session-based.** A client that discards its session cookie
   can count again; there is no IP storage or fingerprinting by design, so this is
   the proportionate limit of the existing stack.
3. **Clicks are not deduplicated** (deliberate — see §6).
4. **`unique_visitors` is session-scoped**, not person-level, and is labelled as
   such.
5. **No retention policy** — see §8.
6. **Historical `views` blend two counting policies** — see §9.
7. **The per-Listing UI is a table, not a chart.** A per-Listing time series is a
   future enhancement, not a defect.
8. The business-less dashboard still shows its "no Business" panel alongside the
   analytics cards; that panel is accurate (the account genuinely has no Business)
   and was left in place deliberately.
