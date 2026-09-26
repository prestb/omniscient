<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_owner_can_not_access_admin_dashboard()
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->get('/admin/dashboard');

        // ✅ CheckRole redirects unprivileged users instead of aborting
        $response->assertRedirect(route('owner.dashboard'));
    }

    public function test_owner_can_access_owner_dashboard()
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)->get('/owner/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_owner_dashboard()
    {
        // ✅ CheckRole grants admins full access across the app (by design)
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/owner/dashboard');

        $response->assertStatus(200);
    }



    public function test_super_admin_can_access_super_admin_dashboard()
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/super-dashboard');
        $response->assertStatus(200);
    }
}