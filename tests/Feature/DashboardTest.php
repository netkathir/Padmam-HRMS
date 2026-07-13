<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_accessible_for_authenticated_user(): void
    {
        $role = Role::create([
            'name' => 'test_role',
            'display_name' => 'Test Role',
            'description' => 'Test role',
            'is_active' => true,
        ]);

        // Dashboard FSD — the Overall Dashboard route is now permission-gated
        // (previously ungated), so a role must be explicitly granted
        // dashboard.read to reach it, same as every other module.
        Permission::syncModules();
        $dashboardRead = Permission::where('module', 'dashboard')->where('access_level', 'read')->first();
        $role->permissions()->sync([$dashboardRead->id]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user)->get('http://localhost/dashboard');

        $response->assertStatus(200);
    }
}
