# Phase 23C-R5 — Meilisearch Index-Safety Audit (READ-ONLY)

**Status: COMPLETE. Final decision: GO for Phase 24 — see §11.5.**

> **Follow-ups.** F1 was reproduced by execution, fixed and verified — **§10**.
> A separate, **pre-existing** CI defect (F8) was then found, which kept the
> verdict at NO-GO. Option (b) was taken and CI now runs a real Meilisearch —
> **§11** — so F8 is closed and the verdict is **GO**, conditional only on the
> first CI run on a branch the workflow actually triggers. Finally, a scope call
> cleared the two dead-code findings, **F2 and F3 — §12**.

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

### F8 — PRE-EXISTING: the one live-search test cannot pass under the CI driver

Found during the F1 follow-up (§10.5). `tests/Feature/Listing/ListingLifecycleTest:267`
is the only call in the entire suite that reaches a real search engine. Under
`SCOUT_DRIVER=null` it resolves to `NullEngine` and fails. Introduced in
`d432897` (Phase 11 Wave 1D-2), long before 23C-R4; the guard written for exactly
this situation, `skip_if_no_meilisearch()` in `tests/Pest.php`, has been present
since `0e1546b` and is **never called**. Not introduced by 23C-R4, but it means
the pipeline is red for a second and older reason.

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
   reading. It was **not** observed by running the suite under `.env.ci`, because
   that would have written to the Meilisearch test index and violated the
   read-only constraint. **This limitation was removed by the follow-up in §10,
   which reproduced F1 by execution and then fixed it.**

## 10. Follow-up — F1 reproduced, fixed and verified

Recorded after the audit proper. This section is not read-only: it documents a
code change.

### 10.1 F1 reproduced by execution (not reasoning)

A temporary probe (gitignored, since deleted) seeded `APP_ENV=testing`,
`SCOUT_PREFIX=test_`, `SCOUT_DRIVER=null`, `MEILISEARCH_HOST=` into
`putenv()` + `$_ENV` + `$_SERVER` — exactly how PHPUnit's `<env>` tags win over
an immutable Dotenv repository — and then ran the real `tests/bootstrap.php`.

```
scout.driver           = NULL
scout.prefix           = 'test_'
scout.meilisearch.host = ''
SearchIndexName::listing() = 'test_listings'

PROBE RESULT: bootstrap THREW GuzzleHttp\Exception\RequestException
PROBE MESSAGE: URI must include a scheme and host. Use an absolute URI,
               a network-path reference starting with //, or configure a base_uri.
```

Two mechanisms the audit had asserted from source reading are now confirmed by
execution:

* `SCOUT_DRIVER=null` resolves to a **genuine NULL driver**, not the
  `meilisearch` default. `Env::getOption()` maps the literal `null` to `null`
  via a bare `return;`, and `PhpOption\Some::map()` wraps that as `Some(null)`
  whose `getOrCall()` returns the value — so the default is never consulted.
  `EngineManager::getDefaultDriver()` then returns `'null'` → `NullEngine`.
* `MEILISEARCH_HOST=` resolves to the **empty string**, not the
  `http://localhost:7700` default, for the same reason in reverse.

### 10.2 The fix

`ConfigureMeilisearch::hasUsableEngine()` was added — a small, unit-testable
predicate — and `tests/bootstrap.php` now skips its settings write when it is
false:

```php
public static function hasUsableEngine(): bool
{
    $host = config('scout.meilisearch.host');

    return config('scout.driver') === 'meilisearch'
        && is_string($host)
        && trim($host) !== '';
}
```

The ordering is unchanged: `APP_ENV` must still be `testing` **first**, then the
engine check, then the fail-closed name guard, and only then is a client
constructed. A host that is configured but **unreachable** is deliberately not
covered by the skip — that is a real misconfiguration and must fail loudly.

### 10.3 Verified by execution, four cases

| Case | `scout.driver` | `scout.meilisearch.host` | Result |
|---|---|---|---|
| A — the CI condition | `null` | `''` | **SKIP**, returns normally (was a hard crash) |
| B — Meilisearch in use, server down | `meilisearch` | unreachable | **THROWS** `CommunicationException: cURL error 7` |
| C — Meilisearch selected, no host | `meilisearch` | `''` | **SKIP** |
| D — normal developer run | `meilisearch` | live | **WRITE** — `configured test index 'test_listings' (4 settings tasks)` |

Case B is the important negative control: the fix does not turn a genuine
misconfiguration into a silent skip.

### 10.4 Regression coverage

Six tests were added to `tests/Unit/Support/SearchIndexNameTest.php` pinning the
predicate, including the exact CI pair (`driver = null`, `host = ''`). The file
already covered `ConfigureMeilisearch::targetIndex()` and `applyTo()`, so this
extends an existing home rather than creating a new one. **23 passed** (was 17).

### 10.5 The residual, PRE-EXISTING CI defect — F8

Removing the bootstrap abort does **not** by itself make CI green. The whole test
suite contains exactly **one** call that reaches a real search engine:

```
tests/Feature/Listing/ListingLifecycleTest.php:267
    $ids = Listing::search($marker)->take(5)->get()->pluck('id');
```

Under `SCOUT_DRIVER=null` that resolves to `NullEngine`, returns nothing, and
the test fails. This is **not** caused by 23C-R4 or by this fix:

* the test was introduced in `d432897` (Phase 11 Wave 1D-2), long before 23C-R4;
* `skip_if_no_meilisearch()` in `tests/Pest.php` — which guards on exactly
  `! config('scout.meilisearch.host')` — was added in the repository's first
  commit `0e1546b` and is **never called anywhere**.

The 123 `tests/Feature/Search` cases are unaffected: none of them performs a
live search, which is why they pass under any driver.

Two honest options, neither taken here because both change CI or test scope:

* **(a)** call the existing `skip_if_no_meilisearch()` in that one test — uses a
  mechanism the repository already provides, and skips only where there is
  provably no engine; or
* **(b)** add a Meilisearch service container to `.github/workflows/tests.yml`
  and stop setting `SCOUT_DRIVER=null` in `.env.ci`, so CI exercises the same
  engine the local suite does.

Option (b) gives real coverage at the cost of CI complexity and an image the
pipeline has never used. Option (a) is one line but leaves that behaviour
unverified anywhere.

### 10.6 Full-suite regression evidence (after the fix)

```
php artisan test
  → 1055 passed, 2 failed, 1 incomplete  (3664 assertions, 584.16s)
```

Arithmetic: 1049 passed before the fix + 6 new predicate tests = 1055. The same
two pre-existing `ImagePipelineTest` failures, unchanged and untouched.

Development indexes, measured against a marker task uid captured before the run
(`uid 80283`, highest after the run `81042` — 759 tasks):

```
tasks with uid > 80283 on listings   : NONE
tasks with uid > 80283 on categories : NONE
counts  before 298 / 72   after 298 / 72
```

Every one of those 759 tasks landed on `test_listings` / `test_categories`. The
F1 fix did not weaken isolation.

### 10.7 Updated decision

**Index safety: PASS** (unchanged).
**F1: RESOLVED and verified by execution against the CI condition.**
**F8: NEW — pre-existing, not introduced by 23C-R4.**

**Phase 24 readiness: still NO-GO, on F8 alone.** The 23C-R4 regression is gone,
but `php artisan test` in the CI configuration still fails on
`ListingLifecycleTest`, so the pipeline remains red for a different and older
reason. Phase 24 should begin once F8 has an explicit decision — (a), (b), or a
recorded acceptance that CI does not exercise live search.

> **Superseded by §11**, which takes option (b): CI now runs a real Meilisearch,
> so F8 is closed as well.

## 11. CI completion — option (b)

`.env.ci` was changed by the repository owner to `SCOUT_DRIVER=meilisearch` and
`MEILISEARCH_HOST=http://localhost:7700`. On its own that made CI **worse**, not
better: `hasUsableEngine()` became true, so the bootstrap attempted a write
against a host with no server and the suite aborted with `cURL error 7` instead
of the previous URI error. F1 was *relocated*, not resolved. The change is only
correct once a Meilisearch actually exists in CI.

### 11.1 What was completed

| File | Change |
|---|---|
| `.github/workflows/tests.yml` | a `meilisearch` service, `getmeili/meilisearch:v1.53.1` — the version the local suite runs against; job-level `MEILISEARCH_KEY`; an explicit "Wait for Meilisearch to be ready" step; scout config added to the existing diagnostic |
| `.env.ci` | `DB_USERNAME`/`DB_PASSWORD` restored to `ci_user` / `ci_password`; the real master key replaced |
| `.env.ci` | `MEILISEARCH_KEY=ci-meilisearch-master-key` — a non-secret literal, never a real key |

### 11.2 Three problems the `.env.ci` edit had introduced, now closed

1. **No server.** The workflow declared only a MySQL service. Now it declares a
   Meilisearch pinned to `v1.53.1` (`GET /version` on the local instance returns
   `1.53.1`), with a health check and an explicit readiness poll, because GitHub
   treats a service's health options as advisory.
2. **Contradictory database credentials.** `.env.ci` had been changed to
   `root` with an empty password while the service sets `MYSQL_ROOT_PASSWORD:
   root` and creates `ci_user` / `ci_password`. `php artisan migrate --force`
   and the suite would have failed authentication. Restored to match the
   service.
3. **A real credential in a tracked file.** `.env.ci` is tracked, and it briefly
   held the same master key as the local `.env`. It was still only in the
   working tree, so nothing reached history: searching every tracked blob at
   HEAD for the full credential returns **no match**, and no commit message
   contains it either. The local `.env` (gitignored) keeps the real key.
   (Deliberately not quoting the search pattern here — doing so would make the
   check match its own description.)

The master key is supplied to **both** the service and the test process from one
expression:

```yaml
${{ secrets.MEILI_MASTER_KEY || 'ci-meilisearch-master-key' }}
```

so the two cannot disagree. The literal fallback matters: without it, an unset
secret would leave the *service* keyless while the *application* still sent
`.env.ci`'s key, and the two states would depend on undocumented behaviour about
how Meilisearch treats an `Authorization` header when no master key is set. The
fallback removes that question. Setting the repository secret `MEILI_MASTER_KEY`
is all that is needed to use a real key.

This also fixes the secret leak in the diagnostic step: it dumps `.env` and now
prints only `key_set` as a boolean, never the key.

### 11.3 Verification performed

```
Symfony YAML parse of tests.yml           : OK
  services                                : mysql, meilisearch
  job env                                 : MEILISEARCH_KEY
  steps                                   : 16 (was 15)
  meilisearch image                       : getmeili/meilisearch:v1.53.1
  app key == service key expression       : yes
  mysql service env                       : unchanged

Dotenv parse of .env.ci                   : OK
  APP_ENV=testing, DB_USERNAME=ci_user, DB_PASSWORD=ci_password,
  DB_DATABASE=omniscient_testing, SCOUT_DRIVER=meilisearch,
  MEILISEARCH_HOST=http://localhost:7700, MEILISEARCH_KEY=ci-meilisearch-master-key

real master key in tracked files          : NONE (every blob at HEAD searched
                                            for the full credential; no commit
                                            message contains it either)

bootstrap branch under the new CI config  : hasUsableEngine() = true
  (probe, read-only — no index write)     : resolved index 'test_listings'
                                          : guard PASSES
```

The write path itself was already proven end-to-end in §10.3 case D, which wrote
four settings tasks to `test_listings` against a real Meilisearch.

### 11.4 What could NOT be verified, and the honest limit

* **The container has never run.** GitHub Actions cannot be executed from here,
  and the workflow triggers only on `push`/`pull_request` to **`main` and
  `develop`** — this branch is `phase-11-resume`, and nothing was pushed. So the
  image, its health check, and the `secrets` context inside
  `jobs.<job_id>.services.*.env` are **reviewed by reading, not observed**. The
  first run on `main`/`develop` is the real test.
* **The DB credentials are correct only against the declared service.** They were
  restored to `ci_user` / `ci_password` to match it; if the service definition
  changes, `.env.ci` must change with it.
* `curl` is assumed present in the `getmeili/meilisearch` image, which is what
  Meilisearch's own Docker Compose example uses.

### 11.5 Final decision

**Index safety: PASS** — unchanged, and re-confirmed after the fix in §10.6.
**F1: RESOLVED** — gate added, four outcomes verified by execution.
**F8: CLOSED** — CI now runs a real Meilisearch, so
`ListingLifecycleTest:267` has an engine instead of `NullEngine`.
**F2–F4 remain recorded, not fixed**; they do not endanger `listings` or
`categories`. **F5–F6 remain accepted residual risk.**

> **Superseded by §12:** F2 and F3 were subsequently cleaned up.

**Phase 24 readiness: GO.**

The only outstanding item is administrative, not technical: the CI change must be
confirmed by the first run on a branch the workflow actually triggers. That is a
green-light condition, not a blocker — the local suite is green (1055 passed, 2
pre-existing `ImagePipelineTest` failures), isolation is measured, and nothing is
pushed.

If the first CI run fails on the container itself, the fallback is already
available and requires no code change: `skip_if_no_meilisearch()` in
`tests/Pest.php` for the one live-search test, plus the `hasUsableEngine()` skip
that §10.2 already added.

## 12. Deferred cleanup — F2 and F3 closed

An explicit scope call was made to clear the two findings that were dead code
rather than index-safety defects. Both had **zero live references** before the
change, verified first.

### 12.1 F3 — the stale `index-settings.businesses` block (removed)

`config/scout.php` held one live `meilisearch.index-settings` key, `businesses`.
Nothing about it was true any more: Business does not use the `Searchable` trait
(`0` matches in `app/Models/Business.php`), the index does not exist on the
server, and `scout:sync-index-settings` resolves a key through `indexableAs()`
**only when the key is a model class** — a bare string becomes `prefix . name`
directly, so this was the last hardcoded index name in configuration.

Proven by execution, before and after:

```
before : would target  'businesses' (dev) / 'test_businesses' (test)
after  : php artisan scout:sync-index-settings
         → "No index settings found for the \"meilisearch\" engine."
         → task uid 81046 before and after: NO MEILISEARCH TASK CREATED
```

The block is replaced by a comment stating the invariant — **live keys must be
model classes** — so the removal cannot be silently undone.

### 12.2 F2 — the dead `UpdateMeilisearchSettings` command (deleted)

`app/Console/Commands/UpdateMeilisearchSettings.php` hardcoded
`$indexName = 'businesses'`, built its own raw `Client`, and carried a comment
claiming *"must match ConfigureMeilisearch exactly. Both commands write to the
same index; whichever runs last wins."* That was false: it wrote to
`businesses`, never to `listings`. A dead command that documents itself as
configuring the discovery index is precisely the kind of footgun this whole line
of work exists to remove, so it was deleted rather than repaired.

Nothing referenced it — no scheduler entry, no `Artisan::call`, no test, no
deployment script (the repository has no ops scripts at all). It was auto-loaded
by `$this->load(__DIR__.'/Commands')`, so deletion needed no registration
change. `php artisan list` still shows `meilisearch:configure`; the removed
`meilisearch:update-settings` now fails loudly as an unknown command instead of
silently writing to an index nobody reads.

### 12.3 Regression guard

`tests/Unit/Support/SearchIndexNameTest.php` gains
`test_no_configured_index_settings_bypass_the_resolver()`, which asserts the
`businesses` key is absent and that every live key is a model class — the exact
invariant F3 violated. It extends the file that already covers
`ConfigureMeilisearch`, rather than creating a new home.

### 12.4 Verification

```
unit tests            : 24 passed (54 assertions)      [was 23]
php artisan list      : meilisearch:configure present, meilisearch:update-settings gone
scout:sync-index-settings : no-op, zero Meilisearch tasks
full suite            : 1056 passed, 2 failed, 1 incomplete   (see §12.5)
development indexes   : unchanged - see §12.5
```

### 12.5 Full-suite and index evidence

```
php artisan test  →  1056 passed, 2 failed, 1 incomplete  (3666 assertions)
```

1055 before the cleanup + 1 new guard test = 1056. The same two pre-existing
`ImagePipelineTest` failures, untouched.

Development indexes, marker uid 81046 before the run (81805 after — 759 tasks,
every one of them on `test_listings` / `test_categories`):

```
tasks on listings   : NONE
tasks on categories : NONE
counts              : 298 / 72 before and after
```

### 12.6 Final state after the cleanup

| Finding | Status |
|---|---|
| F1 — bootstrap aborted with no Meilisearch engine | **RESOLVED** (§10) |
| F8 — one live-search test under a null driver | **CLOSED** (§11) |
| F2 — dead `UpdateMeilisearchSettings` command | **CLOSED** (§12.2) |
| F3 — stale `index-settings.businesses` key | **CLOSED** (§12.1), guarded (§12.3) |
| F4 — `scout:delete-all-indexes` deletes everything under an empty prefix | **RECLASSIFIED by 23F — see §13** |
| F5 — `scout:flush` / `import` / `delete-index` are prefix-aware but unguarded | **RECLASSIFIED by 23F — see §13** |
| F6 — no host allow-list for `APP_ENV=testing` | **RECLASSIFIED by 23F — see §13** |

**Phase 24: GO**, unchanged, still conditional only on the first CI run on a
branch the workflow triggers.

## 13. Phase 23F — F4 / F5 / F6 re-audited at a later HEAD

Re-derived against `fc77a41` (after 23C-R1, 23C-R4, 23C-R5, 23D and 23E) from the
current code and the live server, rather than carried forward from the text
above. **No production code was changed as a result.**

### F4 — `scout:delete-all-indexes` deletes every index under an empty prefix

**Classification: ENVIRONMENT-ONLY (vendor-intentional). Not a code defect.**

Mechanically still true: `MeilisearchEngine::deleteAllIndexes()` deletes every
index whose uid `startsWith(config('scout.prefix'))`, and the development prefix
is still `''`. But:

* the command's **name and its behaviour agree** — it deletes all indexes;
* **nothing in the application invokes it**: no `$schedule->command`, no
  `Artisan::call`, no seeder, no CI step — only documentation mentions it;
* the hazard needs a Meilisearch server **shared with other applications**.
  Every index on this server belongs to this application, so "delete all"
  removes exactly this application's own indexes.

R5's word *"catastrophic"* over-stated it. Residual recorded, not fixed: on a
**shared** server with an empty prefix it would delete other applications'
indexes. That is operational guidance, not code.

### F5 — `scout:flush` / `import` / `delete-index` are prefix-aware but unguarded

**Classification: INTENTIONAL / accepted residual. Not a code defect.**

Re-derived: **none of these commands can produce an empty or unsafe index name.**

* `scout:flush` and `scout:import` take a **model class** and resolve through
  `searchableAs()` → `App\Support\SearchIndexName`, which returns `prefix . base`
  and can never be empty.
* `scout:delete-index <name>` resolves a bare string through `prefix . $name`,
  short-circuiting when the name is already prefixed. Under the pinned test
  configuration, `scout:delete-index listings` therefore targets `test_listings`,
  **not** the development index.
* `SearchIndexName::resolve()` always appends a non-empty base, so an empty
  resolved name is unreachable.

The guard's absence is real but **unreachable**. `assertSafeForMutation()` would
be a no-op for these commands in development — where mutating the development
index is the entire point of `scout:flush` — and under testing the `phpunit.xml`
pin already forces `test_*`. An event-level guard was judged speculative: new
surface to prevent a state no supported invocation can reach.

### F6 — no host allow-list

**Classification: INTENTIONAL (accepted residual). Not a code defect.**

`APP_ENV=testing` with `SCOUT_PREFIX=test_` writes to `test_listings` on whatever
host `MEILISEARCH_HOST` names — the same class of misconfiguration as pointing a
test suite at a production database, and not made worse by the search layer. A
host allow-list is new infrastructure, which Phase 23F's brief forbids.

### 13.1 Verified intact — no regression

| Contract | Evidence |
|---|---|
| **Index naming** (23C-R4) | `Listing`/`Category` → `SearchIndexName`; the only prefix reader in `app/`/`config/` is `SearchIndexName::prefix()`; no hard-coded index literal remains |
| **Mutation safety** | `ConfigureMeilisearch::applyTo()` guards **before** constructing a client; `scout:sync-index-settings` is a true no-op (empty `index-settings`) |
| **22D isolation** | `phpunit.xml` still pins `DB_DATABASE=omniscient_testing`; the hardened every-source guard is intact (`tests/TestCase.php`) |
| **23D resilience** | `SearchController::index` still wraps only the engine pipeline, logs, and returns an empty paginator with HTTP 200 |
| **23E contract** | `/search` still emits the flattened paginator; no `meta` key |
| **Searchable document** | `toSearchableArray()` exposes no secrets, no contacts, no owner identity and no unused columns; it carries only public discovery fields |
| **Bulk import** | `makeAllSearchableUsing()` restricts `scout:import` to published, unhidden rows |
| **Visibility transitions** | `ModelObserver::saved()` removes the document when `shouldBeSearchable()` becomes false, and `deleted()` removes it on delete — **now proven against the real server** by three new tests (§13.2) |

### 13.2 New coverage added by 23F

`ListingIndexVisibilityTest` asserts the **model hook** and deliberately needs no
running Meilisearch, so it never proved the **index** actually loses the
document. `tests/Feature/Listing/ListingLifecycleTest.php` gains three
live-server tests: an unpublished, a hidden and a deleted Listing must each be
**removed** from `test_listings`, not merely filtered out of queries. All three
pass (0.7s each). This converts the 23C visibility contract from
vendor-reading into measured evidence.

### 13.3 Environment observation — Meilisearch instance replaced

While auditing, the local Meilisearch was found to have been **replaced since
23E**: the task counter had reset from ~81,800 to 846, the **`listings` index no
longer exists**, and a **`businesses` index (3 documents, dated 2026-09-03)** has
reappeared — a leftover from before Business stopped being searchable. The
`categories` index holds **71 documents dated 2026-09-03 while the development
`categories` table is EMPTY**, i.e. it is stale orphan state that nothing
consumes and nothing reconciles.

This is an **environment change, not a code defect**, and nothing in this phase
created or repaired it. It is recorded because it materially changes what
"the development index" means for future verification. In particular, the three
new removal tests pass because the suite targets `test_listings`; the **absent
development `listings` index** is unrelated to them.

### 13.4 The two ImagePipelineTest failures now PASS — and that is not 23F's doing

§4 above records two `Tests\Feature\Images\ImagePipelineTest` failures as
pre-existing and environment-dependent. **They now pass**, and the full suite is
green: `1 incomplete, 1078 passed (3804 assertions)` with exit code 0.

Verified in isolation: `ImagePipelineTest` → **4 passed (18 assertions)**.

This phase changed **no** image, media or storage code, so it neither caused nor
fixed them. They were **environmental**, and the same environment rebuild noted
in §13.3 repaired them. Recorded here so a future reader does not chase a defect
that no longer reproduces — and so that 23F does not take credit for it.

Arithmetic is consistent: 23E's `1073 passed + 2 failed` becomes
`1075 passed`, plus 23F's three new visibility-removal tests = **1078**.
