<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Skip a test if Meilisearch isn't available.
 * Useful for tests that need Scout to work with a real index.
 */
function skip_if_no_meilisearch(): void
{
    if (!config('scout.meilisearch.host')) {
        test()->markTestSkipped('Meilisearch not configured');
    }
}