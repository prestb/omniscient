<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 20 — CORE PRODUCT READINESS: THE ENTRY JOURNEY.
 *
 * THE BUG THIS FILE EXISTS TO PREVENT:
 *
 *   RegisteredUserController called `event(new Registered($user))`, which sends
 *   the verification email INLINE. With a real SMTP transport that failed, the
 *   exception escaped and the user got a 500 — account row created, nobody able
 *   to get in. Registration was completely broken in production.
 *
 *   The suite never caught it because the test environment uses the array/log
 *   mail transport, so the failing path was never exercised.
 *
 * Therefore these tests use a GENUINELY BROKEN SMTP transport rather than
 * Mail::fake(). A fake proves nothing about a real mailer outage.
 */

/** Point the mailer at a closed port so the transport really throws. */
function brokenMailer(): void
{
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
        'mail.mailers.smtp.username' => null,
        'mail.mailers.smtp.password' => null,
        'mail.mailers.smtp.encryption' => null,
        'mail.mailers.smtp.timeout' => 1,
    ]);
}

function validRegistration(array $overrides = []): array
{
    return array_merge([
        'name' => 'New Person',
        'email' => 'new' . random_int(10000, 99999) . '@example.com',
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
        'terms' => '1',
        'account_type' => 'user',
    ], $overrides);
}

// ── THE REGRESSION: registration must survive a dead mailer ──────────────────

test('registration succeeds even when the mail transport fails', function () {
    brokenMailer();

    $payload = validRegistration();

    $this->post('/register', $payload)->assertRedirect(route('user.dashboard'));

    $user = User::where('email', $payload['email'])->first();
    expect($user)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('registration succeeds for every account type when mail is down', function (string $type, string $route) {
    brokenMailer();

    $payload = validRegistration(['account_type' => $type]);

    $this->post('/register', $payload)->assertRedirect(route($route));
    $this->assertNotNull(User::where('email', $payload['email'])->first());
})->with([
    ['user', 'user.dashboard'],
    ['professional', 'owner.listings.create'],
    ['owner', 'owner.businesses.create'],
]);

test('a mail failure does not leave the account unusable', function () {
    brokenMailer();

    $payload = validRegistration();
    $this->post('/register', $payload)->assertRedirect();

    // The person is genuinely signed in and can reach their own area.
    $user = User::where('email', $payload['email'])->first();
    $this->actingAs($user)->get('/user/dashboard')->assertOk();
});

// ── The three personas actually get in ──────────────────────────────────────

test('a normal user registers, is authenticated, gets the right role and lands correctly', function () {
    $payload = validRegistration();

    $this->post('/register', $payload)->assertRedirect(route('user.dashboard'));

    $user = User::where('email', $payload['email'])->first();
    expect($user->role)->toBe(User::ROLE_USER);
    $this->assertAuthenticatedAs($user);
});

test('a professional registers with no business and lands on listing creation', function () {
    $payload = validRegistration(['account_type' => 'professional']);

    $this->post('/register', $payload)->assertRedirect(route('owner.listings.create'));

    $user = User::where('email', $payload['email'])->first();
    expect($user->role)->toBe(User::ROLE_OWNER);
    // The architecture permits a discoverable account with no organization.
    expect(Business::where('owner_id', $user->id)->count())->toBe(0);
});

test('a business owner registers and lands on business creation', function () {
    $payload = validRegistration(['account_type' => 'owner']);

    $this->post('/register', $payload)->assertRedirect(route('owner.businesses.create'));

    $user = User::where('email', $payload['email'])->first();
    expect($user->role)->toBe(User::ROLE_OWNER);
});

// ── Validation must be visible and safe ─────────────────────────────────────

test('missing fields produce visible validation errors', function () {
    $this->post('/register', ['account_type' => 'user'])
        ->assertSessionHasErrors(['name', 'email', 'password', 'terms']);
});

test('an invalid email and a weak password are rejected with reasons', function () {
    $this->post('/register', validRegistration([
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]))->assertSessionHasErrors(['email', 'password']);
});

test('a duplicate email is rejected', function () {
    $existing = User::factory()->create(['email' => 'taken@example.com']);

    $this->post('/register', validRegistration(['email' => $existing->email]))
        ->assertSessionHasErrors('email');
});

test('a password mismatch is rejected', function () {
    $this->post('/register', validRegistration(['password_confirmation' => 'Different!Pass1']))
        ->assertSessionHasErrors('password');
});

test('an unaccepted terms checkbox blocks registration', function () {
    $this->post('/register', validRegistration(['terms' => '0']))
        ->assertSessionHasErrors('terms');
});

// ── Security: no privilege escalation through the public form ───────────────

test('a registration request cannot assign a privileged role', function (string $role) {
    $payload = validRegistration(['role' => $role, 'account_type' => 'user']);

    $this->post('/register', $payload);

    $user = User::where('email', $payload['email'])->first();

    if ($user !== null) {
        // A role supplied in the payload is ignored; account_type is the only
        // public lever, and it maps only to ROLE_USER or ROLE_OWNER.
        expect($user->role)->toBe(User::ROLE_USER);
    } else {
        expect(true)->toBeTrue();
    }
})->with(['admin', 'super_admin']);

test('an unknown account type is rejected outright', function () {
    $this->post('/register', validRegistration(['account_type' => 'super_admin']))
        ->assertSessionHasErrors('account_type');
});

// ── Password policy is documented, not assumed ──────────────────────────────

test('the password policy requirements are recorded', function () {
    $payload = validRegistration(['password' => 'password123', 'password_confirmation' => 'password123']);

    // A password many real users would choose is rejected, and the reason is
    // surfaced. The strictness is a known drop-off risk, recorded here so it is
    // a deliberate product decision rather than an invisible one.
    $this->post('/register', $payload)->assertSessionHasErrors('password');
});
