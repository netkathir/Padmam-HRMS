<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchScopedUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeBranch(array $overrides = []): Branch
    {
        static $seq = 0;
        $seq++;

        return Branch::create(array_merge([
            'name' => "Branch $seq",
            'code' => "BR$seq",
            'is_active' => true,
        ], $overrides));
    }

    private function makeRole(string $name, array $overrides = []): Role
    {
        // branch_head/branch_user already exist — seeded by the
        // 2026_07_11_000005 migration that RefreshDatabase runs.
        if ($existing = Role::where('name', $name)->first()) {
            $existing->update($overrides);
            return $existing;
        }

        return Role::create(array_merge([
            'name' => $name,
            'display_name' => ucfirst($name),
            'is_active' => true,
        ], $overrides));
    }

    private function superAdmin(): User
    {
        $role = $this->makeRole('super_admin');

        return User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'username' => 'superadmin',
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);
    }

    private function branchHead(Branch $branch, Role $role): User
    {
        return User::create([
            'name' => 'Branch Head',
            'email' => 'head@example.com',
            'username' => 'branchhead',
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
            'user_type' => 'branch_head',
            'branch_id' => $branch->id,
        ]);
    }

    private function grantUsersFullAndManage(Role $role): void
    {
        Permission::syncModules();
        $perm = Permission::where('module', 'users')->where('access_level', 'full')->firstOrFail();
        $role->permissions()->syncWithoutDetaching([$perm->id => ['can_manage_users' => true]]);
    }

    public function test_super_admin_auto_resolves_to_first_active_branch_with_no_all_branches_option(): void
    {
        $inactive = $this->makeBranch(['is_active' => false]);
        $first = $this->makeBranch();
        $second = $this->makeBranch();

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get('http://localhost/users');
        $response->assertStatus(200);
        $response->assertDontSee('All Branches');

        $this->assertEquals($first->id, session('current_branch_id'));
    }

    public function test_super_admin_users_index_only_shows_currently_selected_branch(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();

        $admin = $this->superAdmin();

        $roleForUsers = $this->makeRole('branch_user', ['applicable_user_types' => ['branch_user']]);

        User::create([
            'name' => 'User In A', 'email' => 'a@example.com', 'username' => 'userA',
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => $roleForUsers->id, 'user_type' => 'branch_user', 'branch_id' => $branchA->id,
        ]);
        User::create([
            'name' => 'User In B', 'email' => 'b@example.com', 'username' => 'userB',
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => $roleForUsers->id, 'user_type' => 'branch_user', 'branch_id' => $branchB->id,
        ]);

        // Auto-resolves to branchA (first by id).
        $response = $this->actingAs($admin)->get('http://localhost/users');
        $response->assertSee('User In A');
        $response->assertDontSee('User In B');

        // Switch to branch B — now only branch B's user is visible.
        $this->actingAs($admin)->post('http://localhost/branch-admin/branch-switcher/switch', ['branch_id' => $branchB->id]);
        $response = $this->actingAs($admin)->get('http://localhost/users');
        $response->assertSee('User In B');
        $response->assertDontSee('User In A');
    }

    public function test_branch_head_can_create_user_for_own_branch(): void
    {
        $branch = $this->makeBranch();
        $headRole = $this->makeRole('branch_head', ['applicable_user_types' => ['branch_head']]);
        $this->grantUsersFullAndManage($headRole);
        $head = $this->branchHead($branch, $headRole);

        $targetRole = $this->makeRole('branch_user', ['applicable_user_types' => ['branch_user']]);

        $response = $this->actingAs($head)->post('http://localhost/users', [
            'name' => 'New Branch User',
            'email' => 'newuser@example.com',
            'username' => 'newbranchuser',
            'role_id' => $targetRole->id,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'branch_id' => $branch->id,
            'user_type' => 'branch_user',
        ]);
    }

    public function test_branch_head_cannot_assign_role_outside_permitted_scope(): void
    {
        $branch = $this->makeBranch();
        $headRole = $this->makeRole('branch_head', ['applicable_user_types' => ['branch_head']]);
        $this->grantUsersFullAndManage($headRole);
        $head = $this->branchHead($branch, $headRole);

        // A role NOT tagged applicable to branch_user (e.g. some other
        // operational role) must be rejected server-side even if posted
        // directly, not just hidden from the dropdown.
        $otherRole = $this->makeRole('some_other_role', ['applicable_user_types' => []]);

        $response = $this->actingAs($head)->post('http://localhost/users', [
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'username' => 'sneakyuser',
            'role_id' => $otherRole->id,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors('role_id');
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_branch_head_cannot_manage_user_in_another_branch(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $headRole = $this->makeRole('branch_head', ['applicable_user_types' => ['branch_head']]);
        $this->grantUsersFullAndManage($headRole);
        $head = $this->branchHead($branchA, $headRole);

        $otherRole = $this->makeRole('branch_user', ['applicable_user_types' => ['branch_user']]);
        $otherBranchUser = User::create([
            'name' => 'Other Branch User', 'email' => 'other@example.com', 'username' => 'otherbranchuser',
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => $otherRole->id, 'user_type' => 'branch_user', 'branch_id' => $branchB->id,
        ]);

        $this->actingAs($head)->get("http://localhost/users/{$otherBranchUser->id}/edit")->assertStatus(403);
    }

    public function test_super_admin_creating_branch_head_forces_currently_selected_branch(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $admin = $this->superAdmin();

        // Resolves current branch to branchA (first by id).
        $this->actingAs($admin)->get('http://localhost/users');

        $headRole = $this->makeRole('branch_head', ['applicable_user_types' => ['branch_head']]);

        // Attacker/careless client attempts to submit branch_id = branchB
        // directly — must be ignored in favor of the currently selected branch.
        $response = $this->actingAs($admin)->post('http://localhost/users', [
            'name' => 'New Head',
            'email' => 'newhead@example.com',
            'username' => 'newhead',
            'user_type' => 'branch_head',
            'branch_id' => $branchB->id,
            'role_id' => $headRole->id,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newhead@example.com',
            'branch_id' => $branchA->id,
        ]);
    }
}
