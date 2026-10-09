# Phase 23C-R5 — Meilisearch Index-Safety Audit (READ-ONLY)

**Status: COMPLETE. Decision: NO-GO for Phase 24 — one blocker (F1).**

This phase changed **no code, no configuration, no index, and no git state.** It
executed **no test suite** (running it triggers the Phase 23C-R4 bootstrap's
settings write). It did not push, did not repair the development index, and did
not broaden 23C-R4 into a general search redesign.

---

## 1. Repository state — PASS

| Check | Result |
|---|---|
| Branch | `phase-11-resume` |
| HEAD | `0f48985997a8403b0b3d96d34516f56282dbe3fa` (the 23C-R4 slice-2 commit) |
| Working tree | CLEAN — no tracked, untracked or stashed changes |
| `origin/phase-11-resume` (local tracking ref) | `5f1d6ba6559eb94d272cf4f7b95b2c12e5e7ad6f` |
| Divergence | **0 behind / 10 ahead** — the tracking ref is stale exactly as recorded; nothing was synced backwards |
| `git show --check` on both 23C-R4 commits | clean |
| Live remote tip | **UNVERIFIED.** `git ls-remote origin refs/heads/phase-11-resume` fails with `schannel: AcquireCredentialsHandle failed: SEC_E_NO_CREDENTIALS`. Divergence is therefore measured against the local tracking ref only, and current CI status on the remote could not be observed. |
| Pushed | No. All 10 commits are local-only. |
| `bootstrap/cache/config.php` | absent — no frozen `SCOUT_PREFIX` |

## 2. Committed 23C-R4 implementation review — PASS

Read from the committed tree, not from the phase report.

* `SearchIndexName::prefix()` reads `config('scout.prefix')`. A repository-wide
  grep confirms this is the **only** reader of `scout.prefix` in `app/` and
  `config/` besides `config/scout.php:32` itself.
* `assertSafeForMutation()` refuses an empty/whitespace name unconditionally,
  refuses the development names **by name** while `APP_ENV=testing`, and then
  refuses anything outside `managedIndexes()`. Fail-closed; no override exists.
* `Listing::searchableAs()` → `SearchIndexName::listing()`;
  `Category::searchableAs()` → `SearchIndexName::category()`. Verified with
  `git show HEAD:<path>`.
* `phpunit.xml` is well-formed and pins `SCOUT_PREFIX=test_` with `force="true"`.

## 3. Index-mutation path inventory

Only **two** `new Client(` sites exist anywhere in `app/`. Every Scout-driven
path resolves the index through `indexableAs()` → `searchableAs()` →
`SearchIndexName`.

| Path | Index resolution | Guarded | Dev-index risk |
|---|---|---|---|
| `ConfigureMeilisearch` (raw client) | `SearchIndexName::listing()` | yes — guard runs **before** client construction | none |
| `tests/bootstrap.php` (raw client) | resolver + guard + `APP_ENV` precheck | yes — two-phase, ordered | none |
| Scout `saved` / `deleted` / `restored` / `forceDeleted` (Searchable trait) on `Listing`, `Category` | `MeilisearchEngine` L71 / L172 → `indexableAs()` | prefix-aware | none |
| `scout:flush` | `FlushCommand` → `removeAllFromSearch()` → engine L489 | prefix-aware | none |
| `scout:import` | model `indexableAs()` | prefix-aware | none |
| `scout:delete-index <name>` | class → `indexableAs()`; else `prefix . $name` | no | operator-invoked only |
| `scout:delete-all-indexes` | vendor: deletes every index whose uid `startsWith(scout.prefix)` | no | **dev prefix `''` matches EVERY index** (F4) |
| `scout:sync-index-settings` | class → `indexableAs()`; else `prefix . $name` | no | config key is the literal `businesses` (F3) |
| `UpdateMeilisearchSettings` (raw client) | hardcoded `'businesses'` | no | none — dead index (F2) |

Corroborating negatives:

* The only registered model observers are `ReviewObserver` and
  `SubscriptionObserver`; neither touches Scout.
* No `scout:*` command appears in `app/Console/Kernel.php`, `routes/console.php`
  or any seeder. `DirectoryTestSeeder` only **prints** a `scout:import` hint as
  a string.
* The only `Artisan::call` sites are `cache:clear`, `optimize:clear` and
  `queue:restart`.
* `.github/workflows/*` contain zero `scout` / `meili` references.
* Only two models use the `Searchable` trait: `Listing` and `Category`.

## 4. Test bootstrap review — PASS

The ordering is correct and load-bearing: `APP_ENV` precheck → guard → client
construction. The minimal bootstrap (`LoadEnvironmentVariables` +
`LoadConfiguration`) installs no global handlers, verified as the reason the
suite is not reported risky. Settings tasks are waited on and asserted
`succeeded`. The write is idempotent, so parallel runs are safe.

`test_categories` being unconfigured is **correct**, not an omission: the
development `categories` index and `test_categories` both carry Meilisearch's
defaults (`filterable=[] sortable=[] searchable=[*]`), confirmed read-only
against the server.

## 5. The two image failures — PASS

Recorded as pre-existing in
`docs/PHASE_23_SEARCH_DISCOVERY_REFINEMENT.md` (section *"Verification
performed"* → *"4. The two failures are PRE-EXISTING"*), including the
`git stash push -u` reproduction and its output.

`Tests\Feature\Images\ImagePipelineTest` is not touched by 23C-R4:
`git diff 1bdd277..HEAD` lists no image, media or storage file. The saved run
artifact records `Tests: 2 failed, 1 incomplete, 1049 passed (3653 assertions)`.
**The subsystem was not changed.**

## 6. Findings

### F1 — BLOCKER: 23C-R4 breaks `php artisan test` in the CI configuration

* `.github/workflows/tests.yml` declares **only a MySQL service**, runs
  `cp .env.ci .env`, then `php artisan test`. There is no Meilisearch service.
* `.env.ci` sets `SCOUT_DRIVER=null` and `MEILISEARCH_HOST=` — CI deliberately
  runs with no search engine.
* `tests/bootstrap.php:100` calls `ConfigureMeilisearch::applyTo($index, null, true)`
  **unconditionally**: no driver check, no host check, no reachability check and
  no `try/catch`.
* `MEILISEARCH_HOST=` makes the Dotenv option *present with an empty value*, so
  `PhpOption\Option::fromValue('')` yields `Some('')` and
  `env('MEILISEARCH_HOST', 'http://localhost:7700')` returns `''`, **not** the
  default.
* `Meilisearch\Http\Client::__construct` accepts `''` without validation (the
  non-empty-string constraint is docblock-only), so the failure surfaces on the
  first settings request — a URI error, or a connection refused.

The exception escapes the PHPUnit bootstrap, so **the run aborts before a single
test executes**.

23C-R4's verification never exercised this path: its full-suite runs used the
local `.env` with a live Meilisearch. The workflow triggers only on `main` and
`develop`, so the failure has not surfaced yet; it will on merge. Current CI
status could not be observed (see §1).

This does **not** endanger the development index — it fails closed, just
catastrophically.

*Recommended direction (NOT implemented in this audit):* skip the settings write
when the engine is unusable — e.g. when `scout.driver` is not `meilisearch`, or
when `config('scout.meilisearch.host')` is empty — mirroring the intent of
`skip_if_no_meilisearch()` in `tests/Pest.php`, **which is defined and never
called anywhere**. Open question for the fix: whether the 123 `tests/Feature/Search`
cases actually pass under `SCOUT_DRIVER=null`. That is currently untested and
must not be assumed.

### F2 — `UpdateMeilisearchSettings` is an unguarded raw-client mutation

Hardcoded `$indexName = 'businesses'`, its own `Client`, no resolver, no guard,
and it targets an index that no longer exists (Business is not searchable). It
cannot reach `listings` or `categories`, so it is not a 23C-R4 index-safety
defect — but it is the same defect family. Already documented as out of scope.

### F3 — `config/scout.php` still ships an `index-settings.businesses` block

`scout:sync-index-settings` would configure the literal `businesses` in
development / `test_businesses` under test, bypassing the resolver entirely.
Pre-existing, dead, unguarded.

### F4 — `scout:delete-all-indexes` is catastrophic in development

Vendor behaviour: every index whose uid `startsWith(config('scout.prefix'))` is
deleted. In development the prefix is `''`, which matches **everything** —
`listings` and `categories` included. Under test it is safely scoped to
`test_*`. Pre-existing vendor semantics, not introduced by 23C-R4, but no guard
covers it.

### F5 — The guard protects the settings-write path only

`scout:flush`, `scout:import` and `scout:delete-index` are prefix-aware but
unguarded. Under the pinned test configuration they resolve to test indexes; the
`phpunit.xml` pin plus the bootstrap's `APP_ENV` check are what keep them safe.

### F6 — No host allow-list

`APP_ENV=testing` with `SCOUT_PREFIX=test_` writes to `test_listings` on
**whatever host** `MEILISEARCH_HOST` names, including a production Meilisearch.
Unlikely for a local suite, but nothing prevents it.

### F7 — Independent count corroboration (partial)

Static count of `tests/Feature/Search`: **85 test declarations**, expanding to
the recorded **123 cases** through Pest `->with()` datasets. Consistent, but the
suite was not re-executed (that would write to the test index), so the 123 figure
rests on the committed evidence artifact rather than on a fresh run.

## 7. Live index evidence (read-only GETs only)

```
indexes              categories, listings, test_categories, test_listings
doc counts           categories 72 | listings 298 | test_categories 54 | test_listings 299

tasks with uid > 79453 on listings   : NONE
tasks with uid > 79453 on categories : NONE

dev   listings settings : unchanged (filterable + ranking match the captured baseline)
test_ listings settings : canonical (match the development settings exactly)
categories / test_categories : both on Meilisearch defaults (filterable=[], sortable=[], searchable=[*])
```

No `scout:flush`, `scout:import`, index deletion or settings update was issued
against `listings` or `categories` at any point in this audit.

## 8. Decision

**Index safety: PASS.**

No unresolved index-safety issue remains. The development indexes are provably
untouched since the 23C-R4 marker task, every Scout path for the only two
searchable models resolves through `SearchIndexName`, and the guard runs before
any client exists on both mutation paths that 23C-R4 owns.

**Phase 24 readiness: NO-GO.**

Recorded blocker: **F1**. 23C-R4 as committed makes `php artisan test` fail at
bootstrap under the CI environment, so Phase 24 would be built on a baseline that
cannot pass its own pipeline. Phase 24 should begin only after F1 is fixed — a
small, bounded change — **and the fix is verified against the CI configuration,
not only against the local `.env`.**

F2–F4 are recorded for an explicit decision, not silently accepted: none endanger
`listings` or `categories`, and none should be repaired inside a search phase
without a deliberate scope call. F5 and F6 are accepted residual risk.

## 9. Audit limitations

1. The live remote tip could not be read (git credential failure), so true remote
   divergence and current CI status are unknown; only the local tracking ref was
   measurable.
2. The suite was not re-executed, by design, to keep the audit read-only. All
   test-result figures are quoted from the committed evidence artifact
   `storage/app/phase23c-r4/full-suite-run-2.txt`.
3. F1 is derived from the workflow and `.env.ci` contents plus vendor source
   reading; it was **not** observed by running the suite under `.env.ci`, because
   that would have written to the Meilisearch test index and violated the
   read-only constraint. The mechanism is deterministic but is reported as
   reasoned rather than executed.
