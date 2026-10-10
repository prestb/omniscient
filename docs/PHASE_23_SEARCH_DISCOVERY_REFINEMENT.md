# Phase 23 / 23B — Search / Discovery Refinement

**Status: COMPLETE — VERIFIED.** Phase 23 was narrowed to **Phase 23B** after its
audit showed most of the proposed convergence had already been delivered by
earlier phases.

---

## 0. Reconciled status — added by Phase 23D

This document is **append-only history**: each section was written as its phase
completed, and later sections supersede earlier ones. Read this section first, or
read the document in full.

| Area | Current status |
|---|---|
| Phase 23 / 23B search refinement | **Substantially implemented** |
| 23C — autocomplete error boundary | Implemented (`SearchController::autocomplete`, covered by `AutocompleteErrorBoundaryTest`) |
| 23C — `shouldBeSearchable()` index visibility | Implemented (`2fb5cbd`, covered by `ListingIndexVisibilityTest`) |
| 23C-R4 — index-naming convergence | Implemented — one resolver and one guarded settings-write path (`048d746`, `0f48985`) |
| 23C-R5 **F1** — bootstrap aborted when no engine is configured | Implemented (`79c93a5`) |
| 23C-R5 **F2** — dead `UpdateMeilisearchSettings` command | Implemented — the command was deleted (`1006c8e`) |
| 23C-R5 **F3** — stale `index-settings.businesses` key | Implemented — removed and guarded (`1006c8e`) |
| 23C-R5 **F8** — live-search test under a null driver | Implemented — CI runs a real Meilisearch service (`2ff94c5`) |
| Main `/search` error boundary | **Implemented by 23D** — an engine outage now returns HTTP 200 with an empty result set instead of a 500 |
| 23E — `/search` response contract | **Implemented by 23E** — the page consumes the canonical flattened paginator envelope |
| 23G — development Meilisearch reconciliation | **Performed** — the `businesses` index was proven stale and removed, the orphaned `categories` documents were flushed, and the canonical `listings` index was rebuilt. See `docs/PHASE_23G_MEILISEARCH_INDEX_RECONCILIATION.md` |
| 23H — Listing child → search synchronization | **Implemented** — category, service, location, subscription and review mutations now synchronize the owning Listing's document through one explicit path. See `docs/PHASE_23H_LISTING_CHILD_SEARCH_SYNCHRONIZATION.md` |

**Historical statements that must NOT be read as current status.** Section 4
("Workstream B — Meilisearch") was written during the Phase 23B audit. It records
`numeric typing: NOT VERIFIED`, `visibility/indexing: NOT VERIFIED` and closes
with *"This workstream was not completed."* Those were accurate **when written**
and are **superseded** by the 23C, R4 and R5 sections further down this same
document. Do not schedule that workstream again on the strength of §4.

**F4 / F5 / F6 — re-audited by Phase 23F; none is a code defect.** The R5 text
above was written at an earlier HEAD and has been re-derived against the current
one (full evidence in `docs/PHASE_23C_R5_AUDIT.md` §13):

* **F4** — `scout:delete-all-indexes` deletes every index when the Scout prefix
  is empty. **ENVIRONMENT-ONLY, vendor-intentional**: the command's name and
  behaviour agree, nothing in the application invokes it, and the hazard needs a
  server shared with other applications. No fix applied.
* **F5** — Scout's `flush` / `import` / `delete-index`. **INTENTIONAL**: none of
  them can produce an empty or unsafe index name — they resolve through
  `SearchIndexName`, and `delete-index` short-circuits an already-prefixed name.
  No fix applied.
* **F6** — no Meilisearch host allow-list. **INTENTIONAL, accepted residual**;
  adding one would be new infrastructure.

**Also corrected by 23F:** the claim that the visibility contract was
vendor-trusted. `ListingIndexVisibilityTest` asserts only the model hook, so
three live-server tests were added proving an unpublished, hidden or deleted
Listing is actually **removed** from `test_listings`.

**`/search` response contract — RESOLVED by 23E.** 23D recorded that
`Search/Index.vue` reads `listings.total`, `listings.from` and `listings.links`
at the top level while `/search` delivered
`{ data, links: {first,last,prev,next}, meta: {…} }`, and concluded "the paths
under `meta` are the real ones". **That diagnosis was wrong.** The flattened
paginator is the contract the whole application uses — `DirectoryController`,
`CollectionController` and `Admin\ListingController` all pass a RAW paginator,
and `Pagination.vue` declares `links: { type: Array, required: true }`. `/search`
was the only place wrapping a paginator in `Resource::collection()`, which turned
`links` into an object and moved `total`/`from`/`to` under `meta`. The server
boundary was corrected; the Vue page's existing reads became correct.

**Current contract for `/search`:**

```text
listings
├── data            (array of ListingDirectoryResource payloads)
├── links           (array of { url, label, active })  ← Pagination.vue
├── current_page
├── first_page_url / last_page_url / next_page_url / prev_page_url
├── from / to / last_page / per_page / total / path
```

There is no `meta` key. `data`, `current_page`, `from`, `to`, `last_page`,
`per_page` and `total` are all top level. See `SearchResponseContractTest.php`.

See `docs/PHASE_22E_GEOSPATIAL_FOUNDATION.md` for the geospatial foundation that
§1 below already lists as a true claim.

---

## 1. Initial audit (Phase 23)

Read-only audit of the search/discovery pipeline. Result: the architecture was
already Listing-centric, so a full Phase 23 would have been largely a no-op. Four
concrete gaps remained, which became Phase 23B.

## 2. Scope reduction

The original Phase 23 asked for a broad search/discovery convergence. The audit
established that these were **already true** and did not need reimplementation:

| Claim | Evidence |
|---|---|
| Listing is the search entity | `Listing::search()` is the only search call site (`SearchController:46`, `:129`) |
| Business is not searchable | `Business` does not use `Searchable`; no `Business::search` exists |
| Category via listings | only `listing_categories` exists; `business_categories` **does not exist** |
| Rating is Listing-owned | Phase 21C-R1 |
| Location via Listing | `whereHas('location', …)` |
| Radius server-authoritative | Phase 22E-3 / 22E-4 |

Phase 23B therefore covered exactly four workstreams.

## 3. Workstream A — `BusinessDirectoryResource`

**Classification: LEGITIMATE organization presentation. RETAINED.**

Its single live consumer is `DirectoryController::resolveRelatedBusinesses()`
(`:786`), called from `:593` inside the **Business profile** action. It renders a
related-organizations widget on an organization page.

It does **not** masquerade as a Listing search result: canonical discovery
(`/directory`, `/search`) uses `ListingDirectoryResource`.

**AUDITED — NO CHANGE REQUIRED.**

## 4. Workstream B — Meilisearch

```
toSearchableArray:     present on Listing; field-level numeric typing NOT verified
shouldBeSearchable:    NOT traced in this phase
searchableAttributes:  NOT re-audited
filterableAttributes:  documented in ConfigureMeilisearch (status, type, is_featured,
                       hidden, has_active_subscription, is_open_now, business_id,
                       location_id, category_ids, city_id, region_id, country_id)
sortableAttributes:    created_at, published_at, rating, reviews_count, is_featured_rank
numeric typing:        NOT VERIFIED — carried forward as a known gap
visibility/indexing:   NOT VERIFIED — carried forward as a known gap
```

This workstream was **not completed**. It is the largest remaining item and is
recorded honestly rather than claimed.

## 5. Workstream C — Autocomplete

**Before:** a single `try` at `SearchController:128` wrapped the Meilisearch
listing search, the MySQL-only category suggestions, and the intent-parser
suggestions. Its `catch (\Exception)` at `:202` returned all three arrays empty.

**Problem:** a search-engine outage discarded `category_suggestions` and
`search_suggestions` — neither of which depends on Meilisearch. Users lost
suggestions the database could still answer.

**Change:** the listing search now has its **own** `try`/`catch (\Throwable)`
(`:132`–`:152`) which logs via `\Log::warning('Autocomplete listing search failed',
…)` and degrades `suggestions` to an empty collection. The category and intent
workstreams now run **outside** it, so they answer regardless.

This is a narrow boundary around the only failing workstream — not a blanket
`catch (\Throwable) {}`. The outer `catch (\Exception)` is retained for genuinely
unexpected errors and still logs.

**After:** engine down → listing suggestions degrade; category and intent
suggestions still work. Response shape is unchanged.

## 6. Workstream D — Pagination / sorting

**AUDITED — NO CHANGE REQUIRED.** No pagination or sorting defect was
demonstrated within this phase's scope. Radius persistence across
list ↔ map (Phase 22E-4) was not modified and did not regress — the full suite,
including `DiscoveryExperienceTest`, passes.

## 7. Code changes

```
app/Http/Controllers/Public/SearchController.php   search-only error boundary
tests/Feature/Search/AutocompleteErrorBoundaryTest.php   new, 5 tests
docs/PHASE_23_SEARCH_DISCOVERY_REFINEMENT.md       this document
public/build/**                                    regenerated by the client build
```

## 8. Legacy references

```
business_categories        NOT A TABLE — 4 documentation comments   HISTORICAL DOCUMENTATION
BusinessDirectoryResource  1 consumer, Business profile             LEGITIMATE (organization)
BusinessCategory           no model                                  N/A
Business::search           0 occurrences                             N/A
haversine                  1 source comment (DirectoryMap.vue)      REMOVED (22E-4)
distanceKm / withinRadius / calculateDistance   0 occurrences        N/A
```

No new client-side discovery authority was introduced.

## 9. Tests

```
focused:     5 passed  (AutocompleteErrorBoundaryTest, 16 assertions)
relevant:    119 passed (tests/Feature/Search, 403 assertions)
full:        1030 passed, 0 failed, 1 incomplete (pre-existing ExampleTest)
assertions:  3608
```

## 10. Meilisearch verification

The **real Meilisearch server was not exercised** in this pass. The autocomplete
regression test fakes the *engine* boundary (a Scout engine whose `search()`
throws) rather than disabling Scout, so the application path under test is real.
`SCOUT_DRIVER=null` was not used.

## 11. Builds

```
client:  PASS
SSR:     PASS
```

## 12. Remaining risks

1. **`toSearchableArray()` numeric typing is unverified.** Laravel Scout requires
   numeric types for Meilisearch numeric filters. `rating`, `reviews_count` are
   used in filtering/sorting. If any is emitted as a string, those filters can
   silently misbehave.
2. **`shouldBeSearchable()` is untraced.** The SQL path filters
   `status = 'published'` and `hidden_at IS NULL`. Whether the index applies
   equivalent visibility is unknown, so an undiscoverable Listing *could*
   theoretically enter the index.
3. Autocomplete has no integration test against a live Meilisearch server; the
   healthy path is covered only through the real application against the test
   database.
4. No browser/component test runner exists in this repository, so no frontend
   behaviour is verified by execution.

---

# Phase 23C — Meilisearch Searchable-Data & Visibility Convergence

**Status: COMPLETE — VERIFIED** (workstream B, the one Phase 23B left open).

## toSearchableArray()

Fields: id, name, description, slug, type, status, is_featured, hidden,
created_at, published_at, business_id, location_id, city_id, region_id,
country_id, city, region, country, address, is_open_now, category_ids,
categories_names, services_names, has_active_subscription, rating,
reviews_count, is_featured_rank.

Numeric typing verified against the REAL index, not assumed:

```
id Int32 · business_id Int32 · created_at Int32 · rating Int32
reviews_count Int32 · is_featured_rank Int32
is_featured/hidden/is_open_now/has_active_subscription Boolean
```

`rating` and `reviews_count` are explicitly cast in the model
(`round((float) …)`, `(int) …`). **No numeric-type defect found — NO CHANGE.**

## shouldBeSearchable()

**Did not exist.** Scout therefore indexed everything. Real index before the fix:
289 draft + 1 deleted + 1 rejected + 1 suspended of 596 documents.

Added, mirroring the canonical SQL discovery condition:

```php
return $this->status === self::STATUS_PUBLISHED && $this->hidden_at === null;
```

## Bulk-import path (the trap)

`shouldBeSearchable()` governs save/update indexing, but `scout:import` calls
`searchable()` directly and **bypasses** it. Verified: after adding the hook and
re-importing, the live index still held 292 non-discoverable documents.

Added `makeAllSearchableUsing($query)`, scoping the bulk import to the same
condition.

## Real Meilisearch verification

```
server:       http://127.0.0.1:7700 (health: available)
driver:       SCOUT_DRIVER=meilisearch (real engine; SCOUT_DRIVER=null NOT used)
index:        listings
flush + re-import: performed, then re-verified
```

After the fix and a full test run:

```
total docs: 299
status=published  count=299      <-- zero non-discoverable
```

Sorting exercised live: `sort: ["rating:desc"]` returned hits.

`filter: "rating > 0"` returned 400 — **correct**: `rating` is sortable but not
filterable, and the application filters `min_rating` in SQL, never in
Meilisearch. Config therefore matches executable search code.

## Configuration

```
searchable: name, description, categories_names, services_names, city, region, country, address
filterable: status, type, is_featured, hidden, has_active_subscription, is_open_now,
            business_id, location_id, category_ids, city_id, region_id, country_id
sortable:   created_at, is_featured_rank, published_at, rating, reviews_count
```

Re-derived from executable search code and confirmed against the live server.
**NO CHANGE REQUIRED.**

## Changes

```
app/Models/Listing.php                                 shouldBeSearchable() + makeAllSearchableUsing()
tests/Feature/Search/ListingIndexVisibilityTest.php    new, 4 tests
```

## Tests

```
focused:    4 passed  (ListingIndexVisibilityTest, 11 assertions)
relevant:   123 passed (tests/Feature/Search)
full:       1034 passed, 0 failed, 1 incomplete (pre-existing ExampleTest)
assertions: 3619
```

## Builds

```
client: PASS      SSR: PASS
```

## Residual risk (IMPORTANT)

`SCOUT_PREFIX` is **empty**, so the test suite and the development application
share the same `listings` index. Tests write real documents into it. This is why
the index previously held 289 drafts. The visibility fix prevents
*non-discoverable* writes, but index isolation between test and dev remains an
open hazard for a later phase (a test-specific prefix would close it).

---

# Phase 23C-R1 — Working-Tree Recovery & Meilisearch Isolation

**Status: RECOVERY COMPLETE — ISOLATION BLOCKED (documented, not attempted).**

## Working-tree recovery — RESOLVED

Starting state: 249 dirty entries, **all** under `public/build`
(124 deleted, 1 modified `manifest.json`, 124 untracked). **Zero source files
were affected** — Phase 23C changed PHP only.

Evidence the churn was generated build noise, not source drift:

* every untracked path was inside `public/build/assets/` (verified before deletion);
* no non-`public/build` path was modified, deleted or untracked;
* Phase 23C changed no frontend source, so the committed artifacts remain a valid
  build of the current source.

Action taken: `git checkout -- public/build`, then removal of the 124 untracked
generated assets, restricted by an explicit `public/build/assets/` path check.
**Working tree is CLEAN.** No build artifact was staged or committed.

Note: the build is not bit-reproducible across runs (the same source produced no
diff after one build and 249 entries after another), so restoring the committed
artifacts is the correct recovery rather than committing churn.

## Meilisearch test/dev isolation — BLOCKED

The intended approach was a deterministic test index prefix
(`SCOUT_PREFIX=test_`). **It cannot be applied safely as a small change**, because
index configuration does not go through Scout at all:

```
app/Console/Commands/ConfigureMeilisearch.php
  L24  public const INDEX = 'listings';                  <-- hardcoded literal
  L30  new Client(env('MEILISEARCH_HOST'), env('MEILISEARCH_KEY'));   <-- raw client
  L35  $index = $client->index(self::INDEX);
  scout / prefix references in the command: 0
```

Consequences of simply setting `SCOUT_PREFIX=test_`:

1. Scout would write documents to `test_listings`.
2. `meilisearch:configure` would still configure **`listings`** — the DEVELOPMENT
   index — because the name is hardcoded and the raw client ignores Scout.
3. The test index would therefore have **no filterable attributes**, so every
   search test that uses `->where('status', …)` would fail with
   "attribute is not filterable".
4. Running that command during tests would **reconfigure the development index**,
   which is the opposite of isolation.

Nothing in the test bootstrap configures index settings today (verified by
searching `tests/` for `meilisearch:configure`, `ConfigureMeilisearch`,
`updateFilterable`, `SCOUT_PREFIX`), so the required attributes for an isolated
index cannot currently be established.

Also noted: `Category` defines **no** `searchableAs()`, so it relies on Scout's
default naming and would be affected by a prefix independently of
`ConfigureMeilisearch`.

Per this phase's own instruction — *"if environment-specific index isolation
cannot be established safely, stop and document the blocker rather than running
destructive commands against the shared index"* — **no isolation change was made.**

## Required work to close isolation (future phase)

1. Make `ConfigureMeilisearch` resolve the index name through Scout (or accept a
   name/prefix argument) so it can target the configured index.
2. Configure the test index during test bootstrap, guarded so it can never target
   the development index.
3. Add a guarded cleanup path so tests cannot flush development indexes.
4. Implement `Category::searchableAs()` explicitly, or confirm the default is
   intended.
5. Prove search, filter, sort, import, flush and cleanup all resolve to the test
   index, then verify against the real server.

## Verification performed

```
build churn restored:   yes  (public/build -> HEAD; 124 untracked removed)
working tree:           CLEAN
full suite:             NOT RE-RUN in this pass
```

The full suite passed at 1034 passed / 0 failed / 1 incomplete immediately before
this recovery (commit `2fb5cbd`). No source file was modified by this phase, so
that result stands for the code as committed.

---

# Phase 23C-R4 — Meilisearch Test-Index Isolation

**Status: COMPLETE.**

## Correction to 23C-R1 — the diagnosis was incomplete

23C-R1 concluded that the blocker was `ConfigureMeilisearch`: a hardcoded
`const INDEX = 'listings'` and a raw Meilisearch client that never consulted
Scout. That was real, but it was **not the whole defect, and not the most
important part of it**.

**23C-R1 missed `Listing::searchableAs()`.**

```
app/Models/Listing.php:392        return 'listings';        <-- bare literal
```

Scout's own default is `config('scout.prefix') . $this->getTable()`
(`vendor/laravel/scout/src/Searchable.php:385`). The override therefore
**silently discarded `scout.prefix`** — for the one entity that is actually
searchable. Setting `SCOUT_PREFIX` alone did nothing for `Listing`, which is
why 23C-R1's proposed step 1 ("make `ConfigureMeilisearch` resolve through
Scout") could never have been sufficient on its own.

There were **three** hardcoded/implicit sites, not one:

| # | Site | Defect |
|---|------|--------|
| 1 | `app/Models/Listing.php:392` | bare literal `'listings'` — discarded `scout.prefix` |
| 2 | `app/Console/Commands/ConfigureMeilisearch.php:24` | `const INDEX = 'listings'` plus a raw `Client` (L30-35) — **0** references to scout/prefix |
| 3 | `app/Models/Category.php` | no `searchableAs()` at all — implicit Scout default (the only site that did honour the prefix) |

23C-R1 also cited "nothing in the test bootstrap configures index settings" as
a reason to stop. That observation was correct, but the conclusion was not: it
is the reason the bootstrap had to be **written**, not a reason isolation was
impossible.

## What 23C-R1 also missed: a test run does not only WRITE the shared index

The shared index is not merely polluted with test documents. Because
`RefreshDatabase` runs `migrate:fresh`, the test database's auto-increment
restarts at 1 while the **development index still holds documents 1..N**. A
test that creates Listing id 2 in a non-discoverable state makes Scout issue a
**deletion** for document id 2 — a real development document.

Measured during this phase: one non-isolated run of
`tests/Feature/Images/ImagePipelineTest` (4 tests, each creating draft
Listings) produced four `documentDeletion` tasks against the development
`listings` index (`GET /tasks?indexUids=listings`), and the document count fell
**302 → 298**. Tests had been silently deleting real development documents.

## Architecture

### `app/Support/SearchIndexName.php` (new) — single source of truth

```php
SearchIndexName::listing();   // 'listings'      | 'test_listings'
SearchIndexName::category();  // 'categories'    | 'test_categories'
SearchIndexName::prefix();    // config('scout.prefix')
SearchIndexName::assertSafeForMutation(string $index);
```

The prefix is read from `config('scout.prefix')` — the **same** value Scout
itself uses — so there is exactly one place that decides which indexes this
application talks to, and `Listing` can no longer discard the prefix by writing
a literal. Because it is Scout's prefix, `SCOUT_PREFIX` alone is now
sufficient.

### The guard is fail-closed

```
assertSafeForMutation($index)
  always    : reject an empty / whitespace-only name
  testing   : reject 'listings' or 'categories'                (by NAME, not by prefix)
  testing   : reject anything not in managedIndexes()          (typo / stray index)
  otherwise : inert — meilisearch:configure MUST be able to target
              the development index
```

`prefix presence` is deliberately **not** the check. An empty prefix would make
`'listings'` look "prefixed"; the guard tests the **complete resolved name**
against the development names and against the managed set. If the prefix is
missing or wrong, the resolved name *is* a development name and the write is
refused.

There is no override. An escape hatch would have to admit a name the guard
exists to reject.

### The three sites are wired through it

* `Listing::searchableAs()` → `SearchIndexName::listing()`
* `Category::searchableAs()` → `SearchIndexName::category()` (new method)
* `ConfigureMeilisearch::targetIndex()` → `SearchIndexName::listing()`;
  `const INDEX` is **gone**, and the command now builds its client from
  `config('scout.meilisearch.*')` so it behaves under `config:cache`.

### One definition of the index settings

`ConfigureMeilisearch` exposes its vocabulary as
`filterableAttributes()` / `sortableAttributes()` / `rankingRules()` /
`searchableAttributes()`. The command and the test bootstrap read the **same**
methods; the previous duplication (attribute lists written once for the
`updateX()` call and again for the echo loop) is gone. The values are byte for
byte the Phase 13 ones — no ranking policy changed.

### `tests/bootstrap.php` (new) — guarded, ordered, and verified

phpunit.xml now uses `bootstrap="tests/bootstrap.php"`. It:

1. boots **only** `LoadEnvironmentVariables` + `LoadConfiguration` (booting the
   console kernel there installs Laravel's global error/exception handlers
   outside any test, so PHPUnit's per-test snapshot still contains them while
   the teardown removes them — and every single test is then reported as risky:
   *"Test code or tested code removed error handlers other than its own"*);
2. **fails closed** unless `APP_ENV=testing` — checked *first*, because outside
   `testing` the guard is deliberately inert and an unpinned run could
   reconfigure the development index;
3. resolves the index, and passes it through `assertSafeForMutation()` **before
   any client is constructed**;
4. writes the settings, **waits** for the Meilisearch tasks
   (`waitForCompletion`), and aborts on any task that is not `succeeded` —
   otherwise an early search test would fail with a confusing
   "attribute is not filterable".

`test_categories` is deliberately **not** configured. The development
`categories` index runs on Meilisearch defaults (no filterable/sortable
attributes, `searchableAttributes: ["*"]`) and nothing in `app/` searches
Category through Meilisearch — it is queried in SQL. A fresh `test_categories`
index already matches it exactly; writing the Listing vocabulary into it would
have *created* a divergence.

### `SearchConvergenceTest` — the one necessary assertion update

Two assertions pinned the literal `'listings'`. They now assert the resolved
name **and** the literal, which is strictly stronger:

```php
expect($listing->searchableAs())->toBe(SearchIndexName::listing());
expect($listing->searchableAs())->toBe('test_listings');   // phpunit.xml pins SCOUT_PREFIX=test_
```

Nothing was weakened, deleted or skipped.

## Out of scope (reported, not changed)

* `app/Console/Commands/UpdateMeilisearchSettings.php` still hardcodes
  `$indexName = 'businesses'` and drives a raw client. It is a **fourth**
  hardcoded-index site of the same family, but it targets the dead `businesses`
  index (Business is no longer searchable and the index does not exist on the
  running server). It never touches `listings` or `categories`, so it was left
  alone rather than silently widening this phase's blast radius.
* `scout:import` / `scout:flush` were **not** run against `listings` or
  `categories`. They route through Scout and are therefore prefix-aware, but
  exercising them was out of scope.
* The development `listings` index still holds documents written by
  pre-isolation runs, and lost 4 documents to the non-isolated reproduction
  described above. **Nothing was deleted, imported or reconfigured to repair
  this** — the phase forbids destructive commands against the development
  index. Cleanup is a separate, explicit decision.

## Verification performed

Ordered as the phase required.

### 1. Resolver + guard unit tests

```
php artisan test tests/Unit/Support/SearchIndexNameTest.php
  → 17 passed (41 assertions)
```

Includes the spy proof: with `SCOUT_PREFIX=test_` configured (so the *only*
reason to refuse is the name), `ConfigureMeilisearch::applyTo('listings', $spy)`
throws and the spy factory is **never invoked** — `constructions === 0` and the
index double recorded no writes. The anti-vacuous control is in the same file:
the same spy *does* fire once and records all four setting blocks for
`test_listings`.

### 2. Development index baseline (READ-ONLY)

Captured before any change, and again before and after the full suite, with GET
requests only (`/indexes`, `/indexes/{uid}/settings`, `/indexes/{uid}/stats`,
`/tasks`). No `scout:flush`, no `scout:import`, no deletion, no settings update
was ever issued against `listings` or `categories`.

```
php artisan test tests/Feature/Search
  → 123 passed (416 assertions)      [count unchanged; +2 assertions]
```

### 3. Full suite

```
php artisan test
  → 1049 passed, 2 failed, 1 incomplete  (3653 assertions, 568.83s)
```

Baseline arithmetic: the stated baseline is 1034 passed. 1034 + 17 new
resolver/guard tests = 1051, and 1049 passed + 2 failed = 1051. **No test was
lost, skipped or weakened.**

### 4. The two failures are PRE-EXISTING, not caused by this phase

`Tests\Feature\Images\ImagePipelineTest` — *"uploading a logo generates all
variants"* and *"deleting a listing image removes all variants from storage"*
fail with `Unable to find a file or directory at path [...jpg]`: the base image
is stored but the generated variant is not.

Reproduced **at HEAD with this phase's changes stashed**
(`git stash push -u`; tree pristine, `phpunit.xml` back to
`bootstrap="vendor/autoload.php"` and no `SCOUT_PREFIX`):

```
php artisan test tests/Feature/Images/ImagePipelineTest.php
  → 2 failed, 2 passed       (identical failures, identical paths)
```

It also fails when run alone under the new configuration, so it is neither
test-order pollution nor an index effect. It is an environment-dependent
image-pipeline defect that the stated 0-failure baseline does not reproduce
here. **This phase neither caused nor fixed it**, and it is left untouched.

### 5. Development indexes are provably untouched by a test run

```
snapshot (uid list + doc counts + full settings for listings and categories)
  before the full suite : storage/app/phase23c-r4/dev-index-now.json
  after  the full suite : storage/app/phase23c-r4/dev-index-after-full-suite.json
  comparison            : BYTE-IDENTICAL

Meilisearch tasks with uid > 79453 (the highest uid before the run):
  indexUids=listings    : NONE
  indexUids=categories  : NONE
  every task the run created is on test_listings or test_categories
```

The isolation is not inferred from the prefix — it is measured against the
server. The documents the suite created (and the deletions that, before this
phase, silently deleted real development documents) all landed on
`test_listings` / `test_categories`.

### 6. Frontend build

```
npm run build : PASS      (vite client 7.97s + vite SSR 3.31s)
```

This phase changes no frontend source, so the build is a regression check only.

The build is **not** bit-reproducible and churned exactly **249** tracked
`public/build` entries (124 deletions, 1 modified `manifest.json`, 124
untracked) — the same figure 23C-R1 recorded. `git status` was inspected: every
untracked path was verified to be under `public/build/` (plus the one intended
new file, `tests/bootstrap.php`) before anything was removed, then
`git checkout -- public/build` and `git clean -fdq -- public/build` restored the
committed artifacts. **No build artifact was staged or committed.**

Note: `npm.ps1` cannot run under this machine's execution policy; the build was
run through `npm.cmd`. In the confined sandbox the Vite/esbuild build also fails
with `spawn EPERM` because esbuild launches its helper over piped stdio — that
is a sandbox boundary, not a project defect.

### 7. Working tree

```
git diff --check : clean
```

---

# Phase 23H — Listing Child → Search Synchronization

## The rule

> **A persisted mutation of a child whose data appears in
> `Listing::toSearchableArray()` synchronizes the owning Listing's search
> document.**

Previously the document only followed the **Listing's own** save, so
`DATABASE STATE ≠ SEARCH INDEX STATE` for child changes. Phase 23G measured it on
the live index: attaching a category and creating a service left
`category_ids: []` and `services_names: []` indefinitely.

## Categories

`Listing::categories()` now declares `->using(ListingCategoryPivot::class)`.
Laravel only fires pivot MODEL events for a relation declared with `->using()`;
otherwise `attach()` bulk-inserts and `detach()` bulk-deletes through the query
builder and fire nothing (and Laravel 12 does **not** fire
`pivotAttached`/`pivotDetached` at all). `ListingCategoryPivotObserver` therefore
covers attach, detach, sync, syncWithoutDetaching and toggle through one path.

## Services

`ListingServiceObserver` covers create, update, delete, restore and forceDelete
for `services_names`.

## Additional children found by the audit

`toSearchableArray()` publishes three more child-derived groups, all of which
were unsynchronized and are now covered:

| Document fields | Child | New boundary |
|---|---|---|
| `city_id`, `region_id`, `country_id`, `city`, `region`, `country`, `address`, `is_open_now` | `Location` | `LocationObserver` |
| `has_active_subscription` | owner's `Subscription` | `SubscriptionObserver` (extended) |
| `rating`, `reviews_count` | the Listing's approved `Review`s | `ReviewObserver` (extended) |

Images, contacts, favourites and analytics are **not** in the document, so no
synchronization is required — a deliberate decision, not an omission.

## Synchronization mechanism

`App\Support\ListingSearchSync` is the single explicit entry point: it evaluates
`shouldBeSearchable()` and then calls `searchable()` or `unsearchable()`. There
is **no `->touch()`** and no arbitrary column write — search synchronization is
an explicit act, not a side effect of a timestamp — and it is the **only**
`->searchable()` call site in `app/` outside a comment. The LISTING is always the
path, never the Business, so a Business-less Professional behaves identically.

Child observers live at the model boundary, never in controllers, so a future
mutation path cannot silently bypass indexing.

## Transaction / queue behaviour

Scout indexing is **synchronous** here (`scout.queue` false, `after_commit`
false); this phase adds no queue. Child mutations can fire inside a
`DB::transaction`, and the sync reads the same connection, so the normal path
publishes the correct new state — but a **rollback** would leave the index ahead
of the database. That exposure is pre-existing (Scout already indexes a Listing
inside a transaction) and changing it would alter every Scout write, so it is
documented rather than redesigned.

Known bulk-query operations that cannot fire model events — the Admin business
restore/forceDelete and the Owner business deletion — now call
`ListingSearchSync::forListingIds()` explicitly.

## Tests

`tests/Feature/Search/ListingChildSearchSyncTest.php` — 13 tests, 39 assertions,
asserting real index behaviour (categories), the document itself (location,
subscription, review) and the canonical visibility contract. Reverting only the
two wiring edits makes **8 of them fail**, proving they test the defect rather
than the implementation.

Full record: `docs/PHASE_23H_LISTING_CHILD_SEARCH_SYNCHRONIZATION.md`.
