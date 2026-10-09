<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * PHASE 22D — FAIL-CLOSED TEST DATABASE GUARD.
     *
     * `RefreshDatabase` runs `migrate:fresh`, which DROPS EVERY TABLE in the
     * connected database. Before Phase 22D, `phpunit.xml` set `APP_ENV=testing`
     * but no DB overrides, and `.env.testing` does not exist — so a local test
     * run loaded `.env` and pointed that destructive migration at the
     * DEVELOPMENT database `omniscient`.
     *
     * `phpunit.xml` now pins `DB_DATABASE=omniscient_testing`. This guard is the
     * second line of defence: if the name is ever absent, or does not look like a
     * test database, the suite aborts BEFORE `parent::setUp()` boots the app and
     * therefore before any migration can run.
     *
     * The check reads raw environment variables deliberately. It must run before
     * the application boots, so `config()` is not available yet — and by the time
     * it would be, `migrate:fresh` has already executed.
     */
    protected function setUp(): void
    {
        $this->guardUnsafeTestDatabase();

        parent::setUp();
    }

    /**
     * Abort unless the configured database is recognisably a test database.
     *
     * Fail-closed by design: there is deliberately NO override. An escape hatch
     * would have to admit a name the guard exists to reject — including
     * `omniscient` itself — and would therefore be either redundant (for a
     * `_testing` name) or dangerous (for anything else).
     */
    private function guardUnsafeTestDatabase(): void
    {
        $database = self::envValue('DB_DATABASE');

        if ($database === null || $database === '') {
            throw new RuntimeException(
                'PHASE 22D: no test database is configured, so tests would run against '
                . 'whatever `.env` points at. Refusing to continue, because '
                . 'RefreshDatabase runs `migrate:fresh`. Set DB_DATABASE in phpunit.xml.'
            );
        }

        if (! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                "PHASE 22D: refusing to run tests against database '{$database}'. "
                . 'Test databases must be named with a `_testing` suffix, because '
                . 'RefreshDatabase drops and recreates every table. Set '
                . 'DB_DATABASE=omniscient_testing in phpunit.xml.'
            );
        }
    }

    /** Read an environment variable without booting the application. */
    private static function envValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? (getenv($key) ?: null);

        return is_string($value) ? $value : null;
    }
}
