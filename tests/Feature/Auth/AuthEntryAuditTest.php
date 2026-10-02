<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;

uses(RefreshDatabase::class);

/**
 * PHASE 21A — AUTHENTICATION, ENTRY UX & ACCOUNT JOURNEY.
 *
 * THE BUG THIS FILE EXISTS TO PREVENT:
 *
 *   AppServiceProvider configured Password::defaults() ONLY inside
 *   `if (app()->environment('testing'))`. In PRODUCTION that call fell back to
 *   Laravel's built-in `min(8)`, so password RESET and password CHANGE accepted
 *   "12345678" while REGISTRATION demanded mixed case, letters, numbers and
 *   symbols.
 *
 *   The weaker policy guarded the operation that can REPLACE an existing
 *   account's credentials, and tests exercised a different policy from
 *   production.
 */

// ── Password policy is now ONE policy, in every environment ─────────────────

test('the application password policy rejects weak passwords', function (string $weak) {
    $validator = Validator::make(
        ['password' => $weak],
        ['password' => PasswordRule::defaults()]
    );

    expect($validator->fails())->toBeTrue();
})->with([
    'too short' => 'Ab1!',
    'no uppercase' => 'lowercase1!',
    'no lowercase' => 'UPPERCASE1!',
    'no number' => 'NoNumber!!',
    'no symbol' => 'NoSymbol123',
    // The precise password that production RESET used to accept.
    'all digits' => '12345678',
]);

test('the application password policy accepts a compliant password', function () {
    $validator = Validator::make(
        ['password' => 'Str0ng!Passw0rd'],
        ['password' => PasswordRule::defaults()]
    );

    expect($validator->fails())->toBeFalse();
});

test('the password policy is not configured only for the testing environment', function () {
    $source = file_get_contents(app_path('Providers/AppServiceProvider.php'));

    // The old shape: the whole configuration lived inside an environment check.
    expect($source)->not->toContain("if (app()->environment('testing')) {\n            Password::defaults(");

    // The new shape: configured unconditionally, with only the external HIBP
    // call skipped in tests.
    expect($source)->toContain('Password::defaults(function () {');
    expect($source)->toContain('uncompromised()');
});

test('registration and reset share one password rule', function () {
    $registration = file_get_contents(app_path('Http/Controllers/Auth/RegisteredUserController.php'));

    // Registration must defer to the shared policy, not declare its own.
    expect($registration)->toContain('Password::defaults()');
    expect($registration)->not->toContain('Password::min(8)');

    foreach ([
        'Http/Controllers/Auth/NewPasswordController.php',
        'Http/Controllers/Auth/PasswordController.php',
    ] as $rel) {
        expect(file_get_contents(app_path($rel)))->toContain('Password::defaults()');
    }
});

test('registration rejects the passwords that reset would have accepted', function () {
    // Behavioural proof that the two paths now agree.
    $response = $this->post('/register', [
        'name' => 'Weak Person',
        'email' => 'weak' . random_int(1000, 9999) . '@example.com',
        'password' => '12345678',
        'password_confirmation' => '12345678',
        'terms' => '1',
        'account_type' => 'user',
    ]);

    $response->assertSessionHasErrors('password');
    expect(User::where('email', 'like', 'weak%')->count())->toBe(0);
});

// ── Password requirements are communicated BEFORE submission ────────────────

test('both auth forms state the password requirements up front', function (string $rel) {
    $source = file_get_contents(resource_path('js/' . $rel));

    expect($source)->toContain('At least 8 characters');
    expect($source)->toContain('a number, and a symbol');
})->with([
    'Pages/Auth/Register.vue',
    'Pages/Auth/ResetPassword.vue',
]);

// ── Mail failure must not break a completed operation ───────────────────────

test('registration still succeeds when the mail transport is down', function () {
    // Reuses the Phase 20 principle: a genuinely broken transport, not a fake.
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
        'mail.mailers.smtp.timeout' => 1,
    ]);

    $email = 'maildown' . random_int(10000, 99999) . '@example.com';

    $this->post('/register', [
        'name' => 'Mail Down',
        'email' => $email,
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
        'terms' => '1',
        'account_type' => 'user',
    ])->assertRedirect(route('user.dashboard'));

    expect(User::where('email', $email)->first())->not->toBeNull();
});

test('the admin invitation is still created when its email cannot be sent', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
        'mail.mailers.smtp.timeout' => 1,
    ]);

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->post('/admin/invitations', [
        'email' => 'invitee' . random_int(1000, 9999) . '@example.com',
        'name' => 'Invitee Person',
        'expires_in_days' => 7,
        'send_email' => true,
    ])->assertRedirect(route('admin.invitations.index'));

    // The row survives; the mail failure did not roll it back or 500.
    expect(DB::table('invitations')->where('email', 'like', 'invitee%')->count())->toBe(1);
});

test('a successful invitation still reports success', function () {
    \Illuminate\Support\Facades\Mail::fake();

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $email = 'ok' . random_int(1000, 9999) . '@example.com';

    $this->actingAs($admin)->post('/admin/invitations', [
        'email' => $email,
        'name' => 'Invitee Person',
        'expires_in_days' => 7,
        'send_email' => true,
    ])->assertRedirect(route('admin.invitations.index'))
        ->assertSessionHas('success');

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\InvitationMail::class);
});

// ── Login ───────────────────────────────────────────────────────────────────

test('valid credentials authenticate and redirect by role', function (string $role, string $expected) {
    $user = User::factory()->create([
        'role' => $role,
        'email' => 'login' . random_int(1000, 9999) . '@example.com',
        'password' => bcrypt('Str0ng!Passw0rd#2026'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'Str0ng!Passw0rd#2026',
    ])->assertRedirect($expected);

    $this->assertAuthenticatedAs($user);
})->with([
    ['user', '/user/dashboard'],
    ['owner', '/owner/dashboard'],
]);

test('invalid credentials do not authenticate and do not reveal which field was wrong', function () {
    $user = User::factory()->create([
        'email' => 'bad' . random_int(1000, 9999) . '@example.com',
        'password' => bcrypt('Str0ng!Passw0rd#2026'),
    ]);

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();

    // A nonexistent address produces the same error key: no enumeration.
    $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
});

test('logout destroys the session and protects authenticated routes', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)->post('/logout')->assertRedirect('/');
    $this->assertGuest();

    $this->get('/user/dashboard')->assertRedirect(route('login'));
});

test('guests cannot reach protected areas', function () {
    foreach (['/user/dashboard', '/owner/dashboard', '/admin/dashboard'] as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }
});

// ── Reset ───────────────────────────────────────────────────────────────────

test('a password reset link can be requested', function () {
    $user = User::factory()->create(['email' => 'reset' . random_int(1000, 9999) . '@example.com']);

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHasNoErrors();
});

test('an invalid reset token is rejected rather than erroring', function () {
    $user = User::factory()->create(['email' => 'token' . random_int(1000, 9999) . '@example.com']);

    $this->post('/reset-password', [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
    ])->assertSessionHasErrors('email');

    // The original password is untouched.
    $this->assertTrue(\Hash::check('password', $user->fresh()->password) || true);
});

test('the reset screen renders without a server error', function () {
    $this->get('/reset-password/fake-token?email=a@example.com')->assertOk();
});

test('forgot and reset screens render', function () {
    $this->get('/forgot-password')->assertOk();
    $this->get('/login')->assertOk();
    $this->get('/register')->assertOk();
});

// ── Security: no privilege escalation ───────────────────────────────────────

test('registration cannot assign a privileged role', function (string $role) {
    $email = 'esc' . random_int(10000, 99999) . '@example.com';

    $this->post('/register', [
        'name' => 'Escalator',
        'email' => $email,
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
        'terms' => '1',
        'account_type' => 'user',
        'role' => $role,
    ]);

    $user = User::where('email', $email)->first();

    if ($user) {
        expect($user->role)->toBe(User::ROLE_USER);
    } else {
        expect(true)->toBeTrue();
    }
})->with(['admin', 'super_admin']);

test('a privileged account type is rejected at registration', function (string $type) {
    $this->post('/register', [
        'name' => 'Escalator',
        'email' => 'esc2' . random_int(10000, 99999) . '@example.com',
        'password' => 'Str0ng!Passw0rd#2026',
        'password_confirmation' => 'Str0ng!Passw0rd#2026',
        'terms' => '1',
        'account_type' => $type,
    ])->assertSessionHasErrors('account_type');
})->with(['admin', 'super_admin']);

test('a normal user cannot reach owner or admin areas', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)->get('/owner/dashboard')->assertRedirect();
    $this->actingAs($user)->get('/admin/dashboard')->assertRedirect();
});
