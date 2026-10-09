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
     * It checks EVERY environment source, not the first one found, because the
     * application reads them in a different order than this guard used to. See
     * {@see self::databaseNameCandidates()} for the measurement that forced it.
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
     * Abort unless EVERY source of DB_DATABASE names a test database.
     *
     * Fail-closed by design: there is deliberately NO override. An escape hatch
     * would have to admit a name the guard exists to reject — including
     * `omniscient` itself — and would therefore be either redundant (for a
     * `_testing` name) or dangerous (for anything else).
     *
     * PHASE 22D — every source is checked, not the first one found, because the
     * application does NOT read them in the order this guard used to. See
     * {@see self::databaseNameCandidates()}.
     */
    private function guardUnsafeTestDatabase(): void
    {
        $candidates = self::databaseNameCandidates();

        if ($candidates === []) {
            throw new RuntimeException(
                'PHASE 22D: no test database is configured, so tests would run against '
                . 'whatever `.env` points at. Refusing to continue, because '
                . 'RefreshDatabase runs `migrate:fresh`. Set DB_DATABASE in phpunit.xml.'
            );
        }

        foreach ($candidates as $source => $database) {
            if (! str_ends_with($database, '_testing')) {
                throw new RuntimeException(
                    "PHASE 22D: refusing to run tests against database '{$database}' "
                    . "(read from {$source}). Test databases must be named with a `_testing` "
                    . 'suffix, because RefreshDatabase drops and recreates every table. Set '
                    . 'DB_DATABASE=omniscient_testing in phpunit.xml and make sure no ambient '
                    . 'DB_DATABASE overrides it.'
                );
            }
        }
    }

    /**
     * Every source the application could resolve DB_DATABASE from, keyed by
     * where the value came from.
     *
     * PHASE 22D — WHY EVERY SOURCE INSTEAD OF THE FIRST.
     *
     * The application resolves the name through Laravel's immutable Dotenv
     * repository, which reads `$_SERVER` BEFORE `$_ENV`. A guard that reads only
     * `$_ENV ?? $_SERVER ?? getenv()` can therefore be shown a DIFFERENT value
     * from the one the application will actually use.
     *
     * That is not theoretical. Adding `force="true"` to phpunit.xml's
     * DB_DATABASE makes PHPUnit write the pinned name into `$_ENV` and
     * `putenv()` while `$_SERVER` keeps the ambient one
     * (vendor/phpunit/phpunit/src/TextUI/Configuration/PhpHandler.php writes
     * only those two). Measured: with `DB_DATABASE=omniscient` exported, the
     * guard passed and the application resolved to the DEVELOPMENT database —
     * so `migrate:fresh` would have dropped every table in it. Checking every
     * source removes that entire class of divergence.
     *
     * @return array<string, string>
     */
    private static function databaseNameCandidates(): array
    {
        $getenv = getenv('DB_DATABASE');

        $sources = [
            '$_ENV' => $_ENV['DB_DATABASE'] ?? null,
            '$_SERVER' => $_SERVER['DB_DATABASE'] ?? null,
            'getenv()' => $getenv === false ? null : $getenv,
        ];

        return array_filter(
            $sources,
            static fn ($value): bool => is_string($value) && $value !== ''
        );
    }
}
