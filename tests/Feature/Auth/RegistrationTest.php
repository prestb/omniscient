<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_loads()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_user_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            // ✅ Password that isn't in HaveIBeenPwned
            'password' => 'Xk9$mPq2!wZr',
            'password_confirmation' => 'Xk9$mPq2!wZr',
            'account_type' => 'user',
            'terms' => true,
        ]);

        // If there are validation errors, dump them
        if ($response->status() === 302) {
            $errors = session('errors');
            if ($errors) {
                dump($errors->all());
            }
        }

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role' => 'user',
            'status' => 'active',
        ]);

        $response->assertRedirect();
    }

    public function test_registration_fails_with_invalid_data()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '1234',
            'terms' => false,
            'account_type' => 'user',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'terms']);
    }

    public function test_registration_fails_with_weak_password()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'phone' => '+237699123456',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['password']);
    }


}