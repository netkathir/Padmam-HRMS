<?php

namespace Tests\Feature;

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

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }
}
