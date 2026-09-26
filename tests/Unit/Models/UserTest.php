<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_user_is_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'owner']);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($superAdmin->isAdmin());
        $this->assertFalse($owner->isAdmin());
    }

    public function test_user_is_super_admin()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($admin->isSuperAdmin());
    }

    public function test_user_is_owner()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($owner->isOwner());
        $this->assertFalse($admin->isOwner());
    }

    public function test_user_is_active()
    {
        $active = User::factory()->create(['status' => 'active']);
        $pending = User::factory()->create(['status' => 'pending']);

        $this->assertTrue($active->isActive());
        $this->assertFalse($pending->isActive());
    }

    public function test_user_is_suspended()
    {
        $suspended = User::factory()->create(['status' => 'suspended']);
        $active = User::factory()->create(['status' => 'active']);

        $this->assertTrue($suspended->isSuspended());
        $this->assertFalse($active->isSuspended());
    }

    public function test_user_has_businesses_relationship()
    {
        $user = User::factory()->create();
        $business = \App\Models\Business::factory()->create(['owner_id' => $user->id]);

        $this->assertCount(1, $user->businesses);
        $this->assertEquals($business->id, $user->businesses->first()->id);
    }

    public function test_user_can_be_soft_deleted()
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}