<?php

namespace Tests\Unit;

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

    /** The guard must refuse a database that is not recognisably a test database. */
    public function test_guard_rejects_the_development_database(): void
    {
        $this->assertGuardRejects('omniscient');
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
        $originalEnv = $_ENV['DB_DATABASE'] ?? null;
        $originalServer = $_SERVER['DB_DATABASE'] ?? null;
        $originalGetenv = getenv('DB_DATABASE');

        try {
            if ($database === null) {
                unset($_ENV['DB_DATABASE'], $_SERVER['DB_DATABASE']);
                putenv('DB_DATABASE');
            } else {
                $_ENV['DB_DATABASE'] = $database;
                $_SERVER['DB_DATABASE'] = $database;
                putenv('DB_DATABASE=' . $database);
            }

            $callback();
        } finally {
            if ($originalEnv === null) {
                unset($_ENV['DB_DATABASE']);
            } else {
                $_ENV['DB_DATABASE'] = $originalEnv;
            }

            if ($originalServer === null) {
                unset($_SERVER['DB_DATABASE']);
            } else {
                $_SERVER['DB_DATABASE'] = $originalServer;
            }

            if ($originalGetenv === false) {
                putenv('DB_DATABASE');
            } else {
                putenv('DB_DATABASE=' . $originalGetenv);
            }
        }
    }

    private function invokeGuard(): void
    {
        $method = new ReflectionMethod(TestCase::class, 'guardUnsafeTestDatabase');
        $method->setAccessible(true);
        $method->invoke($this);
    }
}
