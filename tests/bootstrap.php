<?php

/**
 * PHASE 23C-R4 — GUARDED TEST-INDEX BOOTSTRAP.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS FILE EXISTS
 * ─────────────────────────────────────────────────────────────────────────
 * Isolating the suite behind `SCOUT_PREFIX=test_` is NOT enough on its own.
 * Meilisearch does not infer index settings from the documents it receives: a
 * freshly created `test_listings` index has NO filterable attributes, so every
 * `->where('status', 'published')` throws "attribute is not filterable".
 *
 * Before this phase NOTHING in `tests/` configured index settings — there was
 * no `meilisearch:configure`, no `ConfigureMeilisearch` reference and no
 * `updateFilterable` call anywhere under `tests/`. Flipping the prefix alone
 * therefore broke every search test.
 *
 * This bootstrap closes that gap: once, before any test runs, it writes the
 * canonical settings to the resolved TEST index and waits for Meilisearch to
 * finish the tasks.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ORDER OF OPERATIONS — THIS IS THE WHOLE POINT
 * ─────────────────────────────────────────────────────────────────────────
 * The settings write is an index MUTATION. If it ever resolved to `listings`
 * it would reconfigure the DEVELOPMENT index — silently, successfully, and in
 * exactly the way this phase exists to prevent. So the file is ordered:
 *
 *   1. the application MUST be running as `testing` — checked FIRST, because
 *      outside `testing` `SearchIndexName::assertSafeForMutation()` is
 *      deliberately inert (`php artisan meilisearch:configure` must still be
 *      able to target the development index);
 *   2. if no Meilisearch engine is in use the write is SKIPPED entirely (see
 *      the PHASE 23C-R5 note at that check) — there is then no index to
 *      configure and no shared index to protect;
 *   3. the resolved index name MUST pass the fail-closed guard, which refuses
 *      the development names outright and refuses anything the current
 *      configuration does not manage — a name that merely looks prefixed is
 *      not proof of safety.
 *
 * A host that IS configured but unreachable still aborts. The Meilisearch
 * client is only constructed after every check above passes, and an unfinished
 * or FAILED settings task aborts the run rather than letting tests fail later
 * with a confusing "attribute is not filterable".
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY ONLY THESE TWO BOOTSTRAPPERS
 * ─────────────────────────────────────────────────────────────────────────
 * `LoadEnvironmentVariables` + `LoadConfiguration` are exactly what is needed
 * (`config('scout.*')` and `app()->environment()`), and nothing more.
 * Booting the CONSOLE KERNEL here would install Laravel's global error and
 * exception handlers OUTSIDE any test; PHPUnit takes its per-test snapshot
 * with those already on the stack and then reports every single test as risky
 * ("Test code or tested code removed error handlers other than its own").
 * The minimal bootstrap keeps global state untouched.
 *
 * `test_categories` is deliberately NOT configured. The development
 * `categories` index runs on Meilisearch's own defaults (no filterable or
 * sortable attributes, searchableAttributes `["*"]`), and nothing in `app/`
 * searches Category through Meilisearch — it is queried through SQL. A fresh
 * `test_categories` index therefore already matches it exactly; writing the
 * Listing vocabulary into it would CREATE a difference rather than preserve
 * one.
 */

use App\Console\Commands\ConfigureMeilisearch;
use App\Support\SearchIndexName;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$app->bootstrapWith([
    LoadEnvironmentVariables::class,
    LoadConfiguration::class,
]);

// A bootstrapped app is what every later check depends on, so prove it early.
if (! function_exists('config')) {
    throw new \RuntimeException('PHASE 23C-R4: the test bootstrap could not boot configuration.');
}

// ── FAIL CLOSED (1): the suite is really running as tests ───────────────────
if (! $app->environment('testing')) {
    throw new \RuntimeException(
        'PHASE 23C-R4: the test bootstrap refuses to configure a Meilisearch index while '
        . "APP_ENV is '{$app->environment()}', not 'testing'. Outside the test environment "
        . 'the index-name guard is intentionally inert so `meilisearch:configure` can still '
        . 'target the development index — which means an unpinned run here could reconfigure '
        . 'it. Set APP_ENV=testing in phpunit.xml.'
    );
}

// ── SKIP when there is no Meilisearch engine to configure ──────────────────
//
// PHASE 23C-R5 — F1. This bootstrap used to perform its settings write
// unconditionally, which broke `php artisan test` in the CI configuration:
// `.env.ci` sets `SCOUT_DRIVER=null` (which Laravel resolves to a genuine NULL
// driver, NOT the `meilisearch` default, so Scout uses NullEngine) and
// `MEILISEARCH_HOST=` (which resolves to the EMPTY string, NOT the
// `http://localhost:7700` default), while the workflow declares no Meilisearch
// service. The write therefore threw
// `GuzzleHttp\Exception\RequestException: URI must include a scheme and host`
// and the run aborted BEFORE A SINGLE TEST EXECUTED.
//
// With no Meilisearch engine in use there is nothing to configure and no
// shared index to protect — the isolated index only matters BECAUSE Scout will
// talk to it. Skipping is not a weakening: under a null driver Scout writes
// nowhere, and with no host it cannot write at all.
//
// A host that IS configured but UNREACHABLE still aborts, deliberately: you
// asked for Meilisearch, so the suite must not silently run against an index
// whose settings were never applied. That is the ordering trap this file
// exists to close.
$driver = config('scout.driver');
$host = config('scout.meilisearch.host');

if (! ConfigureMeilisearch::hasUsableEngine()) {
    fwrite(STDOUT, sprintf(
        'PHASE 23C-R4: skipping test-index configuration — no Meilisearch engine in use '
        . '(scout.driver=%s, scout.meilisearch.host=%s). No index was read or written.'
        . PHP_EOL,
        var_export($driver, true),
        var_export($host, true)
    ));

    return;
}

// ── FAIL CLOSED (2): the resolved name is a test index ─────────────────────
$index = SearchIndexName::listing();

SearchIndexName::assertSafeForMutation($index);

// ── APPLY, WAIT, AND VERIFY ────────────────────────────────────────────────
$tasks = ConfigureMeilisearch::applyTo($index, null, true);

foreach ($tasks as $task) {
    if (($task['status'] ?? null) !== 'succeeded') {
        throw new \RuntimeException(
            "PHASE 23C-R4: a settings task for '{$index}' did not succeed: " . json_encode($task)
        );
    }
}

fwrite(STDOUT, "PHASE 23C-R4: configured test index '{$index}' (" . count($tasks) . " settings tasks)." . PHP_EOL);
