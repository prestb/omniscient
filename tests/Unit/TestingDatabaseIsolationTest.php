<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * PHASE 22D — TEST ENVIRONMENT ISOLATION VERIFICATION.
 *
 * This test lives in `tests/Unit` and deliberately does NOT use
 * `RefreshDatabase`. It therefore cannot invoke `migrate:fresh`, which is the
 * whole point of the phase: proving the guard works without risking the very
 * destructive operation being guarded against.
 *
 * Booting the application is safe — Laravel connections are lazy, and nothing
 * here issues a query.
 */
class TestingDatabaseIsolationTest extends TestCase
{
    /**
     * The resolved connection must be the dedicated test database, not the
     * development database named in `.env`.
     */
    public function test_resolved_test_database_is_isolated_from_development(): void
    {
        $database = config('database.connections.mysql.database');

        $this->assertSame(
            'omniscient_testing',
            $database,
            'Tests must resolve to the isolated test database.'
        );

        $this->assertNotSame(
            'omniscient',
            $database,
            'Tests must never resolve to the development database: RefreshDatabase '
            . 'runs migrate:fresh and would drop every table in it.'
        );
    }

    /**
     * PHASE 22D — the LIVE connection, not just the configuration array.
     *
     * The assertions above read `config()`. This one asks the connection object
     * the application actually runs queries through, which is what
     * `RefreshDatabase`'s `migrate:fresh` will target.
     */
    public function test_the_live_connection_resolves_to_the_test_database(): void
    {
        $this->assertSame(
            'omniscient_testing',
            DB::connection()->getDatabaseName(),
            'The live database connection must point at the isolated test database.'
        );

        $this->assertNotSame(
            'omniscient',
            DB::connection()->getDatabaseName(),
            'The live connection must never point at the development database.'
        );
    }

    /** The guard must refuse a database that is not recognisably a test database. */
    public function test_guard_rejects_the_development_database(): void
    {
        $this->assertGuardRejects('omniscient');
    }

    /**
     * PHASE 22D — THE DIVERGENCE CASE.
     *
     * The application resolves DB_DATABASE through Laravel's immutable Dotenv
     * repository, which reads `$_SERVER` BEFORE `$_ENV`. The guard used to read
     * them in the opposite order, so a source that said "testing" while
     * `$_SERVER` said "omniscient" made the guard pass while the application
     * used the DEVELOPMENT database.
     *
     * Reproduced in practice by adding `force="true"` to phpunit.xml's
     * DB_DATABASE: PHPUnit then writes the pinned name to `$_ENV` and
     * `putenv()` and leaves `$_SERVER` alone. This test pins the fix.
     */
    public function test_guard_rejects_when_any_single_source_names_the_development_database(): void
    {
        $this->withDivergentSources(
            env: 'omniscient_testing',
            server: 'omniscient',
            getenv: 'omniscient_testing',
            callback: function (): void {
                $this->expectException(RuntimeException::class);
                $this->expectExceptionMessage('PHASE 22D');

                $this->invokeGuard();
            }
        );
    }

    /** And symmetrically, when only `getenv()` disagrees. */
    public function test_guard_rejects_when_only_getenv_names_the_development_database(): void
    {
        $this->withDivergentSources(
            env: 'omniscient_testing',
            server: 'omniscient_testing',
            getenv: 'omniscient',
            callback: function (): void {
                $this->expectException(RuntimeException::class);
                $this->expectExceptionMessage('PHASE 22D');

                $this->invokeGuard();
            }
        );
    }

    /** A missing name must fail closed rather than fall back to whatever `.env` says. */
    public function test_guard_rejects_an_absent_database_name(): void
    {
        $this->assertGuardRejects(null);
    }

    /** A `_testing` name must be accepted. */
    public function test_guard_accepts_a_testing_suffixed_database(): void
    {
        $this->withDatabaseName('omniscient_testing', function (): void {
            $this->invokeGuard(); // must not throw
            $this->assertTrue(true);
        });
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function assertGuardRejects(?string $database): void
    {
        $this->withDatabaseName($database, function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('PHASE 22D');

            $this->invokeGuard();
        });
    }

    /**
     * Temporarily override DB_DATABASE for the duration of a callback.
     *
     * The guard reads the environment on every call, so this exercises the real
     * production code path rather than a reimplementation of it.
     */
    private function withDatabaseName(?string $database, callable $callback): void
    {
        $this->withDivergentSources($database, $database, $database, $callback);
    }

    /**
     * Set each DB_DATABASE source independently; `null` unsets that source and
     * everything is restored afterwards. Lets a test reproduce the divergence
     * between `$_ENV` and `$_SERVER` that the application can see.
     */
    private function withDivergentSources(
        ?string $env,
        ?string $server,
        ?string $getenv,
        callable $callback
    ): void {
        $originalEnv = $_ENV['DB_DATABASE'] ?? null;
        $originalServer = $_SERVER['DB_DATABASE'] ?? null;
        $originalGetenv = getenv('DB_DATABASE');

        try {
            $this->applyDatabaseName($env, $server, $getenv);

            $callback();
        } finally {
            $this->applyDatabaseName(
                $originalEnv,
                $originalServer,
                $originalGetenv === false ? null : $originalGetenv
            );
        }
    }

    /** Write (or unset) each environment source. */
    private function applyDatabaseName(?string $env, ?string $server, ?string $getenv): void
    {
        if ($env === null) {
            unset($_ENV['DB_DATABASE']);
        } else {
            $_ENV['DB_DATABASE'] = $env;
        }

        if ($server === null) {
            unset($_SERVER['DB_DATABASE']);
        } else {
            $_SERVER['DB_DATABASE'] = $server;
        }

        if ($getenv === null) {
            putenv('DB_DATABASE');
        } else {
            putenv('DB_DATABASE=' . $getenv);
        }
    }

    private function invokeGuard(): void
    {
        $method = new ReflectionMethod(TestCase::class, 'guardUnsafeTestDatabase');
        $method->setAccessible(true);
        $method->invoke($this);
    }
}
