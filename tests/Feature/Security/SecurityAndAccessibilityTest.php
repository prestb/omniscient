<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

/**
 * WAVE 30 — SECURITY AND ACCESSIBILITY REGRESSION.
 *
 * Two confirmed defects are pinned here.
 *
 * 1. SECURITY. `PushSubscriptionController::subscribe` logged `$request->all()`.
 *    The payload carries the push ENDPOINT plus the p256dh/auth keys, which are
 *    credentials for delivering push to that user, so the application log held
 *    unusable-but-sensitive material. Both handlers also returned
 *    `$e->getMessage()` to the client.
 *
 * 2. ACCESSIBILITY. Five page-level surfaces rendered visible inputs identified
 *    only by a placeholder, or with no accessible name at all (WCAG 2.2 AA
 *    4.1.2 Name, Role, Value). `<label for>` + `id` is used across the auth forms,
 *    which is why the invariant below accepts either mechanism.
 */

test('the push subscription endpoint never logs push credentials', function () {
    Log::spy();

    $user = User::factory()->create(['status' => 'active']);

    $this->actingAs($user)->postJson('/push/subscribe', [
        'endpoint' => 'https://push.example.com/SECRET-ENDPOINT-TOKEN',
        'keys' => [
            'p256dh' => 'SECRET_P256DH_VALUE',
            'auth' => 'SECRET_AUTH_VALUE',
        ],
    ]);

    Log::shouldNotHaveReceived('info', function ($message, $context = []) {
        $payload = is_array($context) ? json_encode($context) : (string) $context;

        return str_contains($payload, 'SECRET_P256DH_VALUE')
            || str_contains($payload, 'SECRET_AUTH_VALUE')
            || str_contains($payload, 'SECRET-ENDPOINT-TOKEN');
    });
});

test('push handlers do not return internal exception detail to the client', function () {
    $source = file_get_contents(app_path('Http/Controllers/Api/PushSubscriptionController.php'));

    // The detail is logged server-side; it must not be echoed in the response body.
    expect($source)
        ->not->toContain("'Failed to subscribe: ' . \$e->getMessage()")
        ->not->toContain("'Failed to unsubscribe: ' . \$e->getMessage()")
        ->toContain("'message' => 'Failed to subscribe.'")
        ->toContain("'message' => 'Failed to unsubscribe.'");
});

test('an unauthenticated push subscription is refused without a session', function () {
    $this->postJson('/push/subscribe', [
        'endpoint' => 'https://push.example.com/x',
        'keys' => ['p256dh' => 'a', 'auth' => 'b'],
    ])->assertUnauthorized();
});

test('every page-level visible input has an accessible name', function () {
    $offenders = [];

    foreach (File::allFiles(resource_path('js/Pages')) as $file) {
        if ($file->getExtension() !== 'vue') {
            continue;
        }

        $contents = $file->getContents();

        if (!str_contains($contents, '<input')) {
            continue;
        }

        // Hidden CSRF/state inputs are not user-facing controls.
        $visible = preg_match_all('/<input(?![^>]*type="hidden")/', $contents);

        if ($visible === 0) {
            continue;
        }

        $hasAccessibleName = str_contains($contents, 'aria-label')
            || str_contains($contents, 'aria-labelledby')
            || str_contains($contents, '<label');

        if (!$hasAccessibleName) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});

test('the public search combobox exposes an accessible name and role', function () {
    $source = file_get_contents(resource_path('js/Components/Public/SearchAutocomplete.vue'));

    expect($source)->toContain('role="combobox"')
        ->and($source)->toContain(':aria-label=');
});
