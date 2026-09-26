<?php

use App\Models\User;

test('user can log in with valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

test('user cannot log in with wrong password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('login is rate limited after too many attempts', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
    }

    // 6th attempt should be throttled
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(429);
});